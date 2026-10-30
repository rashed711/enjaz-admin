<?php
/**
 * includes/client_backup.php - النسخ الاحتياطي اليومي لبيانات العملاء وتصديرها بصيغة Excel/CSV
 * نظام إنجاز للحلول الذكية
 */

require_once __DIR__ . '/functions.php';

/**
 * توليد ملف CSV كامل لبيانات العملاء مع التنسيق العربي والدعم الكامل لـ Microsoft Excel (UTF-8 BOM)
 */
function generateClientsCSV(?array $clientIds = null): string {
    $db = getDB();

    $where = "1=1";
    $params = [];
    if (!empty($clientIds)) {
        $in = implode(',', array_fill(0, count($clientIds), '?'));
        $where = "c.id IN ($in)";
        $params = array_values(array_map('intval', $clientIds));
    }

    $sql = "
        SELECT c.*,
               (SELECT COALESCE(SUM(cs.price), 0) FROM client_subscriptions cs WHERE cs.client_id = c.id AND cs.status != 'cancelled') AS total_services,
               (SELECT COALESCE(SUM(p.amount), 0) FROM payments p WHERE p.client_id = c.id) AS total_paid,
               (SELECT COUNT(*) FROM client_subscriptions cs WHERE cs.client_id = c.id) AS subs_count,
               (SELECT GROUP_CONCAT(DISTINCT CONCAT(s_all.name, IF(cs_all.plan_name IS NOT NULL AND cs_all.plan_name != '', CONCAT(' (', cs_all.plan_name, ')'), '')) SEPARATOR ' + ')
                FROM client_subscriptions cs_all 
                JOIN services s_all ON s_all.id = cs_all.service_id 
                WHERE cs_all.client_id = c.id AND cs_all.status != 'cancelled') AS all_services,
               (SELECT MIN(cs_ren.end_date) 
                FROM client_subscriptions cs_ren 
                WHERE cs_ren.client_id = c.id AND cs_ren.status = 'active' AND cs_ren.end_date >= CURDATE()) AS next_renewal,
               (SELECT MIN(cs_st.start_date) 
                FROM client_subscriptions cs_st 
                WHERE cs_st.client_id = c.id AND cs_st.status != 'cancelled') AS sub_start,
               (SELECT MAX(cs_en.end_date) 
                FROM client_subscriptions cs_en 
                WHERE cs_en.client_id = c.id AND cs_en.status != 'cancelled') AS sub_end,
               (CASE 
                  WHEN EXISTS (SELECT 1 FROM client_subscriptions cs_act WHERE cs_act.client_id = c.id AND cs_act.status = 'active') THEN 'نشط'
                  WHEN EXISTS (SELECT 1 FROM client_subscriptions cs_exp WHERE cs_exp.client_id = c.id AND cs_exp.status = 'expired') THEN 'منتهي'
                  WHEN EXISTS (SELECT 1 FROM client_subscriptions cs_any WHERE cs_any.client_id = c.id) THEN 'ملغي'
                  ELSE 'لا يوجد اشتراكات'
                END) AS sub_status_label
        FROM clients c
        WHERE {$where}
        ORDER BY c.id ASC
    ";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $fp = fopen('php://temp', 'r+');

    // ترويسة الأعمدة الشاملة لجميع بيانات العميل
    $headers = [
        'كود العميل',
        'اسم العميل',
        'اسم الشركة',
        'النشاط التجاري',
        'رقم الموبايل الأساسي',
        'رقم الموبايل الإضافي',
        'البريد الإلكتروني',
        'الدولة',
        'الدومين',
        'مزود الدومين',
        'اسم المستخدم / الاستضافة',
        'لوحة السيرفر',
        'العنوان',
        'حالة العميل',
        'عدد الاشتراكات',
        'الخدمات والخطط الحالية',
        'أقرب موعد تجديد',
        'تاريخ بدء الاشتراكات',
        'تاريخ انتهاء الاشتراكات',
        'حالة الاشتراكات',
        'إجمالي قيمة الخدمات',
        'إجمالي المدفوع',
        'المتبقي (المديونية)',
        'الملاحظات',
        'تاريخ التسجيل باللوحة'
    ];
    fputcsv($fp, $headers);

    foreach ($clients as $c) {
        $rem = (float)$c['total_services'] - (float)$c['total_paid'];
        $countryName = ($c['country'] === 'EG') ? 'مصر' : (($c['country'] === 'SA') ? 'السعودية' : ($c['country'] ?? ''));
        $clientStatus = ($c['status'] == 1) ? 'نشط' : 'موقوف';

        $row = [
            $c['id'],
            $c['name'],
            $c['company_name'] ?? '',
            $c['activity'] ?? '',
            $c['mobile'],
            $c['mobile_2'] ?? '',
            $c['email'] ?? '',
            $countryName,
            $c['domain'] ?? '',
            $c['domain_provider'] ?? '',
            $c['username_note'] ?? '',
            $c['server_panel'] ?? 'cp.enjaz.cloud',
            $c['address'] ?? '',
            $clientStatus,
            $c['subs_count'] ?? 0,
            $c['all_services'] ?? '—',
            $c['next_renewal'] ?? '—',
            $c['sub_start'] ?? '—',
            $c['sub_end'] ?? '—',
            $c['sub_status_label'] ?? '—',
            number_format((float)$c['total_services'], 2, '.', ''),
            number_format((float)$c['total_paid'], 2, '.', ''),
            number_format($rem, 2, '.', ''),
            preg_replace('/[\r\n]+/', ' ', $c['notes'] ?? ''),
            $c['created_at']
        ];
        fputcsv($fp, $row);
    }

    rewind($fp);
    $csv = stream_get_contents($fp);
    fclose($fp);

    // إضافة علامة UTF-8 BOM لفتح الملف مباشرة باللغة العربية في Microsoft Excel
    return "\xEF\xBB\xBF" . $csv;
}

