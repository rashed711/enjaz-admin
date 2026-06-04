<?php
/**
 * dashboard.php - لوحة التحكم الرئيسية
 */
require_once __DIR__ . '/config/app.php';
requireLogin();

$db = getDB();

// ── الإحصائيات ──────────────────────────────────────────────────
$totalClients    = (int)$db->query("SELECT COUNT(*) FROM clients WHERE status=1")->fetchColumn();
$totalRevenue    = (float)$db->query("SELECT COALESCE(SUM(amount),0) FROM payments")->fetchColumn();
$totalDebt       = (float)$db->query("
    SELECT COALESCE(SUM(cs.price),0) - COALESCE((SELECT SUM(p.amount) FROM payments p),0)
    FROM client_subscriptions cs WHERE cs.status != 'cancelled'
")->fetchColumn();

$warningDays = (int)getSetting('renewal_warning_days','30');
$renewalsSoon = (int)$db->prepare("
    SELECT COUNT(*) FROM client_subscriptions cs
    JOIN clients c ON c.id=cs.client_id
    WHERE cs.status='active' AND cs.end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(),INTERVAL ? DAY)
")->execute([$warningDays]) ? $db->query("
    SELECT COUNT(*) FROM client_subscriptions cs
    WHERE cs.status='active' AND cs.end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(),INTERVAL $warningDays DAY)
")->fetchColumn() : 0;

// Fix query properly
$rStmt = $db->prepare("SELECT COUNT(*) FROM client_subscriptions WHERE status='active' AND end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)");
$rStmt->execute([$warningDays]);
$renewalsSoon = (int)$rStmt->fetchColumn();

$thisMonthRevenue = (float)$db->query("SELECT COALESCE(SUM(amount),0) FROM payments WHERE YEAR(payment_date)=YEAR(CURDATE()) AND MONTH(payment_date)=MONTH(CURDATE())")->fetchColumn();
$newClientsMonth  = (int)$db->query("SELECT COUNT(*) FROM clients WHERE YEAR(created_at)=YEAR(CURDATE()) AND MONTH(created_at)=MONTH(CURDATE())")->fetchColumn();

