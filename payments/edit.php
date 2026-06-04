<?php
/**
 * payments/edit.php - تعديل دفعة
 */
require_once dirname(__DIR__) . '/config/app.php';
requireLogin();
requirePermission('add_payments');

$db = getDB();
$id = (int)($_GET['id'] ?? 0);

$stmt = $db->prepare("SELECT p.*, c.name as client_name FROM payments p JOIN clients c ON c.id=p.client_id WHERE p.id=?");
$stmt->execute([$id]);
$payment = $stmt->fetch();
if (!$payment) {
    setFlash('error', 'الدفعة غير موجودة.');
    header('Location: ../clients/index.php');
    exit;
}

$errors = [];
$formData = $payment;

// Get client active subscriptions for dropdown
$subs = $db->prepare("SELECT cs.id, s.name as service_name, cs.plan_name FROM client_subscriptions cs JOIN services s ON s.id=cs.service_id WHERE cs.client_id = ? AND cs.status='active'");
$subs->execute([$payment['client_id']]);
$subscriptions = $subs->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) { $errors[] = 'خطأ في الأمان.'; }
    else {
        $formData['amount']           = (float)($_POST['amount'] ?? 0);
        $formData['payment_date']     = clean($_POST['payment_date'] ?? '');
        $formData['payment_method']   = clean($_POST['payment_method'] ?? 'كاش');
        $formData['reference_number'] = clean($_POST['reference_number'] ?? '');
        $formData['notes']            = clean($_POST['notes'] ?? '');
        $formData['subscription_id']  = (int)($_POST['subscription_id'] ?? 0) ?: null;

        if ($formData['amount'] <= 0)  $errors[] = 'المبلغ يجب أن يكون أكبر من صفر.';
        if (!$formData['payment_date']) $errors[] = 'تاريخ الدفع مطلوب.';

        // رفع وحفظ ملف الإيصال
        $receiptFile = $payment['receipt_file'];
        if (empty($errors) && !empty($_FILES['receipt_file']['name'])) {
            $file = $_FILES['receipt_file'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'pdf'];
            if (!in_array($ext, $allowed)) {
                $errors[] = 'نوع الملف المرفق غير مسموح به (فقط الصور وملفات PDF).';
            } else if ($file['size'] > 5 * 1024 * 1024) {
                $errors[] = 'حجم الملف المرفق يجب ألا يتجاوز 5 ميجا بايت.';
            } else {
                $uploadDir = ROOT_PATH . '/uploads/receipts/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                $filename = time() . '_' . uniqid() . '.' . $ext;
                if (move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
                    // حذف الملف القديم إن وجد
                    if (!empty($payment['receipt_file']) && file_exists(ROOT_PATH . '/' . $payment['receipt_file'])) {
                        @unlink(ROOT_PATH . '/' . $payment['receipt_file']);
                    }
                    $receiptFile = 'uploads/receipts/' . $filename;
                } else {
                    $errors[] = 'فشل في حفظ الملف المرفق.';
                }
            }
        }

        if (empty($errors)) {
            $db->prepare("UPDATE payments SET subscription_id=?, amount=?, payment_date=?, payment_method=?, reference_number=?, notes=?, receipt_file=? WHERE id=?")
               ->execute([$formData['subscription_id'], $formData['amount'], $formData['payment_date'], $formData['payment_method'], $formData['reference_number'], $formData['notes'], $receiptFile, $id]);
            setFlash('success', 'تم تعديل الدفعة بنجاح.');
            header("Location: ../clients/view.php?id={$payment['client_id']}");
            exit;
        }
    }
}

$pageTitle  = 'تعديل دفعة';
$activePage = 'payments';
$depth      = 1;
require_once INCLUDES_PATH . '/header.php';
?>
<div class="page-header">
  <div class="page-header-text">
    <h1 class="page-title"><i class="fas fa-edit" style="color:var(--success);margin-left:8px;"></i>تعديل دفعة مالية</h1>
    <p class="page-subtitle">تعديل الدفعة الخاصة بالعميل: <?= e($payment['client_name']) ?></p>
  </div>
  <div class="page-actions">
    <a href="../clients/view.php?id=<?= $payment['client_id'] ?>" class="btn btn-outline"><i class="fas fa-arrow-right"></i> رجوع</a>
  </div>