/**
 * إرسال بريد إلكتروني يحتوي على الشيت الكامل كنسخة احتياطية
 */
function sendClientsBackupEmail(bool $isManual = false): array {
    try {
        $db = getDB();
        $recipientEmail = trim(getSetting('backup_email', ''));
        if (empty($recipientEmail)) {
            $recipientEmail = 'rashed1711@gmail.com';
        }

        if (empty($recipientEmail)) {
            return ['success' => false, 'message' => 'لم يتم تحديد بريد إلكتروني لاستلام النسخة الاحتياطية.'];
        }

        // إحصائيات سريعة للبريد
        $stats = $db->query("
            SELECT 
                COUNT(*) as total_clients,
                SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) as active_clients,
                SUM(CASE WHEN status = 0 THEN 1 ELSE 0 END) as suspended_clients
            FROM clients
        ")->fetch(PDO::FETCH_ASSOC);

        $finance = $db->query("
            SELECT 
                COALESCE(SUM(CASE WHEN cs.status != 'cancelled' THEN cs.price ELSE 0 END), 0) AS total_services,
                COALESCE((SELECT SUM(amount) FROM payments), 0) AS total_paid
            FROM client_subscriptions cs
        ")->fetch(PDO::FETCH_ASSOC);

        $totalClients = (int)($stats['total_clients'] ?? 0);
        $activeClients = (int)($stats['active_clients'] ?? 0);
        $suspendedClients = (int)($stats['suspended_clients'] ?? 0);
        $totalServices = (float)($finance['total_services'] ?? 0);
        $totalPaid = (float)($finance['total_paid'] ?? 0);
        $totalRemaining = $totalServices - $totalPaid;

        // توليد ملف CSV الكامل
        $csvContent = generateClientsCSV();
        $todayDate = date('Y-m-d');
        $currentTime = date('h:i A');
        $filename = "enjaz_clients_backup_{$todayDate}.csv";

        $subject = "نسخة احتياطية لبيانات العملاء - إنجاز للحلول الذكية ({$todayDate})";

        $modeText = $isManual ? 'نسخة احتياطية فورية (طلب يدوي)' : 'النسخة الاحتياطية اليومية المجدولة (الساعة 12:00 منتصف الليل)';

        $html = <<<HTML
<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
<meta charset="UTF-8">
<style>
  body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f8fafc; margin: 0; padding: 24px; color: #1e293b; }
  .card { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 16px rgba(0,0,0,0.06); border: 1px solid #e2e8f0; }
  .header { background: linear-gradient(135deg, #2456a4, #1a3f7a); color: #ffffff; padding: 28px 24px; text-align: center; }
  .header h1 { margin: 0 0 6px 0; font-size: 20px; font-weight: 800; }
  .header p { margin: 0; opacity: 0.9; font-size: 13px; }
  .body { padding: 24px; }
  .stats-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin: 18px 0; }
  .stat-box { background: #f1f5f9; border-radius: 8px; padding: 12px; text-align: center; }
  .stat-val { font-size: 18px; font-weight: 800; color: #2456a4; margin-top: 4px; }
  .stat-lbl { font-size: 11.5px; color: #64748b; font-weight: 600; }
  .badge { display: inline-block; padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 700; background: #e0f2fe; color: #0369a1; }
  .footer { background: #f8fafc; border-top: 1px solid #e2e8f0; padding: 16px; text-align: center; font-size: 12px; color: #94a3b8; }
</style>
</head>
<body>
  <div class="card">
    <div class="header">
      <h1>إنجاز للحلول الذكية</h1>
      <p>{$modeText}</p>
    </div>
    <div class="body">
      <p style="font-size: 14px; line-height: 1.6; margin-top: 0;">
        السلام عليكم ورحمة الله وبركاته،<br>
        مرفق مع هذه الرسالة ملف <strong>Excel (CSV)</strong> كامل ومفصل يضم جميع بيانات العملاء واشتراكاتهم ومتابعاتهم المالية حتى تاريخ <strong>{$todayDate}</strong> الساعة <strong>{$currentTime}</strong>.
      </p>

      <div class="stats-grid">
        <div class="stat-box">
          <div class="stat-lbl">إجمالي العملاء المسجلين</div>
          <div class="stat-val">{$totalClients}</div>
        </div>
        <div class="stat-box">
          <div class="stat-lbl">العملاء النشطين</div>
          <div class="stat-val" style="color: #10b981;">{$activeClients}</div>
        </div>
        <div class="stat-box">
          <div class="stat-lbl">إجمالي قيمة الخدمات</div>
          <div class="stat-val">{$totalServices}</div>
        </div>
        <div class="stat-box">
          <div class="stat-lbl">إجمالي المديونيات المتبقية</div>
          <div class="stat-val" style="color: #ef4444;">{$totalRemaining}</div>
        </div>
      </div>

      <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 12px; font-size: 12.5px; color: #166534; margin-top: 16px;">
        📎 <strong>الملف المرفق:</strong> <code>{$filename}</code><br>
        تم حفظ وتنسيق الملف بترميز UTF-8 مع BOM لفتحه مباشرة في Microsoft Excel بشكل صحيح دون أي مشاكل في الخطوط العربية.
      </div>
    </div>
    <div class="footer">
      نظام إدارة العملاء والاشتراكات — إنجاز للحلول الذكية © {$todayDate}
    </div>
  </div>
</body>
</html>
HTML;

        $sent = sendSMTPMailWithAttachment($recipientEmail, $subject, $html, $csvContent, $filename);

        if ($sent) {
            try {
                $db->prepare("UPDATE settings SET value = ? WHERE `key` = 'backup_clients_last_date'")->execute([$todayDate]);
            } catch (Exception $e) {}
            return [
                'success'   => true,
                'message'   => "تم إرسال النسخة الاحتياطية بنجاح إلى البريد ({$recipientEmail}).",
                'recipient' => $recipientEmail,
                'count'     => $totalClients
            ];
        } else {
            global $lastMailLog;
            $details = !empty($lastMailLog) ? ' [' . implode(' | ', $lastMailLog) . ']' : '';
            return [
                'success'   => false,
                'message'   => "تعذر إرسال البريد إلى ({$recipientEmail}){$details}، يرجى مراجعة إعدادات خادم البريد.",
                'recipient' => $recipientEmail
            ];
        }
    } catch (\Throwable $e) {
        error_log('[Client Backup Exception] ' . $e->getMessage());
        return [
            'success' => false,
            'message' => 'حدث خطأ أثناء إعداد النسخة الاحتياطية: ' . $e->getMessage()
        ];
    }
}

/**
 * فحص وتشغيل النسخة الاحتياطية اليومية تلقائياً عند منتصف الليل
 */
function runDailyClientsEmailBackup(bool $force = false): array {
    $enabled = (getSetting('backup_clients_enabled', '1') === '1');
    if (!$enabled && !$force) {
        return ['skipped' => true, 'reason' => 'Daily client backup is disabled in settings'];
    }

    $lastDate = getSetting('backup_clients_last_date', '');
    $todayDate = date('Y-m-d');

    if (!$force && $lastDate === $todayDate) {
        return ['skipped' => true, 'reason' => 'Backup already sent today: ' . $todayDate];
    }

    $backupHour = getSetting('backup_clients_hour', '00:00');
    $currentHour = date('H:i');

    // إذا لم يكن تشغيلاً إجبارياً، نفحص تطابق الساعة (مثلاً 00)
    if (!$force) {
        $targetHourNum = (int)explode(':', $backupHour)[0];
        $currentHourNum = (int)date('H');
        if ($currentHourNum !== $targetHourNum) {
            return ['skipped' => true, 'reason' => "Current hour ($currentHourNum) != target hour ($targetHourNum)"];
        }
    }

    return sendClientsBackupEmail($force);
}

