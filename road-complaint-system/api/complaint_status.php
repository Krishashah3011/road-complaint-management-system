<?php
// ============================================================
// api/complaint_status.php
// JSON API 1 — Get complaint status by ID or complaint number
//
// Usage:
//   GET /api/complaint_status.php?id=12
//   GET /api/complaint_status.php?no=CMP202512345
//
// Returns JSON object with complaint details + status
// ============================================================

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../config/db.php';

// ── Input ────────────────────────────────────────────────────
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$no = isset($_GET['no']) ? trim($_GET['no'])  : '';

if (!$id && !$no) {
    http_response_code(400);
    echo json_encode([
        "status" => "error",
        "message" => "Provide id or no parameter."
    ], JSON_PRETTY_PRINT);
    exit;
}

// ── Query ────────────────────────────────────────────────────
$db = getDB();

$sql = "
    SELECT
        c.id,
        c.complaint_no,
        c.title,
        c.description,
        c.status,
        c.priority,
        c.is_repeated,
        c.sla_response_breach,
        c.sla_resolution_breach,
        c.submitted_at,
        c.first_response_at,
        c.resolved_at,
        c.updated_at,
        cat.name  AS category,
        w.ward_no,
        w.name    AS ward_name,
        a.name    AS area_name,
        s.name    AS spot_name,
        u.name    AS submitted_by,
        st.name   AS assigned_to,
        TIMESTAMPDIFF(HOUR, c.submitted_at, NOW()) AS hours_elapsed
    FROM complaints c
    JOIN categories cat ON c.category_id = cat.id
    JOIN wards      w   ON c.ward_id     = w.id
    JOIN areas      a   ON c.area_id     = a.id
    JOIN spots      s   ON c.spot_id     = s.id
    JOIN users      u   ON c.submitted_by = u.id
    LEFT JOIN users st  ON c.assigned_to  = st.id
    WHERE " . ($id ? "c.id = ?" : "c.complaint_no = ?");

$stmt = $db->prepare($sql);
$stmt->execute([$id ?: $no]);
$row = $stmt->fetch();

if (!$row) {
    http_response_code(404);
    echo json_encode([
        "status" => "error",
        "message" => "Complaint not found."
    ], JSON_PRETTY_PRINT);
    exit;
}

// ── Fetch last history entry ──────────────────────────────────
$hist = $db->prepare("
    SELECT h.new_status, h.remark, h.action_at, u.name as action_by_name
    FROM complaint_history h
    JOIN users u ON h.action_by = u.id
    WHERE h.complaint_id = ?
    ORDER BY h.action_at DESC LIMIT 1
");
$hist->execute([$row['id']]);
$lastAction = $hist->fetch();

// ── Build response ────────────────────────────────────────────
$response = [
    'id'                  => (int)$row['id'],
    'complaint_no'        => $row['complaint_no'],
    'title'               => $row['title'],
    'status'              => $row['status'],
    'priority'            => $row['priority'],
    'category'            => $row['category'],
    'location' => [
        'ward'  => $row['ward_no'] . ' - ' . $row['ward_name'],
        'area'  => $row['area_name'],
        'spot'  => $row['spot_name'],
    ],
    'is_repeated'          => (bool)$row['is_repeated'],
    'sla_response_breach'  => (bool)$row['sla_response_breach'],
    'sla_resolution_breach'=> (bool)$row['sla_resolution_breach'],
    'hours_elapsed'        => (int)$row['hours_elapsed'],
    'submitted_by'         => $row['submitted_by'],
    'assigned_to'          => $row['assigned_to'],
    'submitted_at'         => $row['submitted_at'],
    'first_response_at'    => $row['first_response_at'],
    'resolved_at'          => $row['resolved_at'],
    'updated_at'           => $row['updated_at'],
    'last_action'          => $lastAction ?: null,
];

echo json_encode([
    "status" => "success",
    "data" => $response
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);