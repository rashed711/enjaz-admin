<?php
/**
 * reports/renewals.php - تقرير التجديدات القريبة المحدث والمتجاوب بالكامل
 */
require_once dirname(__DIR__) . '/config/app.php';
requireLogin();
requirePermission('view_reports');

$db = getDB();
$warningDays = (int)getSetting('renewal_warning_days', '60');
$filterDays   = isset($_GET['days']) ? (int)$_GET['days'] : $warningDays;
$clientStatus = $_GET['status'] ?? '1'; // '1' = active, '0' = stopped, 'all' = both
$country      = clean($_GET['country'] ?? '');
$server       = clean($_GET['server'] ?? '');
$search       = clean($_GET['search'] ?? '');

$availableServers = [];
try {
    $serversStmt = $db->query("SELECT DISTINCT server_panel FROM clients WHERE server_panel IS NOT NULL AND server_panel != '' ORDER BY server_panel ASC");
    $availableServers = $serversStmt->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {}

$where = ["cs.status='active'", "cs.end_date IS NOT NULL", "cs.end_date <= DATE_ADD(CURDATE(), INTERVAL ? DAY)"];
$params = [$filterDays];

if ($search !== '') {
    $where[] = "(c.name LIKE ? OR c.company_name LIKE ? OR c.mobile LIKE ? OR c.domain LIKE ? OR cs.notes LIKE ? OR cs.plan_name LIKE ?)";
    $s = "%$search%";
    $params = array_merge($params, [$s, $s, $s, $s, $s, $s]);
}

if ($clientStatus !== 'all') {
    $where[] = "c.status = ?";
    $params[] = (int)$clientStatus;
}

if ($country !== '') {
    $where[] = "c.country = ?";
    $params[] = $country;
}

if ($server !== '') {
    $where[] = "c.server_panel = ?";
    $params[] = $server;
}

$whereStr = implode(' AND ', $where);

$stmt = $db->prepare("
    SELECT cs.*, c.name as client_name, c.mobile, c.company_name, c.country, c.server_panel, c.domain as client_domain,
           s.name as service_name,
           DATEDIFF(cs.end_date, CURDATE()) as days_left
    FROM client_subscriptions cs
    JOIN clients c ON c.id=cs.client_id
    JOIN services s ON s.id=cs.service_id
    WHERE $whereStr
    ORDER BY cs.end_date ASC
");
$stmt->execute($params);
$renewals = $stmt->fetchAll();

// تجميع الاشتراكات حسب العميل
$clients = [];
$urgentExpiredCount = 0;
$totalSubsCount = count($renewals);

foreach ($renewals as $r) {
    $cId = $r['client_id'];
    if (!isset($clients[$cId])) {
        $clients[$cId] = [
            'client_id'     => $cId,
            'client_name'   => $r['client_name'],
            'mobile'        => $r['mobile'],
            'company_name'  => $r['company_name'],
            'country'       => $r['country'] ?? 'EG',
            'server_panel'  => $r['server_panel'] ?? 'cp.enjaz.cloud',
            'client_domain' => $r['client_domain'] ?? '',
            'total_price'   => 0,
            'min_days_left' => 999999,
            'min_end_date'  => $r['end_date'],
            'subscriptions' => []
        ];
    }
    $clients[$cId]['total_price'] += (float)$r['price'];
    if ($r['days_left'] < $clients[$cId]['min_days_left']) {
        $clients[$cId]['min_days_left'] = $r['days_left'];
        $clients[$cId]['min_end_date']  = $r['end_date'];
    }
    if ($r['days_left'] <= 7) {
        $urgentExpiredCount++;
    }
    $clients[$cId]['subscriptions'][] = $r;
}
$grandTotalRenewals = array_sum(array_column($clients, 'total_price'));
$totalClientsCount  = count($clients);

// حساب إجمالي كل خدمة على حدة
$serviceTotals = [];
$serviceCounts = [];
foreach ($renewals as $r) {
    $sName = $r['service_name'];
    if (!isset($serviceTotals[$sName])) {
        $serviceTotals[$sName] = 0;
        $serviceCounts[$sName] = 0;
    }
    $serviceTotals[$sName] += (float)$r['price'];
    $serviceCounts[$sName]++;
}
arsort($serviceTotals);

function renderRenewalsTable($clients) {
    if (empty($clients)): ?>
    <tr>
      <td colspan="9" style="text-align: center; padding: 40px;">
        <div class="empty-state" style="padding: 0;">
          <div class="empty-icon" style="color:var(--success); font-size: 42px; margin-bottom: 10px;">
            <i class="fas fa-check-circle"></i>
          </div>
          <p class="empty-title" style="font-size: 16px; font-weight: 800; color: var(--text-primary);">ممتاز! لا توجد تجديدات تنتهي في الفترة المحددة</p>
          <p style="font-size: 12.5px; color: var(--text-muted); margin-top: 4px;">جرب تغيير معايير الفلترة أو زيادة فترة الأيام</p>
        </div>
      </td>
    </tr>
    <?php else: ?>
    <?php foreach ($clients as $cId => $c): 
      $cCode = $c['country'] ?? 'EG';
      $cInfo = getCountryInfo($cCode);
      $srvCount = count($c['subscriptions']);
      $srvLabel = $c['server_panel'];
      if ($srvLabel === 'cp.enjaz.cloud') $srvLabel = 'السيرفر الأول (cp)';
      elseif ($srvLabel === 'panel.enjaz.cloud') $srvLabel = 'السيرفر الثاني (panel)';
    ?>
    <tr class="client-row" data-client-id="<?= $cId ?>" style="transition: background 0.15s;">
      <td style="text-align: center; vertical-align: middle; width: 45px;">
        <input type="checkbox" name="client_ids[]" value="<?= $cId ?>" class="client-checkbox form-check-input" style="cursor: pointer; width: 18px; height: 18px;">
      </td>
      <td>
        <div style="display:flex;align-items:center;gap:8px;">
          <span title="<?= e($cInfo['name']) ?>" style="display:inline-flex;align-items:center;flex-shrink:0;">
            <?= getCountryFlagSvg($cCode, 22, 15) ?>
          </span>
          <div>
            <a href="../clients/view.php?id=<?= $cId ?>" style="font-weight:700; color: var(--text-primary); text-decoration: none;" onmouseover="this.style.color='var(--primary)'" onmouseout="this.style.color='var(--text-primary)'">
              <?= e($c['client_name']) ?>
            </a>
            <?php if ($c['mobile']): ?>
            <div style="font-size:11px;color:var(--text-muted); margin-top: 2px; display: flex; align-items: center; gap: 4px;">
              <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $c['mobile']) ?>" target="_blank" style="color: #10b981; text-decoration: none;" title="مراسلة عبر واتساب">
                <i class="fab fa-whatsapp"></i> <?= e($c['mobile']) ?>
              </a>
            </div>
            <?php endif; ?>
          </div>
        </div>
      </td>
      <td class="text-muted" style="font-size: 12.5px;"><?= e($c['company_name'] ?: '—') ?></td>
      <td>
        <span class="badge" style="background: rgba(36,86,164,0.06); color: var(--primary); font-size: 11px; padding: 3px 8px; border-radius: 6px;">
          <?= e($srvLabel) ?>
        </span>
      </td>
      <td>
        <button type="button" class="btn btn-sm btn-outline-primary" onclick="toggleClientDetails(<?= $cId ?>)" style="border-radius: 20px; font-weight: 700; font-size: 11.5px; padding: 2px 10px; display: inline-flex; align-items: center; gap: 5px;">
          <i class="fas fa-layer-group"></i>
          <span><?= $srvCount ?> <?= $srvCount > 1 ? 'خدمات' : 'خدمة' ?></span>
          <i class="fas fa-chevron-down" id="arrow-details-<?= $cId ?>" style="font-size: 10px; transition: transform 0.2s;"></i>
        </button>
      </td>
      <td class="fw-bold" style="color: var(--success); font-size: 13.5px;">
        <?= formatMoney($c['total_price']) ?>
      </td>
      <td style="font-size: 12.5px; font-weight: 600;"><?= formatDate($c['min_end_date']) ?></td>
      <td>
        <?php if ($c['min_days_left'] <= 0): ?>
          <span class="badge badge-danger" style="font-size: 11px; padding: 3px 8px;">
            <i class="fas fa-exclamation-circle" style="margin-left: 3px;"></i> انتهى
          </span>
        <?php elseif ($c['min_days_left'] <= 7): ?>
          <span class="badge badge-danger" style="font-size: 11px; padding: 3px 8px;">
            <i class="fas fa-clock" style="margin-left: 3px;"></i> باقي <?= $c['min_days_left'] ?> يوم
          </span>
        <?php elseif ($c['min_days_left'] <= 14): ?>
          <span class="badge badge-warning" style="font-size: 11px; padding: 3px 8px;">
            <i class="fas fa-hourglass-half" style="margin-left: 3px;"></i> باقي <?= $c['min_days_left'] ?> يوم
          </span>
        <?php else: ?>
          <span class="badge badge-info" style="font-size: 11px; padding: 3px 8px;">
            باقي <?= $c['min_days_left'] ?> يوم
          </span>
        <?php endif; ?>
      </td>
      <td>
        <div style="display: flex; align-items: center; gap: 6px;">
          <button type="button" class="btn btn-sm btn-outline-primary" onclick="toggleClientDetails(<?= $cId ?>)" title="عرض تفاصيل الخدمات" style="padding: 3px 8px; font-size: 11px; border-radius: 6px;">
            <i class="fas fa-list-ul"></i>
          </button>
          <a href="../clients/view.php?id=<?= $cId ?>" class="btn btn-sm btn-primary" title="عرض ملف العميل" style="padding: 3px 8px; font-size: 11px; border-radius: 6px;">
            <i class="fas fa-user"></i>
          </a>
        </div>
      </td>
    </tr>
    <!-- Collapsible Details Row -->
    <tr id="details-row-<?= $cId ?>" class="details-row" style="display:none; background-color: rgba(36, 86, 164, 0.02); border-bottom: 2px solid var(--border-color);">
      <td></td>
      <td colspan="8" style="padding: 12px 18px;">
        <div style="padding: 12px 16px; border-right: 4px solid var(--primary); background: var(--card-bg); border-radius: 8px; border: 1px solid var(--border-color); box-shadow: 0 2px 8px rgba(0,0,0,0.02);">
          <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px; flex-wrap: wrap; gap: 8px;">
            <h5 style="margin: 0; font-size: 12.5px; color: var(--primary); font-weight: 800; display: flex; align-items: center; gap: 6px;">
              <i class="fas fa-receipt"></i>
              <span>الخدمات المستحقة لتجديد العميل (<?= e($c['client_name']) ?>):</span>
            </h5>
            <span style="font-size: 11.5px; color: var(--text-muted);">
              إجمالي المستحق: <strong style="color: var(--success);"><?= formatMoney($c['total_price']) ?></strong>
            </span>
          </div>

          <div class="table-wrapper" style="margin: 0; box-shadow: none; border: 1px solid var(--border-color); border-radius: 6px; overflow: hidden;">
            <table class="data-table" style="margin: 0; font-size: 12px; width: 100%;">
              <thead>
                <tr style="background-color: var(--bg-hover);">
                  <th>الخدمة</th>
                  <th>الباقة / التفاصيل</th>
                  <th>قيمة الاشتراك</th>
                  <th>تاريخ الانتهاء</th>
                  <th>المتبقي</th>
                  <th style="width: 90px; text-align: center;">إجراء سريع</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($c['subscriptions'] as $sub): 
                  $isDom = (mb_strpos(mb_strtolower($sub['service_name']), 'دومين') !== false || mb_strpos(mb_strtolower($sub['service_name']), 'domain') !== false);
                ?>
                <tr>
                  <td>
                    <div style="font-weight: 700; color: var(--text-primary); display: flex; align-items: center; gap: 6px;">
                      <i class="fas <?= $isDom ? 'fa-globe text-info' : 'fa-check-circle text-primary' ?>"></i>
                      <span><?= e($sub['service_name']) ?></span>
                    </div>
                  </td>
                  <td class="text-muted">
                    <?= e($sub['plan_name'] ?: '—') ?>
                    <?php if (!empty($sub['notes'])): ?>
                      <div style="font-size: 10.5px; color: var(--text-muted);"><?= e($sub['notes']) ?></div>
                    <?php endif; ?>
                  </td>
                  <td class="fw-bold" style="color: var(--text-primary);">
                    <?= formatPlanPrice((float)$sub['price'], $sub['currency'] ?? 'EGP', isset($sub['original_price']) && $sub['original_price'] !== null ? (float)$sub['original_price'] : null) ?>
                  </td>
                  <td><?= formatDate($sub['end_date']) ?></td>
                  <td>
                    <?php if ($sub['days_left'] <= 0): ?>
                      <span class="badge badge-danger" style="font-size: 10.5px;">انتهى</span>
                    <?php elseif ($sub['days_left'] <= 7): ?>
                      <span class="badge badge-danger" style="font-size: 10.5px;"><?= $sub['days_left'] ?> يوم</span>
                    <?php elseif ($sub['days_left'] <= 14): ?>
                      <span class="badge badge-warning" style="font-size: 10.5px;"><?= $sub['days_left'] ?> يوم</span>
                    <?php else: ?>
                      <span class="badge badge-info" style="font-size: 10.5px;"><?= $sub['days_left'] ?> يوم</span>
                    <?php endif; ?>
                  </td>
                  <td style="text-align: center;">
                    <a href="../subscriptions/renew.php?id=<?= $sub['id'] ?>" class="btn btn-xs btn-success" style="padding: 2px 8px; font-size: 11px; border-radius: 4px;">
                      <i class="fas fa-redo"></i> تجديد
                    </a>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </td>
    </tr>
    <?php endforeach; ?>
    <?php endif;
}

// AJAX live search handler
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    ob_start();
    renderRenewalsTable($clients);
    $tbodyHtml = ob_get_clean();

    $statusText = ($clientStatus === '1') ? ' النشطين' : (($clientStatus === '0') ? ' الموقوفين' : '');

    ob_start();
    foreach ($serviceTotals as $sName => $total): 
      $cnt = $serviceCounts[$sName] ?? 0;
    ?>
    <div style="background: var(--card-bg); border: 1px solid var(--border-color); padding: 5px 12px; border-radius: 8px; font-size: 12px; font-weight: 700; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.02);">
      <span style="color: var(--text-primary);"><i class="fas fa-layer-group text-primary" style="margin-left: 4px;"></i> <?= e($sName) ?>:</span>
      <span class="badge badge-primary" style="font-size: 10.5px;"><?= $cnt ?> اشتراك</span>
      <span style="color: var(--success); font-weight: 800;"><?= formatMoney($total) ?></span>
    </div>
    <?php endforeach;
    $serviceTotalsHtml = ob_get_clean();

    echo json_encode([
        'tbody' => $tbodyHtml,
        'subtitle' => $totalClientsCount . ' عميل' . $statusText . ' لديهم تجديدات قريبة بقيمة إجمالية ' . formatMoney($grandTotalRenewals) . ' خلال ' . $filterDays . ' يوم القادمة',
        'clients_count' => $totalClientsCount,
        'renewals_count' => $totalSubsCount,
        'urgent_count' => $urgentExpiredCount,
        'grand_total_formatted' => formatMoney($grandTotalRenewals),
        'service_totals_html' => $serviceTotalsHtml
    ]);
    exit;
}

