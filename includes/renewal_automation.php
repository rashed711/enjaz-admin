<?php
/**
 * includes/renewal_automation.php
 * محرك التنبيهات والجدولة التلقائية للتجديدات وإشعارات الواتساب
 * نظام إنجاز للحلول الذكية
 */

require_once __DIR__ . '/../config/app.php';

/**
 * التأكد الذاتي من وجود جدول renewal_notifications وجميع أعمدته
 */
function ensureRenewalTableExists(): void {
    static $ensured = false;
    if ($ensured) return;
    try {
        $db = getDB();
        $db->exec("
            CREATE TABLE IF NOT EXISTS `renewal_notifications` (
              `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
              `client_id` INT UNSIGNED NOT NULL,
              `cycle_end_date` DATE NOT NULL,
              `stage` INT NOT NULL COMMENT '60, 45, 30, 15, 7',
              `status` VARCHAR(20) NOT NULL DEFAULT 'pending_review' COMMENT 'pending_review, approved, sent, skipped, failed',
              `message` TEXT NOT NULL,
              `admin_notified_at` DATETIME NULL,
              `reviewed_at` DATETIME NULL,
              `reviewed_by` INT UNSIGNED NULL,
              `sent_at` DATETIME NULL,
              `response` TEXT NULL,
              `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
              `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              KEY `idx_client_cycle_stage` (`client_id`, `cycle_end_date`, `stage`),
              KEY `idx_status` (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        $desc = $db->query("SHOW COLUMNS FROM `renewal_notifications` LIKE 'updated_at'")->fetch();
        if (!$desc) {
            $db->exec("ALTER TABLE `renewal_notifications` ADD COLUMN `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;");
        }
    } catch (Exception $e) {}
    $ensured = true;
}

// استدعاء التأكد الذاتي من الجدول فور تحميل الملف
ensureRenewalTableExists();

/**
 * إرسال رسالة واتساب مباشرة وتسجيلها في السجلات
 */
function sendWhatsappMessageDirect(string $mobile, string $message, ?int $clientId = null, string $msgType = 'renewal'): array {
    $db = getDB();
    $mobile = normalizeMobile($mobile);

    $apiUrl    = getSetting('whatsapp_api_url', '');
    $apiToken  = getSetting('whatsapp_api_token', '');
    $sessionId = getSetting('whatsapp_sender', '');

    $status   = 'failed';
    $response = '';

    if (!empty($apiUrl) && !empty($apiToken) && !empty($sessionId)) {
        $endpoint = rtrim($apiUrl, '/');
        if (strpos($endpoint, '/api/sessions') === false) {
            $endpoint = $endpoint . '/api/sessions/' . $sessionId . '/messages';
        }

        $payload = json_encode([
            'to'   => $mobile,
            'text' => $message,
        ]);

        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'x-api-key: ' . $apiToken
            ]
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $status = ($httpCode >= 200 && $httpCode < 300) ? 'sent' : 'failed';
    } else {
        $status   = 'sent'; // الوضع التجريبي عند عدم تكوين البوابة
        $response = 'API not configured — logged only';
    }

    // تسجيل في whatsapp_logs
    try {
        $stmt = $db->prepare("INSERT INTO whatsapp_logs (client_id, mobile, message, msg_type, status, response, sent_by, sent_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$clientId ?: null, $mobile, $message, $msgType, $status, $response, currentUserId() ?: null]);
    } catch (Exception $e) {}

    return [
        'success'  => ($status === 'sent'),
        'status'   => $status,
        'response' => $response,
        'mobile'   => $mobile
    ];
}

/**
 * جلب قائمة العملاء الذين ينتهي اشتراكهم غداً (متبقي يوم واحد فقط)
 * لإشعار الإدمن بمتابعتهم أو إيقاف الخدمة في حال عدم السداد
 */
function getExpiringTomorrowClients(): array {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT c.id as client_id, c.name as client_name, c.mobile, c.company_name, c.country, c.domain as client_domain,
               cs.id as sub_id, cs.plan_name, cs.price, cs.currency, cs.end_date,
               s.name as service_name,
               DATEDIFF(cs.end_date, CURDATE()) as days_left
        FROM client_subscriptions cs
        JOIN clients c ON c.id = cs.client_id
        JOIN services s ON s.id = cs.service_id
        WHERE cs.status = 'active'
          AND cs.end_date IS NOT NULL
          AND DATEDIFF(cs.end_date, CURDATE()) = 1
        ORDER BY c.name ASC
    ");
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $grouped = [];
    foreach ($rows as $r) {
        $cId = $r['client_id'];
        if (!isset($grouped[$cId])) {
            $grouped[$cId] = [
                'client_id'     => $cId,
                'client_name'   => $r['client_name'],
                'mobile'        => $r['mobile'],
                'company_name'  => $r['company_name'] ?? '',
                'country'       => $r['country'] ?? 'EG',
                'client_domain' => $r['client_domain'] ?? '',
                'end_date'      => $r['end_date'],
                'days_left'     => 1,
                'services'      => []
            ];
        }
        $grouped[$cId]['services'][] = $r['service_name'] . (!empty($r['plan_name']) ? " ({$r['plan_name']})" : "");
    }
    return array_values($grouped);
}

/**
 * جلب وتحديد العملاء المؤهلين لإرسال إشعارات التجديد (مراحل: 60، 45، 30، 15، 7)
 * مع تطبيق قاعدة الاستثناء الذكية:
 * إذا تم اعتماد إشعار الـ 60 يوم لنفس دورة الاشتراك، يتم تخطي (45، 30، 15) تماماً ولا يظهر العميل إلا عند الـ 7 أيام
 *
 * @param int $daysAhead عدد الأيام للأمام (1 تعني فحص المستحقين للإرسال غداً، 0 تعني فحص اليوم)
 */
function getRenewalCandidates(int $daysAhead = 1): array {
    $db = getDB();
    $companyName = getSetting('company_name', APP_NAME);

    // جلب جميع الاشتراكات النشطة التي تنتهي خلال 65 يوماً من التاريخ المستهدف
    $daysAheadInt = (int)$daysAhead;
    $stmt = $db->prepare("
        SELECT c.id as client_id, c.name as client_name, c.mobile, c.company_name, c.country, c.server_panel, c.domain as client_domain,
               cs.id as sub_id, cs.service_id, cs.plan_name, cs.currency, cs.original_price, cs.price, cs.end_date,
               s.name as service_name,
               DATEDIFF(cs.end_date, DATE_ADD(CURDATE(), INTERVAL {$daysAheadInt} DAY)) as days_left
        FROM client_subscriptions cs
        JOIN clients c ON c.id = cs.client_id
        JOIN services s ON s.id = cs.service_id
        WHERE cs.status = 'active'
          AND cs.end_date IS NOT NULL
          AND DATEDIFF(cs.end_date, DATE_ADD(CURDATE(), INTERVAL {$daysAheadInt} DAY)) BETWEEN 0 AND 60
          AND c.mobile IS NOT NULL AND c.mobile != ''
        ORDER BY cs.end_date ASC
    ");
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($rows)) {
        return [];
    }

    // تجميع الاشتراكات لكل عميل
    $clients = [];
    foreach ($rows as $r) {
        $cId = $r['client_id'];
        if (!isset($clients[$cId])) {
            $clients[$cId] = [
                'id'            => $cId,
                'name'          => $r['client_name'],
                'mobile'        => $r['mobile'],
                'company_name'  => $r['company_name'] ?? '',
                'country'       => $r['country'] ?? 'EG',
                'server_panel'  => $r['server_panel'] ?? '',
                'client_domain' => $r['client_domain'] ?? '',
                'domain'        => $r['client_domain'] ?? '',
                'min_end_date'  => $r['end_date'],
                'min_days_left' => (int)$r['days_left'],
                'subscriptions' => []
            ];
        }
        if ((int)$r['days_left'] < $clients[$cId]['min_days_left']) {
            $clients[$cId]['min_days_left'] = (int)$r['days_left'];
            $clients[$cId]['min_end_date']  = $r['end_date'];
        }
        $clients[$cId]['subscriptions'][] = $r;
    }

    $candidates = [];

    foreach ($clients as $cId => $cData) {
        $daysLeft = $cData['min_days_left'];
        $cycleEndDate = $cData['min_end_date'];

        // تحديد المرحلة المناسبة بدقة عند بلوغ المحطة الزمنية المحددة (نافذة 2 يوم كحد أقصى)
        $stage = null;
        if ($daysLeft <= 60 && $daysLeft >= 58) {
            $stage = 60;
        } elseif ($daysLeft <= 45 && $daysLeft >= 43) {
            $stage = 45;
        } elseif ($daysLeft <= 30 && $daysLeft >= 28) {
            $stage = 30;
        } elseif ($daysLeft <= 15 && $daysLeft >= 13) {
            $stage = 15;
        } elseif ($daysLeft <= 7 && $daysLeft >= 0) {
            $stage = 7;
        }

        if ($stage === null) {
            continue; // العميل بين مرحلتين، ينتظر النظام حتى يصل لمحطته القادمة
        }

        // فحص قاعدة الاستثناء الذكية:
        // هل تم اعتماد أو إرسال إشعار الـ 60 يوم لهذا العميل لنفس دورة تاريخ الانتهاء؟
        $chk60 = $db->prepare("
            SELECT COUNT(*) FROM renewal_notifications
            WHERE client_id = ?
              AND cycle_end_date = ?
              AND stage = 60
              AND status IN ('approved', 'sent')
        ");
        $chk60->execute([$cId, $cycleEndDate]);
        $has60Approved = ((int)$chk60->fetchColumn() > 0);

        if ($has60Approved) {
            // إذا كان قد اعتمد في الـ 60 يوم، يتم استبعاد مراحل 45 و 30 و 15 تماماً
            if ($daysLeft > 7) {
                continue; // تخطي حتى يصل إلى 7 أيام
            }
            // إذا وصل إلى 7 أيام أو أقل ولم يجدد، يستمر في مرحلة الـ 7
            $stage = 7;
        }

        // التحقق هل تم إنشاء إشعار لهذه المرحلة بالذات مسبقاً لنفس دورة التجديد
        $chkStage = $db->prepare("
            SELECT id, status, message, admin_notified_at
            FROM renewal_notifications
            WHERE client_id = ?
              AND cycle_end_date = ?
              AND stage = ?
            LIMIT 1
        ");
        $chkStage->execute([$cId, $cycleEndDate, $stage]);
        $existing = $chkStage->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            // إذا تم إرساله أو تخطيه بالفعل، لا نعيد إدراجه
            if (in_array($existing['status'], ['sent', 'skipped'])) {
                continue;
            }
            // إذا كان معتمداً أو قيد المراجعة، ندرجه كمرشح موجود مسبقاً
            $candidates[] = [
                'notification_id' => (int)$existing['id'],
                'client_id'       => $cId,
                'name'            => $cData['name'],
                'company_name'    => $cData['company_name'],
                'mobile'          => $cData['mobile'],
                'cycle_end_date'  => $cycleEndDate,
                'days_left'       => $daysLeft,
                'stage'           => $stage,
                'status'          => $existing['status'],
                'message'         => $existing['message'],
                'is_existing'     => true,
                'subscriptions'   => $cData['subscriptions']
            ];
            continue;
        }

        // صياغة الرسالة الذكية للعميل متضمنة المرحلة والأيام المتبقية بدقة
        $build = buildRenewalWhatsappMessage($cData, $cData['subscriptions'], $companyName, $stage);

        $candidates[] = [
            'notification_id' => 0,
            'client_id'       => $cId,
            'name'            => $cData['name'],
            'company_name'    => $cData['company_name'],
            'mobile'          => $cData['mobile'],
            'cycle_end_date'  => $cycleEndDate,
            'days_left'       => $daysLeft,
            'stage'           => $stage,
            'status'          => 'pending_review',
            'message'         => $build['message'],
            'due_date'        => $build['due_date'],
            'total_formatted' => $build['total_formatted'],
            'is_existing'     => false,
            'subscriptions'   => $cData['subscriptions']
        ];
    }

    return $candidates;
}

/**
 * مزامنة المرشحين الجدد في جدول renewal_notifications
 */
function syncCandidatesToDatabase(array &$candidates, bool $isAutoReview): array {
    $db = getDB();
    $newCount = 0;

    foreach ($candidates as &$c) {
        if (!empty($c['is_existing']) && !empty($c['notification_id'])) {
            continue;
        }

        $initStatus = $isAutoReview ? 'pending_review' : 'approved';
        $stmt = $db->prepare("
            INSERT INTO renewal_notifications
            (client_id, cycle_end_date, stage, status, message, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $c['client_id'],
            $c['cycle_end_date'],
            $c['stage'],
            $initStatus,
            $c['message']
        ]);
        $c['notification_id'] = (int)$db->lastInsertId();
        $c['status'] = $initStatus;
        $newCount++;
    }

    return ['new_count' => $newCount];
}

/**
 * إرسال ملخص واتساب لرقم الإدمن (01028855779)
 * يحتوي على العملاء المقرر إرسال رسائل لهم غداً، بالإضافة للعملاء الذين ينتهي اشتراكهم غداً
 */
function sendAdminDigestNotification(array $candidates, array $expiringTomorrow, bool $isAutoReview): array {
    $adminMobile = getSetting('renewal_admin_mobile', '201028855779');
    $db = getDB();

    if (empty($adminMobile)) {
        return ['success' => false, 'message' => 'رقم واتساب الإدمن غير محدد في الإعدادات.'];
    }

    $lines = [];

    if ($isAutoReview) {
        // وضع المراجعة مفعل: يصل ملخص بالمرشحين للمراجعة
        $lines[] = "🔔 *إنجاز للحلول الذكية - تذكير التجديدات اليومي*";
        $lines[] = "عزيزي المدير، إليك ملخص الاشتراكات والتجديدات المستحقة:";
        $lines[] = "";

        if (!empty($candidates)) {
            $lines[] = "📋 *إشعارات مجدولة للمراجعة والإرسال غداً:* (" . count($candidates) . " عميل)";
            $i = 1;
            foreach ($candidates as $c) {
                $comp = !empty($c['company_name']) ? " ({$c['company_name']})" : "";
                $lines[] = "{$i}. *{$c['name']}*{$comp}";
                $lines[] = "   • المرحلة: *قبل {$c['stage']} يوم* | ينتهي: {$c['cycle_end_date']}";
                $i++;
            }
            $lines[] = "";
        } else {
            $lines[] = "✅ لا توجد إشعارات تجديد جديدة مجدولة للغد.";
            $lines[] = "";
        }

        if (!empty($expiringTomorrow)) {
            $lines[] = "⚠️ *تنبيه عاجل: عملاء ينتهي اشتراكهم غداً (متبقي يوم واحد):* (" . count($expiringTomorrow) . " عميل)";
            $j = 1;
            foreach ($expiringTomorrow as $exp) {
                $dom = !empty($exp['client_domain']) ? " - " . $exp['client_domain'] : "";
                $lines[] = "{$j}. *{$exp['client_name']}*{$dom} (هاتف: {$exp['mobile']})";
                $j++;
            }
            $lines[] = "   _(يرجى المتابعة العاجلة أو إيقاف الخدمة في حال عدم السداد)_";
            $lines[] = "";
        }

        $lines[] = "🔗 *رابط مراجعة واعتماد الرسائل:*";
        $lines[] = "https://admin.enjaz.cloud/reports/renewal_reviews.php";
    } else {
        // وضع الإرسال المباشر مفعل: الإدمن يتلقى فقط تنبيهات الـ 7 أيام ومن ينتهي اشتراكه غداً
        $stage7List = array_filter($candidates, fn($c) => (int)$c['stage'] === 7);

        // إذا لم يكن هناك عملاء 7 أيام ولا من ينتهي غداً، لا داعي لإزعاج الإدمن
        if (empty($stage7List) && empty($expiringTomorrow)) {
            return ['success' => true, 'message' => 'لا توجد تنبيهات عاجلة للإدمن اليوم.'];
        }

        $lines[] = "🔔 *إنجاز للحلول الذكية - تنبيهات التجديد العاجلة*";
        $lines[] = "";

        if (!empty($expiringTomorrow)) {
            $lines[] = "⚠️ *عملاء ينتهي اشتراكهم غداً (متبقي يوم واحد):* (" . count($expiringTomorrow) . " عميل)";
            $j = 1;
            foreach ($expiringTomorrow as $exp) {
                $dom = !empty($exp['client_domain']) ? " - " . $exp['client_domain'] : "";
                $lines[] = "{$j}. *{$exp['client_name']}*{$dom} (هاتف: {$exp['mobile']})";
                $j++;
            }
            $lines[] = "   _(يرجى المتابعة أو إيقاف الخدمة في حال عدم السداد)_";
            $lines[] = "";
        }

        if (!empty($stage7List)) {
            $lines[] = "⏳ *عملاء في مرحلة الـ 7 أيام الأخيرة (تم إرسال إشعار التجديد لهم):* (" . count($stage7List) . " عميل)";
            $i = 1;
            foreach ($stage7List as $c) {
                $lines[] = "{$i}. *{$c['name']}* (تاريخ الانتهاء: {$c['cycle_end_date']})";
                $i++;
            }
            $lines[] = "";
        }

        $lines[] = "🔗 متابعة تقرير التجديدات: https://admin.enjaz.cloud/reports/renewals.php";
    }

    $digestMessage = implode("\n", $lines);
    $res = sendWhatsappMessageDirect($adminMobile, $digestMessage, null, 'renewal_admin_alert');

    // تحديث تاريخ تنبيه الإدمن للمرشحين
    if ($res['success'] && !empty($candidates)) {
        $notifIds = array_filter(array_column($candidates, 'notification_id'));
        if (!empty($notifIds)) {
            $placeholders = implode(',', array_fill(0, count($notifIds), '?'));
            $db->prepare("UPDATE renewal_notifications SET admin_notified_at = NOW() WHERE id IN ($placeholders)")->execute($notifIds);
        }
    }

    return $res;
}

/**
 * إرسال الرسائل المعتمدة في جدول renewal_notifications
 */
function dispatchApprovedRenewalMessages(): array {
    $db = getDB();

    $stmt = $db->query("
        SELECT rn.*, c.name as client_name, c.mobile
        FROM renewal_notifications rn
        JOIN clients c ON c.id = rn.client_id
        WHERE rn.status = 'approved'
          AND CURDATE() >= DATE_SUB(rn.cycle_end_date, INTERVAL rn.stage DAY)
        ORDER BY rn.id ASC
    ");
    $approvedList = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($approvedList)) {
        return ['count' => 0, 'sent' => 0, 'failed' => 0];
    }

    $sentCount   = 0;
    $failedCount = 0;

    foreach ($approvedList as $item) {
        $res = sendWhatsappMessageDirect($item['mobile'], $item['message'], (int)$item['client_id'], 'renewal');

        if ($res['success']) {
            $db->prepare("UPDATE renewal_notifications SET status = 'sent', sent_at = NOW(), response = ? WHERE id = ?")
               ->execute([$res['response'], $item['id']]);
            $sentCount++;
        } else {
            $db->prepare("UPDATE renewal_notifications SET status = 'failed', response = ? WHERE id = ?")
               ->execute([$res['response'], $item['id']]);
            $failedCount++;
        }

        // تأخير عشوائي آمن بين الرسائل لمنع الحظر وتقليل الحمل
        sleep(rand(3, 7));
    }

    return [
        'count'  => count($approvedList),
        'sent'   => $sentCount,
        'failed' => $failedCount
    ];
}

/**
 * إرسال رسائل التجديد المعتمدة للعملاء في موعد الإرسال الصباحي (الافتراضي 11:00 صباحاً)
 * يفحص التوقيت وحالة الرسائل وتواريخ استحقاق المراحل
 */
function runDailyRenewalDispatch(bool $isManual = false): array {
    $db = getDB();
    $sendHour     = getSetting('renewal_send_hour', '11:00');
    $lastDispatch = getSetting('renewal_last_dispatch_date', '');
    $todayDate    = date('Y-m-d');
    $currentTime  = date('H:i');

    if (!$isManual) {
        // تأكد أنه لم ينفذ الإرسال الصباحي اليوم بالفعل
        if (!empty($lastDispatch) && substr($lastDispatch, 0, 10) === $todayDate) {
            return [
                'skipped' => true,
                'reason'  => 'تم تنفيذ الإرسال الصباحي للتجديدات المعتمدة اليوم بالفعل بتاريخ: ' . $lastDispatch
            ];
        }

        // إذا لم يحن موعد الإرسال الصباحي المحدد بعد
        if ($currentTime < $sendHour) {
            return [
                'skipped' => true,
                'reason'  => "لم يحن موعد الإرسال الصباحي بعد (الموعد المحدد: {$sendHour}، الوقت الحالي: {$currentTime})"
            ];
        }
    }

    // إرسال الرسائل المعتمدة التي حان موعد استحقاقها اليوم أو سابقاً
    $dispatchResult = dispatchApprovedRenewalMessages();

    // تحديث تاريخ آخر إرسال صباحي
    try {
        $chk = $db->prepare("SELECT COUNT(*) FROM settings WHERE `key` = 'renewal_last_dispatch_date'");
        $chk->execute();
        if ((int)$chk->fetchColumn() === 0) {
            $db->prepare("INSERT INTO settings (`key`, `value`) VALUES ('renewal_last_dispatch_date', ?)")->execute([date('Y-m-d H:i:s')]);
        } else {
            $db->prepare("UPDATE settings SET value = ? WHERE `key` = 'renewal_last_dispatch_date'")->execute([date('Y-m-d H:i:s')]);
        }
    } catch (Exception $e) {}

    return [
        'skipped'         => false,
        'dispatch_result' => $dispatchResult
    ];
}

/**
 * تشغيل دورة الفحص الكاملة (تنفذ عبر الـ Cron Job أو يدوياً عبر زر الفحص)
 *
 * @param bool $isManual إذا كان تشغيلاً يدوياً يتم تنفيذه فوراً متجاوزاً فحص التوقيت اليومي
 */
function runRenewalDailyRoutine(bool $isManual = false): array {
    $db = getDB();
    $isAutoReview = (getSetting('renewal_auto_review', '1') === '1');
    $alertHour    = getSetting('renewal_alert_hour', '20:00');
    $lastCheck    = getSetting('renewal_last_check_date', '');
    $todayDate    = date('Y-m-d');
    $currentTime  = date('H:i');

    // إذا كان التشغيل تلقائياً (عبر الكرون)، نتأكد أنه لم ينفذ اليوم بالفعل، وأنه قد حان الوقت المحدد
    if (!$isManual) {
        if (!empty($lastCheck) && substr($lastCheck, 0, 10) === $todayDate) {
            return [
                'skipped' => true,
                'reason'  => 'تم فحص وإرسال تنبيهات التجديد اليوم بالفعل بتاريخ: ' . $lastCheck
            ];
        }

        // إذا لم يحن موعد الإرسال اليومي المحدد بعد
        if ($currentTime < $alertHour) {
            return [
                'skipped' => true,
                'reason'  => "لم يحن موعد التنبيه اليومي بعد (الموعد المحدد: {$alertHour}، الوقت الحالي: {$currentTime})"
            ];
        }
    }

    // 1. جلب عملاء الغد للتذكيرات (مراحل: 60, 45, 30, 15, 7)
    $candidates = getRenewalCandidates(1);

    // 2. جلب العملاء الذين ينتهي اشتراكهم غداً (متبقي يوم واحد)
    $expiringTomorrow = getExpiringTomorrowClients();

    // 3. مزامنة المرشحين في جدول renewal_notifications
    syncCandidatesToDatabase($candidates, $isAutoReview);

    // 4. إرسال تنبيه للإدمن على الواتساب
    $adminResult = ['success' => false, 'message' => 'لم يتم إرسال تنبيه'];
    if (!empty($candidates) || !empty($expiringTomorrow) || $isManual) {
        $adminResult = sendAdminDigestNotification($candidates, $expiringTomorrow, $isAutoReview);
    }

    // 5. إذا كان وضع الإرسال التلقائي المباشر مفعلاً (المراجعة = 0)، نقوم بإرسال الرسائل المعتمدة
    $dispatchResult = ['count' => 0, 'sent' => 0, 'failed' => 0];
    if (!$isAutoReview) {
        $dispatchResult = dispatchApprovedRenewalMessages();
    }

    // تحديث تاريخ آخر فحص
    try {
        $chk = $db->prepare("SELECT COUNT(*) FROM settings WHERE `key` = 'renewal_last_check_date'");
        $chk->execute();
        if ((int)$chk->fetchColumn() === 0) {
            $db->prepare("INSERT INTO settings (`key`, `value`) VALUES ('renewal_last_check_date', ?)")->execute([date('Y-m-d H:i:s')]);
        } else {
            $db->prepare("UPDATE settings SET value = ? WHERE `key` = 'renewal_last_check_date'")->execute([date('Y-m-d H:i:s')]);
        }
    } catch (Exception $e) {}

    return [
        'skipped'            => false,
        'is_review_mode'     => $isAutoReview,
        'candidates_count'   => count($candidates),
        'expiring_tomorrow'  => count($expiringTomorrow),
        'admin_notification' => $adminResult,
        'dispatch_result'    => $dispatchResult
    ];
}
