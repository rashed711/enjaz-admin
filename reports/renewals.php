<?php
/**
 * reports/renewals.php - تقرير التجديدات القريبة
 */
require_once dirname(__DIR__) . '/config/app.php';
requireLogin();
requirePermission('view_reports');

$db = getDB();
$warningDays = (int)getSetting('renewal_warning_days','30');
$filterDays  = (int)($_GET['days'] ?? $warningDays);

$stmt = $db->prepare("
    SELECT cs.*, c.name as client_name, c.mobile, c.company_name,
           s.name as service_name,
           DATEDIFF(cs.end_date, CURDATE()) as days_left
    FROM client_subscriptions cs
    JOIN clients c ON c.id=cs.client_id
    JOIN services s ON s.id=cs.service_id
    WHERE cs.status='active'
      AND cs.end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
    ORDER BY cs.end_date ASC
");
$stmt->execute([$filterDays]);
$renewals = $stmt->fetchAll();

// AJAX live search handler
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    ob_start();
    ?>
    <?php if (empty($renewals)): ?>
    <tr><td colspan="7">
      <div class="empty-state">
        <div class="empty-icon" style="color:var(--success);"><i class="fas fa-check-circle"></i></div>
        <p class="empty-title">ممتاز! لا توجد اشتراكات تنتهي قريباً</p>
      </div>
    </td></tr>
    <?php else: ?>
    <?php foreach ($renewals as $r): ?>
    <tr>
      <td>
        <a href="../clients/view.php?id=<?= $r['client_id'] ?>" style="font-weight:700;">
          <?= e($r['client_name']) ?>
        </a>
        <div style="font-size:11.5px;color:var(--text-muted);">
          <i class="fab fa-whatsapp" style="color:#25D366;"></i> <?= e($r['mobile']) ?>
        </div>
      </td>
      <td class="text-muted"><?= e($r['company_name'] ?: '—') ?></td>
      <td><?= e($r['service_name']) ?></td>
      <td class="fw-bold"><?= formatMoney($r['price']) ?></td>
      <td><?= formatDate($r['end_date']) ?></td>
      <td>
        <?php if ($r['days_left'] <= 0): ?>
          <span class="badge badge-danger">انتهى</span>
        <?php elseif ($r['days_left'] <= 7): ?>
          <span class="badge badge-danger"><?= $r['days_left'] ?> يوم</span>
        <?php elseif ($r['days_left'] <= 14): ?>
          <span class="badge badge-warning"><?= $r['days_left'] ?> يوم</span>
        <?php else: ?>
          <span class="badge badge-info"><?= $r['days_left'] ?> يوم</span>
        <?php endif; ?>
      </td>
      <td>
        <div class="table-actions">
          <a href="../clients/view.php?id=<?= $r['client_id'] ?>" class="btn btn-sm btn-primary"><i class="fas fa-eye"></i></a>
          <a href="../subscriptions/renew.php?id=<?= $r['id'] ?>" class="btn btn-sm btn-success"><i class="fas fa-redo"></i> تجديد</a>
        </div>
      </td>
    </tr>
    <?php endforeach; ?>
    <?php endif; ?>
    <?php
    $tbodyHtml = ob_get_clean();

    echo json_encode([
        'tbody' => $tbodyHtml,
        'subtitle' => count($renewals) . ' اشتراك ينتهي خلال ' . $filterDays . ' يوم القادمة'
    ]);
    exit;
}

$pageTitle  = 'تقرير التجديدات';
$activePage = 'reports-renewals';
$depth      = 1;
require_once INCLUDES_PATH . '/header.php';
?>
<div class="page-header">
  <div class="page-header-text">
    <h1 class="page-title"><i class="fas fa-calendar-exclamation" style="color:var(--warning);margin-left:8px;"></i>تقرير التجديدات القريبة</h1>
    <p class="page-subtitle"><?= count($renewals) ?> اشتراك ينتهي خلال <?= $filterDays ?> يوم القادمة</p>
  </div>
  <div class="page-actions">
    <form method="GET" style="display:flex;gap:10px;align-items:center;" id="filterForm">
      <label style="font-size:13px;font-weight:600;">خلال</label>
      <select name="days" class="form-control" style="width:auto;">
        <?php foreach ([7,14,30,60,90] as $d): ?>
        <option value="<?= $d ?>" <?= $filterDays===$d?'selected':'' ?>><?= $d ?> يوم</option>
        <?php endforeach; ?>
      </select>
    </form>
    <?php if (hasPermission('send_whatsapp') && !empty($renewals)): ?>
    <a href="../whatsapp/bulk.php?type=renewal&days=<?= $filterDays ?>"
       class="btn btn-success"
       id="bulkWhatsappBtn"
       data-confirm="إرسال رسائل واتساب لكل <?= count($renewals) ?> عميل؟">
      <i class="fab fa-whatsapp"></i> إرسال للكل
    </a>
    <?php endif; ?>
  </div>
