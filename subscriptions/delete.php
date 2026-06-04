<?php
/**
 * subscriptions/delete.php - حذف اشتراك
 */
require_once dirname(__DIR__) . '/config/app.php';
requireLogin();
requirePermission('edit_subscriptions');

$db = getDB();
$id = (int)($_GET['id'] ?? 0);
$clientId = (int)($_GET['client_id'] ?? 0);

if ($id > 0) {
    try {
        $db->beginTransaction();
        // إزالة الربط بالمدفوعات أولاً
        $db->prepare("UPDATE payments SET subscription_id = NULL WHERE subscription_id = ?")->execute([$id]);
        
        // حذف الاشتراك
        $stmt = $db->prepare("DELETE FROM client_subscriptions WHERE id = ?");
        $stmt->execute([$id]);
        
        $db->commit();
        setFlash('success', 'تم حذف الاشتراك بنجاح.');
    } catch (Exception $e) {
        $db->rollBack();
        setFlash('error', 'فشل في حذف الاشتراك: ' . $e->getMessage());
    }
}

if ($clientId > 0) {
    header("Location: ../clients/view.php?id=$clientId");
} else {
    header("Location: ../clients/index.php");
}
exit;
