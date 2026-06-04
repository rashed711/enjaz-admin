<?php
/**
 * payments/add.php - إضافة دفعة
 */
require_once dirname(__DIR__) . '/config/app.php';
requireLogin();
requirePermission('add_payments');

$db = getDB();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) { $errors[] = 'خطأ في الأمان.'; }
    else {
        $clientId      = (int)($_POST['client_id'] ?? 0);
        $amount        = (float)($_POST['amount'] ?? 0);
        $paymentDate   = clean($_POST['payment_date'] ?? '');
        $method        = clean($_POST['payment_method'] ?? 'cash');
        $reference     = clean($_POST['reference_number'] ?? '');
        $notes         = clean($_POST['notes'] ?? '');
        $subscriptionId= (int)($_POST['subscription_id'] ?? 0) ?: null;

        if (!$clientId)    $errors[] = 'العميل مطلوب.';
        if ($amount <= 0)  $errors[] = 'المبلغ يجب أن يكون أكبر من صفر.';
        if (!$paymentDate) $errors[] = 'تاريخ الدفع مطلوب.';

        if (empty($errors)) {
            $db->prepare("INSERT INTO payments (client_id,subscription_id,amount,payment_date,payment_method,reference_number,notes,created_by) VALUES (?,?,?,?,?,?,?,?)")
               ->execute([$clientId,$subscriptionId,$amount,$paymentDate,$method,$reference,$notes,currentUserId()]);
            setFlash('success', 'تم تسجيل الدفعة بمبلغ ' . formatMoney($amount) . ' بنجاح.');
            header("Location: ../clients/view.php?id=$clientId");
            exit;
        }
    }
}

$clientId = (int)($_POST['client_id'] ?? $_GET['client_id'] ?? 0);
if (!empty($errors)) setFlash('error', implode(' | ', $errors));
header("Location: ../clients/view.php?id=$clientId"); exit;
