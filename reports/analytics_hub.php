<?php
/**
 * reports/analytics_hub.php - مركز تحليلات الأداء والنمو المتكامل (معاينة تجريبية)
 * يجمع بين: إحصائيات ونمو الخدمات + ولاء وبقاء العملاء + تدفق المشتركين الشهري مع الربط التفاعلي
 * نظام إنجاز للحلول الذكية
 */
require_once dirname(__DIR__) . '/config/app.php';
requireLogin();
requirePermission('view_reports');

$db = getDB();
$activeTab = clean($_GET['tab'] ?? 'services');
if (!in_array($activeTab, ['services', 'loyalty', 'intake'])) {
    $activeTab = 'services';
}

$year          = (int)($_GET['year'] ?? date('Y'));
$selectedMonth = isset($_GET['month']) && $_GET['month'] !== '' ? (int)$_GET['month'] : null;
$clientStatus  = clean($_GET['client_status'] ?? 'active');
$country       = clean($_GET['country'] ?? '');
$search        = clean($_GET['search'] ?? '');

$arabicMonths = [
    1=>'يناير',2=>'فبراير',3=>'مارس',4=>'أبريل',5=>'مايو',6=>'يونيو',
    7=>'يوليو',8=>'أغسطس',9=>'سبتمبر',10=>'أكتوبر',11=>'نوفمبر',12=>'ديسمبر',
];

// ─────────────────────────────────────────────────────────────────
// معالج الـ AJAX للتفاصيل الفورية لأي خدمة وشهر
// ─────────────────────────────────────────────────────────────────
$detailMonth   = isset($_GET['detail_month']) ? (int)$_GET['detail_month'] : null;
$detailService = isset($_GET['detail_service']) ? (int)$_GET['detail_service'] : null;

