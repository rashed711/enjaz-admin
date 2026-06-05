<?php
/**
 * reports/financial-hub.php - المركز المالي والتقارير (المدفوعات، الفواتير، الإيرادات، ملخص الخدمات)
 */
require_once dirname(__DIR__) . '/config/app.php';
requireLogin();

// تحديد التبويب الحالي
$tab = $_GET['tab'] ?? 'payments';

$db = getDB();

// ── 1. تبويب المدفوعات (Payments Tab) ─────────────────────────────
if ($tab === 'payments') {
    requirePermission('view_payments');
    
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

    // مجموع الفلاتر الحالية
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

    // معالج البحث الفوري للمدفوعات
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
            <a href="?tab=payments&<?= $queryBase ?><?= $sep ?>page=<?= $pager['current_page'] - 1 ?>"
               class="page-btn <?= !$pager['has_prev'] ? 'disabled' : '' ?>" data-page="<?= $pager['current_page'] - 1 ?>">
              <i class="fas fa-chevron-right"></i>
            </a>
            <?php for ($p = 1; $p <= $pager['total_pages']; $p++): ?>
            <a href="?tab=payments&<?= $queryBase ?><?= $sep ?>page=<?= $p ?>"
               class="page-btn <?= $p === $pager['current_page'] ? 'active' : '' ?>" data-page="<?= $p ?>">
              <?= $p ?>
            </a>
            <?php endfor; ?>
            <a href="?tab=payments&<?= $queryBase ?><?= $sep ?>page=<?= $pager['current_page'] + 1 ?>"
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
}

