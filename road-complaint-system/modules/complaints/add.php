<?php
// ============================================================
// modules/complaints/add.php — File a New Complaint
// ============================================================
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();

$db = getDB();
$user = getCurrentUser();
$error = $success = '';

// Load categories
$categories = $db->query("SELECT * FROM categories WHERE is_active = 1 ORDER BY name")->fetchAll();
// Load wards
$wards = $db->query("SELECT * FROM wards WHERE is_active = 1 ORDER BY ward_no")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = sanitize($_POST['title'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $category_id = (int)($_POST['category_id'] ?? 0);
    $ward_id     = (int)($_POST['ward_id'] ?? 0);
    $area_id     = (int)($_POST['area_id'] ?? 0);
    $spot_id     = (int)($_POST['spot_id'] ?? 0);
    $priority    = sanitize($_POST['priority'] ?? 'medium');

    $errors = [];
    if (strlen($title) < 10)       $errors[] = 'Title must be at least 10 characters.';
    if (strlen($description) < 20) $errors[] = 'Description must be at least 20 characters.';
    if (!$category_id)             $errors[] = 'Please select a category.';
    if (!$ward_id)                 $errors[] = 'Please select a ward.';
    if (!$area_id)                 $errors[] = 'Please select an area.';
    if (!$spot_id)                 $errors[] = 'Please select a spot.';
    if (!in_array($priority, ['low','medium','high','critical'])) $priority = 'medium';

    $is_repeated = 0;
    $repeated_ref_id = null;
    $dupStmt = $db->prepare("
        SELECT id, complaint_no FROM complaints
        WHERE category_id = ? AND area_id = ?
          AND submitted_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
          AND status NOT IN ('closed')
        ORDER BY submitted_at DESC LIMIT 1
    ");
    $dupStmt->execute([$category_id, $area_id, REPEATED_COMPLAINT_DAYS]);
    $dupRow = $dupStmt->fetch();
    if ($dupRow) { $is_repeated = 1; $repeated_ref_id = $dupRow['id']; }

    $attachment_path = null;
    if (!empty($_FILES['attachment']['name'])) {
        $file = $_FILES['attachment'];
        $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ALLOWED_EXTENSIONS)) {
            $errors[] = 'Invalid file type. Only JPG, PNG, PDF allowed.';
        } elseif ($file['size'] > MAX_FILE_SIZE) {
            $errors[] = 'File size exceeds 5MB limit.';
        } elseif ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'File upload error.';
        } else {
            $newName = uniqid('complaint_', true) . '.' . $ext;
            $dest    = UPLOAD_PATH . 'complaints/' . $newName;
            if (move_uploaded_file($file['tmp_name'], $dest)) {
                $attachment_path = 'complaints/' . $newName;
            } else {
                $errors[] = 'Could not save uploaded file.';
            }
        }
    }

    if (empty($errors)) {
        do {
            $complaint_no = generateComplaintNo();
            $chk = $db->prepare("SELECT id FROM complaints WHERE complaint_no = ?");
            $chk->execute([$complaint_no]);
        } while ($chk->fetch());

        $ins = $db->prepare("
            INSERT INTO complaints
              (complaint_no, title, description, category_id, ward_id, area_id, spot_id, priority, status, is_repeated, repeated_ref_id, submitted_by)
            VALUES (?,?,?,?,?,?,?,?,'submitted',?,?,?)
        ");
        $ins->execute([$complaint_no, $title, $description, $category_id, $ward_id, $area_id, $spot_id, $priority, $is_repeated, $repeated_ref_id, $user['id']]);
        $newId = $db->lastInsertId();

        $db->prepare("INSERT INTO complaint_history (complaint_id, old_status, new_status, remark, action_by) VALUES (?, NULL, 'submitted', 'Complaint submitted by citizen', ?)")
           ->execute([$newId, $user['id']]);

        if ($attachment_path) {
            $db->prepare("INSERT INTO attachments (complaint_id, file_name, file_path, file_type, file_size, upload_type, uploaded_by) VALUES (?,?,?,?,?,'complaint_proof',?)")
               ->execute([$newId, $_FILES['attachment']['name'], $attachment_path, $_FILES['attachment']['type'], $_FILES['attachment']['size'], $user['id']]);
        }

        header("Location: " . APP_URL . "/modules/complaints/view.php?id={$newId}&success=1&repeated=" . $is_repeated);
        exit;
    } else {
        $error = implode('<br>', $errors);
    }
}

$pageTitle = 'File New Complaint';
include __DIR__ . '/../../includes/header.php';
?>
<script>window.APP_URL = '<?= APP_URL ?>';</script>

<div class="page-header">
  <div class="container">
    <h1><i class="fa fa-plus-circle"></i> File New Complaint</h1>
    <p>Report a road or pathway surface damage issue in your area</p>
  </div>
</div>

