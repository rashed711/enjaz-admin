<?php
/**
 * payments/index.php - سجل المدفوعات
 */
require_once dirname(__DIR__) . '/config/app.php';
requireLogin();
requirePermission('view_payments');

$db      = getDB();
$search  = clean($_GET['search'] ?? '');
$method  = $_GET['method'] ?? '';
$dateFrom= clean($_GET['date_from'] ?? '');
$dateTo  = clean($_GET['date_to'] ?? '');
$page    = max(1,(int)($_GET['page'] ?? 1));
$perPage = 25;

$where  = ['1=1'];
$params = [];
if ($search) { $where[] = "(c.name LIKE ? OR c.company_name LIKE ?)"; $s="%$search%"; $params=array_merge($params,[$s,$s]); }
if ($method) { $where[] = "p.payment_method = ?"; $params[] = $method; }
if ($dateFrom) { $where[] = "p.payment_date >= ?"; $params[] = $dateFrom; }
if ($dateTo)   { $where[] = "p.payment_date <= ?"; $params[] = $dateTo; }
$whereStr = implode(' AND ', $where);

$countStmt = $db->prepare("SELECT COUNT(*) FROM payments p LEFT JOIN clients c ON c.id=p.client_id WHERE $whereStr");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();
$pager = paginate($total, $perPage, $page);

// Total amount for current filter
$sumStmt = $db->prepare("SELECT COALESCE(SUM(p.amount),0) FROM payments p LEFT JOIN clients c ON c.id=p.client_id WHERE $whereStr");
$sumStmt->execute($params);
$totalAmount = (float)$sumStmt->fetchColumn();

$stmt = $db->prepare("
    SELECT p.*, c.name as client_name, c.company_name, u.full_name as added_by,
           s.name as service_name
    FROM payments p
    LEFT JOIN clients c ON c.id=p.client_id
    LEFT JOIN users u ON u.id=p.created_by
    LEFT JOIN client_subscriptions cs ON cs.id=p.subscription_id
    LEFT JOIN services s ON s.id=cs.service_id
    WHERE $whereStr
    ORDER BY p.payment_date DESC, p.created_at DESC
    LIMIT ? OFFSET ?
");
$stmt->execute(array_merge($params,[$perPage,$pager['offset']]));
$payments = $stmt->fetchAll();

// AJAX live search handler
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    ob_start();
    ?>
    <?php if (empty($payments)): ?>
    <tr><td colspan="11"><div class="empty-state"><div class="empty-icon"><i class="fas fa-coins"></i></div><p class="empty-title">لا توجد مدفوعات</p></div></td></tr>
    <?php else: ?>
    <?php foreach ($payments as $i => $pay): ?>
    <tr>
      <td class="text-muted"><?= $pager['offset']+$i+1 ?></td>
      <td><?= formatDate($pay['payment_date']) ?></td>
      <td>
        <a href="../clients/view.php?id=<?= $pay['client_id'] ?>" style="font-weight:600;color:var(--text-primary);">
          <?= e($pay['client_name']) ?>
        </a>
        <?php if ($pay['company_name']): ?>
        <div style="font-size:11.5px;color:var(--text-muted);"><?= e($pay['company_name']) ?></div>
        <?php endif; ?>
      </td>
      <td style="color:var(--success);font-weight:700;"><?= formatMoney($pay['amount']) ?></td>
      <td><?= paymentMethodLabel($pay['payment_method']) ?></td>
      <td class="text-muted"><?= e($pay['service_name'] ?: '—') ?></td>
      <td class="text-muted fs-sm"><?= e($pay['reference_number'] ?: '—') ?></td>
      <td>
        <?php if (!empty($pay['receipt_file'])): ?>
          <a href="../<?= e($pay['receipt_file']) ?>" target="_blank" class="btn btn-sm btn-outline-info" title="عرض الإيصال" style="padding: 2px 6px; font-size: 11.5px;">
            <i class="fas fa-file-invoice"></i> عرض
          </a>
        <?php else: ?>
          <span class="text-muted">—</span>
        <?php endif; ?>
      </td>
      <td class="text-muted fs-sm"><?= e($pay['notes'] ?: '—') ?></td>
      <td class="text-muted fs-sm"><?= e($pay['added_by'] ?? '—') ?></td>
      <?php if (hasPermission('delete_payments')): ?>
      <td>
        <a href="delete.php?id=<?= $pay['id'] ?>&client_id=<?= $pay['client_id'] ?>"
           class="btn btn-sm btn-outline-danger" data-confirm="حذف هذه الدفعة؟">
          <i class="fas fa-trash"></i>
        </a>
      </td>
      <?php endif; ?>
    </tr>
    <?php endforeach; ?>
    <?php endif; ?>
    <?php
    $tbodyHtml = ob_get_clean();

    ob_start();
    ?>
    <?php if (!empty($payments)): ?>
    <tr style="background:#f8fafc;font-weight:700;">
      <td colspan="3" style="padding:12px 16px;text-align:right;">المجموع في هذه الصفحة:</td>
      <td style="padding:12px 16px;color:var(--success);font-size:15px;">
        <?= formatMoney(array_sum(array_column($payments,'amount'))) ?>
      </td>
      <td colspan="<?= hasPermission('delete_payments') ? 7 : 6 ?>"></td>
    </tr>
    <?php endif; ?>
    <?php
    $tfootHtml = ob_get_clean();

    ob_start();
    ?>
    <?php if ($pager['total_pages'] > 1): ?>
    <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
      <span class="text-muted fs-sm">
        عرض <?= $pager['offset'] + 1 ?> - <?= min($pager['offset'] + $perPage, $total) ?>
        من <?= $total ?> دفعة
      </span>
      <div class="pagination">
        <?php
        $queryBase = http_build_query(array_filter(['search' => $search, 'method' => $method, 'date_from' => $dateFrom, 'date_to' => $dateTo]));
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
        'tfoot' => $tfootHtml,
        'pagination' => $paginationHtml,
        'subtitle' => 'إجمالي ' . $total . ' دفعة — مجموع: ' . formatMoney($totalAmount)
    ]);
    exit;
}

