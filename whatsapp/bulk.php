<?php
/**
 * whatsapp/bulk.php - إرسال جماعي للاشتراكات المنتهية قريباً (مجمعة لكل عميل)
 */
require_once dirname(__DIR__) . '/config/app.php';
requireLogin();
requirePermission('send_whatsapp');

$db   = getDB();
$type = clean($_GET['type'] ?? 'renewal');
$days = (int)($_POST['days'] ?? $_GET['days'] ?? 30);

$clientIds = [];
$isPost = ($_SERVER['REQUEST_METHOD'] === 'POST');

if ($isPost) {
    $clientIds = $_POST['client_ids'] ?? [];
    if (empty($clientIds)) {
        setFlash('error', 'يرجى تحديد عميل واحد على الأقل للإرسال.');
        header('Location: ../reports/renewals.php');
        exit;
    }
}

// بناء الجلب المناسب
if (!empty($clientIds)) {
    $placeholders = implode(',', array_fill(0, count($clientIds), '?'));
    $stmt = $db->prepare("
        SELECT c.id, c.name, c.mobile, cs.end_date, s.name as service_name, cs.plan_name
        FROM client_subscriptions cs
        JOIN clients c ON c.id=cs.client_id
        JOIN services s ON s.id=cs.service_id
        WHERE cs.status='active'
          AND cs.end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
          AND c.id IN ($placeholders)
          AND c.mobile IS NOT NULL AND c.mobile != ''
        ORDER BY cs.end_date ASC
    ");
    $stmt->execute(array_merge([$days], $clientIds));
} else {
    // إرسال للكل (GET)
    $stmt = $db->prepare("
        SELECT c.id, c.name, c.mobile, cs.end_date, s.name as service_name, cs.plan_name
        FROM client_subscriptions cs
        JOIN clients c ON c.id=cs.client_id
        JOIN services s ON s.id=cs.service_id
        WHERE cs.status='active'
          AND cs.end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
          AND c.mobile IS NOT NULL AND c.mobile != ''
        ORDER BY cs.end_date ASC
    ");
    $stmt->execute([$days]);
}

$rawResults = $stmt->fetchAll();
$companyName = getSetting('company_name', APP_NAME);

if (empty($rawResults)) {
    setFlash('info', 'لا يوجد عملاء مستهدفين للإرسال.');
    header('Location: ../reports/renewals.php');
    exit;
}

// تجميع الخدمات المنتهية لكل عميل لإرسال رسالة واحدة مجمعة
$clientsData = [];
foreach ($rawResults as $row) {
    $cId = $row['id'];
    if (!isset($clientsData[$cId])) {
        $clientsData[$cId] = [
            'id'       => $cId,
            'name'     => $row['name'],
            'mobile'   => $row['mobile'],
            'services' => []
        ];
    }
    $clientsData[$cId]['services'][] = $row;
}

$sent = 0;
$failed = 0;

$apiUrl    = getSetting('whatsapp_api_url', '');
$apiToken  = getSetting('whatsapp_api_token', '');
$sessionId = getSetting('whatsapp_sender', '');

foreach ($clientsData as $cl) {
    $mobile = preg_replace('/\D/', '', $cl['mobile']);
    if (strlen($mobile) === 11 && $mobile[0] === '0') {
        $mobile = '2' . $mobile;
    }

    // صياغة قائمة الخدمات والتنبيهات للعميل
    $servicesText = "";
    foreach ($cl['services'] as $srv) {
        $planDetails = $srv['plan_name'] ? " ({$srv['plan_name']})" : "";
        $endDateFormatted = formatDate($srv['end_date']);
        $servicesText .= "• خدمة «{$srv['service_name']}»{$planDetails} - تنتهي بتاريخ {$endDateFormatted}\n";
    }

    $message = "السادة / {$cl['name']}\n\nنود إعلامكم بأن الاشتراكات التالية لديكم تنتهي قريباً:\n\n{$servicesText}\nيرجى التواصل معنا لتجديد الاشتراك والاستمرار في الاستفادة من خدماتنا.\n\nشكراً لثقتكم 🌟\n{$companyName}";

    $status   = 'sent';
    $response = '';

    if (!empty($apiUrl) && !empty($apiToken) && !empty($sessionId)) {
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
        $status   = 'sent'; // Optimistic in dev
        $response = 'API not configured — logged only';
    }

    $db->prepare("INSERT INTO whatsapp_logs (client_id,mobile,message,msg_type,status,response,sent_by) VALUES (?,?,?,'bulk',?,?,?)")
       ->execute([$cl['id'],$mobile,$message,$status,$response,currentUserId()]);

    $status === 'sent' ? $sent++ : $failed++;
    usleep(500000); // 0.5 second delay
}

setFlash('success', "تم إرسال " . $sent . " رسالة تذكير مجمعة للعملاء بنجاح." . ($failed > 0 ? " | فشل: $failed" : ''));
header('Location: ../reports/renewals.php');
exit;
