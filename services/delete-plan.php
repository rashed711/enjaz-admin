<?php
/**
 * services/delete-plan.php - حذف باقة
 */
require_once dirname(__DIR__) . '/config/app.php';
requireLogin();
requirePermission('manage_services');

$db   = getDB();
$id   = (int)($_GET['id']         ?? 0);

if ($id) {
    $stmt = $db->prepare("SELECT name FROM service_plans WHERE id=?");
    $stmt->execute([$id]);
    $p = $stmt->fetch();
    if ($p) {
        $db->prepare("DELETE FROM service_plans WHERE id=?")->execute([$id]);
        setFlash('success', "تم حذف الباقة «{$p['name']}» بنجاح.");
    }
}
header('Location: index.php');
exit;