$pageTitle  = 'سجل المدفوعات';
$activePage = 'payments';
$depth      = 1;
require_once INCLUDES_PATH . '/header.php';
?>

<div class="page-header">
  <div class="page-header-text">
    <h1 class="page-title"><i class="fas fa-money-bill-wave" style="color:var(--success);margin-left:8px;"></i>سجل المدفوعات</h1>
    <p class="page-subtitle">إجمالي <?= $total ?> دفعة — مجموع: <?= formatMoney($totalAmount) ?></p>
  </div>
</div>

<!-- Filters -->
<div class="card" style="margin-bottom:16px;">
  <div class="filters-bar">
    <form method="GET" style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;width:100%;" id="searchForm">
      <div class="search-box">
        <i class="fas fa-search search-icon"></i>
        <input type="text" name="search" class="form-control" placeholder="اسم العميل..." value="<?= e($search) ?>" autocomplete="off">
      </div>
      <?php 
      $payMethods = explode(',', getSetting('payment_methods', 'كاش,تحويل بنكي,فودافون كاش,شيك,أخرى')); 
      ?>
      <select name="method" class="form-control" style="width:auto;">
        <option value="">كل طرق الدفع</option>
        <?php foreach ($payMethods as $pm): $pm = trim($pm); ?>
        <option value="<?= e($pm) ?>" <?= $method === $pm ? 'selected' : '' ?>><?= e($pm) ?></option>
        <?php endforeach; ?>
      </select>
      <input type="date" name="date_from" class="form-control" style="width:auto;" value="<?= e($dateFrom) ?>" placeholder="من تاريخ">
      <input type="date" name="date_to" class="form-control" style="width:auto;" value="<?= e($dateTo) ?>" placeholder="إلى تاريخ">
      <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> بحث</button>
      <a href="index.php" class="btn btn-outline" id="clearSearchBtn"><i class="fas fa-times"></i> مسح</a>
    </form>
  </div>
</div>

