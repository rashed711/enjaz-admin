<?php
/**
 * users/index.php - قائمة المستخدمين
 */
require_once dirname(__DIR__) . '/config/app.php';
requireLogin();
if (!isAdmin()) { header('Location: ../403.php'); exit; }

$db    = getDB();
$users = $db->query("SELECT * FROM users ORDER BY role ASC, full_name ASC")->fetchAll();

$pageTitle  = 'إدارة المستخدمين';
$activePage = 'users';
$depth      = 1;
require_once INCLUDES_PATH . '/header.php';
?>

<div class="page-header">
  <div class="page-header-text">
    <h1 class="page-title"><i class="fas fa-user-shield" style="color:var(--primary-light);margin-left:8px;"></i>المستخدمون والصلاحيات</h1>
    <p class="page-subtitle">إدارة حسابات الموظفين وتخصيص صلاحياتهم</p>
  </div>
  <div class="page-actions">
    <a href="add.php" class="btn btn-primary" id="btn-add-user">
      <i class="fas fa-user-plus"></i>
      إضافة مستخدم
    </a>
  </div>
</div>

<div class="card">
  <div class="card-header">
    <span class="card-title"><i class="fas fa-list"></i> قائمة المستخدمين</span>
    <span class="badge badge-primary"><?= count($users) ?> مستخدم</span>
  </div>
  <div class="table-wrapper">
    <table class="data-table">
      <thead>
        <tr>
          <th>#</th>
          <th>الاسم الكامل</th>
          <th>اسم المستخدم</th>
          <th>الدور</th>
          <th>الحالة</th>
          <th>آخر دخول</th>
          <th>الصلاحيات</th>
          <th>الإجراءات</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($users)): ?>
        <tr><td colspan="8"><div class="empty-state"><div class="empty-icon"><i class="fas fa-users-slash"></i></div><p class="empty-title">لا يوجد مستخدمون</p></div></td></tr>
        <?php else: ?>
        <?php foreach ($users as $i => $user):
          $perms = json_decode($user['permissions'] ?? '[]', true) ?? [];
          $allPerms = ALL_PERMISSIONS;
        ?>
        <tr>
          <td class="text-muted"><?= $i + 1 ?></td>
          <td>
            <div style="display:flex;align-items:center;gap:10px;">
              <div style="width:36px;height:36px;border-radius:50%;background:var(--primary-light);
                          display:flex;align-items:center;justify-content:center;color:#fff;
                          font-weight:700;font-size:14px;flex-shrink:0;">
                <?= e(mb_substr($user['full_name'], 0, 1, 'UTF-8')) ?>
              </div>
              <strong><?= e($user['full_name']) ?></strong>
            </div>
          </td>
          <td><code style="background:#f1f5f9;padding:3px 8px;border-radius:5px;font-size:12px;"><?= e($user['username']) ?></code></td>
          <td>
            <?php if ($user['role'] === 'admin'): ?>
              <span class="badge badge-accent"><i class="fas fa-crown"></i> مدير</span>
            <?php else: ?>
              <span class="badge badge-primary"><i class="fas fa-user"></i> موظف</span>
            <?php endif; ?>
          </td>
          <td>
            <?php if ($user['status']): ?>
              <span class="badge badge-success">نشط</span>
            <?php else: ?>
              <span class="badge badge-danger">موقوف</span>
            <?php endif; ?>
          </td>
          <td class="text-muted"><?= $user['last_login'] ? formatDateTime($user['last_login']) : 'لم يدخل بعد' ?></td>
          <td>
            <?php if ($user['role'] === 'admin'): ?>
              <span class="badge badge-secondary">كل الصلاحيات</span>
            <?php else: ?>
              <span style="font-size:12.5px;color:var(--text-secondary);">
                <?= count($perms) ?> من <?= count($allPerms) ?>
              </span>
            <?php endif; ?>
          </td>
          <td>
            <div class="table-actions">
              <a href="edit.php?id=<?= $user['id'] ?>" class="btn btn-sm btn-outline" title="تعديل">
                <i class="fas fa-edit"></i>
              </a>
              <?php if ($user['id'] !== currentUserId()): ?>
              <a href="toggle.php?id=<?= $user['id'] ?>"
                 class="btn btn-sm <?= $user['status'] ? 'btn-outline-danger' : 'btn-outline' ?>"
                 data-confirm="<?= $user['status'] ? 'هل تريد إيقاف هذا المستخدم؟' : 'هل تريد تفعيل هذا المستخدم؟' ?>"
                 title="<?= $user['status'] ? 'إيقاف' : 'تفعيل' ?>">
                <i class="fas fa-<?= $user['status'] ? 'ban' : 'check' ?>"></i>
              </a>
              <a href="delete.php?id=<?= $user['id'] ?>"
                 class="btn btn-sm btn-outline-danger"
                 data-confirm="هل أنت متأكد من حذف هذا المستخدم؟ لا يمكن التراجع."
                 title="حذف">
                <i class="fas fa-trash"></i>
              </a>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
