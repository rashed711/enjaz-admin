<?php
/**
 * subscriptions/add.php - إضافة اشتراك (بيع خدمة لعميل)
 */
require_once dirname(__DIR__) . '/config/app.php';
requireLogin();
requirePermission('add_subscriptions');

$db = getDB();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) { $errors[] = 'خطأ في الأمان.'; }
    else {
        $clientId  = (int)($_POST['client_id'] ?? 0);
        $serviceId = (int)($_POST['service_id'] ?? 0);
        $planName  = clean($_POST['plan_name'] ?? '');
        $price     = (float)($_POST['price'] ?? 0);
        $startDate = clean($_POST['start_date'] ?? '');
        $endDate   = clean($_POST['end_date'] ?? '');
        $notes     = clean($_POST['notes'] ?? '');

        if (!$clientId)   $errors[] = 'العميل مطلوب.';
        if (!$serviceId)  $errors[] = 'الخدمة مطلوبة.';
        if ($price <= 0)  $errors[] = 'السعر يجب أن يكون أكبر من صفر.';
        if (!$startDate)  $errors[] = 'تاريخ البداية مطلوب.';
        if (!$endDate && $serviceId) {
            // Check if service has duration
            $srvStmt = $db->prepare("SELECT duration_months FROM services WHERE id = ?");
            $srvStmt->execute([$serviceId]);
            $srv = $srvStmt->fetch();
            if ($srv && $srv['duration_months'] == 0) {
                $endDate = $startDate; // one-time service
            } else {
                $errors[] = 'تاريخ النهاية مطلوب.';
            }
        }

        if (empty($errors)) {
            $db->prepare("
                INSERT INTO client_subscriptions
                  (client_id, service_id, plan_name, price, start_date, end_date, notes, status, created_by)
                VALUES (?,?,?,?,?,?,?,'active',?)
            ")->execute([$clientId, $serviceId, $planName, $price, $startDate, $endDate, $notes, currentUserId()]);
            setFlash('success', 'تم إضافة الخدمة للعميل بنجاح.');
            header("Location: ../clients/view.php?id=$clientId");
            exit;
        }
    }
}

// If GET or errors — redirect back
$clientId = (int)($_POST['client_id'] ?? $_GET['client_id'] ?? 0);
if (!empty($errors)) {
    setFlash('error', implode(' | ', $errors));
}
header("Location: ../clients/view.php?id=$clientId");
exit;
