<?php
/**
 * users/toggle.php - تفعيل/إيقاف مستخدم
 */
require_once dirname(__DIR__) . '/config/app.php';
requireLogin();
if (!isAdmin()) { header('Location: ../403.php'); exit; }

$db = getDB();
$id = (int)($_GET['id'] ?? 0);
if ($id && $id !== currentUserId()) {
    $stmt = $db->prepare("SELECT status, full_name FROM users WHERE id = ?");
    $stmt->execute([$id]);
    $u = $stmt->fetch();
    if ($u) {
        $newStatus = $u['status'] ? 0 : 1;
        $db->prepare("UPDATE users SET status = ? WHERE id = ?")->execute([$newStatus, $id]);
        $label = $newStatus ? 'تفعيل' : 'إيقاف';
        setFlash('success', "تم $label حساب \"{$u['full_name']}\" بنجاح.");
    }
}
header('Location: index.php');
exit;
