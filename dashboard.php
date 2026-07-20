<?php
/**
 * dashboard.php - لوحة التحكم الرئيسية الشاملة والإحصائيات المتقدمة
 */
require_once __DIR__ . '/config/app.php';
requireLogin();

$db = getDB();

// ── 1. الإحصائيات الأساسية والمتقدمة (KPIs) ───────────────────────────
$totalClients    = (int)$db->query("SELECT COUNT(*) FROM clients")->fetchColumn();
$activeClients   = (int)$db->query("SELECT COUNT(*) FROM clients WHERE status=1")->fetchColumn();
$totalRevenue    = (float)$db->query("SELECT COALESCE(SUM(amount),0) FROM payments")->fetchColumn();
$totalDebt       = (float)$db->query("
    SELECT COALESCE(SUM(cs.price),0) - COALESCE((SELECT SUM(p.amount) FROM payments p),0)
    FROM client_subscriptions cs WHERE cs.status != 'cancelled'
")->fetchColumn();

$warningDays = (int)getSetting('renewal_warning_days','30');

// التجديدات القريبة
$rStmt = $db->prepare("SELECT COUNT(*) FROM client_subscriptions WHERE status='active' AND end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)");
$rStmt->execute([$warningDays]);
$renewalsSoon = (int)$rStmt->fetchColumn();

$thisMonthRevenue = (float)$db->query("SELECT COALESCE(SUM(amount),0) FROM payments WHERE YEAR(payment_date)=YEAR(CURDATE()) AND MONTH(payment_date)=MONTH(CURDATE())")->fetchColumn();
$newClientsMonth  = (int)$db->query("SELECT COUNT(*) FROM clients WHERE YEAR(created_at)=YEAR(CURDATE()) AND MONTH(created_at)=MONTH(CURDATE())")->fetchColumn();

// إجمالي الاشتراكات النشطة
$activeSubs = (int)$db->query("SELECT COUNT(*) FROM client_subscriptions WHERE status = 'active'")->fetchColumn();

// عدد الدومينات المحجوزة من خلالنا (العملاء الذين لديهم دومين واشتروا خدمة حجز دومين)
$ourDomainsCount = (int)$db->query("
    SELECT COUNT(*) FROM client_subscriptions cs
    JOIN services s ON s.id = cs.service_id
    WHERE cs.status != 'cancelled'
      AND (s.name LIKE '%دومين%' OR s.name LIKE '%domain%')
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

// ── 2. آخر العملاء المضافين ──────────────────────────────────────────
$latestClients = $db->query("
    SELECT c.*, COALESCE(SUM(CASE WHEN cs.status!='cancelled' THEN cs.price ELSE 0 END),0) as total,
           COALESCE((SELECT SUM(p.amount) FROM payments p WHERE p.client_id=c.id),0) as paid
    FROM clients c LEFT JOIN client_subscriptions cs ON cs.client_id=c.id
    GROUP BY c.id ORDER BY c.created_at DESC LIMIT 6
")->fetchAll();

// ── 3. الاشتراكات المنتهية قريباً ───────────────────────────────
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

// ── 4. بيانات الرسم البياني للإيرادات (آخر 6 أشهر) ───────────────────
$chartData = [];
for ($i = 5; $i >= 0; $i--) {
    $date  = new DateTime("first day of -$i month");
    $year  = $date->format('Y');
    $month = $date->format('m');
    $stmt  = $db->prepare("SELECT COALESCE(SUM(amount),0) FROM payments WHERE YEAR(payment_date)=? AND MONTH(payment_date)=?");
    $stmt->execute([$year,$month]);
    $chartData[] = ['label' => $date->format('M'), 'value' => (float)$stmt->fetchColumn()];
}

// ── 5. الاشتراكات الجديدة شهرياً (آخر 12 شهر) ──────────────────────
$monthlyTrend = $db->query("
    SELECT DATE_FORMAT(start_date, '%Y-%m') as month_label, COUNT(*) as count 
    FROM client_subscriptions 
    WHERE start_date IS NOT NULL AND start_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
    GROUP BY month_label 
    ORDER BY month_label ASC
")->fetchAll();

// ── 6. توزيع الاشتراكات حسب الباقات والخدمات ────────────────────────
$packageDist = $db->query("
    SELECT COALESCE(cs.plan_name, 'بدون باقة مخصصة') as plan, COUNT(*) as count 
    FROM client_subscriptions cs
    JOIN services s ON s.id = cs.service_id
    WHERE cs.status != 'cancelled'
      AND (s.name NOT LIKE '%دومين%' AND s.name NOT LIKE '%domain%')
      AND cs.plan_name IS NOT NULL AND cs.plan_name != ''
    GROUP BY plan 
    ORDER BY count DESC
")->fetchAll();

$serviceDist = $db->query("
    SELECT s.name as service_name, COUNT(*) as count 
    FROM client_subscriptions cs
    JOIN services s ON s.id = cs.service_id
    WHERE cs.status != 'cancelled'
    GROUP BY s.id 
    ORDER BY count DESC
")->fetchAll();

// ── 7. قائمة العملاء الذين حجزنا الدومين لهم ───────────────────────
$ourDomainsClients = $db->query("
    SELECT cs.id AS sub_id, c.id, c.name, c.company_name, c.domain AS client_domain, c.domain_provider,
           cs.plan_name AS sub_plan_name, cs.notes AS sub_notes
    FROM client_subscriptions cs
    JOIN clients c ON c.id = cs.client_id
    JOIN services s ON s.id = cs.service_id
    WHERE cs.status != 'cancelled'
      AND (s.name LIKE '%دومين%' OR s.name LIKE '%domain%')
    ORDER BY c.name ASC
")->fetchAll();

$pageTitle  = 'لوحة التحكم';
$activePage = 'dashboard';
$depth      = 0;
require_once INCLUDES_PATH . '/header.php';
?>

<!-- stats-grid rows -->
<div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap:20px; margin-bottom:20px;">
  
  <!-- 👥 العملاء والاشتراكات -->
  <div class="card" style="border-right: 4px solid var(--primary); padding:20px; background:#fff; margin:0; display:flex; flex-direction:column; gap:12px; justify-content: space-between;">
    <h3 style="font-size:14px; font-weight:800; color:var(--primary); border-bottom:1px solid #f1f5f9; padding-bottom:8px; margin:0;">
      <i class="fas fa-users" style="margin-left:6px;"></i> العملاء والاشتراكات
    </h3>
    <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:10px; text-align:center;">
      <div>
        <div style="font-size:11px; color:var(--text-muted); font-weight:600;">إجمالي العملاء</div>
        <div style="font-size:16px; font-weight:800; color:var(--primary); margin-top:4px;"><?= number_format($totalClients) ?></div>
        <?php if ($newClientsMonth > 0): ?>
        <div style="font-size:10px; color:var(--success); margin-top:2px;"><i class="fas fa-plus"></i> <?= $newClientsMonth ?></div>
        <?php endif; ?>
      </div>
      <div>
        <div style="font-size:11px; color:var(--text-muted); font-weight:600;">العملاء النشطين</div>
        <div style="font-size:16px; font-weight:800; color:#10b981; margin-top:4px;"><?= number_format($activeClients) ?></div>
      </div>
      <div>
        <div style="font-size:11px; color:var(--text-muted); font-weight:600;">الاشتراكات النشطة</div>
        <div style="font-size:16px; font-weight:800; color:#8b5cf6; margin-top:4px;"><?= number_format($activeSubs) ?></div>
      </div>
    </div>
  </div>

  <!-- 💰 الموقف المالي -->
  <div class="card" style="border-right: 4px solid var(--success); padding:20px; background:#fff; margin:0; display:flex; flex-direction:column; gap:12px; justify-content: space-between;">
    <h3 style="font-size:14px; font-weight:800; color:var(--success); border-bottom:1px solid #f1f5f9; padding-bottom:8px; margin:0;">
      <i class="fas fa-coins" style="margin-left:6px;"></i> الموقف المالي العام
    </h3>
    <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:10px; text-align:center;">
      <div>
        <div style="font-size:11px; color:var(--text-muted); font-weight:600;">إجمالي الإيرادات</div>
        <div style="font-size:15px; font-weight:800; color:var(--success); margin-top:4px;"><?= formatMoney($totalRevenue) ?></div>
      </div>
      <div>
        <div style="font-size:11px; color:var(--text-muted); font-weight:600;">تحصيلات الشهر</div>
        <div style="font-size:15px; font-weight:800; color:#10b981; margin-top:4px;"><?= formatMoney($thisMonthRevenue) ?></div>
      </div>
      <div>
        <div style="font-size:11px; color:var(--text-muted); font-weight:600;">المبالغ المستحقة</div>
        <div style="font-size:15px; font-weight:800; color:var(--danger); margin-top:4px;"><?= formatMoney(max(0,$totalDebt)) ?></div>
      </div>
    </div>
  </div>

  <!-- ⚙️ التشغيل والمتابعة -->
  <div class="card" style="border-right: 4px solid var(--warning); padding:20px; background:#fff; margin:0; display:flex; flex-direction:column; gap:12px; justify-content: space-between;">
    <h3 style="font-size:14px; font-weight:800; color:var(--warning); border-bottom:1px solid #f1f5f9; padding-bottom:8px; margin:0;">
      <i class="fas fa-sliders" style="margin-left:6px;"></i> المتابعة والتشغيل
    </h3>
    <div style="display:grid; grid-template-columns:1fr 1fr 1.2fr; gap:10px; text-align:center;">
      <div>
        <div style="font-size:11px; color:var(--text-muted); font-weight:600;">تجديدات قريبة</div>
        <div style="font-size:16px; font-weight:800; color:var(--warning); margin-top:4px;"><?= $renewalsSoon ?></div>
      </div>
      <div>
        <div style="font-size:11px; color:var(--text-muted); font-weight:600;">دومينات محجوزة</div>
        <div style="font-size:16px; font-weight:800; color:#06b6d4; margin-top:4px;"><?= $ourDomainsCount ?></div>
      </div>
      <div>
        <div style="font-size:11px; color:var(--text-muted); font-weight:600;">تصميم المواقع</div>
        <div style="font-size:12px; font-weight:800; color:#3b82f6; margin-top:4px; line-height:1.2;"><?= $designPaid ?> مدفوع<br><?= $designFree ?> مجاني</div>
      </div>
    </div>
  </div>

</div>

<!-- Primary Dashboard Layout -->
<div style="display:grid;grid-template-columns:2fr 1fr;gap:20px;align-items:start;margin-bottom: 24px;">

  <!-- Column 1: Charts & Renewals -->
  <div>
    <!-- Interactive Charts Card -->
    <div class="card" style="margin-bottom:20px; display:flex; flex-direction:column;">
      <div class="card-header" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
        <div style="display:inline-flex; gap:6px; background:#f1f5f9; padding:4px; border-radius:8px;">
          <button type="button" id="btnRevenueChart" class="btn" style="font-size:12px; padding:6px 12px; font-weight:700; border-radius:6px; background:var(--primary); color:#fff; border:none; cursor:pointer;" onclick="switchDashboardChart('revenue')">
            <i class="fas fa-chart-bar" style="margin-left:4px;"></i> الإيرادات المحصلة شهرياً
          </button>
          <button type="button" id="btnTrendChart" class="btn" style="font-size:12px; padding:6px 12px; font-weight:700; border-radius:6px; background:transparent; color:var(--text-primary); border:none; cursor:pointer;" onclick="switchDashboardChart('trend')">
            <i class="fas fa-chart-line" style="margin-left:4px;"></i> نمو الاشتراكات الجديدة
          </button>
        </div>
      </div>
      <div class="card-body" id="revenueChartContainer">
        <div style="height:250px; position:relative;"><canvas id="revenueChart"></canvas></div>
      </div>
      <div class="card-body" id="trendChartContainer" style="display:none;">
        <div style="height:250px; position:relative;"><canvas id="monthlyTrendChart"></canvas></div>
      </div>
    </div>

    <!-- Upcoming Renewals -->
    <?php if (!empty($upcomingRenewals)): ?>
    <div class="card" style="margin-bottom: 20px;">
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

  <!-- Column 2: Package Distribution & Latest Clients -->
  <div>
    <!-- Package Distribution Chart -->
    <div class="card" style="margin-bottom: 20px;">
      <div class="card-header">
        <span class="card-title"><i class="fas fa-chart-pie"></i> توزيع باقات الاشتراكات</span>
      </div>
      <div class="card-body">
        <div style="max-height: 220px; position: relative; display: flex; justify-content: center;">
          <canvas id="packageDistChart"></canvas>
        </div>
      </div>
    </div>

    <!-- Latest Clients -->
    <div class="card">
      <div class="card-header">
        <span class="card-title"><i class="fas fa-user-clock"></i> آخر العملاء المضافين</span>
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

<!-- Breakdown Lists & Hosted Domains (Full Width Dashboard Tables) -->
<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; align-items: start; margin-bottom: 24px;">

  <!-- Service Stats -->
  <div class="card">
    <div class="card-header">
      <span class="card-title"><i class="fas fa-server"></i> إحصائيات الاشتراكات لكل خدمة</span>
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
          <?php foreach ($serviceDist as $sd): ?>
          <tr>
            <td><strong><?= e($sd['service_name']) ?></strong></td>
            <td style="text-align: center;"><span class="badge badge-primary"><?= $sd['count'] ?> اشتراك</span></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Package Stats -->
  <div class="card">
    <div class="card-header">
      <span class="card-title"><i class="fas fa-box-open"></i> تفاصيل توزيع الباقات المعتمدة</span>
    </div>
    <div class="card-body" style="padding: 0;">
      <table class="data-table">
        <thead>
          <tr>
            <th>اسم الباقة</th>
            <th style="text-align: center;">عدد المشتركين</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($packageDist as $pd): ?>
          <tr onclick="window.location='clients/index.php?plan=<?= urlencode($pd['plan']) ?>';" style="cursor:pointer;" onmouseover="this.style.background='#f8fbff'" onmouseout="this.style.background=''">
            <td>
              <a href="clients/index.php?plan=<?= urlencode($pd['plan']) ?>" style="font-weight: 700; color: var(--primary);">
                <?= e($pd['plan']) ?>
              </a>
            </td>
            <td style="text-align: center;"><span class="badge badge-info"><?= $pd['count'] ?> عميل</span></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>

<!-- Hosted Domains Client List -->
<div class="card" style="margin-bottom: 24px;">
  <div class="card-header" style="display:flex; justify-content:space-between; align-items:center;">
    <span class="card-title"><i class="fas fa-globe-americas"></i> العملاء الذين تم حجز النطاق (الدومين) لهم من خلالنا (إجمالي: <?= count($ourDomainsClients) ?> نطاق/دومين)</span>
    <button type="button" class="btn btn-outline btn-sm" id="btnToggleDomainsTable" onclick="toggleDomainsTable()" style="font-weight:700; font-size:12px; padding:6px 12px; border-radius:6px; cursor:pointer;">
      <i class="fas fa-eye"></i> عرض القائمة
    </button>
  </div>
  <div class="table-wrapper" id="domainsTableContainer" style="display:none;">
    <table class="data-table">
      <thead>
        <tr>
          <th>#</th>
          <th>اسم العميل</th>
          <th>اسم الشركة</th>
          <th>نطاق الموقع (الدومين)</th>
          <th>مسجل الدومين والـ Provider</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($ourDomainsClients)): ?>
        <tr><td colspan="5"><div class="empty-state">لا يوجد عملاء لديهم دومينات محجوزة من طرفنا حالياً</div></td></tr>
        <?php else: ?>
        <?php foreach ($ourDomainsClients as $index => $c): 
          $domainToDisplay = '';
          if (strpos($c['sub_plan_name'], '.') !== false) {
              $domainToDisplay = $c['sub_plan_name'];
          } elseif (strpos($c['sub_notes'], '.') !== false) {
              $domainToDisplay = $c['sub_notes'];
          } else {
              $domainToDisplay = $c['client_domain'];
          }
        ?>
        <tr>
          <td class="text-muted"><?= $index + 1 ?></td>
          <td>
            <a href="clients/view.php?id=<?= $c['id'] ?>" style="font-weight: 700;">
              <?= e($c['name']) ?>
            </a>
          </td>
          <td><?= e($c['company_name'] ?: '—') ?></td>
          <td>
            <?php if ($domainToDisplay): ?>
            <a href="http://<?= e($domainToDisplay) ?>" target="_blank" style="color: var(--primary); font-weight: 600;">
              <i class="fas fa-external-link-alt" style="font-size: 11px; margin-left: 4px;"></i>
              <?= e($domainToDisplay) ?>
            </a>
            <?php else: ?>
            <span class="text-muted">—</span>
            <?php endif; ?>
          </td>
          <td><span class="badge badge-success"><?= e($c['domain_provider'] ?: '—') ?></span></td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
function switchDashboardChart(type) {
    const revContainer = document.getElementById('revenueChartContainer');
    const trendContainer = document.getElementById('trendChartContainer');
    const btnRev = document.getElementById('btnRevenueChart');
    const btnTrend = document.getElementById('btnTrendChart');
    
    if (type === 'revenue') {
        if (revContainer) revContainer.style.display = 'block';
        if (trendContainer) trendContainer.style.display = 'none';
        if (btnRev) {
            btnRev.style.background = 'var(--primary)';
            btnRev.style.color = '#fff';
        }
        if (btnTrend) {
            btnTrend.style.background = 'transparent';
            btnTrend.style.color = 'var(--text-primary)';
        }
    } else {
        if (revContainer) revContainer.style.display = 'none';
        if (trendContainer) trendContainer.style.display = 'block';
        if (btnRev) {
            btnRev.style.background = 'transparent';
            btnRev.style.color = 'var(--text-primary)';
        }
        if (btnTrend) {
            btnTrend.style.background = 'var(--primary)';
            btnTrend.style.color = '#fff';
        }
    }
}

function toggleDomainsTable() {
    const container = document.getElementById('domainsTableContainer');
    const btn = document.getElementById('btnToggleDomainsTable');
    if (container) {
        if (container.style.display === 'none') {
            container.style.display = 'block';
            if (btn) btn.innerHTML = '<i class="fas fa-eye-slash"></i> إخفاء القائمة';
        } else {
            container.style.display = 'none';
            if (btn) btn.innerHTML = '<i class="fas fa-eye"></i> عرض القائمة';
        }
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // 1. Chart: Monthly Revenue (6 Months)
    const revCtx = document.getElementById('revenueChart');
    if (revCtx) {
      new Chart(revCtx, {
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

    // 2. Chart: Monthly Growth Trend
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

    // 3. Chart: Package Distribution
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
