<?php
/**
 * cron/daily_report.php - التقرير اليومي التلقائي للعملاء والمديونيات
 * يتم تشغيله يومياً الساعة 12:00 ص (00:00) عبر الـ Cron Job.
 */
require_once dirname(__DIR__) . '/config/app.php';

// التحقق من الأمان: يجب تشغيل السكريبت من الـ CLI أو باستخدام توكن سري
$cronToken = 'EnjazDailyReportToken2026';
if (php_sapi_name() !== 'cli' && ($_GET['token'] ?? '') !== $cronToken) {
    http_response_code(403);
    die('غير مصرح بالوصول.');
}

$db = getDB();

// جلب بيانات جميع العملاء بالصيغة المطلوبة
$query = "
    SELECT 
        c.id, 
        c.name, 
        c.mobile, 
        c.domain, 
        c.username_note, 
        c.status AS client_status,
        (SELECT cs2.plan_name FROM client_subscriptions cs2 JOIN services s2 ON s2.id = cs2.service_id WHERE cs2.client_id = c.id AND (s2.name LIKE '%بريد%' OR s2.name LIKE '%mail%' OR s2.name LIKE '%email%' OR s2.name LIKE '%ايميل%') ORDER BY (CASE WHEN cs2.status = 'active' THEN 1 ELSE 2 END) ASC, cs2.id DESC LIMIT 1) AS email_plan,
        (SELECT cs2.start_date FROM client_subscriptions cs2 JOIN services s2 ON s2.id = cs2.service_id WHERE cs2.client_id = c.id AND (s2.name LIKE '%بريد%' OR s2.name LIKE '%mail%' OR s2.name LIKE '%email%' OR s2.name LIKE '%ايميل%') ORDER BY (CASE WHEN cs2.status = 'active' THEN 1 ELSE 2 END) ASC, cs2.id DESC LIMIT 1) AS email_start_date,
        EXISTS(SELECT 1 FROM client_subscriptions cs2 JOIN services s2 ON s2.id = cs2.service_id WHERE cs2.client_id = c.id AND cs2.status != 'cancelled' AND (s2.name LIKE '%دومين%' OR s2.name LIKE '%domain%')) AS domain_from_us,
        (COALESCE((SELECT SUM(cs3.price) FROM client_subscriptions cs3 WHERE cs3.client_id = c.id AND cs3.status != 'cancelled'), 0) 
         - COALESCE((SELECT SUM(p.amount) FROM payments p WHERE p.client_id = c.id), 0)) AS remaining_due
    FROM clients c
    ORDER BY c.name ASC
";
$clients = $db->query($query)->fetchAll();

// بناء جدول التقرير بصيغة HTML متناسقة وراقية
$html = '<html dir="rtl" lang="ar"><head><meta charset="utf-8">';
$html .= '<style>';
$html .= 'body { font-family: "Cairo", Tahoma, Arial, sans-serif; direction: rtl; text-align: right; background-color: #f4f6f9; padding: 20px; color: #333; }';
$html .= '.container { background: #ffffff; border-radius: 12px; padding: 25px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); max-width: 100%; overflow-x: auto; }';
$html .= 'h2 { color: #0e1e35; border-bottom: 2px solid #f0a500; padding-bottom: 10px; margin-top: 0; font-size: 20px; }';
$html .= 'table { width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 13.5px; }';
$html .= 'th { background-color: #0e1e35; color: #ffffff; padding: 12px 10px; text-align: center; font-weight: 700; border: 1px solid #dee2e6; }';
$html .= 'td { padding: 10px 8px; text-align: center; border: 1px solid #dee2e6; }';
$html .= 'tr:nth-child(even) { background-color: #f8f9fa; }';
$html .= '.badge { padding: 4px 8px; border-radius: 6px; font-size: 11px; font-weight: bold; color: #fff; display: inline-block; }';
$html .= '.badge-active { background-color: #10b981; }';
$html .= '.badge-suspended { background-color: #ef4444; }';
$html .= '.badge-from-us { background-color: #3b82f6; }';
$html .= '.badge-from-client { background-color: #6b7280; }';
$html .= '.text-danger { color: #ef4444; font-weight: bold; }';
$html .= '.text-success { color: #10b981; font-weight: bold; }';
$html .= '</style>';
$html .= '</head><body>';
$html .= '<div class="container">';
$html .= '<h2>📊 تقرير العملاء اليومي التلقائي — ' . date('Y-m-d') . '</h2>';
$html .= '<table>';
$html .= '<thead><tr>';
$html .= '<th>اسم العميل</th>';
$html .= '<th>رقم الموبايل</th>';
$html .= '<th>الدومين</th>';
$html .= '<th>اسم المستخدم</th>';
$html .= '<th>باقة الايميل</th>';
$html .= '<th>تاريخ الاشتراك</th>';
$html .= '<th>حالة الدومين</th>';
$html .= '<th>المديونية</th>';
$html .= '<th>الحالة</th>';
$html .= '</tr></thead>';
$html .= '<tbody>';

foreach ($clients as $c) {
    $mobile = $c['mobile'] ?: '—';
    $domain = $c['domain'] ?: '—';
    $username = $c['username_note'] ?: '—';
    $emailPlan = $c['email_plan'] ?: '—';
    $emailStart = $c['email_start_date'] ? formatDate($c['email_start_date']) : '—';
    
    $domainStatus = $c['domain_from_us'] 
        ? '<span class="badge badge-from-us">من عندنا</span>' 
        : '<span class="badge badge-from-client">حاجزه بنفسه</span>';
        
    $debt = '';
    if ($c['remaining_due'] > 0.01) {
        $debt = '<span class="text-danger">نعم (' . formatMoney($c['remaining_due']) . ')</span>';
    } else {
        $debt = '<span class="text-success">لا (0)</span>';
    }
    
    $status = $c['client_status'] 
        ? '<span class="badge badge-active">نشط</span>' 
        : '<span class="badge badge-suspended">موقوف</span>';

    $html .= '<tr>';
    $html .= '<td><strong>' . e($c['name']) . '</strong></td>';
    $html .= '<td><span style="direction: ltr; display: inline-block;">' . e($mobile) . '</span></td>';
    $html .= '<td>' . e($domain) . '</td>';
    $html .= '<td>' . e($username) . '</td>';
    $html .= '<td>' . e($emailPlan) . '</td>';
    $html .= '<td>' . $emailStart . '</td>';
    $html .= '<td>' . $domainStatus . '</td>';
    $html .= '<td>' . $debt . '</td>';
    $html .= '<td>' . $status . '</td>';
    $html .= '</tr>';
}

$html .= '</tbody></table>';
$html .= '</div></body></html>';

// إرسال البريد
$to = 'rashed1711@gmail.com';
$subject = '📊 تقرير العملاء والمديونيات اليومي الشامل';

$res = sendSMTPMail($to, $subject, $html);
if ($res) {
    echo "Report sent successfully to " . $to . "\n";
} else {
    echo "Failed to send report.\n";
}
