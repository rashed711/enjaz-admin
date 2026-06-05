<?php
/**
 * whatsapp/bulk.php - إرسال جماعي للاشتراكات المنتهية قريباً
 */
require_once dirname(__DIR__) . '/config/app.php';
requireLogin();
requirePermission('send_whatsapp');

$db   = getDB();
$type = clean($_GET['type'] ?? 'renewal');
$days = (int)($_GET['days'] ?? 30);

// جلب العملاء المستهدفين
$stmt = $db->prepare("
    SELECT DISTINCT c.id, c.name, c.mobile, cs.end_date, s.name as service_name
    FROM client_subscriptions cs
    JOIN clients c ON c.id=cs.client_id
    JOIN services s ON s.id=cs.service_id
    WHERE cs.status='active'
      AND cs.end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
      AND c.mobile IS NOT NULL AND c.mobile != ''
    ORDER BY cs.end_date ASC
");
$stmt->execute([$days]);
$clients = $stmt->fetchAll();

$companyName = getSetting('company_name', APP_NAME);

if (empty($clients)) {
    setFlash('info', 'لا يوجد عملاء للإرسال إليهم.');
    header('Location: ../reports/renewals.php');
    exit;
}

// إرسال جماعي
$sent = 0;
$failed = 0;

foreach ($clients as $cl) {
    $mobile = preg_replace('/\D/', '', $cl['mobile']);
    if (strlen($mobile) === 11 && $mobile[0] === '0') $mobile = '2' . $mobile;

    $daysLeft = (new DateTime($cl['end_date']))->diff(new DateTime('today'))->days;
    $endDate  = formatDate($cl['end_date']);

    $message = "السادة / {$cl['name']}\n\nنود إعلامكم بأن اشتراككم في خدمة «{$cl['service_name']}» لدى {$companyName} ينتهي بتاريخ {$endDate}.\n\nيرجى التواصل معنا لتجديد الاشتراك والاستمرار في الاستفادة من خدماتنا.\n\nشكراً لثقتكم 🌟";

    $apiUrl    = getSetting('whatsapp_api_url', '');
    $apiToken  = getSetting('whatsapp_api_token', '');
    $sessionId = getSetting('whatsapp_sender', '');
    $status    = 'sent';
    $response  = '';

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
            CURLOPT_TIMEOUT        => 10, 
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json', 
                'x-api-key: ' . $apiToken
            ]
        ]);
        $response = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $status = ($code >= 200 && $code < 300) ? 'sent' : 'failed';
    } else {
        $response = 'API or Session not configured — logged only';
    }

    $db->prepare("INSERT INTO whatsapp_logs (client_id,mobile,message,msg_type,status,response,sent_by) VALUES (?,?,?,'bulk',?,?,?)")
       ->execute([$cl['id'],$mobile,$message,$status,$response,currentUserId()]);

    $status === 'sent' ? $sent++ : $failed++;
    usleep(500000); // 0.5 second delay between messages
}

setFlash('success', "تم الإرسال: $sent رسالة بنجاح" . ($failed > 0 ? " | فشل: $failed" : ''));
header('Location: ../reports/renewals.php');
exit;
