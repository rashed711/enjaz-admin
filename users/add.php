<?php
/**
 * users/add.php - إضافة مستخدم جديد
 */
require_once dirname(__DIR__) . '/config/app.php';
requireLogin();
if (!isAdmin()) { header('Location: ../403.php'); exit; }

$errors = [];
$formData = ['username' => '', 'full_name' => '', 'role' => 'employee', 'status' => '1', 'permissions' => []];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) { $errors[] = 'خطأ في الأمان.'; }
    else {
        $formData['username']    = clean($_POST['username'] ?? '');
        $formData['full_name']   = clean($_POST['full_name'] ?? '');
        $formData['role']        = in_array($_POST['role'] ?? '', ['admin','employee']) ? $_POST['role'] : 'employee';
        $formData['status']      = ($_POST['status'] ?? '1') === '1' ? 1 : 0;
        $formData['permissions'] = array_keys(array_filter($_POST['permissions'] ?? [], fn($v) => $v === '1'));
        $password                = $_POST['password'] ?? '';
        $passwordConfirm         = $_POST['password_confirm'] ?? '';

        // Validation
        if (empty($formData['username']))  $errors[] = 'اسم المستخدم مطلوب.';
        if (empty($formData['full_name'])) $errors[] = 'الاسم الكامل مطلوب.';
        if (strlen($password) < 6)         $errors[] = 'كلمة المرور يجب أن تكون 6 أحرف على الأقل.';
        if ($password !== $passwordConfirm) $errors[] = 'كلمة المرور وتأكيدها غير متطابقتين.';

        if (empty($errors)) {
            $db = getDB();
            // تحقق من تكرار اسم المستخدم
            $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
            $stmt->execute([$formData['username']]);
            if ($stmt->fetchColumn() > 0) {
                $errors[] = 'اسم المستخدم مستخدم بالفعل، اختر اسماً آخر.';
            } else {
                $hashed = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
                $permsJson = $formData['role'] === 'admin' ? null : json_encode($formData['permissions']);
                $db->prepare("INSERT INTO users (username, password, full_name, role, permissions, status) VALUES (?,?,?,?,?,?)")
                   ->execute([$formData['username'], $hashed, $formData['full_name'], $formData['role'], $permsJson, $formData['status']]);
                setFlash('success', 'تم إضافة المستخدم "' . $formData['full_name'] . '" بنجاح.');
                header('Location: index.php');
                exit;
            }
        }
    }
}

$allPerms   = ALL_PERMISSIONS;
$pageTitle  = 'إضافة مستخدم';
$activePage = 'users';
$depth      = 1;
require_once INCLUDES_PATH . '/header.php';
?>

<div class="page-header">
  <div class="page-header-text">
    <h1 class="page-title"><i class="fas fa-user-plus" style="color:var(--primary-light);margin-left:8px;"></i>إضافة مستخدم جديد</h1>
    <p class="page-subtitle">إنشاء حساب موظف جديد وتحديد صلاحياته</p>
  </div>
  <div class="page-actions">
    <a href="index.php" class="btn btn-outline"><i class="fas fa-arrow-right"></i> رجوع</a>
  </div>
</div>

<?php if ($errors): ?>
<div class="alert alert-error" data-auto-dismiss="6000">
  <i class="fas fa-times-circle"></i>
  <div><?php foreach ($errors as $err): ?><div><?= e($err) ?></div><?php endforeach; ?></div>
</div>
<?php endif; ?>

