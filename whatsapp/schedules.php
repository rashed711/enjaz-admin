<?php
/**
 * whatsapp/schedules.php - إدارة التنبيهات والرسائل التلقائية المجدولة
 */
require_once dirname(__DIR__) . '/config/app.php';
requireLogin();
requirePermission('send_whatsapp');

$db = getDB();

// Handle pause/resume toggle via GET
if (isset($_GET['toggle'])) {
    $scheduleId = (int)$_GET['toggle'];
    $status = (int)$_GET['status'];
    $stmt = $db->prepare("UPDATE whatsapp_schedules SET status = ? WHERE id = ?");
    $stmt->execute([$status, $scheduleId]);
    setFlash('success', 'تم تحديث حالة الجدولة بنجاح.');
    header('Location: schedules.php');
    exit;
}

// Fetch all schedules
$schedules = $db->query("SELECT * FROM whatsapp_schedules ORDER BY created_at DESC")->fetchAll();

$pageTitle  = 'جدولة الرسائل التلقائية';
$activePage = 'whatsapp-schedules';
$depth      = 1;
require_once INCLUDES_PATH . '/header.php';
?>

<div class="page-header">
  <div class="page-header-text">
    <h1 class="page-title"><i class="fas fa-clock" style="color:var(--primary-light);margin-left:8px;"></i>جدولة الرسائل التلقائية</h1>
    <p class="page-subtitle">إنشاء وإدارة رسائل الواتساب التي ترسل للعملاء بشكل تلقائي متكرر</p>
  </div>
  <div class="page-actions">
    <button onclick="openAddScheduleModal()" class="btn btn-primary">
      <i class="fas fa-plus"></i> إنشاء جدولة جديدة
    </button>
  </div>
</div>

<?php if (empty($schedules)): ?>
<div class="card">
  <div class="empty-state" style="padding:60px;">
    <div class="empty-icon" style="font-size: 48px; color: var(--primary-light); opacity: 0.4;"><i class="fas fa-calendar-alt"></i></div>
    <p class="empty-title">لا توجد رسائل مجدولة حالياً</p>
    <p class="empty-text">ابدأ بإنشاء أول جدولة تلقائية للتواصل مع عملائك بشكل دوري.</p>
    <button onclick="openAddScheduleModal()" class="btn btn-primary"><i class="fas fa-plus"></i> إنشاء جدولة جديدة</button>
  </div>
