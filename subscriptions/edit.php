<?php
/**
 * subscriptions/edit.php - تعديل اشتراك
 */
require_once dirname(__DIR__) . '/config/app.php';
requireLogin();
requirePermission('edit_subscriptions');

$db  = getDB();
$id  = (int)($_GET['id'] ?? 0);
$stmt = $db->prepare("SELECT cs.*, s.name as service_name, c.name as client_name FROM client_subscriptions cs JOIN services s ON s.id=cs.service_id JOIN clients c ON c.id=cs.client_id WHERE cs.id=?");
$stmt->execute([$id]);
$sub = $stmt->fetch();
if (!$sub) { setFlash('error','الاشتراك غير موجود.'); header('Location: ../clients/index.php'); exit; }

// جلب الباقات المتاحة لهذه الخدمة
$plans = $db->prepare("SELECT * FROM service_plans WHERE service_id = ? AND status = 1 ORDER BY sort_order ASC, price ASC");
$plans->execute([$sub['service_id']]);
$plans = $plans->fetchAll();

// جلب مدة الخدمة الافتراضية
$srvStmt = $db->prepare("SELECT duration_months FROM services WHERE id = ?");
$srvStmt->execute([$sub['service_id']]);
$srv = $srvStmt->fetch();
$serviceDuration = $srv ? (int)$srv['duration_months'] : 12;

$errors = [];
$formData = $sub;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) { $errors[] = 'خطأ في الأمان.'; }
    else {
        $planName = clean($_POST['plan_name'] ?? '');
        if ($planName === 'custom') {
            $planName = clean($_POST['custom_plan_name'] ?? '');
        }
        $formData['plan_name']  = $planName;
        $formData['price']      = (float)($_POST['price'] ?? 0);
        $formData['start_date'] = clean($_POST['start_date'] ?? '');
        $formData['end_date']   = clean($_POST['end_date'] ?? '');
        $formData['status']     = clean($_POST['status'] ?? 'active');
        $formData['notes']      = clean($_POST['notes'] ?? '');

        if ($formData['price'] <= 0) $errors[] = 'السعر يجب أن يكون أكبر من صفر.';

        $dbValStartDate = null;
        $dbValEndDate   = null;

        if ($serviceDuration > 0) {
            if (!$formData['start_date']) {
                $errors[] = 'تاريخ البداية مطلوب لهذه الخدمة.';
            } else {
                $dbValStartDate = $formData['start_date'];
            }
            if (!$formData['end_date']) {
                $errors[] = 'تاريخ النهاية مطلوب لهذه الخدمة.';
            } else {
                $dbValEndDate = $formData['end_date'];
            }
        } else {
            if ($formData['start_date']) $dbValStartDate = $formData['start_date'];
            if ($formData['end_date'])   $dbValEndDate   = $formData['end_date'];
        }

        if (empty($errors)) {
            $db->prepare("UPDATE client_subscriptions SET plan_name=?,price=?,start_date=?,end_date=?,status=?,notes=? WHERE id=?")
               ->execute([$formData['plan_name'],$formData['price'],$dbValStartDate,$dbValEndDate,$formData['status'],$formData['notes'],$id]);
            setFlash('success','تم تحديث الاشتراك بنجاح.');
            header("Location: ../clients/view.php?id={$sub['client_id']}");
            exit;
        }
    }
}

$pageTitle  = 'تعديل اشتراك';
$activePage = 'subscriptions';
$depth      = 1;
require_once INCLUDES_PATH . '/header.php';
?>
<div class="page-header">
  <div class="page-header-text">
    <h1 class="page-title"><i class="fas fa-edit" style="color:var(--primary-light);margin-left:8px;"></i>تعديل اشتراك</h1>
    <p class="page-subtitle"><?= e($sub['client_name']) ?> — <?= e($sub['service_name']) ?></p>
  </div>
  <div class="page-actions">
    <a href="../clients/view.php?id=<?= $sub['client_id'] ?>" class="btn btn-outline"><i class="fas fa-arrow-right"></i> رجوع</a>
  </div>
