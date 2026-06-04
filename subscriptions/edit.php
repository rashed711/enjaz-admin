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

$errors = [];
$formData = $sub;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) { $errors[] = 'خطأ في الأمان.'; }
    else {
        $formData['plan_name']  = clean($_POST['plan_name'] ?? '');
        $formData['price']      = (float)($_POST['price'] ?? 0);
        $formData['start_date'] = clean($_POST['start_date'] ?? '');
        $formData['end_date']   = clean($_POST['end_date'] ?? '');
        $formData['status']     = clean($_POST['status'] ?? 'active');
        $formData['notes']      = clean($_POST['notes'] ?? '');

        if ($formData['price'] <= 0) $errors[] = 'السعر يجب أن يكون أكبر من صفر.';
        if (!$formData['start_date']) $errors[] = 'تاريخ البداية مطلوب.';
        if (!$formData['end_date'])   $errors[] = 'تاريخ النهاية مطلوب.';

        if (empty($errors)) {
            $db->prepare("UPDATE client_subscriptions SET plan_name=?,price=?,start_date=?,end_date=?,status=?,notes=? WHERE id=?")
               ->execute([$formData['plan_name'],$formData['price'],$formData['start_date'],$formData['end_date'],$formData['status'],$formData['notes'],$id]);
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
          <input type="text" id="plan_name" name="plan_name" class="form-control" value="<?= e($formData['plan_name']) ?>">
        </div>
      </div>
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
          <label class="form-label" for="start_date">البداية <span class="required">*</span></label>
          <input type="date" id="startDate" name="start_date" class="form-control" value="<?= $formData['start_date'] ?>" required>
        </div>
        <div class="form-group">
          <label class="form-label">المدة (للحساب)</label>
          <select id="durationMonths" class="form-control">
            <option value="0">—</option>
            <option value="1">شهر</option>
            <option value="3">3 أشهر</option>
            <option value="6">6 أشهر</option>
            <option value="12">سنة</option>
            <option value="24">سنتان</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label" for="end_date">النهاية <span class="required">*</span></label>
          <input type="date" id="endDate" name="end_date" class="form-control" value="<?= $formData['end_date'] ?>" required>
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