</div>
<?php else: ?>
<div class="card">
  <div class="table-wrapper">
    <table class="data-table">
      <thead>
        <tr>
          <th>عنوان الجدولة</th>
          <th>المستهدفون</th>
          <th>التكرار</th>
          <th>وقت الإرسال</th>
          <th>آخر تشغيل</th>
          <th>التشغيل القادم</th>
          <th>الحالة</th>
          <th style="text-align: left;">إجراءات</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($schedules as $sch): ?>
        <tr>
          <td>
            <strong><?= e($sch['title']) ?></strong>
            <div style="font-size:11.5px;color:var(--text-muted);max-width:280px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" title="<?= e($sch['message']) ?>">
              <?= e($sch['message']) ?>
            </div>
          </td>
          <td>
            <?php
            $targetLabels = [
                'all'             => '<span class="badge badge-primary">كل العملاء</span>',
                'active'          => '<span class="badge badge-success">العملاء النشطين فقط</span>',
                'suspended'       => '<span class="badge badge-danger">العملاء الموقوفين فقط</span>',
                'debt'            => '<span class="badge badge-warning">عملاء عليهم مديونية</span>',
                'active_debt'     => '<span class="badge badge-warning">العملاء النشطين الذين عليهم مديونية</span>',
                'all_debt'        => '<span class="badge badge-warning">كل العملاء الذين عليهم مديونية</span>',
                'expiring'        => '<span class="badge badge-info">تجديدات قريبة (' . $sch['warning_days'] . ' يوم)</span>',
                'active_expiring' => '<span class="badge badge-info">النشطين - تجديدات قريبة (' . $sch['warning_days'] . ' يوم)</span>',
                'all_expiring'    => '<span class="badge badge-info">الكل - تجديدات قريبة (' . $sch['warning_days'] . ' يوم)</span>',
                'active_expired'  => '<span class="badge badge-danger">النشطين - اشتراكات منتهية بالفعل</span>',
                'new_clients'     => '<span class="badge badge-success">العملاء الجدد (آخر 30 يوم)</span>',
                'no_subscriptions'=> '<span class="badge badge-secondary">عملاء بدون أي اشتراكات</span>',
            ];
            echo $targetLabels[$sch['target_type']] ?? $sch['target_type'];
            ?>
          </td>
          <td>
            <?php
            if ($sch['frequency'] === 'daily') echo 'يوميًا';
            elseif ($sch['frequency'] === 'weekly') echo 'أسبوعيًا';
            elseif ($sch['frequency'] === 'monthly') echo 'شهريًا';
            elseif ($sch['frequency'] === 'interval') echo 'كل ' . $sch['custom_interval_days'] . ' يوم';
            ?>
          </td>
          <td class="fw-bold"><?= date('h:i A', strtotime($sch['send_at_time'])) ?></td>
          <td class="text-muted"><?= $sch['last_run'] ? formatDateTime($sch['last_run']) : '—' ?></td>
          <td style="color:var(--primary-light);font-weight:700;"><?= formatDateTime($sch['next_run']) ?></td>
          <td>
            <?php if ($sch['status']): ?>
              <span class="badge badge-success">نشطة</span>
            <?php else: ?>
              <span class="badge badge-secondary">موقوفة</span>
            <?php endif; ?>
          </td>
          <td>
            <div class="table-actions" style="justify-content: flex-end;">
              <?php if ($sch['status']): ?>
                <a href="schedules.php?toggle=<?= $sch['id'] ?>&status=0" class="btn btn-sm btn-outline" title="إيقاف مؤقت">
                  <i class="fas fa-pause"></i>
                </a>
              <?php else: ?>
                <a href="schedules.php?toggle=<?= $sch['id'] ?>&status=1" class="btn btn-sm btn-outline-success" title="تفعيل">
                  <i class="fas fa-play"></i>
                </a>
              <?php endif; ?>
              
              <button onclick="editSchedule(<?= htmlspecialchars(json_encode($sch), ENT_QUOTES) ?>)" class="btn btn-sm btn-outline" title="تعديل">
                <i class="fas fa-edit"></i>
              </button>
              
              <a href="delete_schedule.php?id=<?= $sch['id'] ?>" class="btn btn-sm btn-outline-danger" data-confirm="هل تريد حذف هذه الجدولة التلقائية نهائياً؟" title="حذف">
                <i class="fas fa-trash"></i>
              </a>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<!-- ════ Modal: إضافة / تعديل جدولة ════════════════════════════════ -->
<div class="modal-overlay" id="scheduleModal" style="display:none;">
  <div class="modal" style="max-width: 600px;">
    <div class="modal-header">
      <span class="modal-title" id="scheduleModalTitle">
        <i class="fas fa-plus-circle" style="color:var(--primary-light);"></i> إنشاء جدولة جديدة
      </span>
      <button class="modal-close" onclick="closeModal('scheduleModal')"><i class="fas fa-times"></i></button>
    </div>
    <form method="POST" action="save_schedule.php" data-validate id="scheduleForm">
      <?= csrfField() ?>
      <input type="hidden" name="id" id="scheduleId" value="">
      <div class="modal-body">
        
        <div class="form-group">
          <label class="form-label" for="sch_title">عنوان الجدولة <span class="required">*</span></label>
          <input type="text" id="sch_title" name="title" class="form-control" required placeholder="مثال: تنبيه التجديد التلقائي للعملاء">
        </div>

        <div class="form-group">
          <label class="form-label" for="sch_message">نص الرسالة <span class="required">*</span></label>
          <textarea id="sch_message" name="message" class="form-control" rows="4" required placeholder="اكتب نص الرسالة هنا..."></textarea>
          <span class="form-hint">المتغيرات المتاحة: <strong>{name}</strong> لاسم العميل، و <strong>{company}</strong> لاسم الشركة.</span>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label" for="sch_target_type">المجموعة المستهدفة</label>
            <select id="sch_target_type" name="target_type" class="form-control" onchange="onTargetTypeChange(this)">
              <option value="all">كل العملاء (النشطين والموقوفين)</option>
              <option value="active">العملاء النشطين فقط</option>
              <option value="suspended">العملاء الموقوفين فقط</option>
              <option value="active_debt">العملاء النشطين الذين عليهم مديونية فقط</option>
              <option value="all_debt">كل العملاء الذين عليهم مديونية (نشط وموقوف)</option>
              <option value="active_expiring">العملاء النشطين الذين لديهم اشتراكات تنتهي قريباً</option>
              <option value="all_expiring">كل العملاء الذين لديهم اشتراكات تنتهي قريباً (نشط وموقوف)</option>
              <option value="active_expired">العملاء النشطين الذين لديهم اشتراكات منتهية بالفعل</option>
              <option value="new_clients">العملاء الجدد المسجلين خلال (آخر 30 يوم)</option>
              <option value="no_subscriptions">العملاء النشطين الذين ليس لديهم أي اشتراكات</option>
            </select>
          </div>
          
          <div class="form-group" id="warning_days_group" style="display:none;">
            <label class="form-label" for="sch_warning_days">تنبيه قبل (أيام) <span class="required">*</span></label>
            <input type="number" id="sch_warning_days" name="warning_days" class="form-control" value="30" min="1">
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label" for="sch_frequency">تكرار الإرسال</label>
            <select id="sch_frequency" name="frequency" class="form-control" onchange="onFrequencyChange(this)">
              <option value="daily">يوميًا</option>
              <option value="weekly">أسبوعيًا</option>
              <option value="monthly">شهريًا</option>
              <option value="interval">كل عدد محدد من الأيام</option>
            </select>
          </div>

          <div class="form-group" id="custom_interval_group" style="display:none;">
            <label class="form-label" for="sch_custom_interval">عدد الأيام الفاصلة <span class="required">*</span></label>
            <input type="number" id="sch_custom_interval" name="custom_interval_days" class="form-control" value="1" min="1">
          </div>

          <div class="form-group">
            <label class="form-label" for="sch_send_at_time">وقت الإرسال <span class="required">*</span></label>
            <input type="time" id="sch_send_at_time" name="send_at_time" class="form-control" value="10:00" required>
          </div>
        </div>

      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('scheduleModal')">إلغاء</button>
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> حفظ الجدولة</button>
      </div>
    </form>
  </div>
