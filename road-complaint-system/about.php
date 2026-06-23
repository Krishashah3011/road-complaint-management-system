<?php
// ============================================================
// about.php — About Page
// ============================================================
require_once __DIR__ . '/includes/auth.php';
$pageTitle = 'About';
include __DIR__ . '/includes/header.php';
?>
<script>window.APP_URL = '<?= APP_URL ?>';</script>

<div class="page-header">
  <div class="container">
    <h1><i class="fa fa-circle-info"></i> About RoadFix Portal</h1>
    <p>A citizen-driven road complaint and resolution tracking system</p>
  </div>
</div>

<div class="container" style="max-width:860px;">

  <!-- What is it -->
  <div class="card mb-2">
    <div class="card-header">
      <h3><i class="fa fa-road" style="color:var(--accent);"></i> What is RoadFix Portal?</h3>
    </div>
    <p style="line-height:1.8;color:var(--text-muted);">
      RoadFix Portal is a web-based complaint and resolution tracking system designed for citizens to report road and pathway surface damage in their wards. It connects complainants directly with field staff and administrators to ensure faster resolution with full transparency.
    </p>
  </div>

  <!-- How it works -->
  <div class="card mb-2">
    <div class="card-header">
      <h3><i class="fa fa-gears" style="color:var(--accent);"></i> How It Works</h3>
    </div>
    <div style="display:flex;flex-direction:column;gap:1rem;">
      <?php
      $steps = [
        ['fa-user-plus',      '#0891b2', 'Citizen Registers',    'A citizen creates a free account and files a complaint with description, location (Ward → Area → Spot), category, priority, and photo proof.'],
        ['fa-shield-halved',  '#7c3aed', 'Admin Reviews',        'The admin verifies the complaint, checks for duplicates or repeated issues, and assigns it to the appropriate field staff member.'],
        ['fa-hard-hat',       '#d97706', 'Staff Resolves',       'The assigned staff member works on the issue in the field and updates the status (In Progress → Resolved) with resolution proof.'],
        ['fa-circle-check',   '#16a34a', 'Admin Closes',         'The admin reviews the resolution and closes the complaint. If unsatisfactory, it can be reopened for further action.'],
        ['fa-magnifying-glass','#e63946','Citizen Tracks',       'Throughout the process, the citizen can track their complaint status in real-time using their dashboard or the public tracker.'],
      ];
      foreach ($steps as $i => [$icon, $color, $title, $desc]):
      ?>
      <div style="display:flex;gap:1rem;align-items:flex-start;">
        <div style="width:40px;height:40px;border-radius:50%;background:<?= $color ?>22;border:1px solid <?= $color ?>44;display:flex;align-items:center;justify-content:center;flex-shrink:0;color:<?= $color ?>;font-size:1rem;">
          <i class="fa <?= $icon ?>"></i>
        </div>
        <div>
          <div style="font-weight:700;font-size:0.95rem;margin-bottom:0.2rem;"><?= $i+1 ?>. <?= $title ?></div>
          <div style="font-size:0.875rem;color:var(--text-muted);line-height:1.6;"><?= $desc ?></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- SLA Info -->
  <div class="card mb-2">
    <div class="card-header">
      <h3><i class="fa fa-clock" style="color:var(--accent);"></i> Service Level Agreements (SLA)</h3>
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
      <div style="padding:1rem;background:rgba(8,145,178,0.06);border:1px solid rgba(8,145,178,0.2);border-radius:10px;text-align:center;">
        <div style="font-family:'Syne',sans-serif;font-size:2rem;font-weight:800;color:#0891b2;">7h</div>
        <div style="font-weight:600;font-size:0.875rem;margin-top:0.25rem;">Initial Response SLA</div>
        <div style="font-size:0.8rem;color:var(--text-muted);margin-top:0.25rem;">Complaint must be acknowledged within 7 hours of submission</div>
      </div>
      <div style="padding:1rem;background:rgba(217,119,6,0.06);border:1px solid rgba(217,119,6,0.2);border-radius:10px;text-align:center;">
        <div style="font-family:'Syne',sans-serif;font-size:2rem;font-weight:800;color:#d97706;">48h</div>
        <div style="font-weight:600;font-size:0.875rem;margin-top:0.25rem;">Resolution SLA</div>
        <div style="font-size:0.8rem;color:var(--text-muted);margin-top:0.25rem;">Issue must be resolved within 48 hours of assignment</div>
      </div>
    </div>
  </div>

  <!-- Complaint Statuses -->
  <div class="card mb-2">
    <div class="card-header">
      <h3><i class="fa fa-list-check" style="color:var(--accent);"></i> Complaint Status Flow</h3>
    </div>
    <div style="display:flex;flex-wrap:wrap;gap:0.5rem;align-items:center;">
      <?php
      $statuses = ['submitted','verified','assigned','in_progress','resolved','closed','reopened','escalated'];
      foreach ($statuses as $i => $s):
      ?>
      <span class="badge badge-<?= $s ?>" style="font-size:0.8rem;padding:0.3rem 0.75rem;"><?= ucwords(str_replace('_',' ',$s)) ?></span>
      <?php if ($i < count($statuses)-1): ?>
      <i class="fa fa-arrow-right" style="font-size:0.7rem;color:var(--text-muted);"></i>
      <?php endif; ?>
      <?php endforeach; ?>
    </div>
    <p style="font-size:0.82rem;color:var(--text-muted);margin-top:1rem;line-height:1.6;">
      Only <strong>Admin</strong> can Close or Reopen a complaint. Staff can move complaints forward from Submitted through to Resolved. Escalated complaints are flagged for priority attention.
    </p>
  </div>

  <!-- Roles -->
  <div class="card mb-2">
    <div class="card-header">
      <h3><i class="fa fa-users" style="color:var(--accent);"></i> User Roles</h3>
    </div>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1rem;">
      <?php
      $roles = [
        ['fa-user',         '#6366f1', 'Citizen / User',  'Files complaints, tracks status, can view their own complaints history.'],
        ['fa-hard-hat',     '#d97706', 'Staff',           'Receives assigned complaints, updates progress, marks as resolved with proof.'],
        ['fa-user-shield',  '#e63946', 'Admin',           'Full control — verifies, assigns, closes, reopens, manages users and views reports.'],
      ];
      foreach ($roles as [$icon, $color, $role, $desc]):
      ?>
      <div style="padding:1rem;border:1px solid var(--border);border-radius:10px;text-align:center;">
        <div style="width:48px;height:48px;border-radius:50%;background:<?= $color ?>18;border:1px solid <?= $color ?>44;display:flex;align-items:center;justify-content:center;margin:0 auto 0.75rem;font-size:1.2rem;color:<?= $color ?>;">
          <i class="fa <?= $icon ?>"></i>
        </div>
        <div style="font-weight:700;margin-bottom:0.4rem;"><?= $role ?></div>
        <div style="font-size:0.82rem;color:var(--text-muted);line-height:1.5;"><?= $desc ?></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Tech Stack -->
  <div class="card mb-2">
    <div class="card-header">
      <h3><i class="fa fa-code" style="color:var(--accent);"></i> Tech Stack</h3>
    </div>
    <div style="display:flex;flex-wrap:wrap;gap:0.5rem;">
      <?php
      $tech = ['PHP 8','MySQL','HTML5','CSS3','JavaScript','jQuery','AJAX','Font Awesome','Google Fonts'];
      foreach ($tech as $t):
      ?>
      <span style="background:var(--bg);border:1px solid var(--border);border-radius:6px;padding:0.3rem 0.75rem;font-size:0.82rem;font-weight:600;color:var(--text-muted);">
        <?= $t ?>
      </span>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- API Links -->
  <div class="card">
    <div class="card-header">
      <h3><i class="fa fa-plug" style="color:var(--accent);"></i> Available APIs</h3>
    </div>
    <div style="display:flex;flex-direction:column;gap:0.75rem;">
      <div style="display:flex;align-items:center;justify-content:space-between;padding:0.75rem;background:var(--bg);border:1px solid var(--border);border-radius:8px;flex-wrap:wrap;gap:0.5rem;">
        <div>
          <div style="font-weight:600;font-size:0.9rem;">Complaint Status API</div>
          <div style="font-size:0.8rem;color:var(--text-muted);">GET /api/complaint_status.php?id=1 &nbsp;|&nbsp; ?no=CMP202500001</div>
        </div>
        <a href="<?= APP_URL ?>/api/complaint_status.php?id=1" target="_blank" class="btn btn-outline btn-sm">
          <i class="fa fa-arrow-up-right-from-square"></i> Try it
        </a>
      </div>
      <div style="display:flex;align-items:center;justify-content:space-between;padding:0.75rem;background:var(--bg);border:1px solid var(--border);border-radius:8px;flex-wrap:wrap;gap:0.5rem;">
        <div>
          <div style="font-weight:600;font-size:0.9rem;">Area-wise Pending API</div>
          <div style="font-size:0.8rem;color:var(--text-muted);">GET /api/area_pending.php &nbsp;|&nbsp; ?ward_id=1 &nbsp;|&nbsp; ?status=submitted</div>
        </div>
        <a href="<?= APP_URL ?>/api/area_pending.php" target="_blank" class="btn btn-outline btn-sm">
          <i class="fa fa-arrow-up-right-from-square"></i> Try it
        </a>
      </div>
    </div>
  </div>

</div>

<?php include __DIR__ . '/includes/footer.php'; ?>