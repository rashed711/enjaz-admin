<?php
/**
 * users/edit.php - تعديل مستخدم
 */
require_once dirname(__DIR__) . '/config/app.php';
requireLogin();
if (!isAdmin()) { header('Location: ../403.php'); exit; }

$db = getDB();
$id = (int)($_GET['id'] ?? 0);
$user = $db->prepare("SELECT * FROM users WHERE id = ?");
$user->execute([$id]);
$user = $user->fetch();
if (!$user) { setFlash('error', 'المستخدم غير موجود.'); header('Location: index.php'); exit; }

$errors   = [];
$formData = [
    'username'    => $user['username'],
    'full_name'   => $user['full_name'],
    'role'        => $user['role'],
    'status'      => $user['status'],
    'permissions' => json_decode($user['permissions'] ?? '[]', true) ?? [],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) { $errors[] = 'خطأ في الأمان.'; }
    else {
        $formData['full_name']   = clean($_POST['full_name'] ?? '');
        $formData['role']        = in_array($_POST['role'] ?? '', ['admin','employee']) ? $_POST['role'] : 'employee';
        $formData['status']      = ($_POST['status'] ?? '1') === '1' ? 1 : 0;
        $formData['permissions'] = array_keys(array_filter($_POST['permissions'] ?? [], fn($v) => $v === '1'));
        $newPassword             = $_POST['new_password'] ?? '';
        $passwordConfirm         = $_POST['password_confirm'] ?? '';

        if (empty($formData['full_name'])) $errors[] = 'الاسم الكامل مطلوب.';
        if (!empty($newPassword) && strlen($newPassword) < 6) $errors[] = 'كلمة المرور يجب 6 أحرف على الأقل.';
        if (!empty($newPassword) && $newPassword !== $passwordConfirm) $errors[] = 'كلمة المرور وتأكيدها غير متطابقتين.';

        if (empty($errors)) {
            $permsJson = $formData['role'] === 'admin' ? null : json_encode($formData['permissions']);
            if (!empty($newPassword)) {
                $hashed = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);
                $db->prepare("UPDATE users SET full_name=?, role=?, permissions=?, status=?, password=? WHERE id=?")
                   ->execute([$formData['full_name'], $formData['role'], $permsJson, $formData['status'], $hashed, $id]);
            } else {
                $db->prepare("UPDATE users SET full_name=?, role=?, permissions=?, status=? WHERE id=?")
                   ->execute([$formData['full_name'], $formData['role'], $permsJson, $formData['status'], $id]);
            }
            setFlash('success', 'تم تحديث بيانات المستخدم "' . $formData['full_name'] . '" بنجاح.');
            header('Location: index.php');
            exit;
        }
    }
}

$allPerms   = ALL_PERMISSIONS;
$pageTitle  = 'تعديل المستخدم';
$activePage = 'users';
$depth      = 1;
require_once INCLUDES_PATH . '/header.php';
?>

<div class="page-header">
  <div class="page-header-text">
    <h1 class="page-title"><i class="fas fa-user-edit" style="color:var(--primary-light);margin-left:8px;"></i>تعديل: <?= e($user['full_name']) ?></h1>
    <p class="page-subtitle">تعديل بيانات الحساب وصلاحياته</p>
  </div>
  <div class="page-actions">
    <a href="index.php" class="btn btn-outline"><i class="fas fa-arrow-right"></i> رجوع</a>
  </div>
</div>

<?php if ($errors): ?>
<div class="alert alert-error">
  <i class="fas fa-times-circle"></i>
  <div><?php foreach ($errors as $err): ?><div><?= e($err) ?></div><?php endforeach; ?></div>
</div>
<?php endif; ?>

