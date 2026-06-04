<?php
/**
 * invoices/index.php - قائمة الفواتير
 */
require_once dirname(__DIR__) . '/config/app.php';
requireLogin();
requirePermission('print_invoices');

$db  = getDB();
$invs = $db->query("
    SELECT i.*, c.name as client_name, c.company_name
    FROM invoices i JOIN clients c ON c.id=i.client_id
    ORDER BY i.created_at DESC LIMIT 100
")->fetchAll();

$pageTitle  = 'الفواتير';
$activePage = 'invoices';
$depth      = 1;
require_once INCLUDES_PATH . '/header.php';
?>
<div class="page-header">
  <div class="page-header-text">
    <h1 class="page-title"><i class="fas fa-file-invoice" style="color:var(--primary-light);margin-left:8px;"></i>الفواتير</h1>
    <p class="page-subtitle">سجل الفواتير المُصدَرة</p>
  </div>
</div>
<div class="card">
  <div class="table-wrapper">
    <table class="data-table">
      <thead>
        <tr><th>#</th><th>رقم الفاتورة</th><th>العميل</th><th>الإجمالي</th><th>المدفوع</th><th>المتبقي</th><th>الحالة</th><th>التاريخ</th><th>إجراء</th></tr>
      </thead>
      <tbody>
        <?php if (empty($invs)): ?>
        <tr><td colspan="9"><div class="empty-state"><div class="empty-icon"><i class="fas fa-file-invoice"></i></div><p class="empty-title">لا توجد فواتير</p></div></td></tr>
        <?php else: ?>
        <?php foreach ($invs as $i => $inv): ?>
        <tr>
          <td class="text-muted"><?= $i+1 ?></td>
          <td><strong style="color:var(--primary-light);"><?= e($inv['invoice_number']) ?></strong></td>
          <td>
            <a href="../clients/view.php?id=<?= $inv['client_id'] ?>" style="font-weight:600;"><?= e($inv['client_name']) ?></a>
            <?php if ($inv['company_name']): ?><div style="font-size:11.5px;color:var(--text-muted);"><?= e($inv['company_name']) ?></div><?php endif; ?>
          </td>
          <td class="fw-bold"><?= formatMoney($inv['total_amount']) ?></td>
          <td style="color:var(--success);font-weight:600;"><?= formatMoney($inv['paid_amount']) ?></td>
          <td style="color:<?= $inv['remaining']>0?'var(--danger)':'var(--success)' ?>;font-weight:700;"><?= formatMoney($inv['remaining']) ?></td>
          <td><?= invoiceStatusBadge($inv['status']) ?></td>
          <td class="text-muted"><?= formatDate($inv['created_at']) ?></td>
          <td>
            <a href="print.php?id=<?= $inv['id'] ?>" class="btn btn-sm btn-primary" target="_blank">
              <i class="fas fa-print"></i>
            </a>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require_once INCLUDES_PATH . '/footer.php'; ?>
