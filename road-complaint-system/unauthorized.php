<?php
// ============================================================
// unauthorized.php — 403 Access Denied Page
// ============================================================
require_once __DIR__ . '/includes/auth.php';
http_response_code(403);
$pageTitle = 'Access Denied';
include __DIR__ . '/includes/header.php';
?>
<script>window.APP_URL = '<?= APP_URL ?>';</script>

<div style="min-height:60vh;display:flex;align-items:center;justify-content:center;padding:2rem;">
  <div style="text-align:center;max-width:460px;">

    <div style="font-size:5rem;line-height:1;margin-bottom:1rem;">
      <i class="fa fa-lock" style="color:var(--accent);"></i>
    </div>

    <h1 style="font-family:'Syne',sans-serif;font-size:2.5rem;font-weight:800;color:var(--text);margin-bottom:0.5rem;">
      403
    </h1>
    <h2 style="font-size:1.2rem;font-weight:600;color:var(--text-muted);margin-bottom:1rem;">
      Access Denied
    </h2>
    <p style="color:var(--text-muted);font-size:0.95rem;line-height:1.6;margin-bottom:2rem;">
      You don't have permission to access this page.
      This area may be restricted to a specific role such as Admin or Staff.
    </p>

    <div style="display:flex;gap:0.75rem;justify-content:center;flex-wrap:wrap;">
      <?php if (isLoggedIn()):
        $u = getCurrentUser();
        if ($u['role'] === 'admin')     $home = APP_URL . '/modules/admin/dashboard.php';
        elseif ($u['role'] === 'staff') $home = APP_URL . '/modules/staff/dashboard.php';
        else                            $home = APP_URL . '/modules/users/dashboard.php';
      ?>
        <a href="<?= $home ?>" class="btn btn-primary btn-lg">
          <i class="fa fa-gauge"></i> Go to Dashboard
        </a>
      <?php else: ?>
        <a href="<?= APP_URL ?>/login.php" class="btn btn-primary btn-lg">
          <i class="fa fa-sign-in-alt"></i> Login
        </a>
      <?php endif; ?>
      <a href="<?= APP_URL ?>/index.php" class="btn btn-outline btn-lg">
        <i class="fa fa-home"></i> Home
      </a>
    </div>

  </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
