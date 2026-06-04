<?php
/**
 * subscriptions/index.php - قائمة الاشتراكات
 */
require_once dirname(__DIR__) . '/config/app.php';
requireLogin();
requirePermission('view_subscriptions');

$db      = getDB();
$status  = clean($_GET['status'] ?? '');
$service = (int)($_GET['service'] ?? 0);
$search  = clean($_GET['search'] ?? '');

$where  = ['1=1'];
$params = [];
if ($status)  { $where[] = "cs.status = ?"; $params[] = $status; }
if ($service) { $where[] = "cs.service_id = ?"; $params[] = $service; }
if ($search)  { $where[] = "(c.name LIKE ? OR c.company_name LIKE ?)"; $s="%$search%"; $params=array_merge($params,[$s,$s]); }
$whereStr = implode(' AND ', $where);

$subs = $db->prepare("
    SELECT cs.*, c.name as client_name, c.mobile, s.name as service_name,
           DATEDIFF(cs.end_date, CURDATE()) as days_left
    FROM client_subscriptions cs
    JOIN clients c ON c.id=cs.client_id
    JOIN services s ON s.id=cs.service_id
    WHERE $whereStr
    ORDER BY cs.end_date ASC
");
$subs->execute($params);
$subs = $subs->fetchAll();

// AJAX live search handler
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    ob_start();
    ?>
    <?php if (empty($subs)): ?>
    <tr><td colspan="9"><div class="empty-state"><div class="empty-icon"><i class="fas fa-file-slash"></i></div><p class="empty-title">لا توجد اشتراكات</p></div></td></tr>
    <?php else: ?>
    <?php foreach ($subs as $sub): ?>
    <tr>
      <td>
        <a href="../clients/view.php?id=<?= $sub['client_id'] ?>" style="font-weight:700;">
          <?= e($sub['client_name']) ?>
        </a>
      </td>
      <td><?= e($sub['service_name']) ?></td>
      <td class="text-muted"><?= e($sub['plan_name'] ?: '—') ?></td>
      <td class="fw-bold"><?= formatMoney($sub['price']) ?></td>
      <td><?= formatDate($sub['start_date']) ?></td>
      <td><?= formatDate($sub['end_date']) ?></td>
      <td>
        <?php
        $d = $sub['days_left'];
        if ($d === null): ?><span class="badge badge-success">مفتوح (لا ينتهي)</span>
        <?php elseif ($d < 0): ?><span class="badge badge-danger">انتهى</span>
        <?php elseif ($d <= 7): ?><span class="badge badge-danger"><?= $d ?> يوم</span>
        <?php elseif ($d <= 30): ?><span class="badge badge-warning"><?= $d ?> يوم</span>
        <?php else: ?><span class="badge badge-success"><?= $d ?> يوم</span>
        <?php endif; ?>
      </td>
      <td><?= subscriptionStatusBadge($sub['status'], $sub['end_date']) ?></td>
      <td>
        <div class="table-actions">
          <?php if (hasPermission('edit_subscriptions')): ?>
          <a href="edit.php?id=<?= $sub['id'] ?>" class="btn btn-sm btn-outline"><i class="fas fa-edit"></i></a>
          <?php endif; ?>
          <?php if ($sub['status'] !== 'active' && hasPermission('add_subscriptions')): ?>
          <a href="renew.php?id=<?= $sub['id'] ?>" class="btn btn-sm btn-success"><i class="fas fa-redo"></i></a>
          <?php endif; ?>
        </div>
      </td>
    </tr>
    <?php endforeach; ?>
    <?php endif; ?>
    <?php
    $tbodyHtml = ob_get_clean();

    echo json_encode([
        'tbody' => $tbodyHtml,
        'subtitle' => 'إجمالي ' . count($subs) . ' اشتراك'
    ]);
    exit;
}

$services = $db->query("SELECT id,name FROM services ORDER BY name")->fetchAll();
$pageTitle  = 'الاشتراكات';
$activePage = 'subscriptions';
$depth      = 1;
require_once INCLUDES_PATH . '/header.php';
?>
<div class="page-header">
  <div class="page-header-text">
    <h1 class="page-title"><i class="fas fa-file-contract" style="color:var(--primary-light);margin-left:8px;"></i>الاشتراكات</h1>
    <p class="page-subtitle">إجمالي <?= count($subs) ?> اشتراك</p>
  </div>
</div>

