<?php
/**
 * whatsapp/cron.php - تشغيل التنبيهات والرسائل التلقائية المجدولة
 * يمكن تشغيل هذا الملف عبر Cron Job على الاستضافة (مثلاً كل ساعة)
 * أو يتم تشغيله تلقائياً في الخلفية عند تصفح الموقع
 */

// السماح بالتشغيل عبر الويب أو CLI
if (php_sapi_name() !== 'cli') {
    // تحديد وقت أقصى للتشغيل لمنع انتهاء وقت الاستضافة
    set_time_limit(180); 
}

require_once dirname(__DIR__) . '/config/app.php';

$db = getDB();

// جلب الجدولة المستحقة للتشغيل حالياً
$schedules = $db->query("
    SELECT * FROM whatsapp_schedules 
    WHERE status = 1 
      AND next_run <= NOW()
")->fetchAll();

if (empty($schedules)) {
    echo "No pending schedules to run.\n";
    exit;
}

$apiUrl    = getSetting('whatsapp_api_url', '');
$apiToken  = getSetting('whatsapp_api_token', '');
$sessionId = getSetting('whatsapp_sender', '');
$companyName = getSetting('company_name', APP_NAME);

foreach ($schedules as $sch) {
    echo "Processing schedule: {$sch['title']} (ID: {$sch['id']})\n";
    
    // تحديد العملاء المستهدفين
    $clients = [];
    if ($sch['target_type'] === 'all') {
        $clients = $db->query("SELECT id, name, mobile, company_name FROM clients WHERE status = 1 AND mobile IS NOT NULL AND mobile != ''")->fetchAll();
    } elseif ($sch['target_type'] === 'active') {
        $clients = $db->query("SELECT id, name, mobile, company_name FROM clients WHERE status = 1 AND mobile IS NOT NULL AND mobile != ''")->fetchAll();
    } elseif ($sch['target_type'] === 'suspended') {
        $clients = $db->query("SELECT id, name, mobile, company_name FROM clients WHERE status = 0 AND mobile IS NOT NULL AND mobile != ''")->fetchAll();
    } elseif ($sch['target_type'] === 'debt') {
        $clients = $db->query("
            SELECT c.id, c.name, c.mobile, c.company_name
            FROM clients c
            LEFT JOIN client_subscriptions cs ON cs.client_id = c.id
            GROUP BY c.id
            HAVING (COALESCE(SUM(CASE WHEN cs.status != 'cancelled' THEN cs.price ELSE 0 END), 0) - COALESCE((SELECT SUM(p.amount) FROM payments p WHERE p.client_id = c.id), 0)) > 0
               AND c.mobile IS NOT NULL AND c.mobile != ''
        ")->fetchAll();
    } elseif ($sch['target_type'] === 'expiring') {
        $stmt = $db->prepare("
            SELECT DISTINCT c.id, c.name, c.mobile, c.company_name
            FROM client_subscriptions cs
            JOIN clients c ON c.id = cs.client_id
            WHERE cs.status = 'active'
              AND cs.end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
              AND c.mobile IS NOT NULL AND c.mobile != ''
        ");
        $stmt->execute([$sch['warning_days']]);
        $clients = $stmt->fetchAll();
    }

    if (empty($clients)) {
        echo "No target clients found for this schedule.\n";
    } else {
        foreach ($clients as $cl) {
            $mobile = normalizeMobile($cl['mobile']);

            // صياغة الرسالة الشخصية للعميل
            $message = $sch['message'];
            $message = str_replace('{name}', $cl['name'], $message);
            $message = str_replace('{company}', $cl['company_name'] ?? '', $message);

            $status   = 'failed';
            $response = '';

            // إرسال الرسالة عبر API الواتساب
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
                $status   = 'sent'; // Dev mock
                $response = 'API not configured — logged only';
            }

            // تسجيل السجل
            $db->prepare("INSERT INTO whatsapp_logs (client_id, mobile, message, msg_type, status, response, sent_by) VALUES (?,?,?, 'scheduled', ?,?, NULL)")
               ->execute([$cl['id'], $mobile, $message, $status, $response]);

            echo " - Message sent to {$cl['name']} ({$status})\n";
            
            // تأخير 2 ثانية بين كل رسالة والثانية لمنع الحظر وتقليل الضغط
            sleep(2);
        }
    }

    // حساب موعد التشغيل القادم وتحديث السجل للجدولة
    $next = new DateTime($sch['next_run']);
    if ($sch['frequency'] === 'daily') {
        $next->modify('+1 day');
    } elseif ($sch['frequency'] === 'weekly') {
        $next->modify('+7 days');
    } elseif ($sch['frequency'] === 'monthly') {
        $next->modify('+1 month');
    } elseif ($sch['frequency'] === 'interval') {
        $next->modify('+' . $sch['custom_interval_days'] . ' days');
    }

    // الحماية من الدوران اللا نهائي لو كان تاريخ الاستحقاق قديماً جداً
    $now = new DateTime();
    while ($next <= $now) {
        if ($sch['frequency'] === 'daily') $next->modify('+1 day');
        elseif ($sch['frequency'] === 'weekly') $next->modify('+7 days');
        elseif ($sch['frequency'] === 'monthly') $next->modify('+1 month');
        elseif ($sch['frequency'] === 'interval') $next->modify('+' . $sch['custom_interval_days'] . ' days');
    }

    $nextRunStr = $next->format('Y-m-d H:i:s');
    $db->prepare("UPDATE whatsapp_schedules SET last_run = NOW(), next_run = ? WHERE id = ?")
       ->execute([$nextRunStr, $sch['id']]);
       
    echo "Schedule ID {$sch['id']} updated next run to {$nextRunStr}\n\n";
}

echo "All pending schedules processed successfully.\n";
