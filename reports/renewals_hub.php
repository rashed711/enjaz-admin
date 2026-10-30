<?php
/**
 * reports/renewals_hub.php - مركز التجديدات الموحد (نسخة تجريبية متكاملة)
 * يجمع بين: مراجعة واعتماد رسائل الواتساب الذكية + جدول وفلاتر التجديدات المتقدمة
 * نظام إنجاز للحلول الذكية
 */
require_once dirname(__DIR__) . '/config/app.php';
requireLogin();
requirePermission('send_whatsapp');
require_once INCLUDES_PATH . '/renewal_automation.php';

// التأكد التام من وجود جدول الإشعارات
ensureRenewalTableExists();

$db = getDB();
$activeTab = clean($_GET['tab'] ?? 'pending');
if (!in_array($activeTab, ['pending', 'approved', 'table', 'history'])) {
    $activeTab = 'pending';
}

$isAutoReview = (getSetting('renewal_auto_review', '1') === '1');
$adminMobile  = getSetting('renewal_admin_mobile', '201028855779');

// ─────────────────────────────────────────────────────────────────
// معالجة طلبات الـ POST (الاعتماد، الإرسال الفوري، الجدولة، الفحص)
// ─────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) {
        setFlash('error', 'خطأ في التحقق من الأمان.');
        header("Location: renewals_hub.php?tab={$activeTab}");
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
        header('Location: renewals_hub.php?tab=pending');
        exit;
    }

    // 2. إرسال فوري لإشعار عميل مفرد
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
        header('Location: renewals_hub.php?tab=pending');
        exit;
    }

    // 3. اعتماد للجدولة الصباحية فقط
    if ($action === 'approve_single') {
        $notifId = (int)($_POST['notification_id'] ?? 0);
        $customMessage = trim($_POST['message'] ?? '');
        $stmt = $db->prepare("UPDATE renewal_notifications SET message = COALESCE(NULLIF(?, ''), message), status = 'approved', reviewed_at = NOW(), reviewed_by = ? WHERE id = ?");
        $stmt->execute([$customMessage, currentUserId(), $notifId]);
        setFlash('success', 'تم اعتماد الرسالة، وسيتم إرسالها تلقائياً في موعد الإرسال الصباحي.');
        header('Location: renewals_hub.php?tab=pending');
        exit;
    }

    // 4. تخطي العميل
    if ($action === 'skip_single') {
        $notifId = (int)($_POST['notification_id'] ?? 0);
        $stmt = $db->prepare("UPDATE renewal_notifications SET status = 'skipped', reviewed_at = NOW(), reviewed_by = ? WHERE id = ?");
        $stmt->execute([currentUserId(), $notifId]);
        setFlash('info', 'تم تخطي هذا الإشعار.');
        header('Location: renewals_hub.php?tab=pending');
        exit;
    }

    // 5. اعتماد وجدولة الرسائل المحددة جماعياً للإرسال الصباحي
    if ($action === 'bulk_approve') {
        $selectedIds = $_POST['selected_ids'] ?? [];
        if (empty($selectedIds)) {
            setFlash('error', 'يرجى تحديد إشعار واحد على الأقل.');
            header('Location: renewals_hub.php?tab=pending');
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
        header('Location: renewals_hub.php?tab=approved');
        exit;
    }

    // 6. اعتماد وإرسال الرسائل المحددة جماعياً الآن مباشرة عبر الواتساب
    if ($action === 'bulk_send') {
        $selectedIds = $_POST['selected_ids'] ?? [];
        if (empty($selectedIds)) {
            setFlash('error', 'يرجى تحديد إشعار واحد على الأقل.');
            header('Location: renewals_hub.php?tab=pending');
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
                $customMsg = trim($_POST["message_{$id}"] ?? '');
                $msgToSend = !empty($customMsg) ? $customMsg : $item['message'];

                $res = sendWhatsappMessageDirect($item['mobile'], $msgToSend, (int)$item['client_id'], 'renewal');

                if ($res['success']) {
                    $db->prepare("
                        UPDATE renewal_notifications 
                        SET message = ?, status = 'sent', reviewed_at = NOW(), reviewed_by = ?, sent_at = NOW(), response = ?
                        WHERE id = ?
                    ")->execute([$msgToSend, currentUserId(), $res['response'], $id]);
                    $sentCount++;
                } else {
                    $db->prepare("
                        UPDATE renewal_notifications 
                        SET message = ?, status = 'failed', reviewed_at = NOW(), reviewed_by = ?, response = ?
                        WHERE id = ?
                    ")->execute([$msgToSend, currentUserId(), $res['response'], $id]);
                    $failedCount++;
                }
                usleep(500000); // نصف ثانية فاصل أمان
            }
        }

        if ($failedCount === 0) {
            setFlash('success', "تم إرسال ({$sentCount}) إشعار تجديد بنجاح للعملاء الآن!");
        } else {
            setFlash('warning', "تم إرسال ({$sentCount}) إشعار بنجاح، بينما تعذر إرسال ({$failedCount}) إشعار.");
        }

        header('Location: renewals_hub.php?tab=history');
        exit;
    }

    // 7. إرسال جميع الرسائل المعتمدة فوراً من تبويب المعتمد
    if ($action === 'send_all_approved') {
        $stmt = $db->query("
            SELECT rn.*, c.mobile, c.name as client_name
            FROM renewal_notifications rn
            JOIN clients c ON c.id = rn.client_id
            WHERE rn.status = 'approved'
            ORDER BY rn.id ASC
        ");
        $approvedItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $sentCount = 0;
        $failedCount = 0;

        foreach ($approvedItems as $item) {
            $res = sendWhatsappMessageDirect($item['mobile'], $item['message'], (int)$item['client_id'], 'renewal');
            if ($res['success']) {
                $db->prepare("
                    UPDATE renewal_notifications 
                    SET status = 'sent', sent_at = NOW(), response = ?
                    WHERE id = ?
                ")->execute([$res['response'], $item['id']]);
                $sentCount++;
            } else {
                $db->prepare("
                    UPDATE renewal_notifications 
                    SET status = 'failed', response = ?
                    WHERE id = ?
                ")->execute([$res['response'], $item['id']]);
                $failedCount++;
            }
            usleep(500000);
        }

        setFlash('success', "تم إرسال ({$sentCount}) رسالة معتمدة فوراً للعملاء بنجاح!");
        header('Location: renewals_hub.php?tab=history');
        exit;
    }
}

// ─────────────────────────────────────────────────────────────────
// جلب الإحصائيات العامة للمركز
// ─────────────────────────────────────────────────────────────────
$pendingCount = 0;
$approvedCount = 0;
$sentTodayCount = 0;
$urgent7DaysCount = 0;

try {
    $pendingCount = (int)$db->query("SELECT COUNT(*) FROM renewal_notifications WHERE status = 'pending_review'")->fetchColumn();
    $approvedCount = (int)$db->query("SELECT COUNT(*) FROM renewal_notifications WHERE status = 'approved'")->fetchColumn();
    $sentTodayCount = (int)$db->query("SELECT COUNT(*) FROM renewal_notifications WHERE status = 'sent' AND DATE(sent_at) = CURDATE()")->fetchColumn();
    $urgent7DaysCount = (int)$db->query("SELECT COUNT(*) FROM client_subscriptions WHERE status = 'active' AND end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)")->fetchColumn();
} catch (\Throwable $e) {}

// ─────────────────────────────────────────────────────────────────
// تجهيز بيانات التبويب النشط
// ─────────────────────────────────────────────────────────────────

// 1. بيانات تبويب: بانتظار المراجعة (pending)
$pendingItems = [];
$milestoneStats = [];
if ($activeTab === 'pending') {
    $stageFilter = isset($_GET['stage']) && $_GET['stage'] !== '' ? (int)$_GET['stage'] : null;
    $wherePending = "rn.status = 'pending_review'";
    $paramsPending = [];

    if ($stageFilter !== null) {
        $wherePending .= " AND rn.stage = ?";
        $paramsPending[] = $stageFilter;
    }

    try {
        $stmtPending = $db->prepare("
            SELECT rn.*, 
                   c.name as client_name, c.company_name, c.mobile, c.country, c.domain as client_domain,
                   DATEDIFF(rn.cycle_end_date, CURDATE()) as days_left
            FROM renewal_notifications rn
            JOIN clients c ON c.id = rn.client_id
            WHERE {$wherePending}
            ORDER BY rn.stage ASC, rn.cycle_end_date ASC
        ");
        $stmtPending->execute($paramsPending);
        $pendingItems = $stmtPending->fetchAll(PDO::FETCH_ASSOC);

        // إحصائيات كل مرحلة
        $mRows = $db->query("
            SELECT stage, COUNT(*) as cnt 
            FROM renewal_notifications 
            WHERE status = 'pending_review' 
            GROUP BY stage
        ")->fetchAll(PDO::FETCH_KEY_PAIR);
        foreach ([60, 45, 30, 15, 7] as $m) {
            $milestoneStats[$m] = $mRows[$m] ?? 0;
        }
    } catch (\Throwable $e) {}
}

// 2. بيانات تبويب: معتمدة للإرسال الصباحي (approved)
$approvedItems = [];
if ($activeTab === 'approved') {
    try {
        $stmtApproved = $db->query("
            SELECT rn.*, 
                   c.name as client_name, c.company_name, c.mobile, c.country, c.domain as client_domain,
                   u.username as reviewer_name
            FROM renewal_notifications rn
            JOIN clients c ON c.id = rn.client_id
            LEFT JOIN users u ON u.id = rn.reviewed_by
            WHERE rn.status = 'approved'
            ORDER BY rn.stage ASC, rn.cycle_end_date ASC
        ");
        $approvedItems = $stmtApproved->fetchAll(PDO::FETCH_ASSOC);
    } catch (\Throwable $e) {}
}

// 3. بيانات تبويب: جدول التجديدات المتقدم والبحث (table)
$renewalsTableData = [];
$availableServers = [];
$totalExpectedRevenue = 0;
$totalClientsTable = 0;
$filterDays = 60;
$clientStatus = '1';
$country = '';
$server = '';
$search = '';

if ($activeTab === 'table') {
    $warningDays = (int)getSetting('renewal_warning_days', '60');
    $filterDays   = isset($_GET['days']) ? (int)$_GET['days'] : $warningDays;
    $clientStatus = $_GET['status'] ?? '1';
    $country      = clean($_GET['country'] ?? '');
    $server       = clean($_GET['server'] ?? '');
    $search       = clean($_GET['search'] ?? '');

    try {
        $serversStmt = $db->query("SELECT DISTINCT server_panel FROM clients WHERE server_panel IS NOT NULL AND server_panel != '' ORDER BY server_panel ASC");
        $availableServers = $serversStmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (Exception $e) {}

    $whereT = ["cs.status='active'", "cs.end_date IS NOT NULL", "cs.end_date <= DATE_ADD(CURDATE(), INTERVAL ? DAY)"];
    $paramsT = [$filterDays];

    if ($search !== '') {
        $whereT[] = "(c.name LIKE ? OR c.company_name LIKE ? OR c.mobile LIKE ? OR c.domain LIKE ? OR cs.notes LIKE ? OR cs.plan_name LIKE ?)";
        $s = "%$search%";
        $paramsT = array_merge($paramsT, [$s, $s, $s, $s, $s, $s]);
    }
    if ($clientStatus !== 'all') {
        $whereT[] = "c.status = ?";
        $paramsT[] = (int)$clientStatus;
    }
    if ($country !== '') {
        $whereT[] = "c.country = ?";
        $paramsT[] = $country;
    }
    if ($server !== '') {
        $whereT[] = "c.server_panel = ?";
        $paramsT[] = $server;
    }

    $whereTStr = implode(' AND ', $whereT);
    $stmtT = $db->prepare("
        SELECT cs.*, c.name as client_name, c.mobile, c.company_name, c.country, c.server_panel, c.domain as client_domain,
               s.name as service_name,
               DATEDIFF(cs.end_date, CURDATE()) as days_left
        FROM client_subscriptions cs
        JOIN clients c ON c.id=cs.client_id
        JOIN services s ON s.id=cs.service_id
        WHERE $whereTStr
        ORDER BY cs.end_date ASC
    ");
    $stmtT->execute($paramsT);
    $rawRenewals = $stmtT->fetchAll(PDO::FETCH_ASSOC);

    function calculateSubRenewalPrice(array $sub): float {
        $sid = (int)($sub['service_id'] ?? 0);
        $sName = mb_strtolower($sub['service_name'] ?? '');
        $isDomain = ($sid === 2 || mb_strpos($sName, 'دومين') !== false || mb_strpos($sName, 'domain') !== false);
        $curr = strtoupper(trim($sub['currency'] ?? 'EGP'));
        $isSAR = ($curr === 'SAR');
        if ($isDomain) {
            return $isSAR ? (float)getSetting('renewal_domain_price_sar', 86) : (float)getSetting('renewal_domain_price_egp', 1350);
        }
        return (float)($sub['price'] ?? 0);
    }

    $groupedClients = [];
    foreach ($rawRenewals as $r) {
        $cId = $r['client_id'];
        if (!isset($groupedClients[$cId])) {
            $groupedClients[$cId] = [
                'client_id'     => $cId,
                'name'          => $r['client_name'],
                'mobile'        => $r['mobile'],
                'company_name'  => $r['company_name'],
                'country'       => $r['country'],
                'server_panel'  => $r['server_panel'],
                'client_domain' => $r['client_domain'],
                'total_price'   => 0,
                'currency'      => $r['currency'] ?? 'EGP',
                'min_days_left' => 999999,
                'min_end_date'  => null,
                'subscriptions' => [],
            ];
        }
        $rPrice = calculateSubRenewalPrice($r);
        $r['renewal_price'] = $rPrice;
        $groupedClients[$cId]['total_price'] += $rPrice;
        if ($r['days_left'] < $groupedClients[$cId]['min_days_left']) {
            $groupedClients[$cId]['min_days_left'] = $r['days_left'];
            $groupedClients[$cId]['min_end_date']  = $r['end_date'];
        }
        $groupedClients[$cId]['subscriptions'][] = $r;
    }
    $renewalsTableData = $groupedClients;
    $totalClientsTable = count($groupedClients);
    $totalExpectedRevenue = array_sum(array_column($groupedClients, 'total_price'));
}

// 4. بيانات تبويب: سجل الإرسال والأرشيف (history)
$historyItems = [];
if ($activeTab === 'history') {
    try {
        $stmtHistory = $db->query("
            SELECT rn.*, 
                   c.name as client_name, c.company_name, c.mobile,
                   u.username as reviewer_name
            FROM renewal_notifications rn
            JOIN clients c ON c.id = rn.client_id
            LEFT JOIN users u ON u.id = rn.reviewed_by
            WHERE rn.status IN ('sent', 'failed', 'skipped')
            ORDER BY COALESCE(rn.sent_at, rn.reviewed_at) DESC
            LIMIT 100
        ");
        $historyItems = $stmtHistory->fetchAll(PDO::FETCH_ASSOC);
    } catch (\Throwable $e) {}
}

$depth = 1;
$pageTitle = 'مركز التجديدات الموحد (معاينة تجريبية)';
$activePage = 'renewals-hub';
require_once INCLUDES_PATH . '/header.php';
?>

<style>
/* ترويسة المركز وبطاقات الإحصائيات */
.hub-banner {
  background: linear-gradient(135deg, #1e3a8a, #0284c7);
  color: #fff;
  border-radius: 14px;
  padding: 22px 26px;
  margin-bottom: 22px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 16px;
  box-shadow: 0 4px 20px rgba(2, 132, 199, 0.15);
}
.hub-title {
  margin: 0;
  font-size: 22px;
  font-weight: 800;
  display: flex;
  align-items: center;
  gap: 10px;
}
.hub-badge-beta {
  background: #facc15;
  color: #854d0e;
  font-size: 11px;
  font-weight: 800;
  padding: 3px 8px;
  border-radius: 6px;
}
.hub-desc {
  margin: 4px 0 0 0;
  font-size: 13px;
  opacity: 0.9;
}
.hub-nav-tabs {
  display: flex;
  gap: 8px;
  margin-bottom: 20px;
  border-bottom: 2px solid var(--border-color);
  padding-bottom: 6px;
  overflow-x: auto;
}
.hub-tab-link {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 10px 18px;
  border-radius: 10px 10px 0 0;
  font-size: 14px;
  font-weight: 700;
  color: var(--text-muted);
  text-decoration: none;
  transition: all 0.2s ease;
  border-bottom: 3px solid transparent;
}
.hub-tab-link:hover {
  color: var(--primary);
  background: rgba(37, 99, 235, 0.05);
}
.hub-tab-link.active {
  color: var(--primary);
  background: var(--bg-card);
  border-bottom-color: var(--primary);
}
.hub-tab-badge {
  padding: 2px 7px;
  border-radius: 20px;
  font-size: 11px;
  font-weight: 800;
}
.hub-stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 14px;
  margin-bottom: 22px;
}
.hub-stat-card {
  background: var(--bg-card);
  border: 1px solid var(--border-color);
  border-radius: 12px;
  padding: 16px;
  display: flex;
  align-items: center;
  gap: 14px;
}
.hub-stat-icon {
  width: 44px;
  height: 44px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 20px;
}

/* تنسيق كروت المراجعة */
.review-cards-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(360px, 1fr));
  gap: 16px;
}
.review-card {
  background: var(--bg-card);
  border: 1px solid var(--border-color);
  border-radius: 12px;
  overflow: hidden;
  display: flex;
  flex-direction: column;
  transition: transform 0.2s, box-shadow 0.2s;
}
.review-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 18px rgba(0,0,0,0.06);
}
.review-card-header {
  padding: 14px 16px;
  background: rgba(0,0,0,0.02);
  border-bottom: 1px solid var(--border-color);
  display: flex;
  align-items: center;
  justify-content: space-between;
}
.review-card-body {
  padding: 16px;
  flex: 1;
}
.review-card-footer {
  padding: 12px 16px;
  background: rgba(0,0,0,0.015);
  border-top: 1px solid var(--border-color);
  display: flex;
  gap: 8px;
}
.stage-pill {
  padding: 4px 10px;
  border-radius: 6px;
  font-size: 12px;
  font-weight: 800;
}
.stage-60 { background: #dbeafe; color: #1e40af; }
.stage-45 { background: #e0e7ff; color: #3730a3; }
.stage-30 { background: #fef3c7; color: #92400e; }
.stage-15 { background: #ffedd5; color: #9a3412; }
.stage-7  { background: #fee2e2; color: #991b1b; animation: pulse 2s infinite; }

.batch-bar {
  background: var(--bg-card);
  border: 1px solid var(--border-color);
  border-radius: 10px;
  padding: 12px 18px;
  margin-bottom: 16px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 12px;
}

/* نافذة المودال */
.hub-modal {
  display: none;
  position: fixed;
  z-index: 9999;
  left: 0; top: 0;
  width: 100%; height: 100%;
  background: rgba(0,0,0,0.5);
  backdrop-filter: blur(4px);
  align-items: center;
  justify-content: center;
}
.hub-modal.open { display: flex; }
.hub-modal-content {
  background: var(--bg-card);
  border-radius: 12px;
  max-width: 550px;
  width: 90%;
  padding: 24px;
  box-shadow: 0 10px 30px rgba(0,0,0,0.2);
}
</style>

<div class="page-header" style="margin-bottom: 16px;">
  <div class="page-title">
    <i class="fas fa-layer-group" style="color: var(--primary);"></i>
    <span>مركز التجديدات الموحد</span>
    <span class="hub-badge-beta">معاينة تجريبية Side-by-Side</span>
  </div>
  <div class="page-actions" style="display: flex; gap: 8px;">
    <form method="POST" style="margin: 0;">
      <?= csrfField() ?>
      <input type="hidden" name="action" value="run_check">
      <button type="submit" class="btn btn-outline" style="font-weight: 700;">
        <i class="fas fa-sync-alt"></i> فحص التجديدات الآن
      </button>
    </form>
    <a href="renewal_reviews.php" class="btn btn-sm btn-outline" style="color: var(--text-muted);" title="فتح صفحة المراجعة القديمة">
      الصفحة القديمة <i class="fas fa-external-link-alt"></i>
    </a>
  </div>
</div>

<?= renderFlash() ?>

<!-- شريط الإحصائيات العامة السريع -->
<div class="hub-stats-grid">
  <div class="hub-stat-card">
    <div class="hub-stat-icon" style="background: rgba(245, 158, 11, 0.15); color: #d97706;">
      <i class="fas fa-clock"></i>
    </div>
    <div>
      <div style="font-size: 12px; color: var(--text-muted); font-weight: 600;">بانتظار المراجعة</div>
      <div style="font-size: 20px; font-weight: 800; color: #d97706;"><?= $pendingCount ?></div>
    </div>
  </div>

  <div class="hub-stat-card">
    <div class="hub-stat-icon" style="background: rgba(16, 185, 129, 0.15); color: #10b981;">
      <i class="fas fa-calendar-check"></i>
    </div>
    <div>
      <div style="font-size: 12px; color: var(--text-muted); font-weight: 600;">معتمدة للإرسال الصباحي</div>
      <div style="font-size: 20px; font-weight: 800; color: #10b981;"><?= $approvedCount ?></div>
    </div>
  </div>

  <div class="hub-stat-card">
    <div class="hub-stat-icon" style="background: rgba(239, 68, 68, 0.15); color: #ef4444;">
      <i class="fas fa-exclamation-triangle"></i>
    </div>
    <div>
      <div style="font-size: 12px; color: var(--text-muted); font-weight: 600;">تجديدات عاجلة (≤ 7 أيام)</div>
      <div style="font-size: 20px; font-weight: 800; color: #ef4444;"><?= $urgent7DaysCount ?></div>
    </div>
  </div>

  <div class="hub-stat-card">
    <div class="hub-stat-icon" style="background: rgba(59, 130, 246, 0.15); color: #3b82f6;">
      <i class="fas fa-paper-plane"></i>
    </div>
    <div>
      <div style="font-size: 12px; color: var(--text-muted); font-weight: 600;">أُرسلت اليوم</div>
      <div style="font-size: 20px; font-weight: 800; color: #3b82f6;"><?= $sentTodayCount ?></div>
    </div>
  </div>
</div>

<!-- شريط التبويبات الموحد -->
<div class="hub-nav-tabs">
  <a href="renewals_hub.php?tab=pending" class="hub-tab-link <?= $activeTab === 'pending' ? 'active' : '' ?>">
    <i class="fas fa-tasks"></i>
    <span>بانتظار المراجعة والاعتماد</span>
    <span class="hub-tab-badge" style="background: #fef3c7; color: #92400e;"><?= $pendingCount ?></span>
  </a>

  <a href="renewals_hub.php?tab=approved" class="hub-tab-link <?= $activeTab === 'approved' ? 'active' : '' ?>">
    <i class="fas fa-calendar-check"></i>
    <span>معتمدة للإرسال الصباحي</span>
    <span class="hub-tab-badge" style="background: #dcfce7; color: #166534;"><?= $approvedCount ?></span>
  </a>

  <a href="renewals_hub.php?tab=table" class="hub-tab-link <?= $activeTab === 'table' ? 'active' : '' ?>">
    <i class="fas fa-table"></i>
    <span>جدول التجديدات والبحث المتقدم</span>
    <span class="hub-tab-badge" style="background: #e0f2fe; color: #0369a1;">فلاتر شاملة</span>
  </a>

  <a href="renewals_hub.php?tab=history" class="hub-tab-link <?= $activeTab === 'history' ? 'active' : '' ?>">
    <i class="fas fa-history"></i>
    <span>سجل الإرسال والأرشيف</span>
  </a>
</div>

<!-- ───────────────────────────────────────────────────────────── -->
<!-- محتوى التبويب 1: بانتظار المراجعة والاعتماد -->
<!-- ───────────────────────────────────────────────────────────── -->
<?php if ($activeTab === 'pending'): ?>
  <!-- فلترة المراحل -->
  <div style="display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 16px;">
    <a href="renewals_hub.php?tab=pending" class="btn btn-sm <?= (!isset($_GET['stage']) || $_GET['stage'] === '') ? 'btn-primary' : 'btn-outline' ?>">
      الكل (<?= $pendingCount ?>)
    </a>
    <?php foreach ([60, 45, 30, 15, 7] as $m): ?>
      <a href="renewals_hub.php?tab=pending&stage=<?= $m ?>" class="btn btn-sm <?= (isset($_GET['stage']) && $_GET['stage'] == $m) ? 'btn-primary' : 'btn-outline' ?>">
        مرحلة <?= $m ?> يوم (<?= $milestoneStats[$m] ?? 0 ?>)
      </a>
    <?php endforeach; ?>
  </div>

  <?php if (empty($pendingItems)): ?>
    <div class="card" style="text-align: center; padding: 48px 24px;">
      <i class="fas fa-check-circle" style="font-size: 48px; color: var(--success); margin-bottom: 12px;"></i>
      <h3 style="margin: 0 0 6px 0;">رائع! لا توجد إشعارات بانتظار المراجعة</h3>
      <p style="color: var(--text-muted); margin: 0;">جميع العملاء المستحقين للتنبيه تمت مراجعتهم أو لا توجد اشتراكات مستحقة حالياً.</p>
    </div>
  <?php else: ?>
    <!-- شريط الإجراءات الجماعية -->
    <form id="bulkForm" method="POST" action="renewals_hub.php?tab=pending">
      <?= csrfField() ?>
      <div class="batch-bar">
        <div style="display: flex; align-items: center; gap: 10px;">
          <label style="display: flex; align-items: center; gap: 6px; cursor: pointer; font-weight: 700; margin: 0;">
            <input type="checkbox" id="selectAllReviews" onclick="toggleSelectAllReviews(this)">
            <span>تحديد الكل</span>
          </label>
          <span style="font-size: 13px; color: var(--text-muted);">(المحدد: <strong id="selectedReviewsCount">0</strong>)</span>
        </div>

        <div style="display: flex; gap: 8px;">
          <button type="submit" name="action" value="bulk_approve" class="btn btn-sm btn-primary" id="bulkApproveBtn" disabled onclick="return confirm('هل أنت متأكد من اعتماد ونقل المحدد للإرسال الصباحي المجدول؟')">
            <i class="fas fa-calendar-check"></i> اعتماد للجدولة الصباحية
          </button>
          <button type="submit" name="action" value="bulk_send" class="btn btn-sm btn-success" id="bulkSendBtn" disabled onclick="return confirm('تحذير: هل أنت متأكد من إرسال رسائل الواتساب للمحددين فوراً الآن؟')">
            <i class="fab fa-whatsapp"></i> إرسال الآن مباشرة
          </button>
        </div>
      </div>

      <!-- شبكة الكروت -->
      <div class="review-cards-grid">
        <?php foreach ($pendingItems as $idx => $item): 
          $stageClass = 'stage-' . $item['stage'];
        ?>
          <div class="review-card">
            <div class="review-card-header">
              <div style="display: flex; align-items: center; gap: 8px;">
                <input type="checkbox" name="selected_ids[]" value="<?= $item['id'] ?>" class="review-item-checkbox" onchange="updateReviewSelectionCount()">
                <div>
                  <strong style="font-size: 14.5px;"><?= e($item['client_name']) ?></strong>
                  <?php if (!empty($item['company_name'])): ?>
                    <div style="font-size: 11px; color: var(--text-muted);"><?= e($item['company_name']) ?></div>
                  <?php endif; ?>
                </div>
              </div>
              <span class="stage-pill <?= $stageClass ?>">مرحلة <?= $item['stage'] ?> يوم</span>
            </div>

            <div class="review-card-body">
              <div style="font-size: 12px; color: var(--text-muted); margin-bottom: 10px; display: flex; justify-content: space-between;">
                <span>📱 <?= e($item['mobile']) ?></span>
                <span>📅 ينتهي: <strong><?= e($item['cycle_end_date']) ?></strong> (متبقي <?= $item['days_left'] ?> يوم)</span>
              </div>

              <label style="font-size: 12px; font-weight: 700; color: var(--text-muted); margin-bottom: 4px; display: block;">
                نص الرسالة المخصص للعميل:
              </label>
              <textarea name="message_<?= $item['id'] ?>" class="form-control" style="font-size: 12.5px; height: 130px; resize: vertical; line-height: 1.5; font-family: inherit;"><?= e($item['message']) ?></textarea>
            </div>

            <div class="review-card-footer">
              <button type="button" class="btn btn-sm btn-success" style="flex: 1;" onclick="sendSingleFromCard(<?= $item['id'] ?>, this)">
                <i class="fab fa-whatsapp"></i> إرسال الآن
              </button>
              <button type="button" class="btn btn-sm btn-outline" style="flex: 1;" onclick="approveSingleFromCard(<?= $item['id'] ?>, this)">
                <i class="fas fa-check"></i> اعتماد للجدولة
              </button>
              <button type="button" class="btn btn-sm btn-outline" style="color: var(--danger); border-color: var(--danger);" onclick="skipSingleFromCard(<?= $item['id'] ?>)" title="تخطي العميل لهذه المرحلة">
                <i class="fas fa-times"></i>
              </button>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </form>
  <?php endif; ?>

<!-- ───────────────────────────────────────────────────────────── -->
<!-- محتوى التبويب 2: معتمدة للإرسال الصباحي -->
<!-- ───────────────────────────────────────────────────────────── -->
<?php elseif ($activeTab === 'approved'): ?>
  <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
    <div>
      <h3 style="margin: 0;">الرسائل المعتمدة الجاهزة للإرسال الصباحي (<?= count($approvedItems) ?>)</h3>
      <p style="margin: 4px 0 0 0; font-size: 12.5px; color: var(--text-muted);">
        سيقوم النظام بإرسال هذه الرسائل تلقائياً عبر الكرون جوب في تمام الساعة <strong>10:00 صباحاً</strong>.
      </p>
    </div>

    <?php if (!empty($approvedItems)): ?>
      <form method="POST" onsubmit="return confirm('تحذير: هل أنت متأكد من رغبتك في إرسال جميع الرسائل المعتمدة الآن مباشرة بدلاً من انتظار الصباح؟')">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="send_all_approved">
        <button type="submit" class="btn btn-success" style="font-weight: 700;">
          <i class="fab fa-whatsapp"></i> إرسال الكل المعتمد الآن
        </button>
      </form>
    <?php endif; ?>
  </div>

  <?php if (empty($approvedItems)): ?>
    <div class="card" style="text-align: center; padding: 48px 24px;">
      <i class="fas fa-inbox" style="font-size: 48px; color: var(--text-muted); opacity: 0.5; margin-bottom: 12px;"></i>
      <h3 style="margin: 0 0 6px 0;">لا توجد رسائل معتمدة حالياً</h3>
      <p style="color: var(--text-muted); margin: 0;">يمكنك مراجعة الرسائل المستحقة واعتمادها من تبويب "بانتظار المراجعة والاعتماد".</p>
    </div>
  <?php else: ?>
    <div class="card">
      <div class="table-responsive">
        <table class="table">
          <thead>
            <tr>
              <th>العميل</th>
              <th>الهاتف</th>
              <th>المرحلة</th>
              <th>تاريخ الانتهاء</th>
              <th>نص الرسالة</th>
              <th>اعتمد بواسطة</th>
              <th>الإجراء</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($approvedItems as $app): ?>
              <tr>
                <td>
                  <strong><?= e($app['client_name']) ?></strong>
                  <?php if (!empty($app['company_name'])): ?>
                    <div style="font-size: 11px; color: var(--text-muted);"><?= e($app['company_name']) ?></div>
                  <?php endif; ?>
                </td>
                <td><span dir="ltr"><?= e($app['mobile']) ?></span></td>
                <td><span class="stage-pill stage-<?= $app['stage'] ?>">مرحلة <?= $app['stage'] ?> يوم</span></td>
                <td><?= e($app['cycle_end_date']) ?></td>
                <td>
                  <button type="button" class="btn btn-sm btn-outline" data-msg="<?= htmlspecialchars($app['message'], ENT_QUOTES, 'UTF-8') ?>" onclick="openHubMessageModal(this.getAttribute('data-msg'))">
                    <i class="fas fa-eye"></i> عرض الرسالة
                  </button>
                </td>
                <td><span style="font-size: 12px; color: var(--text-muted);"><?= e($app['reviewer_name'] ?? 'الأدمن') ?></span></td>
                <td>
                  <form method="POST" style="margin: 0; display: inline;">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="send_single">
                    <input type="hidden" name="notification_id" value="<?= $app['id'] ?>">
                    <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('إرسال الآن؟')">
                      <i class="fab fa-whatsapp"></i> إرسال الآن
                    </button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  <?php endif; ?>

<!-- ───────────────────────────────────────────────────────────── -->
<!-- محتوى التبويب 3: جدول التجديدات والبحث المتقدم -->
<!-- ───────────────────────────────────────────────────────────── -->
<?php elseif ($activeTab === 'table'): ?>
  <!-- نموذج الفلترة والبحث -->
  <div class="card" style="margin-bottom: 16px;">
    <form method="GET" action="renewals_hub.php" style="margin: 0;">
      <input type="hidden" name="tab" value="table">
      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; align-items: end;">
        <div>
          <label style="font-size: 12px; font-weight: 700;">البحث</label>
          <input type="text" name="search" class="form-control" placeholder="اسم، هاتف، دومين..." value="<?= e($search) ?>">
        </div>

        <div>
          <label style="font-size: 12px; font-weight: 700;">تنبيه التجديد (خلال)</label>
          <select name="days" class="form-control">
            <?php foreach ([7, 15, 30, 45, 60, 90, 120] as $d): ?>
              <option value="<?= $d ?>" <?= $filterDays == $d ? 'selected' : '' ?>><?= $d ?> يوم</option>
            <?php endforeach; ?>
          </select>
        </div>

        <div>
          <label style="font-size: 12px; font-weight: 700;">السيرفر</label>
          <select name="server" class="form-control">
            <option value="">جميع السيرفرات</option>
            <?php foreach ($availableServers as $s): ?>
              <option value="<?= e($s) ?>" <?= $server === $s ? 'selected' : '' ?>><?= e($s) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div>
          <label style="font-size: 12px; font-weight: 700;">الدولة</label>
          <select name="country" class="form-control">
            <option value="">الكل</option>
            <option value="EG" <?= $country === 'EG' ? 'selected' : '' ?>>مصر</option>
            <option value="SA" <?= $country === 'SA' ? 'selected' : '' ?>>السعودية</option>
          </select>
        </div>

        <div>
          <button type="submit" class="btn btn-primary" style="width: 100%;">
            <i class="fas fa-filter"></i> تطبيق الفلترة
          </button>
        </div>
      </div>
    </form>
  </div>

  <!-- ملخص الفلترة -->
  <div style="display: flex; gap: 12px; margin-bottom: 14px; font-size: 13px; color: var(--text-muted);">
    <span>إجمالي العملاء المستحقين: <strong><?= $totalClientsTable ?></strong></span>
    <span>•</span>
    <span>إجمالي الإيرادات المتوقعة: <strong style="color: var(--success);"><?= number_format($totalExpectedRevenue) ?> ج.م / ر.س</strong></span>
  </div>

  <div class="card">
    <div class="table-responsive">
      <table class="table">
        <thead>
          <tr>
            <th>العميل</th>
            <th>النطاق والسيرفر</th>
            <th>الخدمات المستحقة</th>
            <th>أقرب انتهاء</th>
            <th>المبلغ المتوقع</th>
            <th>الإجراء السريع</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($renewalsTableData)): ?>
            <tr>
              <td colspan="6" style="text-align: center; padding: 40px; color: var(--text-muted);">
                لا توجد اشتراكات تنتهي خلال الفترة المحددة.
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($renewalsTableData as $c): ?>
              <tr>
                <td>
                  <strong><?= e($c['name']) ?></strong>
                  <div style="font-size: 11px; color: var(--text-muted);"><?= e($c['mobile']) ?></div>
                </td>
                <td>
                  <div><code><?= e($c['client_domain'] ?: '—') ?></code></div>
                  <div style="font-size: 11px; color: var(--text-muted);"><?= e($c['server_panel'] ?: '—') ?></div>
                </td>
                <td>
                  <?php foreach ($c['subscriptions'] as $sub): ?>
                    <span class="badge" style="font-size: 11px; margin-bottom: 2px;">
                      <?= e($sub['service_name']) ?> <?= !empty($sub['plan_name']) ? '(' . e($sub['plan_name']) . ')' : '' ?>
                    </span>
                  <?php endforeach; ?>
                </td>
                <td>
                  <strong><?= e($c['min_end_date']) ?></strong>
                  <div style="font-size: 11px; color: <?= $c['min_days_left'] <= 7 ? 'var(--danger)' : 'var(--text-muted)' ?>;">
                    متبقي <?= $c['min_days_left'] ?> يوم
                  </div>
                </td>
                <td>
                  <strong style="color: var(--primary);"><?= number_format($c['total_price']) ?> <?= e($c['currency']) ?></strong>
                </td>
                <td>
                  <a href="https://wa.me/<?= preg_replace('/\D/', '', $c['mobile']) ?>" target="_blank" class="btn btn-sm btn-outline" style="color: #10b981; border-color: #10b981;">
                    <i class="fab fa-whatsapp"></i> فتح شات
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

<!-- ───────────────────────────────────────────────────────────── -->
<!-- محتوى التبويب 4: سجل الإرسال والأرشيف -->
<!-- ───────────────────────────────────────────────────────────── -->
<?php elseif ($activeTab === 'history'): ?>
  <div class="card">
    <div class="table-responsive">
      <table class="table">
        <thead>
          <tr>
            <th>التاريخ والوقت</th>
            <th>العميل</th>
            <th>الهاتف</th>
            <th>المرحلة</th>
            <th>الحالة</th>
            <th>الرسالة</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($historyItems)): ?>
            <tr>
              <td colspan="6" style="text-align: center; padding: 40px; color: var(--text-muted);">
                لا يوجد سجل إرسال سابق حتى الآن.
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($historyItems as $h): ?>
              <tr>
                <td><?= e($h['sent_at'] ?? $h['reviewed_at']) ?></td>
                <td><strong><?= e($h['client_name']) ?></strong></td>
                <td><span dir="ltr"><?= e($h['mobile']) ?></span></td>
                <td><span class="stage-pill stage-<?= $h['stage'] ?>">مرحلة <?= $h['stage'] ?> يوم</span></td>
                <td>
                  <?php if ($h['status'] === 'sent'): ?>
                    <span class="badge badge-success"><i class="fas fa-check"></i> تم الإرسال</span>
                  <?php elseif ($h['status'] === 'failed'): ?>
                    <span class="badge badge-danger"><i class="fas fa-times"></i> فشل</span>
                  <?php else: ?>
                    <span class="badge badge-secondary">تم التخطي</span>
                  <?php endif; ?>
                </td>
                <td>
                  <button type="button" class="btn btn-sm btn-outline" data-msg="<?= htmlspecialchars($h['message'], ENT_QUOTES, 'UTF-8') ?>" onclick="openHubMessageModal(this.getAttribute('data-msg'))">
                    <i class="fas fa-eye"></i> عرض الرسالة
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

<!-- نافذة عرض الرسالة المنبثقة (Modal) -->
<div id="hubMessageModal" class="hub-modal" onclick="closeHubMessageModal(event)">
  <div class="hub-modal-content" onclick="event.stopPropagation()">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px;">
      <h3 style="margin: 0; font-size: 16px;"><i class="fab fa-whatsapp" style="color: #10b981;"></i> نص رسالة التجديد</h3>
      <button type="button" class="btn btn-sm btn-outline" onclick="closeHubMessageModal()">✕</button>
    </div>
    <div id="hubMessageModalBody" style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 14px; font-size: 13px; line-height: 1.6; white-space: pre-wrap; max-height: 350px; overflow-y: auto;"></div>
    <div style="text-align: left; margin-top: 14px;">
      <button type="button" class="btn btn-sm btn-secondary" onclick="closeHubMessageModal()">إغلاق</button>
    </div>
  </div>
</div>

<script>
// فتح نافذة عرض نص الرسالة بأمان
function openHubMessageModal(msgText) {
  const modal = document.getElementById('hubMessageModal');
  const body = document.getElementById('hubMessageModalBody');
  if (modal && body) {
    body.textContent = msgText;
    modal.classList.add('open');
  }
}

function closeHubMessageModal(e) {
  const modal = document.getElementById('hubMessageModal');
  if (modal) modal.classList.remove('open');
}

// التحكم في التحديد الجماعي
function toggleSelectAllReviews(source) {
  const checkboxes = document.querySelectorAll('.review-item-checkbox');
  checkboxes.forEach(cb => cb.checked = source.checked);
  updateReviewSelectionCount();
}

function updateReviewSelectionCount() {
  const checked = document.querySelectorAll('.review-item-checkbox:checked').length;
  const countEl = document.getElementById('selectedReviewsCount');
  const btnApprove = document.getElementById('bulkApproveBtn');
  const btnSend = document.getElementById('bulkSendBtn');
  if (countEl) countEl.textContent = checked;
  if (btnApprove) btnApprove.disabled = (checked === 0);
  if (btnSend) btnSend.disabled = (checked === 0);
}

// إجراءات الكارت الفردية
function sendSingleFromCard(notifId, btn) {
  if (!confirm('هل أنت متأكد من إرسال رسالة التجديد لهذا العميل الآن مباشرة عبر الواتساب؟')) return;
  const form = document.createElement('form');
  form.method = 'POST';
  form.action = 'renewals_hub.php?tab=pending';

  const csrf = document.querySelector('input[name="csrf_token"]');
  if (csrf) form.appendChild(csrf.cloneNode(true));

  const actionInput = document.createElement('input');
  actionInput.type = 'hidden';
  actionInput.name = 'action';
  actionInput.value = 'send_single';
  form.appendChild(actionInput);

  const idInput = document.createElement('input');
  idInput.type = 'hidden';
  idInput.name = 'notification_id';
  idInput.value = notifId;
  form.appendChild(idInput);

  // إرسال النص المعدل إن وجد
  const textarea = document.querySelector(`textarea[name="message_${notifId}"]`);
  if (textarea) {
    const msgInput = document.createElement('input');
    msgInput.type = 'hidden';
    msgInput.name = 'message';
    msgInput.value = textarea.value;
    form.appendChild(msgInput);
  }

  document.body.appendChild(form);
  form.submit();
}

function approveSingleFromCard(notifId, btn) {
  const form = document.createElement('form');
  form.method = 'POST';
  form.action = 'renewals_hub.php?tab=pending';

  const csrf = document.querySelector('input[name="csrf_token"]');
  if (csrf) form.appendChild(csrf.cloneNode(true));

  const actionInput = document.createElement('input');
  actionInput.type = 'hidden';
  actionInput.name = 'action';
  actionInput.value = 'approve_single';
  form.appendChild(actionInput);

  const idInput = document.createElement('input');
  idInput.type = 'hidden';
  idInput.name = 'notification_id';
  idInput.value = notifId;
  form.appendChild(idInput);

  const textarea = document.querySelector(`textarea[name="message_${notifId}"]`);
  if (textarea) {
    const msgInput = document.createElement('input');
    msgInput.type = 'hidden';
    msgInput.name = 'message';
    msgInput.value = textarea.value;
    form.appendChild(msgInput);
  }

  document.body.appendChild(form);
  form.submit();
}

function skipSingleFromCard(notifId) {
  if (!confirm('هل تريد تخطي هذا التنبيه لهذه المرحلة؟')) return;
  const form = document.createElement('form');
  form.method = 'POST';
  form.action = 'renewals_hub.php?tab=pending';

  const csrf = document.querySelector('input[name="csrf_token"]');
  if (csrf) form.appendChild(csrf.cloneNode(true));

  const actionInput = document.createElement('input');
  actionInput.type = 'hidden';
  actionInput.name = 'action';
  actionInput.value = 'skip_single';
  form.appendChild(actionInput);

  const idInput = document.createElement('input');
  idInput.type = 'hidden';
  idInput.name = 'notification_id';
  idInput.value = notifId;
  form.appendChild(idInput);

  document.body.appendChild(form);
  form.submit();
}
</script>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>

