<?php
/**
 * subscriptions/renew.php - تجديد اشتراك
 */
require_once dirname(__DIR__) . '/config/app.php';
requireLogin();
requirePermission('add_subscriptions');

$db  = getDB();
$id  = (int)($_GET['id'] ?? 0);
$stmt = $db->prepare("SELECT cs.*, s.name as service_name, s.duration_months, c.name as client_name FROM client_subscriptions cs JOIN services s ON s.id=cs.service_id JOIN clients c ON c.id=cs.client_id WHERE cs.id=?");
$stmt->execute([$id]);
$sub = $stmt->fetch();
if (!$sub) { setFlash('error','الاشتراك غير موجود.'); header('Location: ../clients/index.php'); exit; }

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) { $errors[] = 'خطأ في الأمان.'; }
    else {
        $price     = (float)($_POST['price'] ?? 0);
        $startDate = clean($_POST['start_date'] ?? '');
        $endDate   = clean($_POST['end_date'] ?? '');
        $notes     = clean($_POST['notes'] ?? '');

        if ($price <= 0)  $errors[] = 'السعر يجب أن يكون أكبر من صفر.';
        if (!$startDate)  $errors[] = 'تاريخ البداية مطلوب.';
        if (!$endDate)    $errors[] = 'تاريخ النهاية مطلوب.';

        if (empty($errors)) {
            // Mark old as expired
            $db->prepare("UPDATE client_subscriptions SET status='expired' WHERE id=?")->execute([$id]);
            // Create new
            $db->prepare("INSERT INTO client_subscriptions (client_id,service_id,plan_name,price,start_date,end_date,notes,status,created_by) VALUES (?,?,?,?,?,?,'تجديد: '||?,'active',?)")
               ->execute([$sub['client_id'],$sub['service_id'],$sub['plan_name'],$price,$startDate,$endDate,$notes,currentUserId()]);
            setFlash('success','تم تجديد الاشتراك بنجاح.');
            header("Location: ../clients/view.php?id={$sub['client_id']}");
            exit;
        }
    }
}

// Suggested new dates
$newStart = date('Y-m-d');
$dur = (int)($sub['duration_months'] ?? 12);
$newEnd = $dur > 0
    ? date('Y-m-d', strtotime("+$dur months -1 day", strtotime($newStart)))
    : $newStart;

$pageTitle  = 'تجديد اشتراك';
$activePage = 'subscriptions';
$depth      = 1;
require_once INCLUDES_PATH . '/header.php';
?>
<div class="page-header">
  <div class="page-header-text">
    <h1 class="page-title"><i class="fas fa-redo" style="color:var(--success);margin-left:8px;"></i>تجديد اشتراك</h1>
    <p class="page-subtitle"><?= e($sub['client_name']) ?> — <?= e($sub['service_name']) ?></p>
  </div>
  <div class="page-actions">
    <a href="../clients/view.php?id=<?= $sub['client_id'] ?>" class="btn btn-outline"><i class="fas fa-arrow-right"></i> رجوع</a>
  </div>
</div>

<div class="card" style="max-width:600px;margin:0 auto;">
  <div class="card-header"><span class="card-title"><i class="fas fa-redo" style="color:var(--success);"></i> بيانات التجديد</span></div>
  <div class="card-body">
    <div style="background:var(--info-light);border-right:4px solid var(--info);border-radius:8px;padding:12px 14px;margin-bottom:20px;font-size:13px;color:#1e40af;">
      <strong>الاشتراك الحالي:</strong> <?= e($sub['service_name']) ?> | من <?= formatDate($sub['start_date']) ?> إلى <?= formatDate($sub['end_date']) ?> | السعر: <?= formatMoney($sub['price']) ?>
    </div>
    <form method="POST" action="renew.php?id=<?= $id ?>" data-validate>
      <?= csrfField() ?>
      <div class="form-group">
        <label class="form-label" for="price">سعر التجديد <span class="required">*</span></label>
        <input type="number" id="price" name="price" class="form-control" step="0.01" min="0" value="<?= $sub['price'] ?>" required>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label" for="startDate">بداية التجديد <span class="required">*</span></label>
          <input type="date" id="startDate" name="start_date" class="form-control" value="<?= $newStart ?>" required>
        </div>
        <div class="form-group">
          <label class="form-label">المدة</label>
          <select id="durationMonths" class="form-control">
            <option value="1">شهر</option>
            <option value="3">3 أشهر</option>
            <option value="6">6 أشهر</option>
            <option value="12" <?= $dur===12?'selected':'' ?>>سنة</option>
            <option value="24">سنتان</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label" for="endDate">نهاية التجديد <span class="required">*</span></label>
          <input type="date" id="endDate" name="end_date" class="form-control" value="<?= $newEnd ?>" required>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label" for="renew_notes">ملاحظات</label>
        <textarea id="renew_notes" name="notes" class="form-control" rows="2" placeholder="ملاحظات التجديد..."></textarea>
      </div>
      <div style="display:flex;gap:12px;justify-content:flex-end;">
        <a href="../clients/view.php?id=<?= $sub['client_id'] ?>" class="btn btn-outline">إلغاء</a>
        <button type="submit" class="btn btn-success"><i class="fas fa-redo"></i> تجديد الاشتراك</button>
      </div>
    </form>
  </div>
</div>
<?php require_once INCLUDES_PATH . '/footer.php'; ?>
