<?php
// ============================================================
// modules/complaints/view.php — Complaint Detail & Timeline
// ============================================================
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

$db   = getDB();
$user = getCurrentUser();
$id   = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: ' . APP_URL . '/modules/complaints/list.php'); exit; }

// Fetch complaint
$stmt = $db->prepare("
    SELECT c.*, 
           cat.name AS category_name,
           w.name AS ward_name, w.ward_no,
           a.name AS area_name,
           s.name AS spot_name, s.landmark,
           u.name AS submitted_by_name, u.email AS submitted_by_email,
           st.name AS assigned_to_name
    FROM complaints c
    JOIN categories cat ON c.category_id = cat.id
    JOIN wards w ON c.ward_id = w.id
    JOIN areas a ON c.area_id = a.id
    JOIN spots s ON c.spot_id = s.id
    JOIN users u ON c.submitted_by = u.id
    LEFT JOIN users st ON c.assigned_to = st.id
    WHERE c.id = ?
");
$stmt->execute([$id]);
$complaint = $stmt->fetch();

if (!$complaint) { header('Location: ' . APP_URL . '/modules/complaints/list.php'); exit; }

// Access control: users can only see their own
if ($user['role'] === 'user' && $complaint['submitted_by'] != $user['id']) {
    header('Location: ' . APP_URL . '/unauthorized.php'); exit;
}
if ($user['role'] === 'staff' && $complaint['assigned_to'] != $user['id']) {
    header('Location: ' . APP_URL . '/unauthorized.php'); exit;
}

// Fetch history/timeline
$history = $db->prepare("
    SELECT h.*, u.name AS action_by_name
    FROM complaint_history h
    JOIN users u ON h.action_by = u.id
    WHERE h.complaint_id = ?
    ORDER BY h.action_at ASC
");
$history->execute([$id]);
$timeline = $history->fetchAll();

// Fetch attachments
$attStmt = $db->prepare("SELECT * FROM attachments WHERE complaint_id = ? ORDER BY uploaded_at ASC");
$attStmt->execute([$id]);
$attachments = $attStmt->fetchAll();

// Handle status update (staff/admin)
$updateError = $updateSuccess = '';

// Admin: full control including Close and Reopen
// Staff: can only move complaints forward — cannot Close or Reopen
$allowedTransitions = $user['role'] === 'admin' ? [
    'submitted'   => ['verified', 'escalated'],
    'verified'    => ['assigned', 'escalated'],
    'assigned'    => ['in_progress', 'escalated'],
    'in_progress' => ['resolved', 'escalated'],
    'resolved'    => ['closed', 'reopened'],
    'closed'      => ['reopened'],
    'reopened'    => ['assigned', 'in_progress', 'escalated'],
    'escalated'   => ['assigned', 'in_progress'],
] : [
    'submitted'   => ['verified', 'escalated'],
    'verified'    => ['assigned', 'escalated'],
    'assigned'    => ['in_progress', 'escalated'],
    'in_progress' => ['resolved', 'escalated'],
    'reopened'    => ['in_progress', 'escalated'],
    'escalated'   => ['in_progress'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_status'])) {
    if (!in_array($user['role'], ['admin', 'staff'])) {
        $updateError = 'Unauthorized.';
    } else {
        $newStatus = $_POST['new_status'] ?? '';
        $remark    = sanitize($_POST['remark'] ?? '');
        $current   = $complaint['status'];
        $allowed   = $allowedTransitions[$current] ?? [];

        if (!in_array($newStatus, $allowed)) {
            $updateError = "Invalid status transition from '{$current}' to '{$newStatus}'.";
        } else {
            // Update complaint
            $extra = '';
            if ($newStatus === 'resolved') {
                $extra = ', resolved_at = NOW()';
            } elseif ($newStatus === 'closed') {
                $extra = ', closed_at = NOW()';
            } elseif (in_array($current, ['submitted']) && !$complaint['first_response_at']) {
                $extra = ', first_response_at = NOW()';
            }

            // Check SLA breach
            $hoursElapsed = (time() - strtotime($complaint['submitted_at'])) / 3600;
            $slaResponseBreach = ($hoursElapsed > SLA_INITIAL_RESPONSE && !$complaint['first_response_at']) ? 1 : $complaint['sla_response_breach'];
            $slaResolutionBreach = ($hoursElapsed > SLA_RESOLUTION && $newStatus !== 'resolved') ? 1 : $complaint['sla_resolution_breach'];

            $db->prepare("UPDATE complaints SET status = ?, sla_response_breach = ?, sla_resolution_breach = ? $extra WHERE id = ?")
               ->execute([$newStatus, $slaResponseBreach, $slaResolutionBreach, $id]);

            // History
            $db->prepare("INSERT INTO complaint_history (complaint_id, old_status, new_status, remark, action_by) VALUES (?,?,?,?,?)")
               ->execute([$id, $current, $newStatus, $remark, $user['id']]);

            // Resolution proof upload
            if (!empty($_FILES['resolution_proof']['name'])) {
                $file = $_FILES['resolution_proof'];
                $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                if (in_array($ext, ALLOWED_EXTENSIONS) && $file['size'] <= MAX_FILE_SIZE && $file['error'] === UPLOAD_ERR_OK) {
                    $newName = uniqid('resolve_', true) . '.' . $ext;
                    $dest    = UPLOAD_PATH . 'resolutions/' . $newName;
                    if (move_uploaded_file($file['tmp_name'], $dest)) {
                        $db->prepare("INSERT INTO attachments (complaint_id, file_name, file_path, file_type, file_size, upload_type, uploaded_by) VALUES (?,?,?,?,?,'resolution_proof',?)")
                           ->execute([$id, $file['name'], 'resolutions/' . $newName, $file['type'], $file['size'], $user['id']]);
                    }
                }
            }

            $updateSuccess = "Status updated to " . ucwords(str_replace('_',' ',$newStatus));
            // Re-fetch
            $stmt->execute([$id]);
            $complaint = $stmt->fetch();
        }
    }
}

// SLA calculations
$hoursElapsed     = (time() - strtotime($complaint['submitted_at'])) / 3600;
$responseBreached = $hoursElapsed > SLA_INITIAL_RESPONSE && !$complaint['first_response_at'];
$resolveBreached  = $hoursElapsed > SLA_RESOLUTION && !in_array($complaint['status'], ['resolved','closed']);

$pageTitle = 'Complaint ' . $complaint['complaint_no'];
include __DIR__ . '/../../includes/header.php';
?>
<script>window.APP_URL = '<?= APP_URL ?>';</script>

<?php if (isset($_GET['success'])): ?>
<div class="alert alert-success" style="margin:0;border-radius:0;">
  <i class="fa fa-check-circle"></i> Complaint submitted successfully!
  <?php if ($_GET['repeated'] ?? 0): ?> <strong>Note: Marked as Repeated Complaint</strong> <?php endif; ?>
</div>
<?php endif; ?>

<div class="page-header">
  <div class="container flex-between" style="flex-wrap:wrap;gap:1rem;">
    <div>
      <h1><i class="fa fa-file-alt"></i> <?= htmlspecialchars($complaint['complaint_no']) ?></h1>
      <p><?= htmlspecialchars($complaint['title']) ?></p>
    </div>
    <div style="display:flex;gap:0.5rem;align-items:center;flex-wrap:wrap;">
      <span class="badge badge-<?= $complaint['status'] ?>" style="font-size:0.9rem;padding:0.4rem 1rem;">
        <?= ucwords(str_replace('_',' ',$complaint['status'])) ?>
      </span>
      <?php if ($complaint['is_repeated']): ?>
      <span class="badge badge-repeated"><i class="fa fa-rotate"></i> Repeated Complaint</span>
      <?php endif; ?>
      <a href="<?= APP_URL ?>/modules/complaints/list.php" class="btn btn-outline btn-sm">← Back</a>
    </div>
  </div>
</div>

<div class="container">

  <?php if ($updateError): ?><div class="alert alert-danger"><i class="fa fa-exclamation-circle"></i> <?= $updateError ?></div><?php endif; ?>
  <?php if ($updateSuccess): ?><div class="alert alert-success"><i class="fa fa-check-circle"></i> <?= htmlspecialchars($updateSuccess) ?></div><?php endif; ?>

  <!-- SLA Warnings -->
  <?php if ($responseBreached): ?>
  <div class="sla-warning mb-2"><i class="fa fa-clock"></i> <strong>SLA Breach:</strong> Initial response not provided within <?= SLA_INITIAL_RESPONSE ?> hours (<?= round($hoursElapsed) ?>h elapsed)</div>
  <?php endif; ?>
  <?php if ($resolveBreached): ?>
  <div class="sla-warning mb-2" style="border-color:#b91c1c;"><i class="fa fa-triangle-exclamation"></i> <strong>Resolution SLA Breach:</strong> Issue not resolved within <?= SLA_RESOLUTION ?> hours</div>
  <?php endif; ?>

  <div class="complaint-detail-grid">
    <!-- Left: Details -->
    <div>
      <!-- Live Tracker -->
      <div class="card mb-2" id="liveTracker" data-complaint-id="<?= $id ?>">
        <div class="card-header">
          <h3><span class="live-dot"></span> Live Status</h3>
          <small class="text-muted live-updated">Tracking active</small>
        </div>
        <div class="live-status">
          <span class="badge badge-<?= $complaint['status'] ?>"><?= ucwords(str_replace('_',' ',$complaint['status'])) ?></span>
        </div>
      </div>

      <!-- Complaint Info -->
      <div class="card mb-2">
        <div class="card-header"><h3><i class="fa fa-info-circle"></i> Complaint Details</h3></div>
        <div class="info-list">
          <div class="info-row"><span class="info-label">Description</span><span class="info-value"><?= nl2br(htmlspecialchars($complaint['description'])) ?></span></div>
          <div class="info-row"><span class="info-label">Category</span><span class="info-value"><?= htmlspecialchars($complaint['category_name']) ?></span></div>
          <div class="info-row"><span class="info-label">Priority</span><span class="info-value"><span class="priority-badge priority-<?= $complaint['priority'] ?>"><?= ucfirst($complaint['priority']) ?></span></span></div>
          <div class="info-row"><span class="info-label">Ward</span><span class="info-value"><?= htmlspecialchars($complaint['ward_no'] . ' - ' . $complaint['ward_name']) ?></span></div>
          <div class="info-row"><span class="info-label">Area</span><span class="info-value"><?= htmlspecialchars($complaint['area_name']) ?></span></div>
          <div class="info-row"><span class="info-label">Spot</span><span class="info-value"><?= htmlspecialchars($complaint['spot_name']) ?><?php if($complaint['landmark']): ?> <span style="color:var(--text-muted);font-size:0.82rem;">(<?= htmlspecialchars($complaint['landmark']) ?>)</span><?php endif; ?></span></div>
          <div class="info-row"><span class="info-label">Filed By</span><span class="info-value"><?= htmlspecialchars($complaint['submitted_by_name']) ?></span></div>
          <div class="info-row"><span class="info-label">Filed On</span><span class="info-value"><?= date('d M Y, h:i A', strtotime($complaint['submitted_at'])) ?></span></div>
          <?php if ($complaint['assigned_to_name']): ?>
          <div class="info-row"><span class="info-label">Assigned To</span><span class="info-value"><?= htmlspecialchars($complaint['assigned_to_name']) ?></span></div>
          <?php endif; ?>
          <?php if ($complaint['resolved_at']): ?>
          <div class="info-row"><span class="info-label">Resolved On</span><span class="info-value"><?= date('d M Y, h:i A', strtotime($complaint['resolved_at'])) ?></span></div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Attachments -->
      <?php if (!empty($attachments)): ?>
      <div class="card mb-2">
        <div class="card-header"><h3><i class="fa fa-paperclip"></i> Attachments</h3></div>
        <div style="display:flex;flex-direction:column;gap:0.5rem;">
          <?php foreach ($attachments as $att): ?>
          <div style="display:flex;align-items:center;gap:0.75rem;padding:0.5rem;background:var(--bg);border-radius:6px;border:1px solid var(--border);">
            <i class="fa <?= strstr($att['file_type'],'pdf') ? 'fa-file-pdf' : 'fa-image' ?>" style="color:var(--accent);font-size:1.2rem;"></i>
            <div style="flex:1;">
              <div style="font-size:0.875rem;font-weight:600;"><?= htmlspecialchars($att['file_name']) ?></div>
              <div style="font-size:0.75rem;color:var(--text-muted);">
                <?= ucwords(str_replace('_',' ',$att['upload_type'])) ?> &bull;
                <?= round($att['file_size']/1024) ?>KB &bull;
                <?= date('d M Y', strtotime($att['uploaded_at'])) ?>
              </div>
            </div>
            <a href="<?= APP_URL ?>/uploads/<?= htmlspecialchars($att['file_path']) ?>" target="_blank" class="btn btn-outline btn-sm">
              <i class="fa fa-eye"></i> View
            </a>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>
    </div>

    <!-- Right: Timeline + Actions -->
    <div>
    <!-- Status Update (staff/admin) -->
      <?php
      $nextStatuses = $allowedTransitions[$complaint['status']] ?? [];
      if (in_array($user['role'], ['admin','staff']) && !empty($nextStatuses)):
      ?>
      <div class="card mb-2">
        <div class="card-header"><h3><i class="fa fa-arrows-rotate"></i> Update Status</h3></div>
        <?php if ($user['role'] === 'staff'): ?>
        <div style="background:rgba(8,145,178,0.08);border:1px solid rgba(8,145,178,0.2);border-radius:6px;padding:0.6rem 0.8rem;margin-bottom:1rem;font-size:0.82rem;color:#0891b2;display:flex;align-items:center;gap:0.5rem;">
          <i class="fa fa-info-circle"></i> Closing and Reopening complaints is restricted to Admin only.
        </div>
        <?php endif; ?>
        <form method="POST" enctype="multipart/form-data">
          <input type="hidden" name="csrf" value="<?= generateCSRF() ?>">
          <div class="form-group">
            <label>New Status</label>
            <select name="new_status" class="form-control" required>
              <option value="">Select new status</option>
              <?php foreach ($nextStatuses as $s): ?>
              <option value="<?= $s ?>"><?= ucwords(str_replace('_',' ',$s)) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Remark / Note</label>
            <textarea name="remark" class="form-control" rows="2" placeholder="Add a note about this update…"></textarea>
          </div>
          <div class="form-group">
            <label>Resolution Proof (optional)</label>
            <input type="file" name="resolution_proof" class="form-control" accept=".jpg,.jpeg,.png,.pdf">
          </div>
          <button type="submit" class="btn btn-primary w-full" style="justify-content:center;">
            <i class="fa fa-check"></i> Update Status
          </button>
        </form>
      </div>
      <?php endif; ?>

      <?php
      // Show locked message to staff when complaint is resolved/closed
      if ($user['role'] === 'staff' && in_array($complaint['status'], ['resolved','closed'])):
      ?>
      <div class="card mb-2" style="border-color:rgba(22,163,74,0.3);background:rgba(22,163,74,0.04);">
        <div style="display:flex;align-items:center;gap:0.75rem;padding:0.25rem 0;">
          <i class="fa fa-lock" style="font-size:1.5rem;color:#16a34a;"></i>
          <div>
            <div style="font-weight:700;font-size:0.9rem;color:#16a34a;">
              Complaint <?= ucfirst($complaint['status']) ?>
            </div>
            <div style="font-size:0.82rem;color:var(--text-muted);">
              Further actions (Close / Reopen) are handled by Admin.
            </div>
          </div>
        </div>
      </div>
      <?php endif; ?>

      <!-- Timeline -->
      <div class="card">
        <div class="card-header"><h3><i class="fa fa-timeline"></i> Activity Timeline</h3></div>
        <div class="timeline">
          <?php foreach ($timeline as $entry): ?>
          <div class="timeline-item">
            <div class="timeline-dot"></div>
            <div class="timeline-content">
              <div style="font-weight:600;font-size:0.9rem;">
                <?php if ($entry['old_status']): ?>
                  <span style="color:var(--text-muted);"><?= ucwords(str_replace('_',' ',$entry['old_status'])) ?></span>
                  <i class="fa fa-arrow-right" style="font-size:0.7rem;margin:0 0.3rem;"></i>
                <?php endif; ?>
                <span class="badge badge-<?= $entry['new_status'] ?>"><?= ucwords(str_replace('_',' ',$entry['new_status'])) ?></span>
              </div>
              <?php if ($entry['remark']): ?>
              <p style="font-size:0.85rem;margin-top:0.3rem;"><?= htmlspecialchars($entry['remark']) ?></p>
              <?php endif; ?>
              <div class="timeline-meta">
                <i class="fa fa-user"></i> <?= htmlspecialchars($entry['action_by_name']) ?>
                &bull; <?= date('d M Y, h:i A', strtotime($entry['action_at'])) ?>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
