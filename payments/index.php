<?php
/**
 * payments/index.php - سجل المدفوعات
 */
require_once dirname(__DIR__) . '/config/app.php';
requireLogin();
requirePermission('view_payments');

$db      = getDB();
$search  = clean($_GET['search'] ?? '');
$method  = $_GET['method'] ?? '';
$dateFrom= clean($_GET['date_from'] ?? '');
$dateTo  = clean($_GET['date_to'] ?? '');
$page    = max(1,(int)($_GET['page'] ?? 1));
$perPage = 25;

$where  = ['1=1'];
$params = [];
if ($search) { $where[] = "(c.name LIKE ? OR c.company_name LIKE ?)"; $s="%$search%"; $params=array_merge($params,[$s,$s]); }
if ($method) { $where[] = "p.payment_method = ?"; $params[] = $method; }
if ($dateFrom) { $where[] = "p.payment_date >= ?"; $params[] = $dateFrom; }
if ($dateTo)   { $where[] = "p.payment_date <= ?"; $params[] = $dateTo; }
$whereStr = implode(' AND ', $where);

$total = (int)$db->prepare("SELECT COUNT(*) FROM payments p LEFT JOIN clients c ON c.id=p.client_id WHERE $whereStr")->execute($params) ? $db->query("SELECT COUNT(*) FROM payments p LEFT JOIN clients c ON c.id=p.client_id WHERE $whereStr")->fetchColumn() : 0;
$countStmt = $db->prepare("SELECT COUNT(*) FROM payments p LEFT JOIN clients c ON c.id=p.client_id WHERE $whereStr");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();
$pager = paginate($total, $perPage, $page);

// Total amount for current filter
$sumStmt = $db->prepare("SELECT COALESCE(SUM(p.amount),0) FROM payments p LEFT JOIN clients c ON c.id=p.client_id WHERE $whereStr");
$sumStmt->execute($params);
$totalAmount = (float)$sumStmt->fetchColumn();

$stmt = $db->prepare("
    SELECT p.*, c.name as client_name, c.company_name, u.full_name as added_by,
           s.name as service_name
    FROM payments p
    LEFT JOIN clients c ON c.id=p.client_id
    LEFT JOIN users u ON u.id=p.created_by
    LEFT JOIN client_subscriptions cs ON cs.id=p.subscription_id
    LEFT JOIN services s ON s.id=cs.service_id
    WHERE $whereStr
    ORDER BY p.payment_date DESC, p.created_at DESC
    LIMIT ? OFFSET ?
");
$stmt->execute(array_merge($params,[$perPage,$pager['offset']]));
$payments = $stmt->fetchAll();

$pageTitle  = 'سجل المدفوعات';
$activePage = 'payments';
$depth      = 1;
require_once INCLUDES_PATH . '/header.php';
?>

<div class="page-header">
  <div class="page-header-text">
    <h1 class="page-title"><i class="fas fa-money-bill-wave" style="color:var(--success);margin-left:8px;"></i>سجل المدفوعات</h1>
    <p class="page-subtitle">إجمالي <?= $total ?> دفعة — مجموع: <?= formatMoney($totalAmount) ?></p>
  </div>
</div>

<!-- Filters -->
<div class="card" style="margin-bottom:16px;">
  <div class="filters-bar">
    <form method="GET" style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;width:100%;">
      <div class="search-box">
        <i class="fas fa-search search-icon"></i>
        <input type="text" name="search" class="form-control" placeholder="اسم العميل..." value="<?= e($search) ?>">
      </div>
      <select name="method" class="form-control" style="width:auto;">
        <option value="">كل طرق الدفع</option>
        <option value="cash" <?= $method==='cash'?'selected':'' ?>>كاش</option>
        <option value="transfer" <?= $method==='transfer'?'selected':'' ?>>تحويل</option>
        <option value="check" <?= $method==='check'?'selected':'' ?>>شيك</option>
      </select>
      <input type="date" name="date_from" class="form-control" style="width:auto;" value="<?= e($dateFrom) ?>" placeholder="من تاريخ">
      <input type="date" name="date_to" class="form-control" style="width:auto;" value="<?= e($dateTo) ?>" placeholder="إلى تاريخ">
      <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> بحث</button>
      <a href="index.php" class="btn btn-outline"><i class="fas fa-times"></i> مسح</a>
    </form>
  </div>
</div>

<div class="card">
  <div class="table-wrapper">
    <table class="data-table">
      <thead>
        <tr>
          <th>#</th>
          <th>التاريخ</th>
          <th>العميل</th>
          <th>المبلغ</th>
          <th>طريقة الدفع</th>
          <th>الخدمة</th>
          <th>المرجع</th>
          <th>ملاحظات</th>
          <th>أضافه</th>
          <?php if (hasPermission('delete_payments')): ?><th></th><?php endif; ?>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($payments)): ?>
        <tr><td colspan="10"><div class="empty-state"><div class="empty-icon"><i class="fas fa-coins"></i></div><p class="empty-title">لا توجد مدفوعات</p></div></td></tr>
        <?php else: ?>
        <?php foreach ($payments as $i => $pay): ?>
        <tr>
          <td class="text-muted"><?= $pager['offset']+$i+1 ?></td>
          <td><?= formatDate($pay['payment_date']) ?></td>
          <td>
            <a href="../clients/view.php?id=<?= $pay['client_id'] ?>" style="font-weight:600;color:var(--text-primary);">
              <?= e($pay['client_name']) ?>
            </a>
            <?php if ($pay['company_name']): ?>
            <div style="font-size:11.5px;color:var(--text-muted);"><?= e($pay['company_name']) ?></div>
            <?php endif; ?>
          </td>
          <td style="color:var(--success);font-weight:700;"><?= formatMoney($pay['amount']) ?></td>
          <td><?= paymentMethodLabel($pay['payment_method']) ?></td>
          <td class="text-muted"><?= e($pay['service_name'] ?: '—') ?></td>
          <td class="text-muted fs-sm"><?= e($pay['reference_number'] ?: '—') ?></td>
          <td class="text-muted fs-sm"><?= e($pay['notes'] ?: '—') ?></td>
          <td class="text-muted fs-sm"><?= e($pay['added_by'] ?? '—') ?></td>
          <?php if (hasPermission('delete_payments')): ?>
          <td>
            <a href="delete.php?id=<?= $pay['id'] ?>&client_id=<?= $pay['client_id'] ?>"
               class="btn btn-sm btn-outline-danger" data-confirm="حذف هذه الدفعة؟">
              <i class="fas fa-trash"></i>
            </a>
          </td>
          <?php endif; ?>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
      <?php if (!empty($payments)): ?>
      <tfoot>
        <tr style="background:#f8fafc;font-weight:700;">
          <td colspan="3" style="padding:12px 16px;text-align:right;">المجموع في هذه الصفحة:</td>
          <td style="padding:12px 16px;color:var(--success);font-size:15px;">
            <?= formatMoney(array_sum(array_column($payments,'amount'))) ?>
          </td>
          <td colspan="<?= hasPermission('delete_payments') ? 6 : 5 ?>"></td>
        </tr>
      </tfoot>
      <?php endif; ?>
    </table>
  </div>
</div>

<?php require_once INCLUDES_PATH . '/footer.php'; ?>
