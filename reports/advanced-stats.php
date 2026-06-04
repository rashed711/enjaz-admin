<?php
/**
 * reports/advanced-stats.php - التقارير المتقدمة والإحصائيات الشاملة
 */
require_once dirname(__DIR__) . '/config/app.php';
requireLogin();
requirePermission('view_reports');

$db = getDB();

// ── 1. الإحصائيات العامة (KPIs) ───────────────────────────────────
// إجمالي العملاء
$totalClients = (int)$db->query("SELECT COUNT(*) FROM clients")->fetchColumn();
// العملاء النشطين
$activeClients = (int)$db->query("SELECT COUNT(*) FROM clients WHERE status = 1")->fetchColumn();
// إجمالي الاشتراكات النشطة
$activeSubs = (int)$db->query("SELECT COUNT(*) FROM client_subscriptions WHERE status = 'active'")->fetchColumn();

// عدد الدومينات المحجوزة من خلالنا
$ourDomainsCount = (int)$db->query("
    SELECT COUNT(*) FROM clients 
    WHERE domain IS NOT NULL AND domain != '' 
      AND domain_provider IS NOT NULL AND domain_provider != '' AND domain_provider != '-'
")->fetchColumn();

// مشاريع تصميم وتطوير المواقع المدفوعة والمجانية
$designPaid = (int)$db->query("
    SELECT COUNT(*) FROM client_subscriptions cs 
    JOIN services s ON s.id = cs.service_id 
    WHERE (s.name LIKE '%تصميم%' OR s.name LIKE '%موقع%' OR s.name LIKE '%web%' OR s.name LIKE '%design%') 
      AND cs.price > 0 AND cs.status != 'cancelled'
")->fetchColumn();

$designFree = (int)$db->query("
    SELECT COUNT(*) FROM client_subscriptions cs 
    JOIN services s ON s.id = cs.service_id 
    WHERE (s.name LIKE '%تصميم%' OR s.name LIKE '%موقع%' OR s.name LIKE '%web%' OR s.name LIKE '%design%') 
      AND cs.price = 0 AND cs.status != 'cancelled'
")->fetchColumn();

// ── 2. الاشتراكات الجديدة شهرياً (آخر 12 شهر) ──────────────────────
$monthlyTrend = $db->query("
    SELECT DATE_FORMAT(start_date, '%Y-%m') as month_label, COUNT(*) as count 
    FROM client_subscriptions 
    WHERE start_date IS NOT NULL AND start_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
    GROUP BY month_label 
    ORDER BY month_label ASC
")->fetchAll();

// ── 3. توزيع الاشتراكات حسب الباقات ───────────────────────────────
$packageDist = $db->query("
    SELECT COALESCE(plan_name, 'بدون باقة مخصصة') as plan, COUNT(*) as count 
    FROM client_subscriptions 
    WHERE status != 'cancelled'
    GROUP BY plan 
    ORDER BY count DESC
")->fetchAll();

// ── 4. توزيع الاشتراكات حسب الخدمات ───────────────────────────────
$serviceDist = $db->query("
    SELECT s.name as service_name, COUNT(*) as count 
    FROM client_subscriptions cs
    JOIN services s ON s.id = cs.service_id
    WHERE cs.status != 'cancelled'
    GROUP BY s.id 
    ORDER BY count DESC
")->fetchAll();

// ── 5. قائمة العملاء الذين لديهم دومين محجوز من خلالنا ─────────────
$ourDomainsClients = $db->query("
    SELECT id, name, company_name, domain, domain_provider 
    FROM clients 
    WHERE domain IS NOT NULL AND domain != '' 
      AND domain_provider IS NOT NULL AND domain_provider != '' AND domain_provider != '-'
    ORDER BY name ASC
")->fetchAll();

$pageTitle  = 'التقارير المتقدمة';
$activePage = 'reports-advanced';
$depth      = 1;
require_once INCLUDES_PATH . '/header.php';
?>

<div class="page-header">
  <div class="page-header-text">
    <h1 class="page-title"><i class="fas fa-chart-pie" style="color:var(--primary-light);margin-left:8px;"></i>التقارير المتقدمة والإحصائيات</h1>
    <p class="page-subtitle">نظرة شاملة وتحليلية لأداء العملاء، الخدمات، والدومينات</p>
  </div>
</div>

<!-- KPI Grid -->
<div class="stats-grid" style="margin-bottom: 24px;">
  
  <div class="stat-card stat-primary">
    <div class="stat-icon bg-primary"><i class="fas fa-users"></i></div>
    <div class="stat-content">
      <div class="stat-value"><?= $totalClients ?></div>
      <div class="stat-label">إجمالي العملاء المسجلين</div>
      <div class="stat-change up"><i class="fas fa-check"></i> <?= $activeClients ?> نشط حالياً</div>
    </div>
  </div>

  <div class="stat-card stat-success">
    <div class="stat-icon bg-success"><i class="fas fa-file-signature"></i></div>
    <div class="stat-content">
      <div class="stat-value"><?= $activeSubs ?></div>
      <div class="stat-label">الاشتراكات النشطة حالياً</div>
    </div>
  </div>

  <div class="stat-card stat-info">
    <div class="stat-icon bg-info" style="background-color: var(--primary-light);"><i class="fas fa-globe"></i></div>
    <div class="stat-content">
      <div class="stat-value"><?= $ourDomainsCount ?></div>
      <div class="stat-label">دومينات محجوزة من خلالنا</div>
    </div>
  </div>

  <div class="stat-card stat-warning">
    <div class="stat-icon bg-warning"><i class="fas fa-laptop-code"></i></div>
    <div class="stat-content">
      <div class="stat-value" style="font-size: 19px;"><?= $designPaid ?> مدفوع / <?= $designFree ?> مجاني</div>
      <div class="stat-label">مشاريع تصميم المواقع</div>
    </div>
  </div>

</div>

<!-- Charts Row -->
<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-bottom: 24px; align-items: start;">
  
  <!-- Trend Chart -->
  <div class="card">
    <div class="card-header">
      <span class="card-title"><i class="fas fa-chart-line"></i> منحنى نمو الاشتراكات الجديدة شهرياً</span>
    </div>
    <div class="card-body">
      <canvas id="monthlyTrendChart" height="110"></canvas>
    </div>
  </div>

  <!-- Package Distribution Chart -->
  <div class="card">
    <div class="card-header">
      <span class="card-title"><i class="fas fa-chart-pie"></i> توزيع الاشتراكات حسب الباقات</span>
    </div>
    <div class="card-body">
      <div style="max-height: 250px; position: relative; display: flex; justify-content: center;">
        <canvas id="packageDistChart"></canvas>
      </div>
    </div>
  </div>

</div>

<!-- Breakdown Lists & Tables -->
<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; align-items: start; margin-bottom: 24px;">

  <!-- Service Stats -->
  <div class="card">
    <div class="card-header">
      <span class="card-title"><i class="fas fa-server"></i> عدد الاشتراكات لكل خدمة</span>
    </div>
    <div class="card-body" style="padding: 0;">
      <table class="data-table">
        <thead>
          <tr>
            <th>الخدمة</th>
            <th style="text-align: center;">عدد الاشتراكات</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($serviceDist)): ?>
          <tr><td colspan="2"><div class="empty-state">لا توجد بيانات</div></td></tr>
          <?php else: ?>
          <?php foreach ($serviceDist as $sd): ?>
          <tr>
            <td><strong><?= e($sd['service_name']) ?></strong></td>
            <td style="text-align: center;"><span class="badge badge-primary"><?= $sd['count'] ?> اشتراك</span></td>
          </tr>
          <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Package Stats -->
  <div class="card">
    <div class="card-header">
      <span class="card-title"><i class="fas fa-box-open"></i> تفاصيل توزيع باقات العملاء</span>
    </div>
    <div class="card-body" style="padding: 0;">
      <table class="data-table">
        <thead>
          <tr>
            <th>اسم الباقة</th>
            <th style="text-align: center;">عدد العملاء المشتركين</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($packageDist)): ?>
          <tr><td colspan="2"><div class="empty-state">لا توجد بيانات</div></td></tr>
          <?php else: ?>
          <?php foreach ($packageDist as $pd): ?>
          <tr>
            <td><strong><?= e($pd['plan']) ?></strong></td>
            <td style="text-align: center;"><span class="badge badge-info"><?= $pd['count'] ?> عميل</span></td>
          </tr>
          <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>

<!-- Hosted Domains Client List -->
<div class="card" style="margin-bottom: 24px;">
  <div class="card-header">
    <span class="card-title"><i class="fas fa-globe-americas"></i> قائمة تفصيلية بالعملاء الذين حجزنا الدومين لهم (إجمالي: <?= count($ourDomainsClients) ?> عميل)</span>
  </div>
  <div class="table-wrapper">
    <table class="data-table">
      <thead>
        <tr>
          <th>#</th>
          <th>اسم العميل</th>
          <th>اسم الشركة</th>
          <th>نطاق الموقع (الدومين)</th>
          <th>مزود الخدمة والـ Domain Provider</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($ourDomainsClients)): ?>
        <tr><td colspan="5"><div class="empty-state">لا يوجد أي عملاء مسجلين بدومين محجوز من طرفنا حالياً</div></td></tr>
        <?php else: ?>
        <?php foreach ($ourDomainsClients as $index => $c): ?>
        <tr>
          <td class="text-muted"><?= $index + 1 ?></td>
          <td>
            <a href="../clients/view.php?id=<?= $c['id'] ?>" style="font-weight: 700;">
              <?= e($c['name']) ?>
            </a>
          </td>
          <td><?= e($c['company_name'] ?: '—') ?></td>
          <td>
            <a href="http://<?= e($c['domain']) ?>" target="_blank" style="color: var(--primary); font-weight: 600;">
              <i class="fas fa-external-link-alt" style="font-size: 11px; margin-left: 4px;"></i>
              <?= e($c['domain']) ?>
            </a>
          </td>
          <td><span class="badge badge-success"><?= e($c['domain_provider']) ?></span></td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Chart: Monthly Growth Trend
    const trendCtx = document.getElementById('monthlyTrendChart');
    if (trendCtx) {
        new Chart(trendCtx, {
            type: 'line',
            data: {
                labels: [<?= implode(',', array_map(fn($t) => '"'.$t['month_label'].'"', $monthlyTrend)) ?>],
                datasets: [{
                    label: 'الاشتراكات الجديدة',
                    data: [<?= implode(',', array_column($monthlyTrend, 'count')) ?>],
                    borderColor: '#2456a4',
                    backgroundColor: 'rgba(36, 86, 164, 0.08)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.3,
                    pointBackgroundColor: '#2456a4'
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1 }
                    }
                }
            }
        });
    }

    // 2. Chart: Package Distribution
    const distCtx = document.getElementById('packageDistChart');
    if (distCtx) {
        new Chart(distCtx, {
            type: 'doughnut',
            data: {
                labels: [<?= implode(',', array_map(fn($p) => '"'.$p['plan'].'"', $packageDist)) ?>],
                datasets: [{
                    data: [<?= implode(',', array_column($packageDist, 'count')) ?>],
                    backgroundColor: [
                        '#2456a4',
                        '#10b981',
                        '#f59e0b',
                        '#ef4444',
                        '#8b5cf6',
                        '#6366f1',
                        '#ec4899'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            font: { family: 'Cairo' },
                            boxWidth: 12
                        }
                    }
                }
            }
        });
    }
});
</script>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
