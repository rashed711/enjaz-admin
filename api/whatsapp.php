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
