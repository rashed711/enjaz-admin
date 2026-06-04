<?php
/**
 * reports/client-statement.php - كشف حساب عميل (قابل للطباعة)
 */
require_once dirname(__DIR__) . '/config/app.php';
requireLogin();
requirePermission('print_invoices');

$db = getDB();
$id = (int)($_GET['id'] ?? 0);

$clientStmt = $db->prepare("SELECT * FROM clients WHERE id = ?");
$clientStmt->execute([$id]);
$client = $clientStmt->fetch();
if (!$client) { die('العميل غير موجود.'); }

$subsStmt = $db->prepare("SELECT cs.*, s.name as service_name FROM client_subscriptions cs JOIN services s ON s.id=cs.service_id WHERE cs.client_id=? AND cs.status!='cancelled' ORDER BY cs.created_at");
$subsStmt->execute([$id]);
$subs = $subsStmt->fetchAll();

$paysStmt = $db->prepare("SELECT * FROM payments WHERE client_id=? ORDER BY payment_date");
$paysStmt->execute([$id]);
$pays = $paysStmt->fetchAll();

$summary  = getClientSummary($id);
$companyName = getSetting('company_name', APP_NAME);
$currency    = getSetting('currency','جنيه');
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <title>كشف حساب — <?= e($client['name']) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="../assets/css/print.css">
  <style>
    body { font-family: 'Cairo',sans-serif; direction: rtl; background: #f0f4f8; color: #1e293b; }
    .statement-wrapper { max-width: 800px; margin: 20px auto; background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,.1); }
    .stmt-header { background: #1a3a5c; color: #fff; padding: 24px 28px; display: flex; justify-content: space-between; align-items: flex-start; }
    .stmt-header h1 { font-size: 20px; font-weight: 800; margin-bottom: 4px; }
    .stmt-header p { font-size: 12px; opacity: .75; }
    .stmt-body { padding: 24px 28px; }
    .client-info-row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; background: #f8fafc; border-radius: 10px; padding: 16px; margin-bottom: 24px; }
    .info-item label { display: block; font-size: 11px; color: #64748b; font-weight: 600; margin-bottom: 3px; }
    .info-item span { font-size: 14px; font-weight: 700; color: #1e293b; }
    table { width: 100%; border-collapse: collapse; font-size: 13px; margin-bottom: 20px; }
    th { background: #1a3a5c; color: #fff; padding: 10px 12px; text-align: right; font-size: 12px; }
    td { padding: 10px 12px; border-bottom: 1px solid #f1f5f9; }
    tr:nth-child(even) td { background: #f8fafc; }
    .section-title { font-size: 14px; font-weight: 800; color: #1a3a5c; margin: 20px 0 10px; padding-bottom: 8px; border-bottom: 2px solid #e2e8f0; }
    .totals-box { background: #f8fafc; border-radius: 10px; padding: 16px 20px; width: 280px; margin-right: auto; }
    .totals-row { display: flex; justify-content: space-between; padding: 7px 0; border-bottom: 1px solid #e2e8f0; font-size: 13px; }
    .totals-row:last-child { border-bottom: none; font-size: 16px; font-weight: 800; color: #1a3a5c; padding-top: 12px; }
    .text-success { color: #16a34a; font-weight: 700; }
    .text-danger { color: #dc2626; font-weight: 700; }
    .print-btn { display: block; width: fit-content; margin: 20px auto; padding: 12px 28px; background: #1a3a5c; color: #fff; border: none; border-radius: 8px; font-family: 'Cairo',sans-serif; font-size: 14px; font-weight: 700; cursor: pointer; }
    @media print { .print-btn { display: none; } body { background: #fff; } .statement-wrapper { box-shadow: none; border-radius: 0; } }
  </style>
</head>
<body>

<button class="print-btn screen-only" onclick="window.print()">
  <i class="fas fa-print"></i> طباعة كشف الحساب
</button>

<div class="statement-wrapper">

  <!-- Header -->
  <div class="stmt-header">
    <div>
      <h1><?= e($companyName) ?></h1>
      <p>كشف حساب عميل</p>
    </div>
    <div style="text-align:left;">
      <div style="font-size:13px;font-weight:700;"><?= e($client['name']) ?></div>
      <div style="font-size:11px;opacity:.75;">تاريخ الطباعة: <?= date('d/m/Y') ?></div>
    </div>
  </div>

  <div class="stmt-body">

    <!-- Client Info -->
    <div class="client-info-row">
      <div class="info-item"><label>اسم العميل</label><span><?= e($client['name']) ?></span></div>
      <div class="info-item"><label>الشركة</label><span><?= e($client['company_name'] ?: '—') ?></span></div>
      <div class="info-item"><label>الموبايل</label><span><?= e($client['mobile']) ?></span></div>
      <div class="info-item"><label>النشاط</label><span><?= e($client['activity'] ?: '—') ?></span></div>
    </div>

    <!-- Subscriptions -->
    <div class="section-title"><i class="fas fa-file-contract" style="margin-left:8px;"></i>الخدمات والاشتراكات</div>
    <?php if (empty($subs)): ?>
    <p style="color:#64748b;font-size:13px;">لا توجد خدمات مسجّلة.</p>
    <?php else: ?>
    <table>
      <thead>
        <tr><th>الخدمة</th><th>الخطة</th><th>من</th><th>إلى</th><th>السعر</th><th>الحالة</th></tr>
      </thead>
      <tbody>
        <?php foreach ($subs as $s): ?>
        <tr>
          <td><?= e($s['service_name']) ?></td>
          <td><?= e($s['plan_name'] ?: '—') ?></td>
          <td><?= formatDate($s['start_date']) ?></td>
          <td><?= formatDate($s['end_date']) ?></td>
          <td><?= formatMoney($s['price']) ?></td>
          <td><?= $s['status'] === 'active' ? '<span style="color:#16a34a;font-weight:700;">نشط</span>' : '<span style="color:#64748b;">منتهي</span>' ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>

    <!-- Payments -->
    <div class="section-title"><i class="fas fa-money-bill-wave" style="margin-left:8px;"></i>سجل المدفوعات</div>
    <?php if (empty($pays)): ?>
    <p style="color:#64748b;font-size:13px;">لا توجد مدفوعات مسجّلة.</p>
    <?php else: ?>
    <table>
      <thead>
        <tr><th>التاريخ</th><th>المبلغ</th><th>طريقة الدفع</th><th>المرجع</th><th>ملاحظات</th></tr>
      </thead>
      <tbody>
        <?php foreach ($pays as $p): ?>
        <tr>
          <td><?= formatDate($p['payment_date']) ?></td>
          <td class="text-success"><?= formatMoney($p['amount']) ?></td>
          <td><?= paymentMethodLabel($p['payment_method']) ?></td>
          <td><?= e($p['reference_number'] ?: '—') ?></td>
          <td><?= e($p['notes'] ?: '—') ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>

    <!-- Totals -->
    <div class="totals-box">
      <div class="totals-row">
        <span>إجمالي الخدمات</span>
        <span><?= formatMoney($summary['total']) ?></span>
      </div>
      <div class="totals-row">
        <span>إجمالي المدفوع</span>
        <span class="text-success"><?= formatMoney($summary['paid']) ?></span>
      </div>
      <div class="totals-row <?= $summary['remaining'] > 0 ? 'text-danger' : 'text-success' ?>">
        <span>الرصيد المتبقي</span>
        <span><?= formatMoney($summary['remaining']) ?></span>
      </div>
    </div>

    <div style="margin-top:30px;padding-top:20px;border-top:1px solid #e2e8f0;display:flex;justify-content:space-between;font-size:11.5px;color:#64748b;">
      <span><?= e($companyName) ?> — <?= date('Y') ?></span>
      <span>تم إصدار هذا الكشف بتاريخ <?= date('d/m/Y h:i A') ?></span>
    </div>

  </div>
</div>

</body>
</html>
