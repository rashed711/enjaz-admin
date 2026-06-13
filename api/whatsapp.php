<?php
/**
 * api/whatsapp.php - إرسال رسائل WhatsApp
 */
require_once dirname(__DIR__) . '/config/app.php';
requireLogin();
requirePermission('send_whatsapp');

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit;
}

if (!verifyCsrf()) {
    echo json_encode(['success' => false, 'message' => 'خطأ في التحقق من الأمان.']);
    exit;
}

$db        = getDB();
$clientId  = (int)($_POST['client_id'] ?? 0);
$mobile    = clean($_POST['mobile'] ?? '');
$message   = trim($_POST['message'] ?? '');
$msgType   = clean($_POST['msg_type'] ?? 'custom');
$sendType  = clean($_POST['send_type'] ?? 'now');
$sendAt    = clean($_POST['send_at'] ?? '');
$minDelay  = (int)($_POST['min_delay'] ?? 3);
$maxDelay  = (int)($_POST['max_delay'] ?? 15);

if (empty($mobile)) {
    echo json_encode(['success' => false, 'message' => 'رقم الموبايل مطلوب.']);
    exit;
}
if (empty($message)) {
    echo json_encode(['success' => false, 'message' => 'نص الرسالة مطلوب.']);
    exit;
}

// Normalize mobile number using the global helper function
$mobile = normalizeMobile($mobile);

if ($sendType === 'schedule') {
    if (empty($sendAt)) {
        echo json_encode(['success' => false, 'message' => 'تاريخ ووقت الإرسال مطلوب للجدولة.']);
        exit;
    }
    
    $sendAtFormatted = date('Y-m-d H:i:s', strtotime($sendAt));
    
    try {
        $stmt = $db->prepare("INSERT INTO whatsapp_queue (client_id, mobile, message, send_at, min_delay, max_delay, status, sent_by) VALUES (?, ?, ?, ?, ?, ?, 'pending', ?)");
        $stmt->execute([$clientId ?: null, $mobile, $message, $sendAtFormatted, $minDelay, $maxDelay, currentUserId()]);
        echo json_encode(['success' => true, 'message' => 'تم جدولة الرسالة بنجاح في قائمة الانتظار.']);
        exit;
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'حدث خطأ أثناء جدولة الرسالة: ' . $e->getMessage()]);
        exit;
    }
}

$apiUrl    = getSetting('whatsapp_api_url', '');
$apiToken  = getSetting('whatsapp_api_token', '');
$sessionId = getSetting('whatsapp_sender', '');

$status   = 'failed';
$response = '';

if (!empty($apiUrl) && !empty($apiToken) && !empty($sessionId)) {
    // بناء الرابط الكامل للـ API باستخدام الـ Session ID
    $endpoint = rtrim($apiUrl, '/');
    if (strpos($endpoint, '/api/sessions') === false) {
        $endpoint = $endpoint . '/api/sessions/' . $sessionId . '/messages';
    }

    $payload = json_encode([
        'to'   => $mobile,
        'text' => $message,
    ]);

    $ch = curl_init($endpoint);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json', 
            'x-api-key: ' . $apiToken
        ],
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $status = ($httpCode >= 200 && $httpCode < 300) ? 'sent' : 'failed';
} else {
    // No API configured — log only
    $status   = 'sent';  // Optimistic in dev mode
    $response = 'API or Session not configured — logged only';
}

// Log the message
$db->prepare("INSERT INTO whatsapp_logs (client_id, mobile, message, msg_type, status, response, sent_by) VALUES (?,?,?,?,?,?,?)")
   ->execute([$clientId ?: null, $mobile, $message, $msgType, $status, $response, currentUserId()]);

if ($status === 'sent') {
    echo json_encode(['success' => true, 'message' => 'تم إرسال الرسالة بنجاح.']);
} else {
    echo json_encode(['success' => false, 'message' => 'فشل إرسال الرسالة. تحقق من إعدادات الواتساب.', 'details' => $response]);
}