<form method="POST" action="edit.php?id=<?= $id ?>" data-validate>
  <?= csrfField() ?>

  <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;align-items:start;">

    <!-- بيانات الحساب -->
    <div>
      <div class="card">
        <div class="card-header">
          <span class="card-title"><i class="fas fa-id-card"></i> بيانات الحساب</span>
        </div>
        <div class="card-body">

          <div class="form-group">
            <label class="form-label">اسم المستخدم</label>
            <input type="text" class="form-control" value="<?= e($formData['username']) ?>" readonly>
            <span class="form-hint">لا يمكن تغيير اسم المستخدم</span>
          </div>

          <div class="form-group">
            <label class="form-label" for="full_name">الاسم الكامل <span class="required">*</span></label>
            <input type="text" id="full_name" name="full_name" class="form-control"
                   value="<?= e($formData['full_name']) ?>" required>
          </div>

          <div class="form-group">
            <label class="form-label" for="new_password">كلمة المرور الجديدة</label>
            <input type="password" id="new_password" name="new_password" class="form-control"
                   placeholder="اتركه فارغاً لعدم التغيير" minlength="6">
            <span class="form-hint">6 أحرف على الأقل</span>
          </div>

          <div class="form-group">
            <label class="form-label" for="password_confirm">تأكيد كلمة المرور الجديدة</label>
            <input type="password" id="password_confirm" name="password_confirm" class="form-control"
                   placeholder="اتركه فارغاً لعدم التغيير">
          </div>

          <div class="form-row">
            <div class="form-group">
              <label class="form-label" for="role">الدور</label>
              <select id="role" name="role" class="form-control" onchange="togglePerms(this.value)"
                      <?= $id === currentUserId() ? 'disabled' : '' ?>>
                <option value="employee" <?= $formData['role'] === 'employee' ? 'selected' : '' ?>>موظف</option>
                <option value="admin" <?= $formData['role'] === 'admin' ? 'selected' : '' ?>>مدير</option>
              </select>
            </div>
            <div class="form-group">
              <label class="form-label" for="status">الحالة</label>
              <select id="status" name="status" class="form-control"
                      <?= $id === currentUserId() ? 'disabled' : '' ?>>
                <option value="1" <?= $formData['status'] ? 'selected' : '' ?>>نشط</option>
                <option value="0" <?= !$formData['status'] ? 'selected' : '' ?>>موقوف</option>
              </select>
            </div>
          </div>

        </div>
      </div>
    </div>

    <!-- الصلاحيات -->
    <div>
      <div class="card" id="permissionsCard">
        <div class="card-header">
          <span class="card-title"><i class="fas fa-shield-halved"></i> الصلاحيات</span>
          <div style="display:flex;gap:8px;">
            <button type="button" class="btn btn-sm btn-outline" onclick="selectAllPerms(true)">تحديد الكل</button>
            <button type="button" class="btn btn-sm btn-outline" onclick="selectAllPerms(false)">إلغاء الكل</button>
          </div>
        </div>
        <div class="card-body">
          <div style="display:grid;gap:10px;">
            <?php foreach ($allPerms as $key => $label): ?>
            <label style="display:flex;align-items:center;gap:10px;cursor:pointer;padding:10px 12px;
                          border:1.5px solid var(--border-color);border-radius:8px;transition:all .2s;font-size:13.5px;font-weight:500;"
                   onmouseover="this.style.borderColor='var(--primary-light)';this.style.background='rgba(36,86,164,.04)'"
                   onmouseout="this.style.borderColor='var(--border-color)';this.style.background=''">
              <input type="checkbox" name="permissions[<?= e($key) ?>]" value="1"
                     class="perm-checkbox" style="width:16px;height:16px;accent-color:var(--primary-light);"
                     <?= in_array($key, $formData['permissions']) ? 'checked' : '' ?>>
              <?= e($label) ?>
            </label>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>

  </div>

  <div style="display:flex;gap:12px;justify-content:flex-end;margin-top:20px;">
    <a href="index.php" class="btn btn-outline">إلغاء</a>
    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> حفظ التعديلات</button>
  </div>
</form>

<script>
function togglePerms(role) {
  const card = document.getElementById('permissionsCard');
  card.style.opacity = role === 'admin' ? '0.4' : '1';
  card.querySelectorAll('input').forEach(cb => cb.disabled = role === 'admin');
}
function selectAllPerms(check) {
  document.querySelectorAll('.perm-checkbox').forEach(cb => { if (!cb.disabled) cb.checked = check; });
}
togglePerms(document.getElementById('role')?.value || 'employee');
</script>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