</div>
<?php if ($errors): ?>
<div class="alert alert-error"><i class="fas fa-times-circle"></i><div><?php foreach ($errors as $err): ?><div><?= e($err) ?></div><?php endforeach; ?></div></div>
<?php endif; ?>
<div class="card">
  <div class="card-header"><span class="card-title"><i class="fas fa-file-contract"></i> بيانات الاشتراك</span></div>
  <div class="card-body">
    <form method="POST" action="edit.php?id=<?= $id ?>" data-validate>
      <?= csrfField() ?>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">الخدمة</label>
          <input type="text" class="form-control" value="<?= e($sub['service_name']) ?>" readonly>
        </div>
        <div class="form-group">
          <label class="form-label" for="plan_name">الخطة</label>
          <?php if (!empty($plans)): 
              $planNames = array_column($plans, 'name');
              $isCustom = !empty($formData['plan_name']) && !in_array($formData['plan_name'], $planNames);
          ?>
            <select id="plan_name" name="plan_name" class="form-control" onchange="updatePriceFromPlan(this)">
              <option value="" data-price="0.00">— اختر باقة —</option>
              <?php foreach ($plans as $p): ?>
                <option value="<?= e($p['name']) ?>" data-price="<?= $p['price'] ?>" <?= $formData['plan_name'] === $p['name'] ? 'selected' : '' ?>>
                  <?= e($p['name']) ?> (<?= formatMoney($p['price']) ?>)
                </option>
              <?php endforeach; ?>
              <option value="custom" data-price="" <?= $isCustom ? 'selected' : '' ?>>باقة مخصصة...</option>
            </select>
            <input type="text" id="custom_plan_name" name="custom_plan_name" class="form-control" 
                   style="margin-top:8px; display: <?= $isCustom ? 'block' : 'none' ?>;" 
                   placeholder="اكتب اسم الباقة المخصصة..." value="<?= e($formData['plan_name']) ?>">
          <?php else: ?>
            <input type="text" id="plan_name" name="plan_name" class="form-control" value="<?= e($formData['plan_name']) ?>">
          <?php endif; ?>
        </div>
      </div>
      <script>
        function updatePriceFromPlan(selectEl) {
          const selectedOpt = selectEl.options[selectEl.selectedIndex];
          const price = selectedOpt.getAttribute('data-price');
          const customInput = document.getElementById('custom_plan_name');
          
          if (selectEl.value === 'custom') {
            if (customInput) {
              customInput.style.display = 'block';
              customInput.value = '';
              customInput.focus();
            }
          } else {
            if (customInput) {
              customInput.style.display = 'none';
              customInput.value = selectEl.value;
            }
            if (price && parseFloat(price) > 0) {
              document.getElementById('price').value = parseFloat(price);
            }
          }
        }
      </script>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label" for="price">السعر <span class="required">*</span></label>
          <input type="number" id="price" name="price" class="form-control" step="0.01" min="0" value="<?= $formData['price'] ?>" required>
        </div>
        <div class="form-group">
          <label class="form-label" for="sub_status">الحالة</label>
          <select id="sub_status" name="status" class="form-control">
            <option value="active" <?= $formData['status']==='active'?'selected':'' ?>>نشط</option>
            <option value="expired" <?= $formData['status']==='expired'?'selected':'' ?>>منتهي</option>
            <option value="cancelled" <?= $formData['status']==='cancelled'?'selected':'' ?>>ملغي</option>
            <option value="pending" <?= $formData['status']==='pending'?'selected':'' ?>>معلّق</option>
          </select>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label" for="start_date">البداية <?= $serviceDuration > 0 ? '<span class="required">*</span>' : '' ?></label>
          <input type="date" id="startDate" name="start_date" class="form-control" value="<?= $formData['start_date'] ?>" <?= $serviceDuration > 0 ? 'required' : '' ?>>
        </div>
        <div class="form-group">
          <label class="form-label">المدة (للحساب)</label>
          <select id="durationMonths" class="form-control">
            <option value="0" <?= $serviceDuration === 0 ? 'selected' : '' ?>>—</option>
            <option value="1">شهر</option>
            <option value="3">3 أشهر</option>
            <option value="6">6 أشهر</option>
            <option value="12" <?= $serviceDuration === 12 ? 'selected' : '' ?>>سنة</option>
            <option value="24">سنتان</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label" for="end_date">النهاية <?= $serviceDuration > 0 ? '<span class="required">*</span>' : '' ?></label>
          <input type="date" id="endDate" name="end_date" class="form-control" value="<?= $formData['end_date'] ?>" <?= $serviceDuration > 0 ? 'required' : '' ?>>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label" for="edit_notes">ملاحظات</label>
        <textarea id="edit_notes" name="notes" class="form-control" rows="2"><?= e($formData['notes']) ?></textarea>
      </div>
      <div style="display:flex;gap:12px;justify-content:flex-end;">
        <a href="../clients/view.php?id=<?= $sub['client_id'] ?>" class="btn btn-outline">إلغاء</a>
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> حفظ</button>
      </div>
    </form>
  </div>
</div>
<?php require_once INCLUDES_PATH . '/footer.php'; ?>
