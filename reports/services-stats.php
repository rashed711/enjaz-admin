<?php
/**
 * reports/services-stats.php - تقرير احصائيات الخدمات الشهري المتقدم
 */
require_once dirname(__DIR__) . '/config/app.php';
requireLogin();
requirePermission('view_reports');

$db          = getDB();
$year        = (int)($_GET['year'] ?? date('Y'));
$clientStatus = $_GET['client_status'] ?? 'active';

$country      = clean($_GET['country'] ?? '');

$arabicMonths = [
    1=>'يناير',2=>'فبراير',3=>'مارس',4=>'ابريل',5=>'مايو',6=>'يونيو',
    7=>'يوليو',8=>'اغسطس',9=>'سبتمبر',10=>'اكتوبر',11=>'نوفمبر',12=>'ديسمبر',
];

function clientStatusWhere(string $alias='c'): array {
    global $clientStatus;
    if ($clientStatus==='active')    return ["{$alias}.status=1"];
    if ($clientStatus==='suspended') return ["{$alias}.status=0"];
    return [];
}

$servicesStmt = $db->query("SELECT id, name FROM services WHERE status=1 ORDER BY sort_order ASC, id ASC");
$services = $servicesStmt->fetchAll();
$serviceColors = ['#3b82f6','#f59e0b','#22c55e','#a855f7','#ef4444','#06b6d4'];
$serviceIconMap = ['دومين'=>'fa-globe','ايميل'=>'fa-envelope','بريد'=>'fa-envelope','موقع'=>'fa-laptop-code'];
$getServiceIcon = function(string $n) use ($serviceIconMap): string {
    foreach ($serviceIconMap as $k=>$v) { if (mb_strpos($n,$k)!==false) return $v; }
    return 'fa-concierge-bell';
};

$primaryServiceId=null;$primaryServiceName='';
foreach($services as $svc){
    $nm=mb_strtolower($svc['name']);
    if(mb_strpos($nm,'بريد')!==false||mb_strpos($nm,'ايميل')!==false||mb_strpos($nm,'email')!==false||mb_strpos($nm,'mail')!==false){
        $primaryServiceId=(int)$svc['id'];$primaryServiceName=$svc['name'];break;
    }
}
if(!$primaryServiceId&&!empty($services)){$primaryServiceId=(int)$services[0]['id'];$primaryServiceName=$services[0]['name'];}

$statusJoin  = "JOIN clients c ON c.id=cs.client_id";
$statusConds = clientStatusWhere('c');
if ($country !== '') {
    $statusConds[] = "c.country = " . $db->quote($country);
}
$statusSql   = $statusConds ? ' AND '.implode(' AND ',$statusConds) : '';

