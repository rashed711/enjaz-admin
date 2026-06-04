<?php
require_once dirname(__DIR__) . '/config/app.php';
requireLogin();
requirePermission('manage_services');
$db = getDB();
$id = (int)($_GET['id'] ?? 0);
if ($id) {
    $stmt = $db->prepare("SELECT name FROM services WHERE id=?");
    $stmt->execute([$id]);
    $s = $stmt->fetch();
    if ($s) { $db->prepare("DELETE FROM services WHERE id=?")->execute([$id]); setFlash('success',"تم حذف الخدمة \"{$s['name']}\"."); }
}
header('Location: index.php'); exit;
