<?php
/**
 * invoices/print.php - طباعة الفاتورة
 */
require_once dirname(__DIR__) . '/config/app.php';
requireLogin();
requirePermission('print_invoices');

$db = getDB();
$id = (int)($_GET['id'] ?? 0);
$stmt = $db->prepare("SELECT i.*, c.name as client_name, c.company_name, c.mobile, c.activity, c.address FROM invoices i JOIN clients c ON c.id=i.client_id WHERE i.id=?");
$stmt->execute([$id]);
$invoice = $stmt->fetch();
if (!$invoice) die('الفاتورة غير موجودة.');

$items       = json_decode($invoice['items'] ?? '[]', true) ?? [];
$companyName = getSetting('company_name', APP_NAME);
$currency    = getSetting('currency', 'جنيه');
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <title>فاتورة <?= e($invoice['invoice_number']) ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="../assets/css/print.css">
  <style>
    body { font-family:'Cairo',sans-serif; direction:rtl; background:#f0f4f8; margin:0; }
    .invoice-wrapper { max-width:800px; margin:20px auto; background:#fff; border-radius:12px; overflow:hidden; box-shadow:0 4px 20px rgba(0,0,0,.1); }
    .inv-header { background:#1a3a5c; color:#fff; padding:28px 32px; display:flex; justify-content:space-between; align-items:flex-start; }
    .inv-header .company-name { font-size:22px; font-weight:900; margin-bottom:4px; }
    .inv-header .company-sub  { font-size:12px; opacity:.65; }
    .inv-num  { font-size:24px; font-weight:900; text-align:left; }
    .inv-date { font-size:12px; opacity:.7; text-align:left; }
    .inv-body { padding:28px 32px; }
    .info-grid { display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-bottom:24px; }
    .info-box { padding:14px 16px; background:#f8fafc; border-radius:8px; border-right:3px solid #1a3a5c; }
    .info-box .label { font-size:10px; font-weight:700; color:#64748b; letter-spacing:1px; text-transform:uppercase; margin-bottom:6px; }
    .info-box .value { font-size:13.5px; font-weight:700; color:#1e293b; }
    .info-box .value-sm { font-size:12px; color:#64748b; margin-top:3px; }
    table.inv-table { width:100%; border-collapse:collapse; font-size:13px; margin-bottom:20px; }
    .inv-table thead tr { background:#1a3a5c; }
    .inv-table thead th { color:#fff; padding:11px 14px; text-align:right; font-size:12px; }
    .inv-table tbody tr:nth-child(even) td { background:#f8fafc; }
    .inv-table tbody td { padding:11px 14px; border-bottom:1px solid #f1f5f9; }
    .totals-wrap { display:flex; justify-content:flex-end; margin-bottom:24px; }
    .totals-box { width:260px; border:1px solid #e2e8f0; border-radius:8px; overflow:hidden; }
    .t-row { display:flex; justify-content:space-between; padding:9px 14px; border-bottom:1px solid #f1f5f9; font-size:13px; }
    .t-row:last-child { border-bottom:none; }
    .t-row.grand { background:#1a3a5c; color:#fff; font-size:15px; font-weight:800; }
    .t-row.paid  { color:#16a34a; font-weight:700; }
    .t-row.rem   { color:#dc2626; font-weight:700; }
    .inv-footer  { margin-top:24px; padding-top:16px; border-top:1px solid #e2e8f0; display:flex; justify-content:space-between; font-size:11px; color:#64748b; }
    .sig-box { text-align:center; }
    .sig-line { width:120px; height:1px; background:#1e293b; margin:40px auto 8px; }
    .print-actions { display:flex; gap:12px; justify-content:center; padding:20px; background:#f0f4f8; }
    .btn-print { padding:12px 28px; background:#1a3a5c; color:#fff; border:none; border-radius:8px; font-family:'Cairo',sans-serif; font-size:14px; font-weight:700; cursor:pointer; display:flex;align-items:center;gap:8px; }
    .btn-back  { padding:12px 28px; background:#fff; color:#1a3a5c; border:2px solid #1a3a5c; border-radius:8px; font-family:'Cairo',sans-serif; font-size:14px; font-weight:700; cursor:pointer; text-decoration:none; display:flex;align-items:center;gap:8px; }
    @media print { .print-actions { display:none!important; } body{background:#fff;} .invoice-wrapper{box-shadow:none;border-radius:0;margin:0;} }
  </style>
</head>
<body>

<div class="print-actions screen-only">
  <a href="../clients/view.php?id=<?= $invoice['client_id'] ?>" class="btn-back"><i class="fas fa-arrow-right"></i> رجوع</a>
  <button class="btn-print" onclick="window.print()"><i class="fas fa-print"></i> طباعة / PDF</button>
</div>

<div class="invoice-wrapper">

  <!-- Header -->
  <div class="inv-header">
    <div>
      <div class="company-name"><?= e($companyName) ?></div>
      <div class="company-sub">نظام إدارة العملاء</div>
      <?php $phone = getSetting('company_phone',''); if ($phone): ?>
      <div class="company-sub" style="margin-top:4px;"><i class="fas fa-phone" style="margin-left:5px;"></i><?= e($phone) ?></div>
      <?php endif; ?>
    </div>
    <div>
      <div class="inv-num">#<?= e($invoice['invoice_number']) ?></div>
      <div class="inv-date">تاريخ الإصدار: <?= formatDate($invoice['created_at']) ?></div>
    </div>
  </div>

  <div class="inv-body">

    <!-- Info Grid -->
    <div class="info-grid">
      <div class="info-box">
        <div class="label">فاتورة إلى</div>
        <div class="value"><?= e($invoice['client_name']) ?></div>
        <?php if ($invoice['company_name']): ?><div class="value-sm"><?= e($invoice['company_name']) ?></div><?php endif; ?>
        <?php if ($invoice['mobile']): ?><div class="value-sm"><i class="fas fa-phone" style="margin-left:5px;"></i><?= e($invoice['mobile']) ?></div><?php endif; ?>
        <?php if ($invoice['activity']): ?><div class="value-sm"><?= e($invoice['activity']) ?></div><?php endif; ?>
      </div>
      <div class="info-box">
        <div class="label">تفاصيل الفاتورة</div>
        <div class="value">رقم الفاتورة: <?= e($invoice['invoice_number']) ?></div>
        <div class="value-sm">تاريخ الإصدار: <?= formatDate($invoice['created_at']) ?></div>
        <div class="value-sm">
          الحالة:
          <?php if ($invoice['remaining'] <= 0): ?>
          <span style="color:#16a34a;font-weight:700;">مسدّدة بالكامل</span>
          <?php elseif ($invoice['paid_amount'] > 0): ?>
          <span style="color:#f59e0b;font-weight:700;">مدفوعة جزئياً</span>
          <?php else: ?>
          <span style="color:#dc2626;font-weight:700;">غير مسدّدة</span>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Items Table -->
    <table class="inv-table">
      <thead>
        <tr><th>#</th><th>الخدمة</th><th>الخطة</th><th>من</th><th>إلى</th><th>المبلغ</th></tr>
      </thead>
      <tbody>
        <?php foreach ($items as $i => $item): ?>
        <tr>
          <td><?= $i+1 ?></td>
          <td><strong><?= e($item['service'] ?? '') ?></strong></td>
          <td class="text-muted"><?= e($item['plan'] ?? '—') ?></td>
          <td><?= formatDate($item['start'] ?? '') ?></td>
          <td><?= formatDate($item['end'] ?? '') ?></td>
          <td style="font-weight:700;"><?= formatMoney($item['price'] ?? 0) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <!-- Totals -->
    <div class="totals-wrap">
      <div class="totals-box">
        <div class="t-row"><span>الإجمالي</span><span><?= formatMoney($invoice['total_amount']) ?></span></div>
        <div class="t-row paid"><span>المدفوع</span><span><?= formatMoney($invoice['paid_amount']) ?></span></div>
        <div class="t-row rem"><span>المتبقي</span><span><?= formatMoney($invoice['remaining']) ?></span></div>
        <div class="t-row grand"><span>الإجمالي</span><span><?= formatMoney($invoice['total_amount']) ?></span></div>
      </div>
    </div>

    <?php if ($invoice['notes']): ?>
    <div style="background:#f8fafc;border-radius:8px;padding:14px 16px;margin-bottom:20px;">
      <strong style="font-size:12px;color:#64748b;">ملاحظات:</strong>
      <p style="font-size:13px;color:#1e293b;margin-top:6px;"><?= nl2br(e($invoice['notes'])) ?></p>
    </div>
    <?php endif; ?>

    <!-- Footer -->
    <div class="inv-footer">
      <div>
        <div style="font-weight:700;color:#1e293b;"><?= e($companyName) ?></div>
        <div>شكراً لتعاملكم معنا</div>
      </div>
      <div class="sig-box">
        <div class="sig-line"></div>
        <div>التوقيع والختم</div>
      </div>
    </div>

  </div>
</div>

</body>
</html>
