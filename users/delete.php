<?php
/**
 * users/delete.php - حذف مستخدم
 */
require_once dirname(__DIR__) . '/config/app.php';
requireLogin();
if (!isAdmin()) { header('Location: ../403.php'); exit; }

$db = getDB();
$id = (int)($_GET['id'] ?? 0);
if ($id && $id !== currentUserId()) {
    $stmt = $db->prepare("SELECT full_name FROM users WHERE id = ?");
    $stmt->execute([$id]);
    $u = $stmt->fetch();
    if ($u) {
        $db->prepare("DELETE FROM users WHERE id = ?")->execute([$id]);
        setFlash('success', "تم حذف المستخدم \"{$u['full_name']}\" بنجاح.");
    }
} else {
    setFlash('error', 'لا يمكنك حذف حسابك الشخصي.');
}
header('Location: index.php');
exit;
