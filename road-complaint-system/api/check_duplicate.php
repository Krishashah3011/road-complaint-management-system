<?php
// ============================================================
// api/check_duplicate.php
// AJAX: Check if a similar complaint exists within last 7 days
//
// GET /api/check_duplicate.php?category_id=1&area_id=3
// Returns:
//   { "is_duplicate": false }
//   { "is_duplicate": true, "complaint_no": "CMP202500123",
//     "days_ago": 2, "status": "in_progress" }
// ============================================================

header('Content-Type: application/json');

require_once __DIR__ . '/../config/db.php';

$categoryId = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;
$areaId     = isset($_GET['area_id'])     ? (int)$_GET['area_id']     : 0;

if (!$categoryId || !$areaId) {
    echo json_encode(['is_duplicate' => false]);
    exit;
}

$db = getDB();

$stmt = $db->prepare("
    SELECT
        c.id,
        c.complaint_no,
        c.status,
        c.submitted_at,
        DATEDIFF(NOW(), c.submitted_at) AS days_ago
    FROM complaints c
    WHERE c.category_id = ?
      AND c.area_id     = ?
      AND c.submitted_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
      AND c.status NOT IN ('closed')
    ORDER BY c.submitted_at DESC
    LIMIT 1
");
$stmt->execute([$categoryId, $areaId, REPEATED_COMPLAINT_DAYS]);
$row = $stmt->fetch();

if ($row) {
    echo json_encode([
        'is_duplicate' => true,
        'complaint_no' => $row['complaint_no'],
        'status'       => $row['status'],
        'submitted_at' => $row['submitted_at'],
        'days_ago'     => (int)$row['days_ago'],
    ]);
} else {
    echo json_encode(['is_duplicate' => false]);
}
