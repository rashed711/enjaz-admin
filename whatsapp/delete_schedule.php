<?php
/**
 * whatsapp/delete_schedule.php - حذف جدولة تلقائية
 */
require_once dirname(__DIR__) . '/config/app.php';
requireLogin();
requirePermission('send_whatsapp');

$id = (int)($_GET['id'] ?? 0);
if ($id > 0) {
    $db = getDB();
    $stmt = $db->prepare("DELETE FROM whatsapp_schedules WHERE id = ?");
    $stmt->execute([$id]);
    setFlash('success', 'تم حذف الجدولة التلقائية بنجاح.');
} else {
    setFlash('error', 'معرف جدولة غير صالح.');
}

header('Location: schedules.php');
exit;
