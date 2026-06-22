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
    $monthlyExpenses = [];
    $monthlySales = [];
    $monthlyPurchases = [];
    for ($m = 1; $m <= 12; $m++) {
        // الإيرادات
        $stmt = $db->prepare("SELECT COALESCE(SUM(amount),0) FROM payments WHERE YEAR(payment_date)=? AND MONTH(payment_date)=?");
        $stmt->execute([$year, $m]);
        $months[$m] = (float)$stmt->fetchColumn();

        // المصروفات
        $stmt2 = $db->prepare("SELECT COALESCE(SUM(amount),0) FROM expenses WHERE YEAR(expense_date)=? AND MONTH(expense_date)=?");
        $stmt2->execute([$year, $m]);
        $monthlyExpenses[$m] = (float)$stmt2->fetchColumn();

        // المبيعات (من الاشتراكات والخدمات المسجلة)
        $stmt3 = $db->prepare("SELECT COALESCE(SUM(price),0) FROM client_subscriptions WHERE YEAR(start_date)=? AND MONTH(start_date)=?");
        $stmt3->execute([$year, $m]);
        $monthlySales[$m] = (float)$stmt3->fetchColumn();

        // المشتريات (تساوي المصروفات)
        $monthlyPurchases[$m] = $monthlyExpenses[$m];
    }
    $totalYear = array_sum($months);
    $totalExpensesYear = array_sum($monthlyExpenses);
    $netProfitYear = $totalYear - $totalExpensesYear;

    $totalSalesYear = array_sum($monthlySales);
    $totalPurchasesYear = array_sum($monthlyPurchases);
    $netSalesProfitYear = $totalSalesYear - $totalPurchasesYear;

    // أفضل الخدمات
    $topServices = $db->query("
        SELECT s.name, COUNT(*) as count, SUM(cs.price) as total
        FROM client_subscriptions cs JOIN services s ON s.id=cs.service_id
        GROUP BY s.id ORDER BY total DESC LIMIT 5
    ")->fetchAll();

    // تفصيل الإيرادات حسب الخدمات لكل شهر
    $allServices = $db->query("SELECT id, name FROM services ORDER BY sort_order ASC, name ASC")->fetchAll();
    $serviceMonthlyRevenue = [];
    foreach (range(1, 12) as $m) {
        $serviceMonthlyRevenue[$m] = [];
        foreach ($allServices as $srv) {
            $stmt = $db->prepare("
                SELECT COALESCE(SUM(p.amount),0) 
                FROM payments p
                JOIN client_subscriptions cs ON cs.id = p.subscription_id
                WHERE cs.service_id = ? 
                  AND YEAR(p.payment_date) = ? 
                  AND MONTH(p.payment_date) = ?
            ");
            $stmt->execute([$srv['id'], $year, $m]);
            $serviceMonthlyRevenue[$m][$srv['id']] = (float)$stmt->fetchColumn();
        }
    }
    // تفصيل المصروفات حسب التصنيفات لكل شهر
    $expenseCategories = ['دومين', 'سيرفر', 'إعلانات', 'موظفين', 'أخرى'];
    $categoryMonthlyExpenses = [];
    foreach (range(1, 12) as $m) {
        $categoryMonthlyExpenses[$m] = [];
        foreach ($expenseCategories as $cat) {
            $stmt = $db->prepare("
                SELECT COALESCE(SUM(amount),0) 
                FROM expenses 
                WHERE category = ? 
                  AND YEAR(expense_date) = ? 
                  AND MONTH(expense_date) = ?
            ");
            $stmt->execute([$cat, $year, $m]);
            $categoryMonthlyExpenses[$m][$cat] = (float)$stmt->fetchColumn();
        }
    }

    $arabicMonths = ['','يناير','فبراير','مارس','أبريل','مايو','يونيو','يوليو','أغسطس','سبتمبر','أكتوبر','نوفمبر','ديسمبر'];
}

// ── 4. تبويب ملخص الخدمات (Services Summary Tab) ─────────────────
elseif ($tab === 'services') {
    requirePermission('view_reports');
    
    $dateFrom = clean($_GET['date_from'] ?? '');
    $dateTo   = clean($_GET['date_to'] ?? '');
    
    $payWhere = ["1=1"];
    $payParams = [];
    if ($dateFrom) { $payWhere[] = "p.payment_date >= ?"; $payParams[] = $dateFrom; }
    if ($dateTo)   { $payWhere[] = "p.payment_date <= ?"; $payParams[] = $dateTo; }
    $payWhereStr = implode(' AND ', $payWhere);
    
    $services = $db->prepare("
        SELECT s.name, s.default_price,
               COUNT(DISTINCT CASE WHEN cs.status='active' THEN cs.client_id END) as active_clients,
               COUNT(DISTINCT cs.client_id) as total_clients,
               COALESCE((
                   SELECT SUM(p.amount)
                   FROM payments p
                   JOIN client_subscriptions cs2 ON cs2.id = p.subscription_id
                   WHERE cs2.service_id = s.id AND $payWhereStr
               ), 0) as total_revenue,
               COUNT(CASE WHEN cs.status='active' AND cs.end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(),INTERVAL 30 DAY) THEN 1 END) as expiring_soon
        FROM services s
        LEFT JOIN client_subscriptions cs ON cs.service_id=s.id
        GROUP BY s.id ORDER BY total_revenue DESC
    ");
    $services->execute($payParams);
}

// ── 5. تبويب المصروفات (Expenses Tab) ─────────────────────────────
elseif ($tab === 'expenses') {
    requirePermission('manage_expenses');
    
    // إضافة مصروف جديد
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_expense'])) {
        $title    = clean($_POST['title']);
        $amount   = (float)$_POST['amount'];
        $expDate  = clean($_POST['expense_date']);
        $category = clean($_POST['category']);
        $notes    = clean($_POST['notes']);
        $userId   = currentUserId();
        
        $db->prepare("
            INSERT INTO expenses (title, amount, expense_date, category, notes, created_by)
            VALUES (?, ?, ?, ?, ?, ?)
        ")->execute([$title, $amount, $expDate, $category, $notes, $userId]);
        
        header("Location: financial-hub.php?tab=expenses&msg=added");
        exit;
    }

    // تعديل مصروف جديد
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_expense'])) {
        $id       = (int)$_POST['expense_id'];
        $title    = clean($_POST['title']);
        $amount   = (float)$_POST['amount'];
        $expDate  = clean($_POST['expense_date']);
        $category = clean($_POST['category']);
        $notes    = clean($_POST['notes']);
        
        $db->prepare("
            UPDATE expenses 
            SET title = ?, amount = ?, expense_date = ?, category = ?, notes = ?
            WHERE id = ?
        ")->execute([$title, $amount, $expDate, $category, $notes, $id]);
        
        header("Location: financial-hub.php?tab=expenses&msg=updated");
        exit;
    }
    
    // حذف مصروف
    if (isset($_GET['delete_expense'])) {
        $id = (int)$_GET['delete_expense'];
        $db->prepare("DELETE FROM expenses WHERE id = ?")->execute([$id]);
        header("Location: financial-hub.php?tab=expenses&msg=deleted");
        exit;
    }
    
    $search   = clean($_GET['search'] ?? '');
    $category = clean($_GET['category'] ?? '');
    $dateFrom = clean($_GET['date_from'] ?? '');
    $dateTo   = clean($_GET['date_to'] ?? '');
    $page     = max(1, (int)($_GET['page'] ?? 1));
    $perPage  = 25;
    
    $where  = ['1=1'];
    $params = [];
    if ($search) { $where[] = "(title LIKE ? OR notes LIKE ?)"; $s="%$search%"; $params=array_merge($params,[$s,$s]); }
    if ($category) { $where[] = "category = ?"; $params[] = $category; }
    if ($dateFrom) { $where[] = "expense_date >= ?"; $params[] = $dateFrom; }
    if ($dateTo)   { $where[] = "expense_date <= ?"; $params[] = $dateTo; }
    $whereStr = implode(' AND ', $where);
    
    $countStmt = $db->prepare("SELECT COUNT(*) FROM expenses WHERE $whereStr");
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();
    $pager = paginate($total, $perPage, $page);
    
    $sumStmt = $db->prepare("SELECT COALESCE(SUM(amount),0) FROM expenses WHERE $whereStr");
    $sumStmt->execute($params);
    $totalAmount = (float)$sumStmt->fetchColumn();
    
    $stmt = $db->prepare("
        SELECT e.*, u.full_name as added_by
        FROM expenses e
        LEFT JOIN users u ON u.id = e.created_by
        WHERE $whereStr
        ORDER BY e.expense_date DESC, e.created_at DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->execute(array_merge($params, [$perPage, $pager['offset']]));
    $expenses = $stmt->fetchAll();
    
    // AJAX handling
    if (isset($_GET['ajax'])) {
        header('Content-Type: application/json');
        ob_start();
        ?>
        <?php if (empty($expenses)): ?>
        <tr><td colspan="8"><div class="empty-state"><div class="empty-icon"><i class="fas fa-file-signature"></i></div><p class="empty-title">لا توجد مصروفات</p></div></td></tr>
        <?php else: ?>
        <?php foreach ($expenses as $i => $exp): ?>
        <tr>
          <td class="text-muted"><?= $pager['offset']+$i+1 ?></td>
          <td><?= formatDate($exp['expense_date']) ?></td>
          <td><strong><?= e($exp['title']) ?></strong></td>
          <td style="color:var(--danger);font-weight:700;"><?= formatMoney($exp['amount']) ?></td>
          <td><span class="badge badge-info"><?= e($exp['category']) ?></span></td>
          <td class="text-muted fs-sm"><?= e($exp['notes'] ?: '—') ?></td>
          <td class="text-muted fs-sm"><?= e($exp['added_by'] ?? '—') ?></td>
          <td>
            <div class="table-actions">
              <button type="button" class="btn btn-sm btn-outline" title="تعديل"
                      onclick="openEditExpenseModal(<?= htmlspecialchars(json_encode([
                          'id' => $exp['id'],
                          'title' => $exp['title'],
                          'amount' => $exp['amount'],
                          'expense_date' => $exp['expense_date'],
                          'category' => $exp['category'],
                          'notes' => $exp['notes']
                      ]), ENT_QUOTES, 'UTF-8') ?>)">
                <i class="fas fa-edit"></i>
              </button>
              <a href="?tab=expenses&delete_expense=<?= $exp['id'] ?>"
                 class="btn btn-sm btn-outline-danger" data-confirm="حذف هذا المصروف؟" title="حذف">
                <i class="fas fa-trash"></i>
              </a>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
        <?php
        $tbodyHtml = ob_get_clean();
        
        ob_start();
        ?>
        <?php if (!empty($expenses)): ?>
        <tr style="background:#f8fafc;font-weight:700;">
          <td colspan="3" style="padding:12px 16px;text-align:right;">المجموع في هذه الصفحة:</td>
          <td style="padding:12px 16px;color:var(--danger);font-size:15px;">
            <?= formatMoney(array_sum(array_column($expenses,'amount'))) ?>
          </td>
          <td colspan="4"></td>
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
            من <?= $total ?> مصروف
          </span>
          <div class="pagination">
            <?php
            $queryBase = http_build_query(array_filter(['search' => $search, 'category' => $category, 'date_from' => $dateFrom, 'date_to' => $dateTo]));
            $sep = $queryBase ? '&' : '';
            ?>
            <a href="?tab=expenses&<?= $queryBase ?><?= $sep ?>page=<?= $pager['current_page'] - 1 ?>"
               class="page-btn <?= !$pager['has_prev'] ? 'disabled' : '' ?>" data-page="<?= $pager['current_page'] - 1 ?>">
              <i class="fas fa-chevron-right"></i>
            </a>
            <?php for ($p = 1; $p <= $pager['total_pages']; $p++): ?>
            <a href="?tab=expenses&<?= $queryBase ?><?= $sep ?>page=<?= $p ?>"
               class="page-btn <?= $p === $pager['current_page'] ? 'active' : '' ?>" data-page="<?= $p ?>">
              <?= $p ?>
            </a>
            <?php endfor; ?>
            <a href="?tab=expenses&<?= $queryBase ?><?= $sep ?>page=<?= $pager['current_page'] + 1 ?>"
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
            'subtitle' => 'إجمالي ' . $total . ' مصروف — مجموع المصروفات: ' . formatMoney($totalAmount)
        ]);
        exit;
    }
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
    <i class="fas fa-chart-line"></i> الأرباح والخسائر والتحليلات
  </a>
  <a href="?tab=services" class="tab-btn <?= $tab === 'services' ? 'active' : '' ?>">
    <i class="fas fa-layer-group"></i> ملخص الخدمات
  </a>
  <a href="?tab=expenses" class="tab-btn <?= $tab === 'expenses' ? 'active' : '' ?>">
    <i class="fas fa-file-signature"></i> المصروفات
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

  <!-- 3. الأرباح والخسائر والتحليلات -->
  <?php if ($tab === 'monthly'): ?>
  
  <!-- بطاقات الملخص السنوي للمركز المالي -->
  <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap:20px; margin-bottom:20px;">
    <div class="card" style="border-right: 4px solid var(--success); padding:16px; background:#fff; display:flex; align-items:center; gap:16px; margin: 0;">
      <div style="width:48px; height:48px; border-radius:12px; background:rgba(16,185,129,0.1); color:var(--success); display:flex; align-items:center; justify-content:center; font-size:20px;">
        <i class="fas fa-arrow-trend-up"></i>
      </div>
      <div>
        <div style="font-size:12.5px; color:var(--text-muted); font-weight:600;">إجمالي إيرادات السنة</div>
        <div style="font-size:18px; font-weight:800; color:var(--success); margin-top:4px;"><?= formatMoney($totalYear) ?></div>
      </div>
    </div>

    <div class="card" style="border-right: 4px solid var(--danger); padding:16px; background:#fff; display:flex; align-items:center; gap:16px; margin: 0;">
      <div style="width:48px; height:48px; border-radius:12px; background:rgba(239,68,68,0.1); color:var(--danger); display:flex; align-items:center; justify-content:center; font-size:20px;">
        <i class="fas fa-arrow-trend-down"></i>
      </div>
      <div>
        <div style="font-size:12.5px; color:var(--text-muted); font-weight:600;">إجمالي مصروفات السنة</div>
        <div style="font-size:18px; font-weight:800; color:var(--danger); margin-top:4px;"><?= formatMoney($totalExpensesYear) ?></div>
      </div>
    </div>

    <div class="card" style="border-right: 4px solid <?= $netProfitYear >= 0 ? 'var(--success)' : 'var(--danger)' ?>; padding:16px; background:#fff; display:flex; align-items:center; gap:16px; margin: 0;">
      <div style="width:48px; height:48px; border-radius:12px; background:<?= $netProfitYear >= 0 ? 'rgba(16,185,129,0.1)' : 'rgba(239,68,68,0.1)' ?>; color:<?= $netProfitYear >= 0 ? 'var(--success)' : 'var(--danger)' ?>; display:flex; align-items:center; justify-content:center; font-size:20px;">
        <i class="fas <?= $netProfitYear >= 0 ? 'fa-scale-balanced' : 'fa-triangle-exclamation' ?>"></i>
      </div>
      <div>
        <div style="font-size:12.5px; color:var(--text-muted); font-weight:600;">صافي أرباح السنة</div>
        <div style="font-size:18px; font-weight:800; color:<?= $netProfitYear >= 0 ? 'var(--success)' : 'var(--danger)' ?>; margin-top:4px;"><?= ($netProfitYear >= 0 ? '+' : '') . formatMoney($netProfitYear) ?></div>
      </div>
    </div>

    <div class="card" style="border-right: 4px solid #2563eb; padding:16px; background:#fff; display:flex; align-items:center; gap:16px; margin: 0;">
      <div style="width:48px; height:48px; border-radius:12px; background:rgba(37,99,235,0.1); color:#2563eb; display:flex; align-items:center; justify-content:center; font-size:20px;">
        <i class="fas fa-file-invoice-dollar"></i>
      </div>
      <div>
        <div style="font-size:12.5px; color:var(--text-muted); font-weight:600;">إجمالي مبيعات السنة (الاشتراكات والخدمات)</div>
        <div style="font-size:18px; font-weight:800; color:#2563eb; margin-top:4px;"><?= formatMoney($totalSalesYear) ?></div>
      </div>
    </div>

    <div class="card" style="border-right: 4px solid #ea580c; padding:16px; background:#fff; display:flex; align-items:center; gap:16px; margin: 0;">
      <div style="width:48px; height:48px; border-radius:12px; background:rgba(234,88,12,0.1); color:#ea580c; display:flex; align-items:center; justify-content:center; font-size:20px;">
        <i class="fas fa-cart-shopping"></i>
      </div>
      <div>
        <div style="font-size:12.5px; color:var(--text-muted); font-weight:600;">إجمالي مشتريات السنة (المصروفات)</div>
        <div style="font-size:18px; font-weight:800; color:#ea580c; margin-top:4px;"><?= formatMoney($totalPurchasesYear) ?></div>
      </div>
    </div>

    <div class="card" style="border-right: 4px solid <?= $netSalesProfitYear >= 0 ? '#2563eb' : 'var(--danger)' ?>; padding:16px; background:#fff; display:flex; align-items:center; gap:16px; margin: 0;">
      <div style="width:48px; height:48px; border-radius:12px; background:<?= $netSalesProfitYear >= 0 ? 'rgba(37,99,235,0.1)' : 'rgba(239,68,68,0.1)' ?>; color:<?= $netSalesProfitYear >= 0 ? '#2563eb' : 'var(--danger)' ?>; display:flex; align-items:center; justify-content:center; font-size:20px;">
        <i class="fas <?= $netSalesProfitYear >= 0 ? 'fa-scale-balanced' : 'fa-triangle-exclamation' ?>"></i>
      </div>
      <div>
        <div style="font-size:12.5px; color:var(--text-muted); font-weight:600;">صافي قيمة المبيعات</div>
        <div style="font-size:18px; font-weight:800; color:<?= $netSalesProfitYear >= 0 ? '#2563eb' : 'var(--danger)' ?>; margin-top:4px;"><?= ($netSalesProfitYear >= 0 ? '+' : '') . formatMoney($netSalesProfitYear) ?></div>
      </div>
    </div>
  </div>

  <div style="display:grid;grid-template-columns:2fr 1fr;gap:20px;align-items:start;">
    <div class="card" style="margin: 0;">
      <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
        <span class="card-title"><i class="fas fa-chart-bar"></i> تحليل الأرباح والخسائر لعام <?= $year ?></span>
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
          <thead><tr><th>الشهر</th><th>الإيرادات (+)</th><th>المصروفات (-)</th><th>صافي الربح / الخسارة</th></tr></thead>
          <tbody>
            <?php foreach ($months as $m => $amt): 
              $expAmt = $monthlyExpenses[$m] ?? 0;
              $profit = $amt - $expAmt;
            ?>
            <tr>
              <td>
                <a href="?tab=payments&date_from=<?= $year ?>-<?= str_pad($m, 2, '0', STR_PAD_LEFT) ?>-01&date_to=<?= $year ?>-<?= str_pad($m, 2, '0', STR_PAD_LEFT) ?>-<?= date('t', strtotime("$year-$m-01")) ?>" style="font-weight:700;color:var(--primary);">
                  <?= $arabicMonths[$m] ?>
                </a>
              </td>
              <td class="fw-bold text-success"><?= formatMoney($amt) ?></td>
              <td class="fw-bold text-danger"><?= formatMoney($expAmt) ?></td>
              <td class="fw-bold" style="color: <?= $profit >= 0 ? 'var(--success)' : 'var(--danger)' ?>;">
                <?= ($profit >= 0 ? '+' : '') . formatMoney($profit) ?>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
          <tfoot>
            <tr style="background:#f8fafc;font-weight:800;font-size:14px;">
              <td style="padding:12px 16px;">الإجمالي السنوي</td>
              <td style="padding:12px 16px;color:var(--success);"><?= formatMoney($totalYear) ?></td>
              <td style="padding:12px 16px;color:var(--danger);"><?= formatMoney($totalExpensesYear) ?></td>
              <td style="padding:12px 16px;color: <?= $netProfitYear >= 0 ? 'var(--success)' : 'var(--danger)' ?>;">
                <?= ($netProfitYear >= 0 ? '+' : '') . formatMoney($netProfitYear) ?>
              </td>
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

  <!-- تحليل المبيعات والمشتريات لعام YYYY -->
  <div class="card" style="margin-top: 20px;">
    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
      <span class="card-title"><i class="fas fa-chart-line"></i> تحليل المبيعات والمشتريات لعام <?= $year ?></span>
    </div>
    <div class="card-body">
      <canvas id="salesPurchasesChart" height="80"></canvas>
    </div>
    <div class="table-wrapper">
      <table class="data-table">
        <thead>
          <tr>
            <th>الشهر</th>
            <th>المبيعات (الاشتراكات والخدمات) (+)</th>
            <th>المشتريات (المصروفات) (-)</th>
            <th>صافي قيمة المبيعات</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($months as $m => $amt): 
            $salesAmt = $monthlySales[$m] ?? 0;
            $purchAmt = $monthlyPurchases[$m] ?? 0;
            $diff = $salesAmt - $purchAmt;
          ?>
          <tr>
            <td>
              <span style="font-weight:700;color:var(--primary);"><?= $arabicMonths[$m] ?></span>
            </td>
            <td class="fw-bold text-success"><?= formatMoney($salesAmt) ?></td>
            <td class="fw-bold text-danger"><?= formatMoney($purchAmt) ?></td>
            <td class="fw-bold" style="color: <?= $diff >= 0 ? '#2563eb' : 'var(--danger)' ?>;">
              <?= ($diff >= 0 ? '+' : '') . formatMoney($diff) ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
        <tfoot>
          <tr style="background:#f8fafc;font-weight:800;font-size:14px;">
            <td style="padding:12px 16px;">الإجمالي السنوي</td>
            <td style="padding:12px 16px;color:#2563eb;"><?= formatMoney($totalSalesYear) ?></td>
            <td style="padding:12px 16px;color:var(--danger);"><?= formatMoney($totalPurchasesYear) ?></td>
            <td style="padding:12px 16px;color: <?= $netSalesProfitYear >= 0 ? '#2563eb' : 'var(--danger)' ?>;">
              <?= ($netSalesProfitYear >= 0 ? '+' : '') . formatMoney($netSalesProfitYear) ?>
            </td>
          </tr>
        </tfoot>
      </table>
    </div>
  </div>

  <!-- تفصيل الأرباح والخسائر الشامل لعام 2026 -->
  <div class="card" style="margin-top: 20px;">
    <div class="card-header">
      <span class="card-title"><i class="fas fa-table"></i> الميزانية التفصيلية (إيرادات الخدمات مقابل مصروفات الأقسام) لعام <?= $year ?></span>
    </div>
    <div class="table-wrapper" style="overflow-x: auto;">
      <table class="data-table" style="font-size:12px; width:100%; min-width:1200px; text-align:center;">
        <thead>
          <tr style="background:#f1f5f9; text-align:center;">
            <th rowspan="2" style="vertical-align:middle; border-left:1.5px solid var(--border-color); text-align:center;">الشهر</th>
            <th colspan="<?= count($allServices) + 1 ?>" style="background:rgba(16,185,129,0.08); color:#065f46; border-left:1.5px solid var(--border-color); font-weight:800; text-align:center;">إيرادات الخدمات المباشرة (+)</th>
            <th colspan="<?= count($expenseCategories) + 1 ?>" style="background:rgba(239,68,68,0.08); color:#991b1b; border-left:1.5px solid var(--border-color); font-weight:800; text-align:center;">مصروفات التشغيل حسب الأقسام (-)</th>
            <th rowspan="2" style="vertical-align:middle; background:rgba(36,86,164,0.08); color:#1e3a8a; font-weight:800; text-align:center;">صافي الربح / الخسارة</th>
          </tr>
          <tr style="background:#fafbfc;">
            <?php foreach ($allServices as $srv): ?>
            <th style="font-size:11px;"><?= e($srv['name']) ?></th>
            <?php endforeach; ?>
            <th style="font-weight:700; border-left:1.5px solid var(--border-color); background:rgba(16,185,129,0.04); color:#065f46;">إجمالي الإيرادات</th>
            
            <?php foreach ($expenseCategories as $cat): ?>
            <th style="font-size:11px;"><?= e($cat) ?></th>
            <?php endforeach; ?>
            <th style="font-weight:700; border-left:1.5px solid var(--border-color); background:rgba(239,68,68,0.04); color:#991b1b;">إجمالي المصروفات</th>
          </tr>
        </thead>
        <tbody>
          <?php 
          $servicesColTotals = array_fill_keys(array_column($allServices, 'id'), 0);
          $categoriesColTotals = array_fill_keys($expenseCategories, 0);
          $grandTotalRevenue = 0;
          $grandTotalExpenses = 0;
          $grandTotalNetProfit = 0;

          foreach (range(1, 12) as $m): 
              $rowRevTotal = 0;
              $rowExpTotal = 0;
          ?>
          <tr style="transition: background 0.15s;">
            <td style="font-weight:700; border-left:1.5px solid var(--border-color); background:#fafbfc;">
              <a href="?tab=payments&date_from=<?= $year ?>-<?= str_pad($m, 2, '0', STR_PAD_LEFT) ?>-01&date_to=<?= $year ?>-<?= str_pad($m, 2, '0', STR_PAD_LEFT) ?>-<?= date('t', strtotime("$year-$m-01")) ?>" style="color:var(--primary); font-weight:700;">
                <?= $arabicMonths[$m] ?>
              </a>
            </td>
            
            <!-- قيم إيرادات الخدمات -->
            <?php foreach ($allServices as $srv): 
              $val = $serviceMonthlyRevenue[$m][$srv['id']] ?? 0;
              $rowRevTotal += $val;
              $servicesColTotals[$srv['id']] += $val;
            ?>
            <td class="<?= $val > 0 ? 'fw-bold text-success' : 'text-muted' ?>" style="font-size:11.5px;">
              <?= $val > 0 ? formatMoney($val) : '—' ?>
            </td>
            <?php endforeach; ?>
            <td class="fw-bold text-success" style="border-left:1.5px solid var(--border-color); background:rgba(16,185,129,0.02); font-size:12px;">
              <?= formatMoney($rowRevTotal) ?>
            </td>
            
            <!-- قيم مصروفات الأقسام -->
            <?php foreach ($expenseCategories as $cat): 
              $val = $categoryMonthlyExpenses[$m][$cat] ?? 0;
              $rowExpTotal += $val;
              $categoriesColTotals[$cat] += $val;
            ?>
            <td class="<?= $val > 0 ? 'fw-bold text-danger' : 'text-muted' ?>" style="font-size:11.5px;">
              <?= $val > 0 ? formatMoney($val) : '—' ?>
            </td>
            <?php endforeach; ?>
            <td class="fw-bold text-danger" style="border-left:1.5px solid var(--border-color); background:rgba(239,68,68,0.02); font-size:12px;">
              <?= formatMoney($rowExpTotal) ?>
            </td>
            
            <!-- صافي الربح للمقاصة -->
            <?php 
            $rowNet = $rowRevTotal - $rowExpTotal;
            $grandTotalRevenue += $rowRevTotal;
            $grandTotalExpenses += $rowExpTotal;
            $grandTotalNetProfit += $rowNet;
            ?>
            <td class="fw-bold" style="background:rgba(36,86,164,0.02); color:<?= $rowNet >= 0 ? 'var(--success)' : 'var(--danger)' ?>; font-size:12px;">
              <?= ($rowNet >= 0 ? '+' : '') . formatMoney($rowNet) ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
        <tfoot class="table-totals">
          <tr style="background:#f8fafc; font-weight:800; border-top:2.5px solid #cbd5e1; font-size:12px;">
            <td style="padding:12px 16px; border-left:1.5px solid var(--border-color);">الإجمالي السنوي</td>
            
            <!-- مجاميع الخدمات -->
            <?php foreach ($allServices as $srv): ?>
            <td style="padding:12px 16px; color:var(--primary-light);"><?= formatMoney($servicesColTotals[$srv['id']]) ?></td>
            <?php endforeach; ?>
            <td style="padding:12px 16px; color:var(--success); border-left:1.5px solid var(--border-color); background:rgba(16,185,129,0.04); font-size:12.5px;">
              <?= formatMoney($grandTotalRevenue) ?>
            </td>
            
            <!-- مجاميع المصروفات -->
            <?php foreach ($expenseCategories as $cat): ?>
            <td style="padding:12px 16px; color:var(--primary-light);"><?= formatMoney($categoriesColTotals[$cat]) ?></td>
            <?php endforeach; ?>
            <td style="padding:12px 16px; color:var(--danger); border-left:1.5px solid var(--border-color); background:rgba(239,68,68,0.04); font-size:12.5px;">
              <?= formatMoney($grandTotalExpenses) ?>
            </td>
            
            <!-- صافي الربح السنوي الإجمالي -->
            <td style="padding:12px 16px; color:<?= $grandTotalNetProfit >= 0 ? 'var(--success)' : 'var(--danger)' ?>; font-size:13px; background:rgba(36,86,164,0.06);">
              <?= ($grandTotalNetProfit >= 0 ? '+' : '') . formatMoney($grandTotalNetProfit) ?>
            </td>
          </tr>
        </tfoot>
      </table>
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
            datasets: [
              {
                label: 'الإيرادات',
                data: [<?= implode(',', array_values($months)) ?>],
                backgroundColor: 'rgba(16, 185, 129, 0.75)',
                borderColor: '#10b981',
                borderWidth: 1.5,
                borderRadius: 6,
              },
              {
                label: 'المصروفات',
                data: [<?= implode(',', array_values($monthlyExpenses)) ?>],
                backgroundColor: 'rgba(239, 68, 68, 0.75)',
                borderColor: '#ef4444',
                borderWidth: 1.5,
                borderRadius: 6,
              }
            ]
          },
          options: {
            responsive: true,
            plugins: {
              legend: { display: true, labels: { font: { family: 'Cairo', weight: 'bold' } } },
              tooltip: { callbacks: { label: ctx => ctx.dataset.label + ': ' + ctx.parsed.y.toLocaleString('en-US',{minimumFractionDigits:2}) + ' <?= getSetting('currency','جنيه') ?>' } }
            },
            scales: { y: { beginAtZero: true, ticks: { font: { family:'Cairo' } } }, x: { grid: { display:false }, ticks: { font:{family:'Cairo'} } } }
          }
        });
      }

      const ctxSales = document.getElementById('salesPurchasesChart');
      if (ctxSales) {
        new Chart(ctxSales, {
          type: 'bar',
          data: {
            labels: [<?= implode(',', array_map(fn($m) => '"'.$arabicMonths[$m].'"', range(1,12))) ?>],
            datasets: [
              {
                label: 'المبيعات',
                data: [<?= implode(',', array_values($monthlySales)) ?>],
                backgroundColor: 'rgba(37, 99, 235, 0.75)',
                borderColor: '#2563eb',
                borderWidth: 1.5,
                borderRadius: 6,
              },
              {
                label: 'المشتريات',
                data: [<?= implode(',', array_values($monthlyPurchases)) ?>],
                backgroundColor: 'rgba(234, 88, 12, 0.75)',
                borderColor: '#ea580c',
                borderWidth: 1.5,
                borderRadius: 6,
              }
            ]
          },
          options: {
            responsive: true,
            plugins: {
              legend: { display: true, labels: { font: { family: 'Cairo', weight: 'bold' } } },
              tooltip: { callbacks: { label: ctx => ctx.dataset.label + ': ' + ctx.parsed.y.toLocaleString('en-US',{minimumFractionDigits:2}) + ' <?= getSetting('currency','جنيه') ?>' } }
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
  <?php
  $dateFrom = clean($_GET['date_from'] ?? '');
  $dateTo   = clean($_GET['date_to'] ?? '');
  ?>
  <div class="card" style="margin-bottom:16px;">
    <div class="filters-bar">
      <form method="GET" style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;width:100%;" id="servicesFilterForm">
        <input type="hidden" name="tab" value="services">
        <span style="font-weight:700;color:var(--text-primary);margin-left:8px;">فلترة إيرادات الخدمات:</span>
        
        <!-- أزرار الفلترة السريعة -->
        <div class="btn-group" id="servicesQuickDateFilters" style="display:flex;gap:4px;background:#f1f5f9;padding:4px;border-radius:8px;">
          <button type="button" class="btn btn-sm" data-range="all" style="border:none;padding:6px 14px;border-radius:6px;font-weight:600;font-size:13px;cursor:pointer;transition:all 0.2s;">الكل</button>
          <button type="button" class="btn btn-sm" data-range="day" style="border:none;padding:6px 14px;border-radius:6px;font-weight:600;font-size:13px;cursor:pointer;transition:all 0.2s;">يوم</button>
          <button type="button" class="btn btn-sm" data-range="month" style="border:none;padding:6px 14px;border-radius:6px;font-weight:600;font-size:13px;cursor:pointer;transition:all 0.2s;">شهر</button>
          <button type="button" class="btn btn-sm" data-range="year" style="border:none;padding:6px 14px;border-radius:6px;font-weight:600;font-size:13px;cursor:pointer;transition:all 0.2s;">سنة</button>
          <button type="button" class="btn btn-sm" data-range="custom" style="border:none;padding:6px 14px;border-radius:6px;font-weight:600;font-size:13px;cursor:pointer;transition:all 0.2s;">مخصص</button>
        </div>

        <div id="servicesCustomWrapper" style="display:none; gap:12px; align-items:center;">
          <input type="date" name="date_from" class="form-control" style="width:auto;" value="<?= e($dateFrom) ?>" placeholder="من تاريخ">
          <input type="date" name="date_to" class="form-control" style="width:auto;" value="<?= e($dateTo) ?>" placeholder="إلى تاريخ">
        </div>
        <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> فلترة</button>
        <?php if ($dateFrom || $dateTo): ?>
        <a href="?tab=services" class="btn btn-outline"><i class="fas fa-times"></i> إلغاء</a>
        <?php endif; ?>
      </form>
    </div>
  </div>

  <div class="card">
    <div class="table-wrapper">
      <table class="data-table">
        <thead>
          <tr><th>#</th><th>الخدمة</th><th>السعر الافتراضي</th><th>إجمالي العملاء</th><th>العملاء النشطين</th><th>تنتهي قريباً</th><th>الإيرادات في هذه الفترة</th></tr>
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
        <tfoot class="table-totals">
          <tr style="background:#f8fafc;font-weight:700;">
            <td colspan="6" style="padding:12px 16px;text-align:right;">إجمالي الإيرادات للفترة المحددة:</td>
            <td style="padding:12px 16px;color:var(--success);font-size:15px;">
              <?= formatMoney(array_sum(array_column($services,'total_revenue'))) ?>
            </td>
          </tr>
        </tfoot>
      </table>
    </div>
  </div>

  <script>
  document.addEventListener('DOMContentLoaded', function() {
      const dateFromInput = document.querySelector('#servicesFilterForm input[name="date_from"]');
      const dateToInput = document.querySelector('#servicesFilterForm input[name="date_to"]');
      const servicesFilterForm = document.getElementById('servicesFilterForm');
      const quickFilters = document.getElementById('servicesQuickDateFilters');
      const customWrapper = document.getElementById('servicesCustomWrapper');

      const formatDate = (date) => {
          const y = date.getFullYear();
          const m = String(date.getMonth() + 1).padStart(2, '0');
          const d = String(date.getDate()).padStart(2, '0');
          return `${y}-${m}-${d}`;
      };

      function updateActiveFilter(range) {
          document.querySelectorAll('#servicesQuickDateFilters button').forEach(btn => {
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

      const todayStr = formatDate(new Date());
      const firstDayMonth = formatDate(new Date(new Date().getFullYear(), new Date().getMonth(), 1));
      const lastDayMonth = formatDate(new Date(new Date().getFullYear(), new Date().getMonth() + 1, 0));
      const firstDayYear = formatDate(new Date(new Date().getFullYear(), 0, 1));
      const lastDayYear = formatDate(new Date(new Date().getFullYear(), 11, 31));

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
                  servicesFilterForm.submit();
              }
          });
      }
  });
  </script>
  <?php endif; ?>

  <!-- 5. المصروفات -->
  <?php if ($tab === 'expenses'): ?>
  <?php
  $dateFrom = clean($_GET['date_from'] ?? '');
  $dateTo   = clean($_GET['date_to'] ?? '');
  $search   = clean($_GET['search'] ?? '');
  $category = clean($_GET['category'] ?? '');
  ?>
  
  <?php if (isset($_GET['msg'])): ?>
    <?php if ($_GET['msg'] === 'added'): ?>
      <div class="alert alert-success" style="margin-bottom: 16px;"><i class="fas fa-check-circle"></i> تم إضافة المصروف بنجاح.</div>
    <?php elseif ($_GET['msg'] === 'updated'): ?>
      <div class="alert alert-success" style="margin-bottom: 16px;"><i class="fas fa-check-circle"></i> تم تعديل المصروف بنجاح.</div>
    <?php elseif ($_GET['msg'] === 'deleted'): ?>
      <div class="alert alert-success" style="margin-bottom: 16px;"><i class="fas fa-check-circle"></i> تم حذف المصروف بنجاح.</div>
    <?php endif; ?>
  <?php endif; ?>

  <div class="card" style="margin-bottom:16px;">
    <div class="filters-bar" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
      <form method="GET" style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;" id="expensesFilterForm">
        <input type="hidden" name="tab" value="expenses">
        <div class="search-box">
          <i class="fas fa-search search-icon"></i>
          <input type="text" name="search" class="form-control" placeholder="بحث في المصروفات..." value="<?= e($search) ?>" autocomplete="off">
        </div>
        
        <select name="category" class="form-control" style="width:auto;">
          <option value="">كل التصنيفات</option>
          <option value="دومين" <?= $category === 'دومين' ? 'selected' : '' ?>>دومينات</option>
          <option value="سيرفر" <?= $category === 'سيرفر' ? 'selected' : '' ?>>سيرفرات واستضافات</option>
          <option value="إعلانات" <?= $category === 'إعلانات' ? 'selected' : '' ?>>إعلانات وتسويق</option>
          <option value="موظفين" <?= $category === 'موظفين' ? 'selected' : '' ?>>رواتب وموظفين</option>
          <option value="أخرى" <?= $category === 'أخرى' ? 'selected' : '' ?>>مصاريف أخرى</option>
        </select>
        
        <!-- أزرار الفلترة السريعة -->
        <div class="btn-group" id="expensesQuickDateFilters" style="display:flex;gap:4px;background:#f1f5f9;padding:4px;border-radius:8px;">
          <button type="button" class="btn btn-sm" data-range="all" style="border:none;padding:6px 14px;border-radius:6px;font-weight:600;font-size:13px;cursor:pointer;transition:all 0.2s;">الكل</button>
          <button type="button" class="btn btn-sm" data-range="day" style="border:none;padding:6px 14px;border-radius:6px;font-weight:600;font-size:13px;cursor:pointer;transition:all 0.2s;">يوم</button>
          <button type="button" class="btn btn-sm" data-range="month" style="border:none;padding:6px 14px;border-radius:6px;font-weight:600;font-size:13px;cursor:pointer;transition:all 0.2s;">شهر</button>
          <button type="button" class="btn btn-sm" data-range="year" style="border:none;padding:6px 14px;border-radius:6px;font-weight:600;font-size:13px;cursor:pointer;transition:all 0.2s;">سنة</button>
          <button type="button" class="btn btn-sm" data-range="custom" style="border:none;padding:6px 14px;border-radius:6px;font-weight:600;font-size:13px;cursor:pointer;transition:all 0.2s;">مخصص</button>
        </div>

        <div id="expensesCustomWrapper" style="display:none; gap:12px; align-items:center;">
          <input type="date" name="date_from" class="form-control" style="width:auto;" value="<?= e($dateFrom) ?>" placeholder="من تاريخ">
          <input type="date" name="date_to" class="form-control" style="width:auto;" value="<?= e($dateTo) ?>" placeholder="إلى تاريخ">
        </div>
        
        <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> بحث</button>
        <a href="?tab=expenses" class="btn btn-outline" id="expensesClearBtn" style="display:none;"><i class="fas fa-times"></i> مسح</a>
      </form>
      
      <?php if (hasPermission('manage_expenses')): ?>
      <button type="button" class="btn btn-success" onclick="document.getElementById('addExpenseModal').style.display='flex'">
        <i class="fas fa-plus"></i> إضافة مصروف جديد
      </button>
      <?php endif; ?>
    </div>
  </div>

  <div class="card">
    <div class="table-wrapper">
      <table class="data-table">
        <thead>
          <tr>
            <th>#</th>
            <th>التاريخ</th>
            <th>بند المصروف</th>
            <th>القيمة</th>
            <th>التصنيف</th>
            <th>ملاحظات</th>
            <th>أضيف بواسطة</th>
            <th>إجراء</th>
          </tr>
        </thead>
        <tbody>
          <!-- Dynamic body load -->
        </tbody>
        <tfoot class="table-totals">
          <!-- Dynamic totals load -->
        </tfoot>
      </table>
    </div>
    <div class="card-footer" id="expensesFooter">
      <!-- Pagination will be handled dynamically -->
    </div>
  </div>

  <!-- Modal إضافة مصروف -->
  <div class="modal-overlay" id="addExpenseModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center;">
    <div class="modal-box card" style="width:100%; max-width:500px; padding:24px; border-radius:12px; background:#fff; position:relative;">
      <h3 style="margin-top:0; margin-bottom:20px; font-size:18px; font-weight:700; border-bottom:1px solid #f1f5f9; padding-bottom:12px;">
        <i class="fas fa-plus" style="margin-left:8px; color:var(--success);"></i>إضافة مصروف جديد
      </h3>
      <form method="POST" action="?tab=expenses">
        <input type="hidden" name="add_expense" value="1">
        
        <div class="form-group" style="margin-bottom:16px;">
          <label class="form-label" style="display:block; margin-bottom:8px; font-weight:700;">بند المصروف <span style="color:var(--danger)">*</span></label>
          <input type="text" name="title" class="form-control" required placeholder="مثال: تجديد سيرفر الاستضافة">
        </div>
        
        <div class="row" style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:16px;">
          <div class="form-group">
            <label class="form-label" style="display:block; margin-bottom:8px; font-weight:700;">القيمة (بالجنيه) <span style="color:var(--danger)">*</span></label>
            <input type="number" step="0.01" name="amount" class="form-control" required placeholder="0.00">
          </div>
          <div class="form-group">
            <label class="form-label" style="display:block; margin-bottom:8px; font-weight:700;">التاريخ <span style="color:var(--danger)">*</span></label>
            <input type="date" name="expense_date" class="form-control" required value="<?= date('Y-m-d') ?>">
          </div>
        </div>
        
        <div class="form-group" style="margin-bottom:16px;">
          <label class="form-label" style="display:block; margin-bottom:8px; font-weight:700;">التصنيف <span style="color:var(--danger)">*</span></label>
          <select name="category" class="form-control" required>
            <option value="دومين">دومينات</option>
            <option value="سيرفر">سيرفرات واستضافات</option>
            <option value="إعلانات">إعلانات وتسويق</option>
            <option value="موظفين">رواتب وموظفين</option>
            <option value="أخرى">مصاريف أخرى</option>
          </select>
        </div>
        
        <div class="form-group" style="margin-bottom:20px;">
          <label class="form-label" style="display:block; margin-bottom:8px; font-weight:700;">ملاحظات إضافية</label>
          <textarea name="notes" class="form-control" rows="3" placeholder="تفاصيل المصروف..."></textarea>
        </div>
        
        <div style="display:flex; justify-content:flex-end; gap:12px; border-top:1px solid #f1f5f9; padding-top:16px;">
          <button type="button" class="btn btn-outline" onclick="document.getElementById('addExpenseModal').style.display='none'">إلغاء</button>
          <button type="submit" class="btn btn-success">حفظ المصروف</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Modal تعديل مصروف -->
  <div class="modal-overlay" id="editExpenseModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center;">
    <div class="modal-box card" style="width:100%; max-width:500px; padding:24px; border-radius:12px; background:#fff; position:relative;">
      <h3 style="margin-top:0; margin-bottom:20px; font-size:18px; font-weight:700; border-bottom:1px solid #f1f5f9; padding-bottom:12px;">
        <i class="fas fa-edit" style="margin-left:8px; color:var(--primary-light);"></i>تعديل مصروف
      </h3>
      <form method="POST" action="?tab=expenses">
        <input type="hidden" name="edit_expense" value="1">
        <input type="hidden" name="expense_id" id="edit_expense_id">
        
        <div class="form-group" style="margin-bottom:16px;">
          <label class="form-label" style="display:block; margin-bottom:8px; font-weight:700;">بند المصروف <span style="color:var(--danger)">*</span></label>
          <input type="text" name="title" id="edit_expense_title" class="form-control" required placeholder="مثال: تجديد سيرفر الاستضافة">
        </div>
        
        <div class="row" style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:16px;">
          <div class="form-group">
            <label class="form-label" style="display:block; margin-bottom:8px; font-weight:700;">القيمة (بالجنيه) <span style="color:var(--danger)">*</span></label>
            <input type="number" step="0.01" name="amount" id="edit_expense_amount" class="form-control" required placeholder="0.00">
          </div>
          <div class="form-group">
            <label class="form-label" style="display:block; margin-bottom:8px; font-weight:700;">التاريخ <span style="color:var(--danger)">*</span></label>
            <input type="date" name="expense_date" id="edit_expense_date" class="form-control" required>
          </div>
        </div>
        
        <div class="form-group" style="margin-bottom:16px;">
          <label class="form-label" style="display:block; margin-bottom:8px; font-weight:700;">التصنيف <span style="color:var(--danger)">*</span></label>
          <select name="category" id="edit_expense_category" class="form-control" required>
            <option value="دومين">دومينات</option>
            <option value="سيرفر">سيرفرات واستضافات</option>
            <option value="إعلانات">إعلانات وتسويق</option>
            <option value="موظفين">رواتب وموظفين</option>
            <option value="أخرى">مصاريف أخرى</option>
          </select>
        </div>
        
        <div class="form-group" style="margin-bottom:20px;">
          <label class="form-label" style="display:block; margin-bottom:8px; font-weight:700;">ملاحظات إضافية</label>
          <textarea name="notes" id="edit_expense_notes" class="form-control" rows="3" placeholder="تفاصيل المصروف..."></textarea>
        </div>
        
        <div style="display:flex; justify-content:flex-end; gap:12px; border-top:1px solid #f1f5f9; padding-top:16px;">
          <button type="button" class="btn btn-outline" onclick="document.getElementById('editExpenseModal').style.display='none'">إلغاء</button>
          <button type="submit" class="btn btn-primary">حفظ التغييرات</button>
        </div>
      </form>
    </div>
  </div>

  <script>
  function openEditExpenseModal(exp) {
      document.getElementById('edit_expense_id').value = exp.id;
      document.getElementById('edit_expense_title').value = exp.title;
      document.getElementById('edit_expense_amount').value = exp.amount;
      
      const dateEl = document.getElementById('edit_expense_date');
      if (dateEl._flatpickr) {
          dateEl._flatpickr.setDate(exp.expense_date);
      } else {
          dateEl.value = exp.expense_date;
      }
      
      document.getElementById('edit_expense_category').value = exp.category;
      document.getElementById('edit_expense_notes').value = exp.notes || '';
      document.getElementById('editExpenseModal').style.display = 'flex';
  }
  </script>

  <script>
  document.addEventListener('DOMContentLoaded', function() {
      const searchInput = document.querySelector('#expensesFilterForm input[name="search"]');
      const categorySelect = document.querySelector('#expensesFilterForm select[name="category"]');
      const dateFromInput = document.querySelector('#expensesFilterForm input[name="date_from"]');
      const dateToInput = document.querySelector('#expensesFilterForm input[name="date_to"]');
      const expensesFilterForm = document.getElementById('expensesFilterForm');
      const tbody = document.querySelector('.data-table tbody');
      const tfoot = document.querySelector('.table-totals');
      const cardFooter = document.getElementById('expensesFooter');
      const subtitle = document.querySelector('.page-subtitle');
      const clearSearchBtn = document.getElementById('expensesClearBtn');
      
      let debounceTimer;
      let currentPage = 1;

      const quickFilters = document.getElementById('expensesQuickDateFilters');
      const customWrapper = document.getElementById('expensesCustomWrapper');

      const formatDate = (date) => {
          const y = date.getFullYear();
          const m = String(date.getMonth() + 1).padStart(2, '0');
          const d = String(date.getDate()).padStart(2, '0');
          return `${y}-${m}-${d}`;
      };

      function updateActiveFilter(range) {
          document.querySelectorAll('#expensesQuickDateFilters button').forEach(btn => {
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

      const todayStr = formatDate(new Date());
      const firstDayMonth = formatDate(new Date(new Date().getFullYear(), new Date().getMonth(), 1));
      const lastDayMonth = formatDate(new Date(new Date().getFullYear(), new Date().getMonth() + 1, 0));
      const firstDayYear = formatDate(new Date(new Date().getFullYear(), 0, 1));
      const lastDayYear = formatDate(new Date(new Date().getFullYear(), 11, 31));

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

      if (expensesFilterForm) {
          expensesFilterForm.addEventListener('submit', function(e) {
              e.preventDefault();
              doSearch(1);
          });
      }

      function doSearch(page = 1) {
          currentPage = page;
          const searchQuery = searchInput.value;
          const catQuery = categorySelect.value;
          const dateFromQuery = dateFromInput.value;
          const dateToQuery = dateToInput.value;

          const params = new URLSearchParams({
              tab: 'expenses',
              search: searchQuery,
              category: catQuery,
              date_from: dateFromQuery,
              date_to: dateToQuery,
              page: currentPage,
              ajax: 1
          });

          // Update URL
          const cleanParams = new URLSearchParams({
              tab: 'expenses',
              search: searchQuery,
              category: catQuery,
              date_from: dateFromQuery,
              date_to: dateToQuery,
              page: currentPage
          });
          if (!searchQuery) cleanParams.delete('search');
          if (!catQuery) cleanParams.delete('category');
          if (!dateFromQuery) cleanParams.delete('date_from');
          if (!dateToQuery) cleanParams.delete('date_to');
          if (currentPage === 1) cleanParams.delete('page');
          
          const newUrl = window.location.pathname + (cleanParams.toString() ? '?' + cleanParams.toString() : '');
          window.history.replaceState({path: newUrl}, '', newUrl);

          if (clearSearchBtn) {
              if (searchQuery || catQuery || dateFromQuery || dateToQuery) {
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

      if (categorySelect) {
          categorySelect.addEventListener('change', () => doSearch(1));
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
      
      // trigger search initially to load correct pagination footer state
      doSearch(<?= $page ?>);
  });
  </script>
  <?php endif; ?>

</div>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
