<?php
/**
 * whatsapp/save_schedule.php - حفظ تعديل أو إنشاء جدولة جديدة
 */
require_once dirname(__DIR__) . '/config/app.php';
requireLogin();
requirePermission('send_whatsapp');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    setFlash('error', 'طريقة الطلب غير صالحة.');
    header('Location: schedules.php');
    exit;
}

if (!verifyCsrf()) {
    setFlash('error', 'خطأ في التحقق من الأمان.');
    header('Location: schedules.php');
    exit;
}

$db = getDB();

$id                 = (int)($_POST['id'] ?? 0);
$title              = clean($_POST['title'] ?? '');
$message            = trim($_POST['message'] ?? '');
$target_type        = clean($_POST['target_type'] ?? 'all');
$warning_days       = (int)($_POST['warning_days'] ?? 30);
$frequency          = clean($_POST['frequency'] ?? 'daily');
$custom_interval    = (int)($_POST['custom_interval_days'] ?? 1);
$send_at_time       = clean($_POST['send_at_time'] ?? '10:00');

if (empty($title) || empty($message)) {
    setFlash('error', 'جميع الحقول المطلوبة يجب ملؤها.');
    header('Location: schedules.php');
    exit;
}

// Format time to HH:MM:00
$send_at_time = date('H:i:00', strtotime($send_at_time));

// Calculate next run date/time
$now = new DateTime();
$nextRunStr = $now->format('Y-m-d') . ' ' . $send_at_time;
$nextRun = new DateTime($nextRunStr);

if ($nextRun < $now) {
    // If the time has already passed today, calculate based on frequency
    if ($frequency === 'daily') {
        $nextRun->modify('+1 day');
    } elseif ($frequency === 'weekly') {
        $nextRun->modify('+7 days');
    } elseif ($frequency === 'monthly') {
        $nextRun->modify('+1 month');
    } elseif ($frequency === 'interval') {
        $nextRun->modify('+' . $custom_interval . ' days');
    }
}

$next_run_str = $nextRun->format('Y-m-d H:i:s');

if ($id > 0) {
    // Update
    $stmt = $db->prepare("
        UPDATE whatsapp_schedules 
        SET title = ?, message = ?, target_type = ?, warning_days = ?, frequency = ?, custom_interval_days = ?, send_at_time = ?, next_run = ?
        WHERE id = ?
    ");
    $stmt->execute([$title, $message, $target_type, $warning_days, $frequency, $custom_interval, $send_at_time, $next_run_str, $id]);
    setFlash('success', 'تم تعديل الجدولة بنجاح.');
} else {
    // Insert
    $stmt = $db->prepare("
        INSERT INTO whatsapp_schedules (title, message, target_type, warning_days, frequency, custom_interval_days, send_at_time, next_run, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)
    ");
    $stmt->execute([$title, $message, $target_type, $warning_days, $frequency, $custom_interval, $send_at_time, $next_run_str]);
    setFlash('success', 'تم إنشاء الجدولة التلقائية بنجاح.');
}

header('Location: schedules.php');
exit;
