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
        if ($price < 0)  $errors[] = 'السعر لا يمكن أن يكون أقل من صفر.';

        $dbValStartDate = null;
        $dbValEndDate   = null;

        if ($serviceId) {
            $srvStmt = $db->prepare("SELECT duration_months FROM services WHERE id = ?");
            $srvStmt->execute([$serviceId]);
            $srv = $srvStmt->fetch();
            
            if ($srv && (int)$srv['duration_months'] > 0) {
                if (!$startDate) {
                    $errors[] = 'تاريخ البداية مطلوب لهذه الخدمة.';
                } else {
                    $dbValStartDate = $startDate;
                }
                if (!$endDate) {
                    $errors[] = 'تاريخ النهاية مطلوب لهذه الخدمة.';
                } else {
                    $dbValEndDate = $endDate;
                }
            } else {
                if ($startDate) $dbValStartDate = $startDate;
                if ($endDate)   $dbValEndDate   = $endDate;
            }
        }

        if (empty($errors)) {
            $db->prepare("
                INSERT INTO client_subscriptions
                  (client_id, service_id, plan_name, price, start_date, end_date, notes, status, created_by)
                VALUES (?,?,?,?,?,?,?,'active',?)
            ")->execute([$clientId, $serviceId, $planName, $price, $dbValStartDate, $dbValEndDate, $notes, currentUserId()]);

            // تحديث بيانات الدومين ومزود الخدمة للعميل
            $domain = clean($_POST['domain'] ?? '');
            $domainProvider = clean($_POST['domain_provider'] ?? '');
            
            if ($domain) {
                $db->prepare("UPDATE clients SET domain = ?, domain_provider = ? WHERE id = ?")
                   ->execute([$domain, $domainProvider, $clientId]);
            } else {
                // الطريقة الاحتياطية: استخراج الدومين من اسم الخطة إذا كان صالحاً
                $srvNameStmt = $db->prepare("SELECT name FROM services WHERE id = ?");
                $srvNameStmt->execute([$serviceId]);
                $serviceName = $srvNameStmt->fetchColumn();
                if ($serviceName && (mb_strpos($serviceName, 'دومين') !== false || mb_strpos(strtolower($serviceName), 'domain') !== false)) {
                    $trimmedPlan = trim($planName);
                    if (preg_match('/^[a-zA-Z0-9][a-zA-Z0-9-]{1,61}[a-zA-Z0-9]\.[a-zA-Z]{2,6}$/', $trimmedPlan)) {
                        $db->prepare("UPDATE clients SET domain = ? WHERE id = ? AND (domain IS NULL OR domain = '')")
                           ->execute([$trimmedPlan, $clientId]);
                    }
                }
            }

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