<div class="card" style="margin-bottom:16px;">
  <div class="filters-bar">
    <form method="GET" style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;width:100%;" id="searchForm">
      <div class="search-box">
        <i class="fas fa-search search-icon"></i>
        <input type="text" name="search" class="form-control" placeholder="اسم العميل..." value="<?= e($search) ?>" autocomplete="off">
      </div>
      <select name="status" class="form-control" style="width:auto;">
        <option value="">كل الحالات</option>
        <option value="active" <?= $status==='active'?'selected':'' ?>>نشط</option>
        <option value="expired" <?= $status==='expired'?'selected':'' ?>>منتهي</option>
        <option value="cancelled" <?= $status==='cancelled'?'selected':'' ?>>ملغي</option>
      </select>
      <select name="service" class="form-control" style="width:auto;">
        <option value="">كل الخدمات</option>
        <?php foreach ($services as $srv): ?>
        <option value="<?= $srv['id'] ?>" <?= $service===$srv['id']?'selected':'' ?>><?= e($srv['name']) ?></option>
        <?php endforeach; ?>
      </select>
      <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> بحث</button>
      <a href="index.php" class="btn btn-outline" id="clearSearchBtn"><i class="fas fa-times"></i> مسح</a>
    </form>
  </div>
</div>

<div class="card">
  <div class="table-wrapper">
    <table class="data-table">
      <thead>
        <tr><th>العميل</th><th>الخدمة</th><th>الخطة</th><th>السعر</th><th>البداية</th><th>النهاية</th><th>المتبقي</th><th>الحالة</th><th>إجراء</th></tr>
      </thead>
      <tbody>
        <?php if (empty($subs)): ?>
        <tr><td colspan="9"><div class="empty-state"><div class="empty-icon"><i class="fas fa-file-slash"></i></div><p class="empty-title">لا توجد اشتراكات</p></div></td></tr>
        <?php else: ?>
        <?php foreach ($subs as $sub): ?>
        <tr>
          <td>
            <a href="../clients/view.php?id=<?= $sub['client_id'] ?>" style="font-weight:700;">
              <?= e($sub['client_name']) ?>
            </a>
          </td>
          <td><?= e($sub['service_name']) ?></td>
          <td class="text-muted"><?= e($sub['plan_name'] ?: '—') ?></td>
          <td class="fw-bold"><?= formatMoney($sub['price']) ?></td>
          <td><?= formatDate($sub['start_date']) ?></td>
          <td><?= formatDate($sub['end_date']) ?></td>
          <td>
            <?php
            $d = $sub['days_left'];
            if ($d === null): ?><span class="badge badge-success">مفتوح (لا ينتهي)</span>
            <?php elseif ($d < 0): ?><span class="badge badge-danger">انتهى</span>
            <?php elseif ($d <= 7): ?><span class="badge badge-danger"><?= $d ?> يوم</span>
            <?php elseif ($d <= 30): ?><span class="badge badge-warning"><?= $d ?> يوم</span>
            <?php else: ?><span class="badge badge-success"><?= $d ?> يوم</span>
            <?php endif; ?>
          </td>
          <td><?= subscriptionStatusBadge($sub['status'], $sub['end_date']) ?></td>
          <td>
            <div class="table-actions">
              <?php if (hasPermission('edit_subscriptions')): ?>
              <a href="edit.php?id=<?= $sub['id'] ?>" class="btn btn-sm btn-outline"><i class="fas fa-edit"></i></a>
              <?php endif; ?>
              <?php if ($sub['status'] !== 'active' && hasPermission('add_subscriptions')): ?>
              <a href="renew.php?id=<?= $sub['id'] ?>" class="btn btn-sm btn-success"><i class="fas fa-redo"></i></a>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.querySelector('input[name="search"]');
    const statusSelect = document.querySelector('select[name="status"]');
    const serviceSelect = document.querySelector('select[name="service"]');
    const searchForm = document.getElementById('searchForm');
    const tbody = document.querySelector('.data-table tbody');
    const subtitle = document.querySelector('.page-subtitle');
    const clearSearchBtn = document.getElementById('clearSearchBtn');
    
    let debounceTimer;

    searchForm.addEventListener('submit', function(e) {
        e.preventDefault();
        doSearch();
    });

    function doSearch() {
        const searchQuery = searchInput.value;
        const statusQuery = statusSelect.value;
        const serviceQuery = serviceSelect.value;

        const params = new URLSearchParams({
            search: searchQuery,
            status: statusQuery,
            service: serviceQuery,
            ajax: 1
        });

        // Update URL
        const cleanParams = new URLSearchParams({
            search: searchQuery,
            status: statusQuery,
            service: serviceQuery
        });
        if (!searchQuery) cleanParams.delete('search');
        if (!statusQuery) cleanParams.delete('status');
        if (!serviceQuery || serviceQuery === '0') cleanParams.delete('service');
        
        const newUrl = window.location.pathname + (cleanParams.toString() ? '?' + cleanParams.toString() : '');
        window.history.replaceState({path: newUrl}, '', newUrl);

        // Clear button display toggler
        if (clearSearchBtn) {
            if (searchQuery || statusQuery || serviceQuery) {
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
            })
            .catch(err => console.error('Error fetching search results:', err));
    }

    searchInput.addEventListener('input', function() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(doSearch, 150);
    });

    statusSelect.addEventListener('change', doSearch);
    serviceSelect.addEventListener('change', doSearch);
});
</script>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