$subsStmt=$db->prepare("
    SELECT MONTH(cs.start_date) AS month,cs.service_id,
           COUNT(DISTINCT cs.client_id) AS clients_count,SUM(cs.price) AS revenue
    FROM client_subscriptions cs $statusJoin
    WHERE YEAR(cs.start_date)=? AND cs.status!='cancelled' AND cs.start_date IS NOT NULL $statusSql
    GROUP BY MONTH(cs.start_date),cs.service_id");
$subsStmt->execute([$year]);
$subsByMonthService=[];
foreach($subsStmt->fetchAll() as $r)
    $subsByMonthService[(int)$r['month']][(int)$r['service_id']]=['clients_count'=>(int)$r['clients_count'],'revenue'=>(float)$r['revenue']];

if ($country !== '') {
    $payStmt=$db->prepare("SELECT MONTH(p.payment_date) AS month,SUM(p.amount) AS collected FROM payments p JOIN clients c ON c.id=p.client_id WHERE YEAR(p.payment_date)=? AND c.country=? GROUP BY MONTH(p.payment_date)");
    $payStmt->execute([$year, $country]);
} else {
    $payStmt=$db->prepare("SELECT MONTH(payment_date) AS month,SUM(amount) AS collected FROM payments WHERE YEAR(payment_date)=? GROUP BY MONTH(payment_date)");
    $payStmt->execute([$year]);
}
$paymentsByMonth=[];
foreach($payStmt->fetchAll() as $r)$paymentsByMonth[(int)$r['month']]=(float)$r['collected'];

$expStmt=$db->prepare("SELECT MONTH(expense_date) AS month,SUM(amount) AS total FROM expenses WHERE YEAR(expense_date)=? GROUP BY MONTH(expense_date)");
$expStmt->execute([$year]);$expensesByMonth=[];
foreach($expStmt->fetchAll() as $r)$expensesByMonth[(int)$r['month']]=(float)$r['total'];

$svcTotStmt=$db->prepare("
    SELECT cs.service_id,COUNT(DISTINCT cs.client_id) AS total_clients,
           COUNT(DISTINCT CASE WHEN c.status=1 THEN cs.client_id END) AS active_clients,SUM(cs.price) AS total_revenue
    FROM client_subscriptions cs JOIN clients c ON c.id=cs.client_id
    WHERE YEAR(cs.start_date)=? AND cs.status!='cancelled' AND cs.start_date IS NOT NULL $statusSql
    GROUP BY cs.service_id");
$svcTotStmt->execute([$year]);$serviceTotals=[];
foreach($svcTotStmt->fetchAll() as $r)$serviceTotals[(int)$r['service_id']]=$r;

$allSubsRevStmt=$db->prepare("SELECT SUM(cs.price) FROM client_subscriptions cs $statusJoin WHERE YEAR(cs.start_date)=? AND cs.status!='cancelled' AND cs.start_date IS NOT NULL $statusSql");
$allSubsRevStmt->execute([$year]);$yearTotalRevenue=(float)$allSubsRevStmt->fetchColumn();
$yearTotalCollected=array_sum($paymentsByMonth);
$yearTotalExpenses=array_sum($expensesByMonth);
$yearNetProfit=$yearTotalCollected-$yearTotalExpenses;

$primaryClientsStmt=$db->prepare("
    SELECT COUNT(DISTINCT cs.client_id) FROM client_subscriptions cs $statusJoin
    WHERE YEAR(cs.start_date)=? AND cs.status!='cancelled' AND cs.start_date IS NOT NULL AND cs.service_id=? $statusSql");
$primaryClientsStmt->execute([$year,$primaryServiceId]);
$yearTotalClients=(int)$primaryClientsStmt->fetchColumn();

$detailMonth=isset($_GET['detail_month'])?(int)$_GET['detail_month']:null;
$detailService=isset($_GET['detail_service'])?(int)$_GET['detail_service']:null;
if($detailMonth&&isset($_GET['ajax'])){
    $where=["MONTH(cs.start_date)=?","YEAR(cs.start_date)=?","cs.status!='cancelled'","cs.start_date IS NOT NULL"];
    $params=[$detailMonth,$year];
    foreach(clientStatusWhere('c') as $cond)$where[]=$cond;
    if ($country !== '') {
        $where[] = "c.country = ?";
        $params[] = $country;
    }
    if($detailService){$where[]="cs.service_id=?";$params[]=$detailService;}
    $dStmt=$db->prepare("
        SELECT c.id,c.name,c.company_name,c.country,c.status as client_status,
               cs.plan_name,cs.price,cs.currency,cs.original_price,cs.start_date,cs.end_date,cs.status as sub_status,s.name as service_name
        FROM client_subscriptions cs JOIN clients c ON c.id=cs.client_id JOIN services s ON s.id=cs.service_id
        WHERE ".implode(' AND ',$where)." ORDER BY cs.start_date ASC");
    $dStmt->execute($params);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['clients'=>$dStmt->fetchAll(),'month_name'=>$arabicMonths[$detailMonth]??'']);exit;
}

$statusLabels=['active'=>'العملاء النشطون','suspended'=>'العملاء الموقوفون','all'=>'كل العملاء'];
$statusIcons=['active'=>'fa-circle-check','suspended'=>'fa-circle-pause','all'=>'fa-circle'];
$statusColors=['active'=>'var(--success)','suspended'=>'var(--danger)','all'=>'var(--primary-light)'];
$pageTitle='تقرير احصائيات الخدمات';$activePage='reports-services-stats';$depth=1;
require_once dirname(__DIR__).'/includes/header.php';
?>
<style>
.kpi-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:18px;margin-bottom:28px;}
.kpi-card{background:var(--card-bg);border-radius:var(--border-radius);padding:22px 20px;box-shadow:var(--shadow-md);display:flex;align-items:center;gap:16px;position:relative;overflow:hidden;transition:var(--transition);border:1px solid var(--border-color);}
.kpi-card::before{content:'';position:absolute;top:0;right:0;width:4px;height:100%;border-radius:0 var(--border-radius) var(--border-radius) 0;}
.kpi-card.blue::before{background:#3b82f6;}.kpi-card.green::before{background:#22c55e;}.kpi-card.amber::before{background:#f59e0b;}.kpi-card.red::before{background:#ef4444;}.kpi-card.teal::before{background:#14b8a6;}
.kpi-card:hover{transform:translateY(-3px);box-shadow:var(--shadow-lg);}
.kpi-icon{width:52px;height:52px;border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0;}
.kpi-icon.blue{background:#dbeafe;color:#3b82f6;}.kpi-icon.green{background:#dcfce7;color:#22c55e;}.kpi-icon.amber{background:#fef3c7;color:#f59e0b;}.kpi-icon.red{background:#fee2e2;color:#ef4444;}.kpi-icon.teal{background:#ccfbf1;color:#14b8a6;}
.kpi-label{font-size:11.5px;color:var(--text-muted);font-weight:600;margin-bottom:4px;}.kpi-value{font-size:22px;font-weight:800;color:var(--text-primary);line-height:1.1;}.kpi-sub{font-size:11px;color:var(--text-muted);margin-top:4px;}
.svc-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(210px,1fr));gap:16px;margin-bottom:28px;}
.svc-chip{background:var(--card-bg);border-radius:var(--border-radius);padding:18px 20px;box-shadow:var(--shadow-sm);border:1px solid var(--border-color);display:flex;flex-direction:column;gap:10px;transition:var(--transition);text-decoration:none;color:inherit;}
.svc-chip:hover{transform:translateY(-2px);box-shadow:var(--shadow-md);border-color:var(--primary-light);}
.svc-chip.primary-service{border:2px solid var(--primary-light);}
.svc-chip-hd{display:flex;align-items:center;gap:10px;}.svc-dot{width:12px;height:12px;border-radius:50%;}.svc-name{font-weight:700;font-size:13.5px;}
.svc-stats{display:flex;gap:12px;}.svc-stat{flex:1;text-align:center;}.svc-stat .num{font-size:19px;font-weight:800;}.svc-stat .lbl{font-size:10px;color:var(--text-muted);font-weight:600;margin-top:2px;}
.svc-bar{height:6px;background:#e2e8f0;border-radius:99px;overflow:hidden;}.svc-bar-fill{height:100%;border-radius:99px;transition:width .6s;}
.chart-card{background:var(--card-bg);border-radius:var(--border-radius);padding:24px;box-shadow:var(--shadow-md);border:1px solid var(--border-color);margin-bottom:28px;}
.chart-hd{display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:10px;}
.chart-title{font-size:15px;font-weight:800;display:flex;align-items:center;gap:8px;}
.chart-legend{display:flex;gap:14px;flex-wrap:wrap;align-items:center;}
.legend-item{display:flex;align-items:center;gap:6px;font-size:12px;font-weight:600;color:var(--text-secondary);}
.legend-dot{width:10px;height:10px;border-radius:3px;}
.tbl-wrap{background:var(--card-bg);border-radius:var(--border-radius);box-shadow:var(--shadow-md);border:1px solid var(--border-color);overflow:hidden;margin-bottom:28px;}
.tbl-head{padding:18px 24px;border-bottom:1px solid var(--border-color);display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;}
.tbl-title{font-size:15px;font-weight:800;display:flex;align-items:center;gap:8px;}
.stats-tbl{width:100%;border-collapse:collapse;font-size:13px;}
.stats-tbl th{padding:12px 12px;background:#f8fafc;font-weight:700;color:var(--text-secondary);text-align:center;border-bottom:2px solid var(--border-color);white-space:nowrap;font-size:12px;}
.stats-tbl th:first-child{text-align:right;}
.stats-tbl th.primary-col{background:#eff6ff;color:var(--primary-light);border-bottom-color:var(--primary-light);}
.stats-tbl td{padding:13px 12px;border-bottom:1px solid #f1f5f9;text-align:center;vertical-align:middle;}
.stats-tbl td:first-child{text-align:right;}.stats-tbl tbody tr:hover{background:#f8fafc;}
.stats-tbl tfoot td{padding:14px 12px;font-weight:800;background:#f0f4f8;border-top:2px solid var(--border-color);text-align:center;}
.stats-tbl tfoot td:first-child{text-align:right;}
.month-btn{background:none;border:none;cursor:pointer;font-weight:800;font-size:13.5px;color:var(--primary-light);font-family:var(--font-family);padding:0;display:flex;align-items:center;gap:6px;}
.month-btn:hover{color:var(--accent);}
.pill{display:inline-flex;align-items:center;gap:5px;padding:3px 10px;border-radius:99px;font-size:12px;font-weight:700;cursor:pointer;transition:var(--transition);border:1px solid transparent;}
.pill:hover{opacity:.85;transform:scale(1.05);}
.pill.primary-pill{box-shadow:0 0 0 2px rgba(36,86,164,.15);}
.money-pos{color:var(--success);font-weight:700;}.money-neg{color:var(--danger);font-weight:700;}.money-nil{color:var(--text-muted);}
.drawer{background:var(--card-bg);border-radius:var(--border-radius);box-shadow:var(--shadow-lg);border:2px solid var(--primary-light);overflow:hidden;margin-bottom:28px;animation:slideIn .25s ease;}
@keyframes slideIn{from{opacity:0;transform:translateY(-10px);}to{opacity:1;transform:translateY(0);}}
.drawer-hd{padding:16px 22px;background:linear-gradient(135deg,var(--primary),var(--primary-light));color:#fff;display:flex;align-items:center;justify-content:space-between;}
.drawer-title{font-size:14px;font-weight:800;}
.drawer-close{background:rgba(255,255,255,.2);border:none;color:#fff;width:30px;height:30px;border-radius:8px;cursor:pointer;display:flex;align-items:center;justify-content:center;}
.mini-tbl{width:100%;border-collapse:collapse;font-size:12.5px;}
.mini-tbl th{padding:10px 12px;background:#f8fafc;font-weight:700;color:var(--text-secondary);text-align:right;border-bottom:1px solid var(--border-color);}
.mini-tbl td{padding:10px 12px;border-bottom:1px solid #f1f5f9;text-align:right;}
.mini-tbl tbody tr:hover{background:#f8fafc;}
.yr-sel{display:flex;align-items:center;gap:8px;}
.yr-btn{width:32px;height:32px;border-radius:8px;border:1px solid var(--border-color);background:var(--card-bg);color:var(--text-secondary);display:flex;align-items:center;justify-content:center;transition:var(--transition);font-size:13px;text-decoration:none;}
.yr-btn:hover{background:var(--primary);color:#fff;border-color:var(--primary);}
.yr-disp{font-size:18px;font-weight:800;min-width:56px;text-align:center;}
.primary-badge{display:inline-block;background:var(--primary-light);color:#fff;font-size:9.5px;font-weight:700;padding:1px 6px;border-radius:4px;vertical-align:middle;margin-right:4px;}
.status-tabs{display:flex;gap:8px;align-items:center;flex-wrap:wrap;}
.status-tab{display:inline-flex;align-items:center;gap:6px;padding:7px 16px;border-radius:99px;font-size:12.5px;font-weight:700;border:2px solid transparent;text-decoration:none;transition:var(--transition);background:#f1f5f9;color:var(--text-secondary);}
.status-tab:hover{transform:translateY(-1px);box-shadow:var(--shadow-sm);color:inherit;}
.status-tab.active-tab{color:#fff;box-shadow:var(--shadow-md);}
.status-tab.tab-active.active-tab{background:var(--success);border-color:var(--success);}
.status-tab.tab-suspended.active-tab{background:var(--danger);border-color:var(--danger);}
.status-tab.tab-all.active-tab{background:var(--primary-light);border-color:var(--primary-light);}
.status-tab.tab-active:not(.active-tab){border-color:var(--success);color:var(--success);}
.status-tab.tab-suspended:not(.active-tab){border-color:var(--danger);color:var(--danger);}
.status-tab.tab-all:not(.active-tab){border-color:var(--primary-light);color:var(--primary-light);}
.status-banner{display:flex;align-items:center;gap:10px;padding:10px 16px;border-radius:var(--border-radius-sm);font-size:12.5px;font-weight:600;margin-bottom:20px;border:1px solid;}
.status-banner.active{background:#dcfce7;color:#15803d;border-color:#86efac;}
.status-banner.suspended{background:#fee2e2;color:#b91c1c;border-color:#fca5a5;}
.status-banner.all{background:#dbeafe;color:#1d4ed8;border-color:#93c5fd;}
@media(max-width:768px){.kpi-grid{grid-template-columns:repeat(2,1fr);}.stats-tbl{font-size:11px;}.stats-tbl th,.stats-tbl td{padding:8px 6px;}}
</style>
<?php 
$makeUrl = function(string $s, ?string $cnt = null) use ($year, $country): string {
    $c = $cnt !== null ? $cnt : $country;
    $params = ['year' => $year, 'client_status' => $s];
    if ($c !== '') $params['country'] = $c;
    return '?' . http_build_query($params);
}; 
?>
<div style="display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:14px;margin-bottom:20px;">
  <div>
    <h1 style="font-size:22px;font-weight:900;margin:0;"><i class="fas fa-chart-line" style="color:var(--accent);"></i> تقرير احصائيات الخدمات</h1>
    <p style="color:var(--text-muted);font-size:12.5px;margin:4px 0 0;">تفاصيل شهرية لكل خدمة &mdash; عملاء &middot; ايرادات &middot; مصروفات &middot; صافي ربح</p>
  </div>
  <div style="display:flex;flex-direction:column;align-items:flex-end;gap:10px;">
    <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
      <select onchange="window.location='<?= $makeUrl($clientStatus, '__CNT__') ?>'.replace('__CNT__', encodeURIComponent(this.value))" 
              class="form-control" style="font-size:12.5px;padding:6px 12px;width:auto;height:auto;">
        <option value="">🌐 كل الدول</option>
        <?php foreach (getSupportedCountries() as $cCode => $cInfo): ?>
          <option value="<?= e($cCode) ?>" <?= $country === $cCode ? 'selected' : '' ?>>
            <?= $cInfo['flag'] ?> <?= e($cInfo['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
      <div class="yr-sel">
        <a href="<?= $makeUrl($clientStatus).'&year='.($year-1) ?>" class="yr-btn"><i class="fas fa-chevron-right"></i></a>
        <span class="yr-disp"><?= $year ?></span>
        <a href="<?= $makeUrl($clientStatus).'&year='.($year+1) ?>" class="yr-btn"><i class="fas fa-chevron-left"></i></a>
      </div>
    </div>
    <div class="status-tabs">
      <span style="font-size:12px;color:var(--text-muted);font-weight:600;">عرض:</span>
      <a href="<?= $makeUrl('active') ?>" class="status-tab tab-active <?= $clientStatus==='active'?'active-tab':'' ?>"><i class="fas fa-circle-check"></i> النشطون</a>
      <a href="<?= $makeUrl('suspended') ?>" class="status-tab tab-suspended <?= $clientStatus==='suspended'?'active-tab':'' ?>"><i class="fas fa-circle-pause"></i> الموقوفون</a>
      <a href="<?= $makeUrl('all') ?>" class="status-tab tab-all <?= $clientStatus==='all'?'active-tab':'' ?>"><i class="fas fa-circle"></i> الكل</a>
    </div>
  </div>
</div>

<div class="status-banner <?= $clientStatus ?>">
  <i class="fas <?= $statusIcons[$clientStatus] ?>"></i>
  <span>تعرض الارقام حالياً: <strong><?= $statusLabels[$clientStatus] ?></strong> <?= $country ? '— دولة: <strong>' . e(getCountryInfo($country)['name']) . ' ' . getCountryInfo($country)['flag'] . '</strong>' : '' ?></span>
  <?php if($clientStatus!=='all' || $country !== ''): ?><a href="?year=<?= $year ?>&client_status=all" style="margin-right:auto;font-size:11.5px;color:inherit;opacity:.7;text-decoration:underline;">عرض الكل بدون فلاتر</a><?php endif; ?>
</div>

<div class="kpi-grid">
  <div class="kpi-card blue">
    <div class="kpi-icon blue"><i class="fas fa-envelope"></i></div>
    <div>
      <div class="kpi-label"><?= e($primaryServiceName?:'البريد الالكتروني') ?></div>
      <div class="kpi-value"><?= number_format($yearTotalClients) ?></div>
      <div class="kpi-sub"><i class="fas <?= $statusIcons[$clientStatus] ?>" style="color:<?= $statusColors[$clientStatus] ?>;font-size:10px;"></i> <?= $statusLabels[$clientStatus] ?> &mdash; <?= $year ?></div>
    </div>
  </div>
  <div class="kpi-card green">
    <div class="kpi-icon green"><i class="fas fa-hand-holding-usd"></i></div>
    <div><div class="kpi-label">الايرادات المحصلة</div><div class="kpi-value" style="font-size:17px;"><?= formatMoney($yearTotalCollected) ?></div><div class="kpi-sub">المبالغ المستلمة فعلياً</div></div>
  </div>
  <div class="kpi-card amber">
    <div class="kpi-icon amber"><i class="fas fa-file-invoice-dollar"></i></div>
    <div><div class="kpi-label">ايرادات الاشتراكات</div><div class="kpi-value" style="font-size:17px;"><?= formatMoney($yearTotalRevenue) ?></div><div class="kpi-sub">قيمة الاشتراكات المبرمة</div></div>
  </div>
  <div class="kpi-card red">
    <div class="kpi-icon red"><i class="fas fa-receipt"></i></div>
    <div><div class="kpi-label">اجمالي المصروفات</div><div class="kpi-value" style="font-size:17px;"><?= formatMoney($yearTotalExpenses) ?></div><div class="kpi-sub">مصروفات تشغيلية</div></div>
  </div>
  <div class="kpi-card <?= $yearNetProfit>=0?'teal':'red' ?>">
    <div class="kpi-icon <?= $yearNetProfit>=0?'teal':'red' ?>"><i class="fas fa-<?= $yearNetProfit>=0?'arrow-trend-up':'arrow-trend-down' ?>"></i></div>
    <div>
      <div class="kpi-label">صافي الربح</div>
      <div class="kpi-value" style="font-size:17px;color:<?= $yearNetProfit>=0?'var(--success)':'var(--danger)' ?>;"><?= formatMoney($yearNetProfit) ?></div>
      <div class="kpi-sub">ايرادات &minus; مصروفات</div>
    </div>
  </div>
</div>

<div class="svc-grid">
<?php
$maxClients=1;
foreach($services as $s){$c=(int)($serviceTotals[$s['id']]['total_clients']??0);if($c>$maxClients)$maxClients=$c;}
foreach($services as $i=>$svc):
    $color=$serviceColors[$i%count($serviceColors)];
    $tot=$serviceTotals[$svc['id']]??[];
    $cl=(int)($tot['total_clients']??0);$ac=(int)($tot['active_clients']??0);$rv=(float)($tot['total_revenue']??0);
    $pct=$maxClients>0?round($cl/$maxClients*100):0;
    $ip=((int)$svc['id']===$primaryServiceId);
?>
<a class="svc-chip <?= $ip?'primary-service':'' ?>" href="service-details.php?id=<?= $svc['id'] ?>">
  <div class="svc-chip-hd">
    <div class="svc-dot" style="background:<?= $color ?>;"></div>
    <div class="svc-name"><?php if($ip):?><span class="primary-badge">الاساسية</span><?php endif;?>
      <i class="fas <?= $getServiceIcon($svc['name']) ?>" style="color:<?= $color ?>;"></i> <?= e($svc['name']) ?>
    </div>
  </div>
  <div class="svc-stats">
    <div class="svc-stat"><div class="num" style="color:<?= $color ?>;"><?= $cl ?></div><div class="lbl"><?= $clientStatus==='active'?'نشطون':($clientStatus==='suspended'?'موقوفون':'اجمالي') ?></div></div>
    <div class="svc-stat"><div class="num" style="color:var(--success);"><?= $ac ?></div><div class="lbl">نشطون (كل)</div></div>
    <div class="svc-stat"><div class="num" style="font-size:13px;color:var(--text-secondary);"><?= number_format($rv,0) ?></div><div class="lbl">ايرادات (ج.م)</div></div>
  </div>
  <div class="svc-bar"><div class="svc-bar-fill" style="width:<?= $pct ?>%;background:<?= $color ?>;"></div></div>
</a>
<?php endforeach; ?>
</div>
<div class="chart-card">
  <div class="chart-hd">
    <div class="chart-title"><i class="fas fa-chart-bar" style="color:var(--primary-light);"></i> عدد العملاء الجدد شهرياً
      <span style="font-size:12px;font-weight:600;padding:2px 10px;border-radius:99px;background:<?= $clientStatus==='active'?'#dcfce7':($clientStatus==='suspended'?'#fee2e2':'#dbeafe') ?>;color:<?= $statusColors[$clientStatus] ?>;"><?= $statusLabels[$clientStatus] ?></span>
    </div>
    <div class="chart-legend" id="cLegend"></div>
  </div>
  <canvas id="cChart" height="90"></canvas>
</div>
<div class="chart-card">
  <div class="chart-hd">
    <div class="chart-title"><i class="fas fa-chart-area" style="color:var(--success);"></i> الايرادات مقابل المصروفات شهرياً</div>
    <div class="chart-legend">
      <div class="legend-item"><div class="legend-dot" style="background:#22c55e;"></div>الايرادات المحصلة</div>
      <div class="legend-item"><div class="legend-dot" style="background:#ef4444;"></div>المصروفات</div>
      <div class="legend-item"><div class="legend-dot" style="background:#3b82f6;"></div>صافي الربح</div>
    </div>
  </div>
  <canvas id="fChart" height="90"></canvas>
</div>

<div id="drawer" class="drawer" style="display:none;">
  <div class="drawer-hd"><span class="drawer-title" id="drawerTitle">تفاصيل الشهر</span><button class="drawer-close" onclick="closeDrawer()"><i class="fas fa-times"></i></button></div>
  <div id="drawerBody" style="padding:0;"></div>
</div>

<div class="tbl-wrap">
  <div class="tbl-head">
    <div class="tbl-title"><i class="fas fa-table" style="color:var(--primary-light);"></i> التفصيل الشهري لعام <?= $year ?>
      <span style="font-size:11px;background:<?= $clientStatus==='active'?'var(--success)':($clientStatus==='suspended'?'var(--danger)':'var(--primary-light)') ?>;color:#fff;padding:2px 10px;border-radius:99px;font-weight:700;">
        <i class="fas <?= $statusIcons[$clientStatus] ?>"></i> <?= $statusLabels[$clientStatus] ?>
      </span>
    </div>
    <div style="font-size:12px;color:var(--text-muted);"><i class="fas fa-info-circle"></i> انقر على اي رقم لعرض تفاصيل &nbsp;|&nbsp; <span class="primary-badge">الاساسية</span> = العدد الفعلي للعملاء</div>
  </div>
  <div style="overflow-x:auto;">
  <table class="stats-tbl">
    <thead><tr>
      <th style="min-width:90px;">الشهر</th>
      <?php foreach($services as $i=>$svc): $ip=((int)$svc['id']===$primaryServiceId); ?>
      <th style="min-width:130px;" class="<?= $ip?'primary-col':'' ?>">
        <?php if($ip):?><span class="primary-badge">الاساسية</span><?php endif;?>
        <span style="color:<?= $serviceColors[$i%count($serviceColors)] ?>;">&#9679;</span>
        <?= e($svc['name']) ?>
        <div style="font-size:10px;font-weight:500;margin-top:2px;color:var(--text-muted);"><?= $ip?'(العدد الفعلي)':'عملاء' ?></div>
      </th>
      <?php endforeach; ?>
      <th style="min-width:120px;color:var(--success);"><i class="fas fa-arrow-up" style="font-size:10px;"></i> ايرادات محصلة</th>
      <th style="min-width:110px;color:var(--danger);"><i class="fas fa-arrow-down" style="font-size:10px;"></i> مصروفات</th>
      <th style="min-width:120px;">صافي الربح</th>
    </tr></thead>
    <tbody>
<?php
$gC=0;$gE=0;$gN=0;
$gBySvc=array_fill_keys(array_column($services,'id'),0);
for($m=1;$m<=12;$m++):
    $any=false;
    foreach($services as $svc){$cnt=$subsByMonthService[$m][$svc['id']]['clients_count']??0;$gBySvc[$svc['id']]=($gBySvc[$svc['id']]??0)+$cnt;if($cnt>0)$any=true;}
    $col=$paymentsByMonth[$m]??0;$exp=$expensesByMonth[$m]??0;$net=$col-$exp;
    if($col>0||$exp>0)$any=true;$gC+=$col;$gE+=$exp;$gN+=$net;
?>
    <tr style="<?= $any?'':'opacity:.4;' ?>">
      <td><button class="month-btn" onclick="toggleDrawer(<?=$m?>,0)"><i class="fas fa-calendar-alt" style="font-size:11px;color:var(--text-muted);"></i> <?= $arabicMonths[$m] ?></button></td>
      <?php foreach($services as $i=>$svc):
        $color=$serviceColors[$i%count($serviceColors)];
        $cnt=$subsByMonthService[$m][$svc['id']]['clients_count']??0;
        $ip=((int)$svc['id']===$primaryServiceId);
      ?>
      <td>
        <?php if($cnt>0): ?>
        <span class="pill <?= $ip?'primary-pill':'' ?>"
              style="background:<?=$color?>18;color:<?=$color?>;border-color:<?=$color?>35;<?= $ip?'font-size:13px;font-weight:900;':'' ?>"
              onclick="toggleDrawer(<?=$m?>,<?=$svc['id']?>)" title="عرض عملاء <?=e($svc['name'])?> في <?=$arabicMonths[$m]?>">
          <i class="fas <?=$getServiceIcon($svc['name'])?>" style="font-size:10px;"></i> <?=$cnt?> عميل
        </span>
        <?php else: ?><span class="money-nil" style="font-size:12px;">—</span><?php endif; ?>
      </td>
      <?php endforeach; ?>
      <td class="<?=$col>0?'money-pos':'money-nil'?>"><?=$col>0?formatMoney($col):'—'?></td>
      <td class="<?=$exp>0?'money-neg':'money-nil'?>"><?=$exp>0?formatMoney($exp):'—'?></td>
      <td><?php if($col>0||$exp>0): ?><span style="font-weight:800;color:<?=$net>=0?'var(--success)':'var(--danger)'?>;"><i class="fas fa-<?=$net>=0?'arrow-trend-up':'arrow-trend-down'?>" style="font-size:10px;"></i> <?=formatMoney($net)?></span><?php else: ?><span class="money-nil">—</span><?php endif; ?></td>
    </tr>
<?php endfor; ?>
    </tbody>
    <tfoot><tr>
      <td style="text-align:right;font-size:13px;">الاجمالي السنوي</td>
      <?php foreach($services as $i=>$svc): $ip=((int)$svc['id']===$primaryServiceId); ?>
      <td style="color:<?=$ip?'var(--primary-light)':'var(--text-secondary)'?>;font-size:<?=$ip?'15':'13'?>px;font-weight:<?=$ip?'900':'700'?>;">
        <?=($gBySvc[$svc['id']]??0)?> عميل
        <?php if($ip): ?><div style="font-size:10px;font-weight:600;color:var(--text-muted);">العدد الفعلي للعملاء</div><?php endif; ?>
      </td>
      <?php endforeach; ?>
      <td style="color:var(--success);font-size:14px;"><?=formatMoney($gC)?></td>
      <td style="color:var(--danger);font-size:14px;"><?=formatMoney($gE)?></td>
      <td style="color:<?=$gN>=0?'var(--success)':'var(--danger)'?>;font-size:14px;"><?=formatMoney($gN)?></td>
    </tr></tfoot>
  </table></div>
</div>

<div style="background:var(--card-bg);border-radius:var(--border-radius);padding:16px 20px;border:1px solid var(--border-color);margin-bottom:24px;">
  <p style="font-size:12px;color:var(--text-muted);margin:0;display:flex;align-items:flex-start;gap:8px;">
    <i class="fas fa-info-circle" style="color:var(--info);margin-top:2px;flex-shrink:0;"></i>
    <span>
      <strong>ملاحظات:</strong>
      الفلتر الحالي: <strong><?= $statusLabels[$clientStatus] ?></strong> — يؤثر على اعداد العملاء وايرادات الاشتراكات فقط.
      "الايرادات المحصلة" و"المصروفات" لا تتأثر بالفلتر.
    </span>
  </p>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
<script>
const mLabels=<?=json_encode(array_values($arabicMonths))?>;
const svcs=<?=json_encode(array_map(fn($s,$i)=>['id'=>(int)$s['id'],'name'=>$s['name'],'color'=>$serviceColors[$i%count($serviceColors)]],$services,array_keys($services)))?>;
const primaryServiceId=<?=(int)$primaryServiceId?>;
const svcMap={};svcs.forEach(s=>svcMap[s.id]=s.name);
const subsData=<?=json_encode($subsByMonthService)?>;
const payData=<?=json_encode($paymentsByMonth)?>;
const expData=<?=json_encode($expensesByMonth)?>;
const arabicM=<?=json_encode($arabicMonths)?>;
const currentStatus=<?=json_encode($clientStatus)?>;

const cDatasets=svcs.map(s=>({
    label:s.name,
    data:Array.from({length:12},(_,i)=>subsData[i+1]?.[s.id]?.clients_count??0),
    backgroundColor:s.color+'CC',borderColor:s.color,borderWidth:s.id===primaryServiceId?2.5:1.5,borderRadius:5
}));
new Chart(document.getElementById('cChart').getContext('2d'),{
    type:'bar',data:{labels:mLabels,datasets:cDatasets},
    options:{responsive:true,plugins:{legend:{display:false},tooltip:{rtl:true,titleFont:{family:'Cairo'},bodyFont:{family:'Cairo'}}},
    scales:{y:{beginAtZero:true,ticks:{stepSize:1,font:{family:'Cairo',size:11}},grid:{color:'#f1f5f9'}},x:{ticks:{font:{family:'Cairo',size:11}},grid:{display:false}}}}
});
const cLeg=document.getElementById('cLegend');
svcs.forEach(s=>{
    const d=document.createElement('div');d.className='legend-item';const ip=s.id===primaryServiceId;
    d.innerHTML=`<div class="legend-dot" style="background:${s.color};${ip?'box-shadow:0 0 0 2px '+s.color+'55;':''}"></div>${s.name}${ip?'<span class="primary-badge" style="font-size:9px;margin-right:4px;">الاساسية</span>':''}`;
    cLeg.appendChild(d);
});
const colArr=Array.from({length:12},(_,i)=>payData[i+1]??0);
const expArr=Array.from({length:12},(_,i)=>expData[i+1]??0);
const netArr=colArr.map((c,i)=>c-expArr[i]);
new Chart(document.getElementById('fChart').getContext('2d'),{
    type:'bar',data:{labels:mLabels,datasets:[
        {label:'الايرادات المحصلة',data:colArr,backgroundColor:'#22c55eCC',borderColor:'#22c55e',borderWidth:1.5,borderRadius:5,order:2},
        {label:'المصروفات',data:expArr,backgroundColor:'#ef4444CC',borderColor:'#ef4444',borderWidth:1.5,borderRadius:5,order:2},
        {label:'صافي الربح',type:'line',data:netArr,borderColor:'#3b82f6',backgroundColor:'transparent',pointBackgroundColor:'#3b82f6',pointRadius:4,tension:.35,borderWidth:2.5,order:1}
    ]},
    options:{responsive:true,plugins:{legend:{display:false},tooltip:{rtl:true,titleFont:{family:'Cairo'},bodyFont:{family:'Cairo'},
        callbacks:{label:c=>c.dataset.label+': '+new Intl.NumberFormat('ar-EG',{maximumFractionDigits:0}).format(c.raw)+' ج.م'}}},
    scales:{y:{beginAtZero:true,ticks:{font:{family:'Cairo',size:11},callback:v=>v.toLocaleString('ar-EG')},grid:{color:'#f1f5f9'}},x:{ticks:{font:{family:'Cairo',size:11}},grid:{display:false}}}}
});
let aMonth=null,aSvc=null;
function toggleDrawer(month,svcId){const dr=document.getElementById('drawer');if(aMonth===month&&aSvc===svcId&&dr.style.display!=='none'){closeDrawer();return;}aMonth=month;aSvc=svcId;openDrawer(month,svcId);}
function openDrawer(month,svcId){
    const dr=document.getElementById('drawer'),body=document.getElementById('drawerBody'),title=document.getElementById('drawerTitle');
    title.textContent='عملاء '+arabicM[month]+(svcId?' — '+(svcMap[svcId]??''):'— كل الخدمات');
    dr.style.display='block';
    body.innerHTML='<div style="padding:40px;text-align:center;"><div style="width:30px;height:30px;border:3px solid #e2e8f0;border-top-color:var(--primary-light);border-radius:50%;animation:spin .6s linear infinite;margin:auto;"></div><p style="color:var(--text-muted);margin-top:10px;font-size:13px;">جاري التحميل...</p></div>';
    setTimeout(()=>dr.scrollIntoView({behavior:'smooth',block:'nearest'}),50);
    fetch('?year=<?=$year?>&client_status=<?=urlencode($clientStatus)?>&detail_month='+month+'&detail_service='+svcId+'&ajax=1')
        .then(r=>r.json()).then(data=>renderDrawer(data)).catch(()=>{body.innerHTML='<p style="padding:20px;color:var(--danger);text-align:center;">حدث خطا.</p>';});
}
function renderDrawer(data){
    const body=document.getElementById('drawerBody'),clients=data.clients||[];
    if(!clients.length){body.innerHTML='<div style="padding:30px;text-align:center;color:var(--text-muted);"><i class="fas fa-users-slash" style="font-size:28px;display:block;margin-bottom:10px;"></i><p>لا يوجد عملاء</p></div>';return;}
    let html=`<div style="padding:10px 16px;background:#f8fafc;font-size:12px;color:var(--text-muted);border-bottom:1px solid var(--border-color);"><i class="fas fa-users"></i> ${clients.length} عميل</div><div style="overflow-x:auto;"><table class="mini-tbl"><thead><tr><th>#</th><th>اسم العميل</th><th>الشركة</th><th>الخدمة</th><th>الباقة</th><th>القيمة</th><th>بداية</th><th>نهاية</th><th>الحالة</th></tr></thead><tbody>`;
    const svgFlags = {
        'EG': '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 480" width="18" height="13" style="border-radius:2px;vertical-align:middle;display:inline-block;"><rect width="640" height="160" fill="#ce1126"/><rect y="160" width="640" height="160" fill="#ffffff"/><rect y="320" width="640" height="160" fill="#000000"/><path d="M320 200c-7 0-14 8-14 20 0 16 14 30 14 30s14-14 14-30c0-12-7-20-14-20z" fill="#c09300"/></svg>',
        'SA': '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 480" width="18" height="13" style="border-radius:2px;vertical-align:middle;display:inline-block;"><rect width="640" height="480" fill="#006c35"/><path d="M160 320h320v14H160z" fill="#ffffff"/></svg>',
        'AE': '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 480" width="18" height="13" style="border-radius:2px;vertical-align:middle;display:inline-block;"><rect width="640" height="160" fill="#00732f"/><rect y="160" width="640" height="160" fill="#ffffff"/><rect y="320" width="640" height="160" fill="#000000"/><rect width="180" height="480" fill="#ff0000"/></svg>',
        'KW': '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 480" width="18" height="13" style="border-radius:2px;vertical-align:middle;display:inline-block;"><rect width="640" height="160" fill="#007a3d"/><rect y="160" width="640" height="160" fill="#ffffff"/><rect y="320" width="640" height="160" fill="#ce1126"/><polygon points="0,0 200,160 200,320 0,480" fill="#000000"/></svg>',
        'QA': '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 480" width="18" height="13" style="border-radius:2px;vertical-align:middle;display:inline-block;"><rect width="640" height="480" fill="#8d1b3d"/><polygon points="0,0 200,0 240,27 200,53 240,80 200,107 240,133 200,160 240,187 200,213 240,240 200,267 240,293 200,320 240,347 200,373 240,400 200,427 240,453 200,480 0,480" fill="#ffffff"/></svg>',
        'OM': '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 480" width="18" height="13" style="border-radius:2px;vertical-align:middle;display:inline-block;"><rect width="640" height="160" fill="#ffffff"/><rect y="160" width="640" height="160" fill="#db161e"/><rect y="320" width="640" height="160" fill="#008000"/><rect width="180" height="480" fill="#db161e"/></svg>',
        'BH': '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 480" width="18" height="13" style="border-radius:2px;vertical-align:middle;display:inline-block;"><rect width="640" height="480" fill="#ce1126"/><polygon points="0,0 180,0 240,48 180,96 240,144 180,192 240,240 180,288 240,336 180,384 240,432 180,480 0,480" fill="#ffffff"/></svg>',
        'SD': '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 480" width="18" height="13" style="border-radius:2px;vertical-align:middle;display:inline-block;"><rect width="640" height="160" fill="#d21034"/><rect y="160" width="640" height="160" fill="#ffffff"/><rect y="320" width="640" height="160" fill="#000000"/><polygon points="0,0 220,240 0,480" fill="#007229"/></svg>',
        'OTHER': '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 480" width="18" height="13" style="border-radius:2px;vertical-align:middle;display:inline-block;"><rect width="640" height="480" fill="#2563eb"/><circle cx="320" cy="240" r="160" fill="none" stroke="#ffffff" stroke-width="20"/></svg>'
    };
    clients.forEach((c,i)=>{
        const flagSvg = svgFlags[c.country] || svgFlags['EG'];
        const sb=c.client_status==1?'<span style="background:#dcfce7;color:#16a34a;padding:2px 7px;border-radius:99px;font-size:10.5px;font-weight:700;">نشط</span>':'<span style="background:#fee2e2;color:#dc2626;padding:2px 7px;border-radius:99px;font-size:10.5px;font-weight:700;">موقوف</span>';
        const ss=c.sub_status==='active'?'<span style="background:#dcfce7;color:#16a34a;padding:2px 6px;border-radius:99px;font-size:10px;">نشط</span>':`<span style="background:#fef3c7;color:#d97706;padding:2px 6px;border-radius:99px;font-size:10px;">${c.sub_status}</span>`;
        let priceStr = Number(c.price).toLocaleString('ar-EG') + ' ج.م';
        if (c.currency && c.currency !== 'EGP' && c.original_price) {
            priceStr = Number(c.original_price).toLocaleString('ar-EG') + ' ' + c.currency + ` <span style="font-size:10.5px;color:var(--text-muted);">(${Number(c.price).toLocaleString('ar-EG')} ج.م)</span>`;
        }
        html+=`<tr><td style="color:var(--text-muted);font-size:11px;">${i+1}</td><td style="font-weight:700;"><span style="margin-left:6px;display:inline-flex;align-items:center;">${flagSvg}</span><a href="../clients/view.php?id=${c.id}" style="color:var(--primary-light);">${esc(c.name)}</a> ${sb}</td><td style="color:var(--text-muted);">${esc(c.company_name||'—')}</td><td style="font-size:12px;color:var(--primary-light);">${esc(c.service_name||'—')}</td><td style="font-size:12px;">${esc(c.plan_name||'—')}</td><td style="font-weight:700;color:var(--success);">${priceStr}</td><td style="color:var(--text-muted);font-size:12px;">${c.start_date||'—'}</td><td style="color:var(--text-muted);font-size:12px;">${c.end_date||'—'}</td><td>${ss}</td></tr>`;
    });
    html+='</tbody></table></div>';body.innerHTML=html;
}
function esc(s){return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');}
function closeDrawer(){document.getElementById('drawer').style.display='none';aMonth=null;aSvc=null;}
</script>
<?php require_once dirname(__DIR__).'/includes/footer.php'; ?>
