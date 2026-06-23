<?php
// ============================================================
// api/get_spots.php — AJAX: Return spots for a given area_id
//
// GET /api/get_spots.php?area_id=4
// Returns: [ { "id": 6, "name": "Near Kumbharwada Park", "landmark": "Opposite Children Park" }, … ]
// ============================================================

header('Content-Type: application/json');

require_once __DIR__ . '/../config/db.php';

$areaId = isset($_GET['area_id']) ? (int)$_GET['area_id'] : 0;

if (!$areaId) {
    echo json_encode([]);
    exit;
}

$db   = getDB();
$stmt = $db->prepare("SELECT id, name, landmark FROM spots WHERE area_id = ? AND is_active = 1 ORDER BY name");
$stmt->execute([$areaId]);
$spots = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($spots);
