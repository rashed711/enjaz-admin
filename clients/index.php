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
$status = $_GET['status'] ?? '';
$filter = $_GET['filter'] ?? '';
$page   = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;

// Stats calculations for cards
$totalClientsCount = (int)$db->query("SELECT COUNT(*) FROM clients")->fetchColumn();

$debtClientsCount = (int)$db->query("
    SELECT COUNT(*) FROM (
        SELECT c.id,
               COALESCE(SUM(CASE WHEN cs.status != 'cancelled' THEN cs.price ELSE 0 END), 0) AS total_services,
               COALESCE((SELECT SUM(p.amount) FROM payments p WHERE p.client_id = c.id), 0) AS total_paid
        FROM clients c
        LEFT JOIN client_subscriptions cs ON cs.client_id = c.id
        GROUP BY c.id
        HAVING (total_services - total_paid) > 0
    ) tmp
")->fetchColumn();

$stmtExp = $db->prepare("
    SELECT COUNT(DISTINCT client_id) FROM client_subscriptions
    WHERE status = 'active'
      AND end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
");
$stmtExp->execute([$warningDays]);
$expiringClientsCount = (int)$stmtExp->fetchColumn();

$totalDebtsAmount = (float)$db->query("
    SELECT SUM(remaining) FROM (
        SELECT (COALESCE(SUM(CASE WHEN cs.status != 'cancelled' THEN cs.price ELSE 0 END), 0) - COALESCE((SELECT SUM(p.amount) FROM payments p WHERE p.client_id = c.id), 0)) AS remaining
        FROM clients c
        LEFT JOIN client_subscriptions cs ON cs.client_id = c.id
        GROUP BY c.id
    ) tmp WHERE remaining > 0
")->fetchColumn();

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

$whereStr = implode(' AND ', $where);

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
           COUNT(DISTINCT cs.id) AS subs_count
    FROM clients c
    LEFT JOIN client_subscriptions cs ON cs.client_id = c.id
    WHERE $whereStr
    GROUP BY c.id
    $havingStr
    ORDER BY c.created_at DESC
    LIMIT ? OFFSET ?
");
$stmt->execute(array_merge($params, [$perPage, $pager['offset']]));
$clients = $stmt->fetchAll();

// AJAX live search handler
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    ob_start();
    ?>
    <?php if (empty($clients)): ?>
    <tr>
      <td colspan="10">
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
    <tr onclick="if(!event.target.closest('a') && !event.target.closest('button')) window.location='view.php?id=<?= $client['id'] ?>';" style="cursor:pointer;">
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
            <span style="font-size:12px;color:var(--text-muted);"><?= e($client['company_name']) ?></span>
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
      <td class="text-muted"><?= e($client['activity'] ?: '—') ?></td>
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
        <div class="table-actions">
          <a href="view.php?id=<?= $client['id'] ?>" class="btn btn-sm btn-primary" title="عرض الملف">
            <i class="fas fa-eye"></i>
          </a>
          <?php if (hasPermission('edit_clients')): ?>
          <a href="edit.php?id=<?= $client['id'] ?>" class="btn btn-sm btn-outline" title="تعديل">
            <i class="fas fa-edit"></i>
          </a>
          <?php endif; ?>
          <?php if (hasPermission('delete_clients')): ?>
          <a href="delete.php?id=<?= $client['id'] ?>"
             class="btn btn-sm btn-outline-danger"
             data-confirm="هل أنت متأكد من حذف عميل «<?= e($client['name']) ?>»؟ سيتم حذف كل بياناته وخدماته."
             title="حذف">
            <i class="fas fa-trash"></i>
          </a>
          <?php endif; ?>
        </div>
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
        $queryBase = http_build_query(array_filter(['search' => $search, 'status' => $status, 'filter' => $filter]));
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
        'subtitle' => 'إجمالي ' . $totalClients . ' عميل مسجّل في النظام'
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
  <div class="page-actions">
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
      <div style="font-size: 22px; font-weight: 800; color: var(--primary);"><?= $totalClientsCount ?></div>
    </div>
    <div style="width: 44px; height: 44px; border-radius: 10px; background: rgba(36, 86, 164, 0.1); display: flex; align-items: center; justify-content: center; color: var(--primary);">
      <i class="fas fa-users" style="font-size: 18px;"></i>
    </div>
  </div>

  <!-- Debt Clients Card -->
  <div class="stat-card <?= $filter === 'debt' ? 'active' : '' ?>" onclick="applyStatsFilter('debt')" style="background: var(--card-bg); border-radius: 12px; padding: 18px; border: 1px solid var(--border-color); cursor: pointer; display: flex; align-items: center; justify-content: space-between; box-shadow: 0 4px 12px rgba(0,0,0,0.01);">
    <div>
      <div style="font-size: 13px; color: var(--text-muted); font-weight: 600; margin-bottom: 4px;">عملاء عليهم مديونية</div>
      <div style="font-size: 22px; font-weight: 800; color: var(--danger);"><?= $debtClientsCount ?></div>
    </div>
    <div style="width: 44px; height: 44px; border-radius: 10px; background: rgba(239, 68, 68, 0.1); display: flex; align-items: center; justify-content: center; color: var(--danger);">
      <i class="fas fa-hand-holding-dollar" style="font-size: 18px;"></i>
    </div>
  </div>

  <!-- Expiring Soon Card -->
  <div class="stat-card <?= $filter === 'expiring' ? 'active' : '' ?>" onclick="applyStatsFilter('expiring')" style="background: var(--card-bg); border-radius: 12px; padding: 18px; border: 1px solid var(--border-color); cursor: pointer; display: flex; align-items: center; justify-content: space-between; box-shadow: 0 4px 12px rgba(0,0,0,0.01);">
    <div>
      <div style="font-size: 13px; color: var(--text-muted); font-weight: 600; margin-bottom: 4px;">تجديدات قريبة</div>
      <div style="font-size: 22px; font-weight: 800; color: var(--warning);"><?= $expiringClientsCount ?></div>
    </div>
    <div style="width: 44px; height: 44px; border-radius: 10px; background: rgba(240, 165, 0, 0.1); display: flex; align-items: center; justify-content: center; color: var(--warning);">
      <i class="fas fa-calendar-exclamation" style="font-size: 18px;"></i>
    </div>
  </div>

  <!-- Total Debts Amount Card -->
  <div class="stat-card" style="background: var(--card-bg); border-radius: 12px; padding: 18px; border: 1px solid var(--border-color); display: flex; align-items: center; justify-content: space-between; box-shadow: 0 4px 12px rgba(0,0,0,0.01);">
    <div>
      <div style="font-size: 13px; color: var(--text-muted); font-weight: 600; margin-bottom: 4px;">إجمالي المديونيات</div>
      <div style="font-size: 18px; font-weight: 800; color: var(--success);"><?= formatMoney($totalDebtsAmount) ?></div>
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
      <?php if ($search || $status !== '' || $filter !== ''): ?>
      <a href="index.php" class="btn btn-outline" id="clearSearchBtn"><i class="fas fa-times"></i> مسح</a>
      <?php endif; ?>
    </form>
  </div>
</div>

<!-- Table -->
<div class="card">
  <div class="table-wrapper">
    <table class="data-table">
      <thead>
        <tr>
          <th>#</th>
          <th>العميل</th>
          <th>الموبايل</th>
          <th>النشاط</th>
          <th>الخدمات</th>
          <th>الإجمالي</th>
          <th>المدفوع</th>
          <th>المتبقي</th>
          <th>الحالة</th>
          <th>الإجراءات</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($clients)): ?>
        <tr>
          <td colspan="10">
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
        <tr onclick="if(!event.target.closest('a') && !event.target.closest('button')) window.location='view.php?id=<?= $client['id'] ?>';" style="cursor:pointer;">
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
                <span style="font-size:12px;color:var(--text-muted);"><?= e($client['company_name']) ?></span>
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
          <td class="text-muted"><?= e($client['activity'] ?: '—') ?></td>
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
            <div class="table-actions">
              <a href="view.php?id=<?= $client['id'] ?>" class="btn btn-sm btn-primary" title="عرض الملف">
                <i class="fas fa-eye"></i>
              </a>
              <?php if (hasPermission('edit_clients')): ?>
              <a href="edit.php?id=<?= $client['id'] ?>" class="btn btn-sm btn-outline" title="تعديل">
                <i class="fas fa-edit"></i>
              </a>
              <?php endif; ?>
              <?php if (hasPermission('delete_clients')): ?>
              <a href="delete.php?id=<?= $client['id'] ?>"
                 class="btn btn-sm btn-outline-danger"
                 data-confirm="هل أنت متأكد من حذف عميل «<?= e($client['name']) ?>»؟ سيتم حذف كل بياناته وخدماته."
                 title="حذف">
                <i class="fas fa-trash"></i>
              </a>
              <?php endif; ?>
            </div>
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
        $queryBase = http_build_query(array_filter(['search' => $search, 'status' => $status, 'filter' => $filter]));
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
        if (statusSelect) statusSelect.value = '';

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

        const params = new URLSearchParams({
            search: searchQuery,
            status: statusQuery,
            filter: filterQuery,
            page: currentPage,
            ajax: 1
        });

        // Update URL
        const cleanParams = new URLSearchParams({
            search: searchQuery,
            status: statusQuery,
            filter: filterQuery,
            page: currentPage
        });
        if (!searchQuery) cleanParams.delete('search');
        if (!statusQuery) cleanParams.delete('status');
        if (!filterQuery) cleanParams.delete('filter');
        if (currentPage === 1) cleanParams.delete('page');
        
        const newUrl = window.location.pathname + (cleanParams.toString() ? '?' + cleanParams.toString() : '');
        window.history.replaceState({path: newUrl}, '', newUrl);

        // Update Clear Button visibility if it exists
        if (clearSearchBtn) {
            if (searchQuery || statusQuery !== '' || filterQuery !== '') {
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
});
</script>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
