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
$start_date         = clean($_POST['start_date'] ?? date('Y-m-d'));
$target_client_ids  = $_POST['target_client_ids'] ?? [];

if (is_array($target_client_ids)) {
    $target_client_ids_str = implode(',', array_filter(array_map('intval', $target_client_ids)));
} else {
    $target_client_ids_str = clean($target_client_ids);
}

if ($target_type === 'specific_clients' && empty($target_client_ids_str)) {
    setFlash('error', 'يرجى تحديد عميل واحد على الأقل للمجموعة المستهدفة.');
    header('Location: schedules.php');
    exit;
}

if (empty($title) || empty($message)) {
    setFlash('error', 'جميع الحقول المطلوبة يجب ملؤها.');
    header('Location: schedules.php');
    exit;
}

// Format time to HH:MM:00
$send_at_time = date('H:i:00', strtotime($send_at_time));

// Format start date
if (empty($start_date) || !strtotime($start_date)) {
    $start_date = date('Y-m-d');
} else {
    $start_date = date('Y-m-d', strtotime($start_date));
}

// Calculate next run date/time starting from the specified start_date
$now = new DateTime();
$nextRunStr = $start_date . ' ' . $send_at_time;
$nextRun = new DateTime($nextRunStr);

if ($nextRun < $now) {
    // إذا كان الموعد والتاريخ المحددان قد فاتوا بالفعل، يتم تقديمه حسب التكرار
    if ($frequency === 'daily') {
        $nextRun = new DateTime(date('Y-m-d') . ' ' . $send_at_time);
        if ($nextRun < $now) {
            $nextRun->modify('+1 day');
        }
    } elseif ($frequency === 'weekly') {
        while ($nextRun < $now) {
            $nextRun->modify('+7 days');
        }
    } elseif ($frequency === 'monthly') {
        while ($nextRun < $now) {
            $nextRun->modify('+1 month');
        }
    } elseif ($frequency === 'interval') {
        $step = max(1, $custom_interval);
        while ($nextRun < $now) {
            $nextRun->modify('+' . $step . ' days');
        }
    }
}

$next_run_str = $nextRun->format('Y-m-d H:i:s');

if ($id > 0) {
    // Update
    $stmt = $db->prepare("
        UPDATE whatsapp_schedules 
        SET title = ?, message = ?, target_type = ?, target_client_ids = ?, warning_days = ?, frequency = ?, custom_interval_days = ?, send_at_time = ?, next_run = ?
        WHERE id = ?
    ");
    $stmt->execute([$title, $message, $target_type, $target_client_ids_str, $warning_days, $frequency, $custom_interval, $send_at_time, $next_run_str, $id]);
    setFlash('success', 'تم تعديل الجدولة بنجاح.');
} else {
    // Insert
    $stmt = $db->prepare("
        INSERT INTO whatsapp_schedules (title, message, target_type, target_client_ids, warning_days, frequency, custom_interval_days, send_at_time, next_run, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
    ");
    $stmt->execute([$title, $message, $target_type, $target_client_ids_str, $warning_days, $frequency, $custom_interval, $send_at_time, $next_run_str]);
    setFlash('success', 'تم إنشاء الجدولة التلقائية بنجاح.');
}

header('Location: schedules.php');
exit;
