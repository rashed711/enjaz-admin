<?php
/**
 * api/check-username.php - التحقق من توفر اسم المستخدم (username_note)
 */
require_once dirname(__DIR__) . '/config/app.php';
requireLogin();

$db = getDB();
$username = clean($_GET['username'] ?? '');
$clientId = (int)($_GET['client_id'] ?? 0);

if (!$username) {
    echo json_encode(['available' => false, 'message' => 'اسم المستخدم فارغ']);
    exit;
}

// نتحقق إذا كان اسم المستخدم مستخدماً من قبل عميل آخر
$stmt = $db->prepare("SELECT COUNT(*) FROM clients WHERE username_note = ? AND id != ?");
$stmt->execute([$username, $clientId]);
$count = (int)$stmt->fetchColumn();

echo json_encode([
    'available' => ($count === 0),
    'username'  => $username
]);
