<?php
// ============================================================
// api/area_pending.php
// JSON API 2 — Get area-wise pending complaints count
// ============================================================

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../config/db.php';

$db = getDB();

$wardId = isset($_GET['ward_id']) ? (int)$_GET['ward_id'] : 0;
$status = isset($_GET['status'])  ? trim($_GET['status'])  : '';

$validStatuses = ['submitted','verified','assigned','in_progress','reopened','escalated'];

$where  = ["c.status NOT IN ('resolved','closed')"];
$params = [];

if ($wardId) {
    $where[]  = 'c.ward_id = ?';
    $params[] = $wardId;
}

if ($status && in_array($status, $validStatuses)) {
    $where[]  = 'c.status = ?';
    $params[] = $status;
}

$whereSQL = 'WHERE ' . implode(' AND ', $where);

// Main query — using CASE WHEN for MariaDB compatibility
$stmt = $db->prepare("
    SELECT
        a.id   AS area_id,
        a.name AS area_name,
        w.id   AS ward_id,
        w.ward_no,
        w.name AS ward_name,
        COUNT(c.id) AS total_pending,
        SUM(CASE WHEN c.priority = 'critical'  THEN 1 ELSE 0 END) AS cnt_critical,
        SUM(CASE WHEN c.priority = 'high'      THEN 1 ELSE 0 END) AS cnt_high,
        SUM(CASE WHEN c.priority = 'medium'    THEN 1 ELSE 0 END) AS cnt_medium,
        SUM(CASE WHEN c.priority = 'low'       THEN 1 ELSE 0 END) AS cnt_low,
        SUM(CASE WHEN c.status = 'escalated'   THEN 1 ELSE 0 END) AS cnt_escalated,
        SUM(CASE WHEN c.is_repeated = 1        THEN 1 ELSE 0 END) AS cnt_repeated,
        SUM(CASE WHEN c.sla_resolution_breach = 1 THEN 1 ELSE 0 END) AS cnt_sla,
        MIN(c.submitted_at) AS oldest_complaint,
        MAX(c.submitted_at) AS newest_complaint
    FROM complaints c
    JOIN areas a ON c.area_id = a.id
    JOIN wards w ON c.ward_id = w.id
    $whereSQL
    GROUP BY a.id, a.name, w.id, w.ward_no, w.name
    ORDER BY total_pending DESC, cnt_critical DESC
");
$stmt->execute($params);
$rows = $stmt->fetchAll();

// Summary query — also using CASE WHEN
$summaryStmt = $db->prepare("
    SELECT
        COUNT(*) AS grand_total,
        SUM(CASE WHEN c.status = 'submitted'          THEN 1 ELSE 0 END) AS submitted,
        SUM(CASE WHEN c.status = 'in_progress'        THEN 1 ELSE 0 END) AS in_progress,
        SUM(CASE WHEN c.status = 'escalated'          THEN 1 ELSE 0 END) AS escalated,
        SUM(CASE WHEN c.sla_resolution_breach = 1     THEN 1 ELSE 0 END) AS sla_breached
    FROM complaints c
    $whereSQL
");
$summaryStmt->execute($params);
$summary = $summaryStmt->fetch();

$areas = array_map(function ($r) {
    return [
        'area_id'       => (int)$r['area_id'],
        'area_name'     => $r['area_name'],
        'ward_id'       => (int)$r['ward_id'],
        'ward_no'       => $r['ward_no'],
        'ward_name'     => $r['ward_name'],
        'total_pending' => (int)$r['total_pending'],
        'by_priority'   => [
            'critical' => (int)$r['cnt_critical'],
            'high'     => (int)$r['cnt_high'],
            'medium'   => (int)$r['cnt_medium'],
            'low'      => (int)$r['cnt_low'],
        ],
        'escalated'        => (int)$r['cnt_escalated'],
        'repeated'         => (int)$r['cnt_repeated'],
        'sla_breached'     => (int)$r['cnt_sla'],
        'oldest_complaint' => $r['oldest_complaint'],
        'newest_complaint' => $r['newest_complaint'],
    ];
}, $rows);

$response = [
    'status' => 'success',
    'generated_at' => date('Y-m-d H:i:s'),
    'filters' => [
        'ward_id' => $wardId ?: null,
        'status'  => $status ?: 'all_pending',
    ],
    'summary' => [
        'grand_total' => (int)$summary['grand_total'],
        'submitted'   => (int)$summary['submitted'],
        'in_progress' => (int)$summary['in_progress'],
        'escalated'   => (int)$summary['escalated'],
        'sla_breached'=> (int)$summary['sla_breached'],
    ],
    'areas' => $areas,
];

echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);