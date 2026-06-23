<?php
// ============================================================
// api/get_areas.php — AJAX: Return areas for a given ward_id
//
// GET /api/get_areas.php?ward_id=2
// Returns: [ { "id": 4, "name": "Kumbharwada Main Road" }, … ]
// ============================================================

header('Content-Type: application/json');

require_once __DIR__ . '/../config/db.php';

$wardId = isset($_GET['ward_id']) ? (int)$_GET['ward_id'] : 0;

if (!$wardId) {
    echo json_encode([]);
    exit;
}

$db   = getDB();
$stmt = $db->prepare("SELECT id, name FROM areas WHERE ward_id = ? AND is_active = 1 ORDER BY name");
$stmt->execute([$wardId]);
$areas = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($areas);
