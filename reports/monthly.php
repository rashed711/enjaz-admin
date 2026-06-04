<?php
/**
 * reports/monthly.php - تقرير الإيرادات الشهري
 */
require_once dirname(__DIR__) . '/config/app.php';
requireLogin();
requirePermission('view_reports');

$db   = getDB();
$year = (int)($_GET['year'] ?? date('Y'));

// شهري
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
$pageTitle  = 'تقرير الإيرادات الشهري';
$activePage = 'reports-monthly';
$depth      = 1;
require_once INCLUDES_PATH . '/header.php';
?>
<div class="page-header">
  <div class="page-header-text">
    <h1 class="page-title"><i class="fas fa-chart-bar" style="color:var(--primary-light);margin-left:8px;"></i>تقرير الإيرادات الشهري</h1>
    <p class="page-subtitle">إجمالي <?= $year ?> — <?= formatMoney($totalYear) ?></p>
  </div>
  <div class="page-actions">
    <form method="GET" style="display:flex;gap:10px;align-items:center;">
      <select name="year" class="form-control" style="width:auto;" onchange="this.form.submit()">
        <?php for ($y = date('Y'); $y >= date('Y')-5; $y--): ?>
        <option value="<?= $y ?>" <?= $year===$y?'selected':'' ?>><?= $y ?></option>
        <?php endfor; ?>
      </select>
    </form>
  </div>
</div>

<div style="display:grid;grid-template-columns:2fr 1fr;gap:20px;align-items:start;">
  <div class="card">
    <div class="card-header"><span class="card-title"><i class="fas fa-chart-bar"></i> الإيرادات الشهرية <?= $year ?></span></div>
    <div class="card-body">
      <canvas id="monthlyChart" height="100"></canvas>
    </div>
    <div class="table-wrapper">
      <table class="data-table">
        <thead><tr><th>الشهر</th><th>الإيرادات</th><th>النسبة</th></tr></thead>
        <tbody>
          <?php foreach ($months as $m => $amt): ?>
          <tr>
            <td><?= $arabicMonths[$m] ?></td>
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
    <div class="card-header"><span class="card-title"><i class="fas fa-trophy"></i> أفضل الخدمات</span></div>
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
        tooltip: { callbacks: { label: ctx => ctx.parsed.y.toLocaleString('ar-EG',{minimumFractionDigits:2}) + ' <?= getSetting('currency','جنيه') ?>' } }
      },
      scales: { y: { beginAtZero: true, ticks: { font: { family:'Cairo' } } }, x: { grid: { display:false }, ticks: { font:{family:'Cairo'} } } }
    }
  });
}
</script>
<?php require_once INCLUDES_PATH . '/footer.php'; ?>
