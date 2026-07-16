<?php
require_once dirname(__DIR__) . '/config/app.php';
requireLogin();
requirePermission('delete_clients');
$db = getDB();
$id = (int)($_GET['id'] ?? 0);
if ($id) {
    $stmt = $db->prepare("SELECT name FROM clients WHERE id = ?");
    $stmt->execute([$id]);
    $c = $stmt->fetch();
    if ($c) {
        // Delete related subscriptions and payments to maintain referential integrity
        $db->prepare("DELETE FROM client_subscriptions WHERE client_id = ?")->execute([$id]);
        $db->prepare("DELETE FROM payments WHERE client_id = ?")->execute([$id]);
        
        $db->prepare("DELETE FROM clients WHERE id = ?")->execute([$id]);
        setFlash('success', "تم حذف العميل \"{$c['name']}\" وكل بياناته بنجاح.");
    }
}
header('Location: index.php'); exit;
