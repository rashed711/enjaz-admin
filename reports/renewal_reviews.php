<?php
/**
 * reports/renewal_reviews.php - مراجعة واعتماد إشعارات التجديد التلقائية
 * نظام إنجاز للحلول الذكية
 */
require_once dirname(__DIR__) . '/config/app.php';
requireLogin();
requirePermission('send_whatsapp');
require_once INCLUDES_PATH . '/renewal_automation.php';

$db = getDB();

// إعادة التوجيه التلقائي لمركز التجديدات الموحد
header("Location: renewals_hub.php");
exit;
$isAutoReview = (getSetting('renewal_auto_review', '1') === '1');
$adminMobile  = getSetting('renewal_admin_mobile', '201028855779');

// ── معالجة إجراءات الـ POST ─────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) {
        setFlash('error', 'خطأ في التحقق من الأمان.');
        header('Location: renewal_reviews.php');
        exit;
    }

    $action = clean($_POST['action'] ?? '');

    // 1. تشغيل الفحص اليدوي فوراً
    if ($action === 'run_check') {
        $routine = runRenewalDailyRoutine(true);
        $found = $routine['candidates_count'] ?? 0;
        $expiring = $routine['expiring_tomorrow'] ?? 0;
        $adminStatus = ($routine['admin_notification']['status'] ?? '') === 'sent' ? 'وتم إرسال ملخص لواتساب الإدمن بنجاح.' : '';
        setFlash('success', "تم فحص التجديدات بنجاح! تم رصد ({$found}) إشعار تجديد، و ({$expiring}) عميل ينتهي اشتراكه غداً. {$adminStatus}");
        header('Location: renewal_reviews.php');
        exit;
    }

    // 2. اعتماد وإرسال إشعار عميل مفرد فوراً
    if ($action === 'send_single') {
        $notifId = (int)($_POST['notification_id'] ?? 0);
        $customMessage = trim($_POST['message'] ?? '');

        $stmt = $db->prepare("
            SELECT rn.*, c.mobile, c.name as client_name
            FROM renewal_notifications rn
            JOIN clients c ON c.id = rn.client_id
            WHERE rn.id = ?
        ");
        $stmt->execute([$notifId]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($item) {
            $msgToSend = !empty($customMessage) ? $customMessage : $item['message'];
            $res = sendWhatsappMessageDirect($item['mobile'], $msgToSend, (int)$item['client_id'], 'renewal');

            if ($res['success']) {
                $db->prepare("
                    UPDATE renewal_notifications 
                    SET message = ?, status = 'sent', reviewed_at = NOW(), reviewed_by = ?, sent_at = NOW(), response = ?
                    WHERE id = ?
                ")->execute([$msgToSend, currentUserId(), $res['response'], $notifId]);
                setFlash('success', "تم إرسال إشعار التجديد بنجاح للعميل: {$item['client_name']}");
            } else {
                $db->prepare("
                    UPDATE renewal_notifications 
                    SET message = ?, status = 'failed', reviewed_at = NOW(), reviewed_by = ?, response = ?
                    WHERE id = ?
                ")->execute([$msgToSend, currentUserId(), $res['response'], $notifId]);
                setFlash('error', "فشل إرسال الإشعار للعميل ({$item['client_name']}): " . e($res['response']));
            }
        }
        header('Location: renewal_reviews.php');
        exit;
    }

    // 3. اعتماد للجدولة الصباحية فقط
    if ($action === 'approve_single') {
        $notifId = (int)($_POST['notification_id'] ?? 0);
        $customMessage = trim($_POST['message'] ?? '');
        $stmt = $db->prepare("UPDATE renewal_notifications SET message = COALESCE(NULLIF(?, ''), message), status = 'approved', reviewed_at = NOW(), reviewed_by = ? WHERE id = ?");
        $stmt->execute([$customMessage, currentUserId(), $notifId]);
        setFlash('success', 'تم اعتماد الرسالة، وسيتم إرسالها تلقائياً في موعد الإرسال الصباحي.');
        header('Location: renewal_reviews.php');
        exit;
    }

    // 4. تخطي العميل (عدم الإرسال لهذه المرحلة)
    if ($action === 'skip_single') {
        $notifId = (int)($_POST['notification_id'] ?? 0);
        $stmt = $db->prepare("UPDATE renewal_notifications SET status = 'skipped', reviewed_at = NOW(), reviewed_by = ? WHERE id = ?");
        $stmt->execute([currentUserId(), $notifId]);
        setFlash('info', 'تم تخطي هذا الإشعار.');
        header('Location: renewal_reviews.php');
        exit;
    }

    // 5. اعتماد وجدولة الرسائل المحددة للإرسال الصباحي المجدول (مع حفظ أي تعديلات على النصوص)
    if ($action === 'bulk_approve') {
        $selectedIds = $_POST['selected_ids'] ?? [];
        if (empty($selectedIds)) {
            setFlash('error', 'يرجى تحديد إشعار واحد على الأقل.');
            header('Location: renewal_reviews.php?tab=pending');
            exit;
        }

        $approvedCount = 0;
        foreach ($selectedIds as $id) {
            $id = (int)$id;
            $customMsg = trim($_POST["message_{$id}"] ?? '');
            $stmt = $db->prepare("
                UPDATE renewal_notifications 
                SET message = COALESCE(NULLIF(?, ''), message), status = 'approved', reviewed_at = NOW(), reviewed_by = ?
                WHERE id = ? AND status = 'pending_review'
            ");
            $stmt->execute([$customMsg, currentUserId(), $id]);
            $approvedCount++;
        }

        setFlash('success', "تم اعتماد وجدولة ({$approvedCount}) إشعار بنجاح! سيتم إرسالها تلقائياً للعملاء في موعد الإرسال الصباحي المجدول.");
        header('Location: renewal_reviews.php?tab=approved');
        exit;
    }

    // 6. اعتماد وإرسال الرسائل المحددة الآن مباشرة عبر الواتساب (مع حفظ أي تعديلات)
    if ($action === 'bulk_send') {
        $selectedIds = $_POST['selected_ids'] ?? [];
        if (empty($selectedIds)) {
            setFlash('error', 'يرجى تحديد إشعار واحد على الأقل.');
            header('Location: renewal_reviews.php');
            exit;
        }

        $sentCount = 0;
        $failedCount = 0;

        foreach ($selectedIds as $id) {
            $id = (int)$id;
            $stmt = $db->prepare("
                SELECT rn.*, c.mobile, c.name as client_name
                FROM renewal_notifications rn
                JOIN clients c ON c.id = rn.client_id
                WHERE rn.id = ?
            ");
            $stmt->execute([$id]);
            $item = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($item) {
                $customMsg = trim($_POST["message_{$id}"] ?? $item['message']);
                $res = sendWhatsappMessageDirect($item['mobile'], $customMsg, (int)$item['client_id'], 'renewal');
                if ($res['success']) {
                    $db->prepare("UPDATE renewal_notifications SET message = ?, status = 'sent', reviewed_at = NOW(), reviewed_by = ?, sent_at = NOW(), response = ? WHERE id = ?")
                       ->execute([$customMsg, currentUserId(), $res['response'], $id]);
                    $sentCount++;
                } else {
                    $db->prepare("UPDATE renewal_notifications SET message = ?, status = 'failed', reviewed_at = NOW(), reviewed_by = ?, response = ? WHERE id = ?")
                       ->execute([$customMsg, currentUserId(), $res['response'], $id]);
                    $failedCount++;
                }
                sleep(rand(2, 4));
            }
        }

        setFlash('success', "تم إرسال ({$sentCount}) إشعار بنجاح" . ($failedCount > 0 ? " وفشل ({$failedCount}) إشعار." : "."));
        header('Location: renewal_reviews.php');
        exit;
    }

    // 7. إرسال جميع الرسائل المعتمدة فوراً من تبويب الرسائل المعتمدة
    if ($action === 'send_all_approved') {
        $approvedItems = $db->query("
            SELECT rn.*, c.mobile, c.name as client_name
            FROM renewal_notifications rn
            JOIN clients c ON c.id = rn.client_id
            WHERE rn.status = 'approved'
        ")->fetchAll(PDO::FETCH_ASSOC);

        if (empty($approvedItems)) {
            setFlash('info', 'لا توجد رسائل معتمدة للإرسال.');
            header('Location: renewal_reviews.php?tab=approved');
            exit;
        }

        $sentCount = 0;
        $failedCount = 0;
        foreach ($approvedItems as $item) {
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
            sleep(rand(2, 4));
        }

        setFlash('success', "تم إرسال ({$sentCount}) رسالة معتمدة بنجاح" . ($failedCount > 0 ? " وفشل ({$failedCount})." : "."));
        header('Location: renewal_reviews.php?tab=history');
        exit;
    }
}

// ── جلب البيانات للعرض ──────────────────────────────────────────
$tab = clean($_GET['tab'] ?? 'pending');

// التأكد التام من وجود الجدول وأعمدته
ensureRenewalTableExists();

$expiringTomorrow = [];
$pendingList      = [];
$approvedList     = [];
$historyList      = [];

try {
    // 1. العملاء الذين ينتهي اشتراكهم غداً (1-day left)
    $expiringTomorrow = getExpiringTomorrowClients();

    // 2. إشعارات قيد المراجعة
    $pendingStmt = $db->query("
        SELECT rn.*, c.name as client_name, c.company_name, c.mobile, c.country, c.domain as client_domain,
               DATEDIFF(rn.cycle_end_date, CURDATE()) as days_left
        FROM renewal_notifications rn
        JOIN clients c ON c.id = rn.client_id
        WHERE rn.status = 'pending_review'
        ORDER BY rn.stage ASC, rn.cycle_end_date ASC
    ");
    $pendingList = $pendingStmt->fetchAll(PDO::FETCH_ASSOC);

    // 3. إشعارات معتمدة جاهزة للإرسال
    $approvedStmt = $db->query("
        SELECT rn.*, c.name as client_name, c.company_name, c.mobile, c.country, c.domain as client_domain,
               DATEDIFF(rn.cycle_end_date, CURDATE()) as days_left
        FROM renewal_notifications rn
        JOIN clients c ON c.id = rn.client_id
        WHERE rn.status = 'approved'
        ORDER BY rn.cycle_end_date ASC
    ");
    $approvedList = $approvedStmt->fetchAll(PDO::FETCH_ASSOC);

    // 4. سجل الإشعارات المرسلة
    $historyStmt = $db->query("
        SELECT rn.*, c.name as client_name, c.company_name, c.mobile, u.username as reviewer_name
        FROM renewal_notifications rn
        JOIN clients c ON c.id = rn.client_id
        LEFT JOIN users u ON u.id = rn.reviewed_by
        WHERE rn.status IN ('sent', 'failed', 'skipped')
        ORDER BY rn.id DESC
        LIMIT 50
    ");
    $historyList = $historyStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log('[Renewal Reviews Error] ' . $e->getMessage());
}

$counts = [
    'pending'  => count($pendingList),
    'approved' => count($approvedList),
    'expiring' => count($expiringTomorrow),
    'history'  => count($historyList)
];

$pageTitle  = 'مراجعة واعتماد إشعارات التجديد';
$activePage = 'renewal-reviews';
$depth      = 1;
require_once INCLUDES_PATH . '/header.php';
?>

<style>
.stat-card-badge {
  font-size: 11px;
  font-weight: 700;
  padding: 3px 8px;
  border-radius: 6px;
}
.stage-pill {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 4px 10px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 800;
}
.stage-60 { background: #e0f2fe; color: #0284c7; border: 1px solid #bae6fd; }
.stage-45 { background: #e0e7ff; color: #4f46e5; border: 1px solid #c7d2fe; }
.stage-30 { background: #fef3c7; color: #d97706; border: 1px solid #fde68a; }
.stage-15 { background: #ffedd5; color: #ea580c; border: 1px solid #fed7aa; }
.stage-7  { background: #fee2e2; color: #dc2626; border: 1px solid #fecaca; }

.review-card {
  background: var(--card-bg, #fff);
  border: 1px solid var(--border-color, #e2e8f0);
  border-radius: 12px;
  padding: 18px 20px;
  margin-bottom: 16px;
  transition: all 0.2s ease;
  position: relative;
}
.review-card:hover {
  border-color: #cbd5e1;
  box-shadow: 0 4px 12px rgba(0,0,0,0.05);
}
.review-card.urgent {
  border-right: 5px solid #ef4444;
}
.review-card.milestone {
  border-right: 5px solid #3b82f6;
}
.msg-textarea {
  width: 100%;
  font-family: inherit;
  font-size: 13px;
  line-height: 1.6;
  border: 1px solid var(--border-color, #cbd5e1);
  border-radius: 8px;
  padding: 10px 12px;
  background: #f8fafc;
  color: #1e293b;
  resize: vertical;
}
.msg-textarea:focus {
  background: #fff;
  border-color: var(--primary);
  outline: none;
}
.alert-banner {
  background: #fff1f2;
  border: 1px solid #fecdd3;
  border-right: 4px solid #e11d48;
  border-radius: 10px;
  padding: 14px 18px;
  margin-bottom: 24px;
}
</style>

<div class="page-header" style="margin-bottom: 20px;">
  <div class="page-header-text">
    <div style="display:flex; align-items:center; gap: 10px; flex-wrap: wrap;">
      <h1 class="page-title"><i class="fas fa-tasks" style="color:var(--primary); margin-left:8px;"></i>مراجعة واعتماد إشعارات التجديد</h1>
      <?php if ($isAutoReview): ?>
        <span class="badge" style="background: rgba(16,185,129,0.15); color: #059669; font-weight: 800; font-size: 12px; padding: 4px 10px; border-radius: 6px;">
          <i class="fas fa-shield-alt"></i> وضع المراجعة المسبقة مفعّل
        </span>
      <?php else: ?>
        <span class="badge" style="background: rgba(37,99,235,0.15); color: #2563eb; font-weight: 800; font-size: 12px; padding: 4px 10px; border-radius: 6px;">
          <i class="fas fa-bolt"></i> وضع الإرسال التلقائي المباشر مفعّل
        </span>
      <?php endif; ?>
    </div>
    <p class="page-subtitle">مراجعة وتعديل رسائل التجديد المخصصة قبل إرسالها للعملاء، ومتابعة عملاء الغد والـ 7 أيام</p>
  </div>

  <div class="page-actions" style="display:flex; gap: 8px; align-items:center; flex-wrap: wrap;">
    <form method="POST" action="renewal_reviews.php" style="margin:0;">
      <?= csrfField() ?>
      <input type="hidden" name="action" value="run_check">
      <button type="submit" class="btn btn-outline-primary" style="font-weight: 700;">
        <i class="fas fa-sync-alt"></i> فحص التجديدات وإشعار الإدمن الآن
      </button>
    </form>

    <a href="renewals.php" class="btn btn-outline-secondary" style="font-weight: 700;">
      <i class="fas fa-calendar-alt"></i> تقرير التجديدات الشامل
    </a>

    <a href="../settings.php" class="btn btn-outline-secondary" title="إعدادات التجديد">
      <i class="fas fa-cog"></i>
    </a>
  </div>
</div>

<!-- إحصائيات سريعة -->
<div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 24px;">
  <div class="stat-card" style="border-right: 4px solid #f59e0b;">
    <div class="stat-label">إشعارات قيد المراجعة</div>
    <div class="stat-value" style="color: #d97706;"><?= $counts['pending'] ?></div>
    <div class="stat-sub">تحتاج موافقتك قبل الإرسال</div>
  </div>

  <div class="stat-card" style="border-right: 4px solid #ef4444;">
    <div class="stat-label">ينتهي اشتراكهم غداً (يوم 1)</div>
    <div class="stat-value" style="color: #dc2626;"><?= $counts['expiring'] ?></div>
    <div class="stat-sub">متابعة عاجلة أو إيقاف الخدمة</div>
  </div>

  <div class="stat-card" style="border-right: 4px solid #10b981;">
    <div class="stat-label">معتمدة جاهزة للإرسال</div>
    <div class="stat-value" style="color: #059669;"><?= $counts['approved'] ?></div>
    <div class="stat-sub">مجدولة للإرسال الصباحي</div>
  </div>

  <div class="stat-card" style="border-right: 4px solid #6366f1;">
    <div class="stat-label">رقم واتساب الإدمن للتنبيه</div>
    <div class="stat-value" style="font-size: 16px; font-family: monospace; direction: ltr; margin-top: 4px;"><?= e($adminMobile) ?></div>
    <div class="stat-sub">يتلقى ملخص الغد والعاجل</div>
  </div>
</div>

<!-- تنبيه بالعملاء الذين ينتهي اشتراكهم غداً -->
<?php if (!empty($expiringTomorrow)): ?>
<div class="alert-banner">
  <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 10px; flex-wrap: wrap; gap: 8px;">
    <div style="font-weight: 800; font-size: 14.5px; color: #9f1239; display:flex; align-items:center; gap: 8px;">
      <i class="fas fa-exclamation-triangle" style="font-size: 18px;"></i>
      <span>تنبيه عاجل: يوجد (<?= count($expiringTomorrow) ?>) عميل ينتهي اشتراكهم غداً (متبقي يوم واحد فقط)</span>
    </div>
    <span style="font-size: 12px; color: #be123c;">يرجى المتابعة العاجلة للسداد أو إيقاف الخدمة في حال عدم التجديد</span>
  </div>

  <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 10px;">
    <?php foreach ($expiringTomorrow as $exp): ?>
      <div style="background: #fff; border: 1px solid #fecdd3; border-radius: 8px; padding: 10px 14px; display:flex; justify-content:space-between; align-items:center;">
        <div>
          <div style="font-weight: 800; font-size: 13.5px; color: #1e293b;">
            <a href="../clients/view.php?id=<?= $exp['client_id'] ?>" style="color: inherit; text-decoration: none;" target="_blank">
              <?= e($exp['client_name']) ?>
            </a>
            <?php if (!empty($exp['company_name'])): ?>
              <span style="font-size: 11.5px; color: var(--text-muted);">(<?= e($exp['company_name']) ?>)</span>
            <?php endif; ?>
          </div>
          <div style="font-size: 11.5px; color: var(--text-muted); margin-top: 2px;">
            <?= e(implode(' + ', $exp['services'])) ?>
          </div>
          <?php if (!empty($exp['client_domain'])): ?>
            <div style="font-size: 11px; color: #0284c7; margin-top: 2px;">
              <i class="fas fa-globe"></i> <?= e($exp['client_domain']) ?>
            </div>
          <?php endif; ?>
        </div>
        <div style="display:flex; gap: 6px; align-items:center;">
          <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $exp['mobile']) ?>" target="_blank" class="btn btn-sm btn-outline-success" style="padding: 4px 8px;" title="محادثة واتساب">
            <i class="fab fa-whatsapp"></i>
          </a>
          <a href="../clients/view.php?id=<?= $exp['client_id'] ?>" class="btn btn-sm btn-outline-primary" style="padding: 4px 8px;" title="ملف العميل">
            <i class="fas fa-user"></i>
          </a>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<!-- التبويبات الفرعية -->
<div class="tabs-nav" style="display:flex; gap:8px; border-bottom:2px solid var(--border-color); margin-bottom: 20px;">
  <a href="renewal_reviews.php?tab=pending" class="tab-btn <?= $tab === 'pending' ? 'active' : '' ?>">
    <i class="fas fa-inbox"></i> إشعارات قيد المراجعة (<?= $counts['pending'] ?>)
  </a>
  <a href="renewal_reviews.php?tab=approved" class="tab-btn <?= $tab === 'approved' ? 'active' : '' ?>">
    <i class="fas fa-check-circle"></i> معتمدة للإرسال الصباحي (<?= $counts['approved'] ?>)
  </a>
  <a href="renewal_reviews.php?tab=history" class="tab-btn <?= $tab === 'history' ? 'active' : '' ?>">
    <i class="fas fa-history"></i> سجل الإشعارات السابقة (<?= $counts['history'] ?>)
  </a>
</div>

<?php if ($tab === 'pending'): ?>
  <?php if (empty($pendingList)): ?>
    <div class="card" style="text-align: center; padding: 40px;">
      <div style="font-size: 44px; color: #10b981; margin-bottom: 12px;"><i class="fas fa-check-double"></i></div>
      <h3 style="font-weight: 800; font-size: 16px; color: var(--text-primary); margin-bottom: 6px;">لا توجد إشعارات معلقة قيد المراجعة حالياً</h3>
      <p style="font-size: 13px; color: var(--text-muted); max-width: 500px; margin: 0 auto 16px;">
        الجدولة اليومية تفحص الاشتراكات تلقائياً في موعدها المجدول (أو عند الضغط على "فحص التجديدات وإشعار الإدمن الآن").
      </p>
    </div>
  <?php else: ?>
    <form method="POST" action="renewal_reviews.php" id="bulkReviewForm">
      <?= csrfField() ?>
      <input type="hidden" name="action" id="bulkActionInput" value="bulk_approve">

      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 14px; background: #fff; padding: 12px 16px; border-radius: 8px; border: 1px solid var(--border-color); flex-wrap: wrap; gap: 12px;">
        <label style="display:flex; align-items:center; gap: 8px; font-weight: 700; cursor: pointer; font-size: 13.5px; margin: 0;">
          <input type="checkbox" id="selectAllCheckbox" onchange="toggleSelectAll(this)" checked>
          <span>تحديد جميع الرسائل (<span id="selectedCountText"><?= count($pendingList) ?></span>)</span>
        </label>

        <div style="display:flex; gap: 8px; align-items:center; flex-wrap: wrap;">
          <!-- زر اعتماد وجدولة الرسائل المحددة للصباح -->
          <button type="button" class="btn btn-primary" style="font-weight: 800;" onclick="submitBulkAction('bulk_approve')">
            <i class="fas fa-clock"></i> اعتماد وجدولة الرسائل المحددة (للإرسال الصباحي)
          </button>

          <!-- زر اعتماد وإرسال الرسائل المحددة الآن مباشرة -->
          <button type="button" class="btn btn-success" style="font-weight: 800;" onclick="submitBulkAction('bulk_send')">
            <i class="fab fa-whatsapp"></i> اعتماد وإرسال الرسائل المحددة الآن (مباشرة)
          </button>
        </div>
      </div>

      <?php foreach ($pendingList as $item): 
        $stg = (int)$item['stage'];
        $pillClass = "stage-{$stg}";
        $isUrgent = ($stg === 7);
      ?>
        <div class="review-card <?= $isUrgent ? 'urgent' : 'milestone' ?>" id="card-<?= $item['id'] ?>">
          <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom: 12px; flex-wrap: wrap; gap: 10px;">
            <div style="display:flex; align-items:center; gap: 12px;">
              <input type="checkbox" name="selected_ids[]" value="<?= $item['id'] ?>" class="item-checkbox" checked onchange="updateSelectedCount()">
              <div>
                <div style="font-weight: 800; font-size: 15px; color: var(--text-primary); display:flex; align-items:center; gap: 8px;">
                  <a href="../clients/view.php?id=<?= $item['client_id'] ?>" style="color: inherit; text-decoration: none;" target="_blank">
                    <?= e($item['client_name']) ?>
                  </a>
                  <?php if (!empty($item['company_name'])): ?>
                    <span style="font-size: 12px; color: var(--text-muted); font-weight: 600;">(<?= e($item['company_name']) ?>)</span>
                  <?php endif; ?>
                </div>

                <div style="font-size: 12px; color: var(--text-muted); margin-top: 3px; display:flex; gap: 12px; flex-wrap: wrap; align-items:center;">
                  <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $item['mobile']) ?>" target="_blank" style="color: #10b981; text-decoration: none; font-weight: 700;">
                    <i class="fab fa-whatsapp"></i> <?= e($item['mobile']) ?>
                  </a>
                  <span>•</span>
                  <span>تاريخ الانتهاء: <strong><?= e($item['cycle_end_date']) ?></strong></span>
                  <span>•</span>
                  <span>متبقي: <strong><?= (int)$item['days_left'] ?> يوم</strong></span>
                  <?php if (!empty($item['client_domain'])): ?>
                    <span>•</span>
                    <span style="color: #0284c7;"><i class="fas fa-globe"></i> <?= e($item['client_domain']) ?></span>
                  <?php endif; ?>
                </div>
              </div>
            </div>

            <div>
              <span class="stage-pill <?= $pillClass ?>">
                <?php if ($stg === 7): ?>
                  <i class="fas fa-bell"></i> مرحلة الـ 7 أيام الأخيرة
                <?php else: ?>
                  <i class="fas fa-hourglass-half"></i> مرحلة قبل <?= $stg ?> يوماً
                <?php endif; ?>
              </span>
            </div>
          </div>

          <!-- مربع نص الرسالة قابل للتعديل الفوري -->
          <div style="margin-bottom: 12px;">
            <label style="font-size: 12px; font-weight: 700; color: var(--text-muted); margin-bottom: 4px; display:block;">
              <i class="fas fa-edit"></i> نص رسالة الواتساب المخصصة (يمكنك التعديل عليها قبل الاعتماد):
            </label>
            <textarea name="message_<?= $item['id'] ?>" class="msg-textarea" rows="6"><?= e($item['message']) ?></textarea>
          </div>

          <!-- أزرار الإجراءات الفردية لكل كارت -->
          <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap: wrap; gap: 10px; border-top: 1px dashed var(--border-color); padding-top: 10px;">
            <div style="font-size: 11.5px; color: var(--text-muted);">
              <?php if (!empty($item['admin_notified_at'])): ?>
                <i class="fas fa-check-circle" style="color: #10b981;"></i> تم إشعار الإدمن بالواتساب: <?= date('d/m H:i', strtotime($item['admin_notified_at'])) ?>
              <?php else: ?>
                <i class="fas fa-clock" style="color: #f59e0b;"></i> قيد المراجعة
              <?php endif; ?>
            </div>

            <div style="display:flex; gap: 8px;">
              <!-- اعتماد وإرسال الآن مباشرة -->
              <button type="button" class="btn btn-sm btn-success" onclick="sendSingleNow(<?= $item['id'] ?>)" style="font-weight: 700;">
                <i class="fas fa-paper-plane"></i> إرسال الآن
              </button>

              <!-- اعتماد للجدولة الصباحية -->
              <button type="button" class="btn btn-sm btn-primary" onclick="approveSingleLater(<?= $item['id'] ?>)" style="font-weight: 700;">
                <i class="fas fa-clock"></i> اعتماد للجدولة
              </button>

              <!-- تخطي العميل -->
              <button type="button" class="btn btn-sm btn-outline-danger" onclick="skipSingle(<?= $item['id'] ?>)">
                <i class="fas fa-times"></i> تخطي
              </button>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </form>
  <?php endif; ?>

<?php elseif ($tab === 'approved'): ?>
  <!-- تبويب الرسائل المعتمدة الجاهزة للإرسال -->
  <div class="card">
    <div class="card-header" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
      <span class="card-title"><i class="fas fa-check-circle" style="color:#10b981;"></i> الرسائل المعتمدة الجاهزة للإرسال الصباحي (<?= count($approvedList) ?>)</span>
      <?php if (!empty($approvedList)): ?>
        <form method="POST" action="renewal_reviews.php" style="margin:0;" onsubmit="return confirm('هل أنت متأكد من رغبتك في إرسال جميع الرسائل المعتمدة (<?= count($approvedList) ?>) الآن مباشرة عبر الواتساب؟');">
          <?= csrfField() ?>
          <input type="hidden" name="action" value="send_all_approved">
          <button type="submit" class="btn btn-sm btn-success" style="font-weight:700;">
            <i class="fab fa-whatsapp"></i> إرسال جميع المعتمدة الآن مباشرة
          </button>
        </form>
      <?php endif; ?>
    </div>
    <div class="table-container">
      <table class="table">
        <thead>
          <tr>
            <th>العميل</th>
            <th>الهاتف</th>
            <th>المرحلة</th>
            <th>تاريخ الانتهاء</th>
            <th>نص الرسالة</th>
            <th>الإجراءات</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($approvedList)): ?>
            <tr><td colspan="6" style="text-align:center; padding: 30px; color: var(--text-muted);">لا توجد رسائل معتمدة بانتظار الإرسال حالياً.</td></tr>
          <?php else: ?>
            <?php foreach ($approvedList as $app): ?>
              <tr>
                <td style="font-weight: 700;">
                  <a href="../clients/view.php?id=<?= $app['client_id'] ?>"><?= e($app['client_name']) ?></a>
                  <?php if (!empty($app['company_name'])): ?><div style="font-size:11px;color:var(--text-muted);"><?= e($app['company_name']) ?></div><?php endif; ?>
                </td>
                <td dir="ltr" style="font-family:monospace;"><?= e($app['mobile']) ?></td>
                <td><span class="stage-pill stage-<?= $app['stage'] ?>">مرحلة <?= $app['stage'] ?> يوم</span></td>
                <td><?= e($app['cycle_end_date']) ?></td>
                <td><button type="button" class="btn btn-xs btn-outline-secondary" data-msg="<?= htmlspecialchars($app['message'], ENT_QUOTES, 'UTF-8') ?>" onclick="viewMessageModal(this.getAttribute('data-msg'))"><i class="fas fa-eye"></i> عرض الرسالة</button></td>
                <td>
                  <form method="POST" action="renewal_reviews.php" style="display:inline;">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="send_single">
                    <input type="hidden" name="notification_id" value="<?= $app['id'] ?>">
                    <button type="submit" class="btn btn-sm btn-success" style="font-weight:700;"><i class="fab fa-whatsapp"></i> إرسال الآن</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

<?php elseif ($tab === 'history'): ?>
  <!-- تبويب سجل الإشعارات السابقة -->
  <div class="card">
    <div class="card-header"><span class="card-title"><i class="fas fa-history"></i> سجل الإشعارات والتنبيهات المكتملة</span></div>
    <div class="table-container">
      <table class="table">
        <thead>
          <tr>
            <th>#</th>
            <th>العميل</th>
            <th>الهاتف</th>
            <th>المرحلة</th>
            <th>دورة الانتهاء</th>
            <th>الحالة</th>
            <th>تاريخ الإرسال/المراجعة</th>
            <th>المعتمد</th>
            <th>الرسالة</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($historyList)): ?>
            <tr><td colspan="9" style="text-align:center; padding: 30px; color: var(--text-muted);">لا توجد إشعارات مسجلة في الأرشيف حتى الآن.</td></tr>
          <?php else: ?>
            <?php foreach ($historyList as $h): ?>
              <tr>
                <td><?= $h['id'] ?></td>
                <td style="font-weight: 700;"><?= e($h['client_name']) ?></td>
                <td dir="ltr" style="font-family:monospace;"><?= e($h['mobile']) ?></td>
                <td><span class="stage-pill stage-<?= $h['stage'] ?>">مرحلة <?= $h['stage'] ?> يوم</span></td>
                <td><?= e($h['cycle_end_date']) ?></td>
                <td>
                  <?php if ($h['status'] === 'sent'): ?>
                    <span class="badge badge-success"><i class="fas fa-check"></i> تم الإرسال</span>
                  <?php elseif ($h['status'] === 'skipped'): ?>
                    <span class="badge badge-secondary"><i class="fas fa-ban"></i> تم التخطي</span>
                  <?php else: ?>
                    <span class="badge badge-danger"><i class="fas fa-times"></i> فشل</span>
                  <?php endif; ?>
                </td>
                <td style="font-size: 11.5px;"><?= $h['sent_at'] ? date('Y-m-d H:i', strtotime($h['sent_at'])) : ($h['reviewed_at'] ? date('Y-m-d H:i', strtotime($h['reviewed_at'])) : '-') ?></td>
                <td style="font-size: 12px;"><?= e($h['reviewer_name'] ?? 'النظام الآلي') ?></td>
                <td>
                  <button type="button" class="btn btn-xs btn-outline-secondary" data-msg="<?= htmlspecialchars($h['message'], ENT_QUOTES, 'UTF-8') ?>" onclick="viewMessageModal(this.getAttribute('data-msg'))">
                    <i class="fas fa-eye"></i> عرض
                  </button>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php endif; ?>

<!-- نموذج خفي للتعامل مع الإجراءات الفردية بنقرة زر -->
<form id="singleActionForm" method="POST" action="renewal_reviews.php" style="display:none;">
  <?= csrfField() ?>
  <input type="hidden" name="action" id="singleActionInput" value="">
  <input type="hidden" name="notification_id" id="singleNotifIdInput" value="">
  <input type="hidden" name="message" id="singleMessageInput" value="">
</form>

<!-- Modal لعرض الرسالة -->
<div id="viewMsgModal" style="display:none; position:fixed; z-index:9999; inset:0; background:rgba(0,0,0,0.5); align-items:center; justify-content:center;">
  <div style="background:#fff; border-radius:12px; max-width:550px; width:90%; padding:20px; box-shadow:0 10px 25px rgba(0,0,0,0.2);">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
      <h3 style="font-size:15px; font-weight:800; margin:0;"><i class="fab fa-whatsapp" style="color:#25D366;"></i> نص الرسالة المعتمدة</h3>
      <button type="button" onclick="closeMsgModal()" style="border:none; background:none; font-size:18px; cursor:pointer;">&times;</button>
    </div>
    <div id="msgModalContent" style="white-space:pre-wrap; background:#f8fafc; padding:12px; border-radius:8px; font-size:13px; max-height:350px; overflow-y:auto; line-height:1.6; border:1px solid #e2e8f0;"></div>
    <div style="text-align:left; margin-top:14px;">
      <button type="button" class="btn btn-secondary btn-sm" onclick="closeMsgModal()">إغلاق</button>
    </div>
  </div>
</div>

<script>
function submitBulkAction(actionName) {
  const checked = document.querySelectorAll('.item-checkbox:checked').length;
  if (checked === 0) {
    alert('يرجى تحديد إشعار واحد على الأقل.');
    return;
  }

  let confirmMsg = '';
  if (actionName === 'bulk_approve') {
    confirmMsg = `هل تريد اعتماد وتثبيت تعديلات (${checked}) رسالة لجدولتها للإرسال الصباحي التلقائي؟`;
  } else if (actionName === 'bulk_send') {
    confirmMsg = `هل تريد اعتماد وإرسال (${checked}) رسالة الآن مباشرة عبر الواتساب؟`;
  }

  if (confirmMsg && !confirm(confirmMsg)) {
    return;
  }

  document.getElementById('bulkActionInput').value = actionName;
  document.getElementById('bulkReviewForm').submit();
}

function toggleSelectAll(master) {
  const checkboxes = document.querySelectorAll('.item-checkbox');
  checkboxes.forEach(cb => cb.checked = master.checked);
  updateSelectedCount();
}

function updateSelectedCount() {
  const checked = document.querySelectorAll('.item-checkbox:checked').length;
  const countSpan = document.getElementById('selectedCountText');
  if (countSpan) countSpan.textContent = checked;
}

function sendSingleNow(id) {
  if (!confirm('هل تريد إرسال هذا الإشعار الآن عبر الواتساب؟')) return;
  const textarea = document.querySelector(`textarea[name="message_${id}"]`);
  document.getElementById('singleActionInput').value = 'send_single';
  document.getElementById('singleNotifIdInput').value = id;
  document.getElementById('singleMessageInput').value = textarea ? textarea.value : '';
  document.getElementById('singleActionForm').submit();
}

function approveSingleLater(id) {
  const textarea = document.querySelector(`textarea[name="message_${id}"]`);
  document.getElementById('singleActionInput').value = 'approve_single';
  document.getElementById('singleNotifIdInput').value = id;
  document.getElementById('singleMessageInput').value = textarea ? textarea.value : '';
  document.getElementById('singleActionForm').submit();
}

function skipSingle(id) {
  if (!confirm('هل أنت متأكد من تخطي هذا العميل؟ لن يتم إرسال إشعار له في هذه المرحلة.')) return;
  document.getElementById('singleActionInput').value = 'skip_single';
  document.getElementById('singleNotifIdInput').value = id;
  document.getElementById('singleActionForm').submit();
}

function viewMessageModal(msg) {
  document.getElementById('msgModalContent').textContent = msg;
  const modal = document.getElementById('viewMsgModal');
  modal.style.display = 'flex';
}

function closeMsgModal() {
  document.getElementById('viewMsgModal').style.display = 'none';
}
</script>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
