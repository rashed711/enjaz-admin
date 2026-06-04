<?php
/**
 * services/index.php - قائمة الخدمات مع إدارة الباقات
 */
require_once dirname(__DIR__) . '/config/app.php';
requireLogin();
requirePermission('manage_services');

$db       = getDB();
$services = $db->query("
    SELECT s.*, COUNT(DISTINCT cs.client_id) as clients_count
    FROM services s
    LEFT JOIN client_subscriptions cs ON cs.service_id = s.id AND cs.status = 'active'
    GROUP BY s.id ORDER BY s.sort_order ASC, s.name ASC
")->fetchAll();

// جلب الباقات لكل خدمة
$allPlans = [];
if (!empty($services)) {
    $ids      = implode(',', array_column($services, 'id'));
    $planRows = $db->query("SELECT * FROM service_plans WHERE service_id IN ($ids) AND status=1 ORDER BY service_id, sort_order ASC, price ASC")->fetchAll();
    foreach ($planRows as $p) {
        $allPlans[$p['service_id']][] = $p;
    }
}

$pageTitle  = 'الخدمات والباقات';
$activePage = 'services';
$depth      = 1;
require_once INCLUDES_PATH . '/header.php';
?>

<div class="page-header">
  <div class="page-header-text">
    <h1 class="page-title"><i class="fas fa-concierge-bell" style="color:var(--primary-light);margin-left:8px;"></i>الخدمات والباقات</h1>
    <p class="page-subtitle">إدارة الخدمات وباقات الأسعار لكل خدمة</p>
  </div>
  <div class="page-actions">
    <button onclick="openAddServiceModal()" class="btn btn-primary">
      <i class="fas fa-plus"></i> إضافة خدمة
    </button>
  </div>
</div>

<?php if (empty($services)): ?>
<div class="card">
  <div class="empty-state" style="padding:60px;">
    <div class="empty-icon"><i class="fas fa-cube"></i></div>
    <p class="empty-title">لا توجد خدمات بعد</p>
    <button onclick="openAddServiceModal()" class="btn btn-primary"><i class="fas fa-plus"></i> إضافة أول خدمة</button>
  </div>
</div>
<?php else: ?>

<?php foreach ($services as $srv): ?>
<div class="card" style="margin-bottom:20px;">

  <!-- Service Header -->
  <div class="card-header" style="background:linear-gradient(135deg,rgba(36,86,164,.04),rgba(36,86,164,.01));">
    <div style="display:flex;align-items:center;gap:12px;">
      <div style="width:40px;height:40px;background:linear-gradient(135deg,var(--primary-light),var(--primary));
                  border-radius:10px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:18px;">
        <i class="fas fa-server"></i>
      </div>
      <div>
        <div style="font-weight:800;font-size:15px;color:var(--text-primary);"><?= e($srv['name']) ?></div>
        <?php if ($srv['description']): ?>
        <div style="font-size:12px;color:var(--text-muted);"><?= e($srv['description']) ?></div>
        <?php endif; ?>
      </div>
    </div>
    <div style="display:flex;align-items:center;gap:10px;">
      <?php if ($srv['clients_count'] > 0): ?>
      <span class="badge badge-success"><i class="fas fa-users"></i> <?= $srv['clients_count'] ?> عميل نشط</span>
      <?php endif; ?>
      <?= $srv['status'] ? '<span class="badge badge-success">نشطة</span>' : '<span class="badge badge-secondary">موقوفة</span>' ?>
      <button onclick="openAddPlanModal(<?= $srv['id'] ?>, '<?= e(addslashes($srv['name'])) ?>')"
              class="btn btn-sm btn-primary" id="btn-add-plan-<?= $srv['id'] ?>">
        <i class="fas fa-plus"></i> إضافة باقة
      </button>
      <button onclick="editService(<?= htmlspecialchars(json_encode($srv), ENT_QUOTES) ?>)"
              class="btn btn-sm btn-outline" title="تعديل الخدمة">
        <i class="fas fa-edit"></i>
      </button>
      <a href="delete.php?id=<?= $srv['id'] ?>"
         class="btn btn-sm btn-outline-danger"
         data-confirm="حذف خدمة «<?= e($srv['name']) ?>» وكل باقاتها؟"
         title="حذف الخدمة">
        <i class="fas fa-trash"></i>
      </a>
    </div>
  </div>

  <!-- Plans List -->
  <?php $plans = $allPlans[$srv['id']] ?? []; ?>
  <?php if (empty($plans)): ?>
  <div style="padding:24px 20px;text-align:center;color:var(--text-muted);">
    <i class="fas fa-tags" style="font-size:28px;opacity:.3;display:block;margin-bottom:8px;"></i>
    <span style="font-size:13px;">لا توجد باقات — يمكنك إضافة باقات مختلفة بأسعار متنوعة</span>
    <button onclick="openAddPlanModal(<?= $srv['id'] ?>, '<?= e(addslashes($srv['name'])) ?>')"
            class="btn btn-sm btn-outline" style="margin-right:12px;">
      <i class="fas fa-plus"></i> إضافة أول باقة
    </button>
  </div>
  <?php else: ?>
  <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:14px;padding:18px 20px;">
    <?php foreach ($plans as $plan): ?>
    <div class="plan-card" style="border:1.5px solid var(--border-color);border-radius:12px;padding:16px;
                                   position:relative;transition:all .2s;background:#fff;">
      <div style="font-weight:800;font-size:14px;color:var(--text-primary);margin-bottom:4px;">
        <?= e($plan['name']) ?>
      </div>
      <?php if ($plan['description']): ?>
      <div style="font-size:12px;color:var(--text-muted);margin-bottom:8px;"><?= e($plan['description']) ?></div>
      <?php endif; ?>
      <div style="font-size:20px;font-weight:900;color:var(--primary-light);">
        <?= formatMoney($plan['price']) ?>
      </div>
      <!-- Actions -->
      <div style="display:flex;gap:6px;margin-top:10px;">
        <button onclick="editPlan(<?= htmlspecialchars(json_encode($plan), ENT_QUOTES) ?>)"
                class="btn btn-sm btn-outline" style="flex:1;">
          <i class="fas fa-edit"></i> تعديل
        </button>
        <a href="delete-plan.php?id=<?= $plan['id'] ?>&service_id=<?= $srv['id'] ?>"
           class="btn btn-sm btn-outline-danger"
           data-confirm="حذف باقة «<?= e($plan['name']) ?>»؟">
          <i class="fas fa-trash"></i>
        </a>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

</div>
<?php endforeach; ?>
<?php endif; ?>


<!-- ════ Modal: إضافة / تعديل خدمة ════════════════════════════════ -->
<div class="modal-overlay" id="addServiceModal" style="display:none;">
  <div class="modal">
    <div class="modal-header">
      <span class="modal-title" id="serviceModalTitle">
        <i class="fas fa-plus-circle" style="color:var(--primary-light);"></i> إضافة خدمة
      </span>
      <button class="modal-close" onclick="closeModal('addServiceModal')"><i class="fas fa-times"></i></button>
    </div>
    <form method="POST" action="save.php" data-validate id="serviceForm">
      <?= csrfField() ?>
      <input type="hidden" name="service_id" id="serviceId" value="">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label" for="srv_name">اسم الخدمة <span class="required">*</span></label>
          <input type="text" id="srv_name" name="name" class="form-control" required placeholder="مثال: استضافة البريد الإلكتروني">
        </div>
        <div class="form-group">
          <label class="form-label" for="srv_desc">الوصف</label>
          <textarea id="srv_desc" name="description" class="form-control" rows="2" placeholder="وصف مختصر للخدمة (اختياري)"></textarea>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label" for="srv_price">السعر الافتراضي</label>
            <input type="number" id="srv_price" name="default_price" class="form-control" step="0.01" min="0" value="0">
            <span class="form-hint">يُستخدم لو لم تُضف باقات</span>
          </div>
          <div class="form-group">
            <label class="form-label" for="srv_duration">المدة الافتراضية (أشهر)</label>
            <input type="number" id="srv_duration" name="duration_months" class="form-control" min="0" value="12">
            <span class="form-hint">0 = مرة واحدة</span>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label" for="srv_sort">ترتيب الظهور</label>
            <input type="number" id="srv_sort" name="sort_order" class="form-control" min="0" value="0">
            <span class="form-hint">مثال: 1=الدومين، 2=البريد...</span>
          </div>
          <div class="form-group">
            <label class="form-label" for="srv_status">الحالة</label>
            <select id="srv_status" name="status" class="form-control">
              <option value="1">نشطة</option>
              <option value="0">موقوفة</option>
            </select>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('addServiceModal')">إلغاء</button>
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> حفظ الخدمة</button>
      </div>
    </form>
  </div>
</div>


<!-- ════ Modal: إضافة / تعديل باقة ════════════════════════════════ -->
<div class="modal-overlay" id="addPlanModal" style="display:none;">
  <div class="modal">
    <div class="modal-header">
      <span class="modal-title" id="planModalTitle">
        <i class="fas fa-tags" style="color:var(--primary-light);"></i>
        إضافة باقة لـ <span id="planServiceName"></span>
      </span>
      <button class="modal-close" onclick="closeModal('addPlanModal')"><i class="fas fa-times"></i></button>
    </div>
    <form method="POST" action="save-plan.php" data-validate id="planForm">
      <?= csrfField() ?>
      <input type="hidden" name="plan_id"    id="planId"      value="">
      <input type="hidden" name="service_id" id="planSrvId"   value="">
      <div class="modal-body">

        <div class="form-group">
          <label class="form-label" for="plan_name">اسم الباقة <span class="required">*</span></label>
          <input type="text" id="plan_name_input" name="name" class="form-control" required
                 placeholder="مثال: باقة 1 جيجا، باقة 5 جيجا...">
        </div>

        <div class="form-group">
          <label class="form-label" for="plan_desc">الوصف / المواصفات</label>
          <input type="text" id="plan_desc" name="description" class="form-control"
                 placeholder="مثال: مساحة 1 GB, 10 حسابات بريد">
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label" for="plan_price">السعر <span class="required">*</span></label>
            <input type="number" id="plan_price" name="price" class="form-control"
                   step="0.01" min="0" required placeholder="0.00">
          </div>
          <div class="form-group">
            <label class="form-label" for="plan_sort">ترتيب الظهور</label>
            <input type="number" id="plan_sort" name="sort_order" class="form-control" min="0" value="0">
            <span class="form-hint">الأصغر يظهر أولاً</span>
          </div>
          <div class="form-group">
            <label class="form-label" for="plan_status">الحالة</label>
            <select id="plan_status" name="status" class="form-control">
              <option value="1">نشطة</option>
              <option value="0">موقوفة</option>
            </select>
          </div>
        </div>

      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('addPlanModal')">إلغاء</button>
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> حفظ الباقة</button>
      </div>
    </form>
  </div>
</div>


<style>
.plan-card:hover {
  border-color: var(--primary-light) !important;
  box-shadow: 0 4px 16px rgba(36,86,164,.1);
  transform: translateY(-2px);
}
</style>

<script>
// ── Service Modal ────────────────────────────────────
function openAddServiceModal() {
  document.getElementById('serviceModalTitle').innerHTML = '<i class="fas fa-plus-circle" style="color:var(--primary-light);"></i> إضافة خدمة';
  document.getElementById('serviceId').value    = '';
  document.getElementById('srv_name').value     = '';
  document.getElementById('srv_desc').value     = '';
  document.getElementById('srv_price').value    = '0';
  document.getElementById('srv_duration').value = '12';
  document.getElementById('srv_sort').value     = '0';
  document.getElementById('srv_status').value   = '1';
  openModal('addServiceModal');
}

function editService(srv) {
  document.getElementById('serviceModalTitle').innerHTML = '<i class="fas fa-edit" style="color:var(--primary-light);"></i> تعديل خدمة';
  document.getElementById('serviceId').value    = srv.id;
  document.getElementById('srv_name').value     = srv.name;
  document.getElementById('srv_desc').value     = srv.description || '';
  document.getElementById('srv_price').value    = srv.default_price;
  document.getElementById('srv_duration').value = srv.duration_months;
  document.getElementById('srv_sort').value     = srv.sort_order || 0;
  document.getElementById('srv_status').value   = srv.status;
  openModal('addServiceModal');
}

// ── Plan Modal ───────────────────────────────────────
function openAddPlanModal(serviceId, serviceName) {
  document.getElementById('planModalTitle').querySelector('i').className = 'fas fa-tags';
  document.getElementById('planModalTitle').innerHTML =
    '<i class="fas fa-tags" style="color:var(--primary-light);"></i> إضافة باقة لـ <strong>' + serviceName + '</strong>';
  document.getElementById('planId').value         = '';
  document.getElementById('planSrvId').value      = serviceId;
  document.getElementById('plan_name_input').value= '';
  document.getElementById('plan_desc').value      = '';
  document.getElementById('plan_price').value     = '';
  document.getElementById('plan_sort').value      = '0';
  document.getElementById('plan_status').value    = '1';
  openModal('addPlanModal');
}

function editPlan(plan) {
  document.getElementById('planModalTitle').innerHTML =
    '<i class="fas fa-edit" style="color:var(--primary-light);"></i> تعديل باقة';
  document.getElementById('planId').value         = plan.id;
  document.getElementById('planSrvId').value      = plan.service_id;
  document.getElementById('plan_name_input').value= plan.name;
  document.getElementById('plan_desc').value      = plan.description || '';
  document.getElementById('plan_price').value     = plan.price;
  document.getElementById('plan_sort').value      = plan.sort_order || 0;
  document.getElementById('plan_status').value    = plan.status;
  openModal('addPlanModal');
}
</script>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