// ── آخر العملاء ──────────────────────────────────────────────────
$latestClients = $db->query("
    SELECT c.*, COALESCE(SUM(CASE WHEN cs.status!='cancelled' THEN cs.price ELSE 0 END),0) as total,
           COALESCE((SELECT SUM(p.amount) FROM payments p WHERE p.client_id=c.id),0) as paid
    FROM clients c LEFT JOIN client_subscriptions cs ON cs.client_id=c.id
    GROUP BY c.id ORDER BY c.created_at DESC LIMIT 6
")->fetchAll();

// ── الاشتراكات المنتهية قريباً ────────────────────────────────
$upcomingRenewals = $db->prepare("
    SELECT cs.*, c.name as client_name, c.mobile, s.name as service_name,
           DATEDIFF(cs.end_date, CURDATE()) as days_left
    FROM client_subscriptions cs
    JOIN clients c ON c.id=cs.client_id
    JOIN services s ON s.id=cs.service_id
    WHERE cs.status='active' AND cs.end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
    ORDER BY cs.end_date ASC LIMIT 8
");
$upcomingRenewals->execute([$warningDays]);
$upcomingRenewals = $upcomingRenewals->fetchAll();

// ── رسم بياني: إيرادات آخر 6 أشهر ───────────────────────────
$chartData = [];
for ($i = 5; $i >= 0; $i--) {
    $date  = new DateTime("first day of -$i month");
    $year  = $date->format('Y');
    $month = $date->format('m');
    $label = $date->format('M Y');
    $stmt  = $db->prepare("SELECT COALESCE(SUM(amount),0) FROM payments WHERE YEAR(payment_date)=? AND MONTH(payment_date)=?");
    $stmt->execute([$year,$month]);
    $chartData[] = ['label' => $date->format('M'), 'value' => (float)$stmt->fetchColumn()];
}

$pageTitle  = 'لوحة التحكم';
$activePage = 'dashboard';
$depth      = 0;
require_once INCLUDES_PATH . '/header.php';
?>

<!-- Stats Grid -->
<div class="stats-grid">

  <div class="stat-card stat-primary">
    <div class="stat-icon bg-primary"><i class="fas fa-users"></i></div>
    <div class="stat-content">
      <div class="stat-value"><?= number_format($totalClients) ?></div>
      <div class="stat-label">إجمالي العملاء</div>
      <?php if ($newClientsMonth > 0): ?>
      <div class="stat-change up"><i class="fas fa-arrow-up"></i> +<?= $newClientsMonth ?> هذا الشهر</div>
      <?php endif; ?>
    </div>
  </div>

  <div class="stat-card stat-success">
    <div class="stat-icon bg-success"><i class="fas fa-coins"></i></div>
    <div class="stat-content">
      <div class="stat-value" style="font-size:20px;"><?= formatMoney($totalRevenue) ?></div>
      <div class="stat-label">إجمالي الإيرادات</div>
      <?php if ($thisMonthRevenue > 0): ?>
      <div class="stat-change up"><i class="fas fa-arrow-up"></i> <?= formatMoney($thisMonthRevenue) ?> هذا الشهر</div>
      <?php endif; ?>
    </div>
  </div>

  <div class="stat-card stat-danger">
    <div class="stat-icon bg-danger"><i class="fas fa-file-invoice-dollar"></i></div>
    <div class="stat-content">
      <div class="stat-value" style="font-size:20px;"><?= formatMoney(max(0,$totalDebt)) ?></div>
      <div class="stat-label">المبالغ المستحقة</div>
    </div>
  </div>

  <div class="stat-card stat-warning">
    <div class="stat-icon bg-warning"><i class="fas fa-calendar-exclamation"></i></div>
    <div class="stat-content">
      <div class="stat-value"><?= $renewalsSoon ?></div>
      <div class="stat-label">تجديدات قريبة (<?= $warningDays ?> يوم)</div>
    </div>
  </div>

</div>

<div style="display:grid;grid-template-columns:2fr 1fr;gap:20px;align-items:start;">

  <!-- Chart + Renewals -->
  <div>
    <!-- Revenue Chart -->
    <div class="card" style="margin-bottom:20px;">
      <div class="card-header">
        <span class="card-title"><i class="fas fa-chart-bar"></i> الإيرادات — آخر 6 أشهر</span>
      </div>
      <div class="card-body">
        <canvas id="revenueChart" height="80"></canvas>
      </div>
    </div>

    <!-- Upcoming Renewals -->
    <?php if (!empty($upcomingRenewals)): ?>
    <div class="card">
      <div class="card-header">
        <span class="card-title"><i class="fas fa-bell" style="color:var(--warning);"></i> اشتراكات تنتهي قريباً</span>
        <a href="reports/renewals.php" class="btn btn-sm btn-outline">عرض الكل</a>
      </div>
      <div class="table-wrapper">
        <table class="data-table">
          <thead>
            <tr><th>العميل</th><th>الخدمة</th><th>تاريخ الانتهاء</th><th>المتبقي</th><th></th></tr>
          </thead>
          <tbody>
            <?php foreach ($upcomingRenewals as $r): ?>
            <tr>
              <td>
                <a href="clients/view.php?id=<?= $r['client_id'] ?>" style="font-weight:600;">
                  <?= e($r['client_name']) ?>
                </a>
              </td>
              <td class="text-muted"><?= e($r['service_name']) ?></td>
              <td><?= formatDate($r['end_date']) ?></td>
              <td>
                <?php if ($r['days_left'] <= 7): ?>
                  <span class="badge badge-danger"><?= $r['days_left'] ?> يوم</span>
                <?php elseif ($r['days_left'] <= 14): ?>
                  <span class="badge badge-warning"><?= $r['days_left'] ?> يوم</span>
                <?php else: ?>
                  <span class="badge badge-info"><?= $r['days_left'] ?> يوم</span>
                <?php endif; ?>
              </td>
              <td>
                <a href="subscriptions/renew.php?id=<?= $r['id'] ?>" class="btn btn-sm btn-success">
                  <i class="fas fa-redo"></i> تجديد
                </a>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php endif; ?>
  </div>

  <!-- Latest Clients -->
  <div>
    <div class="card">
      <div class="card-header">
        <span class="card-title"><i class="fas fa-user-clock"></i> آخر العملاء</span>
        <a href="clients/index.php" class="btn btn-sm btn-outline">عرض الكل</a>
      </div>
      <div class="card-body" style="padding:0;">
        <?php foreach ($latestClients as $cl):
          $remaining = $cl['total'] - $cl['paid'];
        ?>
        <div style="display:flex;align-items:center;gap:12px;padding:14px 18px;border-bottom:1px solid #f1f5f9;transition:.15s;"
             onmouseover="this.style.background='#f8fbff'" onmouseout="this.style.background=''">
          <div style="width:38px;height:38px;border-radius:10px;background:linear-gradient(135deg,var(--primary-light),var(--primary));
                      display:flex;align-items:center;justify-content:center;color:#fff;font-weight:800;font-size:15px;flex-shrink:0;">
            <?= e(mb_substr($cl['name'],0,1,'UTF-8')) ?>
          </div>
          <div style="flex:1;min-width:0;">
            <a href="clients/view.php?id=<?= $cl['id'] ?>" style="font-weight:700;color:var(--text-primary);display:block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
              <?= e($cl['name']) ?>
            </a>
            <?php if ($cl['company_name']): ?>
            <div style="font-size:11.5px;color:var(--text-muted);"><?= e($cl['company_name']) ?></div>
            <?php endif; ?>
          </div>
          <div style="text-align:left;flex-shrink:0;">
            <?php if ($remaining > 0): ?>
            <div style="font-size:12px;color:var(--danger);font-weight:700;"><?= formatMoney($remaining) ?></div>
            <div style="font-size:10.5px;color:var(--text-muted);">متبقي</div>
            <?php else: ?>
            <span class="badge badge-success">مسدّد</span>
            <?php endif; ?>
          </div>
        </div>
        <?php endforeach; ?>
        <?php if (empty($latestClients)): ?>
        <div class="empty-state" style="padding:40px;"><div class="empty-icon"><i class="fas fa-users"></i></div><p class="empty-title">لا يوجد عملاء بعد</p></div>
        <?php endif; ?>
      </div>
    </div>
  </div>

</div>

<script>
// Revenue Chart
const ctx = document.getElementById('revenueChart');
if (ctx) {
  new Chart(ctx, {
    type: 'bar',
    data: {
      labels: [<?= implode(',', array_map(fn($d) => '"'.$d['label'].'"', $chartData)) ?>],
      datasets: [{
        label: 'الإيرادات',
        data: [<?= implode(',', array_column($chartData,'value')) ?>],
        backgroundColor: 'rgba(36,86,164,0.12)',
        borderColor: 'rgba(36,86,164,0.8)',
        borderWidth: 2,
        borderRadius: 8,
        borderSkipped: false,
      }]
    },
    options: {
      responsive: true,
      plugins: {
        legend: { display: false },
        tooltip: {
          callbacks: {
            label: ctx => ctx.parsed.y.toLocaleString('en-US', {minimumFractionDigits:2}) + ' <?= getSetting('currency','جنيه') ?>'
          }
        }
      },
      scales: {
        y: {
          beginAtZero: true,
          grid: { color: 'rgba(0,0,0,.04)' },
          ticks: { font: { family: 'Cairo' } }
        },
        x: {
          grid: { display: false },
          ticks: { font: { family: 'Cairo' } }
        }
      }
    }
  });
}
</script>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
