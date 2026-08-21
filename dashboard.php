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

// ── 7. توزيع العملاء حسب الدولة والخدمات والسيرفر ────────────────────────
$countryFilterStatus = $_GET['country_status'] ?? '1'; // '1' = active (default), '0' = suspended, 'all' = all
$countryFilterServer = clean($_GET['country_server'] ?? '');

$availableServers = $db->query("
    SELECT DISTINCT server_panel 
    FROM clients 
    WHERE server_panel IS NOT NULL AND server_panel != '' 
    ORDER BY server_panel ASC
")->fetchAll(PDO::FETCH_COLUMN);

if (empty($availableServers)) {
    $availableServers = ['cp.enjaz.cloud', 'panel.enjaz.cloud'];
}

$countryStatusWhere = "";
if ($countryFilterStatus === '1') {
    $countryStatusWhere = " AND c.status = 1 ";
} elseif ($countryFilterStatus === '0') {
    $countryStatusWhere = " AND c.status = 0 ";
}

$countryServerWhere = "";
if ($countryFilterServer !== '') {
    $countryServerWhere = " AND c.server_panel = " . $db->quote($countryFilterServer);
}

$countryCombinedWhere = " WHERE 1=1 " . $countryStatusWhere . $countryServerWhere;

$countryOverview = $db->query("
    SELECT c.country,
           COUNT(DISTINCT c.id) AS total_clients,
           COUNT(DISTINCT CASE WHEN c.status = 1 THEN c.id END) AS active_clients,
           COUNT(DISTINCT CASE WHEN c.status = 0 THEN c.id END) AS suspended_clients,
           COALESCE(SUM(CASE WHEN cs.status != 'cancelled' THEN cs.price ELSE 0 END), 0) AS total_revenue,
           COUNT(DISTINCT CASE WHEN cs.status != 'cancelled' AND (s.name LIKE '%دومين%' OR s.name LIKE '%domain%') THEN cs.id END) AS our_domains_count
    FROM clients c
    LEFT JOIN client_subscriptions cs ON cs.client_id = c.id
    LEFT JOIN services s ON s.id = cs.service_id
    $countryCombinedWhere
    GROUP BY c.country
    ORDER BY total_clients DESC
")->fetchAll();

$countryServiceStats = $db->query("
    SELECT c.country, s.id AS service_id, s.name AS service_name,
           COUNT(cs.id) AS subs_count,
           SUM(cs.price) AS total_price,
           COUNT(DISTINCT CASE WHEN (s.name LIKE '%دومين%' OR s.name LIKE '%domain%') THEN cs.id END) AS our_domains_in_service
    FROM clients c
    JOIN client_subscriptions cs ON cs.client_id = c.id
    JOIN services s ON s.id = cs.service_id
    WHERE cs.status != 'cancelled' $countryStatusWhere $countryServerWhere
    GROUP BY c.country, s.id
    ORDER BY total_price DESC
")->fetchAll();

$countryServicesMap = [];
foreach ($countryServiceStats as $css) {
    $cCode = $css['country'] ?: 'EG';
    $countryServicesMap[$cCode][] = $css;
}

// ── إجماليات الفلتر الحالي للفوتر ────────────────────────
$totalFilteredClients   = array_sum(array_column($countryOverview, 'total_clients'));
$totalFilteredActive    = array_sum(array_column($countryOverview, 'active_clients'));
$totalFilteredSuspended = array_sum(array_column($countryOverview, 'suspended_clients'));
$totalFilteredRevenue   = array_sum(array_column($countryOverview, 'total_revenue'));
$totalFilteredOurDomains= array_sum(array_column($countryOverview, 'our_domains_count'));

$filteredServiceTotals = $db->query("
    SELECT s.id AS service_id, s.name AS service_name,
           COUNT(cs.id) AS total_subs,
           COALESCE(SUM(cs.price), 0) AS total_amount,
           COUNT(DISTINCT CASE WHEN (s.name LIKE '%دومين%' OR s.name LIKE '%domain%') THEN cs.id END) AS our_domains_count
    FROM client_subscriptions cs
    JOIN clients c ON c.id = cs.client_id
    JOIN services s ON s.id = cs.service_id
    WHERE cs.status != 'cancelled' $countryStatusWhere $countryServerWhere
    GROUP BY s.id
    ORDER BY total_amount DESC
")->fetchAll();

// ── 8. قائمة العملاء الذين حجزنا الدومين لهم ───────────────────────
$ourDomainsClients = $db->query("
    SELECT cs.id AS sub_id, c.id, c.name, c.company_name, c.country, c.domain AS client_domain, c.domain_provider,
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

<style>
.dash-kpi-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 20px;
  margin-bottom: 24px;
}
.dash-main-grid {
  display: grid;
  grid-template-columns: 2fr 1fr;
  gap: 20px;
  align-items: start;
  margin-bottom: 24px;
}
.dash-equal-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 20px;
  align-items: start;
  margin-bottom: 24px;
}
@media (max-width: 992px) {
  .dash-main-grid {
    grid-template-columns: 1fr;
  }
  .dash-equal-grid {
    grid-template-columns: 1fr;
  }
}
</style>

<?php
$makeCountryDashUrl = function($newStatus, $newServer) use ($countryFilterStatus, $countryFilterServer) {
    $st = $newStatus !== null ? $newStatus : $countryFilterStatus;
    $sv = $newServer !== null ? $newServer : $countryFilterServer;
    $params = [];
    if ($st !== '1') $params['country_status'] = $st;
    if ($sv !== '') $params['country_server'] = $sv;
    $qs = http_build_query($params);
    return '?' . ($qs ? $qs : '');
};
?>

<!-- ══════════════════════════════════════════════════════════════════════════ -->
<!-- 1. بطاقة تقرير توزيع العملاء والخدمات حسب الدولة والسيرفر (في المقدمة) -->
<!-- ══════════════════════════════════════════════════════════════════════════ -->
<div class="card" style="margin-bottom: 24px; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 16px rgba(0,0,0,0.03);">
  <div class="card-header" style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 14px; padding: 16px 20px; background: var(--card-bg); border-bottom: 1px solid var(--border-color);">
    <div>
      <span class="card-title" style="font-size: 16px; font-weight: 800; display: flex; align-items: center; gap: 8px;">
        <i class="fas fa-globe-americas" style="color: var(--primary);"></i>
        <span>تقرير توزيع العملاء والخدمات حسب الدولة والسيرفر</span>
      </span>
      <p style="font-size: 12px; color: var(--text-muted); margin: 3px 0 0 0;">اضغط على الدولة لعرض تفاصيل الخدمات والتكاليف وعدد الدومينات المحجوزة من خلالنا</p>
    </div>

    <!-- أدوات الفلترة: السيرفر + حالة العملاء -->
    <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
      
      <!-- فلتر السيرفر -->
      <div style="display: inline-flex; align-items: center; gap: 6px;">
        <label style="font-size: 12px; font-weight: 700; color: var(--text-muted); display: flex; align-items: center; gap: 4px;">
          <i class="fas fa-server" style="color: var(--primary);"></i> السيرفر:
        </label>
        <select onchange="window.location=this.value" class="form-control" style="font-size: 12px; padding: 5px 10px; height: auto; width: auto; font-weight: 700; border-radius: 8px; cursor: pointer; border-color: rgba(36,86,164,0.2);">
          <option value="<?= $makeCountryDashUrl(null, '') ?>" <?= $countryFilterServer === '' ? 'selected' : '' ?>>🖥️ كل السيرفرات</option>
          <?php foreach ($availableServers as $srv): 
            $srvLabel = $srv;
            if ($srv === 'cp.enjaz.cloud') $srvLabel = 'السيرفر الأول (cp)';
            elseif ($srv === 'panel.enjaz.cloud') $srvLabel = 'السيرفر الثاني (panel)';
          ?>
          <option value="<?= $makeCountryDashUrl(null, $srv) ?>" <?= $countryFilterServer === $srv ? 'selected' : '' ?>>
            🖥️ <?= e($srvLabel) ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- أزرار فلترة حالة العملاء -->
      <div style="display: inline-flex; background: rgba(36,86,164,0.06); padding: 3px; border-radius: 8px; gap: 4px;">
        <a href="<?= $makeCountryDashUrl('1', null) ?>" 
           class="btn btn-sm" 
           style="font-size: 12px; padding: 4px 12px; border-radius: 6px; border: none; font-weight: 700; text-decoration: none; <?= $countryFilterStatus === '1' ? 'background: var(--success); color: #fff; box-shadow: 0 2px 6px rgba(16,185,129,0.3);' : 'background: transparent; color: var(--text-muted);' ?>">
          <i class="fas fa-circle-check" style="margin-left: 4px;"></i> النشطون
        </a>
        <a href="<?= $makeCountryDashUrl('0', null) ?>" 
           class="btn btn-sm" 
           style="font-size: 12px; padding: 4px 12px; border-radius: 6px; border: none; font-weight: 700; text-decoration: none; <?= $countryFilterStatus === '0' ? 'background: var(--danger); color: #fff; box-shadow: 0 2px 6px rgba(239,68,68,0.3);' : 'background: transparent; color: var(--text-muted);' ?>">
          <i class="fas fa-circle-pause" style="margin-left: 4px;"></i> الموقوفون
        </a>
        <a href="<?= $makeCountryDashUrl('all', null) ?>" 
           class="btn btn-sm" 
           style="font-size: 12px; padding: 4px 12px; border-radius: 6px; border: none; font-weight: 700; text-decoration: none; <?= $countryFilterStatus === 'all' ? 'background: var(--primary); color: #fff; box-shadow: 0 2px 6px rgba(36,86,164,0.3);' : 'background: transparent; color: var(--text-muted);' ?>">
          <i class="fas fa-globe" style="margin-left: 4px;"></i> كل العملاء
        </a>
      </div>

    </div>
  </div>

  <div class="card-body" style="padding: 0;">
    <?php if (empty($countryOverview)): ?>
    <div class="empty-state" style="padding: 40px;">
      <div class="empty-icon"><i class="fas fa-users-slash"></i></div>
      <p class="empty-title">لا يوجد عملاء يطابقون الفلاتر المحددة</p>
      <p style="font-size: 12px; color: var(--text-muted); margin-top: 4px;">جرب تغيير اختيار السيرفر أو حالة العميل</p>
    </div>
    <?php else: ?>
    <div class="country-accordion-list">
      <?php foreach ($countryOverview as $index => $cs): 
        $cCode = $cs['country'] ?: 'EG';
        $cInfo = getCountryInfo($cCode);
        $services = $countryServicesMap[$cCode] ?? [];
        $accordionId = 'country-detail-' . $cCode;
      ?>
      <div class="country-accordion-item" style="border-bottom: 1px solid var(--border-color);">
        <!-- Accordion Header -->
        <div class="country-accordion-header" 
             onclick="toggleCountryAccordion('<?= $accordionId ?>')"
             style="display: flex; align-items: center; justify-content: space-between; padding: 14px 20px; cursor: pointer; background: var(--card-bg); transition: background 0.2s;"
             onmouseover="this.style.background='rgba(36,86,164,0.03)'" 
             onmouseout="this.style.background='var(--card-bg)'">
          
          <!-- الدولة والعلم -->
          <div style="display: flex; align-items: center; gap: 12px; min-width: 200px;">
            <div style="width: 38px; height: 38px; border-radius: 10px; background: rgba(36,86,164,0.06); border: 1px solid rgba(36,86,164,0.12); display: flex; align-items: center; justify-content: center; flex-shrink: 0; box-shadow: 0 2px 4px rgba(0,0,0,0.02);">
              <?= getCountryFlagSvg($cCode, 24, 16) ?>
            </div>
            <div>
              <div style="font-size: 15px; font-weight: 800; color: var(--text-primary); display: flex; align-items: center; gap: 8px;">
                <?= e($cInfo['name']) ?>
                <span class="badge" style="background: rgba(36,86,164,0.08); color: var(--primary); font-size: 11px; padding: 2px 8px; border-radius: 20px;">
                  <?= $cs['total_clients'] ?> <?= $cs['total_clients'] > 1 ? 'عملاء' : 'عميل' ?>
                </span>
                <?php if ($countryFilterServer !== ''): ?>
                <span class="badge" style="background: #e0f2fe; color: #0284c7; font-size: 10.5px; padding: 2px 6px; border-radius: 6px;">
                  <?= e($countryFilterServer) ?>
                </span>
                <?php endif; ?>
              </div>
              <div style="font-size: 11.5px; color: var(--text-muted); margin-top: 2px;">
                <span>نشط: <strong style="color: var(--success);"><?= $cs['active_clients'] ?></strong></span> &bull; 
                <span>موقوف: <strong style="color: var(--danger);"><?= $cs['suspended_clients'] ?></strong></span>
              </div>
            </div>
          </div>

          <!-- إحصائيات الدومينات والتكاليف -->
          <div style="display: flex; align-items: center; gap: 16px; flex-wrap: wrap;">
            
            <!-- بادج الدومينات المحجوزة من خلالنا -->
            <?php if ($cs['our_domains_count'] > 0): ?>
            <div style="background: rgba(16, 185, 129, 0.08); border: 1px solid rgba(16, 185, 129, 0.2); padding: 4px 10px; border-radius: 6px; font-size: 12px; color: var(--success); font-weight: 700; display: inline-flex; align-items: center; gap: 5px;" title="عدد النطاقات المحجوزة لعملاء هذه الدولة من طرف شركتنا">
              <i class="fas fa-globe"></i>
              <span><?= $cs['our_domains_count'] ?> دومين حجزناه</span>
            </div>
            <?php else: ?>
            <div style="background: rgba(0, 0, 0, 0.03); border: 1px solid var(--border-color); padding: 4px 10px; border-radius: 6px; font-size: 11.5px; color: var(--text-muted); font-weight: 600; display: inline-flex; align-items: center; gap: 5px;">
              <i class="fas fa-globe" style="opacity: 0.5;"></i>
              <span>لا توجد دومينات من خلالنا</span>
            </div>
            <?php endif; ?>

            <!-- إجمالي التكلفة / القيمة بالجنيه -->
            <div style="text-align: left; min-width: 140px;">
              <div style="font-size: 14.5px; font-weight: 900; color: var(--primary);">
                <?= formatMoney($cs['total_revenue']) ?>
              </div>
              <div style="font-size: 11px; color: var(--text-muted);">إجمالي قيمة الاشتراكات</div>
            </div>

            <!-- زر وسهم التوسيع -->
            <button type="button" class="btn btn-sm btn-outline" style="border-radius: 50%; width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center;">
              <i class="fas fa-chevron-down" id="arrow-<?= $accordionId ?>" style="transition: transform 0.25s;"></i>
            </button>
          </div>
        </div>

        <!-- Accordion Body: تفاصيل الخدمات وكل خدمة بتكلفتها -->
        <div id="<?= $accordionId ?>" style="display: none; background: rgba(248, 250, 252, 0.7); border-top: 1px dashed var(--border-color); padding: 16px 20px;">
          
          <div style="margin-bottom: 12px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px;">
            <div style="font-size: 13px; font-weight: 800; color: var(--text-primary); display: flex; align-items: center; gap: 6px;">
              <i class="fas fa-list-check" style="color: var(--primary-light);"></i>
              <span>تفاصيل الخدمات والاشتراكات لعملاء <?= e($cInfo['name']) ?> (<?= count($services) ?> خدمات مسجلة):</span>
            </div>
            <a href="clients/index.php?country=<?= urlencode($cCode) ?>&status=<?= $countryFilterStatus === 'all' ? '' : $countryFilterStatus ?>&server=<?= urlencode($countryFilterServer) ?>" 
               class="btn btn-sm btn-primary" 
               style="font-size: 11.5px; padding: 3px 10px; border-radius: 6px;">
              <i class="fas fa-users"></i> عرض عملاء <?= e($cInfo['name']) ?> (<?= $cs['total_clients'] ?>) في جدول العملاء &larr;
            </a>
          </div>

          <?php if (empty($services)): ?>
          <div style="padding: 16px; text-align: center; color: var(--text-muted); font-size: 12.5px; background: var(--card-bg); border-radius: 8px; border: 1px solid var(--border-color);">
            لا توجد اشتراكات نشطة مسجلة لعملاء هذه الدولة حالياً.
          </div>
          <?php else: ?>
          <div class="table-wrapper" style="margin: 0; box-shadow: none; border: 1px solid var(--border-color); border-radius: 8px; overflow: hidden; background: var(--card-bg);">
            <table class="data-table" style="margin: 0; font-size: 12.5px; width: 100%;">
              <thead>
                <tr style="background-color: var(--bg-hover);">
                  <th style="width: 40px;">#</th>
                  <th>الخدمة</th>
                  <th style="text-align: center;">عدد المشتركين</th>
                  <th style="text-align: left;">إجمالي التكلفة / الإيراد</th>
                  <th style="text-align: center;">ملاحظات ونطاقات</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($services as $sIdx => $srv): 
                  $isDomainSrv = (mb_strpos(mb_strtolower($srv['service_name']), 'دومين') !== false || mb_strpos(mb_strtolower($srv['service_name']), 'domain') !== false);
                ?>
                <tr>
                  <td style="color: var(--text-muted);"><?= $sIdx + 1 ?></td>
                  <td>
                    <div style="font-weight: 700; color: var(--text-primary); display: flex; align-items: center; gap: 8px;">
                      <i class="fas <?= $isDomainSrv ? 'fa-globe text-info' : 'fa-check-circle text-primary' ?>"></i>
                      <span><?= e($srv['service_name']) ?></span>
                    </div>
                  </td>
                  <td style="text-align: center;">
                    <span class="badge badge-primary" style="font-size: 11.5px; padding: 2px 8px;">
                      <?= $srv['subs_count'] ?> <?= $srv['subs_count'] > 1 ? 'اشتراكات' : 'اشتراك' ?>
                    </span>
                  </td>
                  <td style="text-align: left; font-weight: 800; color: var(--success); font-size: 13.5px;">
                    <?= formatMoney($srv['total_price']) ?>
                  </td>
                  <td style="text-align: center;">
                    <?php if ($isDomainSrv): ?>
                      <?php if ($srv['our_domains_in_service'] > 0): ?>
                        <span class="badge badge-success" style="font-size: 11px; padding: 3px 8px;">
                          <i class="fas fa-check-double" style="margin-left: 3px;"></i> حجزنا منهم <?= $srv['our_domains_in_service'] ?> دومين
                        </span>
                      <?php else: ?>
                        <span class="text-muted" style="font-size: 11.5px;">دومينات مدارة</span>
                      <?php endif; ?>
                    <?php else: ?>
                      <span class="text-muted" style="font-size: 11.5px;">—</span>
                    <?php endif; ?>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <?php endif; ?>

        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>

  <!-- ══════════════════════════════════════════════════════════════ -->
  <!-- فوتر البطاقة: الإجماليات الشاملة حسب الفلترة الحالية -->
  <!-- ══════════════════════════════════════════════════════════════ -->
  <?php if (!empty($countryOverview)): ?>
  <div class="card-footer" style="padding: 16px 20px; background: rgba(36,86,164,0.03); border-top: 2px solid var(--border-color);">
    
    <!-- شريط الإجماليات الرئيسية -->
    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 14px; margin-bottom: 14px; padding-bottom: 14px; border-bottom: 1px dashed var(--border-color);">
      
      <div style="display: flex; align-items: center; gap: 18px; flex-wrap: wrap;">
        <!-- إجمالي العملاء -->
        <div style="display: flex; align-items: center; gap: 8px;">
          <div style="width: 34px; height: 34px; border-radius: 8px; background: rgba(36,86,164,0.1); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 14px;">
            <i class="fas fa-users"></i>
          </div>
          <div>
            <div style="font-size: 11px; color: var(--text-muted); font-weight: 700;">إجمالي العملاء:</div>
            <div style="font-size: 14.5px; font-weight: 900; color: var(--text-primary);">
              <?= $totalFilteredClients ?> عميل
              <span style="font-size: 11.5px; font-weight: normal; color: var(--text-muted);">
                ( <span style="color: var(--success); font-weight: 700;"><?= $totalFilteredActive ?> نشط</span> &bull; <span style="color: var(--danger); font-weight: 700;"><?= $totalFilteredSuspended ?> موقوف</span> )
              </span>
            </div>
          </div>
        </div>

        <!-- إجمالي الدومينات المحجوزة من خلالنا -->
        <div style="display: flex; align-items: center; gap: 8px;">
          <div style="width: 34px; height: 34px; border-radius: 8px; background: rgba(16,185,129,0.1); color: var(--success); display: flex; align-items: center; justify-content: center; font-size: 14px;">
            <i class="fas fa-globe"></i>
          </div>
          <div>
            <div style="font-size: 11px; color: var(--text-muted); font-weight: 700;">دومينات حجزناها نحن:</div>
            <div style="font-size: 14.5px; font-weight: 900; color: var(--success);">
              <?= $totalFilteredOurDomains ?> دومين
            </div>
          </div>
        </div>
      </div>

      <!-- إجمالي قيمة الاشتراكات -->
      <div style="display: flex; align-items: center; gap: 8px;">
        <div style="width: 34px; height: 34px; border-radius: 8px; background: rgba(36,86,164,0.1); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 14px;">
          <i class="fas fa-coins"></i>
        </div>
        <div style="text-align: left;">
          <div style="font-size: 11px; color: var(--text-muted); font-weight: 700;">إجمالي قيمة الاشتراكات:</div>
          <div style="font-size: 16px; font-weight: 900; color: var(--primary);">
            <?= formatMoney($totalFilteredRevenue) ?>
          </div>
        </div>
      </div>

    </div>

    <!-- تفصيل إجماليات كل خدمة حسب الفلترة الحالية -->
    <?php if (!empty($filteredServiceTotals)): ?>
    <div>
      <div style="font-size: 12px; font-weight: 800; color: var(--text-muted); margin-bottom: 8px; display: flex; align-items: center; gap: 6px;">
        <i class="fas fa-layer-group" style="color: var(--primary-light);"></i>
        <span>إجمالي الاشتراكات والتكاليف لكل خدمة (حسب الفلتر الحالي):</span>
      </div>
      <div style="display: flex; flex-wrap: wrap; gap: 8px;">
        <?php foreach ($filteredServiceTotals as $fst): 
          $isDom = (mb_strpos(mb_strtolower($fst['service_name']), 'دومين') !== false || mb_strpos(mb_strtolower($fst['service_name']), 'domain') !== false);
        ?>
        <div style="background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 8px; padding: 6px 12px; font-size: 12px; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.02);">
          <span style="font-weight: 700; color: var(--text-primary);">
            <i class="fas <?= $isDom ? 'fa-globe text-info' : 'fa-check-circle text-primary' ?>" style="margin-left: 4px;"></i>
            <?= e($fst['service_name']) ?>:
          </span>
          <span class="badge badge-primary" style="font-size: 11px; padding: 2px 7px;">
            <?= $fst['total_subs'] ?> <?= $fst['total_subs'] > 1 ? 'اشتراكات' : 'اشتراك' ?>
          </span>
          <span style="font-weight: 800; color: var(--success);">
            <?= formatMoney($fst['total_amount']) ?>
          </span>
          <?php if ($isDom && $fst['our_domains_count'] > 0): ?>
          <span class="badge badge-success" style="font-size: 10.5px; padding: 2px 6px;">
            (<?= $fst['our_domains_count'] ?> حجزناهم)
          </span>
          <?php endif; ?>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

  </div>
  <?php endif; ?>
</div>

<script>
function toggleCountryAccordion(id) {
  const panel = document.getElementById(id);
  const arrow = document.getElementById('arrow-' + id);
  if (!panel) return;
  
  if (panel.style.display === 'none' || panel.style.display === '') {
    panel.style.display = 'block';
    if (arrow) arrow.style.transform = 'rotate(180deg)';
  } else {
    panel.style.display = 'none';
    if (arrow) arrow.style.transform = 'rotate(0deg)';
  }
}
</script>

<!-- ══════════════════════════════════════════════════════════════════════════ -->
<!-- 2. كروت الإحصائيات السريعة (KPI Overview Cards) -->
<!-- ══════════════════════════════════════════════════════════════════════════ -->
<div class="dash-kpi-grid">
  
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

<!-- ══════════════════════════════════════════════════════════════════════════ -->
<!-- 3. الرسوم البيانية التفاعلية وقائمة آخر العملاء -->
<!-- ══════════════════════════════════════════════════════════════════════════ -->
<div class="dash-main-grid">

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

  <!-- Column 2: Latest Clients -->
  <div>
    <div class="card">
      <div class="card-header">
        <span class="card-title"><i class="fas fa-user-clock"></i> آخر العملاء المضافين</span>
        <a href="clients/index.php" class="btn btn-sm btn-outline">عرض الكل</a>
      </div>
      <div class="card-body" style="padding:0;">
        <?php foreach ($latestClients as $cl):
          $remaining = $cl['total'] - $cl['paid'];
          $cCode = $cl['country'] ?? 'EG';
          $cInfo = getCountryInfo($cCode);
        ?>
        <div style="display:flex;align-items:center;gap:12px;padding:14px 18px;border-bottom:1px solid #f1f5f9;transition:.15s;"
             onmouseover="this.style.background='#f8fbff'" onmouseout="this.style.background=''">
          <div style="width:38px;height:38px;border-radius:10px;background:rgba(36,86,164,0.05);border:1px solid rgba(36,86,164,0.12);
                      display:flex;align-items:center;justify-content:center;flex-shrink:0;box-shadow:0 2px 4px rgba(0,0,0,0.02);"
               title="<?= e($cInfo['name']) ?>">
            <?= getCountryFlagSvg($cCode, 24, 16) ?>
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

<!-- ══════════════════════════════════════════════════════════════════════════ -->
<!-- 4. إحصائيات الخدمات وتوزيع الباقات -->
<!-- ══════════════════════════════════════════════════════════════════════════ -->
<div class="dash-equal-grid">

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
            <div style="display:flex;align-items:center;gap:8px;">
              <?= getCountryFlagSvg($c['country'] ?? 'EG', 20, 14) ?>
              <a href="clients/view.php?id=<?= $c['id'] ?>" style="font-weight: 700;">
                <?= e($c['name']) ?>
              </a>
            </div>
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
