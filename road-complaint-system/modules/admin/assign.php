<?php
// ============================================================
// modules/admin/assign.php — Assign Complaint to Staff
// ============================================================
require_once __DIR__ . '/../../includes/auth.php';
requireRole('admin');

$db  = getDB();
$id  = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: ' . APP_URL . '/modules/complaints/list.php'); exit; }

$complaint = $db->prepare("SELECT c.*, cat.name AS cat_name, a.name AS area_name FROM complaints c JOIN categories cat ON c.category_id=cat.id JOIN areas a ON c.area_id=a.id WHERE c.id=?");
$complaint->execute([$id]);
$c = $complaint->fetch();
if (!$c) { header('Location: ' . APP_URL . '/modules/complaints/list.php'); exit; }

$staffList = $db->query("SELECT * FROM users WHERE role_id = 2 AND is_active = 1 ORDER BY name")->fetchAll();
$error = $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $staffId = (int)($_POST['staff_id'] ?? 0);
    $note    = sanitize($_POST['note'] ?? '');
    if (!$staffId) {
        $error = 'Please select a staff member.';
    } else {
        // Update complaint
        $db->prepare("UPDATE complaints SET assigned_to = ?, status = 'assigned' WHERE id = ?")
           ->execute([$staffId, $id]);

        // Mark old assignments as not current
        $db->prepare("UPDATE assignments SET is_current = 0 WHERE complaint_id = ?")
           ->execute([$id]);

        // New assignment
        $db->prepare("INSERT INTO assignments (complaint_id, assigned_to, assigned_by, note) VALUES (?,?,?,?)")
           ->execute([$id, $staffId, $_SESSION['user_id'], $note]);

        // History
        $db->prepare("INSERT INTO complaint_history (complaint_id, old_status, new_status, remark, action_by) VALUES (?,?,?,'Complaint assigned to staff',?)")
           ->execute([$id, $c['status'], 'assigned', $_SESSION['user_id']]);

        header("Location: " . APP_URL . "/modules/complaints/view.php?id={$id}&success=assigned");
        exit;
    }
}

$pageTitle = 'Assign Complaint';
include __DIR__ . '/../../includes/header.php';
?>
<script>window.APP_URL = '<?= APP_URL ?>';</script>

<div class="page-header">
  <div class="container">
    <h1><i class="fa fa-user-plus"></i> Assign Complaint</h1>
    <p>Assign complaint <?= htmlspecialchars($c['complaint_no']) ?> to a field staff member</p>
  </div>
</div>

<div class="container" style="max-width:600px;">
  <?php if ($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>

  <div class="card mb-2" style="background:rgba(230,57,70,0.04);border-color:rgba(230,57,70,0.2);">
    <div class="info-list">
      <div class="info-row"><span class="info-label">Complaint No</span><span class="info-value" style="font-weight:600;color:var(--accent);"><?= htmlspecialchars($c['complaint_no']) ?></span></div>
      <div class="info-row"><span class="info-label">Title</span><span class="info-value"><?= htmlspecialchars($c['title']) ?></span></div>
      <div class="info-row"><span class="info-label">Category</span><span class="info-value"><?= htmlspecialchars($c['cat_name']) ?></span></div>
      <div class="info-row"><span class="info-label">Area</span><span class="info-value"><?= htmlspecialchars($c['area_name']) ?></span></div>
      <div class="info-row"><span class="info-label">Current Status</span><span class="info-value"><span class="badge badge-<?= $c['status'] ?>"><?= ucwords(str_replace('_',' ',$c['status'])) ?></span></span></div>
    </div>
  </div>

  <div class="card">
    <form method="POST">
      <input type="hidden" name="csrf" value="<?= generateCSRF() ?>">
      <div class="form-group">
        <label>Assign To Staff <span class="required">*</span></label>
        <select name="staff_id" class="form-control" required>
          <option value="">Select Staff Member</option>
          <?php foreach ($staffList as $s): ?>
          <?php
            $openCount = $db->prepare("SELECT COUNT(*) FROM complaints WHERE assigned_to=? AND status NOT IN ('resolved','closed')");
            $openCount->execute([$s['id']]);
            $cnt = $openCount->fetchColumn();
          ?>
          <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?> (<?= $s['email'] ?>) — <?= $cnt ?> open</option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label>Note / Instructions</label>
        <textarea name="note" class="form-control" rows="3" placeholder="Any special instructions for the staff…"></textarea>
      </div>
      <div style="display:flex;gap:0.75rem;">
        <a href="<?= APP_URL ?>/modules/complaints/view.php?id=<?= $id ?>" class="btn btn-outline">Cancel</a>
        <button type="submit" class="btn btn-primary"><i class="fa fa-check"></i> Assign Complaint</button>
      </div>
    </form>
  </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
