<?php
/**
 * reports/services-summary.php - ملخص الخدمات
 */
require_once dirname(__DIR__) . '/config/app.php';
requireLogin();
requirePermission('view_reports');

$db = getDB();
$services = $db->query("
    SELECT s.name, s.default_price,
           COUNT(DISTINCT CASE WHEN cs.status='active' THEN cs.client_id END) as active_clients,
           COUNT(DISTINCT cs.client_id) as total_clients,
           COALESCE(SUM(CASE WHEN cs.status!='cancelled' THEN cs.price ELSE 0 END),0) as total_revenue,
           COUNT(CASE WHEN cs.status='active' AND cs.end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(),INTERVAL 30 DAY) THEN 1 END) as expiring_soon
    FROM services s
    LEFT JOIN client_subscriptions cs ON cs.service_id=s.id
    GROUP BY s.id ORDER BY total_revenue DESC
")->fetchAll();

$pageTitle  = 'ملخص الخدمات';
$activePage = 'reports-services';
$depth      = 1;
require_once INCLUDES_PATH . '/header.php';
?>
<div class="page-header">
  <div class="page-header-text">
    <h1 class="page-title"><i class="fas fa-layer-group" style="color:var(--primary-light);margin-left:8px;"></i>ملخص الخدمات</h1>
    <p class="page-subtitle">مقارنة أداء كل خدمة</p>
  </div>
</div>

<div class="card">
  <div class="table-wrapper">
    <table class="data-table">
      <thead>
        <tr><th>#</th><th>الخدمة</th><th>السعر الافتراضي</th><th>إجمالي العملاء</th><th>العملاء النشطين</th><th>تنتهي قريباً</th><th>إجمالي الإيرادات</th></tr>
      </thead>
      <tbody>
        <?php if (empty($services)): ?>
        <tr><td colspan="7"><div class="empty-state"><p class="empty-title">لا توجد خدمات</p></div></td></tr>
        <?php else: ?>
        <?php foreach ($services as $i => $srv): ?>
        <tr>
          <td class="text-muted"><?= $i+1 ?></td>
          <td><strong><?= e($srv['name']) ?></strong></td>
          <td><?= formatMoney($srv['default_price']) ?></td>
          <td><span class="badge badge-primary"><?= $srv['total_clients'] ?></span></td>
          <td><span class="badge badge-success"><?= $srv['active_clients'] ?></span></td>
          <td>
            <?php if ($srv['expiring_soon'] > 0): ?>
            <span class="badge badge-warning"><?= $srv['expiring_soon'] ?></span>
            <?php else: ?>
            <span class="text-muted">—</span>
            <?php endif; ?>
          </td>
          <td class="fw-bold" style="color:var(--primary-light);"><?= formatMoney($srv['total_revenue']) ?></td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require_once INCLUDES_PATH . '/footer.php'; ?>
