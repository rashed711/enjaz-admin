<?php
/**
 * clients/edit.php - تعديل بيانات عميل
 */
require_once dirname(__DIR__) . '/config/app.php';
requireLogin();
requirePermission('edit_clients');

$db = getDB();
$id = (int)($_GET['id'] ?? 0);
$clientStmt = $db->prepare("SELECT * FROM clients WHERE id = ?");
$clientStmt->execute([$id]);
$client = $clientStmt->fetch();
if (!$client) { setFlash('error','العميل غير موجود.'); header('Location: index.php'); exit; }

$errors   = [];
$formData = $client;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) { $errors[] = 'خطأ في الأمان.'; }
    else {
        $fields = ['name','company_name','mobile','mobile_2','activity','username_note','domain','domain_provider','email','address','notes'];
        foreach ($fields as $f) $formData[$f] = clean($_POST[$f] ?? '');
        $formData['status'] = ($_POST['status'] ?? '1') === '1' ? 1 : 0;

        if (empty($formData['name']))   $errors[] = 'اسم العميل مطلوب.';
        if (empty($formData['mobile'])) $errors[] = 'رقم الموبايل مطلوب.';

        if (empty($errors)) {
            $db->prepare("
                UPDATE clients SET name=?,company_name=?,mobile=?,mobile_2=?,activity=?,
                username_note=?,domain=?,domain_provider=?,email=?,address=?,notes=?,status=? WHERE id=?
            ")->execute([
                $formData['name'],$formData['company_name'],$formData['mobile'],
                $formData['mobile_2'],$formData['activity'],$formData['username_note'],
                $formData['domain'],$formData['domain_provider'],
                $formData['email'],$formData['address'],$formData['notes'],
                $formData['status'], $id
            ]);
            setFlash('success','تم تحديث بيانات العميل "' . $formData['name'] . '" بنجاح.');
            header("Location: view.php?id=$id");
            exit;
        }
    }
}

$pageTitle  = 'تعديل العميل';
$activePage = 'clients';
$depth      = 1;
require_once INCLUDES_PATH . '/header.php';
?>
<div class="page-header">
  <div class="page-header-text">
    <h1 class="page-title"><i class="fas fa-user-edit" style="color:var(--primary-light);margin-left:8px;"></i>تعديل: <?= e($client['name']) ?></h1>
  </div>
  <div class="page-actions">
    <a href="view.php?id=<?= $id ?>" class="btn btn-outline"><i class="fas fa-arrow-right"></i> رجوع</a>
  </div>
</div>

<?php if ($errors): ?>
<div class="alert alert-error"><i class="fas fa-times-circle"></i>
  <div><?php foreach ($errors as $err): ?><div><?= e($err) ?></div><?php endforeach; ?></div>
</div>
<?php endif; ?>

