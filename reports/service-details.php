<?php
/**
 * reports/service-details.php - تفاصيل الخدمة والباقات والمشتركين
 */
require_once dirname(__DIR__) . '/config/app.php';
requireLogin();
requirePermission('view_reports');

$db = getDB();
$serviceId = (int)($_GET['id'] ?? 0);

// جلب تفاصيل الخدمة
$serviceStmt = $db->prepare("SELECT * FROM services WHERE id = ?");
$serviceStmt->execute([$serviceId]);
$service = $serviceStmt->fetch();

if (!$service) {
    die('الخدمة غير موجودة.');
}

// جلب الباقات المحددة في النظام للخدمة
$plansStmt = $db->prepare("SELECT * FROM service_plans WHERE service_id = ? ORDER BY sort_order ASC, name ASC");
$plansStmt->execute([$serviceId]);
$dbPlans = $plansStmt->fetchAll();

// جلب إحصائيات الاشتراكات لكل باقة (نشط وغير نشط) للخدمة
$totalsStmt = $db->prepare("
    SELECT 
        plan_name,
        COUNT(CASE WHEN status = 'active' THEN 1 END) as active_count,
        COUNT(CASE WHEN status != 'active' THEN 1 END) as suspended_count,
        COUNT(*) as total_count
    FROM client_subscriptions
    WHERE service_id = ?
    GROUP BY plan_name
");
$totalsStmt->execute([$serviceId]);
$planTotals = $totalsStmt->fetchAll();

// دمج الباقات المسجلة والاشتراكات الفعلية
$packages = [];
foreach ($dbPlans as $p) {
    $packages[$p['name']] = [
        'name' => $p['name'],
        'price' => $p['price'],
        'active' => 0,
        'suspended' => 0,
        'total' => 0,
        'is_db_plan' => true
    ];
}

foreach ($planTotals as $pt) {
    $name = $pt['plan_name'] ?: '—';
    if (!isset($packages[$name])) {
        $packages[$name] = [
            'name' => $name,
            'price' => null,
            'active' => 0,
            'suspended' => 0,
            'total' => 0,
            'is_db_plan' => false
        ];
    }
    $packages[$name]['active'] = (int)$pt['active_count'];
    $packages[$name]['suspended'] = (int)$pt['suspended_count'];
    $packages[$name]['total'] = (int)$pt['total_count'];
}

// فلاتر البحث والاشتراكات
$statusFilter = clean($_GET['status'] ?? '');
$planFilter   = clean($_GET['plan_name'] ?? '');
$search       = clean($_GET['search'] ?? '');

$where = ["cs.service_id = ?"];
$params = [$serviceId];

if ($statusFilter === 'active') {
    $where[] = "cs.status = 'active'";
} elseif ($statusFilter === 'suspended') {
    $where[] = "cs.status != 'active'";
}

if ($planFilter !== '') {
    $where[] = "cs.plan_name = ?";
    $params[] = $planFilter;
}

if ($search !== '') {
    $where[] = "(c.name LIKE ? OR c.company_name LIKE ?)";
    $s = "%$search%";
    $params[] = $s;
    $params[] = $s;
}

$whereStr = implode(' AND ', $where);

// جلب الاشتراكات المفلترة
$subsStmt = $db->prepare("
    SELECT cs.*, c.name as client_name, c.company_name, c.mobile, c.status as client_status,
           DATEDIFF(cs.end_date, CURDATE()) as days_left
    FROM client_subscriptions cs
    JOIN clients c ON c.id = cs.client_id
    WHERE $whereStr
    ORDER BY cs.status = 'active' DESC, cs.end_date ASC
");
$subsStmt->execute($params);
$subscriptions = $subsStmt->fetchAll();

$pageTitle  = 'تفاصيل خدمة: ' . $service['name'];
$activePage = 'financial-hub';
$depth      = 1;
require_once INCLUDES_PATH . '/header.php';
?>

<div class="page-header">
  <div class="page-header-text">
    <div style="display:flex; align-items:center; gap:12px; margin-bottom: 6px;">
      <a href="financial-hub.php?tab=services" class="btn btn-sm btn-outline" style="padding: 4px 10px; border-radius: 8px;">
        <i class="fas fa-arrow-right"></i> عودة للملخص
      </a>
    </div>
    <h1 class="page-title">
      <i class="fas fa-concierge-bell" style="color:var(--primary-light);margin-left:8px;"></i>
      إحصائيات خدمة: <?= e($service['name']) ?>
    </h1>
    <p class="page-subtitle"><?= e($service['description'] ?: 'عرض الباقات وتفاصيل المشتركين') ?></p>
  </div>
</div>

<!-- كروت الباقات وإحصائياتها -->
<h3 style="font-weight: 800; font-size: 16px; margin: 24px 0 12px; color: var(--text-primary);">
  <i class="fas fa-tags" style="color: var(--primary-light); margin-left: 6px;"></i>الباقات والاشتراكات فيها
</h3>

<div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap:16px; margin-bottom:28px;">
  <?php if (empty($packages)): ?>
    <div class="card" style="grid-column: 1 / -1; padding: 24px; text-align: center; color: var(--text-muted);">
      لا توجد باقات مسجلة أو اشتراكات نشطة لهذه الخدمة.
    </div>
  <?php else: ?>
    <?php foreach ($packages as $pkg): ?>
      <div class="card" style="border-right: 4px solid var(--primary-light); padding:16px; background:#fff; display:flex; flex-direction:column; gap:12px; margin:0;">
        <div style="display:flex; justify-content:space-between; align-items:center;">
          <span style="font-weight:800; font-size:14.5px; color:var(--text-primary);"><?= e($pkg['name']) ?></span>
          <?php if ($pkg['price'] !== null): ?>
            <span style="font-weight:700; color:var(--primary-light); font-size:13.5px;"><?= formatMoney($pkg['price']) ?></span>
          <?php else: ?>
            <span class="badge badge-secondary" style="font-size:10.5px;">باقة مخصصة</span>
          <?php endif; ?>
        </div>
        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:8px; border-top:1px solid #f1f5f9; padding-top:10px;">
          <div style="text-align:center; background:rgba(16,185,129,0.06); border-radius:8px; padding:6px 0;">
            <div style="font-size:11px; color:#065f46; font-weight:600;">نشط</div>
            <div style="font-size:15px; font-weight:800; color:var(--success);"><?= $pkg['active'] ?></div>
          </div>
          <div style="text-align:center; background:rgba(239,68,68,0.06); border-radius:8px; padding:6px 0;">
            <div style="font-size:11px; color:#991b1b; font-weight:600;">موقف / منتهي</div>
            <div style="font-size:15px; font-weight:800; color:var(--danger);"><?= $pkg['suspended'] ?></div>
          </div>
        </div>
        <div style="font-size:11.5px; text-align:center; color:var(--text-muted); font-weight:700;">
          إجمالي المشتركين: <?= $pkg['total'] ?>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<!-- المشتركون والفلترة -->
<h3 style="font-weight: 800; font-size: 16px; margin: 24px 0 12px; color: var(--text-primary);">
  <i class="fas fa-users" style="color: var(--primary-light); margin-left: 6px;"></i>قائمة المشتركين وتفاصيل الاشتراكات
</h3>

<div class="card" style="margin-bottom:16px;">
  <div class="filters-bar">
    <form method="GET" style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;width:100%;">
      <input type="hidden" name="id" value="<?= $serviceId ?>">
      
      <div class="search-box">
        <i class="fas fa-search search-icon"></i>
        <input type="text" name="search" class="form-control" placeholder="اسم العميل أو الشركة..." value="<?= e($search) ?>" autocomplete="off">
      </div>
      
      <select name="plan_name" class="form-control" style="width:auto;">
        <option value="">كل الباقات</option>
        <?php foreach (array_keys($packages) as $pName): ?>
          <option value="<?= e($pName) ?>" <?= $planFilter === $pName ? 'selected' : '' ?>><?= e($pName) ?></option>
        <?php endforeach; ?>
      </select>

      <select name="status" class="form-control" style="width:auto;">
        <option value="">كل الحالات</option>
        <option value="active" <?= $statusFilter === 'active' ? 'selected' : '' ?>>نشط</option>
        <option value="suspended" <?= $statusFilter === 'suspended' ? 'selected' : '' ?>>موقوف / منتهي / معلق</option>
      </select>

      <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> بحث وفلترة</button>
      <?php if ($search || $planFilter || $statusFilter): ?>
        <a href="service-details.php?id=<?= $serviceId ?>" class="btn btn-outline"><i class="fas fa-times"></i> إلغاء</a>
      <?php endif; ?>
    </form>
  </div>
</div>

<div class="card">
  <div class="table-wrapper">
    <table class="data-table">
      <thead>
        <tr>
          <th>العميل</th>
          <th>الشركة</th>
          <th>الجوال</th>
          <th>الباقة</th>
          <th>السعر الفعلي</th>
          <th>تاريخ البداية</th>
          <th>تاريخ النهاية</th>
          <th>المتبقي</th>
          <th>حالة الاشتراك</th>
          <th>حالة العميل</th>
          <th>إجراء</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($subscriptions)): ?>
          <tr>
            <td colspan="11">
              <div class="empty-state">
                <div class="empty-icon"><i class="fas fa-file-contract"></i></div>
                <p class="empty-title">لا توجد اشتراكات مطابقة للبحث</p>
              </div>
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($subscriptions as $sub): ?>
            <tr>
              <td>
                <a href="../clients/view.php?id=<?= $sub['client_id'] ?>" style="font-weight:700; color:var(--primary);">
                  <?= e($sub['client_name']) ?>
                </a>
              </td>
              <td class="text-muted"><?= e($sub['company_name'] ?: '—') ?></td>
              <td>
                <?php if ($sub['mobile']): ?>
                  <span class="text-muted" style="direction:ltr; display:inline-block; font-size:12.5px;"><?= e($sub['mobile']) ?></span>
                  <?php 
                  $cleanMob = preg_replace('/[^0-9]/', '', $sub['mobile']);
                  if (strlen($cleanMob) == 11 && strpos($cleanMob, '01') === 0) {
                      $cleanMob = '20' . substr($cleanMob, 1);
                  }
                  ?>
                  <a href="https://wa.me/<?= $cleanMob ?>" target="_blank" style="color:#25d366; margin-right:6px;" title="واتساب"><i class="fab fa-whatsapp"></i></a>
                <?php else: ?>
                  <span class="text-muted">—</span>
                <?php endif; ?>
              </td>
              <td><strong><?= e($sub['plan_name'] ?: '—') ?></strong></td>
              <td class="fw-bold text-primary"><?= formatMoney($sub['price']) ?></td>
              <td><?= formatDate($sub['start_date']) ?></td>
              <td><?= formatDate($sub['end_date']) ?></td>
              <td>
                <?php
                $d = $sub['days_left'];
                if ($d === null): ?>
                  <span class="badge badge-success">مفتوح</span>
                <?php elseif ($d < 0): ?>
                  <span class="badge badge-danger">انتهى</span>
                <?php elseif ($d <= 7): ?>
                  <span class="badge badge-danger"><?= $d ?> يوم</span>
                <?php elseif ($d <= 30): ?>
                  <span class="badge badge-warning"><?= $d ?> يوم</span>
                <?php else: ?>
                  <span class="badge badge-success"><?= $d ?> يوم</span>
                <?php endif; ?>
              </td>
              <td><?= subscriptionStatusBadge($sub['status'], $sub['end_date']) ?></td>
              <td>
                <?= $sub['client_status'] == 1 
                  ? '<span class="badge badge-success">نشط</span>' 
                  : '<span class="badge badge-danger">موقوف</span>' ?>
              </td>
              <td>
                <div class="table-actions">
                  <a href="../subscriptions/edit.php?id=<?= $sub['id'] ?>" class="btn btn-sm btn-outline" title="تعديل الاشتراك">
                    <i class="fas fa-edit"></i>
                  </a>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php
require_once INCLUDES_PATH . '/footer.php';
?>