</div>

<?php if ($errors): ?>
<div class="alert alert-error">
  <i class="fas fa-times-circle"></i>
  <div><?php foreach ($errors as $err): ?><div><?= e($err) ?></div><?php endforeach; ?></div>
</div>
<?php endif; ?>

<div class="card" style="max-width: 600px; margin: 0 auto;">
  <div class="card-header">
    <span class="card-title"><i class="fas fa-money-bill-wave" style="color:var(--success);"></i> تفاصيل الدفعة</span>
  </div>
  <div class="card-body">
    <form method="POST" action="edit.php?id=<?= $id ?>" enctype="multipart/form-data" data-validate>
      <?= csrfField() ?>
      
      <div class="form-group">
        <label class="form-label" for="amount">المبلغ <span class="required">*</span></label>
        <input type="number" id="amount" name="amount" class="form-control" step="0.01" min="0.01" value="<?= $formData['amount'] ?>" required>
      </div>

      <div class="form-row" style="grid-template-columns: 1fr 1fr;">
        <div class="form-group">
          <label class="form-label" for="payment_date">تاريخ الدفع <span class="required">*</span></label>
          <input type="date" id="payment_date" name="payment_date" class="form-control" value="<?= $formData['payment_date'] ?>" required>
        </div>
        <div class="form-group">
          <label class="form-label" for="payment_method">طريقة الدفع</label>
          <?php 
          $payMethods = explode(',', getSetting('payment_methods', 'كاش,تحويل بنكي,فودافون كاش,شيك,أخرى')); 
          ?>
          <select id="payment_method" name="payment_method" class="form-control">
            <?php foreach ($payMethods as $pm): $pm = trim($pm); ?>
            <option value="<?= e($pm) ?>" <?= $formData['payment_method'] === $pm ? 'selected' : '' ?>><?= e($pm) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="form-row" style="grid-template-columns: 1fr 1fr;">
        <div class="form-group">
          <label class="form-label" for="reference_number">رقم المرجع / الإيصال</label>
          <input type="text" id="reference_number" name="reference_number" class="form-control" value="<?= e($formData['reference_number']) ?>" placeholder="مثال: رقم التحويل أو الشيك">
        </div>
        <div class="form-group">
          <label class="form-label" for="subscription_id">الخدمة المرتبطة بها</label>
          <select id="subscription_id" name="subscription_id" class="form-control">
            <option value="">— دفعة عامة للحساب —</option>
            <?php foreach ($subscriptions as $s): ?>
              <option value="<?= $s['id'] ?>" <?= $formData['subscription_id'] == $s['id'] ? 'selected' : '' ?>>
                <?= e($s['service_name']) ?> <?= $s['plan_name'] ? '(' . e($s['plan_name']) . ')' : '' ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label" for="notes">ملاحظات</label>
        <textarea id="notes" name="notes" class="form-control" rows="3" placeholder="ملاحظات إضافية عن الدفعة..."><?= e($formData['notes']) ?></textarea>
      </div>

      <div class="form-group">
        <label class="form-label" for="receipt_file">صورة الإيصال أو ملف PDF المرفق</label>
        <?php if (!empty($formData['receipt_file'])): ?>
          <div style="margin-bottom: 10px;">
            <a href="../<?= e($formData['receipt_file']) ?>" target="_blank" class="btn btn-sm btn-outline-info" style="display:inline-flex;align-items:center;gap:6px;">
              <i class="fas fa-file-invoice"></i> عرض المرفق الحالي
            </a>
          </div>
        <?php endif; ?>
        <input type="file" id="receipt_file" name="receipt_file" class="form-control" accept="image/*,.pdf">
        <span class="form-hint">يمكنك رفع صورة الإيصال أو ملف PDF. اتركه فارغاً للاحتفاظ بالمرفق الحالي.</span>
      </div>

      <div style="display:flex;gap:12px;justify-content:flex-end;margin-top:10px;">
        <a href="../clients/view.php?id=<?= $payment['client_id'] ?>" class="btn btn-outline">إلغاء</a>
        <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> حفظ التعديلات</button>
      </div>
    </form>
  </div>
</div>
<?php require_once INCLUDES_PATH . '/footer.php'; ?>
