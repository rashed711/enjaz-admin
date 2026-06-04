<?php
/**
 * import_clients.php - كود استيراد بيانات العملاء لمرة واحدة (محدثة)
 */
require_once __DIR__ . '/config/app.php';
requireLogin();
if (!isAdmin()) {
    die('غير مصرح لك بتشغيل هذا الملف. يجب تسجيل الدخول كمدير نظام.');
}

$clientsData = [
    ['م / محمد علي', 'Ellevate Marketing', 'Marketing', '201150695780', 'ellevatemarketing.com', 'ellevatemarketing', 'Netlify'],
    ['م / يوسف', 'G-Tech Solution', 'Tech', '201210622000', 'g-tech-solution.com', 'g-tech-solution', 'Godady'],
    ['م / عمرو خالد', 'Construct Code', 'Contracting', '201012468796', 'Construct-code.com', 'Construct-code', 'Godady'],
    ['م / نبيل رضا', 'Future Green', 'تجارة العدد اليدوية وانظمة الحريق', '201113416722', 'future-green.net', 'future-green', 'Godady'],
    ['م / تامر', 'Alkayan Logistics', '', '201040405470', 'alkayanlogistics.com', 'alkayanlogistics', 'Godady'],
    ['م / محمود مجدي', 'snoby', 'cosmetics', '201018766588', 'snoby-eg.com', 'snoby', 'Godady'],
    ['م / محمد عبد الله', 'Madar MEP', 'MEP', '201060565785', 'MadarMEP.com', 'madarmep', 'Godady'],
    ['م / خالد منصور', 'Omega', 'supplies', '201117013024', 'omega-supplies.com', 'omega-supplies', 'Godady'],
    ['م / ريم', 'myriad', '', '201157184292', 'myriad-co.com', 'myriad-co', 'Godady'],
    ['م / شريف مجدي', 'Senior Constructions', 'Constructions', '201017571005', 'seniorconstructions.com', 'seniorconstructions', 'Godady'],
    ['م / السيد هاني', 'Alamana', 'Transport', '201280265841', 'alamanatransport.com', 'alamanatransport', 'Godady'],
    ['م / فهمي جاد', 'Alnasem', '', '201100691874', 'alnasem-eg.com', 'alnasem-eg', 'Godady'],
    ['د / مصطفي', 'Medx Hub', 'Medical', '201003939499', 'medx-hub.com', 'medx-hub', 'Godady'],
    ['م / تامر خليل', 'Smart Partner', 'agency', '201225770713', 'smartpartner.agency', 'smartpartner', 'Godady'],
    ['م / مروان هشام', 'Wijha Express', 'Express', '201143594429', 'wijha.express', 'wijha', 'Godady'],
    ['م / ت', 'Transmission', 'Digital Marketing', '249123885888', 'transmission-dma.com', 'transmission', 'Godady'],
    ['م / هدوي علي', 'plus-adv', 'Digital Marketing', '201147080280', 'plus-adv.com', 'plus-adv', 'Godady'],
    ['م / كريم', 'Alfa Petroleum', 'Petroleum', '201124252602', 'alfa-petroleum.com', 'alfa-petroleum', 'Godady'],
    ['م / نيرة الليثي', 'Eldewanya', 'تصدير', '201093577377', 'eldewanya.com', 'eldewanya', 'Godady'],
    ['م / عبد اللطيف محمد', 'infinity Logistic', 'Logistic', '201080146149', 'infinity-logistic.net', 'infinity-logistic', 'Godady']
];

$db = getDB();
$count = 0;

foreach ($clientsData as $c) {
    $name = trim($c[0]);
    $companyName = trim($c[1]);
    $activity = trim($c[2]);
    $mobile = trim($c[3]);
    $domain = trim($c[4]);
    $usernameNote = trim($c[5]);
    $domainProvider = trim($c[6]);

    // تنظيف وتجهيز القيم الفارغة أو الناقصة
    $companyName = (empty($companyName) || $companyName === '-') ? null : $companyName;
    $activity = (empty($activity) || $activity === '-') ? null : $activity;
    $mobile = (empty($mobile) || $mobile === '-') ? null : $mobile;
    $domain = (empty($domain) || $domain === '-' || $domain === '"') ? null : $domain;
    $usernameNote = (empty($usernameNote) || $usernameNote === '-') ? null : $usernameNote;
    $domainProvider = (empty($domainProvider) || $domainProvider === '-') ? null : $domainProvider;

    // إصلاح اسم الدومين في الحالات الشبيهة بـ "smartsource-eg.com"
    if ($domain) {
        $domain = trim(str_replace(['"', ' '], '', $domain));
    }

    if (empty($name)) {
        continue;
    }

    try {
        // التحقق من تكرار العميل
        $check = $db->prepare("SELECT COUNT(*) FROM clients WHERE name = ?");
        $check->execute([$name]);
        if ((int)$check->fetchColumn() > 0) {
            echo "العميل: (<strong>{$name}</strong>) مضاف مسبقاً، تخطي.<br>";
            continue;
        }

        $stmt = $db->prepare("
            INSERT INTO clients 
              (name, company_name, activity, mobile, domain, domain_provider, username_note, status, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, 1, ?)
        ");
        $stmt->execute([
            $name,
            $companyName,
            $activity,
            $mobile,
            $domain,
            $domainProvider,
            $usernameNote,
            currentUserId()
        ]);
        $count++;
    } catch (Exception $e) {
        echo "خطأ أثناء إضافة (<strong>{$name}</strong>): " . $e->getMessage() . "<br>";
    }
}

echo "<br>🚀 تم استيراد <strong>{$count}</strong> عملاء بنجاح!";
