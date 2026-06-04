<?php
/**
 * api/service-plans.php
 * يُرجع باقات خدمة معيّنة بصيغة JSON
 */
require_once dirname(__DIR__) . '/config/app.php';
requireLogin();

header('Content-Type: application/json; charset=utf-8');

$serviceId = (int)($_GET['service_id'] ?? 0);
if (!$serviceId) {
    echo json_encode(['plans' => []]);
    exit;
}

$db   = getDB();
$stmt = $db->prepare("
    SELECT id, name, description, price
    FROM service_plans
    WHERE service_id = ? AND status = 1
    ORDER BY sort_order ASC, price ASC
");
$stmt->execute([$serviceId]);
$plans = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['plans' => $plans]);