<div class="card">
  <div class="table-wrapper">
    <table class="data-table">
      <thead>
        <tr>
          <th>#</th>
          <th>التاريخ</th>
          <th>العميل</th>
          <th>المبلغ</th>
          <th>طريقة الدفع</th>
          <th>الخدمة</th>
          <th>المرجع</th>
          <th>الإيصال</th>
          <th>ملاحظات</th>
          <th>أضافه</th>
          <?php if (hasPermission('delete_payments')): ?><th></th><?php endif; ?>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($payments)): ?>
        <tr><td colspan="11"><div class="empty-state"><div class="empty-icon"><i class="fas fa-coins"></i></div><p class="empty-title">لا توجد مدفوعات</p></div></td></tr>
        <?php else: ?>
        <?php foreach ($payments as $i => $pay): ?>
        <tr>
          <td class="text-muted"><?= $pager['offset']+$i+1 ?></td>
          <td><?= formatDate($pay['payment_date']) ?></td>
          <td>
            <a href="../clients/view.php?id=<?= $pay['client_id'] ?>" style="font-weight:600;color:var(--text-primary);">
              <?= e($pay['client_name']) ?>
            </a>
            <?php if ($pay['company_name']): ?>
            <div style="font-size:11.5px;color:var(--text-muted);"><?= e($pay['company_name']) ?></div>
            <?php endif; ?>
          </td>
          <td style="color:var(--success);font-weight:700;"><?= formatMoney($pay['amount']) ?></td>
          <td><?= paymentMethodLabel($pay['payment_method']) ?></td>
          <td class="text-muted"><?= e($pay['service_name'] ?: '—') ?></td>
          <td class="text-muted fs-sm"><?= e($pay['reference_number'] ?: '—') ?></td>
          <td>
            <?php if (!empty($pay['receipt_file'])): ?>
              <a href="../<?= e($pay['receipt_file']) ?>" target="_blank" class="btn btn-sm btn-outline-info" title="عرض الإيصال" style="padding: 2px 6px; font-size: 11.5px;">
                <i class="fas fa-file-invoice"></i> عرض
              </a>
            <?php else: ?>
              <span class="text-muted">—</span>
            <?php endif; ?>
          </td>
          <td class="text-muted fs-sm"><?= e($pay['notes'] ?: '—') ?></td>
          <td class="text-muted fs-sm"><?= e($pay['added_by'] ?? '—') ?></td>
          <?php if (hasPermission('delete_payments')): ?>
          <td>
            <a href="delete.php?id=<?= $pay['id'] ?>&client_id=<?= $pay['client_id'] ?>"
               class="btn btn-sm btn-outline-danger" data-confirm="حذف هذه الدفعة؟">
              <i class="fas fa-trash"></i>
            </a>
          </td>
          <?php endif; ?>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
      <tfoot class="table-totals">
        <?php if (!empty($payments)): ?>
        <tr style="background:#f8fafc;font-weight:700;">
          <td colspan="3" style="padding:12px 16px;text-align:right;">المجموع في هذه الصفحة:</td>
          <td style="padding:12px 16px;color:var(--success);font-size:15px;">
            <?= formatMoney(array_sum(array_column($payments,'amount'))) ?>
          </td>
          <td colspan="<?= hasPermission('delete_payments') ? 7 : 6 ?>"></td>
        </tr>
        <?php endif; ?>
      </tfoot>
    </table>
  </div>

  <!-- Pagination -->
  <div class="card-footer" style="<?= $pager['total_pages'] <= 1 ? 'display:none;' : '' ?>">
    <?php if ($pager['total_pages'] > 1): ?>
    <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
      <span class="text-muted fs-sm">
        عرض <?= $pager['offset'] + 1 ?> - <?= min($pager['offset'] + $perPage, $total) ?>
        من <?= $total ?> دفعة
      </span>
      <div class="pagination">
        <?php
        $queryBase = http_build_query(array_filter(['search' => $search, 'method' => $method, 'date_from' => $dateFrom, 'date_to' => $dateTo]));
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
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.querySelector('input[name="search"]');
    const methodSelect = document.querySelector('select[name="method"]');
    const dateFromInput = document.querySelector('input[name="date_from"]');
    const dateToInput = document.querySelector('input[name="date_to"]');
    const searchForm = document.getElementById('searchForm');
    const tbody = document.querySelector('.data-table tbody');
    const tfoot = document.querySelector('.table-totals');
    const cardFooter = document.querySelector('.card-footer');
    const subtitle = document.querySelector('.page-subtitle');
    const clearSearchBtn = document.getElementById('clearSearchBtn');
    
    let debounceTimer;
    let currentPage = 1;

    searchForm.addEventListener('submit', function(e) {
        e.preventDefault();
        doSearch(1);
    });

    function doSearch(page = 1) {
        currentPage = page;
        const searchQuery = searchInput.value;
        const methodQuery = methodSelect.value;
        const dateFromQuery = dateFromInput.value;
        const dateToQuery = dateToInput.value;

        const params = new URLSearchParams({
            search: searchQuery,
            method: methodQuery,
            date_from: dateFromQuery,
            date_to: dateToQuery,
            page: currentPage,
            ajax: 1
        });

        // Update URL
        const cleanParams = new URLSearchParams({
            search: searchQuery,
            method: methodQuery,
            date_from: dateFromQuery,
            date_to: dateToQuery,
            page: currentPage
        });
        if (!searchQuery) cleanParams.delete('search');
        if (!methodQuery) cleanParams.delete('method');
        if (!dateFromQuery) cleanParams.delete('date_from');
        if (!dateToQuery) cleanParams.delete('date_to');
        if (currentPage === 1) cleanParams.delete('page');
        
        const newUrl = window.location.pathname + (cleanParams.toString() ? '?' + cleanParams.toString() : '');
        window.history.replaceState({path: newUrl}, '', newUrl);

        // Toggle clear search button
        if (clearSearchBtn) {
            if (searchQuery || methodQuery || dateFromQuery || dateToQuery) {
                clearSearchBtn.style.display = 'inline-flex';
            } else {
                clearSearchBtn.style.display = 'none';
            }
        }

        fetch('index.php?' + params.toString())
            .then(response => response.json())
            .then(data => {
                tbody.innerHTML = data.tbody;
                tfoot.innerHTML = data.tfoot;
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

    // Trigger on inputs
    searchInput.addEventListener('input', function() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => doSearch(1), 150);
    });

    methodSelect.addEventListener('change', () => doSearch(1));

    // Handle Date inputs changes
    setTimeout(() => {
        if (dateFromInput._flatpickr) {
            dateFromInput._flatpickr.config.onChange.push(() => doSearch(1));
        } else {
            dateFromInput.addEventListener('change', () => doSearch(1));
        }

        if (dateToInput._flatpickr) {
            dateToInput._flatpickr.config.onChange.push(() => doSearch(1));
        } else {
            dateToInput.addEventListener('change', () => doSearch(1));
        }
    }, 200);

    // Handle pagination clicks dynamically
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
