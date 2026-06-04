<?php
require_once dirname(__DIR__) . '/config/app.php';
requireLogin();
requirePermission('delete_payments');
$db = getDB();
$id = (int)($_GET['id'] ?? 0);
$clientId = (int)($_GET['client_id'] ?? 0);
if ($id) {
    $db->prepare("DELETE FROM payments WHERE id = ?")->execute([$id]);
    setFlash('success','تم حذف الدفعة بنجاح.');
}
header("Location: ../clients/view.php?id=$clientId"); exit;
