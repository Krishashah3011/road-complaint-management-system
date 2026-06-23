<?php
// ============================================================
// modules/complaints/list.php — View All Complaints
// ============================================================
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

$db   = getDB();
$user = getCurrentUser();

$params = [];
$where  = [];

if ($user['role'] === 'user') {
    $where[] = 'c.submitted_by = ?';
    $params[] = $user['id'];
} elseif ($user['role'] === 'staff') {
    $where[] = 'c.assigned_to = ?';
    $params[] = $user['id'];
}

// Filter from query string
$filterStatus   = $_GET['status']   ?? '';
$filterCategory = $_GET['category'] ?? '';
$filterPriority = $_GET['priority'] ?? '';
$filterRepeated = $_GET['repeated'] ?? '';
$filterSLA      = $_GET['sla_breach'] ?? '';
$search         = trim($_GET['q']   ?? '');

if ($filterStatus)   { $where[] = 'c.status = ?';      $params[] = $filterStatus; }
if ($filterCategory) { $where[] = 'c.category_id = ?'; $params[] = $filterCategory; }
if ($filterPriority) { $where[] = 'c.priority = ?';    $params[] = $filterPriority; }
if ($filterRepeated) { $where[] = 'c.is_repeated = 1'; }
if ($filterSLA)      { $where[] = '(c.sla_response_breach = 1 OR c.sla_resolution_breach = 1)'; }
if ($search) {
    $where[] = '(c.complaint_no LIKE ? OR c.title LIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$perPage = 15;
$page    = max(1, (int)($_GET['page'] ?? 1));
$offset  = ($page - 1) * $perPage;

$countStmt = $db->prepare("SELECT COUNT(*) FROM complaints c $whereSQL");
$countStmt->execute($params);
$total      = $countStmt->fetchColumn();
$totalPages = ceil($total / $perPage);

$params[] = $perPage;
$params[] = $offset;

$stmt = $db->prepare("
    SELECT c.*,
           cat.name AS category_name,
           w.name AS ward_name,
           a.name AS area_name,
           s.name AS spot_name,
           u.name AS submitted_by_name,
           st.name AS assigned_to_name,
           TIMESTAMPDIFF(HOUR, c.submitted_at, NOW()) AS hours_since_submit
    FROM complaints c
    JOIN categories cat ON c.category_id = cat.id
    JOIN wards w ON c.ward_id = w.id
    JOIN areas a ON c.area_id = a.id
    JOIN spots s ON c.spot_id = s.id
    JOIN users u ON c.submitted_by = u.id
    LEFT JOIN users st ON c.assigned_to = st.id
    $whereSQL
    ORDER BY c.submitted_at DESC
    LIMIT ? OFFSET ?
");
$stmt->execute($params);
$complaints = $stmt->fetchAll();

$categories = $db->query("SELECT * FROM categories WHERE is_active=1 ORDER BY name")->fetchAll();

$pageTitle = 'Complaints';
include __DIR__ . '/../../includes/header.php';
?>
<script>window.APP_URL = '<?= APP_URL ?>';</script>

<div class="page-header">
  <div class="container flex-between" style="flex-wrap:wrap;gap:1rem;">
    <div>
      <h1><i class="fa fa-list"></i>
        <?php if ($user['role'] === 'user'): ?>My Complaints
        <?php elseif ($user['role'] === 'staff'): ?>Assigned Complaints
        <?php else: ?>All Complaints
        <?php endif; ?>
      </h1>
      <p><?= $total ?> total complaint<?= $total != 1 ? 's' : '' ?> found</p>
    </div>
    <?php if ($user['role'] === 'user'): ?>
    <a href="<?= APP_URL ?>/modules/complaints/add.php" class="btn btn-primary">
      <i class="fa fa-plus"></i> File New Complaint
    </a>
    <?php endif; ?>
  </div>
</div>

<div class="container">

  <!-- Filter Bar — all inline, Filter + Clear side by side -->
  <form method="GET" style="background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius);padding:1rem 1.25rem;margin-bottom:1.5rem;">
    <div style="display:flex;gap:0.75rem;flex-wrap:wrap;align-items:flex-end;">
      <div class="form-group" style="flex:2;min-width:180px;margin-bottom:0;">
        <label style="font-size:0.78rem;font-weight:600;color:var(--text-muted);text-transform:uppercase;letter-spacing:.05em;">Search</label>
        <input type="text" name="q" class="form-control" id="searchInput"
               placeholder="Title or complaint no…"
               value="<?= htmlspecialchars($search) ?>">
      </div>
      <div class="form-group" style="min-width:130px;margin-bottom:0;">
        <label style="font-size:0.78rem;font-weight:600;color:var(--text-muted);text-transform:uppercase;letter-spacing:.05em;">Status</label>
        <select name="status" class="form-control">
          <option value="">All Status</option>
          <?php foreach (['submitted','verified','assigned','in_progress','resolved','closed','reopened','escalated'] as $s): ?>
          <option value="<?= $s ?>" <?= $filterStatus === $s ? 'selected' : '' ?>><?= ucwords(str_replace('_',' ',$s)) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group" style="min-width:140px;margin-bottom:0;">
        <label style="font-size:0.78rem;font-weight:600;color:var(--text-muted);text-transform:uppercase;letter-spacing:.05em;">Category</label>
        <select name="category" class="form-control">
          <option value="">All Categories</option>
          <?php foreach ($categories as $c): ?>
          <option value="<?= $c['id'] ?>" <?= $filterCategory == $c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group" style="min-width:120px;margin-bottom:0;">
        <label style="font-size:0.78rem;font-weight:600;color:var(--text-muted);text-transform:uppercase;letter-spacing:.05em;">Priority</label>
        <select name="priority" class="form-control">
          <option value="">All Priorities</option>
          <option value="low"      <?= $filterPriority==='low'      ?'selected':'' ?>>Low</option>
          <option value="medium"   <?= $filterPriority==='medium'   ?'selected':'' ?>>Medium</option>
          <option value="high"     <?= $filterPriority==='high'     ?'selected':'' ?>>High</option>
          <option value="critical" <?= $filterPriority==='critical' ?'selected':'' ?>>Critical</option>
        </select>
      </div>
      <div style="display:flex;gap:0.5rem;align-items:flex-end;">
        <button type="submit" class="btn btn-secondary" style="height:38px;"><i class="fa fa-filter"></i> Filter</button>
        <a href="?" class="btn btn-outline" style="height:38px;display:inline-flex;align-items:center;"><i class="fa fa-xmark"></i> Clear</a>
      </div>
    </div>
  </form>

  <!-- Table -->
  <div class="card" style="padding:0;">
    <div class="table-wrapper">
      <table>
        <thead>
          <tr>
            <th>#</th>
            <th>Complaint No</th>
            <th>Title</th>
            <th>Category</th>
            <th>Location</th>
            <th>Priority</th>
            <th>Status</th>
            <th>Filed On</th>
            <?php if ($user['role'] !== 'user'): ?><th>Filed By</th><?php endif; ?>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
        <?php if (empty($complaints)): ?>
          <tr><td colspan="10" style="text-align:center;padding:2rem;color:var(--text-muted);">
            <i class="fa fa-inbox" style="font-size:2rem;display:block;margin-bottom:0.5rem;"></i>No complaints found.
          </td></tr>
        <?php else: ?>
          <?php foreach ($complaints as $i => $c): ?>
          <tr>
            <td style="color:var(--text-muted);"><?= $offset + $i + 1 ?></td>
            <td>
              <a href="<?= APP_URL ?>/modules/complaints/view.php?id=<?= $c['id'] ?>" style="font-weight:600;color:var(--accent);">
                <?= htmlspecialchars($c['complaint_no']) ?>
              </a>
              <?php if ($c['is_repeated']): ?>
              <br><span class="badge badge-repeated"><i class="fa fa-rotate"></i> Repeated</span>
              <?php endif; ?>
            </td>
            <td style="max-width:200px;">
              <span title="<?= htmlspecialchars($c['title']) ?>">
                <?= htmlspecialchars(mb_strimwidth($c['title'], 0, 50, '…')) ?>
              </span>
            </td>
            <td><?= htmlspecialchars($c['category_name']) ?></td>
            <td style="font-size:0.82rem;color:var(--text-muted);"><?= htmlspecialchars($c['area_name']) ?></td>
            <td><span class="priority-badge priority-<?= $c['priority'] ?>"><?= ucfirst($c['priority']) ?></span></td>
            <td>
              <span class="badge badge-<?= $c['status'] ?>"><?= ucwords(str_replace('_',' ',$c['status'])) ?></span>
              <?php if ($c['sla_response_breach'] || ($c['hours_since_submit'] > SLA_INITIAL_RESPONSE && $c['status'] === 'submitted')): ?>
              <br><span class="badge badge-sla-breach"><i class="fa fa-clock"></i> SLA</span>
              <?php endif; ?>
            </td>
            <td style="font-size:0.82rem;white-space:nowrap;"><?= date('d M Y', strtotime($c['submitted_at'])) ?></td>
            <?php if ($user['role'] !== 'user'): ?>
            <td style="font-size:0.82rem;"><?= htmlspecialchars($c['submitted_by_name']) ?></td>
            <?php endif; ?>
            <td>
              <div class="table-actions">
                <a href="<?= APP_URL ?>/modules/complaints/view.php?id=<?= $c['id'] ?>" class="btn btn-sm btn-outline" title="View">
                  <i class="fa fa-eye"></i>
                </a>
                <?php if ($user['role'] === 'admin'): ?>
                <a href="<?= APP_URL ?>/modules/admin/assign.php?id=<?= $c['id'] ?>" class="btn btn-sm btn-secondary" title="Assign">
                  <i class="fa fa-user-plus"></i>
                </a>
                <?php endif; ?>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Pagination -->
  <?php if ($totalPages > 1): ?>
  <div class="pagination">
    <?php for ($p = 1; $p <= $totalPages; $p++): ?>
      <?php $qp = $_GET; $qp['page'] = $p; $qs = http_build_query($qp); ?>
      <a href="?<?= $qs ?>" class="page-link <?= $p === $page ? 'active' : '' ?>"><?= $p ?></a>
    <?php endfor; ?>
  </div>
  <?php endif; ?>

</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>