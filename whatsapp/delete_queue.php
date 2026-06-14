<?php
/**
 * whatsapp/delete_queue.php - حذف رسالة مجدولة من قائمة الانتظار
 */
require_once dirname(__DIR__) . '/config/app.php';
requireLogin();
requirePermission('send_whatsapp');

$id = (int)($_GET['id'] ?? 0);
$db = getDB();

if (isset($_GET['clear_sent']) && $_GET['clear_sent'] == 1) {
    $stmt = $db->query("DELETE FROM whatsapp_queue WHERE status = 'sent'");
    setFlash('success', 'تم حذف جميع الرسائل المرسلة بنجاح.');
} elseif ($id > 0) {
    $stmt = $db->prepare("DELETE FROM whatsapp_queue WHERE id = ?");
    $stmt->execute([$id]);
    setFlash('success', 'تم حذف الرسالة من قائمة الانتظار بنجاح.');
} else {
    setFlash('error', 'معرف غير صالح.');
}

header('Location: schedules.php?tab=queue');
exit;
