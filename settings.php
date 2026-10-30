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
        $fields = ['company_name','company_phone','company_email','company_address','invoice_prefix','currency','renewal_warning_days','default_home_page','whatsapp_api_url','whatsapp_api_token','whatsapp_sender','payment_methods','renewal_auto_review','renewal_admin_mobile','renewal_alert_hour','renewal_send_hour'];
        foreach ($fields as $f) {
            $val = clean($_POST[$f] ?? '');
            $chk = $db->prepare("SELECT COUNT(*) FROM settings WHERE `key`=?");
            $chk->execute([$f]);
            if ((int)$chk->fetchColumn() === 0) {
                $db->prepare("INSERT INTO settings (`key`, value) VALUES (?, ?)")->execute([$f, $val]);
            } else {
                $db->prepare("UPDATE settings SET value=? WHERE `key`=?")->execute([$val,$f]);
            }
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
    <p class="page-subtitle">ضبط معلومات الشركة وإعدادات الواتساب والفواتير والصفحة الرئيسية</p>
  </div>
</div>

<form method="POST" action="settings.php" data-validate>
  <?= csrfField() ?>
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;align-items:start;">

    <div>
      <!-- System Home Page Settings -->
      <div class="card" style="margin-bottom:20px; border-right: 4px solid var(--primary);">
        <div class="card-header"><span class="card-title"><i class="fas fa-house-chimney"></i> الصفحة الرئيسية للنظام</span></div>
        <div class="card-body">
          <div class="form-group">
            <label class="form-label" for="default_home_page">الصفحة الافتراضية بعد تسجيل الدخول وعند فتح الموقع <span class="required">*</span></label>
            <?php $currentHome = $settings['default_home_page'] ?? 'dashboard.php'; ?>
            <select id="default_home_page" name="default_home_page" class="form-control" required style="font-weight: 700;">
              <option value="dashboard.php" <?= $currentHome === 'dashboard.php' ? 'selected' : '' ?>>📊 لوحة التحكم والإحصائيات العامة (مستحسن)</option>
              <option value="clients/index.php" <?= $currentHome === 'clients/index.php' ? 'selected' : '' ?>>👥 قائمة وإدارة العملاء</option>
              <option value="reports/renewals.php" <?= $currentHome === 'reports/renewals.php' ? 'selected' : '' ?>>🔔 تقرير التجديدات القريبة</option>
              <option value="reports/services-stats.php" <?= $currentHome === 'reports/services-stats.php' ? 'selected' : '' ?>>🌐 إحصائيات وتوزيع الخدمات</option>
              <option value="reports/financial-hub.php" <?= $currentHome === 'reports/financial-hub.php' ? 'selected' : '' ?>>💰 الموقف المالي العام والتحصيلات</option>
            </select>
            <span class="form-hint">حدد الصفحة التي تفتح تلقائياً عند تسجيل الدخول أو عند التوجه إلى رابط النظام الرئيسي.</span>
          </div>
        </div>
      </div>

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
            <label class="form-label" for="whatsapp_api_url">رابط الخادم الأساسي (Base URL)</label>
            <input type="url" id="whatsapp_api_url" name="whatsapp_api_url" class="form-control" value="<?= e($settings['whatsapp_api_url'] ?? '') ?>" placeholder="https://whatsapp.enjaz.cloud" dir="ltr">
          </div>
          <div class="form-group">
            <label class="form-label" for="whatsapp_api_token">مفتاح الـ API (x-api-key)</label>
            <input type="text" id="whatsapp_api_token" name="whatsapp_api_token" class="form-control" value="<?= e($settings['whatsapp_api_token'] ?? '') ?>" placeholder="ak_de6fe9510..." dir="ltr">
          </div>
          <div class="form-group">
            <label class="form-label" for="whatsapp_sender">معرف الجلسة (Session ID)</label>
            <input type="text" id="whatsapp_sender" name="whatsapp_sender" class="form-control" value="<?= e($settings['whatsapp_sender'] ?? '') ?>" placeholder="sess_xxxxxx" dir="ltr">
          </div>
          <div style="background:rgba(37,211,102,.08);border-right:3px solid #25D366;border-radius:8px;padding:12px 14px;font-size:12.5px;color:#166534;">
            <i class="fas fa-info-circle" style="margin-left:6px;"></i>
            ضع بيانات خادم بوابة الواتساب والجلسة ومفتاح المصادقة لتفعيل إرسال الإشعارات والرسائل التلقائية للعملاء.
          </div>
        </div>
      </div>

      <!-- Renewal Notifications & Review Automation -->
      <div class="card" style="margin-bottom:20px; border-right: 4px solid #10b981;">
        <div class="card-header" style="display:flex; justify-content:space-between; align-items:center;">
          <span class="card-title"><i class="fas fa-robot" style="color:#10b981;"></i> إعدادات التنبيه التلقائي للتجديدات (واتساب)</span>
          <a href="reports/renewal_reviews.php" class="btn btn-sm btn-outline-primary" style="font-size:12px; font-weight:700;">
            <i class="fas fa-external-link-alt"></i> شاشة المراجعة
          </a>
        </div>
        <div class="card-body">
          <?php $autoReview = $settings['renewal_auto_review'] ?? '1'; ?>
          <div class="form-group" style="margin-bottom: 18px;">
            <label class="form-label" style="font-weight: 800; font-size: 14px; margin-bottom: 8px;">
              <i class="fas fa-sliders-h" style="color:var(--primary); margin-left: 6px;"></i>
              نمط الإرسال والمراجعة (التحكم الآلي) <span class="required">*</span>
            </label>
            <div style="display: flex; flex-direction: column; gap: 10px;">
              <label style="display: flex; align-items: flex-start; gap: 10px; padding: 12px 14px; border: 2px solid <?= $autoReview === '1' ? '#10b981' : 'var(--border-color)' ?>; border-radius: 8px; cursor: pointer; background: <?= $autoReview === '1' ? 'rgba(16,185,129,0.04)' : '#fff' ?>;">
                <input type="radio" name="renewal_auto_review" value="1" <?= $autoReview === '1' ? 'checked' : '' ?> style="margin-top: 4px;">
                <div>
                  <div style="font-weight: 800; color: var(--text-primary); font-size: 13.5px;">
                    🛡️ تفعيل المراجعة المسبقة قبل الإرسال (مستحسن حالياً)
                  </div>
                  <div style="font-size: 12px; color: var(--text-muted); margin-top: 2px;">
                    يصلك إشعار ملخص على الواتساب بأسماء العملاء المقرر إرسال رسائل لهم غداً، ولا تخرج أي رسالة للعميل إلا بعد دخولك واعتمادها.
                  </div>
                </div>
              </label>

              <label style="display: flex; align-items: flex-start; gap: 10px; padding: 12px 14px; border: 2px solid <?= $autoReview === '0' ? '#2563eb' : 'var(--border-color)' ?>; border-radius: 8px; cursor: pointer; background: <?= $autoReview === '0' ? 'rgba(37,99,235,0.04)' : '#fff' ?>;">
                <input type="radio" name="renewal_auto_review" value="0" <?= $autoReview === '0' ? 'checked' : '' ?> style="margin-top: 4px;">
                <div>
                  <div style="font-weight: 800; color: var(--text-primary); font-size: 13.5px;">
                    ⚡ إرسال تلقائي مباشر للعملاء بدون مراجعة
                  </div>
                  <div style="font-size: 12px; color: var(--text-muted); margin-top: 2px;">
                    ترسل الرسائل للعملاء مباشرة في موعدها (60، 45، 30، 15، 7) وفق قاعدة تخطي التكرار، ويصلك فقط تنبيه بمرحلة الـ 7 أيام ومن يتبقى على اشتراكه يوم واحد لإيقافه.
                  </div>
                </div>
              </label>
            </div>
          </div>

          <div class="form-group">
            <label class="form-label" for="renewal_admin_mobile">
              <i class="fab fa-whatsapp" style="color: #25D366; margin-left: 4px;"></i>
              رقم واتساب الإدمن لاستقبال التنبيهات
            </label>
            <input type="text" id="renewal_admin_mobile" name="renewal_admin_mobile" class="form-control" value="<?= e($settings['renewal_admin_mobile'] ?? '201028855779') ?>" placeholder="201028855779" dir="ltr" style="font-weight: 700; font-family: monospace;">
            <span class="form-hint">الرقم الذي يتلقى ملخص الغد، وتنبيهات عملاء الـ 7 أيام، وتنبيه من ينتهي اشتراكه غداً.</span>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label class="form-label" for="renewal_alert_hour">
                <i class="fas fa-clock" style="color:var(--primary); margin-left:4px;"></i>
                موعد تنبيه الإدمن اليومي (ملخص الغد)
              </label>
              <input type="time" id="renewal_alert_hour" name="renewal_alert_hour" class="form-control" value="<?= e($settings['renewal_alert_hour'] ?? '20:00') ?>" style="font-weight:700;">
              <span class="form-hint">الوقت الذي تتلقى فيه رسالة الواتساب اليومية لمراجعة اشتراكات الغد (مثال: 20:00 = 8 مساءً).</span>
              <div style="display:flex; gap:6px; margin-top:6px; flex-wrap:wrap;">
                <button type="button" class="btn btn-xs btn-outline-secondary" onclick="document.getElementById('renewal_alert_hour').value='18:00'">6:00 م</button>
                <button type="button" class="btn btn-xs btn-outline-secondary" onclick="document.getElementById('renewal_alert_hour').value='20:00'">8:00 م (افتراضي)</button>
                <button type="button" class="btn btn-xs btn-outline-secondary" onclick="document.getElementById('renewal_alert_hour').value='21:00'">9:00 م</button>
                <button type="button" class="btn btn-xs btn-outline-secondary" onclick="document.getElementById('renewal_alert_hour').value='22:00'">10:00 م</button>
              </div>
            </div>
            <div class="form-group">
              <label class="form-label" for="renewal_send_hour">
                <i class="fas fa-paper-plane" style="color:#10b981; margin-left:4px;"></i>
                موعد إرسال رسائل العملاء المعتمدة
              </label>
              <input type="time" id="renewal_send_hour" name="renewal_send_hour" class="form-control" value="<?= e($settings['renewal_send_hour'] ?? '11:00') ?>" style="font-weight:700;">
              <span class="form-hint">الوقت الصباحي لإرسال الإشعارات المعتمدة للعملاء في اليوم التالي.</span>
              <div style="display:flex; gap:6px; margin-top:6px; flex-wrap:wrap;">
                <button type="button" class="btn btn-xs btn-outline-secondary" onclick="document.getElementById('renewal_send_hour').value='09:00'">9:00 ص</button>
                <button type="button" class="btn btn-xs btn-outline-secondary" onclick="document.getElementById('renewal_send_hour').value='10:00'">10:00 ص</button>
                <button type="button" class="btn btn-xs btn-outline-secondary" onclick="document.getElementById('renewal_send_hour').value='11:00'">11:00 ص (افتراضي)</button>
                <button type="button" class="btn btn-xs btn-outline-secondary" onclick="document.getElementById('renewal_send_hour').value='12:00'">12:00 ظهراً</button>
              </div>
            </div>
          </div>

          <div style="background: rgba(245,158,11,.08); border-right: 3px solid #f59e0b; border-radius: 8px; padding: 10px 14px; font-size: 12px; color: #92400e; margin-top: 10px;">
            <i class="fas fa-lightbulb" style="margin-left: 6px;"></i>
            <strong>قاعدة الاستثناء الذكية:</strong> عند اعتماد إشعار الـ 60 يوم لأي عميل، يتخطى النظام تلقائياً إشعارات (45، 30، 15) ولن يظهر العميل مجدداً إلا عند وصوله إلى مرحلة الـ 7 أيام في حال لم يجدد.
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
