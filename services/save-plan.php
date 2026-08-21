<?php
/**
 * services/save-plan.php - حفظ باقة (إضافة أو تعديل)
 */
require_once dirname(__DIR__) . '/config/app.php';
requireLogin();
requirePermission('manage_services');

if (!verifyCsrf()) { setFlash('error','خطأ في الأمان.'); header('Location: index.php'); exit; }

$db        = getDB();
$planId    = (int)($_POST['plan_id']    ?? 0);
$serviceId = (int)($_POST['service_id'] ?? 0);
$name      = clean($_POST['name']       ?? '');
$desc      = clean($_POST['description']?? '');
$currency      = clean($_POST['currency'] ?? 'EGP');
$originalPrice = isset($_POST['original_price']) && $_POST['original_price'] !== '' ? (float)$_POST['original_price'] : null;
$price         = (float)($_POST['price'] ?? 0);
$sort          = (int)($_POST['sort_order'] ?? 0);
$status        = ($_POST['status'] ?? '1') === '1' ? 1 : 0;

if ($currency === 'EGP' && $originalPrice === null) {
    $originalPrice = $price;
}

if (empty($name) || !$serviceId) {
    setFlash('error','اسم الباقة والخدمة مطلوبان.');
    header('Location: index.php');
    exit;
}

if ($planId > 0) {
    $db->prepare("UPDATE service_plans SET name=?, description=?, currency=?, original_price=?, price=?, sort_order=?, status=? WHERE id=? AND service_id=?")
       ->execute([$name, $desc, $currency, $originalPrice, $price, $sort, $status, $planId, $serviceId]);
    setFlash('success', "تم تعديل الباقة «$name» بنجاح.");
} else {
    $db->prepare("INSERT INTO service_plans (service_id, name, description, currency, original_price, price, sort_order, status) VALUES (?,?,?,?,?,?,?,?)")
       ->execute([$serviceId, $name, $desc, $currency, $originalPrice, $price, $sort, $status]);
    setFlash('success', "تم إضافة الباقة «$name» بنجاح.");
}

header('Location: index.php');
exit;