<form method="POST" action="edit.php?id=<?= $id ?>" data-validate>
  <?= csrfField() ?>
  <div class="card">
    <div class="card-header"><span class="card-title"><i class="fas fa-id-card"></i> بيانات العميل</span></div>
    <div class="card-body">
      <div class="form-row">
        <div class="form-group">
          <label class="form-label" for="name">الاسم <span class="required">*</span></label>
          <input type="text" id="name" name="name" class="form-control" value="<?= e($formData['name']) ?>" required>
        </div>
        <div class="form-group">
          <label class="form-label" for="company_name">الشركة</label>
          <input type="text" id="company_name" name="company_name" class="form-control" value="<?= e($formData['company_name']) ?>">
        </div>
        <div class="form-group">
          <label class="form-label" for="activity">النشاط</label>
          <input type="text" id="activity" name="activity" class="form-control" value="<?= e($formData['activity']) ?>">
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label" for="mobile">الموبايل <span class="required">*</span></label>
          <input type="tel" id="mobile" name="mobile" class="form-control" value="<?= e($formData['mobile']) ?>" required>
        </div>
        <div class="form-group">
          <label class="form-label" for="mobile_2">موبايل إضافي</label>
          <input type="tel" id="mobile_2" name="mobile_2" class="form-control" value="<?= e($formData['mobile_2']) ?>">
        </div>
        <div class="form-group">
          <label class="form-label" for="email">البريد</label>
          <input type="email" id="email" name="email" class="form-control" value="<?= e($formData['email']) ?>" dir="ltr">
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label" for="domain">نطاق الموقع (الدومين)</label>
          <input type="text" id="domain" name="domain" class="form-control" value="<?= e($formData['domain']) ?>" placeholder="example.com" dir="ltr">
        </div>
        <div class="form-group">
          <label class="form-label" for="domain_provider">مزود الخدمة (الدومين)</label>
          <?php 
          $providers = ['GoDaddy', 'Hostinger', 'Namecheap', 'Cloudflare', 'Dynadot', 'Hostgator', 'Bluehost', 'إنجاز للحلول الذكية'];
          $isCustomProvider = !empty($formData['domain_provider']) && !in_array($formData['domain_provider'], $providers);
          ?>
          <select id="domain_provider_select" class="form-control" onchange="onProviderChange(this)">
            <option value="">— اختر مزود الخدمة —</option>
            <?php foreach ($providers as $prov): ?>
              <option value="<?= e($prov) ?>" <?= $formData['domain_provider'] === $prov ? 'selected' : '' ?>><?= e($prov) ?></option>
            <?php endforeach; ?>
            <option value="custom" <?= $isCustomProvider ? 'selected' : '' ?>>مزود آخر (كتابة يدوية)...</option>
          </select>
          <input type="text" id="domain_provider" name="domain_provider" class="form-control" 
                 value="<?= e($formData['domain_provider']) ?>" 
                 style="margin-top:8px; display: <?= $isCustomProvider ? 'block' : 'none' ?>;" 
                 placeholder="اكتب اسم مزود الخدمة...">
        </div>
        <div class="form-group">
          <label class="form-label" for="username_note">اسم المستخدم</label>
          <input type="text" id="username_note" name="username_note" class="form-control" value="<?= e($formData['username_note']) ?>">
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label" for="address">العنوان</label>
          <input type="text" id="address" name="address" class="form-control" value="<?= e($formData['address']) ?>">
        </div>
        <div class="form-group">
          <label class="form-label" for="status">الحالة</label>
          <select id="status" name="status" class="form-control">
            <option value="1" <?= $formData['status'] ? 'selected' : '' ?>>نشط</option>
            <option value="0" <?= !$formData['status'] ? 'selected' : '' ?>>موقوف</option>
          </select>
        </div>
      </div>

      <script>
        function onProviderChange(selectEl) {
          const textInput = document.getElementById('domain_provider');
          if (selectEl.value === 'custom') {
            textInput.style.display = 'block';
            textInput.value = '';
            textInput.focus();
          } else {
            textInput.style.display = 'none';
            textInput.value = selectEl.value;
          }
        }

        // تتبع كتابة اسم المستخدم يدوياً لمنع تداخل الاقتراحات التلقائية
        document.getElementById('username_note')?.addEventListener('input', function() {
          this.dataset.manual = 'true';
        });

        document.getElementById('domain')?.addEventListener('input', async function() {
          const domainVal = this.value.trim().toLowerCase();
          if (!domainVal) return;
          
          let name = domainVal.replace(/^(https?:\/\/)?(www\.)?/, '');
          name = name.split('/')[0].split('?')[0];
          name = name.split('.')[0];
          
          const parts = name.split('-');
          if (parts.length > 1) {
            const commonSuffixes = ['eg', 'sa', 'ae', 'qa', 'kw', 'bh', 'om', 'jo', 'lb', 'sy', 'iq', 'ye', 'sd', 'ly', 'tn', 'dz', 'ma', 'com', 'net', 'org', 'web', 'dev', 'app', 'solutions', 'tech', 'smart', 'media', 'design'];
            const lastPart = parts[parts.length - 1];
            if (commonSuffixes.includes(lastPart)) {
              name = parts.slice(0, -1).join('-') + '-' + lastPart;
            } else {
              name = parts[0];
            }
          }
          
          const usernameInput = document.getElementById('username_note');
          if (usernameInput && (!usernameInput.dataset.manual || !usernameInput.value)) {
            let finalUsername = name;
            let counter = 1;
            let isAvailable = false;
            const clientId = <?= (int)$id ?>;

            while (!isAvailable && counter < 100) {
              try {
                const res = await fetch(`../api/check-username.php?username=${finalUsername}&client_id=${clientId}`);
                const data = await res.json();
                if (data.available) {
                  isAvailable = true;
                } else {
                  counter++;
                  finalUsername = name + counter;
                }
              } catch (e) {
                isAvailable = true;
              }
            }
            usernameInput.value = finalUsername;
          }
        });
      </script>

      <div class="form-group">
        <label class="form-label" for="notes">ملاحظات</label>
        <textarea id="notes" name="notes" class="form-control" rows="3"><?= e($formData['notes']) ?></textarea>
      </div>
    </div>
    <div class="card-footer">
      <div style="display:flex;gap:12px;justify-content:flex-end;">
        <a href="view.php?id=<?= $id ?>" class="btn btn-outline">إلغاء</a>
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> حفظ التعديلات</button>
      </div>
    </div>
  </div>
</form>
<?php require_once INCLUDES_PATH . '/footer.php'; ?>
