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

// تجربة إرسال النسخة الاحتياطية للبريد مباشرة عبر الرابط GET
if (isset($_GET['test_backup'])) {
    require_once INCLUDES_PATH . '/client_backup.php';
    $backupRes = sendClientsBackupEmail(true);
    if (!empty($backupRes['success'])) {
        setFlash('success', $backupRes['message']);
    } else {
        setFlash('error', $backupRes['message']);
    }
    header('Location: settings.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) { $errors[] = 'خطأ في الأمان.'; }
    else {
        $fields = ['company_name','company_phone','company_email','company_address','invoice_prefix','currency','renewal_warning_days','default_home_page','whatsapp_api_url','whatsapp_api_token','whatsapp_sender','payment_methods','renewal_auto_review','renewal_admin_mobile','renewal_alert_hour','renewal_send_hour','backup_email','backup_clients_enabled','backup_clients_hour'];
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

        // اختبار إرسال النسخة الاحتياطية للبريد فوراً
        if (isset($_POST['send_test_backup_now'])) {
            require_once INCLUDES_PATH . '/client_backup.php';
            $backupRes = sendClientsBackupEmail(true);
            if (!empty($backupRes['success'])) {
                setFlash('success', $backupRes['message']);
            } else {
                setFlash('error', $backupRes['message']);
            }
            header('Location: settings.php');
            exit;
        }

        // حفظ حسابات وطرق الدفع المنظمة حسب عملة الاشتراك
        if (isset($_POST['payment_accounts']) && is_array($_POST['payment_accounts'])) {
            $cleanAccounts = [];
            foreach ($_POST['payment_accounts'] as $curr => $data) {
                $curr = strtoupper(clean($curr));
                if (empty($curr)) continue;
                $label = clean($data['label'] ?? $curr);
                $header = clean($data['header'] ?? "بيانات التحويل ({$curr}):");
                $accList = [];
                if (!empty($data['accounts']) && is_array($data['accounts'])) {
                    foreach ($data['accounts'] as $acc) {
                        $name = clean($acc['name'] ?? '');
                        $details = clean($acc['details'] ?? '');
                        $isActive = !empty($acc['is_active']) ? 1 : 0;
                        if (!empty($name) || !empty($details)) {
                            $accList[] = [
                                'name'      => $name,
                                'details'   => $details,
                                'is_active' => $isActive
                            ];
                        }
                    }
                }
                $cleanAccounts[$curr] = [
                    'label'    => $label,
                    'header'   => $header,
                    'accounts' => $accList
                ];
            }
            $jsonVal = json_encode($cleanAccounts, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            $chkAcc = $db->prepare("SELECT COUNT(*) FROM settings WHERE `key`='renewal_payment_accounts'");
            $chkAcc->execute();
            if ((int)$chkAcc->fetchColumn() === 0) {
                $db->prepare("INSERT INTO settings (`key`, value) VALUES ('renewal_payment_accounts', ?)")->execute([$jsonVal]);
            } else {
                $db->prepare("UPDATE settings SET value=? WHERE `key`='renewal_payment_accounts'")->execute([$jsonVal]);
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

      <!-- النسخة الاحتياطية اليومية لشيت العملاء على البريد -->
      <div class="card">
        <div class="card-header" style="display:flex; justify-content:space-between; align-items:center;">
          <span class="card-title">
            <i class="fas fa-file-excel" style="color:#217346; margin-left:6px;"></i>
            النسخة الاحتياطية اليومية لبيانات العملاء (إكسيل على الإيميل)
          </span>
          <span class="badge" style="background:rgba(33,115,70,0.1); color:#217346; font-weight:700;">
            <i class="fas fa-shield-alt"></i> حماية ومراجعة دورية
          </span>
        </div>
        <div class="card-body">
          <div style="font-size: 13px; color: var(--text-muted); line-height: 1.6; margin-bottom: 16px;">
            يتم توليد شيت إكسيل (CSV مفصل وشامل) يضم كافة بيانات العملاء، أرقام التواصل، الاشتراكات والخدمات، المديونيات، وتواريخ التجديد بترميز UTF-8 مع BOM وإرساله تلقائياً كملف مرفق إلى بريدك الإلكتروني في التوقيت المحدد.
          </div>

          <div class="form-row">
            <div class="form-group" style="flex: 2;">
              <label class="form-label" for="backup_email">
                <i class="fas fa-envelope" style="color:var(--primary); margin-left:4px;"></i>
                بريد استلام النسخة الاحتياطية اليومية
              </label>
              <input type="email" id="backup_email" name="backup_email" class="form-control" value="<?= e($settings['backup_email'] ?? 'rashed1711@gmail.com') ?>" placeholder="rashed1711@gmail.com" dir="ltr" style="font-weight:700;">
              <span class="form-hint">البريد الذي سيصل إليه الشيت اليومي بصيغة Excel كمرفق.</span>
            </div>

            <div class="form-group" style="flex: 1;">
              <label class="form-label" for="backup_clients_hour">
                <i class="fas fa-clock" style="color:#6366f1; margin-left:4px;"></i>
                موعد الإرسال اليومي
              </label>
              <input type="time" id="backup_clients_hour" name="backup_clients_hour" class="form-control" value="<?= e($settings['backup_clients_hour'] ?? '00:00') ?>" style="font-weight:700;">
              <span class="form-hint">افتراضياً <strong>00:00</strong> (الساعة 12:00 منتصف الليل).</span>
            </div>
          </div>

          <div class="form-group" style="margin-top: 6px;">
            <label class="form-label" style="display:flex; align-items:center; gap: 8px; cursor: pointer;">
              <input type="hidden" name="backup_clients_enabled" value="0">
              <input type="checkbox" name="backup_clients_enabled" value="1" <?= ($settings['backup_clients_enabled'] ?? '1') === '1' ? 'checked' : '' ?> style="width:18px; height:18px;">
              <span style="font-weight: 700; color: var(--text-primary);">تفعيل الإرسال اليومي التلقائي لشيت العملاء عبر البريد</span>
            </label>
          </div>

          <div style="display:flex; align-items:center; justify-content:space-between; margin-top: 14px; padding-top: 12px; border-top: 1px dashed var(--border-color); flex-wrap: wrap; gap: 10px;">
            <div style="font-size: 12px; color: var(--text-muted);">
              <?php if (!empty($settings['backup_clients_last_date'])): ?>
                <i class="fas fa-check-circle" style="color: #10b981;"></i> آخر نسخة تم إرسالها بنجاح بتاريخ: <strong><?= e($settings['backup_clients_last_date']) ?></strong>
              <?php else: ?>
                <i class="fas fa-info-circle"></i> لم يتم إرسال نسخة احتياطية بعد.
              <?php endif; ?>
            </div>

            <button type="submit" name="send_test_backup_now" value="1" class="btn btn-outline-success btn-sm" style="font-weight: 700;">
              <i class="fas fa-paper-plane"></i> إرسال نسخة تجريبية للبريد الآن للتأكد
            </button>
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

  <!-- Renewal Payment Accounts Manager (Full Width Card) -->
  <?php $paymentAccounts = getRenewalPaymentAccounts(); ?>
  <div class="card" style="margin-top:20px; border-top: 4px solid #3b82f6;">
    <div class="card-header" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
      <span class="card-title">
        <i class="fas fa-credit-card" style="color:#3b82f6;"></i>
        حسابات وطرق التحويل في رسائل التجديد (مخصصة حسب عملة اشتراك العميل)
      </span>
      <span style="font-size:12px; color:var(--text-muted);">
        يظهر للعميل طرق الدفع الخاصة بالعملة التي اشترك بها فقط (العميل المشترك بالجنيه يرى حسابات مصر فقط، والمشترك بالريال يرى حسابات السعودية فقط)
      </span>
    </div>
    <div class="card-body">
      <!-- تبويبات العملات -->
      <div style="display:flex; gap:8px; border-bottom:2px solid var(--border-color); margin-bottom:18px; flex-wrap:wrap;" id="currencyTabsNav">
        <?php $activeCurrTab = array_key_first($paymentAccounts); ?>
        <?php foreach ($paymentAccounts as $cCode => $cData): ?>
          <button type="button" class="btn btn-sm <?= $cCode === $activeCurrTab ? 'btn-primary' : 'btn-outline-secondary' ?> currency-tab-btn" onclick="switchCurrencyTab('<?= $cCode ?>', this)" data-curr="<?= $cCode ?>" style="font-weight:700;">
            <?= e($cData['label'] ?? $cCode) ?>
          </button>
        <?php endforeach; ?>
        <button type="button" class="btn btn-sm btn-outline-success" onclick="addNewCurrencyPrompt()" style="font-weight:700;">
          <i class="fas fa-plus"></i> إضافة عملة / دولة أخرى
        </button>
      </div>

      <!-- محتويات كل عملة -->
      <div id="currencyPanelsContainer">
        <?php foreach ($paymentAccounts as $cCode => $cData): ?>
          <div class="currency-panel" id="panel-<?= $cCode ?>" style="display: <?= $cCode === $activeCurrTab ? 'block' : 'none' ?>;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; flex-wrap:wrap; gap:8px;">
              <div style="font-weight:800; font-size:14px; color:var(--text-primary);">
                <?= e($cData['label'] ?? $cCode) ?>
              </div>
              <?php if (!in_array($cCode, ['EGP', 'SAR'])): ?>
                <button type="button" class="btn btn-xs btn-outline-danger" onclick="removeCurrencyTab('<?= $cCode ?>')">
                  <i class="fas fa-trash"></i> حذف هذه العملة
                </button>
              <?php endif; ?>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label class="form-label" style="font-size:12.5px;">تسمية التبويب / الدولة</label>
                <input type="text" name="payment_accounts[<?= $cCode ?>][label]" class="form-control" value="<?= e($cData['label'] ?? $cCode) ?>" required>
              </div>
              <div class="form-group">
                <label class="form-label" style="font-size:12.5px;">عنوان فقرة التحويل في رسالة الواتساب</label>
                <input type="text" name="payment_accounts[<?= $cCode ?>][header]" class="form-control" value="<?= e($cData['header'] ?? "بيانات التحويل ({$cCode}):") ?>" required>
              </div>
            </div>

            <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:14px; margin-bottom:14px;">
              <div style="font-weight:800; font-size:13px; color:var(--text-primary); margin-bottom:12px; display:flex; justify-content:space-between; align-items:center;">
                <span>الحسابات البنكية والمحافظ المعتمدة لعملة (<?= $cCode ?>):</span>
                <button type="button" class="btn btn-xs btn-primary" onclick="addAccountRow('<?= $cCode ?>')">
                  <i class="fas fa-plus"></i> إضافة حساب جديد
                </button>
              </div>

              <div id="accounts-container-<?= $cCode ?>" style="display:flex; flex-direction:column; gap:8px;">
                <?php $accList = $cData['accounts'] ?? []; ?>
                <?php foreach ($accList as $idx => $acc): 
                  $isActive = isset($acc['is_active']) ? !empty($acc['is_active']) : true;
                ?>
                  <div class="account-row" style="display:grid; grid-template-columns:1fr 1.5fr auto auto; gap:10px; align-items:center; opacity:<?= $isActive ? '1' : '0.55' ?>; transition:opacity 0.2s;">
                    <input type="text" name="payment_accounts[<?= $cCode ?>][accounts][<?= $idx ?>][name]" class="form-control" value="<?= e($acc['name'] ?? '') ?>" placeholder="اسم البنك / المحفظة (مثال: الراجحي – رقم الحساب)">
                    <input type="text" name="payment_accounts[<?= $cCode ?>][accounts][<?= $idx ?>][details]" class="form-control" value="<?= e($acc['details'] ?? '') ?>" placeholder="رقم الحساب / الآيبان / الهاتف" dir="ltr" style="font-family:monospace;">
                    
                    <label style="display:flex; align-items:center; gap:6px; cursor:pointer; font-size:12px; font-weight:700; margin:0; padding:6px 12px; border-radius:6px; background:<?= $isActive ? '#ecfdf5' : '#f1f5f9' ?>; color:<?= $isActive ? '#059669' : '#64748b' ?>; border:1px solid <?= $isActive ? '#a7f3d0' : '#cbd5e1' ?>; user-select:none;" title="انقر لتفعيل أو إيقاف الحساب من رسائل العملاء">
                      <input type="checkbox" name="payment_accounts[<?= $cCode ?>][accounts][<?= $idx ?>][is_active]" value="1" <?= $isActive ? 'checked' : '' ?> onchange="toggleAccountStatus(this)" style="display:none;">
                      <span class="status-icon"><?= $isActive ? '✅' : '⏸️' ?></span>
                      <span class="status-text"><?= $isActive ? 'مفعّل' : 'موقّف' ?></span>
                    </label>

                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('.account-row').remove()" title="حذف الحساب">
                      <i class="fas fa-trash-alt"></i>
                    </button>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <script>
  function switchCurrencyTab(currCode, btn) {
    document.querySelectorAll('.currency-tab-btn').forEach(b => {
      b.classList.remove('btn-primary');
      b.classList.add('btn-outline-secondary');
    });
    btn.classList.remove('btn-outline-secondary');
    btn.classList.add('btn-primary');

    document.querySelectorAll('.currency-panel').forEach(p => p.style.display = 'none');
    const targetPanel = document.getElementById('panel-' + currCode);
    if (targetPanel) targetPanel.style.display = 'block';
  }

  function toggleAccountStatus(cb) {
    const label = cb.closest('label');
    const row = cb.closest('.account-row');
    const icon = label.querySelector('.status-icon');
    const text = label.querySelector('.status-text');
    if (cb.checked) {
      label.style.background = '#ecfdf5';
      label.style.color = '#059669';
      label.style.borderColor = '#a7f3d0';
      icon.textContent = '✅';
      text.textContent = 'مفعّل';
      row.style.opacity = '1';
    } else {
      label.style.background = '#f1f5f9';
      label.style.color = '#64748b';
      label.style.borderColor = '#cbd5e1';
      icon.textContent = '⏸️';
      text.textContent = 'موقّف';
      row.style.opacity = '0.55';
    }
  }

  function addAccountRow(currCode) {
    const container = document.getElementById('accounts-container-' + currCode);
    if (!container) return;
    const idx = Date.now();
    const row = document.createElement('div');
    row.className = 'account-row';
    row.style.cssText = 'display:grid; grid-template-columns:1fr 1.5fr auto auto; gap:10px; align-items:center; transition:opacity 0.2s;';
    row.innerHTML = `
      <input type="text" name="payment_accounts[${currCode}][accounts][${idx}][name]" class="form-control" placeholder="اسم البنك أو المحفظة">
      <input type="text" name="payment_accounts[${currCode}][accounts][${idx}][details]" class="form-control" placeholder="رقم الحساب / الآيبان / الهاتف" dir="ltr" style="font-family:monospace;">
      <label style="display:flex; align-items:center; gap:6px; cursor:pointer; font-size:12px; font-weight:700; margin:0; padding:6px 12px; border-radius:6px; background:#ecfdf5; color:#059669; border:1px solid #a7f3d0; user-select:none;" title="انقر لتفعيل أو إيقاف الحساب">
        <input type="checkbox" name="payment_accounts[${currCode}][accounts][${idx}][is_active]" value="1" checked onchange="toggleAccountStatus(this)" style="display:none;">
        <span class="status-icon">✅</span>
        <span class="status-text">مفعّل</span>
      </label>
      <button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('.account-row').remove()" title="حذف">
        <i class="fas fa-trash-alt"></i>
      </button>
    `;
    container.appendChild(row);
  }

  function addNewCurrencyPrompt() {
    const code = prompt("أدخل رمز العملة بالإنجليزية (مثال: USD أو AED أو KWD):");
    if (!code) return;
    const cleanCode = code.trim().toUpperCase().replace(/[^A-Z]/g, '');
    if (!cleanCode) {
      alert("رمز العملة غير صالح.");
      return;
    }

    if (document.getElementById('panel-' + cleanCode)) {
      alert("هذه العملة موجودة بالفعل.");
      return;
    }

    const label = prompt("أدخل التسمية بالعربية (مثال: 🇦🇪 الإمارات / درهم):", cleanCode);
    const header = prompt("عنوان فقرة التحويل في الرسالة:", `بيانات التحويل (${cleanCode}):`);

    // إضافة زر التبويب
    const nav = document.getElementById('currencyTabsNav');
    const addBtn = nav.querySelector('.btn-outline-success');
    const newTabBtn = document.createElement('button');
    newTabBtn.type = 'button';
    newTabBtn.className = 'btn btn-sm btn-outline-secondary currency-tab-btn';
    newTabBtn.style.fontWeight = '700';
    newTabBtn.setAttribute('data-curr', cleanCode);
    newTabBtn.textContent = label || cleanCode;
    newTabBtn.onclick = function() { switchCurrencyTab(cleanCode, this); };
    nav.insertBefore(newTabBtn, addBtn);

    // إضافة اللوحة
    const container = document.getElementById('currencyPanelsContainer');
    const panel = document.createElement('div');
    panel.className = 'currency-panel';
    panel.id = 'panel-' + cleanCode;
    panel.style.display = 'none';
    panel.innerHTML = `
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; flex-wrap:wrap; gap:8px;">
        <div style="font-weight:800; font-size:14px; color:var(--text-primary);">${escapeHtml(label || cleanCode)}</div>
        <button type="button" class="btn btn-xs btn-outline-danger" onclick="removeCurrencyTab('${cleanCode}')">
          <i class="fas fa-trash"></i> حذف هذه العملة
        </button>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label" style="font-size:12.5px;">تسمية التبويب / الدولة</label>
          <input type="text" name="payment_accounts[${cleanCode}][label]" class="form-control" value="${escapeHtml(label || cleanCode)}" required>
        </div>
        <div class="form-group">
          <label class="form-label" style="font-size:12.5px;">عنوان فقرة التحويل في رسالة الواتساب</label>
          <input type="text" name="payment_accounts[${cleanCode}][header]" class="form-control" value="${escapeHtml(header || 'بيانات التحويل:')}" required>
        </div>
      </div>
      <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:14px; margin-bottom:14px;">
        <div style="font-weight:800; font-size:13px; color:var(--text-primary); margin-bottom:12px; display:flex; justify-content:space-between; align-items:center;">
          <span>الحسابات البنكية والمحافظ المعتمدة لعملة (${cleanCode}):</span>
          <button type="button" class="btn btn-xs btn-primary" onclick="addAccountRow('${cleanCode}')">
            <i class="fas fa-plus"></i> إضافة حساب جديد
          </button>
        </div>
        <div id="accounts-container-${cleanCode}" style="display:flex; flex-direction:column; gap:8px;"></div>
      </div>
    `;
    container.appendChild(panel);

    // الانتقال للتبويب الجديد وإضافة صف افتراضي
    switchCurrencyTab(cleanCode, newTabBtn);
    addAccountRow(cleanCode);
  }

  function removeCurrencyTab(currCode) {
    if (!confirm('هل أنت متأكد من حذف هذه العملة وجميع حساباتها؟')) return;
    const tabBtn = document.querySelector(`.currency-tab-btn[data-curr="${currCode}"]`);
    const panel = document.getElementById('panel-' + currCode);
    if (tabBtn) tabBtn.remove();
    if (panel) panel.remove();
    const firstBtn = document.querySelector('.currency-tab-btn');
    if (firstBtn) firstBtn.click();
  }

  function escapeHtml(text) {
    return text.replace(/[&<>"']/g, function(m) {
      return {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'}[m];
    });
  }
  </script>

  <div style="display:flex;justify-content:flex-end;margin-top:20px;">
    <button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-save"></i> حفظ الإعدادات</button>
  </div>
</form>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
