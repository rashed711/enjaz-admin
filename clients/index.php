<?php
/**
 * clients/index.php - قائمة العملاء
 */
require_once dirname(__DIR__) . '/config/app.php';
requireLogin();
requirePermission('view_clients');

$db = getDB();
$warningDays = (int)getSetting('renewal_warning_days','60');

// Search & Filter
$search = clean($_GET['search'] ?? '');
$status = isset($_GET['status']) ? $_GET['status'] : '1';
$filter = $_GET['filter'] ?? '';
$plan   = clean($_GET['plan'] ?? '');
$page   = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;

$where  = ['1=1'];
$params = [];
if ($search) {
    $where[]  = "(c.name LIKE ? OR c.company_name LIKE ? OR c.mobile LIKE ? OR c.username_note LIKE ? OR c.domain LIKE ?)";
    $s = "%$search%";
    $params   = array_merge($params, [$s, $s, $s, $s, $s]);
}
if ($status !== '') {
    $where[]  = "c.status = ?";
    $params[] = (int)$status;
}

if ($filter === 'website') {
    $where[] = "c.id IN (
        SELECT DISTINCT cs2.client_id 
        FROM client_subscriptions cs2 
        JOIN services s2 ON s2.id = cs2.service_id 
        WHERE cs2.status != 'cancelled' AND (s2.name LIKE '%تصميم%' OR s2.name LIKE '%موقع%' OR s2.name LIKE '%ويب%' OR s2.name LIKE '%web%')
    )";
} elseif ($filter === 'domain_us') {
    $where[] = "c.id IN (
        SELECT DISTINCT cs2.client_id 
        FROM client_subscriptions cs2 
        JOIN services s2 ON s2.id = cs2.service_id 
        WHERE cs2.status != 'cancelled' AND (s2.name LIKE '%دومين%' OR s2.name LIKE '%domain%')
    )";
} elseif ($filter === 'domain_them') {
    $where[] = "c.domain IS NOT NULL AND c.domain != '' AND c.id NOT IN (
        SELECT DISTINCT cs2.client_id 
        FROM client_subscriptions cs2 
        JOIN services s2 ON s2.id = cs2.service_id 
        WHERE cs2.status != 'cancelled' AND (s2.name LIKE '%دومين%' OR s2.name LIKE '%domain%')
    )";
} elseif ($filter === 'expiring') {
    $where[] = "c.id IN (
        SELECT DISTINCT cs2.client_id 
        FROM client_subscriptions cs2 
        WHERE cs2.status = 'active' 
          AND cs2.end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL $warningDays DAY)
    )";
}

if ($plan !== '') {
    $where[] = "c.id IN (
        SELECT DISTINCT cs2.client_id 
        FROM client_subscriptions cs2 
        WHERE cs2.status != 'cancelled' AND cs2.plan_name = ?
    )";
    $params[] = $plan;
}

$whereStr = implode(' AND ', $where);

// Dynamic stats calculations based on applied filters
$totalClientsCountStmt = $db->prepare("SELECT COUNT(*) FROM clients c WHERE $whereStr");
$totalClientsCountStmt->execute($params);
$totalClientsCount = (int)$totalClientsCountStmt->fetchColumn();

