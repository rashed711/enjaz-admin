<?php
/**
 * settings.php - إعدادات النظام
 */
require_once __DIR__ . '/config/app.php';
requireLogin();
if (!isAdmin()) { header('Location: 403.php'); exit; }

$db       = getDB();
$settings = getSettings();
$errors   = [];
$success  = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) { $errors[] = 'خطأ في الأمان.'; }
    else {
        $fields = ['company_name','company_phone','company_email','company_address','invoice_prefix','currency','renewal_warning_days','whatsapp_api_url','whatsapp_api_token','whatsapp_sender','payment_methods'];
        foreach ($fields as $f) {
            $val = clean($_POST[$f] ?? '');
            $db->prepare("UPDATE settings SET value=? WHERE `key`=?")->execute([$val,$f]);
        }
        // Reset password
        if (!empty($_POST['new_admin_password']) && strlen($_POST['new_admin_password']) >= 6) {
            $hashed = password_hash($_POST['new_admin_password'], PASSWORD_BCRYPT, ['cost'=>12]);
            $db->prepare("UPDATE users SET password=? WHERE id=?")->execute([$hashed, currentUserId()]);
        }
        setFlash('success','تم حفظ الإعدادات بنجاح.');
        header('Location: settings.php');
        exit;
    }
}

$pageTitle  = 'إعدادات النظام';
$activePage = 'settings';
$depth      = 0;
require_once INCLUDES_PATH . '/header.php';
?>
<div class="page-header">
  <div class="page-header-text">
    <h1 class="page-title"><i class="fas fa-cog" style="color:var(--primary-light);margin-left:8px;"></i>إعدادات النظام</h1>
    <p class="page-subtitle">ضبط معلومات الشركة وإعدادات الواتساب والفواتير</p>
  </div>
</div>

<form method="POST" action="settings.php" data-validate>
  <?= csrfField() ?>
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;align-items:start;">

    <div>
      <!-- Company Info -->
      <div class="card" style="margin-bottom:20px;">
        <div class="card-header"><span class="card-title"><i class="fas fa-building"></i> معلومات الشركة</span></div>
        <div class="card-body">
          <div class="form-group">
            <label class="form-label" for="company_name">اسم الشركة <span class="required">*</span></label>
            <input type="text" id="company_name" name="company_name" class="form-control" value="<?= e($settings['company_name'] ?? '') ?>" required>
          </div>
          <div class="form-group">
            <label class="form-label" for="company_phone">رقم الهاتف</label>
            <input type="text" id="company_phone" name="company_phone" class="form-control" value="<?= e($settings['company_phone'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label class="form-label" for="company_email">البريد الإلكتروني</label>
            <input type="email" id="company_email" name="company_email" class="form-control" value="<?= e($settings['company_email'] ?? '') ?>" dir="ltr">
          </div>
          <div class="form-group">
            <label class="form-label" for="company_address">العنوان</label>
            <textarea id="company_address" name="company_address" class="form-control" rows="2"><?= e($settings['company_address'] ?? '') ?></textarea>
          </div>
        </div>
      </div>

      <!-- Invoice Settings -->
      <div class="card" style="margin-bottom:20px;">
        <div class="card-header"><span class="card-title"><i class="fas fa-file-invoice"></i> إعدادات الفواتير</span></div>
        <div class="card-body">
          <div class="form-row">
            <div class="form-group">
              <label class="form-label" for="invoice_prefix">بادئة رقم الفاتورة</label>
              <input type="text" id="invoice_prefix" name="invoice_prefix" class="form-control" value="<?= e($settings['invoice_prefix'] ?? 'INV') ?>" placeholder="INV">
            </div>
            <div class="form-group">
              <label class="form-label" for="currency">العملة</label>
              <input type="text" id="currency" name="currency" class="form-control" value="<?= e($settings['currency'] ?? 'جنيه') ?>">
            </div>
          </div>
          <div class="form-group">
            <label class="form-label" for="renewal_warning_days">تنبيه التجديد (أيام قبل الانتهاء)</label>
            <input type="number" id="renewal_warning_days" name="renewal_warning_days" class="form-control" min="1" max="365" value="<?= e($settings['renewal_warning_days'] ?? '30') ?>">
          </div>
        </div>
      </div>

      <!-- Payment Settings -->
      <div class="card">
        <div class="card-header"><span class="card-title"><i class="fas fa-credit-card"></i> إعدادات طرق الدفع</span></div>
        <div class="card-body">
          <div class="form-group">
            <label class="form-label" for="payment_methods">طرق الدفع المتاحة <span class="required">*</span></label>
            <input type="text" id="payment_methods" name="payment_methods" class="form-control" value="<?= e($settings['payment_methods'] ?? 'كاش,تحويل بنكي,فودافون كاش,شيك,أخرى') ?>" required>
            <span class="form-hint">افصل بين الطرق بفاصلة (,) بدون مسافات إضافية. مثال: كاش,فودافون كاش,تحويل بنكي</span>
          </div>
        </div>
      </div>
    </div>

    <div>
      <!-- WhatsApp Settings -->
      <div class="card" style="margin-bottom:20px;">
        <div class="card-header"><span class="card-title"><i class="fab fa-whatsapp" style="color:#25D366;"></i> إعدادات الواتساب</span></div>
        <div class="card-body">
          <div class="form-group">
            <label class="form-label" for="whatsapp_api_url">رابط الـ API (Endpoint)</label>
            <input type="url" id="whatsapp_api_url" name="whatsapp_api_url" class="form-control" value="<?= e($settings['whatsapp_api_url'] ?? '') ?>" placeholder="https://api.example.com/send" dir="ltr">
          </div>
          <div class="form-group">
            <label class="form-label" for="whatsapp_api_token">Token المصادقة</label>
            <input type="text" id="whatsapp_api_token" name="whatsapp_api_token" class="form-control" value="<?= e($settings['whatsapp_api_token'] ?? '') ?>" placeholder="Bearer token..." dir="ltr">
          </div>
          <div class="form-group">
            <label class="form-label" for="whatsapp_sender">رقم المرسل</label>
            <input type="text" id="whatsapp_sender" name="whatsapp_sender" class="form-control" value="<?= e($settings['whatsapp_sender'] ?? '') ?>" placeholder="20xxxxxxxxxx" dir="ltr">
          </div>
          <div style="background:rgba(37,211,102,.08);border-right:3px solid #25D366;border-radius:8px;padding:12px 14px;font-size:12.5px;color:#166534;">
            <i class="fas fa-info-circle" style="margin-left:6px;"></i>
            ضع بيانات الـ API الخاص بك (WaAPI أو أي سيرفس آخر). النظام سيرسل POST request تلقائياً.
          </div>
        </div>
      </div>

      <!-- Change Password -->
      <div class="card">
        <div class="card-header"><span class="card-title"><i class="fas fa-key"></i> تغيير كلمة المرور</span></div>
        <div class="card-body">
          <div class="form-group">
            <label class="form-label" for="new_admin_password">كلمة المرور الجديدة</label>
            <input type="password" id="new_admin_password" name="new_admin_password" class="form-control" placeholder="اتركه فارغاً عدم التغيير" minlength="6">
            <span class="form-hint">6 أحرف على الأقل</span>
          </div>
        </div>
      </div>
    </div>

  </div>

  <div style="display:flex;justify-content:flex-end;margin-top:20px;">
    <button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-save"></i> حفظ الإعدادات</button>
  </div>
</form>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
