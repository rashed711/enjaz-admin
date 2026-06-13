<?php
/**
 * whatsapp/save_queue.php - تعديل رسالة مجدولة في قائمة الانتظار
 */
require_once dirname(__DIR__) . '/config/app.php';
requireLogin();
requirePermission('send_whatsapp');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    setFlash('error', 'طريقة الطلب غير صالحة.');
    header('Location: schedules.php?tab=queue');
    exit;
}

if (!verifyCsrf()) {
    setFlash('error', 'خطأ في التحقق من الأمان.');
    header('Location: schedules.php?tab=queue');
    exit;
}

$db = getDB();

$id        = (int)($_POST['id'] ?? 0);
$message   = trim($_POST['message'] ?? '');
$send_at   = clean($_POST['send_at'] ?? '');
$min_delay = (int)($_POST['min_delay'] ?? 3);
$max_delay = (int)($_POST['max_delay'] ?? 15);

if (empty($message) || empty($send_at) || $id <= 0) {
    setFlash('error', 'جميع الحقول المطلوبة يجب ملؤها.');
    header('Location: schedules.php?tab=queue');
    exit;
}

$sendAtFormatted = date('Y-m-d H:i:s', strtotime($send_at));

try {
    $stmt = $db->prepare("
        UPDATE whatsapp_queue 
        SET message = ?, send_at = ?, min_delay = ?, max_delay = ?
        WHERE id = ? AND status = 'pending'
    ");
    $stmt->execute([$message, $sendAtFormatted, $min_delay, $max_delay, $id]);
    setFlash('success', 'تم تعديل الرسالة المجدولة بنجاح.');
} catch (Exception $e) {
    setFlash('error', 'حدث خطأ أثناء تعديل الرسالة المجدولة: ' . $e->getMessage());
}

header('Location: schedules.php?tab=queue');
exit;
