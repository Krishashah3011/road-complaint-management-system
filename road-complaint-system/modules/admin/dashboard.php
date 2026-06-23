<?php
// ============================================================
// modules/admin/dashboard.php — Admin Dashboard
// ============================================================
require_once __DIR__ . '/../../includes/auth.php';
requireRole('admin');

$db = getDB();

function getStat($db, $sql, $params = []) {
    $s = $db->prepare($sql); $s->execute($params); return $s->fetchColumn() ?: 0;
}

$stats = [
    'total'       => getStat($db, "SELECT COUNT(*) FROM complaints"),
    'submitted'   => getStat($db, "SELECT COUNT(*) FROM complaints WHERE status = 'submitted'"),
    'in_progress' => getStat($db, "SELECT COUNT(*) FROM complaints WHERE status = 'in_progress'"),
    'resolved'    => getStat($db, "SELECT COUNT(*) FROM complaints WHERE status = 'resolved'"),
    'escalated'   => getStat($db, "SELECT COUNT(*) FROM complaints WHERE status = 'escalated'"),
    'reopened'    => getStat($db, "SELECT COUNT(*) FROM complaints WHERE status = 'reopened'"),
    'repeated'    => getStat($db, "SELECT COUNT(*) FROM complaints WHERE is_repeated = 1"),
    'sla_breach'  => getStat($db, "SELECT COUNT(*) FROM complaints WHERE sla_response_breach = 1 OR sla_resolution_breach = 1"),
];