if ($detailMonth && isset($_GET['ajax'])) {
    $where = ["MONTH(cs.start_date) = ?", "YEAR(cs.start_date) = ?", "cs.status != 'cancelled'", "cs.start_date IS NOT NULL"];
    $params = [$detailMonth, $year];

    if ($clientStatus === 'active') {
        $where[] = "c.status = 1";
    } elseif ($clientStatus === 'suspended') {
        $where[] = "c.status = 0";
    }
    if (!empty($country)) {
        $where[] = "c.country = ?";
        $params[] = $country;
    }
    if ($detailService) {
        $where[] = "cs.service_id = ?";
        $params[] = $detailService;
    }

    $dStmt = $db->prepare("
        SELECT c.id, c.name, c.company_name, c.mobile, c.country, c.status as client_status,
               cs.plan_name, cs.price, cs.currency, cs.start_date, cs.end_date, cs.status as sub_status,
               s.name as service_name
        FROM client_subscriptions cs 
        JOIN clients c ON c.id = cs.client_id 
        JOIN services s ON s.id = cs.service_id
        WHERE " . implode(' AND ', $where) . " 
        ORDER BY cs.start_date ASC
    ");
    $dStmt->execute($params);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success'    => true,
        'clients'    => $dStmt->fetchAll(PDO::FETCH_ASSOC),
        'month_name' => $arabicMonths[$detailMonth] ?? "شهر {$detailMonth}",
        'count'      => $dStmt->rowCount()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ─────────────────────────────────────────────────────────────────
// التجهيزات المشتركة للأمان والفلترة
// ─────────────────────────────────────────────────────────────────
function buildFilterWhere(string $alias = 'c'): array {
    global $clientStatus, $country, $db;
    $w = [];
    if ($clientStatus === 'active') {
        $w[] = "{$alias}.status = 1";
    } elseif ($clientStatus === 'suspended') {
        $w[] = "{$alias}.status = 0";
    }
    if (!empty($country)) {
        $w[] = "{$alias}.country = " . $db->quote($country);
    }
    return $w;
}

// ─────────────────────────────────────────────────────────────────
// 1. بيانات تبويب: إحصائيات ونمو الخدمات (services)
// ─────────────────────────────────────────────────────────────────
$servicesList = [];
$subsByMonthService = [];
$paymentsByMonth = array_fill(1, 12, 0);
$expensesByMonth = array_fill(1, 12, 0);
$serviceTotals = [];
$yearTotalRevenue = 0;
$yearTotalCollected = 0;
$yearTotalExpenses = 0;
$yearNetProfit = 0;

if ($activeTab === 'services') {
    try {
        $servicesStmt = $db->query("SELECT id, name FROM services WHERE status = 1 ORDER BY sort_order ASC, id ASC");
        $servicesList = $servicesStmt->fetchAll(PDO::FETCH_ASSOC);

        $filterW = buildFilterWhere('c');
        $extraSql = !empty($filterW) ? ' AND ' . implode(' AND ', $filterW) : '';

        // الاشتراكات مجمعة شهرياً
        $subsStmt = $db->prepare("
            SELECT MONTH(cs.start_date) AS month, cs.service_id,
                   COUNT(DISTINCT cs.client_id) AS clients_count, SUM(cs.price) AS revenue
            FROM client_subscriptions cs
            JOIN clients c ON c.id = cs.client_id
            WHERE YEAR(cs.start_date) = ? AND cs.status != 'cancelled' AND cs.start_date IS NOT NULL {$extraSql}
            GROUP BY MONTH(cs.start_date), cs.service_id
        ");
        $subsStmt->execute([$year]);
        foreach ($subsStmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $m = (int)$r['month'];
            $sid = (int)$r['service_id'];
            $subsByMonthService[$m][$sid] = [
                'clients_count' => (int)$r['clients_count'],
                'revenue'       => (float)$r['revenue']
            ];
        }

        // المدفوعات شهرياً
        if (!empty($country)) {
            $payStmt = $db->prepare("SELECT MONTH(p.payment_date) AS month, SUM(p.amount) AS collected FROM payments p JOIN clients c ON c.id = p.client_id WHERE YEAR(p.payment_date) = ? AND c.country = ? GROUP BY MONTH(p.payment_date)");
            $payStmt->execute([$year, $country]);
        } else {
            $payStmt = $db->prepare("SELECT MONTH(payment_date) AS month, SUM(amount) AS collected FROM payments WHERE YEAR(payment_date) = ? GROUP BY MONTH(payment_date)");
            $payStmt->execute([$year]);
        }
        foreach ($payStmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $paymentsByMonth[(int)$r['month']] = (float)$r['collected'];
        }

        // المصروفات شهرياً
        $expStmt = $db->prepare("SELECT MONTH(expense_date) AS month, SUM(amount) AS total FROM expenses WHERE YEAR(expense_date) = ? GROUP BY MONTH(expense_date)");
        $expStmt->execute([$year]);
        foreach ($expStmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $expensesByMonth[(int)$r['month']] = (float)$r['total'];
        }

        // إجماليات كل خدمة في العام
        $svcTotStmt = $db->prepare("
            SELECT cs.service_id, COUNT(DISTINCT cs.client_id) AS total_clients,
                   COUNT(DISTINCT CASE WHEN c.status = 1 THEN cs.client_id END) AS active_clients,
                   SUM(cs.price) AS total_revenue
            FROM client_subscriptions cs
            JOIN clients c ON c.id = cs.client_id
            WHERE YEAR(cs.start_date) = ? AND cs.status != 'cancelled' AND cs.start_date IS NOT NULL {$extraSql}
            GROUP BY cs.service_id
        ");
        $svcTotStmt->execute([$year]);
        foreach ($svcTotStmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $serviceTotals[(int)$r['service_id']] = $r;
        }

        $allSubsRevStmt = $db->prepare("SELECT SUM(cs.price) FROM client_subscriptions cs JOIN clients c ON c.id = cs.client_id WHERE YEAR(cs.start_date) = ? AND cs.status != 'cancelled' AND cs.start_date IS NOT NULL {$extraSql}");
        $allSubsRevStmt->execute([$year]);
        $yearTotalRevenue = (float)$allSubsRevStmt->fetchColumn();

        $yearTotalCollected = array_sum($paymentsByMonth);
        $yearTotalExpenses  = array_sum($expensesByMonth);
        $yearNetProfit      = $yearTotalCollected - $yearTotalExpenses;

    } catch (\Throwable $e) {}
}

// ─────────────────────────────────────────────────────────────────
// 2. بيانات تبويب: ولاء وبقاء العملاء (loyalty)
// ─────────────────────────────────────────────────────────────────
$loyaltyClients = [];
$loyaltyStats = [
    'total_clients'    => 0,
    'renewed_count'    => 0,
    'needs_renewal'    => 0,
    'expiring_soon'    => 0,
    'retention_rate'   => 0
];

if ($activeTab === 'loyalty') {
    try {
        $filterW = buildFilterWhere('c');
        if (!empty($search)) {
            $filterW[] = "(c.name LIKE " . $db->quote("%$search%") . " OR c.company_name LIKE " . $db->quote("%$search%") . " OR c.mobile LIKE " . $db->quote("%$search%") . ")";
        }
        $whereSql = !empty($filterW) ? ' WHERE ' . implode(' AND ', $filterW) : '';

        $clientsQuery = $db->query("
            SELECT c.id, c.name, c.company_name, c.mobile, c.country, c.status as client_status, c.created_at,
                   (SELECT MIN(start_date) FROM client_subscriptions WHERE client_id = c.id AND status != 'cancelled') as first_sub_date,
                   (SELECT MAX(end_date) FROM client_subscriptions WHERE client_id = c.id AND status != 'cancelled') as last_end_date,
                   (SELECT COUNT(*) FROM client_subscriptions WHERE client_id = c.id AND status != 'cancelled') as subs_count,
                   (SELECT SUM(amount) FROM payments WHERE client_id = c.id) as total_payments
            FROM clients c
            {$whereSql}
            ORDER BY first_sub_date ASC, c.created_at DESC
        ");
        $rawClients = $clientsQuery->fetchAll(PDO::FETCH_ASSOC);

        $renewedCount = 0;
        $needsRenewalCount = 0;
        $expiringSoonCount = 0;

        foreach ($rawClients as $cl) {
            $firstDate = $cl['first_sub_date'] ? new DateTime($cl['first_sub_date']) : new DateTime($cl['created_at']);
            $now = new DateTime();
            $diff = $firstDate->diff($now);
            $monthsWithUs = ($diff->y * 12) + $diff->m;

            $statusLabel = 'مشترك جديد';
            if ($cl['subs_count'] > 1 || $monthsWithUs >= 12) {
                $statusLabel = 'عميل مجدد وفِي';
                $renewedCount++;
            }

            if ($cl['last_end_date']) {
                $endDate = new DateTime($cl['last_end_date']);
                $daysDiff = (int)$now->diff($endDate)->format('%r%a');
                if ($daysDiff < 0) {
                    $statusLabel = 'يحتاج تجديد (منتهي)';
                    $needsRenewalCount++;
                } elseif ($daysDiff <= 30) {
                    $statusLabel = 'ينتهي قريباً';
                    $expiringSoonCount++;
                }
            }

            $cl['months_with_us'] = $monthsWithUs;
            $cl['loyalty_status'] = $statusLabel;
            $cl['first_date_formatted'] = $firstDate->format('Y-m-d');
            $loyaltyClients[] = $cl;
        }

        $totalC = count($loyaltyClients);
        $loyaltyStats['total_clients']  = $totalC;
        $loyaltyStats['renewed_count']  = $renewedCount;
        $loyaltyStats['needs_renewal']  = $needsRenewalCount;
        $loyaltyStats['expiring_soon']  = $expiringSoonCount;
        $loyaltyStats['retention_rate'] = $totalC > 0 ? round(($renewedCount / $totalC) * 100, 1) : 0;

    } catch (\Throwable $e) {}
}

// ─────────────────────────────────────────────────────────────────
// 3. بيانات تبويب: تدفق المشتركين الجدد شهرياً (intake)
// ─────────────────────────────────────────────────────────────────
$intakeReport = [];
$monthStats = [
    'clients_count' => 0,
    'total_domain'  => 0,
    'total_email'   => 0,
    'total_web'     => 0,
    'grand_total'   => 0
];

if ($activeTab === 'intake') {
    try {
        $filterW = ["YEAR(cs.start_date) = ?", "cs.status != 'cancelled'"];
        $paramsI = [$year];

        if ($clientStatus === 'active') {
            $filterW[] = "c.status = 1";
        } elseif ($clientStatus === 'suspended') {
            $filterW[] = "c.status = 0";
        }
        if ($selectedMonth) {
            $filterW[] = "MONTH(cs.start_date) = ?";
            $paramsI[] = $selectedMonth;
        }

        $whereISql = implode(' AND ', $filterW);
        $intakeStmt = $db->prepare("
            SELECT 
                c.id as client_id, c.name as client_name, c.company_name, c.status as client_status, c.mobile, c.domain as client_domain,
                MONTH(cs.start_date) as start_month,
                COALESCE(SUM(CASE WHEN cs.service_id = 2 THEN cs.price END), 0) as domain_cost,
                COALESCE(SUM(CASE WHEN cs.service_id = 1 THEN cs.price END), 0) as email_cost,
                COALESCE(SUM(CASE WHEN cs.service_id = 3 THEN cs.price END), 0) as website_cost,
                SUM(cs.price) as total_cost,
                MIN(cs.start_date) as min_start_date
            FROM client_subscriptions cs
            JOIN clients c ON c.id = cs.client_id
            WHERE {$whereISql}
            GROUP BY c.id, MONTH(cs.start_date)
            ORDER BY min_start_date DESC
        ");
        $intakeStmt->execute($paramsI);
        $intakeReport = $intakeStmt->fetchAll(PDO::FETCH_ASSOC);

        $monthStats['clients_count'] = count($intakeReport);
        $monthStats['total_domain']  = array_sum(array_column($intakeReport, 'domain_cost'));
        $monthStats['total_email']   = array_sum(array_column($intakeReport, 'email_cost'));
        $monthStats['total_web']     = array_sum(array_column($intakeReport, 'website_cost'));
        $monthStats['grand_total']   = array_sum(array_column($intakeReport, 'total_cost'));

    } catch (\Throwable $e) {}
}

// ضبط العمق والهيدر
$depth = 1;
$pageTitle = 'مركز تحليلات الأداء والنمو (معاينة تجريبية)';
$activePage = 'analytics-hub';
require_once INCLUDES_PATH . '/header.php';
?>

<style>
/* تنسيق الجداول لتمتد 100% بدون انكماش */
.table-wrapper {
  width: 100% !important;
  overflow-x: auto;
  border-radius: 10px;
  background: var(--bg-card);
  margin-top: 10px;
}
table.data-table {
  width: 100% !important;
  border-collapse: collapse;
  font-size: 13px;
}
table.data-table thead th {
  background: #f8fafc;
  padding: 12px 14px;
  text-align: right;
  font-weight: 700;
  color: var(--text-secondary);
  border-bottom: 2px solid var(--border-color);
  white-space: nowrap;
}
table.data-table tbody td {
  padding: 12px 14px;
  vertical-align: middle;
  border-bottom: 1px solid var(--border-color);
  text-align: right;
}
table.data-table tbody tr:hover {
  background: #f8fbff;
}

/* شريط التبويبات */
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
  color: #0d9488;
  background: rgba(13, 148, 136, 0.05);
}
.hub-tab-link.active {
  color: #0d9488;
  background: var(--bg-card);
  border-bottom-color: #0d9488;
}

/* بطاقات الإحصائيات KPI */
.analytics-kpi-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
  gap: 14px;
  margin-bottom: 22px;
}
.analytics-kpi-card {
  background: var(--bg-card);
  border: 1px solid var(--border-color);
  border-radius: 12px;
  padding: 18px;
  display: flex;
  align-items: center;
  gap: 14px;
}
.analytics-kpi-icon {
  width: 46px;
  height: 46px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 20px;
}

/* زر أو شارة الخلية التفاعلية */
.interactive-pill {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  background: #e0f2fe;
  color: #0369a1;
  padding: 4px 10px;
  border-radius: 20px;
  font-size: 11.5px;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.15s ease;
  border: 1px solid #bae6fd;
}
.interactive-pill:hover {
  background: #0284c7;
  color: #fff;
  transform: translateY(-1px);
  box-shadow: 0 2px 8px rgba(2, 132, 199, 0.2);
}

/* نافذة المودال المنبثقة للتفاصيل الفورية */
.drill-modal {
  display: none;
  position: fixed;
  z-index: 9999;
  left: 0; top: 0;
  width: 100%; height: 100%;
  background: rgba(0,0,0,0.55);
  backdrop-filter: blur(4px);
  align-items: center;
  justify-content: center;
}
.drill-modal.open { display: flex; }
.drill-modal-content {
  background: var(--bg-card);
  border-radius: 14px;
  max-width: 750px;
  width: 92%;
  padding: 24px;
  box-shadow: 0 12px 35px rgba(0,0,0,0.25);
  max-height: 85vh;
  display: flex;
  flex-direction: column;
}
</style>

<div class="page-header" style="margin-bottom: 16px;">
  <div class="page-title">
    <i class="fas fa-chart-pie" style="color: #0d9488;"></i>
    <span>مركز تحليلات الأداء والنمو</span>
    <span class="badge" style="background: #fef08a; color: #854d0e; font-size: 11px;">معاينة تجريبية Side-by-Side</span>
  </div>
  <div class="page-actions" style="display: flex; gap: 8px; align-items: center;">
    <!-- محدد السنة مع الأسهم -->
    <div style="display: flex; align-items: center; background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 8px; padding: 4px 10px; font-weight: 700;">
      <a href="?tab=<?= $activeTab ?>&year=<?= $year - 1 ?><?= $selectedMonth ? "&month={$selectedMonth}" : '' ?>" style="color: var(--text-muted); text-decoration: none; padding: 0 4px;" title="العام السابق"><i class="fas fa-chevron-right"></i></a>
      <span style="margin: 0 8px;"><?= $year ?></span>
      <a href="?tab=<?= $activeTab ?>&year=<?= $year + 1 ?><?= $selectedMonth ? "&month={$selectedMonth}" : '' ?>" style="color: var(--text-muted); text-decoration: none; padding: 0 4px;" title="العام التالي"><i class="fas fa-chevron-left"></i></a>
    </div>

    <a href="services-stats.php" class="btn btn-sm btn-outline" style="color: var(--text-muted);" title="فتح صفحة إحصائيات الخدمات القديمة">
      الصفحة القديمة <i class="fas fa-external-link-alt"></i>
    </a>
  </div>
</div>

<?= renderFlash() ?>

<!-- شريط التبويبات الثلاثة -->
<div class="hub-nav-tabs">
  <a href="analytics_hub.php?tab=services&year=<?= $year ?>" class="hub-tab-link <?= $activeTab === 'services' ? 'active' : '' ?>">
    <i class="fas fa-chart-line"></i>
    <span>إحصائيات ونمو مبيعات الخدمات</span>
  </a>

  <a href="analytics_hub.php?tab=loyalty" class="hub-tab-link <?= $activeTab === 'loyalty' ? 'active' : '' ?>">
    <i class="fas fa-handshake"></i>
    <span>تحليل ولاء وبقاء وتجديد العملاء</span>
  </a>

  <a href="analytics_hub.php?tab=intake&year=<?= $year ?><?= $selectedMonth ? "&month={$selectedMonth}" : '' ?>" class="hub-tab-link <?= $activeTab === 'intake' ? 'active' : '' ?>">
    <i class="fas fa-user-plus"></i>
    <span>تدفق المشتركين الجدد شهرياً</span>
    <?php if ($selectedMonth): ?>
      <span class="badge" style="background: #ccfbf1; color: #0f766e;"><?= $arabicMonths[$selectedMonth] ?></span>
    <?php endif; ?>
  </a>
</div>

<!-- ───────────────────────────────────────────────────────────── -->
<!-- محتوى التبويب 1: إحصائيات ونمو مبيعات الخدمات -->
<!-- ───────────────────────────────────────────────────────────── -->
<?php if ($activeTab === 'services'): ?>
  <div class="analytics-kpi-grid">
    <div class="analytics-kpi-card">
      <div class="analytics-kpi-icon" style="background: rgba(13, 148, 136, 0.15); color: #0d9488;">
        <i class="fas fa-file-invoice-dollar"></i>
      </div>
      <div>
        <div style="font-size: 12px; color: var(--text-muted); font-weight: 600;">إجمالي إيراد الخدمات (<?= $year ?>)</div>
        <div style="font-size: 19px; font-weight: 800; color: #0d9488;"><?= number_format($yearTotalRevenue) ?> ج.م</div>
      </div>
    </div>

    <div class="analytics-kpi-card">
      <div class="analytics-kpi-icon" style="background: rgba(16, 185, 129, 0.15); color: #10b981;">
        <i class="fas fa-hand-holding-usd"></i>
      </div>
      <div>
        <div style="font-size: 12px; color: var(--text-muted); font-weight: 600;">المقبوضات المحصلة</div>
        <div style="font-size: 19px; font-weight: 800; color: #10b981;"><?= number_format($yearTotalCollected) ?> ج.م</div>
      </div>
    </div>

    <div class="analytics-kpi-card">
      <div class="analytics-kpi-icon" style="background: rgba(239, 68, 68, 0.15); color: #ef4444;">
        <i class="fas fa-receipt"></i>
      </div>
      <div>
        <div style="font-size: 12px; color: var(--text-muted); font-weight: 600;">المصروفات المسجلة</div>
        <div style="font-size: 19px; font-weight: 800; color: #ef4444;"><?= number_format($yearTotalExpenses) ?> ج.م</div>
      </div>
    </div>

    <div class="analytics-kpi-card">
      <div class="analytics-kpi-icon" style="background: rgba(59, 130, 246, 0.15); color: #3b82f6;">
        <i class="fas fa-balance-scale"></i>
      </div>
      <div>
        <div style="font-size: 12px; color: var(--text-muted); font-weight: 600;">صافي الأرباح التشغيلية</div>
        <div style="font-size: 19px; font-weight: 800; color: <?= $yearNetProfit >= 0 ? '#10b981' : '#ef4444' ?>;">
          <?= number_format($yearNetProfit) ?> ج.م
        </div>
      </div>
    </div>
  </div>

  <!-- رسم بياني Chart.js لحركة الإيرادات والتحصيل شهرياً -->
  <div class="card" style="margin-bottom: 20px;">
    <h3 style="margin: 0 0 16px 0; font-size: 15px;"><i class="fas fa-chart-area" style="color: #0d9488;"></i> منحنى الإيرادات والمصروفات خلال عام <?= $year ?></h3>
    <div style="height: 250px;">
      <canvas id="analyticsChart"></canvas>
    </div>
  </div>

  <!-- جدول مصفوفة الخدمات مع إمكانية الدخول على كل شهر أو خدمة -->
  <div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; flex-wrap: wrap; gap: 8px;">
      <h3 style="margin: 0; font-size: 15px;">
        <i class="fas fa-th-list"></i> مصفوفة الشهور والخدمات لعام <?= $year ?>
      </h3>
      <div style="font-size: 12px; color: var(--text-muted);">
        <i class="fas fa-info-circle" style="color: #0d9488;"></i> اضغط على اسم أي شهر للدخول إلى تقريره التفصيلي، أو اضغط على أرقام الخدمات لمعاينة عملائها فورياً.
      </div>
    </div>

    <div class="table-wrapper">
      <table class="data-table">
        <thead>
          <tr>
            <th style="width: 100px;">الشهر</th>
            <?php foreach ($servicesList as $svc): ?>
              <th><?= e($svc['name']) ?></th>
            <?php endforeach; ?>
            <th style="width: 120px;">إجمالي التحصيل</th>
            <th style="width: 110px;">المصروفات</th>
            <th style="width: 130px; text-align: center;">إجراء سريع</th>
          </tr>
        </thead>
        <tbody>
          <?php for ($m = 1; $m <= 12; $m++): ?>
            <tr>
              <td>
                <a href="analytics_hub.php?tab=intake&year=<?= $year ?>&month=<?= $m ?>" style="font-weight: 800; color: #0d9488; text-decoration: none; display: inline-flex; align-items: center; gap: 4px;" title="عرض تقرير هذا الشهر بالكامل">
                  <?= $arabicMonths[$m] ?> <i class="fas fa-arrow-left" style="font-size: 10px;"></i>
                </a>
              </td>
              <?php foreach ($servicesList as $svc): 
                $cell = $subsByMonthService[$m][$svc['id']] ?? null;
              ?>
                <td>
                  <?php if ($cell && $cell['clients_count'] > 0): ?>
                    <span class="interactive-pill" onclick="openDrillModal(<?= $m ?>, <?= $svc['id'] ?>, '<?= e($arabicMonths[$m]) ?>', '<?= e($svc['name']) ?>')" title="انقر لعرض قائمة المشتركين">
                      <i class="fas fa-users" style="font-size: 10px;"></i>
                      <?= $cell['clients_count'] ?> عميل (<?= number_format($cell['revenue']) ?>)
                    </span>
                  <?php else: ?>
                    <span style="color: var(--text-muted); opacity: 0.35;">—</span>
                  <?php endif; ?>
                </td>
              <?php endforeach; ?>
              <td><strong style="color: #10b981;"><?= number_format($paymentsByMonth[$m] ?? 0) ?></strong></td>
              <td><span style="color: #ef4444;"><?= number_format($expensesByMonth[$m] ?? 0) ?></span></td>
              <td style="text-align: center;">
                <a href="analytics_hub.php?tab=intake&year=<?= $year ?>&month=<?= $m ?>" class="btn btn-sm btn-outline" style="font-size: 11.5px; padding: 3px 8px;" title="فتح تقرير المشتركين لهذا الشهر">
                  <i class="fas fa-list"></i> المشتركين
                </a>
              </td>
            </tr>
          <?php endfor; ?>
        </tbody>
      </table>
    </div>
  </div>

  <script>
  document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('analyticsChart');
    if (ctx) {
      new Chart(ctx, {
        type: 'line',
        data: {
          labels: <?= json_encode(array_values($arabicMonths), JSON_UNESCAPED_UNICODE) ?>,
          datasets: [
            {
              label: 'المقبوضات المحصلة',
              data: <?= json_encode(array_values($paymentsByMonth)) ?>,
              borderColor: '#10b981',
              backgroundColor: 'rgba(16, 185, 129, 0.1)',
              tension: 0.3,
              fill: true
            },
            {
              label: 'المصروفات',
              data: <?= json_encode(array_values($expensesByMonth)) ?>,
              borderColor: '#ef4444',
              backgroundColor: 'rgba(239, 68, 68, 0.05)',
              tension: 0.3,
              fill: true
            }
          ]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: { position: 'top', rtl: true, labels: { font: { family: 'Cairo' } } }
          },
          scales: {
            y: { beginAtZero: true }
          }
        }
      });
    }
  });
  </script>

<!-- ───────────────────────────────────────────────────────────── -->
<!-- محتوى التبويب 2: تحليل ولاء وبقاء العملاء -->
<!-- ───────────────────────────────────────────────────────────── -->
<?php elseif ($activeTab === 'loyalty'): ?>
  <div class="analytics-kpi-grid">
    <div class="analytics-kpi-card">
      <div class="analytics-kpi-icon" style="background: rgba(16, 185, 129, 0.15); color: #10b981;">
        <i class="fas fa-award"></i>
      </div>
      <div>
        <div style="font-size: 12px; color: var(--text-muted); font-weight: 600;">نسبة بقاء وتجديد العملاء</div>
        <div style="font-size: 22px; font-weight: 900; color: #10b981;"><?= $loyaltyStats['retention_rate'] ?>%</div>
      </div>
    </div>

    <div class="analytics-kpi-card">
      <div class="analytics-kpi-icon" style="background: rgba(59, 130, 246, 0.15); color: #3b82f6;">
        <i class="fas fa-redo"></i>
      </div>
      <div>
        <div style="font-size: 12px; color: var(--text-muted); font-weight: 600;">عملاء مجددون (وفاء عالي)</div>
        <div style="font-size: 20px; font-weight: 800; color: #3b82f6;"><?= $loyaltyStats['renewed_count'] ?> عميل</div>
      </div>
    </div>

    <div class="analytics-kpi-card">
      <div class="analytics-kpi-icon" style="background: rgba(245, 158, 11, 0.15); color: #f59e0b;">
        <i class="fas fa-hourglass-half"></i>
      </div>
      <div>
        <div style="font-size: 12px; color: var(--text-muted); font-weight: 600;">ينتهي قريباً (خلال 30 يوم)</div>
        <div style="font-size: 20px; font-weight: 800; color: #f59e0b;"><?= $loyaltyStats['expiring_soon'] ?> عميل</div>
      </div>
    </div>

    <div class="analytics-kpi-card">
      <div class="analytics-kpi-icon" style="background: rgba(239, 68, 68, 0.15); color: #ef4444;">
        <i class="fas fa-user-times"></i>
      </div>
      <div>
        <div style="font-size: 12px; color: var(--text-muted); font-weight: 600;">بحاجة لتجديد عاجل</div>
        <div style="font-size: 20px; font-weight: 800; color: #ef4444;"><?= $loyaltyStats['needs_renewal'] ?> عميل</div>
      </div>
    </div>
  </div>

  <!-- جدول كبار العملاء واستمراريتهم يمتد 100% -->
  <div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; flex-wrap: wrap; gap: 10px;">
      <div>
        <h3 style="margin: 0; font-size: 15px;"><i class="fas fa-crown" style="color: #f59e0b;"></i> تحليل أقدمية واستمرارية العملاء</h3>
        <p style="margin: 4px 0 0 0; font-size: 12px; color: var(--text-muted);">رتب العملاء حسب تاريخ أول اشتراك، إجمالي المبالغ المسددة، وتصنيف ولائهم للشركة.</p>
      </div>

      <form method="GET" action="analytics_hub.php" style="margin: 0; display: flex; gap: 8px;">
        <input type="hidden" name="tab" value="loyalty">
        <input type="text" name="search" class="form-control" style="font-size: 12px; padding: 5px 12px; width: 220px;" placeholder="بحث باسم العميل، الشركة، الهاتف..." value="<?= e($search) ?>">
        <button type="submit" class="btn btn-sm btn-primary">بحث</button>
      </form>
    </div>

    <div class="table-wrapper">
      <table class="data-table">
        <thead>
          <tr>
            <th>العميل</th>
            <th>تاريخ أول اشتراك</th>
            <th>المدة معنا</th>
            <th>عدد الاشتراكات</th>
            <th>إجمالي المدفوع</th>
            <th>تصنيف الولاء</th>
            <th style="text-align: center;">إجراءات</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach (array_slice($loyaltyClients, 0, 50) as $cl): ?>
            <tr>
              <td>
                <a href="../clients/view.php?id=<?= $cl['id'] ?>" style="font-weight: 800; color: var(--primary); text-decoration: none;">
                  <?= e($cl['name']) ?>
                </a>
                <?php if (!empty($cl['company_name'])): ?>
                  <div style="font-size: 11px; color: var(--text-muted);"><?= e($cl['company_name']) ?></div>
                <?php endif; ?>
              </td>
              <td><?= e($cl['first_date_formatted']) ?></td>
              <td>
                <span style="font-weight: 700; color: #0d9488;">
                  <?= $cl['months_with_us'] >= 12 ? round($cl['months_with_us'] / 12, 1) . ' سنة' : $cl['months_with_us'] . ' شهر' ?>
                </span>
              </td>
              <td><span class="badge" style="background: #f1f5f9; color: var(--text-primary); font-weight: 700;"><?= $cl['subs_count'] ?> خدمات</span></td>
              <td><strong style="color: #10b981;"><?= number_format((float)$cl['total_payments']) ?> ج.م</strong></td>
              <td>
                <?php if (strpos($cl['loyalty_status'], 'وفي') !== false): ?>
                  <span class="badge badge-success"><i class="fas fa-gem"></i> <?= $cl['loyalty_status'] ?></span>
                <?php elseif (strpos($cl['loyalty_status'], 'ينتهي') !== false): ?>
                  <span class="badge" style="background: #fef3c7; color: #92400e;"><?= $cl['loyalty_status'] ?></span>
                <?php elseif (strpos($cl['loyalty_status'], 'منتهي') !== false): ?>
                  <span class="badge badge-danger"><?= $cl['loyalty_status'] ?></span>
                <?php else: ?>
                  <span class="badge badge-secondary"><?= $cl['loyalty_status'] ?></span>
                <?php endif; ?>
              </td>
              <td style="text-align: center;">
                <div style="display: inline-flex; gap: 6px;">
                  <a href="client-statement.php?id=<?= $cl['id'] ?>" class="btn btn-sm btn-outline" style="font-size: 11px; padding: 2px 7px;" title="كشف الحساب المالي">
                    <i class="fas fa-file-invoice"></i> كشف
                  </a>
                  <a href="https://wa.me/<?= preg_replace('/\D/', '', $cl['mobile']) ?>" target="_blank" class="btn btn-sm btn-outline" style="color: #10b981; border-color: #10b981; font-size: 11px; padding: 2px 7px;" title="شات واتساب">
                    <i class="fab fa-whatsapp"></i>
                  </a>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

<!-- ───────────────────────────────────────────────────────────── -->
<!-- محتوى التبويب 3: تدفق المشتركين الجدد شهرياً مع شريط التنقل المتداخل -->
<!-- ───────────────────────────────────────────────────────────── -->
<?php elseif ($activeTab === 'intake'): ?>
  <!-- شريط تنقل الشهر المختار -->
  <div class="card" style="margin-bottom: 16px;">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
      <div>
        <?php if ($selectedMonth): ?>
          <div style="display: flex; align-items: center; gap: 10px;">
            <a href="analytics_hub.php?tab=intake&year=<?= $year ?>" class="btn btn-sm btn-outline" style="font-weight: 700;">
              <i class="fas fa-arrow-right"></i> عرض العام <?= $year ?> كاملاً
            </a>
            <h3 style="margin: 0; font-size: 17px; color: #0d9488;">
              <i class="fas fa-calendar-check"></i> تقرير مشتركي شهر: <strong><?= $arabicMonths[$selectedMonth] ?> <?= $year ?></strong>
            </h3>
          </div>
        <?php else: ?>
          <h3 style="margin: 0; font-size: 16px;"><i class="fas fa-calendar-alt"></i> المشتركون الجدد والخدمات خلال عام <?= $year ?> (جميع الشهور)</h3>
          <p style="margin: 4px 0 0 0; font-size: 12px; color: var(--text-muted);">اختر شهراً محدداً من القائمة أو انقر على أي شهر لمشاهدة تفاصيله.</p>
        <?php endif; ?>
      </div>

      <div style="display: flex; gap: 8px; align-items: center;">
        <?php if ($selectedMonth): ?>
          <!-- رابط سريع للمدفوعات في هذا الشهر بالمركز المالي -->
          <a href="financial-hub.php?tab=payments&year=<?= $year ?>&month=<?= $selectedMonth ?>" class="btn btn-sm btn-outline" style="color: #0284c7; border-color: #0284c7; font-weight: 700;">
            <i class="fas fa-wallet"></i> مدفوعات هذا الشهر بالمركز المالي
          </a>
        <?php endif; ?>

        <!-- محدد الشهر المنسدل -->
        <form method="GET" action="analytics_hub.php" style="margin: 0; display: flex; gap: 6px;">
          <input type="hidden" name="tab" value="intake">
          <input type="hidden" name="year" value="<?= $year ?>">
          <select name="month" class="form-control" style="font-size: 12.5px; padding: 5px 12px;" onchange="this.form.submit()">
            <option value="">كل الشهور (إجمالي العام)</option>
            <?php foreach ($arabicMonths as $mNum => $mName): ?>
              <option value="<?= $mNum ?>" <?= $selectedMonth === $mNum ? 'selected' : '' ?>><?= $mName ?></option>
            <?php endforeach; ?>
          </select>
        </form>
      </div>
    </div>
  </div>

  <?php if ($selectedMonth): ?>
    <!-- ملخص مالي للشهر المختار -->
    <div class="analytics-kpi-grid" style="margin-bottom: 16px;">
      <div class="analytics-kpi-card" style="padding: 14px;">
        <div class="analytics-kpi-icon" style="background: rgba(13, 148, 136, 0.15); color: #0d9488; width: 40px; height: 40px;">
          <i class="fas fa-users"></i>
        </div>
        <div>
          <div style="font-size: 11.5px; color: var(--text-muted);">مشتركين جدد في الشهر</div>
          <div style="font-size: 18px; font-weight: 800; color: #0d9488;"><?= $monthStats['clients_count'] ?> عميل</div>
        </div>
      </div>

      <div class="analytics-kpi-card" style="padding: 14px;">
        <div class="analytics-kpi-icon" style="background: rgba(59, 130, 246, 0.15); color: #3b82f6; width: 40px; height: 40px;">
          <i class="fas fa-globe"></i>
        </div>
        <div>
          <div style="font-size: 11.5px; color: var(--text-muted);">إجمالي تكلفة الدومينات</div>
          <div style="font-size: 18px; font-weight: 800; color: #3b82f6;"><?= number_format($monthStats['total_domain']) ?> ج.م</div>
        </div>
      </div>

      <div class="analytics-kpi-card" style="padding: 14px;">
        <div class="analytics-kpi-icon" style="background: rgba(245, 158, 11, 0.15); color: #f59e0b; width: 40px; height: 40px;">
          <i class="fas fa-envelope"></i>
        </div>
        <div>
          <div style="font-size: 11.5px; color: var(--text-muted);">إجمالي تكلفة الإيميلات</div>
          <div style="font-size: 18px; font-weight: 800; color: #f59e0b;"><?= number_format($monthStats['total_email']) ?> ج.م</div>
        </div>
      </div>

      <div class="analytics-kpi-card" style="padding: 14px;">
        <div class="analytics-kpi-icon" style="background: rgba(16, 185, 129, 0.15); color: #10b981; width: 40px; height: 40px;">
          <i class="fas fa-money-check-alt"></i>
        </div>
        <div>
          <div style="font-size: 11.5px; color: var(--text-muted);">إجمالي تكلفة الخدمات المحجوزة</div>
          <div style="font-size: 18px; font-weight: 800; color: #10b981;"><?= number_format($monthStats['grand_total']) ?> ج.م</div>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <!-- جدول المشتركين يمتد 100% -->
  <div class="card">
    <div class="table-wrapper">
      <table class="data-table">
        <thead>
          <tr>
            <th style="width: 90px;">الشهر</th>
            <th>العميل والشركة</th>
            <th>الهاتف</th>
            <th>النطاق</th>
            <th>تكلفة الدومين</th>
            <th>تكلفة البريد</th>
            <th>تكلفة الموقع</th>
            <th>إجمالي التكلفة</th>
            <th style="text-align: center;">إجراءات</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($intakeReport)): ?>
            <tr>
              <td colspan="9" style="text-align: center; padding: 40px; color: var(--text-muted);">
                لا يوجد مشتركون مسجلون في هذه الفترة.
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($intakeReport as $row): ?>
              <tr>
                <td>
                  <a href="analytics_hub.php?tab=intake&year=<?= $year ?>&month=<?= (int)$row['start_month'] ?>" style="font-weight: 700; color: #0d9488; text-decoration: none;">
                    <?= $arabicMonths[(int)$row['start_month']] ?? '' ?>
                  </a>
                </td>
                <td>
                  <a href="../clients/view.php?id=<?= $row['client_id'] ?>" style="font-weight: 800; color: var(--primary); text-decoration: none;">
                    <?= e($row['client_name']) ?>
                  </a>
                  <?php if (!empty($row['company_name'])): ?>
                    <div style="font-size: 11px; color: var(--text-muted);"><?= e($row['company_name']) ?></div>
                  <?php endif; ?>
                </td>
                <td><span dir="ltr"><?= e($row['mobile']) ?></span></td>
                <td><code><?= e($row['client_domain'] ?: '—') ?></code></td>
                <td><?= number_format((float)$row['domain_cost']) ?> ج.م</td>
                <td><?= number_format((float)$row['email_cost']) ?> ج.م</td>
                <td><?= number_format((float)$row['website_cost']) ?> ج.م</td>
                <td><strong style="color: #0d9488; font-size: 13.5px;"><?= number_format((float)$row['total_cost']) ?> ج.م</strong></td>
                <td style="text-align: center;">
                  <div style="display: inline-flex; gap: 6px;">
                    <a href="client-statement.php?id=<?= $row['client_id'] ?>" class="btn btn-sm btn-outline" style="font-size: 11px; padding: 2px 7px;" title="كشف حساب العميل">
                      <i class="fas fa-file-invoice"></i> كشف
                    </a>
                    <a href="https://wa.me/<?= preg_replace('/\D/', '', $row['mobile']) ?>" target="_blank" class="btn btn-sm btn-outline" style="color: #10b981; border-color: #10b981; font-size: 11px; padding: 2px 7px;" title="شات واتساب">
                      <i class="fab fa-whatsapp"></i>
                    </a>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php endif; ?>