<div class="container" style="max-width:800px;">

  <?php if ($error): ?>
  <div class="alert alert-danger"><i class="fa fa-exclamation-circle"></i> <?= $error ?></div>
  <?php endif; ?>

  <div class="repeat-warning" id="duplicateWarning" style="display:none;align-items:center;gap:0.6rem;">
    <i class="fa fa-triangle-exclamation" style="font-size:1.2rem;color:#ea580c;"></i>
    <span class="dup-msg"></span>
  </div>

  <div class="card">
    <form id="complaintForm" method="POST" enctype="multipart/form-data">
      <input type="hidden" name="csrf" value="<?= generateCSRF() ?>">

      <h3 style="margin-bottom:1.25rem;font-size:0.8rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:.08em;font-weight:700;">
        <i class="fa fa-info-circle" style="color:var(--accent);"></i> Basic Information
      </h3>

      <!-- Floating: Title -->
      <div class="float-group">
        <input type="text" id="title" name="title" placeholder=" "
               value="<?= htmlspecialchars($_POST['title'] ?? '') ?>"
               minlength="10" maxlength="200" required>
        <label for="title">Complaint title (min. 10 chars) <span style="color:var(--accent);">*</span></label>
      </div>

      <!-- Floating: Description -->
      <div class="float-group is-textarea">
        <textarea id="description" name="description" placeholder=" " rows="4" minlength="20" required><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
        <label for="description">Describe the damage in detail (min. 20 chars) <span style="color:var(--accent);">*</span></label>
      </div>

      <!-- Category + Priority (floating) -->
      <div class="float-row" style="gap:1rem;">
        <div class="float-group">
          <select id="category_id" name="category_id" required
                  class="<?= !empty($_POST['category_id']) ? 'has-value' : '' ?>"
                  onchange="this.classList.toggle('has-value', this.value !== '')">
            <option value=""></option>
            <?php foreach ($categories as $cat): ?>
            <option value="<?= $cat['id'] ?>" <?= ($_POST['category_id'] ?? '') == $cat['id'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($cat['name']) ?>
            </option>
            <?php endforeach; ?>
          </select>
          <label for="category_id">Category <span style="color:var(--accent);">*</span></label>
        </div>
        <div class="float-group">
          <select id="priority" name="priority" class="has-value"
                  onchange="this.classList.toggle('has-value', this.value !== '')">
            <?php foreach (['low'=>'Low','medium'=>'Medium','high'=>'High','critical'=>'Critical'] as $k => $v): ?>
            <option value="<?= $k ?>" <?= ($_POST['priority'] ?? 'medium') === $k ? 'selected' : '' ?>><?= $v ?></option>
            <?php endforeach; ?>
          </select>
          <label for="priority">Priority</label>
        </div>
      </div>

      <!-- Location — plain selects, no floating -->
      <h3 style="margin:1.5rem 0 1.25rem;font-size:0.8rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:.08em;font-weight:700;">
        <i class="fa fa-map-marker-alt" style="color:var(--accent);"></i> Location
      </h3>

      <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:1rem;">
        <div class="form-group" style="margin-bottom:0;">
          <label for="ward_id">Ward <span class="required">*</span></label>
          <select id="ward_id" name="ward_id" class="form-control" required>
            <option value="">Select Ward</option>
            <?php foreach ($wards as $w): ?>
            <option value="<?= $w['id'] ?>" <?= ($_POST['ward_id'] ?? '') == $w['id'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($w['ward_no'] . ' - ' . $w['name']) ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group" style="margin-bottom:0;">
          <label for="area_id">Area <span class="required">*</span></label>
          <select id="area_id" name="area_id" class="form-control" disabled required>
            <option value="">Select Ward first</option>
          </select>
        </div>
        <div class="form-group" style="margin-bottom:0;">
          <label for="spot_id">Spot / Location <span class="required">*</span></label>
          <select id="spot_id" name="spot_id" class="form-control" disabled required>
            <option value="">Select Area first</option>
          </select>
        </div>
      </div>

      <!-- Attachment -->
      <h3 style="margin:1.5rem 0 1rem;font-size:0.8rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:.08em;font-weight:700;">
        <i class="fa fa-camera" style="color:var(--accent);"></i> Photo / Document Proof
      </h3>

      <div class="form-group">
        <label>Attach Proof (JPG, PNG, PDF — Max 5MB)</label>
        <div class="file-upload-box" onclick="document.getElementById('attachment').click()">
          <i class="fa fa-cloud-upload-alt"></i>
          <p id="fileLabel">Click to upload or drag a file here</p>
        </div>
        <input type="file" id="attachment" name="attachment" accept=".jpg,.jpeg,.png,.pdf" style="display:none">
      </div>

      <div style="display:flex;gap:1rem;justify-content:flex-end;margin-top:1.5rem;">
        <a href="<?= APP_URL ?>/modules/complaints/list.php" class="btn btn-outline">Cancel</a>
        <button type="submit" class="btn btn-primary btn-lg">
          <i class="fa fa-paper-plane"></i> Submit Complaint
        </button>
      </div>
    </form>
  </div>
</div>

<script>
document.getElementById('attachment').addEventListener('change', function () {
  document.getElementById('fileLabel').textContent =
    this.files.length ? this.files[0].name : 'Click to upload or drag a file here';
});
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>