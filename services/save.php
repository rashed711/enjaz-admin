<?php
require_once dirname(__DIR__) . '/config/app.php';
requireLogin();
requirePermission('manage_services');
$db = getDB();
if (!verifyCsrf()) { setFlash('error','خطأ في الأمان.'); header('Location: index.php'); exit; }
$id        = (int)($_POST['service_id'] ?? 0);
$name      = clean($_POST['name'] ?? '');
$desc      = clean($_POST['description'] ?? '');
$price     = (float)($_POST['default_price'] ?? 0);
$duration  = (int)($_POST['duration_months'] ?? 12);
$sortOrder = (int)($_POST['sort_order'] ?? 0);
$status    = ($_POST['status'] ?? '1') === '1' ? 1 : 0;
if (empty($name)) { setFlash('error','اسم الخدمة مطلوب.'); header('Location: index.php'); exit; }
if ($id > 0) {
    $db->prepare("UPDATE services SET name=?,description=?,default_price=?,duration_months=?,sort_order=?,status=? WHERE id=?")
       ->execute([$name,$desc,$price,$duration,$sortOrder,$status,$id]);
    setFlash('success','تم تحديث الخدمة بنجاح.');
} else {
    $db->prepare("INSERT INTO services (name,description,default_price,duration_months,sort_order,status,created_by) VALUES (?,?,?,?,?,?,?)")
       ->execute([$name,$desc,$price,$duration,$sortOrder,$status,currentUserId()]);
    setFlash('success','تم إضافة الخدمة بنجاح.');
}
header('Location: index.php'); exit;