<!-- نافذة المودال للمعاينة الفورية لمشتركي أي خدمة في أي شهر -->
<div id="drillModal" class="drill-modal" onclick="closeDrillModal(event)">
  <div class="drill-modal-content" onclick="event.stopPropagation()">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; border-bottom: 1px solid var(--border-color); padding-bottom: 10px;">
      <div>
        <h3 id="drillModalTitle" style="margin: 0; font-size: 16px; color: #0d9488;">
          <i class="fas fa-users"></i> المشتركون
        </h3>
        <p id="drillModalSubtitle" style="margin: 3px 0 0 0; font-size: 12px; color: var(--text-muted);"></p>
      </div>
      <button type="button" class="btn btn-sm btn-outline" onclick="closeDrillModal()">✕</button>
    </div>

    <div id="drillModalLoading" style="text-align: center; padding: 30px; display: none;">
      <i class="fas fa-spinner fa-spin" style="font-size: 28px; color: #0d9488;"></i>
      <p style="margin-top: 8px; font-size: 12px; color: var(--text-muted);">جاري جلب بيانات المشتركين...</p>
    </div>

    <div id="drillModalBody" style="overflow-y: auto; flex: 1;">
      <!-- سيتم حقن جدول المشتركين هنا عبر الـ Javascript -->
    </div>

    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 14px; border-top: 1px solid var(--border-color); padding-top: 10px;">
      <span id="drillModalCount" style="font-size: 12px; color: var(--text-muted);"></span>
      <button type="button" class="btn btn-sm btn-secondary" onclick="closeDrillModal()">إغلاق</button>
    </div>
  </div>