</div>

<div class="card">
  <div class="table-wrapper">
    <table class="data-table">
      <thead>
        <tr><th>العميل</th><th>الشركة</th><th>الخدمة</th><th>السعر</th><th>تاريخ الانتهاء</th><th>المتبقي</th><th>الإجراءات</th></tr>
      </thead>
      <tbody>
        <?php if (empty($renewals)): ?>
        <tr><td colspan="7">
          <div class="empty-state">
            <div class="empty-icon" style="color:var(--success);"><i class="fas fa-check-circle"></i></div>
            <p class="empty-title">ممتاز! لا توجد اشتراكات تنتهي قريباً</p>
          </div>
        </td></tr>
        <?php else: ?>
        <?php foreach ($renewals as $r): ?>
        <tr>
          <td>
            <a href="../clients/view.php?id=<?= $r['client_id'] ?>" style="font-weight:700;">
              <?= e($r['client_name']) ?>
            </a>
            <div style="font-size:11.5px;color:var(--text-muted);">
              <i class="fab fa-whatsapp" style="color:#25D366;"></i> <?= e($r['mobile']) ?>
            </div>
          </td>
          <td class="text-muted"><?= e($r['company_name'] ?: '—') ?></td>
          <td><?= e($r['service_name']) ?></td>
          <td class="fw-bold"><?= formatMoney($r['price']) ?></td>
          <td><?= formatDate($r['end_date']) ?></td>
          <td>
            <?php if ($r['days_left'] <= 0): ?>
              <span class="badge badge-danger">انتهى</span>
            <?php elseif ($r['days_left'] <= 7): ?>
              <span class="badge badge-danger"><?= $r['days_left'] ?> يوم</span>
            <?php elseif ($r['days_left'] <= 14): ?>
              <span class="badge badge-warning"><?= $r['days_left'] ?> يوم</span>
            <?php else: ?>
              <span class="badge badge-info"><?= $r['days_left'] ?> يوم</span>
            <?php endif; ?>
          </td>
          <td>
            <div class="table-actions">
              <a href="../clients/view.php?id=<?= $r['client_id'] ?>" class="btn btn-sm btn-primary"><i class="fas fa-eye"></i></a>
              <a href="../subscriptions/renew.php?id=<?= $r['id'] ?>" class="btn btn-sm btn-success"><i class="fas fa-redo"></i> تجديد</a>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const daysSelect = document.querySelector('select[name="days"]');
    const filterForm = document.getElementById('filterForm');
    const tbody = document.querySelector('.data-table tbody');
    const subtitle = document.querySelector('.page-subtitle');
    const bulkWhatsappBtn = document.getElementById('bulkWhatsappBtn');

    filterForm.addEventListener('submit', function(e) {
        e.preventDefault();
        doSearch();
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

        // Update Bulk WhatsApp button url & confirm message
        if (bulkWhatsappBtn) {
            bulkWhatsappBtn.setAttribute('href', '../whatsapp/bulk.php?type=renewal&days=' + daysVal);
        }

        fetch('renewals.php?' + params.toString())
            .then(response => response.json())
            .then(data => {
                tbody.innerHTML = data.tbody;
                subtitle.textContent = data.subtitle;
                
                // Dynamically update confirm message
                const count = tbody.querySelectorAll('tr:not(:first-child)').length || (tbody.querySelector('.empty-state') ? 0 : 1);
                if (bulkWhatsappBtn) {
                    if (count > 0) {
                        bulkWhatsappBtn.style.display = 'inline-flex';
                        bulkWhatsappBtn.setAttribute('data-confirm', 'إرسال رسائل واتساب لكل ' + count + ' عميل؟');
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