</div>

<script>
function onTargetTypeChange(sel) {
    const warningDaysGroup = document.getElementById('warning_days_group');
    if (sel.value === 'expiring' || sel.value === 'active_expiring' || sel.value === 'all_expiring') {
        warningDaysGroup.style.display = 'block';
        document.getElementById('sch_warning_days').required = true;
    } else {
        warningDaysGroup.style.display = 'none';
        document.getElementById('sch_warning_days').required = false;
    }
}

function onFrequencyChange(sel) {
    const intervalGroup = document.getElementById('custom_interval_group');
    if (sel.value === 'interval') {
        intervalGroup.style.display = 'block';
        document.getElementById('sch_custom_interval').required = true;
    } else {
        intervalGroup.style.display = 'none';
        document.getElementById('sch_custom_interval').required = false;
    }
}

function openAddScheduleModal() {
    document.getElementById('scheduleModalTitle').innerHTML = '<i class="fas fa-plus-circle" style="color:var(--primary-light);"></i> إنشاء جدولة جديدة';
    document.getElementById('scheduleId').value = '';
    document.getElementById('sch_title').value = '';
    document.getElementById('sch_message').value = '';
    document.getElementById('sch_target_type').value = 'all';
    document.getElementById('sch_warning_days').value = '30';
    document.getElementById('sch_frequency').value = 'daily';
    document.getElementById('sch_custom_interval').value = '1';
    document.getElementById('sch_send_at_time').value = '10:00';
    
    onTargetTypeChange(document.getElementById('sch_target_type'));
    onFrequencyChange(document.getElementById('sch_frequency'));
    
    openModal('scheduleModal');
}

function editSchedule(sch) {
    document.getElementById('scheduleModalTitle').innerHTML = '<i class="fas fa-edit" style="color:var(--primary-light);"></i> تعديل الجدولة';
    document.getElementById('scheduleId').value = sch.id;
    document.getElementById('sch_title').value = sch.title;
    document.getElementById('sch_message').value = sch.message;
    document.getElementById('sch_target_type').value = sch.target_type;
    document.getElementById('sch_warning_days').value = sch.warning_days || 30;
    document.getElementById('sch_frequency').value = sch.frequency;
    document.getElementById('sch_custom_interval').value = sch.custom_interval_days || 1;
    
    // Convert time HH:MM:SS to HH:MM for time input
    const timeParts = sch.send_at_time.split(':');
    document.getElementById('sch_send_at_time').value = timeParts[0] + ':' + timeParts[1];
    
    onTargetTypeChange(document.getElementById('sch_target_type'));
    onFrequencyChange(document.getElementById('sch_frequency'));
    
    openModal('scheduleModal');
}
</script>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
