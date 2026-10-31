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

echo "=== [ENJAZ] Starting Renewal Automation: " . date('Y-m-d H:i:s') . " ===\n";

// 1. تشغيل الإرسال الصباحي للرسائل المعتمدة
$dispatchRes = runDailyRenewalDispatch(false);
if (!empty($dispatchRes['skipped'])) {
    echo "Morning Dispatch: " . $dispatchRes['reason'] . "\n";
} else {
    $d = $dispatchRes['dispatch_result'];
    echo "Morning Dispatch Executed: {$d['sent']} sent, {$d['failed']} failed (Eligible: {$d['count']})\n";
}

// 2. تشغيل الفحص المسائي للمرشحين الجدد وتنبيه الإدمن
$result = runRenewalDailyRoutine(false);
if (!empty($result['skipped'])) {
    echo "Evening Routine: " . $result['reason'] . "\n";
} else {
    echo "Mode: " . ($result['is_review_mode'] ? "Manual Review (Alert Admin First)" : "Direct Auto-Send") . "\n";
    echo "Candidates found for tomorrow: " . $result['candidates_count'] . "\n";
    echo "Expiring tomorrow (1-day left): " . $result['expiring_tomorrow'] . "\n";
    echo "Admin WhatsApp alert status: " . ($result['admin_notification']['status'] ?? 'none') . "\n";
}

echo "=== [ENJAZ] Renewal Automation Finished Successfully ===\n";