// ── 2. تبويب الفواتير (Invoices Tab) ──────────────────────────────
elseif ($tab === 'invoices') {
    requirePermission('print_invoices');
    
    $invs = $db->query("
        SELECT i.*, c.name as client_name, c.company_name
        FROM invoices i JOIN clients c ON c.id=i.client_id
        ORDER BY i.created_at DESC LIMIT 100
    ")->fetchAll();
}

// ── 3. تبويب الإيرادات الشهرية (Monthly Revenue Tab) ──────────────────
elseif ($tab === 'monthly') {
    requirePermission('view_reports');
    
    $year = (int)($_GET['year'] ?? date('Y'));

    $months = [];
    for ($m = 1; $m <= 12; $m++) {
        $stmt = $db->prepare("SELECT COALESCE(SUM(amount),0) FROM payments WHERE YEAR(payment_date)=? AND MONTH(payment_date)=?");
        $stmt->execute([$year, $m]);
        $months[$m] = (float)$stmt->fetchColumn();
    }
    $totalYear = array_sum($months);

    // أفضل الخدمات
    $topServices = $db->query("
        SELECT s.name, COUNT(*) as count, SUM(cs.price) as total
        FROM client_subscriptions cs JOIN services s ON s.id=cs.service_id
        GROUP BY s.id ORDER BY total DESC LIMIT 5
    ")->fetchAll();

    $arabicMonths = ['','يناير','فبراير','مارس','أبريل','مايو','يونيو','يوليو','أغسطس','سبتمبر','أكتوبر','نوفمبر','ديسمبر'];
}

// ── 4. تبويب ملخص الخدمات (Services Summary Tab) ─────────────────
elseif ($tab === 'services') {
    requirePermission('view_reports');
    
    $services = $db->query("
        SELECT s.name, s.default_price,
               COUNT(DISTINCT CASE WHEN cs.status='active' THEN cs.client_id END) as active_clients,
               COUNT(DISTINCT cs.client_id) as total_clients,
               COALESCE(SUM(CASE WHEN cs.status!='cancelled' THEN cs.price ELSE 0 END),0) as total_revenue,
               COUNT(CASE WHEN cs.status='active' AND cs.end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(),INTERVAL 30 DAY) THEN 1 END) as expiring_soon
        FROM services s
        LEFT JOIN client_subscriptions cs ON cs.service_id=s.id
        GROUP BY s.id ORDER BY total_revenue DESC
    ")->fetchAll();
}

$pageTitle  = 'المركز المالي والتقارير';
$activePage = 'financial-hub';
$depth      = 1;
require_once INCLUDES_PATH . '/header.php';
?>

<style>
/* تنسيق التبويبات الفرعية */
.tabs-nav {
  display: flex;
  gap: 8px;
  border-bottom: 2px solid #e2e8f0;
  margin-bottom: 20px;
  padding-bottom: 2px;
  flex-wrap: wrap;
}
.tab-btn {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 12px 20px;
  text-decoration: none;
  font-weight: 700;
  color: var(--text-muted);
  border-radius: 8px 8px 0 0;
  transition: all 0.2s ease;
  font-size: 14.5px;
  border-bottom: 3px solid transparent;
}
.tab-btn:hover {
  color: var(--primary);
  background: rgba(36,86,164,0.04);
}
.tab-btn.active {
  color: var(--primary);
  border-bottom-color: var(--primary);
  background: rgba(36,86,164,0.02);
}
</style>

<div class="page-header">
  <div class="page-header-text">
    <h1 class="page-title"><i class="fas fa-wallet" style="color:var(--primary-light);margin-left:8px;"></i>المركز المالي والتقارير</h1>
    <p class="page-subtitle">أدوات الفوترة، الدفعات، تقارير الإيرادات وأداء الخدمات</p>
  </div>
</div>

<!-- Tabs Navigation -->
<div class="tabs-nav">
  <a href="?tab=payments" class="tab-btn <?= $tab === 'payments' ? 'active' : '' ?>">
    <i class="fas fa-money-bill-wave"></i> المدفوعات
  </a>
  <a href="?tab=invoices" class="tab-btn <?= $tab === 'invoices' ? 'active' : '' ?>">
    <i class="fas fa-file-invoice"></i> الفواتير
  </a>
  <a href="?tab=monthly" class="tab-btn <?= $tab === 'monthly' ? 'active' : '' ?>">
    <i class="fas fa-chart-line"></i> الإيرادات الشهرية
  </a>
  <a href="?tab=services" class="tab-btn <?= $tab === 'services' ? 'active' : '' ?>">
    <i class="fas fa-layer-group"></i> ملخص الخدمات
  </a>
</div>

<!-- Tabs Content Area -->
<div class="tab-content">

  <!-- 1. المدفوعات -->
  <?php if ($tab === 'payments'): ?>
  <div class="card" style="margin-bottom:16px;">
    <div class="filters-bar">
      <form method="GET" style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;width:100%;" id="searchForm">
        <input type="hidden" name="tab" value="payments">
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
        <!-- أزرار الفلترة السريعة -->
        <div class="btn-group" id="quickDateFilters" style="display:flex;gap:4px;background:#f1f5f9;padding:4px;border-radius:8px;">
          <button type="button" class="btn btn-sm" data-range="all" style="border:none;padding:6px 14px;border-radius:6px;font-weight:600;font-size:13px;cursor:pointer;transition:all 0.2s;">الكل</button>
          <button type="button" class="btn btn-sm" data-range="day" style="border:none;padding:6px 14px;border-radius:6px;font-weight:600;font-size:13px;cursor:pointer;transition:all 0.2s;">يوم</button>
          <button type="button" class="btn btn-sm" data-range="month" style="border:none;padding:6px 14px;border-radius:6px;font-weight:600;font-size:13px;cursor:pointer;transition:all 0.2s;">شهر</button>
          <button type="button" class="btn btn-sm" data-range="year" style="border:none;padding:6px 14px;border-radius:6px;font-weight:600;font-size:13px;cursor:pointer;transition:all 0.2s;">سنة</button>
          <button type="button" class="btn btn-sm" data-range="custom" style="border:none;padding:6px 14px;border-radius:6px;font-weight:600;font-size:13px;cursor:pointer;transition:all 0.2s;">مخصص</button>
        </div>

        <div id="customDateWrapper" style="display:none; gap:12px; align-items:center;">
          <input type="date" name="date_from" class="form-control" style="width:auto;" value="<?= e($dateFrom) ?>" placeholder="من تاريخ">
          <input type="date" name="date_to" class="form-control" style="width:auto;" value="<?= e($dateTo) ?>" placeholder="إلى تاريخ">
        </div>
        <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> بحث</button>
        <a href="?tab=payments" class="btn btn-outline" id="clearSearchBtn"><i class="fas fa-times"></i> مسح</a>
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
          <a href="?tab=payments&<?= $queryBase ?><?= $sep ?>page=<?= $pager['current_page'] - 1 ?>"
             class="page-btn <?= !$pager['has_prev'] ? 'disabled' : '' ?>" data-page="<?= $pager['current_page'] - 1 ?>">
            <i class="fas fa-chevron-right"></i>
          </a>
          <?php for ($p = 1; $p <= $pager['total_pages']; $p++): ?>
          <a href="?tab=payments&<?= $queryBase ?><?= $sep ?>page=<?= $p ?>"
             class="page-btn <?= $p === $pager['current_page'] ? 'active' : '' ?>" data-page="<?= $p ?>">
            <?= $p ?>
          </a>
          <?php endfor; ?>
          <a href="?tab=payments&<?= $queryBase ?><?= $sep ?>page=<?= $pager['current_page'] + 1 ?>"
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

      // أجهزة الفلترة السريعة وتنسيق التواريخ
      const quickFilters = document.getElementById('quickDateFilters');
      const customWrapper = document.getElementById('customDateWrapper');

      const formatDate = (date) => {
          const y = date.getFullYear();
          const m = String(date.getMonth() + 1).padStart(2, '0');
          const d = String(date.getDate()).padStart(2, '0');
          return `${y}-${m}-${d}`;
      };

      function updateActiveFilter(range) {
          document.querySelectorAll('#quickDateFilters button').forEach(btn => {
              if (btn.dataset.range === range) {
                  btn.style.background = 'var(--primary)';
                  btn.style.color = '#fff';
              } else {
                  btn.style.background = 'transparent';
                  btn.style.color = 'var(--text-muted)';
              }
          });
          
          if (range === 'custom') {
              customWrapper.style.display = 'inline-flex';
          } else {
              customWrapper.style.display = 'none';
          }
      }

      // حساب التواريخ الافتراضية
      const todayStr = formatDate(new Date());
      const firstDayMonth = formatDate(new Date(new Date().getFullYear(), new Date().getMonth(), 1));
      const lastDayMonth = formatDate(new Date(new Date().getFullYear(), new Date().getMonth() + 1, 0));
      const firstDayYear = formatDate(new Date(new Date().getFullYear(), 0, 1));
      const lastDayYear = formatDate(new Date(new Date().getFullYear(), 11, 31));

      // تحديد الحالة النشطة الافتراضية عند تحميل الصفحة
      const initFrom = dateFromInput.value;
      const initTo = dateToInput.value;
      let activeRange = 'all';

      if (initFrom || initTo) {
          if (initFrom === todayStr && initTo === todayStr) {
              activeRange = 'day';
          } else if (initFrom === firstDayMonth && initTo === lastDayMonth) {
              activeRange = 'month';
          } else if (initFrom === firstDayYear && initTo === lastDayYear) {
              activeRange = 'year';
          } else {
              activeRange = 'custom';
          }
      }
      updateActiveFilter(activeRange);

      if (quickFilters) {
          quickFilters.addEventListener('click', function(e) {
              const btn = e.target.closest('button');
              if (!btn) return;
              
              const range = btn.dataset.range;
              let fromVal = '';
              let toVal = '';
              
              if (range === 'day') {
                  fromVal = todayStr;
                  toVal = todayStr;
              } else if (range === 'month') {
                  fromVal = firstDayMonth;
                  toVal = lastDayMonth;
              } else if (range === 'year') {
                  fromVal = firstDayYear;
                  toVal = lastDayYear;
              } else if (range === 'custom') {
                  fromVal = dateFromInput.value;
                  toVal = dateToInput.value;
              }
              
              if (dateFromInput._flatpickr) {
                  dateFromInput._flatpickr.setDate(fromVal);
              } else {
                  dateFromInput.value = fromVal;
              }
              
              if (dateToInput._flatpickr) {
                  dateToInput._flatpickr.setDate(toVal);
              } else {
                  dateToInput.value = toVal;
              }
              
              updateActiveFilter(range);
              
              if (range !== 'custom') {
                  doSearch(1);
              }
          });
      }

      if (searchForm) {
          searchForm.addEventListener('submit', function(e) {
              e.preventDefault();
              doSearch(1);
          });
      }

      function doSearch(page = 1) {
          currentPage = page;
          const searchQuery = searchInput.value;
          const methodQuery = methodSelect.value;
          const dateFromQuery = dateFromInput.value;
          const dateToQuery = dateToInput.value;

          const params = new URLSearchParams({
              tab: 'payments',
              search: searchQuery,
              method: methodQuery,
              date_from: dateFromQuery,
              date_to: dateToQuery,
              page: currentPage,
              ajax: 1
          });

          // Update URL
          const cleanParams = new URLSearchParams({
              tab: 'payments',
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

          if (clearSearchBtn) {
              if (searchQuery || methodQuery || dateFromQuery || dateToQuery) {
                  clearSearchBtn.style.display = 'inline-flex';
              } else {
                  clearSearchBtn.style.display = 'none';
              }
          }

          fetch('financial-hub.php?' + params.toString())
              .then(response => response.json())
              .then(data => {
                  tbody.innerHTML = data.tbody;
                  tfoot.innerHTML = data.tfoot;
                  if (subtitle) subtitle.textContent = data.subtitle;

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

      if (searchInput) {
          searchInput.addEventListener('input', function() {
              clearTimeout(debounceTimer);
              debounceTimer = setTimeout(() => doSearch(1), 150);
          });
      }

      if (methodSelect) {
          methodSelect.addEventListener('change', () => doSearch(1));
      }

      setTimeout(() => {
          if (dateFromInput && dateFromInput._flatpickr) {
              dateFromInput._flatpickr.config.onChange.push(() => doSearch(1));
          } else if (dateFromInput) {
              dateFromInput.addEventListener('change', () => doSearch(1));
          }

          if (dateToInput && dateToInput._flatpickr) {
              dateToInput._flatpickr.config.onChange.push(() => doSearch(1));
          } else if (dateToInput) {
              dateToInput.addEventListener('change', () => doSearch(1));
          }
      }, 200);

      const card = tbody ? tbody.closest('.card') : null;
      if (card) {
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
      }
  });
  </script>
  <?php endif; ?>

  <!-- 2. الفواتير -->
  <?php if ($tab === 'invoices'): ?>
  <div class="card">
    <div class="table-wrapper">
      <table class="data-table">
        <thead>
          <tr><th>#</th><th>رقم الفاتورة</th><th>العميل</th><th>الإجمالي</th><th>المدفوع</th><th>المتبقي</th><th>الحالة</th><th>التاريخ</th><th>إجراء</th></tr>
        </thead>
        <tbody>
          <?php if (empty($invs)): ?>
          <tr><td colspan="9"><div class="empty-state"><div class="empty-icon"><i class="fas fa-file-invoice"></i></div><p class="empty-title">لا توجد فواتير</p></div></td></tr>
          <?php else: ?>
          <?php foreach ($invs as $i => $inv): ?>
          <tr>
            <td class="text-muted"><?= $i+1 ?></td>
            <td><strong style="color:var(--primary-light);"><?= e($inv['invoice_number']) ?></strong></td>
            <td>
              <a href="../clients/view.php?id=<?= $inv['client_id'] ?>" style="font-weight:600;"><?= e($inv['client_name']) ?></a>
              <?php if ($inv['company_name']): ?><div style="font-size:11.5px;color:var(--text-muted);"><?= e($inv['company_name']) ?></div><?php endif; ?>
            </td>
            <td class="fw-bold"><?= formatMoney($inv['total_amount']) ?></td>
            <td style="color:var(--success);font-weight:600;"><?= formatMoney($inv['paid_amount']) ?></td>
            <td style="color:<?= $inv['remaining']>0?'var(--danger)':'var(--success)' ?>;font-weight:700;"><?= formatMoney($inv['remaining']) ?></td>
            <td><?= invoiceStatusBadge($inv['status']) ?></td>
            <td class="text-muted"><?= formatDate($inv['created_at']) ?></td>
            <td>
              <a href="../invoices/print.php?id=<?= $inv['id'] ?>" class="btn btn-sm btn-primary" target="_blank">
                <i class="fas fa-print"></i>
              </a>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endif; ?>

  <!-- 3. الإيرادات الشهرية -->
  <?php if ($tab === 'monthly'): ?>
  <div style="display:grid;grid-template-columns:2fr 1fr;gap:20px;align-items:start;">
    <div class="card">
      <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
        <span class="card-title"><i class="fas fa-chart-bar"></i> الإيرادات الشهرية لعام <?= $year ?> (إجمالي: <?= formatMoney($totalYear) ?>)</span>
        <form method="GET" style="display:flex;gap:10px;align-items:center;">
          <input type="hidden" name="tab" value="monthly">
          <select name="year" class="form-control" style="width:auto;" onchange="this.form.submit()">
            <?php for ($y = date('Y'); $y >= date('Y')-5; $y--): ?>
            <option value="<?= $y ?>" <?= $year===$y?'selected':'' ?>><?= $y ?></option>
            <?php endfor; ?>
          </select>
        </form>
      </div>
      <div class="card-body">
        <canvas id="monthlyChart" height="100"></canvas>
      </div>
      <div class="table-wrapper">
        <table class="data-table">
          <thead><tr><th>الشهر</th><th>الإيرادات</th><th>النسبة</th></tr></thead>
          <tbody>
            <?php foreach ($months as $m => $amt): ?>
            <tr>
              <td>
                <a href="?tab=payments&date_from=<?= $year ?>-<?= str_pad($m, 2, '0', STR_PAD_LEFT) ?>-01&date_to=<?= $year ?>-<?= str_pad($m, 2, '0', STR_PAD_LEFT) ?>-<?= date('t', strtotime("$year-$m-01")) ?>" style="font-weight:700;color:var(--primary);">
                  <?= $arabicMonths[$m] ?>
                </a>
              </td>
              <td class="fw-bold <?= $amt > 0 ? 'text-success' : 'text-muted' ?>"><?= formatMoney($amt) ?></td>
              <td>
                <?php $pct = $totalYear > 0 ? ($amt/$totalYear*100) : 0; ?>
                <div style="display:flex;align-items:center;gap:8px;">
                  <div style="flex:1;height:6px;background:#f1f5f9;border-radius:3px;">
                    <div style="width:<?= round($pct) ?>%;height:100%;background:var(--primary-light);border-radius:3px;transition:.5s;"></div>
                  </div>
                  <span class="fs-sm text-muted"><?= number_format($pct,1) ?>%</span>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
          <tfoot>
            <tr style="background:#f8fafc;font-weight:800;font-size:14px;">
              <td style="padding:12px 16px;">الإجمالي</td>
              <td style="padding:12px 16px;color:var(--success);"><?= formatMoney($totalYear) ?></td>
              <td></td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><span class="card-title"><i class="fas fa-trophy"></i> أفضل الخدمات إيراداً</span></div>
      <div class="card-body" style="padding:0;">
        <?php foreach ($topServices as $i => $srv): ?>
        <div style="display:flex;align-items:center;gap:12px;padding:14px 18px;border-bottom:1px solid #f1f5f9;">
          <div style="width:28px;height:28px;border-radius:50%;background:var(--primary-light);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:13px;flex-shrink:0;">
            <?= $i+1 ?>
          </div>
          <div style="flex:1;">
            <div style="font-weight:700;font-size:13.5px;"><?= e($srv['name']) ?></div>
            <div style="font-size:12px;color:var(--text-muted);"><?= $srv['count'] ?> اشتراك</div>
          </div>
          <div style="font-weight:800;color:var(--primary-light);font-size:13.5px;"><?= formatMoney($srv['total']) ?></div>
        </div>
        <?php endforeach; ?>
        <?php if (empty($topServices)): ?>
        <div class="empty-state" style="padding:30px;"><p class="empty-title">لا بيانات</p></div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <script>
  document.addEventListener('DOMContentLoaded', function() {
      const ctx = document.getElementById('monthlyChart');
      if (ctx) {
        new Chart(ctx, {
          type: 'bar',
          data: {
            labels: [<?= implode(',', array_map(fn($m) => '"'.$arabicMonths[$m].'"', range(1,12))) ?>],
            datasets: [{
              data: [<?= implode(',', array_values($months)) ?>],
              backgroundColor: [<?= implode(',', array_map(fn($m) => $months[$m]>0?'"rgba(36,86,164,0.7)"':'"rgba(226,232,240,0.7)"', range(1,12))) ?>],
              borderColor: 'rgba(36,86,164,0.8)', borderWidth: 1.5, borderRadius: 6,
            }]
          },
          options: {
            responsive: true,
            plugins: {
              legend: { display: false },
              tooltip: { callbacks: { label: ctx => ctx.parsed.y.toLocaleString('en-US',{minimumFractionDigits:2}) + ' <?= getSetting('currency','جنيه') ?>' } }
            },
            scales: { y: { beginAtZero: true, ticks: { font: { family:'Cairo' } } }, x: { grid: { display:false }, ticks: { font:{family:'Cairo'} } } }
          }
        });
      }
  });
  </script>
  <?php endif; ?>

  <!-- 4. ملخص الخدمات -->
  <?php if ($tab === 'services'): ?>
  <div class="card">
    <div class="table-wrapper">
      <table class="data-table">
        <thead>
          <tr><th>#</th><th>الخدمة</th><th>السعر الافتراضي</th><th>إجمالي العملاء</th><th>العملاء النشطين</th><th>تنتهي قريباً</th><th>إجمالي الإيرادات</th></tr>
        </thead>
        <tbody>
          <?php if (empty($services)): ?>
          <tr><td colspan="7"><div class="empty-state"><p class="empty-title">لا توجد خدمات</p></div></td></tr>
          <?php else: ?>
          <?php foreach ($services as $i => $srv): ?>
          <tr>
            <td class="text-muted"><?= $i+1 ?></td>
            <td><strong><?= e($srv['name']) ?></strong></td>
            <td><?= formatMoney($srv['default_price']) ?></td>
            <td><span class="badge badge-primary"><?= $srv['total_clients'] ?></span></td>
            <td><span class="badge badge-success"><?= $srv['active_clients'] ?></span></td>
            <td>
              <?php if ($srv['expiring_soon'] > 0): ?>
              <span class="badge badge-warning"><?= $srv['expiring_soon'] ?></span>
              <?php else: ?>
              <span class="text-muted">—</span>
              <?php endif; ?>
            </td>
            <td class="fw-bold" style="color:var(--primary-light);"><?= formatMoney($srv['total_revenue']) ?></td>
          </tr>
          <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endif; ?>

</div>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
