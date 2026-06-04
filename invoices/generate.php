<?php
/**
 * invoices/generate.php - إنشاء فاتورة للعميل
 */
require_once dirname(__DIR__) . '/config/app.php';
requireLogin();
requirePermission('print_invoices');

$db       = getDB();
$clientId = (int)($_GET['client_id'] ?? 0);

$clientStmt = $db->prepare("SELECT * FROM clients WHERE id=?");
$clientStmt->execute([$clientId]);
$client = $clientStmt->fetch();
if (!$client) { setFlash('error','العميل غير موجود.'); header('Location: ../clients/index.php'); exit; }

$subsStmt = $db->prepare("SELECT cs.*, s.name as service_name FROM client_subscriptions cs JOIN services s ON s.id=cs.service_id WHERE cs.client_id=? AND cs.status!='cancelled'");
$subsStmt->execute([$clientId]);
$subs = $subsStmt->fetchAll();
$summary  = getClientSummary($clientId);

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) { $errors[] = 'خطأ في الأمان.'; }
    else {
        $paidAmount = (float)($_POST['paid_amount'] ?? $summary['paid']);
        $notes      = clean($_POST['notes'] ?? '');
        $items      = [];
        foreach ($subs as $s) {
            $items[] = ['service' => $s['service_name'], 'plan' => $s['plan_name'], 'start' => $s['start_date'], 'end' => $s['end_date'], 'price' => $s['price']];
        }
        $invoiceNumber = getNextInvoiceNumber();
        $db->prepare("INSERT INTO invoices (invoice_number,client_id,total_amount,paid_amount,items,notes,status,created_by) VALUES (?,?,?,?,?,?,?,?)")
           ->execute([$invoiceNumber,$clientId,$summary['total'],$paidAmount,json_encode($items),$notes,'sent',currentUserId()]);
        $invoiceId = $db->lastInsertId();
        header("Location: print.php?id=$invoiceId");
        exit;
    }
}

$invoiceNumber = getSetting('invoice_prefix','INV').'-'.str_pad((int)getSetting('invoice_counter','1'),4,'0',STR_PAD_LEFT);
$companyName   = getSetting('company_name',APP_NAME);
$pageTitle     = 'إنشاء فاتورة';
$activePage    = 'invoices';
$depth         = 1;
require_once INCLUDES_PATH . '/header.php';
?>
<div class="page-header">
  <div class="page-header-text">
    <h1 class="page-title"><i class="fas fa-file-invoice" style="color:var(--primary-light);margin-left:8px;"></i>إنشاء فاتورة</h1>
    <p class="page-subtitle">للعميل: <?= e($client['name']) ?></p>
  </div>
  <div class="page-actions">
    <a href="../clients/view.php?id=<?= $clientId ?>" class="btn btn-outline"><i class="fas fa-arrow-right"></i> رجوع</a>
  </div>
</div>

<div style="display:grid;grid-template-columns:1fr 340px;gap:20px;align-items:start;">
  <div>
    <div class="card">
      <div class="card-header"><span class="card-title"><i class="fas fa-list-check"></i> بنود الفاتورة</span></div>
      <div class="table-wrapper">
        <table class="data-table">
          <thead><tr><th>الخدمة</th><th>الخطة</th><th>من</th><th>إلى</th><th>السعر</th></tr></thead>
          <tbody>
            <?php foreach ($subs as $s): ?>
            <tr>
              <td><strong><?= e($s['service_name']) ?></strong></td>
              <td class="text-muted"><?= e($s['plan_name'] ?: '—') ?></td>
              <td><?= formatDate($s['start_date']) ?></td>
              <td><?= formatDate($s['end_date']) ?></td>
              <td class="fw-bold"><?= formatMoney($s['price']) ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($subs)): ?>
            <tr><td colspan="5" class="text-muted text-center">لا توجد خدمات</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div>
    <div class="card">
      <div class="card-header"><span class="card-title"><i class="fas fa-calculator"></i> ملخص الفاتورة</span></div>
      <div class="card-body">
        <form method="POST" action="generate.php?client_id=<?= $clientId ?>">
          <?= csrfField() ?>

          <div style="background:#f8fafc;border-radius:10px;padding:14px;margin-bottom:18px;">
            <div class="totals-row" style="display:flex;justify-content:space-between;padding:6px 0;font-size:13.5px;border-bottom:1px solid #e2e8f0;">
              <span>رقم الفاتورة</span>
              <span class="fw-bold" style="color:var(--primary-light);"><?= e($invoiceNumber) ?></span>
            </div>
            <div class="totals-row" style="display:flex;justify-content:space-between;padding:6px 0;font-size:13.5px;border-bottom:1px solid #e2e8f0;">
              <span>إجمالي الخدمات</span>
              <span class="fw-bold"><?= formatMoney($summary['total']) ?></span>
            </div>
            <div class="totals-row" style="display:flex;justify-content:space-between;padding:6px 0;font-size:13.5px;color:var(--success);">
              <span>المدفوع</span>
              <span class="fw-bold"><?= formatMoney($summary['paid']) ?></span>
            </div>
            <div style="display:flex;justify-content:space-between;padding:10px 0 0;font-size:15px;font-weight:800;color:<?= $summary['remaining']>0?'var(--danger)':'var(--success)' ?>;">
              <span>المتبقي</span>
              <span><?= formatMoney($summary['remaining']) ?></span>
            </div>
          </div>

          <div class="form-group">
            <label class="form-label" for="paid_amount">المدفوع في الفاتورة</label>
            <input type="number" id="paid_amount" name="paid_amount" class="form-control" step="0.01" min="0" value="<?= $summary['paid'] ?>">
          </div>
          <div class="form-group">
            <label class="form-label" for="inv_notes">ملاحظات</label>
            <textarea id="inv_notes" name="notes" class="form-control" rows="3" placeholder="ملاحظات الفاتورة..."></textarea>
          </div>

          <button type="submit" class="btn btn-primary w-100">
            <i class="fas fa-file-invoice"></i> إنشاء وطباعة الفاتورة
          </button>
        </form>
      </div>
    </div>
  </div>
</div>
<?php require_once INCLUDES_PATH . '/footer.php'; ?>
