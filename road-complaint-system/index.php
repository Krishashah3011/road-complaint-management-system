<?php
// ============================================================
// index.php — Home Page / Landing Page
// ============================================================
require_once __DIR__ . '/includes/auth.php';

// If logged in, redirect to appropriate dashboard
if (isLoggedIn()) {
    $user = getCurrentUser();
    if ($user['role'] === 'admin') {
        header('Location: ' . APP_URL . '/modules/admin/dashboard.php');
    } elseif ($user['role'] === 'staff') {
        header('Location: ' . APP_URL . '/modules/staff/dashboard.php');
    } else {
        header('Location: ' . APP_URL . '/modules/users/dashboard.php');
    }
    exit;
}

$pageTitle = 'Home';
include __DIR__ . '/includes/header.php';
?>
<script>window.APP_URL = '<?= APP_URL ?>';</script>

<!-- Hero Section -->
<section style="background: linear-gradient(135deg, #1a1a2e 0%, #16213e 60%, #0f3460 100%); color:#fff; padding:5rem 1.5rem; text-align:center;">
  <div style="max-width:700px;margin:0 auto;">
    <div style="font-size:4rem;margin-bottom:1rem;"><i class="fa-solid fa-road" style="color:#e63946;"></i></div>
    <h1 style="font-family:'Syne',sans-serif;font-size:2.8rem;font-weight:800;color:#fff;margin-bottom:1rem;">
      Road & Pathway<br>Complaint Portal
    </h1>
    <p style="color:rgba(255,255,255,0.75);font-size:1.1rem;margin-bottom:2rem;">
      Report road damage, potholes, waterlogging and pathway issues in your ward. Track resolution in real-time.
    </p>
    <div style="display:flex;gap:1rem;justify-content:center;flex-wrap:wrap;">
      <a href="<?= APP_URL ?>/login.php" class="btn btn-primary btn-lg"><i class="fa fa-sign-in-alt"></i> Login</a>
      <a href="<?= APP_URL ?>/register.php" class="btn btn-lg" style="background:rgba(255,255,255,0.15);color:#fff;border:1px solid rgba(255,255,255,0.3);">
        <i class="fa fa-user-plus"></i> Register as Citizen
      </a>
    </div>
  </div>
</section>

<!-- Track Complaint Section -->
<section style="padding:3rem 1.5rem;background:var(--bg);">
  <div class="container">
    <div class="tracker-box" style="max-width:500px;">
      <div class="card">
        <div class="card-header">
          <h3><i class="fa fa-magnifying-glass" style="color:var(--accent);"></i> Track Your Complaint</h3>
        </div>
        <p style="color:var(--text-muted);margin-bottom:1rem;font-size:0.9rem;">Enter your complaint number to check status</p>
        <div style="display:flex;gap:0.5rem;">
          <input type="text" id="trackInput" class="form-control" placeholder="e.g. CMP202512345" style="flex:1;">
          <button class="btn btn-primary" id="trackBtn"><i class="fa fa-search"></i></button>
        </div>
        <div id="trackResult" style="margin-top:1rem;"></div>
      </div>
    </div>
  </div>
</section>

<!-- Features Section -->
<section style="padding:3rem 1.5rem;background:var(--bg-card);border-top:1px solid var(--border);">
  <div class="container">
    <h2 style="text-align:center;margin-bottom:2rem;">How It Works</h2>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1.5rem;">
      <?php
      $steps = [
        ['fa-user-plus','Register','Create a citizen account to file complaints'],
        ['fa-file-alt','File Complaint','Describe the road issue with photo proof'],
        ['fa-cogs','Get Assigned','Admin assigns it to field staff'],
        ['fa-check-circle','Track & Resolve','Real-time updates till issue is resolved'],
      ];
      foreach ($steps as $i => [$icon, $title, $desc]):
      ?>
      <div style="text-align:center;padding:1.5rem;border:1px solid var(--border);border-radius:12px;">
        <div style="width:56px;height:56px;border-radius:50%;background:rgba(230,57,70,0.1);display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;font-size:1.4rem;color:var(--accent);">
          <i class="fa <?= $icon ?>"></i>
        </div>
        <h4 style="margin-bottom:0.4rem;"><?= $title ?></h4>
        <p style="color:var(--text-muted);font-size:0.875rem;"><?= $desc ?></p>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<script>
$('#trackBtn').on('click', function () {
  const no = $('#trackInput').val().trim();
  if (!no) return;
  const $res = $('#trackResult');
  $res.html('<div class="alert alert-info"><span class="spinner"></span> Searching...</div>');
  $.getJSON('<?= APP_URL ?>/api/complaint_status.php', { no: no }, function (data) {
    if (data.error) {
      $res.html('<div class="alert alert-danger"><i class="fa fa-times-circle"></i> ' + data.error + '</div>');
    } else {
      $res.html(`
        <div class="card" style="margin-top:0;">
          <div class="flex-between mb-1">
            <strong>${data.complaint_no}</strong>
            <span class="badge badge-${data.status}">${data.status.replace(/_/g,' ')}</span>
          </div>
          <p style="font-size:0.875rem;color:var(--text-muted);">${data.title}</p>
          <p style="font-size:0.8rem;color:var(--text-light);margin-top:0.4rem;">Filed: ${data.submitted_at}</p>
          ${data.sla_response_breach ? '<div class="sla-warning mt-1"><i class="fa fa-clock"></i> Response SLA breached</div>' : ''}
        </div>
      `);
    }
  }).fail(function () {
    $res.html('<div class="alert alert-danger">Could not reach API. Try again.</div>');
  });
});

$('#trackInput').on('keypress', function (e) {
  if (e.which === 13) $('#trackBtn').click();
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
