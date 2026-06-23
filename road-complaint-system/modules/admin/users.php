<?php
// ============================================================
// modules/admin/users.php — Manage Users
// ============================================================
require_once __DIR__ . '/../../includes/auth.php';
requireRole('admin');

$db = getDB();
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'toggle') {
        $uid = (int)$_POST['uid'];
        $db->prepare("UPDATE users SET is_active = NOT is_active WHERE id = ? AND id != 1")
           ->execute([$uid]);
        $msg = 'User status updated.';
    } elseif ($action === 'add_staff') {
        $name  = sanitize($_POST['name'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $pass  = $_POST['password'] ?? '';
        if ($name && $email && strlen($pass) >= 6) {
            $chk = $db->prepare("SELECT id FROM users WHERE email = ?");
            $chk->execute([$email]);
            if ($chk->fetch()) {
                $msg = 'Email already exists.';
            } else {
                $db->prepare("INSERT INTO users (name, email, password, role_id) VALUES (?,?,?,2)")
                   ->execute([$name, $email, password_hash($pass, PASSWORD_DEFAULT)]);
                $msg = 'Staff member added.';
            }
        }
    }
}

$users = $db->query("SELECT u.*, r.name AS role_name FROM users u JOIN roles r ON u.role_id = r.id ORDER BY u.role_id, u.name")->fetchAll();

$pageTitle = 'Manage Users';
include __DIR__ . '/../../includes/header.php';
?>
<script>window.APP_URL = '<?= APP_URL ?>';</script>

<div class="page-header">
  <div class="container">
    <h1><i class="fa fa-users"></i> Manage Users</h1>
    <p>View, add, and toggle status of system users</p>
  </div>
</div>

<div class="container">
  <?php if ($msg): ?><div class="alert alert-success"><i class="fa fa-check-circle"></i> <?= htmlspecialchars($msg) ?></div><?php endif; ?>

  <div style="display:grid;grid-template-columns:2fr 1fr;gap:1.5rem;">
    <!-- Users Table -->
    <div class="card" style="padding:0;">
      <div class="card-header" style="padding:1rem 1.5rem;">
        <h3><i class="fa fa-table"></i> All Users</h3>
      </div>
      <div class="table-wrapper">
        <table>
          <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Action</th></tr></thead>
          <tbody>
          <?php foreach ($users as $u): ?>
          <tr>
            <td><?= htmlspecialchars($u['name']) ?></td>
            <td style="font-size:0.85rem;"><?= htmlspecialchars($u['email']) ?></td>
            <td><span class="badge" style="background:rgba(99,102,241,0.1);color:#6366f1;"><?= ucfirst($u['role_name']) ?></span></td>
            <td>
              <span class="badge <?= $u['is_active'] ? 'badge-resolved' : 'badge-closed' ?>">
                <?= $u['is_active'] ? 'Active' : 'Inactive' ?>
              </span>
            </td>
            <td>
              <?php if ($u['id'] != 1): ?>
              <form method="POST" style="display:inline;">
                <input type="hidden" name="action" value="toggle">
                <input type="hidden" name="uid" value="<?= $u['id'] ?>">
                <button type="submit" class="btn btn-sm <?= $u['is_active'] ? 'btn-warning' : 'btn-success' ?>">
                  <?= $u['is_active'] ? 'Deactivate' : 'Activate' ?>
                </button>
              </form>
              <?php else: ?>
              <span class="text-muted" style="font-size:0.8rem;">System Admin</span>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Add Staff -->
    <div class="card">
      <div class="card-header"><h3><i class="fa fa-user-plus"></i> Add Staff Member</h3></div>
      <form method="POST">
        <input type="hidden" name="action" value="add_staff">
        <div class="form-group">
          <label>Full Name <span class="required">*</span></label>
          <input type="text" name="name" class="form-control" required>
        </div>
        <div class="form-group">
          <label>Email <span class="required">*</span></label>
          <input type="email" name="email" class="form-control" required>
        </div>
        <div class="form-group">
          <label>Password <span class="required">*</span></label>
          <input type="password" name="password" class="form-control" placeholder="Min. 6 chars" required>
        </div>
        <button type="submit" class="btn btn-primary w-full" style="justify-content:center;">
          <i class="fa fa-plus"></i> Add Staff
        </button>
      </form>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
