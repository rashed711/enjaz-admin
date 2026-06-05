<?php
/**
 * clients/view.php - ملف العميل الكامل
 */
require_once dirname(__DIR__) . '/config/app.php';
requireLogin();
requirePermission('view_clients');

$db = getDB();
$id = (int)($_GET['id'] ?? 0);

// جلب بيانات العميل
$clientStmt = $db->prepare("SELECT * FROM clients WHERE id = ?");
$clientStmt->execute([$id]);
$client = $clientStmt->fetch();
if (!$client) { setFlash('error', 'العميل غير موجود.'); header('Location: index.php'); exit; }

// جلب اشتراكاته النشطة والمعلّقة مع اسم الخدمة
$subsStmt = $db->prepare("
    SELECT cs.*, s.name AS service_name
    FROM client_subscriptions cs
    JOIN services s ON s.id = cs.service_id
    WHERE cs.client_id = ? AND cs.status IN ('active', 'pending')
    ORDER BY cs.created_at DESC
");
$subsStmt->execute([$id]);
$subscriptions = $subsStmt->fetchAll();

// جلب اشتراكاته السابقة (المنتهية أو الملغاة)
$pastSubsStmt = $db->prepare("
    SELECT cs.*, s.name AS service_name
    FROM client_subscriptions cs
    JOIN services s ON s.id = cs.service_id
    WHERE cs.client_id = ? AND cs.status IN ('expired', 'cancelled')
    ORDER BY cs.end_date DESC
");
$pastSubsStmt->execute([$id]);
$pastSubscriptions = $pastSubsStmt->fetchAll();

// جلب مدفوعاته
$paysStmt = $db->prepare("
    SELECT p.*, u.full_name AS added_by_name, s.name AS service_name
    FROM payments p
    LEFT JOIN users u ON u.id = p.created_by
    LEFT JOIN client_subscriptions cs ON cs.id = p.subscription_id
    LEFT JOIN services s ON s.id = cs.service_id
    WHERE p.client_id = ?
    ORDER BY p.payment_date DESC, p.created_at DESC
");
$paysStmt->execute([$id]);
$payments = $paysStmt->fetchAll();

// ملخص الحساب
$summary = getClientSummary($id);

// جلب الخدمات المتاحة (لنافذة إضافة اشتراك)
$services = $db->query("SELECT * FROM services WHERE status = 1 ORDER BY sort_order ASC, name ASC")->fetchAll();

$pageTitle  = 'ملف العميل: ' . $client['name'];
$activePage = 'clients';
$depth      = 1;
require_once INCLUDES_PATH . '/header.php';
?>

<!-- Client Header -->
<div class="client-header-card">
  <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap;position:relative;z-index:1;">
    <div>
      <div class="client-avatar-lg"><?= e(mb_substr($client['name'], 0, 1, 'UTF-8')) ?></div>
      <h1 style="font-size:22px;font-weight:900;color:#fff;margin-bottom:4px;"><?= e($client['name']) ?></h1>
      <?php if ($client['company_name']): ?>
      <p style="color:rgba(255,255,255,.75);font-size:14px;margin-bottom:4px;">
        <i class="fas fa-building" style="margin-left:6px;opacity:.7;"></i><?= e($client['company_name']) ?>
      </p>
      <?php endif; ?>
      <div style="display:flex;gap:14px;flex-wrap:wrap;margin-top:6px;">
        <a href="https://wa.me/<?= preg_replace('/\D/', '', $client['mobile']) ?>" target="_blank"
           style="color:rgba(255,255,255,.85);font-size:13.5px;text-decoration:none;display:flex;align-items:center;gap:6px;">
          <i class="fab fa-whatsapp" style="color:#25D366;font-size:16px;"></i>
          <?= e($client['mobile']) ?>
        </a>
        <?php if ($client['activity']): ?>
        <span style="color:rgba(255,255,255,.65);font-size:13px;">
          <i class="fas fa-briefcase" style="margin-left:5px;opacity:.7;"></i><?= e($client['activity']) ?>
        </span>
        <?php endif; ?>
      </div>
    </div>
    <!-- Action Buttons -->
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
      <?php if (hasPermission('edit_clients')): ?>
      <a href="edit.php?id=<?= $client['id'] ?>" class="btn btn-sm btn-outline" style="background:rgba(255,255,255,.1);border-color:rgba(255,255,255,.25);color:#fff;">
        <i class="fas fa-edit"></i> تعديل
      </a>
      <?php endif; ?>
      <?php if (hasPermission('print_invoices')): ?>
      <a href="../invoices/generate.php?client_id=<?= $client['id'] ?>" class="btn btn-sm btn-outline" style="background:rgba(255,255,255,.1);border-color:rgba(255,255,255,.25);color:#fff;">
        <i class="fas fa-file-invoice"></i> فاتورة
      </a>
      <a href="../reports/client-statement.php?id=<?= $client['id'] ?>" class="btn btn-sm btn-outline" style="background:rgba(255,255,255,.1);border-color:rgba(255,255,255,.25);color:#fff;" target="_blank">
        <i class="fas fa-print"></i> كشف حساب
      </a>
      <?php endif; ?>
      <?php if (hasPermission('send_whatsapp')): ?>
      <button onclick="openModal('waModal')" class="btn btn-sm btn-accent">
        <i class="fab fa-whatsapp"></i> إرسال واتساب
      </button>
      <?php endif; ?>
    </div>
  </div>

  <!-- Summary Boxes -->
  <div class="summary-boxes" style="position:relative;z-index:1;">
    <div class="summary-box">
      <span class="box-value"><?= formatMoney($summary['total']) ?></span>
      <span class="box-label">إجمالي الخدمات</span>
    </div>
    <div class="summary-box box-success">
      <span class="box-value"><?= formatMoney($summary['paid']) ?></span>
      <span class="box-label">إجمالي المدفوع</span>
    </div>
    <div class="summary-box <?= $summary['remaining'] > 0 ? 'box-danger' : 'box-success' ?>">
      <span class="box-value"><?= formatMoney($summary['remaining']) ?></span>
      <span class="box-label"><?= $summary['remaining'] > 0 ? 'المبلغ المتبقي' : '✓ مسدّد بالكامل' ?></span>
    </div>
  </div>
</div>

<div style="display:grid;grid-template-columns:2fr 1fr;gap:20px;align-items:start;">

  <!-- Right Column -->
  <div>

    <!-- Subscriptions -->
    <div class="card mb-2" style="margin-bottom:20px;">
      <div class="card-header">
        <span class="card-title"><i class="fas fa-file-contract"></i> الخدمات والاشتراكات</span>
        <div style="display:flex;gap:8px;">
          <span class="badge badge-primary"><?= count($subscriptions) ?> خدمة</span>
          <?php if (hasPermission('add_subscriptions')): ?>
          <button onclick="openModal('addSubModal')" class="btn btn-sm btn-primary" id="btn-add-service">
            <i class="fas fa-plus"></i> إضافة خدمة
          </button>
          <?php endif; ?>
        </div>
      </div>

      <?php if (empty($subscriptions)): ?>
      <div class="empty-state" style="padding:40px;">
        <div class="empty-icon" style="font-size:40px;"><i class="fas fa-cube"></i></div>
        <p class="empty-title">لا توجد خدمات</p>
        <p class="empty-text">لم يشترك هذا العميل في أي خدمة بعد.</p>
        <?php if (hasPermission('add_subscriptions')): ?>
        <button onclick="openModal('addSubModal')" class="btn btn-primary"><i class="fas fa-plus"></i> إضافة خدمة</button>
        <?php endif; ?>
      </div>
      <?php else: ?>
      <div class="table-wrapper">
        <table class="data-table">
          <thead>
            <tr>
              <th>الخدمة</th>
              <th>الخطة</th>
              <th>البداية</th>
              <th>النهاية</th>
              <th>السعر</th>
              <th>الحالة</th>
              <th>ملاحظات</th>
              <?php if (hasPermission('edit_subscriptions')): ?><th>إجراء</th><?php endif; ?>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($subscriptions as $sub): ?>
            <tr>
              <td>
                <strong><?= e($sub['service_name']) ?></strong>
              </td>
              <td class="text-muted"><?= e($sub['plan_name'] ?: '—') ?></td>
              <td><?= formatDate($sub['start_date']) ?></td>
              <td><?= formatDate($sub['end_date']) ?></td>
              <td class="fw-bold"><?= formatMoney($sub['price']) ?></td>
              <td><?= subscriptionStatusBadge($sub['status'], $sub['end_date']) ?></td>
              <td class="text-muted fs-sm"><?= e($sub['notes'] ?: '—') ?></td>
              <?php if (hasPermission('edit_subscriptions')): ?>
              <td>
                <div class="table-actions">
                  <a href="../subscriptions/edit.php?id=<?= $sub['id'] ?>" class="btn btn-sm btn-outline" title="تعديل">
                    <i class="fas fa-edit"></i>
                  </a>
                  <a href="../subscriptions/delete.php?id=<?= $sub['id'] ?>&client_id=<?= $client['id'] ?>"
                     class="btn btn-sm btn-outline-danger"
                     data-confirm="هل تريد حذف هذا الاشتراك؟ (سيتم إزالة ربطه بالمدفوعات دون حذف المبالغ)"
                     title="حذف">
                    <i class="fas fa-trash"></i>
                  </a>
                  <?php if ($sub['status'] === 'expired' || $sub['status'] === 'cancelled'): ?>
                  <a href="../subscriptions/renew.php?id=<?= $sub['id'] ?>" class="btn btn-sm btn-success" title="تجديد">
                    <i class="fas fa-redo"></i>
                  </a>
                  <?php endif; ?>
                </div>
              </td>
              <?php endif; ?>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
      
      <?php if (!empty($pastSubscriptions)): ?>
      <div style="padding:12px 16px;border-top:1px dashed var(--border-color);background:#fafbfc;" x-data="{ open: false }">
        <button type="button" @click="open = !open" class="btn btn-sm btn-outline" style="border:none;background:transparent;padding:0;color:var(--text-secondary);font-size:12.5px;cursor:pointer;">
          <i class="fas" :class="open ? 'fa-chevron-up' : 'fa-chevron-down'" style="margin-left:6px;"></i>
          عرض الاشتراكات السابقة المنتهية (<?= count($pastSubscriptions) ?>)
        </button>
        <div x-show="open" style="margin-top:12px;overflow-x:auto; display:none;" :style="open ? 'display:block;' : 'display:none;'">
          <table class="data-table" style="font-size:12px;opacity:0.85;width:100%;">
            <thead>
              <tr style="background:#f1f5f9;">
                <th>الخدمة</th>
                <th>الخطة</th>
                <th>البداية</th>
                <th>النهاية</th>
                <th>السعر</th>
                <th>الحالة</th>
                <th>ملاحظات</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($pastSubscriptions as $ps): ?>
              <tr>
                <td><strong><?= e($ps['service_name']) ?></strong></td>
                <td class="text-muted"><?= e($ps['plan_name'] ?: '—') ?></td>
                <td><?= formatDate($ps['start_date']) ?></td>
                <td><?= formatDate($ps['end_date']) ?></td>
                <td><?= formatMoney($ps['price']) ?></td>
                <td><?= subscriptionStatusBadge($ps['status'], $ps['end_date']) ?></td>
                <td class="text-muted fs-sm"><?= e($ps['notes'] ?: '—') ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
      <?php endif; ?>
    </div>

    <!-- Payments -->
    <div class="card">
      <div class="card-header">
        <span class="card-title"><i class="fas fa-money-bill-wave"></i> سجل المدفوعات</span>
        <div style="display:flex;gap:8px;">
          <span class="badge badge-success"><?= count($payments) ?> دفعة</span>
          <?php if (hasPermission('add_payments')): ?>
          <button onclick="openModal('addPayModal')" class="btn btn-sm btn-success" id="btn-add-payment">
            <i class="fas fa-plus"></i> إضافة دفعة
          </button>
          <?php endif; ?>
        </div>
      </div>

      <?php if (empty($payments)): ?>
      <div class="empty-state" style="padding:40px;">
        <div class="empty-icon" style="font-size:40px;"><i class="fas fa-coins"></i></div>
        <p class="empty-title">لا توجد مدفوعات</p>
        <p class="empty-text">لم يتم تسجيل أي مدفوعات لهذا العميل.</p>
      </div>
      <?php else: ?>
      <div class="table-wrapper">
        <table class="data-table">
          <thead>
            <tr>
              <th>التاريخ</th>
              <th>المبلغ</th>
              <th>طريقة الدفع</th>
              <th>الخدمة المرتبطة</th>
              <th>رقم المرجع</th>
              <th>الإيصال</th>
              <th>ملاحظات</th>
              <th>أضافه</th>
              <?php if (hasPermission('add_payments') || hasPermission('delete_payments')): ?><th>إجراء</th><?php endif; ?>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($payments as $pay): ?>
            <tr>
              <td><?= formatDate($pay['payment_date']) ?></td>
              <td style="color:var(--success);font-weight:700;"><?= formatMoney($pay['amount']) ?></td>
              <td><?= paymentMethodLabel($pay['payment_method']) ?></td>
              <td class="text-muted"><?= e($pay['service_name'] ?: '—') ?></td>
              <td class="text-muted fs-sm"><?= e($pay['reference_number'] ?: '—') ?></td>
              <td>
                <?php if (!empty($pay['receipt_file'])): ?>
                  <a href="../<?= e($pay['receipt_file']) ?>" target="_blank" class="btn btn-sm btn-outline-info" title="عرض الإيصال" style="padding: 2px 6px; font-size: 11.5px;">
                    <i class="fas fa-file-invoice"></i> عرض
                  </a>
                <?php else: ?>
                  <span class="text-muted">—</span>
                <?php endif; ?>
              </td>
              <td class="text-muted fs-sm"><?= e($pay['notes'] ?: '—') ?></td>
              <td class="text-muted fs-sm"><?= e($pay['added_by_name'] ?? '—') ?></td>
              <?php if (hasPermission('add_payments') || hasPermission('delete_payments')): ?>
              <td>
                <div class="table-actions">
                  <?php if (hasPermission('add_payments')): ?>
                  <a href="../payments/edit.php?id=<?= $pay['id'] ?>" class="btn btn-sm btn-outline" title="تعديل">
                    <i class="fas fa-edit"></i>
                  </a>
                  <?php endif; ?>
                  <?php if (hasPermission('delete_payments')): ?>
                  <a href="../payments/delete.php?id=<?= $pay['id'] ?>&client_id=<?= $client['id'] ?>"
                     class="btn btn-sm btn-outline-danger"
                     data-confirm="هل تريد حذف هذه الدفعة؟"
                     title="حذف">
                    <i class="fas fa-trash"></i>
                  </a>
                  <?php endif; ?>
                </div>
              </td>
              <?php endif; ?>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </div>

  </div>

  <!-- Left Column — Client Info -->
  <div>
    <div class="card">
      <div class="card-header">
        <span class="card-title"><i class="fas fa-info-circle"></i> بيانات العميل</span>
      </div>
      <div class="card-body">
        <div style="display:flex;flex-direction:column;gap:14px;">

          <?php
          $infoItems = [
            ['fas fa-user',        'الاسم',             $client['name']],
            ['fas fa-building',    'الشركة',            $client['company_name']],
            ['fas fa-phone',       'الموبايل',          $client['mobile']],
            ['fas fa-phone',       'موبايل إضافي',      $client['mobile_2']],
            ['fas fa-briefcase',   'النشاط',            $client['activity']],
            ['fas fa-at',          'اسم المستخدم',       $client['username_note']],
            ['fas fa-globe',       'نطاق الموقع (الدومين)', $client['domain']],
            ['fas fa-server',      'مزود خدمة الدومين',  $client['domain_provider']],
            ['fas fa-envelope',    'البريد',             $client['email']],
            ['fas fa-map-marker',  'العنوان',            $client['address']],
          ];
          foreach ($infoItems as [$icon, $label, $value]):
            if (empty($value)) continue;
          ?>
          <div style="display:flex;gap:10px;align-items:flex-start;">
            <div style="width:32px;height:32px;background:rgba(36,86,164,.08);border-radius:8px;
                        display:flex;align-items:center;justify-content:center;color:var(--primary-light);
                        font-size:13px;flex-shrink:0;margin-top:1px;">
              <i class="<?= $icon ?>"></i>
            </div>
            <div>
              <div style="font-size:11px;color:var(--text-muted);font-weight:600;"><?= $label ?></div>
              <div style="font-size:13.5px;color:var(--text-primary);font-weight:600;">
                <?php if ($label === 'نطاق الموقع (الدومين)'): ?>
                  <a href="http://<?= e($value) ?>" target="_blank" style="color:var(--primary-light);text-decoration:underline;">
                    <?= e($value) ?> <i class="fas fa-external-link-alt fa-xs" style="margin-right:3px;"></i>
                  </a>
                <?php else: ?>
                  <?= e($value) ?>
                <?php endif; ?>
              </div>
            </div>
          </div>
          <?php endforeach; ?>

          <?php if ($client['notes']): ?>
          <div style="background:#fafbfc;border-radius:8px;padding:12px;border:1px solid var(--border-color);">
            <div style="font-size:11px;color:var(--text-muted);font-weight:600;margin-bottom:6px;">
              <i class="fas fa-sticky-note" style="margin-left:4px;"></i>ملاحظات
            </div>
            <div style="font-size:13px;color:var(--text-secondary);line-height:1.6;"><?= nl2br(e($client['notes'])) ?></div>
          </div>
          <?php endif; ?>

          <div style="padding-top:8px;border-top:1px solid var(--border-color);">
            <div style="font-size:11px;color:var(--text-muted);">تاريخ التسجيل: <?= formatDate($client['created_at']) ?></div>
            <div style="font-size:11px;color:var(--text-muted);margin-top:3px;">
              الحالة: <?= $client['status'] ? '<span class="badge badge-success">نشط</span>' : '<span class="badge badge-danger">موقوف</span>' ?>
            </div>
          </div>

        </div>
      </div>
    </div>
  </div>

</div>


<!-- ══ Modal: إضافة خدمة ══════════════════════════════════════ -->
<?php if (hasPermission('add_subscriptions')): ?>
<div class="modal-overlay" id="addSubModal" style="display:none;">
  <div class="modal modal-lg">
    <div class="modal-header">
      <span class="modal-title"><i class="fas fa-plus-circle" style="color:var(--primary-light);"></i> إضافة خدمة للعميل</span>
      <button class="modal-close" onclick="closeModal('addSubModal')"><i class="fas fa-times"></i></button>
    </div>
    <form method="POST" action="../subscriptions/add.php" data-validate>
      <?= csrfField() ?>
      <input type="hidden" name="client_id" value="<?= $client['id'] ?>">
      <div class="modal-body">

        <!-- اختيار الخدمة -->
        <div class="form-group">
          <label class="form-label" for="sub_service_id">الخدمة <span class="required">*</span></label>
          <select id="sub_service_id" name="service_id" class="form-control" required
                  onchange="onServiceChange(this)">
            <option value="">— اختر الخدمة —</option>
            <?php foreach ($services as $srv): ?>
            <option value="<?= $srv['id'] ?>"
                    data-price="<?= $srv['default_price'] ?>"
                    data-duration="<?= $srv['duration_months'] ?>">
              <?= e($srv['name']) ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- الباقات — تظهر ديناميكياً بعد اختيار الخدمة -->
        <div id="plansSection" style="display:none;margin-bottom:14px;">
          <label class="form-label">اختر الباقة <span class="required">*</span></label>
          <div id="plansGrid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:10px;"></div>
          <input type="hidden" id="selectedPlanId" value="">
        </div>

        <!-- سعر مخصص (يظهر لو الخدمة بدون باقات أو اختار "سعر آخر") -->
        <div id="customPriceSection">
          <div class="form-row">
            <div class="form-group">
              <label class="form-label" for="plan_name" id="plan_name_label">اسم الباقة / الخطة</label>
              <input type="text" id="plan_name" name="plan_name" class="form-control"
                     placeholder="مثال: باقة 5 جيجا، أساسية...">
            </div>
            <div class="form-group">
              <label class="form-label" for="price">السعر <span class="required">*</span></label>
              <input type="number" id="price" name="price" class="form-control"
                     step="0.01" min="0" placeholder="0.00" required>
            </div>
          </div>
        </div>

        <!-- التواريخ -->
        <div class="form-row" style="margin-top:4px;">
          <div class="form-group">
            <label class="form-label" for="startDate">تاريخ الاشتراك <span class="required">*</span></label>
            <input type="date" id="startDate" name="start_date" class="form-control"
                   value="<?= date('Y-m-d') ?>" required
                   onchange="recalcEndDate()">
          </div>
          <div class="form-group">
            <label class="form-label" for="durationMonths">المدة</label>
            <select id="durationMonths" class="form-control" onchange="recalcEndDate()">
              <option value="0">بدون تاريخ انتهاء</option>
              <option value="1">شهر واحد</option>
              <option value="3">3 أشهر</option>
              <option value="6">6 أشهر</option>
              <option value="12" selected>سنة كاملة (12 شهر)</option>
              <option value="24">سنتان</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label" for="endDate">تاريخ الانتهاء <span class="required">*</span></label>
            <input type="date" id="endDate" name="end_date" class="form-control" required>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label" for="sub_notes">ملاحظات</label>
          <textarea id="sub_notes" name="notes" class="form-control" rows="2"
                    placeholder="أي ملاحظات خاصة بهذه الخدمة..."></textarea>
        </div>

      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('addSubModal')">إلغاء</button>
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> حفظ الخدمة</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>


<!-- ══ Modal: إضافة دفعة ══════════════════════════════════════ -->
<?php if (hasPermission('add_payments')): ?>
<div class="modal-overlay" id="addPayModal" style="display:none;">
  <div class="modal">
    <div class="modal-header">
      <span class="modal-title"><i class="fas fa-money-bill-wave" style="color:var(--success);"></i> إضافة دفعة جديدة</span>
      <button class="modal-close" onclick="closeModal('addPayModal')"><i class="fas fa-times"></i></button>
    </div>
    <form method="POST" action="../payments/add.php" enctype="multipart/form-data" data-validate>
      <?= csrfField() ?>
      <input type="hidden" name="client_id" value="<?= $client['id'] ?>">
      <div class="modal-body">

        <div style="background:var(--warning-light);border-right:4px solid var(--warning);border-radius:8px;padding:12px 14px;margin-bottom:18px;">
          <div style="font-size:12.5px;color:#92400e;font-weight:700;">ملخص الحساب:</div>
          <div style="font-size:13px;color:#92400e;margin-top:4px;">
            الإجمالي: <strong><?= formatMoney($summary['total']) ?></strong> |
            المدفوع: <strong><?= formatMoney($summary['paid']) ?></strong> |
            المتبقي: <strong><?= formatMoney($summary['remaining']) ?></strong>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label" for="pay_amount">المبلغ <span class="required">*</span></label>
            <input type="number" id="pay_amount" name="amount" class="form-control"
                   step="0.01" min="0.01"
                   placeholder="<?= $summary['remaining'] > 0 ? number_format($summary['remaining'], 2) : '0.00' ?>"
                   required>
          </div>
          <div class="form-group">
            <label class="form-label" for="pay_date">تاريخ الدفع <span class="required">*</span></label>
            <input type="date" id="pay_date" name="payment_date" class="form-control"
                   value="<?= date('Y-m-d') ?>" required>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label" for="pay_method">طريقة الدفع</label>
            <?php 
            $payMethods = explode(',', getSetting('payment_methods', 'كاش,تحويل بنكي,فودافون كاش,شيك,أخرى')); 
            ?>
            <select id="pay_method" name="payment_method" class="form-control">
              <?php foreach ($payMethods as $pm): $pm = trim($pm); ?>
              <option value="<?= e($pm) ?>"><?= e($pm) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label" for="pay_ref">رقم الإيصال / المرجع</label>
            <input type="text" id="pay_ref" name="reference_number" class="form-control" placeholder="اختياري">
          </div>
        </div>

        <?php if (!empty($subscriptions)): ?>
        <div class="form-group">
          <label class="form-label" for="pay_sub">ربط بخدمة (اختياري)</label>
          <select id="pay_sub" name="subscription_id" class="form-control">
            <option value="">— غير مرتبط بخدمة محددة —</option>
            <?php foreach ($subscriptions as $sub): ?>
            <option value="<?= $sub['id'] ?>"><?= e($sub['service_name']) ?> (<?= formatDate($sub['start_date']) ?> - <?= formatDate($sub['end_date']) ?>)</option>
            <?php endforeach; ?>
          </select>
        </div>
        <?php endif; ?>

        <div class="form-group">
          <label class="form-label" for="pay_notes">ملاحظات</label>
          <textarea id="pay_notes" name="notes" class="form-control" rows="2" placeholder="ملاحظات الدفعة..."></textarea>
        </div>

        <div class="form-group">
          <label class="form-label" for="receipt_file">إرفاق صورة الإيصال أو ملف PDF</label>
          <input type="file" id="receipt_file" name="receipt_file" class="form-control" accept="image/*,.pdf">
        </div>

      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('addPayModal')">إلغاء</button>
        <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> تسجيل الدفعة</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>


<!-- ══ Modal: إرسال واتساب ════════════════════════════════════ -->
<?php if (hasPermission('send_whatsapp')): ?>
<div class="modal-overlay" id="waModal" style="display:none;">
  <div class="modal">
    <div class="modal-header">
      <span class="modal-title"><i class="fab fa-whatsapp" style="color:#25D366;"></i> إرسال رسالة واتساب</span>
      <button class="modal-close" onclick="closeModal('waModal')"><i class="fas fa-times"></i></button>
    </div>
    <form method="POST" action="../api/whatsapp.php" data-validate id="waForm">
      <?= csrfField() ?>
      <input type="hidden" name="client_id" value="<?= $client['id'] ?>">
      <input type="hidden" name="mobile" value="<?= e($client['mobile']) ?>">
      <div class="modal-body">

        <div class="form-group">
          <label class="form-label">نوع الرسالة</label>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
            <?php
            $waTemplates = [
              'renewal'  => ['icon'=>'fa-calendar-exclamation','label'=>'تنبيه تجديد','color'=>'var(--warning)'],
              'payment'  => ['icon'=>'fa-check-circle',        'label'=>'تأكيد دفعة', 'color'=>'var(--success)'],
              'statement'=> ['icon'=>'fa-file-alt',            'label'=>'كشف حساب',   'color'=>'var(--info)'],
              'custom'   => ['icon'=>'fa-pen',                 'label'=>'رسالة مخصصة','color'=>'var(--primary-light)'],
            ];
            foreach ($waTemplates as $type => $t):
            ?>
            <label style="display:flex;align-items:center;gap:8px;padding:10px 12px;border:1.5px solid var(--border-color);
                          border-radius:8px;cursor:pointer;font-size:13px;font-weight:600;transition:all .2s;"
                   onmouseover="this.style.borderColor='<?= $t['color'] ?>';this.style.background='rgba(0,0,0,.02)'"
                   onmouseout="this.style.borderColor='var(--border-color)';this.style.background=''">
              <input type="radio" name="msg_type" value="<?= $type ?>"
                     style="accent-color:<?= $t['color'] ?>;"
                     <?= $type === 'custom' ? 'checked' : '' ?>
                     onchange="onWaTypeChange('<?= $type ?>')">
              <i class="fas <?= $t['icon'] ?>" style="color:<?= $t['color'] ?>;font-size:15px;"></i>
              <?= $t['label'] ?>
            </label>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="form-group" id="waCustomGroup">
          <label class="form-label" for="waMessage">نص الرسالة <span class="required">*</span></label>
          <textarea id="waMessage" name="message" class="form-control" rows="4"
                    placeholder="اكتب رسالتك هنا..." required></textarea>
          <span class="form-hint">سيتم إرسالها لـ: <?= e($client['mobile']) ?></span>
        </div>

      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('waModal')">إلغاء</button>
        <button type="submit" class="btn btn-success" id="waSendBtn">
          <i class="fab fa-whatsapp"></i> إرسال
        </button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<script>
// ══ إدارة الباقات ════════════════════════════════════════════
let currentPlans = [];

async function onServiceChange(sel) {
  const serviceId = sel.value;
  const opt       = sel.options[sel.selectedIndex];

  // reset
  document.getElementById('plansSection').style.display = 'none';
  document.getElementById('plansGrid').innerHTML = '';
  document.getElementById('selectedPlanId').value = '';
  document.getElementById('plan_name').value      = '';
  document.getElementById('price').value          = '';
  currentPlans = [];

  // تغيير مسمى الحقل وتلميحه بناءً على اختيار خدمة الدومين
  const serviceText = opt ? opt.text : '';
  const isDomain = serviceText.includes('دومين') || serviceText.toLowerCase().includes('domain');
  const planLabel = document.getElementById('plan_name_label');
  const planInput = document.getElementById('plan_name');
  if (isDomain) {
      if (planLabel) planLabel.innerHTML = 'اسم الدومين المحجوز <span class="required">*</span>';
      if (planInput) {
          planInput.placeholder = 'example.com';
          planInput.required = true;
      }
  } else {
      if (planLabel) planLabel.innerHTML = 'اسم الباقة / الخطة';
      if (planInput) {
          planInput.placeholder = 'مثال: باقة 5 جيجا، أساسية...';
          planInput.required = false;
      }
  }

  // المدة الافتراضية للخدمة
  const defaultDur = parseInt(opt.dataset.duration) || 12;
  const durSel = document.getElementById('durationMonths');
  durSel.value = [0,1,3,6,12,24].includes(defaultDur) ? defaultDur : 12;
  recalcEndDate();

  if (!serviceId) return;

  // جلب الباقات
  try {
    const res   = await fetch('../api/service-plans.php?service_id=' + serviceId);
    const data  = await res.json();
    currentPlans = data.plans || [];
  } catch(e) { currentPlans = []; }

  if (currentPlans.length > 0) {
    document.getElementById('plansSection').style.display = 'block';
    renderPlans(currentPlans);
    // اختر أول باقة تلقائياً
    selectPlan(currentPlans[0]);
  } else {
    // بدون باقات — استخدم السعر الافتراضي
    document.getElementById('price').value = opt.dataset.price || '';
  }
}

function onSubProviderChange(sel) {
  const customInput = document.getElementById('sub_domain_provider');
  if (sel.value === 'custom') {
    customInput.style.display = 'block';
    customInput.required = true;
    customInput.value = '';
  } else {
    customInput.style.display = 'none';
    customInput.required = false;
    customInput.value = sel.value;
  }
}

function renderPlans(plans) {
  const grid = document.getElementById('plansGrid');
  grid.innerHTML = '';
  // كارت "سعر آخر"
  plans.forEach(p => {
    const card = document.createElement('div');
    card.className = 'plan-select-card';
    card.dataset.planId    = p.id;
    card.dataset.planName  = p.name;
    card.dataset.planPrice = p.price;
    card.innerHTML = `
      <div class="plan-card-name">${p.name}</div>
      ${p.description ? '<div class="plan-card-desc">' + p.description + '</div>' : ''}
      <div class="plan-card-price">${parseFloat(p.price).toLocaleString('en-US', {minimumFractionDigits:2})} <?= getSetting('currency','جنيه') ?></div>
    `;
    card.addEventListener('click', () => selectPlan(p));
    grid.appendChild(card);
  });
  // كارت "سعر مخصص"
  const customCard = document.createElement('div');
  customCard.className = 'plan-select-card';
  customCard.dataset.planId    = 'custom';
  customCard.dataset.planName  = '';
  customCard.dataset.planPrice = '';
  customCard.innerHTML = `
    <div class="plan-card-name"><i class="fas fa-pen" style="margin-left:5px;"></i>سعر آخر</div>
    <div class="plan-card-desc">أدخل السعر يدوياً</div>
    <div class="plan-card-price" style="color:var(--text-muted);">—</div>
  `;
  customCard.addEventListener('click', () => selectCustomPlan());
  grid.appendChild(customCard);
}

function selectPlan(plan) {
  // تحديث active
  document.querySelectorAll('.plan-select-card').forEach(c => c.classList.remove('active'));
  const card = document.querySelector(`.plan-select-card[data-plan-id="${plan.id}"]`);
  if (card) card.classList.add('active');

  document.getElementById('selectedPlanId').value = plan.id;
  document.getElementById('plan_name').value      = plan.name;
  document.getElementById('price').value          = plan.price;
}

function selectCustomPlan() {
  document.querySelectorAll('.plan-select-card').forEach(c => c.classList.remove('active'));
  const card = document.querySelector('.plan-select-card[data-plan-id="custom"]');
  if (card) card.classList.add('active');
  document.getElementById('selectedPlanId').value = '';
  document.getElementById('plan_name').value      = '';
  document.getElementById('price').value          = '';
  document.getElementById('price').focus();
}

// ══ حساب تاريخ النهاية ═══════════════════════════════════════
function recalcEndDate() {
  const startInput = document.getElementById('startDate');
  const endInput   = document.getElementById('endDate');
  const durationEl = document.getElementById('durationMonths');
  const startVal   = startInput.value;
  const months     = parseInt(durationEl.value) || 0;

  const startReq = startInput.previousElementSibling.querySelector('.required');
  const endReq   = endInput.previousElementSibling.querySelector('.required');

  if (months === 0) {
    // الخدمة بدون تاريخ انتهاء - التواريخ غير مطلوبة
    startInput.removeAttribute('required');
    endInput.removeAttribute('required');
    if (startReq) startReq.style.display = 'none';
    if (endReq)   endReq.style.display = 'none';
    endInput.value = '';
  } else {
    // الخدمة بمدة محددة - التواريخ مطلوبة
    startInput.setAttribute('required', 'required');
    endInput.setAttribute('required', 'required');
    if (startReq) startReq.style.display = 'inline';
    if (endReq)   endReq.style.display = 'inline';

    if (!startVal) return;

    const start  = new Date(startVal);
    // أضف الشهور
    start.setMonth(start.getMonth() + months);
    // اطرح يوم واحد (ينتهي قبل نفس اليوم من السنة القادمة)
    start.setDate(start.getDate() - 1);

    const yyyy = start.getFullYear();
    const mm   = String(start.getMonth() + 1).padStart(2, '0');
    const dd   = String(start.getDate()).padStart(2, '0');
    const targetDate = `${yyyy}-${mm}-${dd}`;
    if (endInput._flatpickr) {
      endInput._flatpickr.setDate(targetDate);
    } else {
      endInput.value = targetDate;
    }
  }
}

// احسب النهاية عند فتح الـ modal تلقائياً
document.addEventListener('DOMContentLoaded', function() {
  recalcEndDate(); // يحسب بناءً على تاريخ اليوم + 12 شهر
});

// WhatsApp templates
const waTemplates = {
  renewal:   `السادة / <?= e($client['name']) ?>\n\nنود إعلامكم بأن اشتراككم لدى إنجاز للحلول الذكية ينتهي قريباً.\nيرجى التواصل معنا لتجديد الاشتراك.\n\nشكراً لثقتكم 🌟`,
  payment:   `السادة / <?= e($client['name']) ?>\n\nنشكركم على سداد دفعتكم.\nنتطلع دائماً لخدمتكم.\n\nإنجاز للحلول الذكية ✅`,
  statement: `السادة / <?= e($client['name']) ?>\n\nإجمالي خدماتكم: <?= formatMoney($summary['total']) ?>\nالمسدّد: <?= formatMoney($summary['paid']) ?>\nالمتبقي: <?= formatMoney($summary['remaining']) ?>\n\nللاستفسار تواصلوا معنا.`,
  custom:    '',
};

function onWaTypeChange(type) {
  document.getElementById('waMessage').value = waTemplates[type] || '';
}

// WhatsApp form AJAX
document.getElementById('waForm')?.addEventListener('submit', async function(e) {
  e.preventDefault();
  const btn = document.getElementById('waSendBtn');
  btn.disabled = true;
  btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> جاري الإرسال...';
  const result = await apiPost('../api/whatsapp.php', Object.fromEntries(new FormData(this)));
  btn.disabled = false;
  btn.innerHTML = '<i class="fab fa-whatsapp"></i> إرسال';
  closeModal('waModal');
  showToast(result.success ? 'تم إرسال الرسالة بنجاح ✓' : (result.message || 'فشل الإرسال'), result.success ? 'success' : 'error');
});
</script>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
