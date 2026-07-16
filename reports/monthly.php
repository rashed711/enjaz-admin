<?php
/**
 * reports/monthly.php - تقرير المشتركين والخدمات شهرياً
 */
require_once dirname(__DIR__) . '/config/app.php';
requireLogin();
requirePermission('view_reports');

$db = getDB();
$year = (int)($_GET['year'] ?? date('Y'));
$selectedMonth = isset($_GET['month']) ? (int)$_GET['month'] : null;

$arabicMonths = ['', 'يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو', 'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'];

if ($selectedMonth) {
    // ── تفاصيل الشهر المحدد ──
    $stmt = $db->prepare("
        SELECT 
            c.id as client_id,
            c.name as client_name,
            c.company_name,
            c.status as client_status,
            COALESCE(SUM(CASE WHEN cs.service_id = 2 THEN cs.price END), 0) as domain_cost,
            COALESCE(SUM(CASE WHEN cs.service_id = 1 THEN cs.price END), 0) as email_cost,
            COALESCE(SUM(CASE WHEN cs.service_id = 3 THEN cs.price END), 0) as website_cost,
            SUM(cs.price) as total_cost,
            MIN(cs.start_date) as min_start_date
        FROM client_subscriptions cs
        JOIN clients c ON c.id = cs.client_id
        WHERE YEAR(cs.start_date) = ? 
          AND MONTH(cs.start_date) = ? 
          AND cs.status != 'cancelled'
        GROUP BY c.id
        ORDER BY total_cost DESC
    ");
    $stmt->execute([$year, $selectedMonth]);
    $clientsReport = $stmt->fetchAll();

    // حساب الإجماليات للشهر
    $monthTotalDomains = array_sum(array_column($clientsReport, 'domain_cost'));
    $monthTotalEmails = array_sum(array_column($clientsReport, 'email_cost'));
    $monthTotalWebsites = array_sum(array_column($clientsReport, 'website_cost'));
    $monthTotalGrand = array_sum(array_column($clientsReport, 'total_cost'));
} else {
    // ── التقرير السنوي العام ──
    $monthlySummary = [];
    for ($m = 1; $m <= 12; $m++) {
        $stmt = $db->prepare("
            SELECT 
                COUNT(DISTINCT cs.client_id) as clients_count,
                COUNT(CASE WHEN cs.service_id = 2 THEN 1 END) as domain_count,
                COALESCE(SUM(CASE WHEN cs.service_id = 2 THEN cs.price END), 0) as domain_total,
                COALESCE(SUM(CASE WHEN cs.service_id = 1 THEN cs.price END), 0) as email_total,
                COALESCE(SUM(CASE WHEN cs.service_id = 3 THEN cs.price END), 0) as website_total,
                SUM(cs.price) as grand_total
            FROM client_subscriptions cs
            JOIN clients c ON c.id = cs.client_id
            WHERE YEAR(cs.start_date) = ? 
              AND MONTH(cs.start_date) = ? 
              AND cs.status != 'cancelled'
        ");
        $stmt->execute([$year, $m]);
        $row = $stmt->fetch();
        
        $monthlySummary[$m] = [
            'clients_count' => (int)$row['clients_count'],
            'domain_count'  => (int)$row['domain_count'],
            'domain_total'  => (float)$row['domain_total'],
            'email_total'   => (float)$row['email_total'],
            'website_total' => (float)$row['website_total'],
            'grand_total'   => (float)$row['grand_total']
        ];
    }

    // حساب الإجماليات السنوية
    $yearTotalClients = 0;
    // للحصول على العدد الفعلي للعملاء الفريدين طوال السنة
    $stmtYearClients = $db->prepare("
        SELECT COUNT(DISTINCT cs.client_id) 
        FROM client_subscriptions cs
        WHERE YEAR(cs.start_date) = ? AND cs.status != 'cancelled'
    ");
    $stmtYearClients->execute([$year]);
    $yearTotalClients = (int)$stmtYearClients->fetchColumn();

    $yearTotalDomainsCount = array_sum(array_column($monthlySummary, 'domain_count'));
    $yearTotalDomains = array_sum(array_column($monthlySummary, 'domain_total'));
    $yearTotalEmails = array_sum(array_column($monthlySummary, 'email_total'));
    $yearTotalWebsites = array_sum(array_column($monthlySummary, 'website_total'));
    $yearTotalGrand = array_sum(array_column($monthlySummary, 'grand_total'));
}

$pageTitle = $selectedMonth ? "المشتركون في شهر " . $arabicMonths[$selectedMonth] . " " . $year : "المشتركون والخدمات شهرياً لعام " . $year;
$activePage = 'reports-monthly';
$depth = 1;
require_once dirname(__DIR__) . '/includes/header.php';
?>

<div class="reports-container" style="padding: 24px 0;">
  
  <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:16px; margin-bottom:24px;">
    <div>
      <h2 style="margin:0; font-size:22px; font-weight:800; color:var(--text-primary);">
        <?= e($pageTitle) ?>
      </h2>
      <p style="margin:4px 0 0 0; color:var(--text-muted); font-size:13.5px;">
        <?php if ($selectedMonth): ?>
        استعراض تفصيلي لعملاء شهر <?= $arabicMonths[$selectedMonth] ?> وتكلفة الخدمات لكل عميل.
        <?php else: ?>
        تقرير تحليلي يوضح أعداد العملاء المشتركين وتكلفة الخدمات شهرياً.
        <?php endif; ?>
      </p>
    </div>

    <div style="display:flex; align-items:center; gap:12px;">
      <?php if ($selectedMonth): ?>
      <a href="monthly.php?year=<?= $year ?>" class="btn btn-outline" style="font-weight:700;">
        <i class="fas fa-arrow-left" style="margin-left:6px;"></i> العودة للتقرير السنوي
      </a>
      <?php else: ?>
      <form method="GET" action="" style="display:flex; align-items:center; gap:8px;">
        <select name="year" onchange="this.form.submit()" class="form-control" style="font-weight:700; width:120px; padding:6px 12px; border-radius:8px;">
          <?php for($y = date('Y') + 1; $y >= date('Y') - 4; $y--): ?>
            <option value="<?= $y ?>" <?= $y === $year ? 'selected' : '' ?>><?= $y ?></option>
          <?php endfor; ?>
        </select>
      </form>
      <?php endif; ?>
    </div>
  </div>

  <?php if ($selectedMonth): ?>
    <!-- ══ Detailed Month View ══════════════════════════════════ -->
    <div class="card" style="margin-bottom: 24px;">
      <div class="card-header" style="display:flex; justify-content:space-between; align-items:center; border-bottom: 1px solid #f1f5f9; padding: 16px 20px;">
        <span class="card-title" style="font-weight:800;"><i class="fas fa-users"></i> قائمة العملاء المشتركين في شهر <?= $arabicMonths[$selectedMonth] ?> (<?= count($clientsReport) ?> عميل)</span>
      </div>
      
      <div class="table-wrapper" style="overflow-x: auto;">
        <table class="data-table" style="width:100%; text-align:center;">
          <thead>
            <tr style="background:#f8fafc; font-weight:700;">
              <th style="width: 60px;">#</th>
              <th style="text-align:right;">العميل</th>
              <th style="text-align:right;">الشركة</th>
              <th>تاريخ الاشتراك</th>
              <th>تكلفة الدومين</th>
              <th>استضافة البريد</th>
              <th>تصميم الموقع</th>
              <th>إجمالي التكلفة</th>
              <th>إجراء</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($clientsReport)): ?>
            <tr>
              <td colspan="9" style="padding:40px;">
                <div class="empty-state">
                  <div class="empty-icon" style="color:var(--text-muted);"><i class="fas fa-users-slash"></i></div>
                  <p class="empty-title">لا يوجد عملاء مشتركين في هذا الشهر</p>
                </div>
              </td>
            </tr>
            <?php else: ?>
            <?php foreach ($clientsReport as $index => $row): ?>
            <tr>
              <td class="text-muted"><?= $index + 1 ?></td>
              <td style="text-align:right; font-weight:700;">
                <a href="../clients/view.php?id=<?= $row['client_id'] ?>" style="color:var(--primary); text-decoration:none;">
                  <?= e($row['client_name']) ?>
                </a>
                <?php if (isset($row['client_status']) && !$row['client_status']): ?>
                <span class="badge badge-danger" style="font-size:10px; padding:2px 6px; margin-right:4px;">موقوف</span>
                <?php endif; ?>
              </td>
              <td style="text-align:right;" class="text-muted"><?= e($row['company_name'] ?: '—') ?></td>
              <td class="text-muted"><?= formatDate($row['min_start_date']) ?></td>
              <td style="font-weight:700; color: <?= $row['domain_cost'] > 0 ? 'var(--text-primary)' : 'var(--text-muted)' ?>;">
                <?= $row['domain_cost'] > 0 ? formatMoney($row['domain_cost']) : '—' ?>
              </td>
              <td style="font-weight:700; color: <?= $row['email_cost'] > 0 ? 'var(--text-primary)' : 'var(--text-muted)' ?>;">
                <?= $row['email_cost'] > 0 ? formatMoney($row['email_cost']) : '—' ?>
              </td>
              <td style="font-weight:700; color: <?= $row['website_cost'] > 0 ? 'var(--text-primary)' : 'var(--text-muted)' ?>;">
                <?= $row['website_cost'] > 0 ? formatMoney($row['website_cost']) : '—' ?>
              </td>
              <td style="font-weight:800; color: var(--success);">
                <?= formatMoney($row['total_cost']) ?>
              </td>
              <td>
                <a href="../clients/view.php?id=<?= $row['client_id'] ?>" class="btn btn-sm btn-outline-primary" style="padding:3px 8px; font-size:12px; border-radius:6px;">
                  <i class="fas fa-eye"></i> عرض الملف
                </a>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
          <?php if (!empty($clientsReport)): ?>
          <tfoot>
            <tr style="background:#f8fafc; font-weight:800; border-top: 2px solid #e2e8f0;">
              <td colspan="4" style="text-align:right; padding:16px 20px;">الإجمالي للشهر:</td>
              <td style="color:var(--text-primary); font-size:14px;"><?= formatMoney($monthTotalDomains) ?></td>
              <td style="color:var(--text-primary); font-size:14px;"><?= formatMoney($monthTotalEmails) ?></td>
              <td style="color:var(--text-primary); font-size:14px;"><?= formatMoney($monthTotalWebsites) ?></td>
              <td style="color:var(--success); font-size:16px; font-weight:900;"><?= formatMoney($monthTotalGrand) ?></td>
              <td></td>
            </tr>
          </tfoot>
          <?php endif; ?>
        </table>
      </div>
    </div>

  <?php else: ?>
    <!-- ══ Yearly Summary Table ══════════════════════════════════ -->
    <div class="card" style="margin-bottom: 24px;">
      <div class="card-header" style="display:flex; justify-content:between; align-items:center; border-bottom: 1px solid #f1f5f9; padding: 16px 20px;">
        <span class="card-title" style="font-weight:800;"><i class="fas fa-calendar-alt"></i> جدول المشتركين شهرياً وتوزيع مبيعات الخدمات لعام <?= $year ?></span>
      </div>

      <div class="table-wrapper" style="overflow-x: auto;">
        <table class="data-table" style="width:100%; text-align:center;">
          <thead>
            <tr style="background:#f8fafc; font-weight:700;">
              <th>الشهر</th>
              <th>عدد المشتركين الجدد</th>
              <th>مبيعات الدومينات</th>
              <th>مبيعات استضافة البريد</th>
              <th>مبيعات تصميم المواقع</th>
              <th>إجمالي المبيعات للشهر</th>
              <th>إجراء</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($monthlySummary as $m => $data): ?>
            <tr style="<?= $data['clients_count'] > 0 ? 'background: rgba(36,86,164,0.01);' : '' ?>">
              <td style="font-weight:800; font-size:14px;">
                <a href="monthly.php?month=<?= $m ?>&year=<?= $year ?>" style="color:var(--primary); text-decoration:none;">
                  <?= $arabicMonths[$m] ?>
                </a>
              </td>
              <td style="font-weight:700; color: <?= $data['clients_count'] > 0 ? 'var(--primary-light)' : 'var(--text-muted)' ?>;">
                <?= $data['clients_count'] > 0 ? $data['clients_count'] . " عميل (" . $data['domain_count'] . " دومين)" : '—' ?>
              </td>
              <td style="font-weight:700; color: <?= $data['domain_total'] > 0 ? 'var(--text-primary)' : 'var(--text-muted)' ?>;">
                <?= $data['domain_total'] > 0 ? formatMoney($data['domain_total']) : '—' ?>
              </td>
              <td style="font-weight:700; color: <?= $data['email_total'] > 0 ? 'var(--text-primary)' : 'var(--text-muted)' ?>;">
                <?= $data['email_total'] > 0 ? formatMoney($data['email_total']) : '—' ?>
              </td>
              <td style="font-weight:700; color: <?= $data['website_total'] > 0 ? 'var(--text-primary)' : 'var(--text-muted)' ?>;">
                <?= $data['website_total'] > 0 ? formatMoney($data['website_total']) : '—' ?>
              </td>
              <td style="font-weight:800; color: var(--success);">
                <?= $data['grand_total'] > 0 ? formatMoney($data['grand_total']) : '—' ?>
              </td>
              <td>
                <a href="monthly.php?month=<?= $m ?>&year=<?= $year ?>" class="btn btn-sm btn-outline-primary" style="padding:3px 12px; font-size:12px; border-radius:6px; font-weight:700;">
                  <i class="fas fa-list-ul"></i> تفاصيل الشهر
                </a>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
          <tfoot>
            <tr style="background:#f8fafc; font-weight:900; border-top: 2px solid #e2e8f0; font-size:14.5px;">
              <td style="padding:16px 20px;">الإجمالي السنوي:</td>
              <td style="color:var(--primary-light);"><?= $yearTotalClients ?> عميل فريد (<?= $yearTotalDomainsCount ?> دومين)</td>
              <td style="color:var(--text-primary);"><?= formatMoney($yearTotalDomains) ?></td>
              <td style="color:var(--text-primary);"><?= formatMoney($yearTotalEmails) ?></td>
              <td style="color:var(--text-primary);"><?= formatMoney($yearTotalWebsites) ?></td>
              <td style="color:var(--success); font-size:16px; font-weight:900;"><?= formatMoney($yearTotalGrand) ?></td>
              <td></td>
            </tr>
          </tfoot>
        </table>
      </div>
    </div>
  <?php endif; ?>

</div>

<?php
require_once dirname(__DIR__) . '/includes/footer.php';
?>
