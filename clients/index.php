<?php
/**
 * clients/index.php - قائمة العملاء
 */
require_once dirname(__DIR__) . '/config/app.php';
requireLogin();
requirePermission('view_clients');

$db = getDB();

// Search & Filter
$search = clean($_GET['search'] ?? '');
$status = $_GET['status'] ?? '';
$page   = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;

$where  = ['1=1'];
$params = [];
if ($search) {
    $where[]  = "(c.name LIKE ? OR c.company_name LIKE ? OR c.mobile LIKE ? OR c.username_note LIKE ?)";
    $s = "%$search%";
    $params   = array_merge($params, [$s, $s, $s, $s]);
}
if ($status !== '') {
    $where[]  = "c.status = ?";
    $params[] = (int)$status;
}

$whereStr = implode(' AND ', $where);

// Count
$countStmt = $db->prepare("SELECT COUNT(*) FROM clients c WHERE $whereStr");
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
    ORDER BY c.created_at DESC
    LIMIT ? OFFSET ?
");
$stmt->execute(array_merge($params, [$perPage, $pager['offset']]));
$clients = $stmt->fetchAll();

$pageTitle  = 'العملاء';
$activePage = 'clients';
$depth      = 1;
require_once INCLUDES_PATH . '/header.php';
?>

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

<!-- Filters -->
<div class="card mb-2" style="margin-bottom:16px;">
  <div class="filters-bar">
    <form method="GET" action="index.php" style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;width:100%;">

      <div class="search-box">
        <i class="fas fa-search search-icon"></i>
        <input type="text" name="search" class="form-control" id="searchInput"
               placeholder="ابحث بالاسم أو الشركة أو الموبايل..."
               value="<?= e($search) ?>">
      </div>

      <select name="status" class="form-control" style="width:auto;">
        <option value="">كل الحالات</option>
        <option value="1" <?= $status === '1' ? 'selected' : '' ?>>نشط</option>
        <option value="0" <?= $status === '0' ? 'selected' : '' ?>>موقوف</option>
      </select>

      <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> بحث</button>
      <?php if ($search || $status !== ''): ?>
      <a href="index.php" class="btn btn-outline"><i class="fas fa-times"></i> مسح</a>
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
  <?php if ($pager['total_pages'] > 1): ?>
  <div class="card-footer">
    <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
      <span class="text-muted fs-sm">
        عرض <?= $pager['offset'] + 1 ?> - <?= min($pager['offset'] + $perPage, $totalClients) ?>
        من <?= $totalClients ?> عميل
      </span>
      <div class="pagination">
        <?php
        $queryBase = http_build_query(array_filter(['search' => $search, 'status' => $status]));
        $sep = $queryBase ? '&' : '';
        ?>
        <a href="?<?= $queryBase ?><?= $sep ?>page=<?= $pager['current_page'] - 1 ?>"
           class="page-btn <?= !$pager['has_prev'] ? 'disabled' : '' ?>">
          <i class="fas fa-chevron-right"></i>
        </a>
        <?php for ($p = 1; $p <= $pager['total_pages']; $p++): ?>
        <a href="?<?= $queryBase ?><?= $sep ?>page=<?= $p ?>"
           class="page-btn <?= $p === $pager['current_page'] ? 'active' : '' ?>">
          <?= $p ?>
        </a>
        <?php endfor; ?>
        <a href="?<?= $queryBase ?><?= $sep ?>page=<?= $pager['current_page'] + 1 ?>"
           class="page-btn <?= !$pager['has_next'] ? 'disabled' : '' ?>">
          <i class="fas fa-chevron-left"></i>
        </a>
      </div>
    </div>
  </div>
  <?php endif; ?>
</div>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