</div>

<script>
// فتح نافذة المعاينة الفورية للخدمة والشهر عبر AJAX
function openDrillModal(monthNum, serviceId, monthName, serviceName) {
  const modal = document.getElementById('drillModal');
  const title = document.getElementById('drillModalTitle');
  const sub = document.getElementById('drillModalSubtitle');
  const loading = document.getElementById('drillModalLoading');
  const body = document.getElementById('drillModalBody');
  const countSpan = document.getElementById('drillModalCount');

  if (!modal) return;

  title.innerHTML = `<i class="fas fa-users"></i> المشتركون في خدمة: <strong>${serviceName}</strong>`;
  sub.textContent = `خلال شهر: ${monthName} ${<?= $year ?>}`;
  body.innerHTML = '';
  loading.style.display = 'block';
  modal.classList.add('open');

  const url = `analytics_hub.php?ajax=1&year=<?= $year ?>&detail_month=${monthNum}&detail_service=${serviceId}`;

  fetch(url)
    .then(res => res.json())
    .then(data => {
      loading.style.display = 'none';
      if (!data.clients || data.clients.length === 0) {
        body.innerHTML = '<div style="text-align:center; padding: 30px; color: var(--text-muted);">لا يوجد مشتركون في هذه الخدمة خلال هذا الشهر.</div>';
        countSpan.textContent = 'إجمالي المشتركين: 0';
        return;
      }

      countSpan.textContent = `إجمالي المشتركين: ${data.count} عميل`;

      let html = `
        <div class="table-wrapper">
          <table class="data-table" style="font-size: 12.5px;">
            <thead>
              <tr>
                <th>العميل</th>
                <th>الهاتف</th>
                <th>الخطة / الباقة</th>
                <th>المبلغ</th>
                <th>تاريخ البدء</th>
                <th>واتساب</th>
              </tr>
            </thead>
            <tbody>
      `;

      data.clients.forEach(c => {
        const cleanMob = (c.mobile || '').replace(/\D/g, '');
        html += `
          <tr>
            <td>
              <a href="../clients/view.php?id=${c.id}" target="_blank" style="font-weight: 700; color: var(--primary); text-decoration: none;">
                ${c.name}
              </a>
              ${c.company_name ? `<div style="font-size: 11px; color: var(--text-muted);">${c.company_name}</div>` : ''}
            </td>
            <td><span dir="ltr">${c.mobile || '—'}</span></td>
            <td><span class="badge" style="background:#f1f5f9;">${c.plan_name || 'افتراضي'}</span></td>
            <td><strong>${Number(c.price).toLocaleString()} ${c.currency || 'EGP'}</strong></td>
            <td>${c.start_date || '—'}</td>
            <td>
              <a href="https://wa.me/${cleanMob}" target="_blank" class="btn btn-sm btn-outline" style="color: #10b981; border-color: #10b981; padding: 2px 7px; font-size: 11px;">
                <i class="fab fa-whatsapp"></i> شات
              </a>
            </td>
          </tr>
        `;
      });

      html += '</tbody></table></div>';
      body.innerHTML = html;
    })
    .catch(err => {
      loading.style.display = 'none';
      body.innerHTML = '<div style="color: var(--danger); text-align: center; padding: 20px;">حدث خطأ أثناء تحميل البيانات.</div>';
    });
}

function closeDrillModal(e) {
  const modal = document.getElementById('drillModal');
  if (modal) modal.classList.remove('open');
}
</script>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