$debtClientsCountStmt = $db->prepare("
    SELECT COUNT(*) FROM (
        SELECT c.id,
               COALESCE(SUM(CASE WHEN cs.status != 'cancelled' THEN cs.price ELSE 0 END), 0) AS total_services,
               COALESCE((SELECT SUM(p.amount) FROM payments p WHERE p.client_id = c.id), 0) AS total_paid
        FROM clients c
        LEFT JOIN client_subscriptions cs ON cs.client_id = c.id
        WHERE $whereStr
        GROUP BY c.id
        HAVING (total_services - total_paid) > 0
    ) tmp
");
$debtClientsCountStmt->execute($params);
$debtClientsCount = (int)$debtClientsCountStmt->fetchColumn();

$stmtExp = $db->prepare("
    SELECT COUNT(DISTINCT c.id) 
    FROM clients c
    JOIN client_subscriptions cs ON cs.client_id = c.id
    WHERE $whereStr
      AND cs.status = 'active'
      AND cs.end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
");
$stmtExp->execute(array_merge($params, [$warningDays]));
$expiringClientsCount = (int)$stmtExp->fetchColumn();

$totalDebtsAmountStmt = $db->prepare("
    SELECT SUM(remaining) FROM (
        SELECT (COALESCE(SUM(CASE WHEN cs.status != 'cancelled' THEN cs.price ELSE 0 END), 0) - COALESCE((SELECT SUM(p.amount) FROM payments p WHERE p.client_id = c.id), 0)) AS remaining
        FROM clients c
        LEFT JOIN client_subscriptions cs ON cs.client_id = c.id
        WHERE $whereStr
        GROUP BY c.id
    ) tmp WHERE remaining > 0
");
$totalDebtsAmountStmt->execute($params);
$totalDebtsAmount = (float)$totalDebtsAmountStmt->fetchColumn();

$havingStr = "";
if ($filter === 'debt') {
    $havingStr = " HAVING (total_services - total_paid) > 0";
}

// Count
if ($filter === 'debt') {
    $countStmt = $db->prepare("
        SELECT COUNT(*) FROM (
            SELECT c.id,
                   COALESCE(SUM(CASE WHEN cs.status != 'cancelled' THEN cs.price ELSE 0 END), 0) AS total_services,
                   COALESCE((SELECT SUM(p.amount) FROM payments p WHERE p.client_id = c.id), 0) AS total_paid
            FROM clients c
            LEFT JOIN client_subscriptions cs ON cs.client_id = c.id
            WHERE $whereStr
            GROUP BY c.id
            HAVING (total_services - total_paid) > 0
        ) tmp
    ");
} else {
    $countStmt = $db->prepare("SELECT COUNT(*) FROM clients c WHERE $whereStr");
}
$countStmt->execute($params);
$totalClients = (int)$countStmt->fetchColumn();
$pager = paginate($totalClients, $perPage, $page);

// Fetch with summary
$stmt = $db->prepare("
    SELECT c.*,
           COALESCE(SUM(CASE WHEN cs.status != 'cancelled' THEN cs.price ELSE 0 END), 0) AS total_services,
           COALESCE((SELECT SUM(p.amount) FROM payments p WHERE p.client_id = c.id), 0) AS total_paid,
           COUNT(DISTINCT cs.id) AS subs_count,
           (SELECT cs2.start_date FROM client_subscriptions cs2 JOIN services s2 ON s2.id = cs2.service_id WHERE cs2.client_id = c.id AND (s2.name LIKE '%بريد%' OR s2.name LIKE '%mail%' OR s2.name LIKE '%email%' OR s2.name LIKE '%ايميل%') ORDER BY (CASE WHEN cs2.status = 'active' THEN 1 ELSE 2 END) ASC, cs2.id DESC LIMIT 1) AS sub_start,
           (SELECT cs2.end_date FROM client_subscriptions cs2 JOIN services s2 ON s2.id = cs2.service_id WHERE cs2.client_id = c.id AND (s2.name LIKE '%بريد%' OR s2.name LIKE '%mail%' OR s2.name LIKE '%email%' OR s2.name LIKE '%ايميل%') ORDER BY (CASE WHEN cs2.status = 'active' THEN 1 ELSE 2 END) ASC, cs2.id DESC LIMIT 1) AS sub_end,
           (SELECT cs2.status FROM client_subscriptions cs2 JOIN services s2 ON s2.id = cs2.service_id WHERE cs2.client_id = c.id AND (s2.name LIKE '%بريد%' OR s2.name LIKE '%mail%' OR s2.name LIKE '%email%' OR s2.name LIKE '%ايميل%') ORDER BY (CASE WHEN cs2.status = 'active' THEN 1 ELSE 2 END) ASC, cs2.id DESC LIMIT 1) AS sub_status,
           (SELECT COUNT(*) FROM client_subscriptions cs3 JOIN services s3 ON s3.id = cs3.service_id WHERE cs3.client_id = c.id AND cs3.status != 'cancelled' AND (s3.name LIKE '%دومين%' OR s3.name LIKE '%domain%')) AS has_our_domain
    FROM clients c
    LEFT JOIN client_subscriptions cs ON cs.client_id = c.id
    WHERE $whereStr
    GROUP BY c.id
    $havingStr
    ORDER BY COALESCE(
        (SELECT cs2.start_date FROM client_subscriptions cs2 JOIN services s2 ON s2.id = cs2.service_id WHERE cs2.client_id = c.id AND cs2.status != 'cancelled' AND (s2.name LIKE '%دومين%' OR s2.name LIKE '%domain%') ORDER BY cs2.start_date DESC LIMIT 1),
        (SELECT cs3.start_date FROM client_subscriptions cs3 JOIN services s3 ON s3.id = cs3.service_id WHERE cs3.client_id = c.id AND cs3.status != 'cancelled' AND (s3.name LIKE '%بريد%' OR s3.name LIKE '%mail%' OR s3.name LIKE '%email%') ORDER BY cs3.start_date DESC LIMIT 1),
        c.created_at
    ) DESC, c.id DESC
    LIMIT ? OFFSET ?
");
$stmt->execute(array_merge($params, [$perPage, $pager['offset']]));
$clients = $stmt->fetchAll();

// AJAX get all IDs/names/mobiles for bulk actions matching current filters
if (isset($_GET['get_all_ids'])) {
    header('Content-Type: application/json');
    $extraWhere = '';
    $extraParams = [];
    
    if (!empty($_GET['selected_ids'])) {
        $ids = array_filter(array_map('intval', explode(',', $_GET['selected_ids'])));
        if (!empty($ids)) {
            $inPlaceholder = implode(',', array_fill(0, count($ids), '?'));
            $extraWhere = " AND c.id IN ($inPlaceholder)";
            $extraParams = $ids;
        } else {
            echo json_encode([]);
            exit;
        }
    } elseif (!empty($_GET['exclude_ids'])) {
        $ids = array_filter(array_map('intval', explode(',', $_GET['exclude_ids'])));
        if (!empty($ids)) {
            $inPlaceholder = implode(',', array_fill(0, count($ids), '?'));
            $extraWhere = " AND c.id NOT IN ($inPlaceholder)";
            $extraParams = $ids;
        }
    }

    $stmtAll = $db->prepare("
        SELECT c.id, c.name, c.mobile, c.company_name
        FROM clients c
        LEFT JOIN client_subscriptions cs ON cs.client_id = c.id
        WHERE ($whereStr) $extraWhere
        $havingStr
        GROUP BY c.id
        ORDER BY COALESCE(
            (SELECT cs2.start_date FROM client_subscriptions cs2 JOIN services s2 ON s2.id = cs2.service_id WHERE cs2.client_id = c.id AND cs2.status != 'cancelled' AND (s2.name LIKE '%دومين%' OR s2.name LIKE '%domain%') ORDER BY cs2.start_date DESC LIMIT 1),
            (SELECT cs3.start_date FROM client_subscriptions cs3 JOIN services s3 ON s3.id = cs3.service_id WHERE cs3.client_id = c.id AND cs3.status != 'cancelled' AND (s3.name LIKE '%بريد%' OR s3.name LIKE '%mail%' OR s3.name LIKE '%email%') ORDER BY cs3.start_date DESC LIMIT 1),
            c.created_at
        ) DESC, c.id DESC
    ");
    $stmtAll->execute(array_merge($params, $extraParams));
    $allFilteredClients = $stmtAll->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($allFilteredClients);
    exit;
}

// AJAX export all selected or matching clients with full detailed fields
if (isset($_GET['export_all'])) {
    header('Content-Type: application/json');
    $extraWhere = '';
    $extraParams = [];
    
    if (!empty($_GET['selected_ids'])) {
        $ids = array_filter(array_map('intval', explode(',', $_GET['selected_ids'])));
        if (!empty($ids)) {
            $inPlaceholder = implode(',', array_fill(0, count($ids), '?'));
            $extraWhere = " AND c.id IN ($inPlaceholder)";
            $extraParams = $ids;
        } else {
            echo json_encode(['success' => true, 'clients' => []]);
            exit;
        }
    } elseif (!empty($_GET['exclude_ids'])) {
        $ids = array_filter(array_map('intval', explode(',', $_GET['exclude_ids'])));
        if (!empty($ids)) {
            $inPlaceholder = implode(',', array_fill(0, count($ids), '?'));
            $extraWhere = " AND c.id NOT IN ($inPlaceholder)";
            $extraParams = $ids;
        }
    }

    $stmtExport = $db->prepare("
        SELECT c.*,
               COALESCE(SUM(CASE WHEN cs.status != 'cancelled' THEN cs.price ELSE 0 END), 0) AS total_services,
               COALESCE((SELECT SUM(p.amount) FROM payments p WHERE p.client_id = c.id), 0) AS total_paid,
               COUNT(DISTINCT cs.id) AS subs_count,
               (SELECT cs2.start_date FROM client_subscriptions cs2 JOIN services s2 ON s2.id = cs2.service_id WHERE cs2.client_id = c.id AND (s2.name LIKE '%بريد%' OR s2.name LIKE '%mail%' OR s2.name LIKE '%email%' OR s2.name LIKE '%ايميل%') ORDER BY (CASE WHEN cs2.status = 'active' THEN 1 ELSE 2 END) ASC, cs2.id DESC LIMIT 1) AS sub_start,
               (SELECT cs2.end_date FROM client_subscriptions cs2 JOIN services s2 ON s2.id = cs2.service_id WHERE cs2.client_id = c.id AND (s2.name LIKE '%بريد%' OR s2.name LIKE '%mail%' OR s2.name LIKE '%email%' OR s2.name LIKE '%ايميل%') ORDER BY (CASE WHEN cs2.status = 'active' THEN 1 ELSE 2 END) ASC, cs2.id DESC LIMIT 1) AS sub_end,
               (SELECT cs2.status FROM client_subscriptions cs2 JOIN services s2 ON s2.id = cs2.service_id WHERE cs2.client_id = c.id AND (s2.name LIKE '%بريد%' OR s2.name LIKE '%mail%' OR s2.name LIKE '%email%' OR s2.name LIKE '%ايميل%') ORDER BY (CASE WHEN cs2.status = 'active' THEN 1 ELSE 2 END) ASC, cs2.id DESC LIMIT 1) AS sub_status
        FROM clients c
        LEFT JOIN client_subscriptions cs ON cs.client_id = c.id
        WHERE ($whereStr) $extraWhere
        GROUP BY c.id
        $havingStr
        ORDER BY COALESCE(
            (SELECT cs2.start_date FROM client_subscriptions cs2 JOIN services s2 ON s2.id = cs2.service_id WHERE cs2.client_id = c.id AND cs2.status != 'cancelled' AND (s2.name LIKE '%دومين%' OR s2.name LIKE '%domain%') ORDER BY cs2.start_date DESC LIMIT 1),
            (SELECT cs3.start_date FROM client_subscriptions cs3 JOIN services s3 ON s3.id = cs3.service_id WHERE cs3.client_id = c.id AND cs3.status != 'cancelled' AND (s3.name LIKE '%بريد%' OR s3.name LIKE '%mail%' OR s3.name LIKE '%email%') ORDER BY cs3.start_date DESC LIMIT 1),
            c.created_at
        ) DESC, c.id DESC
    ");
    $stmtExport->execute(array_merge($params, $extraParams));
    $exportClients = $stmtExport->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'clients' => $exportClients]);
    exit;
}

// AJAX live search handler
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    ob_start();
    ?>
    <?php if (empty($clients)): ?>
    <tr>
      <td colspan="11">
        <div class="empty-state">
          <div class="empty-icon"><i class="fas fa-users-slash"></i></div>
          <p class="empty-title">لا يوجد عملاء</p>
          <p class="empty-text"><?= $search ? 'لم يتطابق أي عميل مع بحثك.' : 'ابدأ بإضافة أول عميل.' ?></p>
          <?php if (hasPermission('add_clients') && !$search): ?>
          <a href="add.php" class="btn btn-primary"><i class="fas fa-user-plus"></i> إضافة عميل</a>
          <?php endif; ?>
        </div>
      </td>
    </tr>
    <?php else: ?>
    <?php foreach ($clients as $i => $client):
      $remaining = $client['total_services'] - $client['total_paid'];
    ?>
    <tr onclick="if(!event.target.closest('a') && !event.target.closest('button') && !event.target.closest('input')) window.location='view.php?id=<?= $client['id'] ?>';" style="cursor:pointer;">
      <td style="text-align: center;" onclick="event.stopPropagation();">
        <input type="checkbox" class="client-checkbox" value="<?= $client['id'] ?>" data-name="<?= e($client['name']) ?>" data-mobile="<?= e($client['mobile']) ?>" data-company="<?= e($client['company_name']) ?>" style="width: 17px; height: 17px; cursor: pointer;">
      </td>
      <td class="text-muted"><?= $pager['offset'] + $i + 1 ?></td>
      <td>
        <div style="display:flex;align-items:center;gap:10px;">
          <div style="width:38px;height:38px;border-radius:10px;background:linear-gradient(135deg,var(--primary-light),var(--primary));
                      display:flex;align-items:center;justify-content:center;color:#fff;font-weight:800;font-size:15px;flex-shrink:0;">
            <?= e(mb_substr($client['name'], 0, 1, 'UTF-8')) ?>
          </div>
          <div>
            <a href="view.php?id=<?= $client['id'] ?>" style="font-weight:700;color:var(--text-primary);display:block;">
              <?= e($client['name']) ?>
            </a>
            <?php if ($client['company_name']): ?>
            <span style="font-size:12px;color:var(--text-muted);display:block;"><?= e($client['company_name']) ?></span>
            <?php endif; ?>
          </div>
        </div>
      </td>
      <td>
        <a href="https://wa.me/<?= preg_replace('/\D/', '', $client['mobile']) ?>" target="_blank"
           style="color:var(--text-primary);display:flex;align-items:center;gap:6px;">
          <i class="fab fa-whatsapp" style="color:#25D366;font-size:15px;"></i>
          <?= e($client['mobile']) ?>
        </a>
      </td>
      <td class="text-muted">
        <div style="font-weight:600;color:var(--text-primary);"><?= e($client['activity'] ?: '—') ?></div>
        <?php if (!empty($client['domain'])): ?>
        <div style="margin-top:4px;display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
          <a href="http://<?= e($client['domain']) ?>" target="_blank" style="font-size:11px;color:var(--primary);word-break:break-all;font-weight:600;">
            <i class="fas fa-link" style="font-size:9px;margin-left:2px;"></i><?= e($client['domain']) ?>
          </a>
          <?php if ($client['has_our_domain'] > 0): ?>
            <span class="badge badge-success" style="font-size:9px;padding:1px 5px;border-radius:4px;">دومين من خلالنا</span>
          <?php else: ?>
            <span class="badge badge-info" style="font-size:9px;padding:1px 5px;border-radius:4px;">دومين من العميل</span>
          <?php endif; ?>
        </div>
        <?php else: ?>
          <div style="margin-top:4px;">
            <span class="badge badge-secondary" style="font-size:9px;padding:1px 5px;border-radius:4px;"><i class="fas fa-globe-slash" style="margin-left:3px;"></i>بدون دومين</span>
          </div>
        <?php endif; ?>
      </td>
      <td>
        <?php if ($client['subs_count'] > 0): ?>
        <span class="badge badge-primary"><?= $client['subs_count'] ?> خدمة</span>
        <?php else: ?>
        <span class="text-muted fs-sm">—</span>
        <?php endif; ?>
      </td>
      <td class="fw-bold"><?= formatMoney($client['total_services']) ?></td>
      <td style="color:var(--success);font-weight:600;"><?= formatMoney($client['total_paid']) ?></td>
      <td style="color:<?= $remaining > 0 ? 'var(--danger)' : 'var(--success)' ?>;font-weight:700;">
        <?= formatMoney($remaining) ?>
      </td>
      <td>
        <?= $client['status']
          ? '<span class="badge badge-success">نشط</span>'
          : '<span class="badge badge-danger">موقوف</span>' ?>
      </td>
      <td>
        <?php 
        if ($client['sub_start']) {
            $startDate = new DateTime($client['sub_start']);
            $today = new DateTime('today');
            $diffStart = $startDate->diff($today);
            $daysSubscribed = $diffStart->invert ? -$diffStart->days : $diffStart->days;

            if ($daysSubscribed >= 0) {
                echo '<div style="font-size:12px;color:var(--text-muted);margin-bottom:4px;"><i class="fas fa-calendar-alt" style="margin-left:4px;"></i>اشترك بقاله: <strong style="color:var(--text-primary);">' . $daysSubscribed . ' يوم</strong></div>';
            } else {
                echo '<div style="font-size:12px;color:var(--text-muted);margin-bottom:4px;"><i class="fas fa-calendar-alt" style="margin-left:4px;"></i>يبدأ بعد: <strong style="color:var(--text-primary);">' . abs($daysSubscribed) . ' يوم</strong></div>';
            }

            if ($client['sub_end']) {
                $daysLeft = daysUntilExpiry($client['sub_end']);
                if ($daysLeft >= 0) {
                    $color = $daysLeft <= 30 ? 'var(--warning)' : 'var(--success)';
                    echo '<div style="font-size:12px;color:' . $color . ';font-weight:700;"><i class="fas fa-hourglass-half" style="margin-left:4px;"></i>باقي له: ' . $daysLeft . ' يوم</div>';
                } else {
                    echo '<div style="font-size:12px;color:var(--danger);font-weight:700;"><i class="fas fa-exclamation-circle" style="margin-left:4px;"></i>منتهي منذ: ' . abs($daysLeft) . ' يوم</div>';
                }
            } else {
                echo '<div style="font-size:12px;color:var(--success);font-weight:700;"><i class="fas fa-infinity" style="margin-left:4px;"></i>مفتوح بدون انتهاء</div>';
            }
        } else {
            echo '<span class="text-muted fs-sm">— لا يوجد اشتراك —</span>';
        }
        ?>
      </td>
    </tr>
    <?php endforeach; ?>
    <?php endif; ?>
    <?php
    $tbodyHtml = ob_get_clean();

    ob_start();
    ?>
    <?php if ($pager['total_pages'] > 1): ?>
    <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
      <span class="text-muted fs-sm">
        عرض <?= $pager['offset'] + 1 ?> - <?= min($pager['offset'] + $perPage, $totalClients) ?>
        من <?= $totalClients ?> عميل
      </span>
      <div class="pagination">
        <?php
        $queryBase = http_build_query(array_filter(['search' => $search, 'status' => $status, 'filter' => $filter, 'plan' => $plan]));
        $sep = $queryBase ? '&' : '';
        ?>
        <a href="?<?= $queryBase ?><?= $sep ?>page=<?= $pager['current_page'] - 1 ?>"
           class="page-btn <?= !$pager['has_prev'] ? 'disabled' : '' ?>" data-page="<?= $pager['current_page'] - 1 ?>">
          <i class="fas fa-chevron-right"></i>
        </a>
        <?php for ($p = 1; $p <= $pager['total_pages']; $p++): ?>
        <a href="?<?= $queryBase ?><?= $sep ?>page=<?= $p ?>"
           class="page-btn <?= $p === $pager['current_page'] ? 'active' : '' ?>" data-page="<?= $p ?>">
          <?= $p ?>
        </a>
        <?php endfor; ?>
        <a href="?<?= $queryBase ?><?= $sep ?>page=<?= $pager['current_page'] + 1 ?>"
           class="page-btn <?= !$pager['has_next'] ? 'disabled' : '' ?>" data-page="<?= $pager['current_page'] + 1 ?>">
          <i class="fas fa-chevron-left"></i>
        </a>
      </div>
    </div>
    <?php endif; ?>
    <?php
    $paginationHtml = ob_get_clean();

    echo json_encode([
        'tbody' => $tbodyHtml,
        'pagination' => $paginationHtml,
        'total_filtered_clients' => $totalClients,
        'subtitle' => 'إجمالي ' . $totalClients . ' عميل مسجّل في النظام',
        'stats' => [
            'total_clients' => $totalClientsCount,
            'debt_clients' => $debtClientsCount,
            'expiring_clients' => $expiringClientsCount,
            'total_debts' => formatMoney($totalDebtsAmount)
        ]
    ]);
    exit;
}

$pageTitle  = 'العملاء';
$activePage = 'clients';
$depth      = 1;
require_once INCLUDES_PATH . '/header.php';
?>

<style>
.stat-card {
  transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
}
.stat-card:hover {
  transform: translateY(-3px);
  box-shadow: 0 10px 25px rgba(0,0,0,0.06) !important;
  border-color: var(--primary) !important;
}
.stat-card.active {
  border-color: var(--primary) !important;
  background-color: rgba(var(--primary-rgb, 36, 86, 164), 0.02) !important;
  box-shadow: 0 10px 25px rgba(0,0,0,0.06) !important;
}
</style>

<div class="page-header">
  <div class="page-header-text">
    <h1 class="page-title"><i class="fas fa-users" style="color:var(--primary-light);margin-left:8px;"></i>العملاء</h1>
    <p class="page-subtitle">إجمالي <?= $totalClients ?> عميل مسجّل في النظام</p>
  </div>
  <div class="page-actions" style="display:flex;gap:10px;align-items:center;">
    <?php if (hasPermission('send_whatsapp')): ?>
    <button type="button" class="btn btn-success" id="btn-bulk-whatsapp" style="display:none;" onclick="openBulkWhatsappModal()">
      <i class="fab fa-whatsapp"></i>
      إرسال رسالة جماعية (<span id="selected-count">0</span>)
    </button>
    <?php endif; ?>
    <button type="button" class="btn btn-outline" onclick="exportSelectedClients()">
      <i class="fas fa-file-excel" style="color:#217346;"></i>
      تصدير Excel
    </button>
    <?php if (hasPermission('add_clients')): ?>
    <a href="add.php" class="btn btn-primary" id="btn-add-client">
      <i class="fas fa-user-plus"></i>
      إضافة عميل
    </a>
    <?php endif; ?>
  </div>
</div>

<!-- Quick Stats Cards -->
<div class="stats-cards-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 20px;">
  <!-- Total Clients Card -->
  <div class="stat-card <?= $filter === '' ? 'active' : '' ?>" onclick="applyStatsFilter('')" style="background: var(--card-bg); border-radius: 12px; padding: 18px; border: 1px solid var(--border-color); cursor: pointer; display: flex; align-items: center; justify-content: space-between; box-shadow: 0 4px 12px rgba(0,0,0,0.01);">
    <div>
      <div style="font-size: 13px; color: var(--text-muted); font-weight: 600; margin-bottom: 4px;">إجمالي العملاء</div>
      <div id="stat-total-clients" style="font-size: 22px; font-weight: 800; color: var(--primary);"><?= $totalClientsCount ?></div>
    </div>
    <div style="width: 44px; height: 44px; border-radius: 10px; background: rgba(36, 86, 164, 0.1); display: flex; align-items: center; justify-content: center; color: var(--primary);">
      <i class="fas fa-users" style="font-size: 18px;"></i>
    </div>
  </div>

  <!-- Debt Clients Card -->
  <div class="stat-card <?= $filter === 'debt' ? 'active' : '' ?>" onclick="applyStatsFilter('debt')" style="background: var(--card-bg); border-radius: 12px; padding: 18px; border: 1px solid var(--border-color); cursor: pointer; display: flex; align-items: center; justify-content: space-between; box-shadow: 0 4px 12px rgba(0,0,0,0.01);">
    <div>
      <div style="font-size: 13px; color: var(--text-muted); font-weight: 600; margin-bottom: 4px;">عملاء عليهم مديونية</div>
      <div id="stat-debt-clients" style="font-size: 22px; font-weight: 800; color: var(--danger);"><?= $debtClientsCount ?></div>
    </div>
    <div style="width: 44px; height: 44px; border-radius: 10px; background: rgba(239, 68, 68, 0.1); display: flex; align-items: center; justify-content: center; color: var(--danger);">
      <i class="fas fa-hand-holding-dollar" style="font-size: 18px;"></i>
    </div>
  </div>

  <!-- Expiring Soon Card -->
  <div class="stat-card <?= $filter === 'expiring' ? 'active' : '' ?>" onclick="applyStatsFilter('expiring')" style="background: var(--card-bg); border-radius: 12px; padding: 18px; border: 1px solid var(--border-color); cursor: pointer; display: flex; align-items: center; justify-content: space-between; box-shadow: 0 4px 12px rgba(0,0,0,0.01);">
    <div>
      <div style="font-size: 13px; color: var(--text-muted); font-weight: 600; margin-bottom: 4px;">تجديدات قريبة</div>
      <div id="stat-expiring-clients" style="font-size: 22px; font-weight: 800; color: var(--warning);"><?= $expiringClientsCount ?></div>
    </div>
    <div style="width: 44px; height: 44px; border-radius: 10px; background: rgba(240, 165, 0, 0.1); display: flex; align-items: center; justify-content: center; color: var(--warning);">
      <i class="fas fa-calendar-exclamation" style="font-size: 18px;"></i>
    </div>
  </div>

  <!-- Total Debts Amount Card -->
  <div class="stat-card" style="background: var(--card-bg); border-radius: 12px; padding: 18px; border: 1px solid var(--border-color); display: flex; align-items: center; justify-content: space-between; box-shadow: 0 4px 12px rgba(0,0,0,0.01);">
    <div>
      <div style="font-size: 13px; color: var(--text-muted); font-weight: 600; margin-bottom: 4px;">إجمالي المديونيات</div>
      <div id="stat-total-debts" style="font-size: 18px; font-weight: 800; color: var(--success);"><?= formatMoney($totalDebtsAmount) ?></div>
    </div>
    <div style="width: 44px; height: 44px; border-radius: 10px; background: rgba(16, 185, 129, 0.1); display: flex; align-items: center; justify-content: center; color: var(--success);">
      <i class="fas fa-money-bill-trend-up" style="font-size: 18px;"></i>
    </div>
  </div>
</div>

<!-- Filters -->
<div class="card mb-2" style="margin-bottom:16px;">
  <div class="filters-bar">
    <form method="GET" action="index.php" style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;width:100%;" id="searchForm">

      <div class="search-box">
        <i class="fas fa-search search-icon"></i>
        <input type="text" name="search" class="form-control" id="searchInput"
               placeholder="ابحث بالاسم أو الشركة أو الموبايل أو الدومين..."
               value="<?= e($search) ?>" autocomplete="off">
      </div>

      <select name="status" class="form-control" style="width:auto;">
        <option value="">كل الحالات</option>
        <option value="1" <?= $status === '1' ? 'selected' : '' ?>>نشط</option>
        <option value="0" <?= $status === '0' ? 'selected' : '' ?>>موقوف</option>
      </select>

      <select name="filter" class="form-control" style="width:auto;">
        <option value="">كل التصنيفات المخصصة</option>
        <option value="debt" <?= $filter === 'debt' ? 'selected' : '' ?>>عملاء عليهم مديونية</option>
        <option value="expiring" <?= $filter === 'expiring' ? 'selected' : '' ?>>عملاء لديهم تجديدات قريبة</option>
        <option value="website" <?= $filter === 'website' ? 'selected' : '' ?>>عملاء صممنا لهم مواقع</option>
        <option value="domain_us" <?= $filter === 'domain_us' ? 'selected' : '' ?>>عملاء حجزنا لهم الدومين</option>
        <option value="domain_them" <?= $filter === 'domain_them' ? 'selected' : '' ?>>عملاء حجزوا الدومين بأنفسهم</option>
      </select>

      <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> بحث</button>
      <?php if ($search || $status !== '1' || $filter !== '' || $plan !== ''): ?>
      <a href="index.php" class="btn btn-outline" id="clearSearchBtn"><i class="fas fa-times"></i> مسح</a>
      <?php endif; ?>
    </form>
  </div>
</div>

<!-- Table -->
<div class="card">
  <!-- Select All Filtered Banner -->
  <div id="select-all-filtered-banner" style="display:none; background:rgba(36, 86, 164, 0.08); border-bottom:1.5px solid var(--border-color); padding:10px 15px; font-size:13px; color:var(--text-primary); text-align:center; align-items:center; justify-content:center; gap:8px;">
    <span id="banner-text">تم تحديد <strong id="visible-checked-count">0</strong> عميل في هذه الصفحة.</span>
    <button type="button" id="btn-select-all-filtered" class="btn btn-sm" style="background:var(--primary);color:#fff;padding:4px 12px;font-size:11.5px;margin-right:8px;border-radius:4px;border:none;cursor:pointer;font-weight:700;">
      تحديد جميع العملاء الـ (<span id="total-filtered-count-val">0</span>) المطابقين للفلترة الحالية
    </button>
    <button type="button" id="btn-clear-filtered-selection" class="btn btn-sm btn-outline-danger" style="padding:4px 12px;font-size:11.5px;margin-right:8px;border-radius:4px;cursor:pointer;font-weight:700;">
      إلغاء التحديد
    </button>
  </div>

  <div class="table-wrapper">
    <table class="data-table">
      <thead>
        <tr>
          <th style="width: 40px; text-align: center;"><input type="checkbox" id="selectAllClients" style="width: 17px; height: 17px; cursor: pointer;"></th>
          <th>#</th>
          <th>العميل</th>
          <th>الموبايل</th>
          <th>النشاط</th>
          <th>الخدمات</th>
          <th>الإجمالي</th>
          <th>المدفوع</th>
          <th>المتبقي</th>
          <th>الحالة</th>
          <th>الاشتراك / المتبقي</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($clients)): ?>
        <tr>
          <td colspan="11">
            <div class="empty-state">
              <div class="empty-icon"><i class="fas fa-users-slash"></i></div>
              <p class="empty-title">لا يوجد عملاء</p>
              <p class="empty-text"><?= $search ? 'لم يتطابق أي عميل مع بحثك.' : 'ابدأ بإضافة أول عميل.' ?></p>
              <?php if (hasPermission('add_clients') && !$search): ?>
              <a href="add.php" class="btn btn-primary"><i class="fas fa-user-plus"></i> إضافة عميل</a>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php else: ?>
        <?php foreach ($clients as $i => $client):
          $remaining = $client['total_services'] - $client['total_paid'];
        ?>
        <tr onclick="if(!event.target.closest('a') && !event.target.closest('button') && !event.target.closest('input')) window.location='view.php?id=<?= $client['id'] ?>';" style="cursor:pointer;">
          <td style="text-align: center;" onclick="event.stopPropagation();">
            <input type="checkbox" class="client-checkbox" value="<?= $client['id'] ?>" data-name="<?= e($client['name']) ?>" data-mobile="<?= e($client['mobile']) ?>" data-company="<?= e($client['company_name']) ?>" style="width: 17px; height: 17px; cursor: pointer;">
          </td>
          <td class="text-muted"><?= $pager['offset'] + $i + 1 ?></td>
          <td>
            <div style="display:flex;align-items:center;gap:10px;">
              <div style="width:38px;height:38px;border-radius:10px;background:linear-gradient(135deg,var(--primary-light),var(--primary));
                          display:flex;align-items:center;justify-content:center;color:#fff;font-weight:800;font-size:15px;flex-shrink:0;">
                <?= e(mb_substr($client['name'], 0, 1, 'UTF-8')) ?>
              </div>
              <div>
                <a href="view.php?id=<?= $client['id'] ?>" style="font-weight:700;color:var(--text-primary);display:block;">
                  <?= e($client['name']) ?>
                </a>
                <?php if ($client['company_name']): ?>
                <span style="font-size:12px;color:var(--text-muted);display:block;"><?= e($client['company_name']) ?></span>
                <?php endif; ?>
              </div>
            </div>
          </td>
          <td>
            <a href="https://wa.me/<?= preg_replace('/\D/', '', $client['mobile']) ?>" target="_blank"
               style="color:var(--text-primary);display:flex;align-items:center;gap:6px;">
              <i class="fab fa-whatsapp" style="color:#25D366;font-size:15px;"></i>
              <?= e($client['mobile']) ?>
            </a>
          </td>
          <td class="text-muted">
            <div style="font-weight:600;color:var(--text-primary);"><?= e($client['activity'] ?: '—') ?></div>
            <?php if (!empty($client['domain'])): ?>
            <div style="margin-top:4px;display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
              <a href="http://<?= e($client['domain']) ?>" target="_blank" style="font-size:11px;color:var(--primary);word-break:break-all;font-weight:600;">
                <i class="fas fa-link" style="font-size:9px;margin-left:2px;"></i><?= e($client['domain']) ?>
              </a>
              <?php if ($client['has_our_domain'] > 0): ?>
                <span class="badge badge-success" style="font-size:9px;padding:1px 5px;border-radius:4px;">دومين من خلالنا</span>
              <?php else: ?>
                <span class="badge badge-info" style="font-size:9px;padding:1px 5px;border-radius:4px;">دومين من العميل</span>
              <?php endif; ?>
            </div>
            <?php else: ?>
              <div style="margin-top:4px;">
                <span class="badge badge-secondary" style="font-size:9px;padding:1px 5px;border-radius:4px;"><i class="fas fa-globe-slash" style="margin-left:3px;"></i>بدون دومين</span>
              </div>
            <?php endif; ?>
          </td>
          <td>
            <?php if ($client['subs_count'] > 0): ?>
            <span class="badge badge-primary"><?= $client['subs_count'] ?> خدمة</span>
            <?php else: ?>
            <span class="text-muted fs-sm">—</span>
            <?php endif; ?>
          </td>
          <td class="fw-bold"><?= formatMoney($client['total_services']) ?></td>
          <td style="color:var(--success);font-weight:600;"><?= formatMoney($client['total_paid']) ?></td>
          <td style="color:<?= $remaining > 0 ? 'var(--danger)' : 'var(--success)' ?>;font-weight:700;">
            <?= formatMoney($remaining) ?>
          </td>
          <td>
            <?= $client['status']
              ? '<span class="badge badge-success">نشط</span>'
              : '<span class="badge badge-danger">موقوف</span>' ?>
          </td>
          <td>
            <?php 
            if ($client['sub_start']) {
                $startDate = new DateTime($client['sub_start']);
                $today = new DateTime('today');
                $diffStart = $startDate->diff($today);
                $daysSubscribed = $diffStart->invert ? -$diffStart->days : $diffStart->days;

                if ($daysSubscribed >= 0) {
                    echo '<div style="font-size:12px;color:var(--text-muted);margin-bottom:4px;"><i class="fas fa-calendar-alt" style="margin-left:4px;"></i>اشترك بقاله: <strong style="color:var(--text-primary);">' . $daysSubscribed . ' يوم</strong></div>';
                } else {
                    echo '<div style="font-size:12px;color:var(--text-muted);margin-bottom:4px;"><i class="fas fa-calendar-alt" style="margin-left:4px;"></i>يبدأ بعد: <strong style="color:var(--text-primary);">' . abs($daysSubscribed) . ' يوم</strong></div>';
                }

                if ($client['sub_end']) {
                    $daysLeft = daysUntilExpiry($client['sub_end']);
                    if ($daysLeft >= 0) {
                        $color = $daysLeft <= 30 ? 'var(--warning)' : 'var(--success)';
                        echo '<div style="font-size:12px;color:' . $color . ';font-weight:700;"><i class="fas fa-hourglass-half" style="margin-left:4px;"></i>باقي له: ' . $daysLeft . ' يوم</div>';
                    } else {
                        echo '<div style="font-size:12px;color:var(--danger);font-weight:700;"><i class="fas fa-exclamation-circle" style="margin-left:4px;"></i>منتهي منذ: ' . abs($daysLeft) . ' يوم</div>';
                    }
                } else {
                    echo '<div style="font-size:12px;color:var(--success);font-weight:700;"><i class="fas fa-infinity" style="margin-left:4px;"></i>مفتوح بدون انتهاء</div>';
                }
            } else {
                echo '<span class="text-muted fs-sm">— لا يوجد اشتراك —</span>';
            }
            ?>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Pagination -->
  <div class="card-footer" style="<?= $pager['total_pages'] <= 1 ? 'display:none;' : '' ?>">
    <?php if ($pager['total_pages'] > 1): ?>
    <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
      <span class="text-muted fs-sm">
        عرض <?= $pager['offset'] + 1 ?> - <?= min($pager['offset'] + $perPage, $totalClients) ?>
        من <?= $totalClients ?> عميل
      </span>
      <div class="pagination">
        <?php
        $queryBase = http_build_query(array_filter(['search' => $search, 'status' => $status, 'filter' => $filter, 'plan' => $plan]));
        $sep = $queryBase ? '&' : '';
        ?>
        <a href="?<?= $queryBase ?><?= $sep ?>page=<?= $pager['current_page'] - 1 ?>"
           class="page-btn <?= !$pager['has_prev'] ? 'disabled' : '' ?>" data-page="<?= $pager['current_page'] - 1 ?>">
          <i class="fas fa-chevron-right"></i>
        </a>
        <?php for ($p = 1; $p <= $pager['total_pages']; $p++): ?>
        <a href="?<?= $queryBase ?><?= $sep ?>page=<?= $p ?>"
           class="page-btn <?= $p === $pager['current_page'] ? 'active' : '' ?>" data-page="<?= $p ?>">
          <?= $p ?>
        </a>
        <?php endfor; ?>
        <a href="?<?= $queryBase ?><?= $sep ?>page=<?= $pager['current_page'] + 1 ?>"
           class="page-btn <?= !$pager['has_next'] ? 'disabled' : '' ?>" data-page="<?= $pager['current_page'] + 1 ?>">
          <i class="fas fa-chevron-left"></i>
        </a>
      </div>
    </div>
    <?php endif; ?>
  </div>
</div>

<script>
function applyStatsFilter(filterVal) {
    const filterSelect = document.querySelector('select[name="filter"]');
    if (filterSelect) {
        filterSelect.value = filterVal;
        
        // Highlight active card
        document.querySelectorAll('.stat-card').forEach(card => {
            card.classList.remove('active');
        });
        
        const clickedCard = event.currentTarget;
        if (clickedCard) {
            clickedCard.classList.add('active');
        }
        
        // Clear search inputs to make card click clean
        const searchInput = document.getElementById('searchInput');
        if (searchInput) searchInput.value = '';
        
        const statusSelect = document.querySelector('select[name="status"]');
        if (statusSelect) statusSelect.value = '1';

        // Trigger search
        window.doSearchGlobal(1);
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const statusSelect = document.querySelector('select[name="status"]');
    const filterSelect = document.querySelector('select[name="filter"]');
    const searchForm = document.getElementById('searchForm');
    const tbody = document.querySelector('.data-table tbody');
    const subtitle = document.querySelector('.page-subtitle');
    const cardFooter = document.querySelector('.card-footer');
    const clearSearchBtn = document.getElementById('clearSearchBtn');
    
    let debounceTimer;
    let currentPage = 1;

    // Prevent form submission on enter since we have live search
    searchForm.addEventListener('submit', function(e) {
        e.preventDefault();
        doSearch(1);
    });

    function doSearch(page = 1) {
        currentPage = page;
        const searchQuery = searchInput.value;
        const statusQuery = statusSelect.value;
        const filterQuery = filterSelect.value;

        const urlParams = new URLSearchParams(window.location.search);
        const planQuery = urlParams.get('plan') || '';

        const params = new URLSearchParams({
            search: searchQuery,
            status: statusQuery,
            filter: filterQuery,
            page: currentPage,
            ajax: 1
        });
        if (planQuery) params.append('plan', planQuery);

        // Update URL
        const cleanParams = new URLSearchParams({
            search: searchQuery,
            status: statusQuery,
            filter: filterQuery,
            page: currentPage
        });
        if (planQuery) cleanParams.append('plan', planQuery);
        if (!searchQuery) cleanParams.delete('search');
        if (statusQuery === '1') cleanParams.delete('status');
        if (!filterQuery) cleanParams.delete('filter');
        if (currentPage === 1) cleanParams.delete('page');
        
        const newUrl = window.location.pathname + (cleanParams.toString() ? '?' + cleanParams.toString() : '');
        window.history.replaceState({path: newUrl}, '', newUrl);

        // Update Clear Button visibility if it exists
        if (clearSearchBtn) {
            if (searchQuery || statusQuery !== '1' || filterQuery !== '') {
                clearSearchBtn.style.display = 'inline-flex';
            } else {
                clearSearchBtn.style.display = 'none';
            }
        }

        fetch('index.php?' + params.toString())
            .then(response => response.json())
            .then(data => {
                tbody.innerHTML = data.tbody;
                subtitle.textContent = data.subtitle;
                totalFilteredClients = parseInt(data.total_filtered_clients) || 0;
                if (page === 1) {
                    selectAllFiltered = false;
                    selectedClientIds.clear();
                    excludedClientIds.clear();
                }
                if (selectAllCheckbox) selectAllCheckbox.checked = false;
                updateSelectionState();
                
                if (data.stats) {
                    const totalEl = document.getElementById('stat-total-clients');
                    const debtEl = document.getElementById('stat-debt-clients');
                    const expiringEl = document.getElementById('stat-expiring-clients');
                    const debtsEl = document.getElementById('stat-total-debts');
                    
                    if (totalEl) totalEl.textContent = data.stats.total_clients;
                    if (debtEl) debtEl.textContent = data.stats.debt_clients;
                    if (expiringEl) expiringEl.textContent = data.stats.expiring_clients;
                    if (debtsEl) debtsEl.textContent = data.stats.total_debts;
                }
                
                if (data.pagination.trim()) {
                    cardFooter.innerHTML = data.pagination;
                    cardFooter.style.display = 'block';
                } else {
                    cardFooter.innerHTML = '';
                    cardFooter.style.display = 'none';
                }
            })
            .catch(err => console.error('Error fetching search results:', err));
    }

    // Expose search function to global scope for card clicks
    window.doSearchGlobal = doSearch;

    searchInput.addEventListener('input', function() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            // Remove active card indicators if user manually types search
            document.querySelectorAll('.stat-card').forEach(card => {
                card.classList.remove('active');
            });
            doSearch(1);
        }, 150);
    });

    statusSelect.addEventListener('change', function() {
        document.querySelectorAll('.stat-card').forEach(card => {
            card.classList.remove('active');
        });
        doSearch(1);
    });

    filterSelect.addEventListener('change', function() {
        const filterVal = filterSelect.value;
        document.querySelectorAll('.stat-card').forEach(card => {
            card.classList.remove('active');
        });
        
        let targetCard = null;
        if (filterVal === '') {
            targetCard = document.querySelector('.stat-card[onclick="applyStatsFilter(\'\')"]');
        } else if (filterVal === 'debt') {
            targetCard = document.querySelector('.stat-card[onclick="applyStatsFilter(\'debt\')"]');
        } else if (filterVal === 'expiring') {
            targetCard = document.querySelector('.stat-card[onclick="applyStatsFilter(\'expiring\')"]');
        }
        if (targetCard) {
            targetCard.classList.add('active');
        }
        
        doSearch(1);
    });

    // Handle pagination click
    const card = tbody.closest('.card');
    card.addEventListener('click', function(e) {
        const pageBtn = e.target.closest('.page-btn');
        if (pageBtn && !pageBtn.classList.contains('disabled') && !pageBtn.classList.contains('active')) {
            e.preventDefault();
            const page = parseInt(pageBtn.getAttribute('data-page'));
            if (page) {
                doSearch(page);
            }
        }
    });

    // Checkbox selections management
    const selectAllCheckbox = document.getElementById('selectAllClients');
    const bulkButton = document.getElementById('btn-bulk-whatsapp');
    const selectedCountSpan = document.getElementById('selected-count');

    // Banner elements
    const selectAllBanner = document.getElementById('select-all-filtered-banner');
    const visibleCheckedCount = document.getElementById('visible-checked-count');
    const totalFilteredCountVal = document.getElementById('total-filtered-count-val');
    const btnSelectAllFiltered = document.getElementById('btn-select-all-filtered');
    const btnClearFilteredSelection = document.getElementById('btn-clear-filtered-selection');
    const bannerText = document.getElementById('banner-text');

    function updateSelectionState() {
        const checkboxes = document.querySelectorAll('.client-checkbox');
        
        // 1. Sync checkboxes checks based on the selection state
        checkboxes.forEach(cb => {
            const id = parseInt(cb.value);
            if (selectAllFiltered) {
                cb.checked = !excludedClientIds.has(id);
            } else {
                cb.checked = selectedClientIds.has(id);
            }
        });
        
        const checked = document.querySelectorAll('.client-checkbox:checked');
        
        // 2. Sync master selectAllCheckbox
        if (selectAllCheckbox) {
            selectAllCheckbox.checked = checkboxes.length > 0 && checked.length === checkboxes.length;
        }
        
        const totalCount = selectAllFiltered ? (totalFilteredClients - excludedClientIds.size) : selectedClientIds.size;
        
        // 3. Show/hide and update banners
        if (selectAllCheckbox && selectAllCheckbox.checked && totalFilteredClients > checked.length && !selectAllFiltered) {
            selectAllBanner.style.display = 'flex';
            visibleCheckedCount.textContent = checked.length;
            totalFilteredCountVal.textContent = totalFilteredClients;
            btnSelectAllFiltered.style.display = 'inline-block';
            bannerText.innerHTML = `تم تحديد <strong>${checked.length}</strong> عميل في هذه الصفحة.`;
        } else if (selectAllFiltered) {
            selectAllBanner.style.display = 'flex';
            btnSelectAllFiltered.style.display = 'none';
            bannerText.innerHTML = `تم تحديد جميع العملاء الـ <strong>${totalFilteredClients - excludedClientIds.size}</strong> المطابقين للفلترة الحالية.`;
        } else {
            selectAllBanner.style.display = 'none';
        }
        
        if (bulkButton) {
            if (totalCount > 0) {
                selectedCountSpan.textContent = totalCount;
                bulkButton.style.display = 'inline-flex';
            } else {
                bulkButton.style.display = 'none';
            }
        }
    }

    if (btnSelectAllFiltered) {
        btnSelectAllFiltered.addEventListener('click', function() {
            selectAllFiltered = true;
            excludedClientIds.clear();
            selectedClientIds.clear();
            updateSelectionState();
        });
    }

    if (btnClearFilteredSelection) {
        btnClearFilteredSelection.addEventListener('click', function() {
            selectAllFiltered = false;
            excludedClientIds.clear();
            selectedClientIds.clear();
            if (selectAllCheckbox) selectAllCheckbox.checked = false;
            document.querySelectorAll('.client-checkbox').forEach(cb => {
                cb.checked = false;
            });
            updateSelectionState();
        });
    }

    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            const isChecked = selectAllCheckbox.checked;
            document.querySelectorAll('.client-checkbox').forEach(cb => {
                cb.checked = isChecked;
                const id = parseInt(cb.value);
                if (isChecked) {
                    if (selectAllFiltered) {
                        excludedClientIds.delete(id);
                    } else {
                        selectedClientIds.add(id);
                    }
                } else {
                    if (selectAllFiltered) {
                        excludedClientIds.add(id);
                    } else {
                        selectedClientIds.delete(id);
                    }
                }
            });
            updateSelectionState();
        });
    }

    tbody.addEventListener('change', function(e) {
        if (e.target.classList.contains('client-checkbox')) {
            const cb = e.target;
            const id = parseInt(cb.value);
            if (cb.checked) {
                if (selectAllFiltered) {
                    excludedClientIds.delete(id);
                } else {
                    selectedClientIds.add(id);
                }
            } else {
                if (selectAllFiltered) {
                    excludedClientIds.add(id);
                } else {
                    selectedClientIds.delete(id);
                }
            }
            updateSelectionState();
        }
    });

    // We also hook into search/filter requests to reset checkboxes
    const originalDoSearch = window.doSearchGlobal;
    window.doSearchGlobal = function(page) {
        if (page === 1) {
            selectAllFiltered = false;
            selectedClientIds.clear();
            excludedClientIds.clear();
            if (selectAllCheckbox) selectAllCheckbox.checked = false;
            if (bulkButton) bulkButton.style.display = 'none';
        }
        originalDoSearch(page);
    };
    
    // Initial call to sync visible checkboxes on DOM load
    updateSelectionState();
});

