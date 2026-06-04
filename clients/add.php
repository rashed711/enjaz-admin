<?php
/**
 * clients/add.php - إضافة عميل جديد
 */
require_once dirname(__DIR__) . '/config/app.php';
requireLogin();
requirePermission('add_clients');

$errors   = [];
$formData = ['name'=>'','company_name'=>'','mobile'=>'','mobile_2'=>'','activity'=>'','username_note'=>'','email'=>'','address'=>'','notes'=>'','status'=>'1'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) { $errors[] = 'خطأ في الأمان.'; }
    else {
        foreach ($formData as $k => $v) {
            $formData[$k] = clean($_POST[$k] ?? '');
        }
        $formData['status'] = ($_POST['status'] ?? '1') === '1' ? 1 : 0;

        if (empty($formData['name']))   $errors[] = 'اسم العميل مطلوب.';
        if (empty($formData['mobile'])) $errors[] = 'رقم الموبايل مطلوب.';

        if (empty($errors)) {
            $db = getDB();
            $db->prepare("
                INSERT INTO clients (name, company_name, mobile, mobile_2, activity, username_note, email, address, notes, status, created_by)
                VALUES (?,?,?,?,?,?,?,?,?,?,?)
            ")->execute([
                $formData['name'], $formData['company_name'], $formData['mobile'],
                $formData['mobile_2'], $formData['activity'], $formData['username_note'],
                $formData['email'], $formData['address'], $formData['notes'],
                $formData['status'], currentUserId()
            ]);
            $newId = $db->lastInsertId();
            setFlash('success', 'تم إضافة العميل "' . $formData['name'] . '" بنجاح.');
            header("Location: view.php?id=$newId");
            exit;
        }
    }
}

$pageTitle  = 'إضافة عميل';
$activePage = 'clients';
$depth      = 1;
require_once INCLUDES_PATH . '/header.php';
?>

<div class="page-header">
  <div class="page-header-text">
    <h1 class="page-title"><i class="fas fa-user-plus" style="color:var(--primary-light);margin-left:8px;"></i>إضافة عميل جديد</h1>
    <p class="page-subtitle">تسجيل بيانات العميل — يمكن إضافة الخدمات لاحقاً</p>
  </div>
  <div class="page-actions">
    <a href="index.php" class="btn btn-outline"><i class="fas fa-arrow-right"></i> رجوع</a>
  </div>
</div>

<?php if ($errors): ?>
<div class="alert alert-error">
  <i class="fas fa-times-circle"></i>
  <div><?php foreach ($errors as $e): ?><div><?= e($e) ?></div><?php endforeach; ?></div>
</div>
<?php endif; ?>

<form method="POST" action="add.php" data-validate id="clientForm">
  <?= csrfField() ?>

  <div class="card">
    <div class="card-header">
      <span class="card-title"><i class="fas fa-id-card"></i> البيانات الشخصية والتجارية</span>
    </div>
    <div class="card-body">

      <p class="form-section-title"><i class="fas fa-user"></i> بيانات العميل</p>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label" for="name">اسم العميل <span class="required">*</span></label>
          <input type="text" id="name" name="name" class="form-control"
                 value="<?= e($formData['name']) ?>" placeholder="الاسم الكامل للعميل" required>
        </div>
        <div class="form-group">
          <label class="form-label" for="company_name">اسم الشركة</label>
          <input type="text" id="company_name" name="company_name" class="form-control"
                 value="<?= e($formData['company_name']) ?>" placeholder="اسم الشركة أو المؤسسة">
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label" for="mobile">رقم الموبايل <span class="required">*</span></label>
          <input type="tel" id="mobile" name="mobile" class="form-control"
                 value="<?= e($formData['mobile']) ?>" placeholder="مثال: 01012345678" required>
        </div>
        <div class="form-group">
          <label class="form-label" for="mobile_2">رقم موبايل إضافي</label>
          <input type="tel" id="mobile_2" name="mobile_2" class="form-control"
                 value="<?= e($formData['mobile_2']) ?>" placeholder="رقم احتياطي">
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label" for="activity">النشاط التجاري</label>
          <input type="text" id="activity" name="activity" class="form-control"
                 value="<?= e($formData['activity']) ?>" placeholder="مثال: مطعم، صيدلية، شركة تجارية...">
        </div>
        <div class="form-group">
          <label class="form-label" for="username_note">اسم المستخدم (ملاحظة)</label>
          <input type="text" id="username_note" name="username_note" class="form-control"
                 value="<?= e($formData['username_note']) ?>" placeholder="مثال: info@company.com">
          <span class="form-hint">للإشارة فقط (مثل: اسم مستخدم الاستضافة)</span>
        </div>
      </div>

      <p class="form-section-title" style="margin-top:10px;"><i class="fas fa-envelope"></i> بيانات إضافية</p>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label" for="email">البريد الإلكتروني</label>
          <input type="email" id="email" name="email" class="form-control"
                 value="<?= e($formData['email']) ?>" placeholder="example@domain.com" dir="ltr">
        </div>
        <div class="form-group">
          <label class="form-label" for="status">الحالة</label>
          <select id="status" name="status" class="form-control">
            <option value="1" <?= $formData['status'] ? 'selected' : '' ?>>نشط</option>
            <option value="0" <?= !$formData['status'] ? 'selected' : '' ?>>موقوف</option>
          </select>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label" for="address">العنوان</label>
        <input type="text" id="address" name="address" class="form-control"
               value="<?= e($formData['address']) ?>" placeholder="العنوان التفصيلي">
      </div>

      <div class="form-group">
        <label class="form-label" for="notes">ملاحظات</label>
        <textarea id="notes" name="notes" class="form-control" rows="3"
                  placeholder="أي ملاحظات إضافية عن العميل..."><?= e($formData['notes']) ?></textarea>
      </div>

    </div>
    <div class="card-footer">
      <div style="display:flex;gap:12px;justify-content:flex-end;">
        <a href="index.php" class="btn btn-outline">إلغاء</a>
        <button type="submit" class="btn btn-primary">
          <i class="fas fa-save"></i>
          حفظ العميل
        </button>
      </div>
    </div>
  </div>
</form>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
