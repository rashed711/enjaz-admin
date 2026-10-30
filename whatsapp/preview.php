<?php
/**
 * whatsapp/preview.php - واجهة معاينة وتعديل رسائل التجديد قبل الإرسال
 * ترجع بيانات الرسائل المصاغة المخصصة لكل عميل بصيغة JSON
 */
require_once dirname(__DIR__) . '/config/app.php';
requireLogin();
requirePermission('send_whatsapp');

header('Content-Type: application/json; charset=utf-8');

$db = getDB();
$warningDays = (int)getSetting('renewal_warning_days', '60');
$days = (int)($_POST['days'] ?? $_GET['days'] ?? $warningDays);
if ($days <= 0) {
    $days = $warningDays;
}
$clientIds = $_POST['client_ids'] ?? [];
$isAll = !empty($_POST['all']) || !empty($_GET['all']);

if (is_string($clientIds)) {
    $clientIds = array_filter(array_map('intval', explode(',', $clientIds)));
} elseif (is_array($clientIds)) {
    $clientIds = array_filter(array_map('intval', $clientIds));
}

$where = ["cs.status='active'", "cs.end_date IS NOT NULL"];
$params = [];

if (!empty($clientIds) && !$isAll) {
    // تم تحديد عملاء محددين يدوياً: جلب اشتراكاتهم المستحقة دون حجبها
    $placeholders = implode(',', array_fill(0, count($clientIds), '?'));
    $where[] = "c.id IN ($placeholders)";
    $params = $clientIds;

    $maxDays = max($days, $warningDays, 365);
    $where[] = "cs.end_date <= DATE_ADD(CURDATE(), INTERVAL ? DAY)";
    $params[] = $maxDays;
} else {
    // فلترة إضافية عند اختيار الكل
    $where[] = "cs.end_date <= DATE_ADD(CURDATE(), INTERVAL ? DAY)";
    $params[] = $days;

    $clientStatus = clean($_POST['status'] ?? $_GET['status'] ?? '1');
    $country      = clean($_POST['country'] ?? $_GET['country'] ?? '');
    $server       = clean($_POST['server'] ?? $_GET['server'] ?? '');
    $search       = clean($_POST['search'] ?? $_GET['search'] ?? '');

    if ($clientStatus !== 'all') {
        $where[] = "c.status = ?";
        $params[] = (int)$clientStatus;
    }
    if ($country !== '') {
        $where[] = "c.country = ?";
        $params[] = $country;
    }
    if ($server !== '') {
        $where[] = "c.server_panel = ?";
        $params[] = $server;
    }
    if ($search !== '') {
        $where[] = "(c.name LIKE ? OR c.company_name LIKE ? OR c.mobile LIKE ? OR c.domain LIKE ? OR cs.notes LIKE ? OR cs.plan_name LIKE ?)";
        $s = "%$search%";
        $params = array_merge($params, [$s, $s, $s, $s, $s, $s]);
    }
}

$where[] = "c.mobile IS NOT NULL AND c.mobile != ''";
$whereStr = implode(' AND ', $where);

$stmt = $db->prepare("
    SELECT c.id as client_id, c.name as client_name, c.mobile, c.company_name, c.country, c.server_panel, c.domain as client_domain,
           cs.id as sub_id, cs.service_id, cs.plan_name, cs.currency, cs.original_price, cs.price, cs.end_date,
           s.name as service_name
    FROM client_subscriptions cs
    JOIN clients c ON c.id=cs.client_id
    JOIN services s ON s.id=cs.service_id
    WHERE $whereStr
    ORDER BY c.name ASC, cs.end_date ASC
");
$stmt->execute($params);
$rawRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($rawRows)) {
    echo json_encode([
        'success' => false,
        'message' => 'لا توجد اشتراكات مستحقة للتجديد مطابقة للمحددين.',
        'clients' => []
    ]);
    exit;
}

// تجميع الاشتراكات لكل عميل
$groupedClients = [];
foreach ($rawRows as $r) {
    $cId = $r['client_id'];
    if (!isset($groupedClients[$cId])) {
        $groupedClients[$cId] = [
            'id'            => $cId,
            'name'          => $r['client_name'],
            'mobile'        => $r['mobile'],
            'company_name'  => $r['company_name'] ?? '',
            'country'       => $r['country'] ?? 'EG',
            'server_panel'  => $r['server_panel'] ?? '',
            'client_domain' => $r['client_domain'] ?? '',
            'domain'        => $r['client_domain'] ?? '',
            'subscriptions' => []
        ];
    }
    $groupedClients[$cId]['subscriptions'][] = $r;
}

$companyName = getSetting('company_name', APP_NAME);
$resultClients = [];

foreach ($groupedClients as $cId => $cData) {
    // فحص ما إذا كان لدى العميل اشتراك دومين نشط في النظام
    $hasDomainSub = false;
    foreach ($cData['subscriptions'] as $sub) {
        $sName = mb_strtolower($sub['service_name']);
        if ((int)$sub['service_id'] === 2 || mb_strpos($sName, 'دومين') !== false || mb_strpos($sName, 'domain') !== false) {
            $hasDomainSub = true;
            break;
        }
    }

    // إذا لم يكن الدومين ضمن الاشتراكات التي تنتهي في هذه الفترة، نتحقق من قاعدة البيانات هل لديه اشتراك دومين نشط
    if (!$hasDomainSub) {
        $chkDom = $db->prepare("SELECT 1 FROM client_subscriptions WHERE client_id = ? AND service_id = 2 AND status = 'active' LIMIT 1");
        $chkDom->execute([$cId]);
        if ($chkDom->fetchColumn()) {
            // لديه اشتراك دومين مسجل في النظام لكن تاريخه لم ينتهِ بعد، نحتفظ به كـ false لعدم المطالبة به إلا عند استحقاقه
            $hasDomainSub = false; 
        }
    }

    // صياغة الرسالة الذكية
    $build = buildRenewalWhatsappMessage($cData, $cData['subscriptions'], $companyName);
    $countryInfo = getCountryInfo($cData['country']);

    $resultClients[] = [
        'id'              => $cId,
        'name'            => $cData['name'],
        'mobile'          => $cData['mobile'],
        'normalized_mobile' => normalizeMobile($cData['mobile']),
        'company_name'    => $cData['company_name'],
        'domain'          => $build['domain'] ?? $cData['client_domain'] ?? '',
        'country'         => $cData['country'],
        'country_name'    => $countryInfo['name'] ?? '',
        'country_flag_svg'=> $countryInfo['svg'] ?? '',
        'due_date'        => $build['due_date'],
        'currency'        => $build['currency'],
        'currency_label'  => $build['currency_label'],
        'unit_label'      => $build['unit_label'],
        'total_amount'    => $build['total_amount'],
        'total_formatted' => $build['total_formatted'],
        'has_domain'      => $build['has_domain'],
        'default_message' => $build['message'],
        'message'         => $build['message'],
        'items'           => $build['items'],
        'service_count'   => count($cData['subscriptions'])
    ];
}

echo json_encode([
    'success' => true,
    'count'   => count($resultClients),
    'clients' => $resultClients
]);