// Global selection state
let selectAllFiltered = false;
let selectedClientIds = new Set();
let excludedClientIds = new Set();
let totalFilteredClients = <?= (int)$totalClients ?>;
let bulkSendCancelled = false;

// Client-side Excel Export for All selected clients across pages
async function exportSelectedClients() {
    const totalCount = selectAllFiltered ? (totalFilteredClients - excludedClientIds.size) : selectedClientIds.size;
    
    // Show toast or loader
    showToast('جاري تجهيز بيانات التصدير من السيرفر...', 'info');
    
    const searchQuery = document.getElementById('searchInput').value;
    const statusSelect = document.querySelector('select[name="status"]');
    const statusQuery = statusSelect ? statusSelect.value : '1';
    const filterSelect = document.querySelector('select[name="filter"]');
    const filterQuery = filterSelect ? filterSelect.value : '';
    
    const params = new URLSearchParams({
        search: searchQuery,
        status: statusQuery,
        filter: filterQuery,
        export_all: 1
    });
    
    // Pass selected IDs or excluded IDs only if user has an active custom selection
    if (totalCount > 0) {
        if (selectAllFiltered) {
            params.append('exclude_ids', Array.from(excludedClientIds).join(','));
        } else {
            params.append('selected_ids', Array.from(selectedClientIds).join(','));
        }
    }
    
    try {
        const response = await fetch('index.php?' + params.toString());
        const data = await response.json();
        
        if (data.success && data.clients) {
            downloadClientsCSV(data.clients);
        } else {
            showToast('حدث خطأ أثناء تصدير البيانات.', 'error');
        }
    } catch (err) {
        console.error(err);
        showToast('خطأ في الاتصال بالخادم.', 'error');
    }
}