<form method="POST" action="add.php" data-validate id="addUserForm">
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
            <label class="form-label" for="full_name">الاسم الكامل <span class="required">*</span></label>
            <input type="text" id="full_name" name="full_name" class="form-control"
                   value="<?= e($formData['full_name']) ?>" placeholder="مثال: محمد أحمد" required>
          </div>

          <div class="form-group">
            <label class="form-label" for="username">اسم المستخدم <span class="required">*</span></label>
            <input type="text" id="username" name="username" class="form-control"
                   value="<?= e($formData['username']) ?>" placeholder="مثال: mohammed" required
                   pattern="[a-zA-Z0-9_]+" title="أحرف إنجليزية وأرقام وـ فقط">
            <span class="form-hint">أحرف إنجليزية وأرقام وـ فقط</span>
          </div>

          <div class="form-group">
            <label class="form-label" for="password">كلمة المرور <span class="required">*</span></label>
            <div style="position:relative;">
              <input type="password" id="password" name="password" class="form-control"
                     placeholder="6 أحرف على الأقل" required minlength="6">
              <button type="button" onclick="togglePass('password', 'icon1')"
                      style="position:absolute;left:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--text-muted);">
                <i class="fas fa-eye" id="icon1"></i>
              </button>
            </div>
          </div>

          <div class="form-group">
            <label class="form-label" for="password_confirm">تأكيد كلمة المرور <span class="required">*</span></label>
            <div style="position:relative;">
              <input type="password" id="password_confirm" name="password_confirm" class="form-control"
                     placeholder="أعد إدخال كلمة المرور" required>
              <button type="button" onclick="togglePass('password_confirm', 'icon2')"
                      style="position:absolute;left:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--text-muted);">
                <i class="fas fa-eye" id="icon2"></i>
              </button>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label class="form-label" for="role">الدور</label>
              <select id="role" name="role" class="form-control" id="roleSelect"
                      onchange="togglePerms(this.value)">
                <option value="employee" <?= $formData['role'] === 'employee' ? 'selected' : '' ?>>موظف</option>
                <option value="admin" <?= $formData['role'] === 'admin' ? 'selected' : '' ?>>مدير (كل الصلاحيات)</option>
              </select>
            </div>
            <div class="form-group">
              <label class="form-label" for="status">الحالة</label>
              <select id="status" name="status" class="form-control">
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
          <span class="card-title"><i class="fas fa-shield-halved"></i> الصلاحيات المخصصة</span>
          <div style="display:flex;gap:8px;">
            <button type="button" class="btn btn-sm btn-outline" onclick="selectAllPerms(true)">تحديد الكل</button>
            <button type="button" class="btn btn-sm btn-outline" onclick="selectAllPerms(false)">إلغاء الكل</button>
          </div>
        </div>
        <div class="card-body">
          <div style="display:grid;gap:10px;">
            <?php foreach ($allPerms as $key => $label): ?>
            <label style="display:flex;align-items:center;gap:10px;cursor:pointer;padding:10px 12px;
                          border:1.5px solid var(--border-color);border-radius:8px;transition:all .2s;
                          font-size:13.5px;font-weight:500;"
                   onmouseover="this.style.borderColor='var(--primary-light)';this.style.background='rgba(36,86,164,.04)'"
                   onmouseout="this.style.borderColor='var(--border-color)';this.style.background=''">
              <input type="checkbox"
                     name="permissions[<?= e($key) ?>]"
                     value="1"
                     class="perm-checkbox"
                     style="width:16px;height:16px;accent-color:var(--primary-light);"
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
    <button type="submit" class="btn btn-primary" id="saveBtn">
      <i class="fas fa-save"></i>
      حفظ المستخدم
    </button>
  </div>
</form>

<script>
function togglePerms(role) {
  const card = document.getElementById('permissionsCard');
  card.style.opacity = role === 'admin' ? '0.4' : '1';
  card.querySelectorAll('input').forEach(cb => cb.disabled = role === 'admin');
}

function selectAllPerms(check) {
  document.querySelectorAll('.perm-checkbox').forEach(cb => {
    if (!cb.disabled) cb.checked = check;
  });
}

function togglePass(inputId, iconId) {
  const inp  = document.getElementById(inputId);
  const icon = document.getElementById(iconId);
  inp.type   = inp.type === 'password' ? 'text' : 'password';
  icon.className = inp.type === 'password' ? 'fas fa-eye' : 'fas fa-eye-slash';
}

// Init
togglePerms(document.getElementById('roleSelect')?.value || 'employee');
</script>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
