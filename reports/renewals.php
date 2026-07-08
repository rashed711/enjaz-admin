<?php
/**
 * reports/renewals.php - تقرير التجديدات القريبة
 */
require_once dirname(__DIR__) . '/config/app.php';
requireLogin();
requirePermission('view_reports');

$db = getDB();
$warningDays = (int)getSetting('renewal_warning_days','60');
$filterDays  = (int)($_GET['days'] ?? $warningDays);

$stmt = $db->prepare("
    SELECT cs.*, c.name as client_name, c.mobile, c.company_name,
           s.name as service_name,
           DATEDIFF(cs.end_date, CURDATE()) as days_left
    FROM client_subscriptions cs
    JOIN clients c ON c.id=cs.client_id
    JOIN services s ON s.id=cs.service_id
    WHERE cs.status='active'
      AND cs.end_date IS NOT NULL
      AND cs.end_date <= DATE_ADD(CURDATE(), INTERVAL ? DAY)
    ORDER BY cs.end_date ASC
");
$stmt->execute([$filterDays]);
$renewals = $stmt->fetchAll();

// Group by client
$clients = [];
foreach ($renewals as $r) {
    $cId = $r['client_id'];
    if (!isset($clients[$cId])) {
        $clients[$cId] = [
            'client_id' => $cId,
            'client_name' => $r['client_name'],
            'mobile' => $r['mobile'],
            'company_name' => $r['company_name'],
            'total_price' => 0,
            'min_days_left' => 999999,
            'min_end_date' => $r['end_date'],
            'subscriptions' => []
        ];
    }
    $clients[$cId]['total_price'] += $r['price'];
    if ($r['days_left'] < $clients[$cId]['min_days_left']) {
        $clients[$cId]['min_days_left'] = $r['days_left'];
        $clients[$cId]['min_end_date'] = $r['end_date'];
    }
    $clients[$cId]['subscriptions'][] = $r;
}

function renderRenewalsTable($clients) {
    if (empty($clients)): ?>
    <tr><td colspan="8">
      <div class="empty-state">
        <div class="empty-icon" style="color:var(--success);"><i class="fas fa-check-circle"></i></div>
        <p class="empty-title">ممتاز! لا توجد اشتراكات تنتهي قريباً</p>
      </div>
    </td></tr>
    <?php else: ?>
    <?php foreach ($clients as $cId => $c): ?>
    <tr class="client-row" data-client-id="<?= $cId ?>">
      <td style="text-align: center; vertical-align: middle;">
        <input type="checkbox" name="client_ids[]" value="<?= $cId ?>" class="client-checkbox form-check-input" style="cursor: pointer; width: 18px; height: 18px;">
      </td>
      <td>
        <a href="../clients/view.php?id=<?= $cId ?>" style="font-weight:700; color: var(--primary);">
          <?= e($c['client_name']) ?>
        </a>
        <div style="font-size:11.5px;color:var(--text-muted); margin-top: 2px;">
          <i class="fab fa-whatsapp" style="color:#25D366;"></i> <?= e($c['mobile']) ?>
        </div>
      </td>
      <td class="text-muted"><?= e($c['company_name'] ?: '—') ?></td>
      <td>
        <button type="button" class="btn btn-sm btn-outline-primary" onclick="toggleClientDetails(<?= $cId ?>)" style="border-radius: 20px; font-weight: 600; font-size: 12px; padding: 2px 12px;">
          <i class="fas fa-list-ul" style="margin-left: 4px;"></i><?= count($c['subscriptions']) ?> <?= count($c['subscriptions']) > 1 ? 'خدمات' : 'خدمة' ?>
        </button>
      </td>
      <td class="fw-bold text-success"><?= formatMoney($c['total_price']) ?></td>
      <td><?= formatDate($c['min_end_date']) ?></td>
      <td>
        <?php if ($c['min_days_left'] <= 0): ?>
          <span class="badge badge-danger">انتهى</span>
        <?php elseif ($c['min_days_left'] <= 7): ?>
          <span class="badge badge-danger"><?= $c['min_days_left'] ?> يوم</span>
        <?php elseif ($c['min_days_left'] <= 14): ?>
          <span class="badge badge-warning"><?= $c['min_days_left'] ?> يوم</span>
        <?php else: ?>
          <span class="badge badge-info"><?= $c['min_days_left'] ?> يوم</span>
        <?php endif; ?>
      </td>
      <td>
        <div class="table-actions">
          <button type="button" class="btn btn-sm btn-outline-info" onclick="toggleClientDetails(<?= $cId ?>)" style="padding: 2px 8px;">
            <i class="fas fa-chevron-down"></i> تفاصيل
          </button>
          <a href="../clients/view.php?id=<?= $cId ?>" class="btn btn-sm btn-primary" title="عرض الملف"><i class="fas fa-eye"></i></a>
        </div>
      </td>
    </tr>
    <!-- Collapsible Details Row -->
    <tr id="details-row-<?= $cId ?>" class="details-row" style="display:none; background-color: rgba(var(--primary-rgb, 0, 0, 0), 0.02); border-bottom: 2px solid var(--border-color);">
      <td></td>
      <td colspan="7" style="padding: 12px 20px;">
        <div style="padding: 10px 15px; border-right: 4px solid var(--primary); background: var(--card-bg); border-radius: 4px; box-shadow: inset 0 0 5px rgba(0,0,0,0.03);">
          <h5 style="margin-bottom:12px; font-size:13px; color:var(--primary); font-weight:700; display: flex; align-items: center; gap: 6px;">
            <i class="fas fa-concierge-bell"></i>
            <span>الخدمات التي تحتاج إلى تجديد:</span>
          </h5>
          <div class="table-wrapper" style="margin: 0; box-shadow: none; border: 1px solid var(--border-color); border-radius: 6px; overflow: hidden;">
            <table class="data-table" style="margin: 0; font-size: 12.5px; width: 100%;">
              <thead>
                <tr style="background-color: var(--bg-hover);">
                  <th>الخدمة</th>
                  <th>الخطة</th>
                  <th>السعر</th>
                  <th>تاريخ الانتهاء</th>
                  <th>الأيام المتبقية</th>
                  <th style="width:100px; text-align: center;">الإجراء</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($c['subscriptions'] as $sub): ?>
                <tr>
                  <td style="font-weight: 600;"><?= e($sub['service_name']) ?></td>
                  <td class="text-muted"><?= e($sub['plan_name'] ?: '—') ?></td>
                  <td class="fw-bold"><?= formatMoney($sub['price']) ?></td>
                  <td><?= formatDate($sub['end_date']) ?></td>
                  <td>
                    <?php if ($sub['days_left'] <= 0): ?>
                      <span class="badge badge-danger">انتهى</span>
                    <?php elseif ($sub['days_left'] <= 7): ?>
                      <span class="badge badge-danger"><?= $sub['days_left'] ?> يوم</span>
                    <?php elseif ($sub['days_left'] <= 14): ?>
                      <span class="badge badge-warning"><?= $sub['days_left'] ?> يوم</span>
                    <?php else: ?>
                      <span class="badge badge-info"><?= $sub['days_left'] ?> يوم</span>
                    <?php endif; ?>
                  </td>
                  <td style="text-align: center;">
                    <a href="../subscriptions/renew.php?id=<?= $sub['id'] ?>" class="btn btn-xs btn-success" style="padding: 2px 8px; font-size: 11px;"><i class="fas fa-redo"></i> تجديد</a>
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

    echo json_encode([
        'tbody' => $tbodyHtml,
        'subtitle' => count($clients) . ' عميل لديهم تجديدات قريبة خلال ' . $filterDays . ' يوم القادمة',
        'clients_count' => count($clients),
        'renewals_count' => count($renewals)
    ]);
    exit;
}

$pageTitle  = 'تقرير التجديدات';
$activePage = 'reports-renewals';
$depth      = 1;
require_once INCLUDES_PATH . '/header.php';
?>
<form id="bulkWhatsappForm" method="POST" action="../whatsapp/bulk.php?type=renewal">
  <input type="hidden" name="days" value="<?= $filterDays ?>" id="daysInput">

  <div class="page-header">
    <div class="page-header-text">
      <h1 class="page-title"><i class="fas fa-calendar-exclamation" style="color:var(--warning);margin-left:8px;"></i>تقرير التجديدات القريبة</h1>
      <p class="page-subtitle"><?= count($clients) ?> عميل لديهم تجديدات قريبة خلال <?= $filterDays ?> يوم القادمة</p>
    </div>
    <div class="page-actions" style="gap: 12px;">
      <div style="display:flex;gap:10px;align-items:center;" id="filterFormContainer">
        <label style="font-size:13px;font-weight:600;">خلال</label>
        <select name="days_filter" id="daysFilter" class="form-control" style="width:auto;">
          <?php foreach ([7,14,30,60,90,120,180,270,365] as $d): ?>
          <option value="<?= $d ?>" <?= $filterDays == $d ? 'selected' : '' ?>><?= $d ?> يوم</option>
          <?php endforeach; ?>
        </select>
      </div>

      <?php if (hasPermission('send_whatsapp') && !empty($clients)): ?>
      <button type="submit" class="btn btn-success" id="sendSelectedWhatsappBtn" disabled style="display: inline-flex; align-items: center; gap: 6px;">
        <i class="fab fa-whatsapp"></i>
        <span>إرسال للمحددين (<span id="selectedCount">0</span>)</span>
      </button>

      <a href="../whatsapp/bulk.php?type=renewal&days=<?= $filterDays ?>"
         class="btn btn-outline-success"
         id="bulkWhatsappBtn"
         data-confirm="إرسال رسائل واتساب لكل <?= count($clients) ?> عميل؟"
         style="display: inline-flex; align-items: center; gap: 6px;">
        <i class="fas fa-paper-plane"></i> إرسال للكل
      </a>
      <?php endif; ?>
    </div>
  </div>

  <div class="card">
    <div class="table-wrapper">
      <table class="data-table">
        <thead>
          <tr>
            <th style="width: 45px; text-align: center;">
              <input type="checkbox" id="selectAll" class="form-check-input" style="cursor: pointer; width: 18px; height: 18px;">
            </th>
            <th>العميل</th>
            <th>الشركة</th>
            <th>الخدمات المنتهية</th>
            <th>مجموع المبالغ</th>
            <th>أقرب تاريخ انتهاء</th>
            <th>المتبقي</th>
            <th style="width: 150px;">الإجراءات</th>
          </tr>
        </thead>
        <tbody>
          <?php renderRenewalsTable($clients); ?>
        </tbody>
      </table>
    </div>
  </div>
</form>

<script>
function toggleClientDetails(clientId) {
    const row = document.getElementById('details-row-' + clientId);
    if (row) {
        if (row.style.display === 'none') {
            row.style.display = 'table-row';
        } else {
            row.style.display = 'none';
        }
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const daysSelect = document.getElementById('daysFilter');
    const tbody = document.querySelector('.data-table tbody');
    const subtitle = document.querySelector('.page-subtitle');
    const bulkWhatsappBtn = document.getElementById('bulkWhatsappBtn');
    const sendBtn = document.getElementById('sendSelectedWhatsappBtn');
    const selectedCountSpan = document.getElementById('selectedCount');
    const selectAllCheckbox = document.getElementById('selectAll');
    const daysInput = document.getElementById('daysInput');

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

    function doSearch() {
        const daysVal = daysSelect.value;
        const params = new URLSearchParams({
            days: daysVal,
            ajax: 1
        });

        // Update URL
        const cleanParams = new URLSearchParams({ days: daysVal });
        const newUrl = window.location.pathname + '?' + cleanParams.toString();
        window.history.replaceState({path: newUrl}, '', newUrl);

        // Update hidden input
        if (daysInput) {
            daysInput.value = daysVal;
        }

        // Update Bulk WhatsApp button url & confirm message
        if (bulkWhatsappBtn) {
            bulkWhatsappBtn.setAttribute('href', '../whatsapp/bulk.php?type=renewal&days=' + daysVal);
        }

        fetch('renewals.php?' + params.toString())
            .then(response => response.json())
            .then(data => {
                tbody.innerHTML = data.tbody;
                subtitle.textContent = data.subtitle;
                
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
            .catch(err => console.error('Error fetching search results:', err));
    }

    daysSelect.addEventListener('change', doSearch);
});
</script>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
