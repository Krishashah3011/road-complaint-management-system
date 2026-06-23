<?php
// ============================================================
// modules/users/dashboard.php — Citizen Dashboard
// ============================================================
require_once __DIR__ . '/../../includes/auth.php';
requireRole('user');

$db  = getDB();
$uid = $_SESSION['user_id'];

// Stats for this user
function userStat($db, $sql, $params) {
    $s = $db->prepare($sql); $s->execute($params); return $s->fetchColumn() ?: 0;
}

$stats = [
    'total'      => userStat($db, "SELECT COUNT(*) FROM complaints WHERE submitted_by=?", [$uid]),
    'open'       => userStat($db, "SELECT COUNT(*) FROM complaints WHERE submitted_by=? AND status NOT IN('resolved','closed')", [$uid]),
    'resolved'   => userStat($db, "SELECT COUNT(*) FROM complaints WHERE submitted_by=? AND status='resolved'", [$uid]),
    'repeated'   => userStat($db, "SELECT COUNT(*) FROM complaints WHERE submitted_by=? AND is_repeated=1", [$uid]),
];

// Recent complaints
$recent = $db->prepare("
    SELECT c.*, cat.name AS cat_name, a.name AS area_name
    FROM complaints c
    JOIN categories cat ON c.category_id = cat.id
    JOIN areas a ON c.area_id = a.id
    WHERE c.submitted_by = ?
    ORDER BY c.submitted_at DESC
    LIMIT 8
");
$recent->execute([$uid]);
$complaints = $recent->fetchAll();

$pageTitle = 'My Dashboard';
include __DIR__ . '/../../includes/header.php';
?>
<script>window.APP_URL = '<?= APP_URL ?>';</script>

<div class="page-header">
  <div class="container flex-between" style="flex-wrap:wrap;gap:1rem;">
    <div>
      <h1><i class="fa fa-gauge"></i> My Dashboard</h1>
      <p>Welcome back, <strong><?= htmlspecialchars(getCurrentUser()['name']) ?></strong>! Track your filed complaints here.</p>
    </div>
    <a href="<?= APP_URL ?>/modules/complaints/add.php" class="btn btn-primary btn-lg">
      <i class="fa fa-plus"></i> File New Complaint
    </a>
  </div>
</div>

<div class="container">

<!-- Stats — clickable, each links to filtered complaints -->

  <div class="stats-grid" style="grid-template-columns:repeat(4,1fr);">
    <a href="<?= APP_URL ?>/modules/complaints/list.php" class="stat-card" style="text-decoration:none;cursor:pointer;">
      <div class="stat-icon"><i class="fa fa-list-check"></i></div>
      <div class="stat-value"><?= $stats['total'] ?></div>
      <div class="stat-label">Total Filed</div>
    </a>
    <a href="<?= APP_URL ?>/modules/complaints/list.php?status=in_progress" class="stat-card orange" style="text-decoration:none;cursor:pointer;">
      <div class="stat-icon"><i class="fa fa-hourglass-half"></i></div>
      <div class="stat-value"><?= $stats['open'] ?></div>
      <div class="stat-label">In Progress</div>
    </a>
    <a href="<?= APP_URL ?>/modules/complaints/list.php?status=resolved" class="stat-card green" style="text-decoration:none;cursor:pointer;">
      <div class="stat-icon"><i class="fa fa-circle-check"></i></div>
      <div class="stat-value"><?= $stats['resolved'] ?></div>
      <div class="stat-label">Resolved</div>
    </a>
    <a href="<?= APP_URL ?>/modules/complaints/list.php?repeated=1" class="stat-card red" style="text-decoration:none;cursor:pointer;">
      <div class="stat-icon"><i class="fa fa-rotate"></i></div>
      <div class="stat-value"><?= $stats['repeated'] ?></div>
      <div class="stat-label">Repeated</div>
    </a>
  </div>

  <!-- Quick Track -->
  <div class="card mb-2">
    <div class="card-header">
      <h3><i class="fa fa-magnifying-glass"></i> Quick Track</h3>
    </div>
    <div style="display:flex;gap:0.75rem;flex-wrap:wrap;">
      <input type="text" id="quickTrackInput" class="form-control" placeholder="Enter complaint number e.g. CMP202512345" style="flex:1;min-width:220px;">
      <button class="btn btn-primary" id="quickTrackBtn"><i class="fa fa-search"></i> Track</button>
    </div>
    <div id="quickTrackResult" style="margin-top:1rem;"></div>
  </div>

  <!-- My Complaints Table -->
  <div class="card" style="padding:0;">
    <div class="card-header" style="padding:1rem 1.5rem;">
      <h3><i class="fa fa-clipboard-list"></i> My Complaints</h3>
      <a href="<?= APP_URL ?>/modules/complaints/list.php" class="btn btn-outline btn-sm">View All</a>
    </div>

    <?php if (empty($complaints)): ?>
    <div style="text-align:center;padding:3rem 1rem;color:var(--text-muted);">
      <i class="fa fa-road" style="font-size:3rem;margin-bottom:1rem;opacity:0.3;"></i>
      <h3 style="font-size:1.1rem;margin-bottom:0.5rem;">No complaints yet</h3>
      <p style="font-size:0.875rem;">See a damaged road? File your first complaint.</p>
      <a href="<?= APP_URL ?>/modules/complaints/add.php" class="btn btn-primary mt-2">
        <i class="fa fa-plus"></i> File Complaint
      </a>
    </div>
    <?php else: ?>
    <div class="table-wrapper">
      <table>
        <thead>
          <tr>
            <th>Complaint No</th>
            <th>Title</th>
            <th>Category</th>
            <th>Area</th>
            <th>Priority</th>
            <th>Status</th>
            <th>Filed On</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($complaints as $c): ?>
        <tr>
          <td>
            <a href="<?= APP_URL ?>/modules/complaints/view.php?id=<?= $c['id'] ?>"
               style="font-weight:700;color:var(--accent);">
              <?= htmlspecialchars($c['complaint_no']) ?>
            </a>
            <?php if ($c['is_repeated']): ?>
            <br><span class="badge badge-repeated" style="font-size:0.68rem;"><i class="fa fa-rotate"></i> Repeated</span>
            <?php endif; ?>
          </td>
          <td style="max-width:180px;font-size:0.875rem;">
            <?= htmlspecialchars(mb_strimwidth($c['title'], 0, 45, '…')) ?>
          </td>
          <td style="font-size:0.82rem;"><?= htmlspecialchars($c['cat_name']) ?></td>
          <td style="font-size:0.82rem;color:var(--text-muted);"><?= htmlspecialchars($c['area_name']) ?></td>
          <td>
            <span class="priority-badge priority-<?= $c['priority'] ?>">
              <?= ucfirst($c['priority']) ?>
            </span>
          </td>
          <td>
            <span class="badge badge-<?= $c['status'] ?>">
              <?= ucwords(str_replace('_', ' ', $c['status'])) ?>
            </span>
            <?php if ($c['sla_resolution_breach']): ?>
            <br><span class="badge badge-sla-breach" style="font-size:0.68rem;"><i class="fa fa-clock"></i> SLA</span>
            <?php endif; ?>
          </td>
          <td style="font-size:0.82rem;white-space:nowrap;">
            <?= date('d M Y', strtotime($c['submitted_at'])) ?>
          </td>
          <td>
            <a href="<?= APP_URL ?>/modules/complaints/view.php?id=<?= $c['id'] ?>"
               class="btn btn-sm btn-outline">
              <i class="fa fa-eye"></i> View
            </a>
          </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>

</div>

<script>
// Quick Track functionality
$('#quickTrackBtn').on('click', function () {
  const no  = $('#quickTrackInput').val().trim();
  const $r  = $('#quickTrackResult');
  if (!no) return;

  $r.html('<div class="alert alert-info"><span class="spinner"></span> Searching…</div>');

  $.getJSON('<?= APP_URL ?>/api/complaint_status.php', { no: no }, function (data) {
    if (data.error) {
      $r.html('<div class="alert alert-danger"><i class="fa fa-times-circle"></i> ' + data.error + '</div>');
    } else {
      $r.html(`
        <div style="display:flex;flex-wrap:wrap;gap:1rem;align-items:center;
                    background:var(--bg);border:1px solid var(--border);
                    border-radius:10px;padding:1rem;">
          <div style="flex:1;min-width:200px;">
            <div style="font-family:'Syne',sans-serif;font-weight:700;font-size:1rem;color:var(--accent);">
              ${data.complaint_no}
            </div>
            <div style="font-size:0.875rem;margin-top:0.2rem;">${data.title}</div>
            <div style="font-size:0.78rem;color:var(--text-muted);margin-top:0.3rem;">
              Filed: ${data.submitted_at}
            </div>
          </div>
          <div style="display:flex;flex-direction:column;align-items:flex-end;gap:0.4rem;">
            <span class="badge badge-${data.status}">${data.status.replace(/_/g,' ')}</span>
            ${data.sla_resolution_breach ? '<span class="badge badge-sla-breach"><i class="fa fa-clock"></i> SLA Breached</span>' : ''}
            <a href="<?= APP_URL ?>/modules/complaints/view.php?id=${data.id}"
               class="btn btn-sm btn-primary">View Details</a>
          </div>
        </div>
      `);
    }
  }).fail(function () {
    $r.html('<div class="alert alert-danger">Could not reach server. Try again.</div>');
  });
});

$('#quickTrackInput').on('keypress', function (e) {
  if (e.which === 13) $('#quickTrackBtn').click();
});
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