$recent = $db->query("
    SELECT c.*, cat.name AS cat, w.name AS ward, u.name AS by_name
    FROM complaints c
    JOIN categories cat ON c.category_id = cat.id
    JOIN wards w ON c.ward_id = w.id
    JOIN users u ON c.submitted_by = u.id
    ORDER BY c.submitted_at DESC LIMIT 10
")->fetchAll();

$areaPending = $db->query("
    SELECT a.name AS area_name, COUNT(*) AS cnt
    FROM complaints c
    JOIN areas a ON c.area_id = a.id
    WHERE c.status NOT IN ('resolved','closed')
    GROUP BY c.area_id ORDER BY cnt DESC LIMIT 8
")->fetchAll();

$staffLoad = $db->query("
    SELECT u.name, COUNT(c.id) AS cnt
    FROM users u
    LEFT JOIN complaints c ON c.assigned_to = u.id AND c.status NOT IN ('resolved','closed')
    WHERE u.role_id = 2
    GROUP BY u.id ORDER BY cnt DESC
")->fetchAll();

$reopened = $db->query("
    SELECT c.complaint_no, c.title, a.name AS area, c.submitted_at
    FROM complaints c JOIN areas a ON c.area_id = a.id
    WHERE c.status = 'reopened' ORDER BY c.updated_at DESC LIMIT 5
")->fetchAll();

$pageTitle = 'Admin Dashboard';
include __DIR__ . '/../../includes/header.php';
?>
<script>window.APP_URL = '<?= APP_URL ?>';</script>

<div class="page-header">
  <div class="container flex-between" style="flex-wrap:wrap;gap:1rem;">
    <div>
      <h1><i class="fa fa-gauge"></i> Admin Dashboard</h1>
      <p>Overview of all road complaints and resolution status</p>
    </div>
    <div style="display:flex;gap:0.5rem;">
      <a href="<?= APP_URL ?>/modules/complaints/list.php" class="btn btn-outline">All Complaints</a>
      <a href="<?= APP_URL ?>/reports/reopened.php" class="btn btn-primary"><i class="fa fa-chart-bar"></i> Reports</a>
    </div>
  </div>
</div>

<div class="container">

<!-- Stats — each card links to filtered complaints -->
  <div class="stats-grid">
    <a href="<?= APP_URL ?>/modules/complaints/list.php" class="stat-card" style="text-decoration:none;cursor:pointer;">
      <div class="stat-icon"><i class="fa fa-list"></i></div>
      <div class="stat-value"><?= $stats['total'] ?></div>
      <div class="stat-label">Total Complaints</div>
    </a>
    <a href="<?= APP_URL ?>/modules/complaints/list.php?status=submitted" class="stat-card blue" style="text-decoration:none;cursor:pointer;">
      <div class="stat-icon"><i class="fa fa-inbox"></i></div>
      <div class="stat-value"><?= $stats['submitted'] ?></div>
      <div class="stat-label">Awaiting Action</div>
    </a>
    <a href="<?= APP_URL ?>/modules/complaints/list.php?status=in_progress" class="stat-card orange" style="text-decoration:none;cursor:pointer;">
      <div class="stat-icon"><i class="fa fa-cogs"></i></div>
      <div class="stat-value"><?= $stats['in_progress'] ?></div>
      <div class="stat-label">In Progress</div>
    </a>
    <a href="<?= APP_URL ?>/modules/complaints/list.php?status=resolved" class="stat-card green" style="text-decoration:none;cursor:pointer;">
      <div class="stat-icon"><i class="fa fa-check-circle"></i></div>
      <div class="stat-value"><?= $stats['resolved'] ?></div>
      <div class="stat-label">Resolved</div>
    </a>
    <a href="<?= APP_URL ?>/modules/complaints/list.php?status=escalated" class="stat-card red" style="text-decoration:none;cursor:pointer;">
      <div class="stat-icon"><i class="fa fa-arrow-up"></i></div>
      <div class="stat-value"><?= $stats['escalated'] ?></div>
      <div class="stat-label">Escalated</div>
    </a>
    <a href="<?= APP_URL ?>/modules/complaints/list.php?status=reopened" class="stat-card red" style="text-decoration:none;cursor:pointer;">
      <div class="stat-icon"><i class="fa fa-rotate-left"></i></div>
      <div class="stat-value"><?= $stats['reopened'] ?></div>
      <div class="stat-label">Reopened</div>
    </a>
    <a href="<?= APP_URL ?>/modules/complaints/list.php?repeated=1" class="stat-card orange" style="text-decoration:none;cursor:pointer;">
      <div class="stat-icon"><i class="fa fa-rotate"></i></div>
      <div class="stat-value"><?= $stats['repeated'] ?></div>
      <div class="stat-label">Repeated</div>
    </a>
    <a href="<?= APP_URL ?>/modules/complaints/list.php?sla_breach=1" class="stat-card red" style="text-decoration:none;cursor:pointer;">
      <div class="stat-icon"><i class="fa fa-clock"></i></div>
      <div class="stat-value"><?= $stats['sla_breach'] ?></div>
      <div class="stat-label">SLA Breached</div>
    </a>
  </div>

  <div class="dashboard-grid" style="align-items:start;">

    <!-- Left column -->
    <div style="display:flex;flex-direction:column;gap:1rem;">

      <!-- Recent Complaints -->
      <div class="card">
        <div class="card-header">
          <h3><i class="fa fa-clock-rotate-left"></i> Recent Complaints</h3>
          <a href="<?= APP_URL ?>/modules/complaints/list.php" class="btn btn-outline btn-sm">View All</a>
        </div>
        <div class="table-wrapper">
          <table>
            <thead><tr><th>No</th><th>Title</th><th>Category</th><th>Status</th><th>Filed</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($recent as $c): ?>
            <tr>
              <td><a href="<?= APP_URL ?>/modules/complaints/view.php?id=<?= $c['id'] ?>" style="color:var(--accent);font-weight:600;"><?= $c['complaint_no'] ?></a></td>
              <td style="max-width:150px;font-size:0.85rem;"><?= htmlspecialchars(mb_strimwidth($c['title'],0,40,'…')) ?></td>
              <td style="font-size:0.8rem;"><?= htmlspecialchars($c['cat']) ?></td>
              <td><span class="badge badge-<?= $c['status'] ?>"><?= ucwords(str_replace('_',' ',$c['status'])) ?></span></td>
              <td style="font-size:0.8rem;"><?= date('d M', strtotime($c['submitted_at'])) ?></td>
              <td><a href="<?= APP_URL ?>/modules/admin/assign.php?id=<?= $c['id'] ?>" class="btn btn-sm btn-secondary"><i class="fa fa-user-plus"></i></a></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($recent)): ?>
            <tr><td colspan="6" style="text-align:center;padding:1.5rem;color:var(--text-muted);">No complaints yet.</td></tr>
            <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Pending by Area -->
      <div class="card">
        <div class="card-header">
          <h3><i class="fa fa-map-location"></i> Pending Complaints by Area</h3>
          <a href="<?= APP_URL ?>/modules/complaints/list.php?status=submitted" class="btn btn-outline btn-sm">
            <i class="fa fa-arrow-right"></i> View Pending
          </a>
        </div>
        <?php $maxVal = $areaPending ? max(array_column($areaPending, 'cnt')) : 1; ?>
        <div class="bar-chart">
          <?php foreach ($areaPending as $ap): ?>
          <div class="bar-row">
            <span class="bar-label"><?= htmlspecialchars(mb_strimwidth($ap['area_name'],0,20,'…')) ?></span>
            <div class="bar-track">
              <div class="bar-fill" style="width:<?= ($ap['cnt']/$maxVal*100) ?>%"></div>
            </div>
            <span class="bar-count"><?= $ap['cnt'] ?></span>
          </div>
          <?php endforeach; ?>
          <?php if (empty($areaPending)): ?>
          <p style="text-align:center;color:var(--text-muted);padding:1rem 0;font-size:0.875rem;">No pending complaints.</p>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Right column -->
    <div style="display:flex;flex-direction:column;gap:1rem;">

      <!-- Staff Workload -->
      <div class="card">
        <div class="card-header"><h3><i class="fa fa-users"></i> Staff Workload</h3></div>
        <div style="display:flex;flex-direction:column;gap:0.6rem;">
          <?php foreach ($staffLoad as $s): ?>
          <div style="display:flex;justify-content:space-between;align-items:center;padding:0.5rem 0;border-bottom:1px solid var(--border);">
            <span style="font-weight:500;font-size:0.9rem;">
              <i class="fa fa-user" style="color:var(--text-muted);margin-right:0.4rem;"></i>
              <?= htmlspecialchars($s['name']) ?>
            </span>
            <span style="background:rgba(230,57,70,0.1);color:var(--accent);padding:0.15rem 0.6rem;border-radius:999px;font-size:0.8rem;font-weight:700;">
              <?= $s['cnt'] ?> open
            </span>
          </div>
          <?php endforeach; ?>
          <?php if (empty($staffLoad)): ?>
          <p style="color:var(--text-muted);font-size:0.875rem;">No staff registered.</p>
          <?php endif; ?>
        </div>
        <div style="margin-top:1rem;">
          <a href="<?= APP_URL ?>/modules/admin/users.php" class="btn btn-outline btn-sm w-full" style="justify-content:center;">
            <i class="fa fa-cog"></i> Manage Users
          </a>
        </div>
      </div>

      <!-- Reopened Summary -->
      <div class="card">
        <div class="card-header">
          <h3><i class="fa fa-rotate-left" style="color:var(--accent);"></i> Reopened Complaints</h3>
          <a href="<?= APP_URL ?>/reports/reopened.php" class="btn btn-outline btn-sm">Full Report</a>
        </div>
        <?php if (empty($reopened)): ?>
        <p style="color:var(--text-muted);text-align:center;padding:1rem 0;font-size:0.875rem;">No reopened complaints. 🎉</p>
        <?php else: ?>
        <div style="display:flex;flex-direction:column;gap:0.5rem;">
          <?php foreach ($reopened as $r): ?>
          <div style="padding:0.6rem;background:rgba(220,38,38,0.04);border:1px solid rgba(220,38,38,0.15);border-radius:6px;">
            <div style="font-weight:600;font-size:0.875rem;color:var(--accent);"><?= htmlspecialchars($r['complaint_no']) ?></div>
            <div style="font-size:0.82rem;color:var(--text-muted);"><?= htmlspecialchars(mb_strimwidth($r['title'],0,45,'…')) ?></div>
            <div style="font-size:0.78rem;color:var(--text-light);"><?= htmlspecialchars($r['area']) ?></div>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>
    </div>

  </div>

</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>