$pageTitle  = 'تقرير التجديدات القريبة';
$activePage = 'reports-renewals';
$depth      = 1;
require_once INCLUDES_PATH . '/header.php';
?>

<style>
.renewals-kpi-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 16px;
  margin-bottom: 20px;
}
.renewals-filter-card {
  background: var(--card-bg);
  border: 1px solid var(--border-color);
  border-radius: 12px;
  padding: 16px 20px;
  margin-bottom: 20px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.02);
}
@media (max-width: 992px) {
  .renewals-kpi-grid {
    grid-template-columns: 1fr;
  }
}
</style>

<form id="bulkWhatsappForm" method="POST" action="../whatsapp/bulk.php?type=renewal">
  <input type="hidden" name="days" value="<?= $filterDays ?>" id="daysInput">
  <input type="hidden" name="status" value="<?= $clientStatus ?>" id="statusInput">
  <input type="hidden" name="country" value="<?= e($country) ?>" id="countryInput">
  <input type="hidden" name="server" value="<?= e($server) ?>" id="serverInput">

  <?php
  $statusText = ($clientStatus === '1') ? ' النشطين' : (($clientStatus === '0') ? ' الموقوفين' : '');
  ?>

  <!-- Header Section -->
  <div class="page-header" style="margin-bottom: 20px;">
    <div class="page-header-text">
      <h1 class="page-title">
        <i class="fas fa-calendar-exclamation" style="color:var(--warning);margin-left:8px;"></i>
        تقرير التجديدات القريبة
      </h1>
      <p class="page-subtitle" id="renewalsSubtitle">
        <?= $totalClientsCount ?> عميل<?= $statusText ?> لديهم تجديدات قريبة بقيمة إجمالية <?= formatMoney($grandTotalRenewals) ?> خلال <?= $filterDays ?> يوم القادمة
      </p>
    </div>
    
    <div class="page-actions" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
      <?php if (hasPermission('send_whatsapp')): ?>
      <button type="submit" class="btn btn-success" id="sendSelectedWhatsappBtn" disabled style="display: inline-flex; align-items: center; gap: 6px; font-weight: 700;">
        <i class="fab fa-whatsapp"></i>
        <span>إرسال للمحددين (<span id="selectedCount">0</span>)</span>
      </button>

      <a href="../whatsapp/bulk.php?type=renewal&days=<?= $filterDays ?>&status=<?= $clientStatus ?>&country=<?= urlencode($country) ?>&server=<?= urlencode($server) ?>"
         class="btn btn-outline-success"
         id="bulkWhatsappBtn"
         data-confirm="إرسال رسائل واتساب لكل <?= $totalClientsCount ?> عميل؟"
         style="display: <?= $totalClientsCount > 0 ? 'inline-flex' : 'none' ?>; align-items: center; gap: 6px; font-weight: 700;">
        <i class="fas fa-paper-plane"></i> إرسال للكل (<span id="allCount"><?= $totalClientsCount ?></span>)
      </a>
      <?php endif; ?>

      <button type="button" class="btn btn-outline" onclick="exportRenewalsExcel()" style="display: inline-flex; align-items: center; gap: 6px; font-weight: 700;">
        <i class="fas fa-file-excel" style="color:#217346;"></i>
        <span>تصدير Excel</span>
      </button>
    </div>
  </div>

  <!-- KPI Summary Cards -->
  <div class="renewals-kpi-grid">
    
    <!-- عملاء التجديد -->
    <div class="card" style="border-right: 4px solid var(--primary); padding: 16px; margin: 0; background: var(--card-bg); display: flex; align-items: center; justify-content: space-between;">
      <div>
        <div style="font-size: 12px; color: var(--text-muted); font-weight: 700; margin-bottom: 4px;">العملاء المستحقون للتجديد</div>
        <div id="kpiClientsCount" style="font-size: 22px; font-weight: 900; color: var(--primary);"><?= $totalClientsCount ?> عميل</div>
      </div>
      <div style="width: 44px; height: 44px; border-radius: 10px; background: rgba(36,86,164,0.1); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 18px;">
        <i class="fas fa-users"></i>
      </div>
    </div>

    <!-- مبالغ التجديد الإجمالية -->
    <div class="card" style="border-right: 4px solid var(--success); padding: 16px; margin: 0; background: var(--card-bg); display: flex; align-items: center; justify-content: space-between;">
      <div>
        <div style="font-size: 12px; color: var(--text-muted); font-weight: 700; margin-bottom: 4px;">إجمالي المبالغ المطلوبة</div>
        <div id="kpiGrandTotal" style="font-size: 20px; font-weight: 900; color: var(--success);"><?= formatMoney($grandTotalRenewals) ?></div>
      </div>
      <div style="width: 44px; height: 44px; border-radius: 10px; background: rgba(16,185,129,0.1); color: var(--success); display: flex; align-items: center; justify-content: center; font-size: 18px;">
        <i class="fas fa-coins"></i>
      </div>
    </div>

    <!-- عاجلة / منتهية -->
    <div class="card" style="border-right: 4px solid var(--danger); padding: 16px; margin: 0; background: var(--card-bg); display: flex; align-items: center; justify-content: space-between;">
      <div>
        <div style="font-size: 12px; color: var(--text-muted); font-weight: 700; margin-bottom: 4px;">تجديدات عاجلة (<= 7 أيام)</div>
        <div id="kpiUrgentCount" style="font-size: 22px; font-weight: 900; color: var(--danger);"><?= $urgentExpiredCount ?> اشتراك</div>
      </div>
      <div style="width: 44px; height: 44px; border-radius: 10px; background: rgba(239,68,68,0.1); color: var(--danger); display: flex; align-items: center; justify-content: center; font-size: 18px;">
        <i class="fas fa-bell"></i>
      </div>
    </div>

    <!-- عدد الاشتراكات الكلية -->
    <div class="card" style="border-right: 4px solid #8b5cf6; padding: 16px; margin: 0; background: var(--card-bg); display: flex; align-items: center; justify-content: space-between;">
      <div>
        <div style="font-size: 12px; color: var(--text-muted); font-weight: 700; margin-bottom: 4px;">إجمالي الاشتراكات</div>
        <div id="kpiSubsCount" style="font-size: 22px; font-weight: 900; color: #8b5cf6;"><?= $totalSubsCount ?> اشتراك</div>
      </div>
      <div style="width: 44px; height: 44px; border-radius: 10px; background: rgba(139,92,246,0.1); color: #8b5cf6; display: flex; align-items: center; justify-content: center; font-size: 18px;">
        <i class="fas fa-receipt"></i>
      </div>
    </div>

  </div>

  <!-- Detailed Service Breakdown Chips -->
  <div class="card" style="margin-bottom: 20px; padding: 14px 20px; background: rgba(36,86,164,0.02); border: 1px solid var(--border-color); border-radius: 10px;">
    <div style="font-size: 12px; font-weight: 800; color: var(--text-muted); margin-bottom: 8px; display: flex; align-items: center; gap: 6px;">
      <i class="fas fa-layer-group text-primary"></i>
      <span>توزيع إجمالي مبالغ التجديد حسب الخدمة:</span>
    </div>
    <div id="serviceTotalsContainer" style="display: flex; gap: 8px; flex-wrap: wrap;">
      <?php foreach ($serviceTotals as $sName => $total): 
        $cnt = $serviceCounts[$sName] ?? 0;
      ?>
      <div style="background: var(--card-bg); border: 1px solid var(--border-color); padding: 5px 12px; border-radius: 8px; font-size: 12px; font-weight: 700; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.02);">
        <span style="color: var(--text-primary);"><i class="fas fa-layer-group text-primary" style="margin-left: 4px;"></i> <?= e($sName) ?>:</span>
        <span class="badge badge-primary" style="font-size: 10.5px;"><?= $cnt ?> اشتراك</span>
        <span style="color: var(--success); font-weight: 800;"><?= formatMoney($total) ?></span>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Dedicated Filter Bar -->
  <div class="renewals-filter-card">
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; align-items: center;">
      
      <!-- Search Input -->
      <div>
        <label style="font-size: 11.5px; font-weight: 700; color: var(--text-muted); margin-bottom: 4px; display: block;">
          <i class="fas fa-search" style="color: var(--primary);"></i> بحث سريع
        </label>
        <input type="text" id="searchInput" class="form-control" placeholder="اسم العميل، الهاتف، الشركة، النطاق..." value="<?= e($search) ?>" style="font-size: 12px; height: 38px;">
      </div>

      <!-- Country Filter -->
      <div>
        <label style="font-size: 11.5px; font-weight: 700; color: var(--text-muted); margin-bottom: 4px; display: block;">
          <i class="fas fa-globe" style="color: var(--primary);"></i> الدولة
        </label>
        <select id="countryFilter" class="form-control" style="font-size: 12px; height: 38px; font-weight: 700;">
          <option value="">🌍 كل الدول</option>
          <?php foreach (getSupportedCountries() as $cCode => $cInfo): ?>
            <option value="<?= e($cCode) ?>" <?= $country === $cCode ? 'selected' : '' ?>>
              <?= $cInfo['flag'] ?> <?= e($cInfo['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- Server Filter -->
      <div>
        <label style="font-size: 11.5px; font-weight: 700; color: var(--text-muted); margin-bottom: 4px; display: block;">
          <i class="fas fa-server" style="color: var(--primary);"></i> السيرفر
        </label>
        <select id="serverFilter" class="form-control" style="font-size: 12px; height: 38px; font-weight: 700;">
          <option value="" <?= $server === '' ? 'selected' : '' ?>>🖥️ كل السيرفرات</option>
          <?php foreach ($availableServers as $srv): 
            $srvLabel = $srv;
            if ($srv === 'cp.enjaz.cloud') $srvLabel = 'السيرفر الأول (cp)';
            elseif ($srv === 'panel.enjaz.cloud') $srvLabel = 'السيرفر الثاني (panel)';
          ?>
          <option value="<?= e($srv) ?>" <?= $server === $srv ? 'selected' : '' ?>>
            🖥️ <?= e($srvLabel) ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- Status Filter -->
      <div>
        <label style="font-size: 11.5px; font-weight: 700; color: var(--text-muted); margin-bottom: 4px; display: block;">
          <i class="fas fa-user-check" style="color: var(--primary);"></i> حالة العميل
        </label>
        <select id="statusFilter" class="form-control" style="font-size: 12px; height: 38px; font-weight: 700;">
          <option value="1" <?= $clientStatus === '1' ? 'selected' : '' ?>>🟢 النشطين فقط</option>
          <option value="0" <?= $clientStatus === '0' ? 'selected' : '' ?>>🔴 الموقوفين فقط</option>
          <option value="all" <?= $clientStatus === 'all' ? 'selected' : '' ?>>🔵 كل العملاء</option>
        </select>
      </div>

      <!-- Days Filter -->
      <div>
        <label style="font-size: 11.5px; font-weight: 700; color: var(--text-muted); margin-bottom: 4px; display: block;">
          <i class="fas fa-calendar-days" style="color: var(--warning);"></i> فترة الانتهاء
        </label>
        <select id="daysFilter" class="form-control" style="font-size: 12px; height: 38px; font-weight: 700;">
          <?php foreach ([7,14,30,60,90,120,180,270,365] as $d): ?>
          <option value="<?= $d ?>" <?= $filterDays == $d ? 'selected' : '' ?>>خلال <?= $d ?> يوم القادمة</option>
          <?php endforeach; ?>
        </select>
      </div>

    </div>
  </div>

  <!-- Renewals Table Card -->
  <div class="card" style="border-radius: 12px; overflow: hidden; box-shadow: 0 4px 16px rgba(0,0,0,0.03);">
    <div class="card-header" style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px; padding: 14px 20px;">
      <span class="card-title" style="font-size: 15px; font-weight: 800; display: flex; align-items: center; gap: 8px;">
        <i class="fas fa-list-check" style="color: var(--primary);"></i>
        <span>جدول الاشتراكات والعملاء المستحقين للتجديد</span>
      </span>
      <div style="display: flex; align-items: center; gap: 8px;">
        <button type="button" class="btn btn-sm btn-outline" onclick="expandAllDetails()" style="font-size: 11.5px; padding: 4px 10px;">
          <i class="fas fa-chevron-down"></i> فتح الكل
        </button>
        <button type="button" class="btn btn-sm btn-outline" onclick="collapseAllDetails()" style="font-size: 11.5px; padding: 4px 10px;">
          <i class="fas fa-chevron-up"></i> إغلاق الكل
        </button>
      </div>
    </div>

    <div class="table-wrapper" style="margin: 0; box-shadow: none;">
      <table class="data-table" style="margin: 0; width: 100%;">
        <thead>
          <tr style="background-color: var(--bg-hover);">
            <th style="width: 45px; text-align: center;">
              <input type="checkbox" id="selectAll" class="form-check-input" style="cursor: pointer; width: 18px; height: 18px;">
            </th>
            <th>العميل</th>
            <th>الشركة</th>
            <th>السيرفر</th>
            <th>الخدمات</th>
            <th>مجموع المبلغ</th>
            <th>أقرب تاريخ انتهاء</th>
            <th>المتبقي</th>
            <th style="width: 90px; text-align: center;">إجراءات</th>
          </tr>
        </thead>
        <tbody id="renewalsTbody">
          <?php renderRenewalsTable($clients); ?>
        </tbody>
        <tfoot>
          <tr style="background: rgba(36,86,164,0.03); font-weight: 800; border-top: 2px solid var(--border-color);">
            <td colspan="5" style="text-align: left; padding: 14px 20px; font-size: 13.5px;">الإجمالي الكلي للتجديدات:</td>
            <td id="grandTotalCell" style="padding: 14px 20px; color: var(--success); font-size: 16px; font-weight: 900;">
              <?= formatMoney($grandTotalRenewals) ?>
            </td>
            <td colspan="3"></td>
          </tr>
        </tfoot>
      </table>
    </div>
  </div>
</form>

<script>
function toggleClientDetails(clientId) {
    const row = document.getElementById('details-row-' + clientId);
    const arrow = document.getElementById('arrow-details-' + clientId);
    if (row) {
        if (row.style.display === 'none' || row.style.display === '') {
            row.style.display = 'table-row';
            if (arrow) arrow.style.transform = 'rotate(180deg)';
        } else {
            row.style.display = 'none';
            if (arrow) arrow.style.transform = 'rotate(0deg)';
        }
    }
}

function expandAllDetails() {
    document.querySelectorAll('.details-row').forEach(row => {
        row.style.display = 'table-row';
    });
    document.querySelectorAll('[id^="arrow-details-"]').forEach(arr => {
        arr.style.transform = 'rotate(180deg)';
    });
}

function collapseAllDetails() {
    document.querySelectorAll('.details-row').forEach(row => {
        row.style.display = 'none';
    });
    document.querySelectorAll('[id^="arrow-details-"]').forEach(arr => {
        arr.style.transform = 'rotate(0deg)';
    });
}

// تصدير جدول التجديدات بصيغة Excel CSV مع ترميز UTF-8 مع BOM
function exportRenewalsExcel() {
    const table = document.querySelector('.data-table');
    if (!table) return;

    let csvContent = "\uFEFF"; // UTF-8 BOM
    csvContent += "العميل,الشركة,السيرفر,مجموع المبلغ,أقرب تاريخ انتهاء,المتبقي\n";

    const rows = document.querySelectorAll('.client-row');
    rows.forEach(row => {
        const cols = row.querySelectorAll('td');
        if (cols.length >= 8) {
            const clientName = cols[1].innerText.replace(/[\n\r]+/g, ' ').trim().replace(/"/g, '""');
            const company    = cols[2].innerText.replace(/[\n\r]+/g, ' ').trim().replace(/"/g, '""');
            const server     = cols[3].innerText.replace(/[\n\r]+/g, ' ').trim().replace(/"/g, '""');
            const amount     = cols[5].innerText.replace(/[\n\r]+/g, ' ').trim().replace(/"/g, '""');
            const endDate    = cols[6].innerText.replace(/[\n\r]+/g, ' ').trim().replace(/"/g, '""');
            const remaining  = cols[7].innerText.replace(/[\n\r]+/g, ' ').trim().replace(/"/g, '""');

            csvContent += `"${clientName}","${company}","${server}","${amount}","${endDate}","${remaining}"\n`;
        }
    });

    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement("a");
    link.setAttribute("href", url);
    link.setAttribute("download", `تقرير_التجديدات_${new Date().toISOString().slice(0,10)}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

document.addEventListener('DOMContentLoaded', function() {
    const searchInput       = document.getElementById('searchInput');
    const daysSelect        = document.getElementById('daysFilter');
    const statusSelect      = document.getElementById('statusFilter');
    const countrySelect     = document.getElementById('countryFilter');
    const serverSelect      = document.getElementById('serverFilter');
    const tbody             = document.getElementById('renewalsTbody');
    const subtitle          = document.getElementById('renewalsSubtitle');
    const bulkWhatsappBtn   = document.getElementById('bulkWhatsappBtn');
    const sendBtn           = document.getElementById('sendSelectedWhatsappBtn');
    const selectedCountSpan = document.getElementById('selectedCount');
    const allCountSpan      = document.getElementById('allCount');
    const selectAllCheckbox = document.getElementById('selectAll');
    const daysInput         = document.getElementById('daysInput');
    const statusInput       = document.getElementById('statusInput');
    const countryInput      = document.getElementById('countryInput');
    const serverInput       = document.getElementById('serverInput');
    const kpiClientsCount   = document.getElementById('kpiClientsCount');
    const kpiGrandTotal     = document.getElementById('kpiGrandTotal');
    const kpiUrgentCount    = document.getElementById('kpiUrgentCount');
    const kpiSubsCount      = document.getElementById('kpiSubsCount');

    function updateSendButtonState() {
        const checkedBoxes = document.querySelectorAll('.client-checkbox:checked');
        if (sendBtn) {
            sendBtn.disabled = checkedBoxes.length === 0;
        }
        if (selectedCountSpan) {
            selectedCountSpan.textContent = checkedBoxes.length;
        }
    }

    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            document.querySelectorAll('.client-checkbox').forEach(cb => {
                cb.checked = selectAllCheckbox.checked;
            });
            updateSendButtonState();
        });
    }

    if (tbody) {
        tbody.addEventListener('change', function(e) {
            if (e.target.classList.contains('client-checkbox')) {
                updateSendButtonState();
                if (selectAllCheckbox) {
                    const total = document.querySelectorAll('.client-checkbox').length;
                    const checked = document.querySelectorAll('.client-checkbox:checked').length;
                    selectAllCheckbox.checked = (total === checked && total > 0);
                }
            }
        });
    }

    let searchTimeout = null;

    function doSearch() {
        const searchVal  = searchInput ? searchInput.value.trim() : '';
        const daysVal    = daysSelect ? daysSelect.value : '60';
        const statusVal  = statusSelect ? statusSelect.value : '1';
        const countryVal = countrySelect ? countrySelect.value : '';
        const serverVal  = serverSelect ? serverSelect.value : '';

        const params = new URLSearchParams({
            search: searchVal,
            days: daysVal,
            status: statusVal,
            country: countryVal,
            server: serverVal,
            ajax: 1
        });

        // Update URL
        const cleanParams = new URLSearchParams();
        if (searchVal) cleanParams.set('search', searchVal);
        if (daysVal) cleanParams.set('days', daysVal);
        if (statusVal !== '1') cleanParams.set('status', statusVal);
        if (countryVal) cleanParams.set('country', countryVal);
        if (serverVal) cleanParams.set('server', serverVal);
        
        const newUrl = window.location.pathname + (cleanParams.toString() ? '?' + cleanParams.toString() : '');
        window.history.replaceState({path: newUrl}, '', newUrl);

        // Update hidden inputs
        if (daysInput) daysInput.value = daysVal;
        if (statusInput) statusInput.value = statusVal;
        if (countryInput) countryInput.value = countryVal;
        if (serverInput) serverInput.value = serverVal;

        // Update Bulk WhatsApp button url & confirm message
        if (bulkWhatsappBtn) {
            let bulkUrl = '../whatsapp/bulk.php?type=renewal&days=' + daysVal + '&status=' + statusVal;
            if (countryVal) bulkUrl += '&country=' + encodeURIComponent(countryVal);
            if (serverVal) bulkUrl += '&server=' + encodeURIComponent(serverVal);
            if (searchVal) bulkUrl += '&search=' + encodeURIComponent(searchVal);
            bulkWhatsappBtn.setAttribute('href', bulkUrl);
        }

        fetch('renewals.php?' + params.toString())
            .then(response => response.json())
            .then(data => {
                tbody.innerHTML = data.tbody;
                if (subtitle) subtitle.textContent = data.subtitle;
                
                const grandTotalCell = document.getElementById('grandTotalCell');
                if (grandTotalCell && data.grand_total_formatted) {
                    grandTotalCell.textContent = data.grand_total_formatted;
                }
                
                if (kpiClientsCount) kpiClientsCount.textContent = data.clients_count + ' عميل';
                if (kpiGrandTotal) kpiGrandTotal.textContent = data.grand_total_formatted;
                if (kpiUrgentCount) kpiUrgentCount.textContent = data.urgent_count + ' اشتراك';
                if (kpiSubsCount) kpiSubsCount.textContent = data.renewals_count + ' اشتراك';
                if (allCountSpan) allCountSpan.textContent = data.clients_count;

                const serviceTotalsContainer = document.getElementById('serviceTotalsContainer');
                if (serviceTotalsContainer && data.service_totals_html !== undefined) {
                    serviceTotalsContainer.innerHTML = data.service_totals_html;
                }
                
                // Reset checkbox state
                if (selectAllCheckbox) {
                    selectAllCheckbox.checked = false;
                }
                updateSendButtonState();

                if (bulkWhatsappBtn) {
                    if (data.clients_count > 0) {
                        bulkWhatsappBtn.style.display = 'inline-flex';
                        bulkWhatsappBtn.setAttribute('data-confirm', 'إرسال رسائل واتساب لكل ' + data.clients_count + ' عميل؟');
                    } else {
                        bulkWhatsappBtn.style.display = 'none';
                    }
                }
            })
            .catch(err => console.error('Error fetching renewals search results:', err));
    }

    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(doSearch, 300);
        });
    }

    if (daysSelect) daysSelect.addEventListener('change', doSearch);
    if (statusSelect) statusSelect.addEventListener('change', doSearch);
    if (countrySelect) countrySelect.addEventListener('change', doSearch);
    if (serverSelect) serverSelect.addEventListener('change', doSearch);
});
</script>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
