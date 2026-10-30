<?php
/**
 * whatsapp/renewal_cron.php
 * سكريبت تشغيل فحص وإرسال إشعارات التجديد التلقائي
 * يمكن تشغيله عبر Cron Job على الاستضافة (مثلاً يومياً أو كل بضع ساعات)
 * أو استدعاؤه تلقائياً من whatsapp/cron.php
 */

if (php_sapi_name() !== 'cli') {
    set_time_limit(300);
}

require_once dirname(__DIR__) . '/config/app.php';
require_once INCLUDES_PATH . '/renewal_automation.php';

$isCli = (php_sapi_name() === 'cli');

// حماية التشغيل عبر الويب (إذا لم يكن CLI أو مستخدم مسجل الدخول، يتطلب توكن الأمان أو مفتاح النظام)
if (!$isCli && !isLoggedIn()) {
    $providedToken = $_GET['token'] ?? $_POST['token'] ?? '';
    $expectedToken = getSetting('whatsapp_api_token', '');
    if (!empty($expectedToken) && !hash_equals($expectedToken, $providedToken)) {
        http_response_code(403);
        die("Forbidden: Invalid Cron Token");
    }
}

echo "=== [ENJAZ] Starting Renewal Automation Check: " . date('Y-m-d H:i:s') . " ===\n";

$result = runRenewalDailyRoutine(false);

echo "Mode: " . ($result['is_review_mode'] ? "Manual Review (Alert Admin First)" : "Direct Auto-Send") . "\n";
echo "Candidates found for tomorrow: " . $result['candidates_count'] . "\n";
echo "Expiring tomorrow (1-day left): " . $result['expiring_tomorrow'] . "\n";
echo "Admin WhatsApp alert status: " . ($result['admin_notification']['status'] ?? 'none') . "\n";

if (!$result['is_review_mode']) {
    echo "Dispatched approved messages: " . $result['dispatch_result']['sent'] . " sent, " . $result['dispatch_result']['failed'] . " failed\n";
}

echo "=== [ENJAZ] Renewal Automation Finished Successfully ===\n";

