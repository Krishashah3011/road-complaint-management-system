<?php
// ============================================================
// modules/staff/dashboard.php — Staff Dashboard
// ============================================================
require_once __DIR__ . '/../../includes/auth.php';
requireRole('staff');

$db   = getDB();
$uid  = $_SESSION['user_id'];

$open     = $db->prepare("SELECT COUNT(*) FROM complaints WHERE assigned_to = ? AND status NOT IN ('resolved','closed')")->execute([$uid]);
$openCnt  = $db->prepare("SELECT COUNT(*) FROM complaints WHERE assigned_to=? AND status NOT IN('resolved','closed')"); $openCnt->execute([$uid]); $openCnt = $openCnt->fetchColumn();
$resCnt   = $db->prepare("SELECT COUNT(*) FROM complaints WHERE assigned_to=? AND status='resolved'"); $resCnt->execute([$uid]); $resCnt = $resCnt->fetchColumn();
$slaBreach= $db->prepare("SELECT COUNT(*) FROM complaints WHERE assigned_to=? AND (sla_response_breach=1 OR sla_resolution_breach=1)"); $slaBreach->execute([$uid]); $slaBreach=$slaBreach->fetchColumn();

$assigned = $db->prepare("
    SELECT c.*, cat.name AS cat_name, a.name AS area_name,
           TIMESTAMPDIFF(HOUR, c.submitted_at, NOW()) AS hrs
    FROM complaints c
    JOIN categories cat ON c.category_id=cat.id
    JOIN areas a ON c.area_id=a.id
    WHERE c.assigned_to = ? AND c.status NOT IN ('resolved','closed')
    ORDER BY
      FIELD(c.priority,'critical','high','medium','low'),
      c.submitted_at ASC
");
$assigned->execute([$uid]);
$tasks = $assigned->fetchAll();

$pageTitle = 'Staff Dashboard';
include __DIR__ . '/../../includes/header.php';
?>
<script>window.APP_URL = '<?= APP_URL ?>';</script>

<div class="page-header">
  <div class="container">
    <h1><i class="fa fa-hard-hat"></i> My Dashboard</h1>
    <p>Your assigned complaints and field tasks</p>
  </div>
</div>

<div class="container">

<div class="stats-grid" style="grid-template-columns:repeat(3,1fr);">
    <a href="<?= APP_URL ?>/modules/complaints/list.php" class="stat-card orange" style="text-decoration:none;cursor:pointer;">
      <div class="stat-icon"><i class="fa fa-clipboard-list"></i></div>
      <div class="stat-value"><?= $openCnt ?></div>
      <div class="stat-label">Open Tasks</div>
    </a>
    <a href="<?= APP_URL ?>/modules/complaints/list.php?status=resolved" class="stat-card green" style="text-decoration:none;cursor:pointer;">
      <div class="stat-icon"><i class="fa fa-check-double"></i></div>
      <div class="stat-value"><?= $resCnt ?></div>
      <div class="stat-label">Resolved</div>
    </a>
    <a href="<?= APP_URL ?>/modules/complaints/list.php?sla_breach=1" class="stat-card red" style="text-decoration:none;cursor:pointer;">
      <div class="stat-icon"><i class="fa fa-clock"></i></div>
      <div class="stat-value"><?= $slaBreach ?></div>
      <div class="stat-label">SLA Breach</div>
    </a>
  </div>

  <div class="card" style="padding:0;">
    <div class="card-header" style="padding:1rem 1.5rem;">
      <h3><i class="fa fa-tasks"></i> Assigned Complaints</h3>
      <span style="font-size:0.85rem;color:var(--text-muted);">Sorted by priority</span>
    </div>
    <div class="table-wrapper">
      <table>
        <thead>
          <tr><th>No</th><th>Title</th><th>Category</th><th>Area</th><th>Priority</th><th>Status</th><th>Time Open</th><th>Action</th></tr>
        </thead>
        <tbody>
        <?php if (empty($tasks)): ?>
          <tr><td colspan="8" style="text-align:center;padding:2rem;color:var(--text-muted);">
            <i class="fa fa-check-circle" style="font-size:2rem;color:#16a34a;"></i><br>No open tasks. All caught up!
          </td></tr>
        <?php else: ?>
          <?php foreach ($tasks as $t): ?>
          <tr <?= ($t['sla_response_breach'] || $t['sla_resolution_breach']) ? 'style="background:rgba(220,38,38,0.05);"' : '' ?>>
            <td><a href="<?= APP_URL ?>/modules/complaints/view.php?id=<?= $t['id'] ?>" style="color:var(--accent);font-weight:600;"><?= $t['complaint_no'] ?></a></td>
            <td style="max-width:160px;font-size:0.85rem;"><?= htmlspecialchars(mb_strimwidth($t['title'],0,40,'…')) ?></td>
            <td style="font-size:0.82rem;"><?= htmlspecialchars($t['cat_name']) ?></td>
            <td style="font-size:0.82rem;"><?= htmlspecialchars($t['area_name']) ?></td>
            <td><span class="priority-badge priority-<?= $t['priority'] ?>"><?= ucfirst($t['priority']) ?></span></td>
            <td><span class="badge badge-<?= $t['status'] ?>"><?= ucwords(str_replace('_',' ',$t['status'])) ?></span></td>
            <td style="font-size:0.82rem;">
              <?php echo $t['hrs'] > 48 ? '<span style="color:#dc2626;font-weight:700;">' . $t['hrs'] . 'h ⚠</span>' : $t['hrs'] . 'h'; ?>
            </td>
            <td>
              <a href="<?= APP_URL ?>/modules/complaints/view.php?id=<?= $t['id'] ?>" class="btn btn-sm btn-primary">
                <i class="fa fa-pen"></i> Update
              </a>
            </td>
          </tr>
          <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
