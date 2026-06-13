<?php
/**
 * whatsapp/delete_queue.php - حذف رسالة مجدولة من قائمة الانتظار
 */
require_once dirname(__DIR__) . '/config/app.php';
requireLogin();
requirePermission('send_whatsapp');

$id = (int)($_GET['id'] ?? 0);
if ($id > 0) {
    $db = getDB();
    $stmt = $db->prepare("DELETE FROM whatsapp_queue WHERE id = ? AND status = 'pending'");
    $stmt->execute([$id]);
    setFlash('success', 'تم حذف الرسالة المجدولة من قائمة الانتظار بنجاح.');
} else {
    setFlash('error', 'معرف رسالة غير صالح.');
}

header('Location: schedules.php?tab=queue');
exit;