function downloadClientsCSV(clients) {
    if (clients.length === 0) {
        showToast('لا توجد بيانات عملاء لتصديرها.', 'warning');
        return;
    }

    let csv = [];
    // CSV Header matching the table columns
    const header = [
        'اسم العميل',
        'اسم المستخدم',
        'السيرفر',
        'الشركة',
        'الهاتف',
        'عدد الاشتراكات',
        'إجمالي قيمة الاشتراكات',
        'إجمالي المدفوع',
        'المتبقي',
        'تاريخ البداية (آخر اشتراك)',
        'تاريخ الانتهاء (آخر اشتراك)',
        'حالة الاشتراك',
        'الملاحظات'
    ];
    csv.push(header.map(h => `"${h.replace(/"/g, '""')}"`).join(','));
    
    clients.forEach((client) => {
        const remaining = (parseFloat(client.total_services) - parseFloat(client.total_paid)).toFixed(2);
        
        let subStatus = 'لا يوجد';
        if (client.sub_status === 'active') subStatus = 'نشط';
        else if (client.sub_status === 'expired') subStatus = 'منتهي';
        else if (client.sub_status === 'cancelled') subStatus = 'ملغي';
        
        const row = [
            client.name || '',
            client.username_note || '',
            client.server_panel || 'cp.enjaz.cloud',
            client.company_name || '',
            client.mobile || '',
            client.subs_count || 0,
            client.total_services || 0,
            client.total_paid || 0,
            remaining,
            client.sub_start || '',
            client.sub_end || '',
            subStatus,
            client.notes || ''
        ];
        
        csv.push(row.map(val => {
            let data = String(val).replace(/(\r\n|\n|\r)/gm, ' ').replace(/\s+/g, ' ').trim();
            data = data.replace(/"/g, '""');
            return `"${data}"`;
        }).join(','));
    });
    
    const csvString = '\uFEFF' + csv.join('\n');
    const blob = new Blob([csvString], { type: 'text/csv;charset=utf-8;' });
    
    const link = document.createElement('a');
    if (link.download !== undefined) {
        const url = URL.createObjectURL(blob);
        link.setAttribute('href', url);
        link.setAttribute('download', 'قائمة_العملاء_إنجاز.csv');
        link.style.visibility = 'hidden';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        showToast('تم تصدير البيانات إلى Excel بنجاح');
    }
}

function openBulkWhatsappModal() {
    const totalCount = selectAllFiltered ? (totalFilteredClients - excludedClientIds.size) : selectedClientIds.size;
    document.getElementById('bulk-selected-count').textContent = totalCount;
    
    // Reset modal UI
    document.getElementById('bulkWhatsappMessage').value = '';
    document.getElementById('bulk-progress-section').style.display = 'none';
    document.getElementById('bulk-send-form-inputs').style.display = 'block';
    document.getElementById('btn-start-bulk').style.display = 'inline-flex';
    document.getElementById('btn-cancel-bulk').style.display = 'none';
    document.getElementById('bulk-progress-bar').style.width = '0%';
    document.getElementById('bulk-progress-text').textContent = '0%';
    document.getElementById('bulk-log-list').innerHTML = '';
    
    // Set default schedule time to current time + 1 hour, formatted for datetime-local (YYYY-MM-DDTHH:MM)
    const now = new Date();
    now.setHours(now.getHours() + 1);
    const tzOffset = now.getTimezoneOffset() * 60000;
    const localISOTime = (new Date(now - tzOffset)).toISOString().slice(0, 16);
    document.getElementById('bulkSendAt').value = localISOTime;
    
    // Reset Send Type radio buttons
    document.querySelector('input[name="bulkSendType"][value="now"]').checked = true;
    toggleBulkScheduleFields('now');
    
    openModal('bulkWaModal');
}

function toggleBulkScheduleFields(val) {
    const timeGroup = document.getElementById('bulk-schedule-time-group');
    const btnStart = document.getElementById('btn-start-bulk');
    if (val === 'schedule') {
        timeGroup.style.display = 'block';
        btnStart.innerHTML = '<i class="fas fa-calendar-alt"></i> جدولة الإرسال لاحقاً';
    } else {
        timeGroup.style.display = 'none';
        btnStart.innerHTML = '<i class="fas fa-paper-plane"></i> بدء الإرسال الآن';
    }
}

function closeBulkWaModal() {
    bulkSendCancelled = true;
    closeModal('bulkWaModal');
}

async function startBulkSending() {
    const messageTemplate = document.getElementById('bulkWhatsappMessage').value.trim();
    if (!messageTemplate) {
        alert('يرجى كتابة نص الرسالة.');
        return;
    }

    const minDelay = parseInt(document.getElementById('bulkMinDelay').value) || 3;
    const maxDelay = parseInt(document.getElementById('bulkMaxDelay').value) || 15;

    if (minDelay < 1 || maxDelay < minDelay) {
        alert('يرجى تحديد قيم صحيحة لفارق التوقيت.');
        return;
    }

    const sendType = document.querySelector('input[name="bulkSendType"]:checked').value;
    let sendAt = '';
    if (sendType === 'schedule') {
        sendAt = document.getElementById('bulkSendAt').value;
        if (!sendAt) {
            alert('يرجى تحديد وقت وتاريخ الإرسال.');
            return;
        }
    }

    let clientsList = [];
    const logList = document.getElementById('bulk-log-list');

    bulkSendCancelled = false;
    document.getElementById('bulk-send-form-inputs').style.display = 'none';
    document.getElementById('btn-start-bulk').style.display = 'none';
    document.getElementById('btn-cancel-bulk').style.display = 'inline-flex';
    document.getElementById('bulk-progress-section').style.display = 'block';

    const fetchLog = document.createElement('div');
    fetchLog.innerHTML = '<i class="fas fa-spinner fa-spin" style="margin-left:5px;color:var(--primary);"></i>جاري جلب بيانات جميع العملاء المحددين من السيرفر...';
    logList.appendChild(fetchLog);
    
    const searchQuery = document.getElementById('searchInput').value;
    const statusSelect = document.querySelector('select[name="status"]');
    const statusQuery = statusSelect ? statusSelect.value : '1';
    const filterSelect = document.querySelector('select[name="filter"]');
    const filterQuery = filterSelect ? filterSelect.value : '';
    
    const params = new URLSearchParams({
        search: searchQuery,
        status: statusQuery,
        filter: filterQuery,
        get_all_ids: 1
    });
    
    if (selectAllFiltered) {
        params.append('exclude_ids', Array.from(excludedClientIds).join(','));
    } else {
        params.append('selected_ids', Array.from(selectedClientIds).join(','));
    }
    
    try {
        const res = await fetch('index.php?' + params.toString());
        clientsList = await res.json();
        fetchLog.style.color = 'var(--success)';
        fetchLog.innerHTML = '<i class="fas fa-check-circle" style="margin-left:5px;"></i>تم جلب بيانات العملاء بنجاح.';
    } catch (err) {
        fetchLog.style.color = 'var(--danger)';
        fetchLog.innerHTML = '<i class="fas fa-exclamation-triangle" style="margin-left:5px;"></i>فشل جلب بيانات العملاء من السيرفر.';
        document.getElementById('btn-cancel-bulk').style.display = 'none';
        document.getElementById('btn-start-bulk').style.display = 'inline-flex';
        document.getElementById('bulk-send-form-inputs').style.display = 'block';
        return;
    }

    if (clientsList.length === 0) {
        alert('لا يوجد عملاء محددين للإرسال.');
        document.getElementById('btn-cancel-bulk').style.display = 'none';
        document.getElementById('btn-start-bulk').style.display = 'inline-flex';
        document.getElementById('bulk-send-form-inputs').style.display = 'block';
        return;
    }

    const progressBar = document.getElementById('bulk-progress-bar');
    const progressText = document.getElementById('bulk-progress-text');
    const total = clientsList.length;

    let sentCount = 0;

    for (let i = 0; i < total; i++) {
        if (bulkSendCancelled) {
            const logItem = document.createElement('div');
            logItem.style.color = 'var(--danger)';
            logItem.style.fontWeight = '700';
            logItem.innerHTML = '<i class="fas fa-ban" style="margin-left:5px;"></i>تم إيقاف العملية من قبل المستخدم.';
            logList.appendChild(logItem);
            logList.scrollTop = logList.scrollHeight;
            break;
        }

        const client = clientsList[i];
        const clientId = client.id;
        const clientName = client.name;
        const clientCompany = client.company_name || '';
        const rawMobile = client.mobile;

        // Replace placeholders
        let personalizedMsg = messageTemplate
            .replace(/{name}/g, clientName)
            .replace(/{company}/g, clientCompany);

        // Add log entry
        const currentLog = document.createElement('div');
        const verb = (sendType === 'schedule') ? 'جدولة الرسالة لـ' : 'الإرسال إلى';
        currentLog.innerHTML = `<i class="fas fa-spinner fa-spin" style="margin-left:5px;color:var(--primary);"></i>جاري ${verb} ${clientName}...`;
        logList.appendChild(currentLog);
        logList.scrollTop = logList.scrollHeight;

        try {
            // Build Form Data matching api/whatsapp.php requirements
            const formData = new FormData();
            formData.append('client_id', clientId);
            formData.append('mobile', rawMobile);
            formData.append('message', personalizedMsg);
            formData.append('msg_type', 'bulk');
            formData.append('send_type', sendType);
            if (sendType === 'schedule') {
                formData.append('send_at', sendAt);
                formData.append('min_delay', minDelay);
                formData.append('max_delay', maxDelay);
            }
            formData.append('csrf_token', '<?= csrfToken() ?>');

            const response = await fetch('../api/whatsapp.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();

            if (result.success) {
                currentLog.style.color = 'var(--success)';
                const successVerb = (sendType === 'schedule') ? 'تمت الجدولة بنجاح لـ' : 'تم الإرسال بنجاح إلى';
                currentLog.innerHTML = `<i class="fas fa-check-circle" style="margin-left:5px;"></i>${successVerb} ${clientName} ✓`;
            } else {
                currentLog.style.color = 'var(--danger)';
                const failVerb = (sendType === 'schedule') ? 'فشلت جدولة الرسالة لـ' : 'فشل الإرسال لـ';
                currentLog.innerHTML = `<i class="fas fa-times-circle" style="margin-left:5px;"></i>${failVerb} ${clientName}: ${result.message}`;
            }
        } catch (err) {
            currentLog.style.color = 'var(--danger)';
            const errorVerb = (sendType === 'schedule') ? 'خطأ اتصال أثناء جدولة الرسالة لـ' : 'خطأ اتصال أثناء الإرسال لـ';
            currentLog.innerHTML = `<i class="fas fa-exclamation-triangle" style="margin-left:5px;"></i>${errorVerb} ${clientName}`;
        }

        sentCount++;
        const pct = Math.round((sentCount / total) * 100);
        progressBar.style.width = pct + '%';
        progressText.textContent = pct + '%';

        // Delay before next message if not the last one and not scheduling
        if (i < total - 1 && !bulkSendCancelled && sendType !== 'schedule') {
            const delaySec = Math.floor(Math.random() * (maxDelay - minDelay + 1)) + minDelay;
            
            const delayLog = document.createElement('div');
            delayLog.style.color = 'var(--text-muted)';
            delayLog.style.fontSize = '12px';
            delayLog.innerHTML = `<i class="fas fa-clock" style="margin-left:5px;"></i>انتظار فارق توقيت عشوائي مدته ${delaySec} ثانية...`;
            logList.appendChild(delayLog);
            logList.scrollTop = logList.scrollHeight;

            await new Promise(resolve => setTimeout(resolve, delaySec * 1000));
        }
    }

    document.getElementById('btn-cancel-bulk').style.display = 'none';
    const completionLog = document.createElement('div');
    completionLog.style.fontWeight = 'bold';
    completionLog.style.marginTop = '10px';
    completionLog.style.color = 'var(--primary)';
    const complVerb = (sendType === 'schedule') ? 'جدولة' : 'معالجة';
    completionLog.innerHTML = `<i class="fas fa-flag-checkered" style="margin-left:5px;"></i>اكتملت العملية. تم ${complVerb} ${sentCount} من إجمالي ${total} عميل.`;
    logList.appendChild(completionLog);
    logList.scrollTop = logList.scrollHeight;
}
</script>


<!-- ══ Modal: إرسال واتساب جماعي ════════════════════════════════ -->
<div class="modal-overlay" id="bulkWaModal" style="display:none;">
  <div class="modal" style="max-width:600px;">
    <div class="modal-header">
      <span class="modal-title"><i class="fab fa-whatsapp" style="color:var(--success);"></i> إرسال رسالة واتساب جماعية</span>
      <button class="modal-close" onclick="closeBulkWaModal()"><i class="fas fa-times"></i></button>
    </div>
    <div class="modal-body">
      <div style="background:rgba(34,197,94,.08);border-right:4px solid var(--success);border-radius:8px;padding:12px;margin-bottom:18px;">
        <span style="font-size:13px;color:#166534;font-weight:700;">عدد العملاء المحددين: <strong id="bulk-selected-count">0</strong> عميل</span>
      </div>

      <!-- Inputs Section -->
      <div id="bulk-send-form-inputs">
        <div class="form-group">
          <label class="form-label" for="bulkWhatsappMessage">نص الرسالة <span class="required">*</span></label>
          <textarea id="bulkWhatsappMessage" class="form-control" rows="5" placeholder="اكتب رسالتك الجماعية هنا..." required></textarea>
          <span class="form-hint">يمكنك استخدام المتغيرات التلقائية: <strong>{name}</strong> لاسم العميل، و <strong>{company}</strong> لاسم الشركة.</span>
        </div>

        <div class="form-group" style="margin-bottom: 15px;">
          <label class="form-label">توقيت الإرسال</label>
          <div style="display:flex; gap:20px; align-items:center; margin-top:5px; margin-bottom:10px;">
            <label style="display:flex; align-items:center; gap:6px; font-weight:normal; cursor:pointer;">
              <input type="radio" name="bulkSendType" value="now" checked onchange="toggleBulkScheduleFields(this.value)">
              إرسال الآن (عبر المتصفح)
            </label>
            <label style="display:flex; align-items:center; gap:6px; font-weight:normal; cursor:pointer;">
              <input type="radio" name="bulkSendType" value="schedule" onchange="toggleBulkScheduleFields(this.value)">
              جدولة الإرسال لاحقاً (عبر السيرفر)
            </label>
          </div>
        </div>

        <div class="form-group" id="bulk-schedule-time-group" style="display:none; margin-bottom: 15px;">
          <label class="form-label" for="bulkSendAt">تاريخ ووقت الإرسال <span class="required">*</span></label>
          <input type="datetime-local" id="bulkSendAt" class="form-control">
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label" for="bulkMinDelay">أقل انتظار (بالثواني)</label>
            <input type="number" id="bulkMinDelay" class="form-control" value="3" min="1">
          </div>
          <div class="form-group">
            <label class="form-label" for="bulkMaxDelay">أقصى انتظار (بالثواني)</label>
            <input type="number" id="bulkMaxDelay" class="form-control" value="15" min="2">
          </div>
        </div>
      </div>

      <!-- Progress Section -->
      <div id="bulk-progress-section" style="display:none;margin-top:10px;">
        <label class="form-label">حالة تقدم عملية الإرسال:</label>
        <div style="width:100%;height:14px;background:#e2e8f0;border-radius:10px;overflow:hidden;margin-bottom:6px;position:relative;">
          <div id="bulk-progress-bar" style="width:0%;height:100%;background:linear-gradient(135deg, #22c55e, #16a34a);transition:width .3s;"></div>
        </div>
        <div style="display:flex;justify-content:space-between;font-size:12px;color:var(--text-muted);font-weight:600;margin-bottom:14px;">
          <span>نسبة التقدم</span>
          <span id="bulk-progress-text">0%</span>
        </div>
        
        <label class="form-label">تفاصيل العملية (السجل):</label>
        <div id="bulk-log-list" style="max-height:200px;overflow-y:auto;background:var(--content-bg);border:1.5px solid var(--border-color);border-radius:8px;padding:12px;font-size:13px;line-height:1.7;display:flex;flex-direction:column;gap:6px;">
        </div>
      </div>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-outline" onclick="closeBulkWaModal()">إلغاء / إغلاق</button>
      <button type="button" class="btn btn-primary" id="btn-start-bulk" onclick="startBulkSending()"><i class="fas fa-paper-plane"></i> بدء الإرسال الآن</button>
      <button type="button" class="btn btn-danger" id="btn-cancel-bulk" style="display:none;" onclick="bulkSendCancelled = true; this.disabled = true; this.textContent='جاري الإيقاف...';"><i class="fas fa-stop"></i> إيقاف العملية</button>
    </div>
  </div>
</div>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
