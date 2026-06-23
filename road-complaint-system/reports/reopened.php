<?php
// ============================================================
// reports/reopened.php — Mandatory Reopened Complaints Report
// ============================================================
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');

$db = getDB();

// ── Summary counts ───────────────────────────────────────────
$totalReopened = $db->query("SELECT COUNT(*) FROM complaints WHERE status = 'reopened'")->fetchColumn();

// Area-wise grouping of reopened complaints
$areaWise = $db->query("
    SELECT
        w.name  AS ward_name,
        w.ward_no,
        a.name  AS area_name,
        COUNT(c.id) AS total_reopened,
        GROUP_CONCAT(c.complaint_no ORDER BY c.updated_at DESC SEPARATOR ', ') AS complaint_nos
    FROM complaints c
    JOIN areas a  ON c.area_id  = a.id
    JOIN wards w  ON c.ward_id  = w.id
    WHERE c.status = 'reopened'
    GROUP BY c.area_id
    ORDER BY total_reopened DESC
")->fetchAll();

// Full list of reopened complaints
$reopenedList = $db->query("
    SELECT
        c.*,
        cat.name  AS category_name,
        w.name    AS ward_name,
        a.name    AS area_name,
        s.name    AS spot_name,
        u.name    AS submitted_by_name,
        st.name   AS assigned_to_name,
        -- How many times reopened (count history rows with new_status='reopened')
        (SELECT COUNT(*) FROM complaint_history h
         WHERE h.complaint_id = c.id AND h.new_status = 'reopened') AS reopen_count,
        -- Original submission date
        c.submitted_at,
        -- Last reopen date
        (SELECT h.action_at FROM complaint_history h
         WHERE h.complaint_id = c.id AND h.new_status = 'reopened'
         ORDER BY h.action_at DESC LIMIT 1) AS last_reopened_at
    FROM complaints c
    JOIN categories cat ON c.category_id = cat.id
    JOIN wards      w   ON c.ward_id      = w.id
    JOIN areas      a   ON c.area_id      = a.id
    JOIN spots      s   ON c.spot_id      = s.id
    JOIN users      u   ON c.submitted_by = u.id
    LEFT JOIN users st  ON c.assigned_to  = st.id
    WHERE c.status = 'reopened'
    ORDER BY last_reopened_at DESC
")->fetchAll();

// Category-wise reopened count (for second mini-chart)
$catWise = $db->query("
    SELECT cat.name AS cat_name, COUNT(*) AS cnt
    FROM complaints c JOIN categories cat ON c.category_id = cat.id
    WHERE c.status = 'reopened'
    GROUP BY c.category_id ORDER BY cnt DESC
")->fetchAll();

$maxArea = $areaWise ? max(array_column($areaWise, 'total_reopened')) : 1;
$maxCat  = $catWise  ? max(array_column($catWise,  'cnt'))            : 1;

$pageTitle = 'Reopened Complaints Report';
include __DIR__ . '/../includes/header.php';
?>
<script>window.APP_URL = '<?= APP_URL ?>';</script>

<div class="page-header">
  <div class="container flex-between" style="flex-wrap:wrap;gap:1rem;">
    <div>
      <h1><i class="fa fa-rotate-left"></i> Reopened Complaints Report</h1>
      <p>Mandatory summary — all complaints that were closed and later reopened</p>
    </div>
    <div style="display:flex;gap:0.5rem;">
      <a href="<?= APP_URL ?>/modules/admin/dashboard.php" class="btn btn-outline">← Dashboard</a>
      <button onclick="window.print()" class="btn btn-secondary"><i class="fa fa-print"></i> Print</button>
    </div>
  </div>
</div>

<div class="container">

  <!-- ── KPI Banner ──────────────────────────────────────────── -->
  <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:1rem;margin-bottom:1.5rem;">
    <div class="stat-card red">
      <div class="stat-icon"><i class="fa fa-rotate-left"></i></div>
      <div class="stat-value"><?= $totalReopened ?></div>
      <div class="stat-label">Total Reopened</div>
    </div>
    <div class="stat-card orange">
      <div class="stat-icon"><i class="fa fa-map-location-dot"></i></div>
      <div class="stat-value"><?= count($areaWise) ?></div>
      <div class="stat-label">Areas Affected</div>
    </div>
    <div class="stat-card purple">
      <div class="stat-icon"><i class="fa fa-tags"></i></div>
      <div class="stat-value"><?= count($catWise) ?></div>
      <div class="stat-label">Categories</div>
    </div>
    <div class="stat-card blue">
      <div class="stat-icon"><i class="fa fa-calendar-day"></i></div>
      <div class="stat-value"><?= date('d M Y') ?></div>
      <div class="stat-label">Report Date</div>
    </div>
  </div>

  <?php if ($totalReopened === 0): ?>
  <!-- Empty state -->
  <div class="card" style="text-align:center;padding:3rem;">
    <i class="fa fa-party-horn" style="font-size:3rem;color:#16a34a;margin-bottom:1rem;"></i>
    <h2 style="color:#16a34a;">No Reopened Complaints!</h2>
    <p class="text-muted mt-1">All resolved complaints remain closed. Excellent resolution quality.</p>
  </div>

  <?php else: ?>

  <!-- ── Charts Row ──────────────────────────────────────────── -->
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;margin-bottom:1.5rem;">

    <!-- Area-wise bar chart -->
    <div class="card">
      <div class="card-header">
        <h3><i class="fa fa-map-marker-alt" style="color:var(--accent);"></i> Reopened by Area</h3>
      </div>
      <div class="bar-chart">
        <?php foreach ($areaWise as $row): ?>
        <div class="bar-row">
          <span class="bar-label" title="<?= htmlspecialchars($row['ward_name']) ?>">
            <?= htmlspecialchars(mb_strimwidth($row['area_name'], 0, 18, '…')) ?>
          </span>
          <div class="bar-track">
            <div class="bar-fill" style="width:<?= ($row['total_reopened'] / $maxArea * 100) ?>%;background:#dc2626;"></div>
          </div>
          <span class="bar-count"><?= $row['total_reopened'] ?></span>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Category-wise bar chart -->
    <div class="card">
      <div class="card-header">
        <h3><i class="fa fa-tags" style="color:var(--accent);"></i> Reopened by Category</h3>
      </div>
      <div class="bar-chart">
        <?php foreach ($catWise as $row): ?>
        <div class="bar-row">
          <span class="bar-label"><?= htmlspecialchars(mb_strimwidth($row['cat_name'], 0, 18, '…')) ?></span>
          <div class="bar-track">
            <div class="bar-fill" style="width:<?= ($row['cnt'] / $maxCat * 100) ?>%;background:#7c3aed;"></div>
          </div>
          <span class="bar-count"><?= $row['cnt'] ?></span>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

  </div>

  <!-- ── Area-wise Summary Table ─────────────────────────────── -->
  <div class="card mb-2" style="padding:0;">
    <div class="card-header" style="padding:1rem 1.5rem;">
      <h3><i class="fa fa-table"></i> Area-wise Grouping</h3>
    </div>
    <div class="table-wrapper">
      <table>
        <thead>
          <tr>
            <th>#</th>
            <th>Ward</th>
            <th>Area</th>
            <th>Reopened Count</th>
            <th>Complaint Numbers</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($areaWise as $i => $row): ?>
          <tr>
            <td style="color:var(--text-muted);"><?= $i + 1 ?></td>
            <td><?= htmlspecialchars($row['ward_no'] . ' — ' . $row['ward_name']) ?></td>
            <td style="font-weight:600;"><?= htmlspecialchars($row['area_name']) ?></td>
            <td>
              <span style="font-family:'Syne',sans-serif;font-size:1.1rem;font-weight:800;color:#dc2626;">
                <?= $row['total_reopened'] ?>
              </span>
            </td>
            <td style="font-size:0.8rem;color:var(--text-muted);">
              <?= htmlspecialchars($row['complaint_nos']) ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- ── Full Detail Table ───────────────────────────────────── -->
  <div class="card" style="padding:0;">
    <div class="card-header" style="padding:1rem 1.5rem;">
      <h3><i class="fa fa-list-ul"></i> Reopened Complaints — Full Detail</h3>
      <span style="font-size:0.85rem;color:var(--text-muted);"><?= count($reopenedList) ?> record(s)</span>
    </div>
    <div class="table-wrapper">
      <table>
        <thead>
          <tr>
            <th>#</th>
            <th>Complaint No</th>
            <th>Title</th>
            <th>Category</th>
            <th>Area</th>
            <th>Priority</th>
            <th>Assigned To</th>
            <th>Filed On</th>
            <th>Last Reopened</th>
            <th>Reopen #</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($reopenedList as $i => $c): ?>
          <tr>
            <td style="color:var(--text-muted);"><?= $i + 1 ?></td>
            <td>
              <a href="<?= APP_URL ?>/modules/complaints/view.php?id=<?= $c['id'] ?>"
                 style="font-weight:700;color:var(--accent);">
                <?= htmlspecialchars($c['complaint_no']) ?>
              </a>
            </td>
            <td style="max-width:160px;font-size:0.85rem;">
              <?= htmlspecialchars(mb_strimwidth($c['title'], 0, 40, '…')) ?>
            </td>
            <td style="font-size:0.82rem;"><?= htmlspecialchars($c['category_name']) ?></td>
            <td style="font-size:0.82rem;"><?= htmlspecialchars($c['area_name']) ?></td>
            <td>
              <span class="priority-badge priority-<?= $c['priority'] ?>">
                <?= ucfirst($c['priority']) ?>
              </span>
            </td>
            <td style="font-size:0.82rem;">
              <?= $c['assigned_to_name'] ? htmlspecialchars($c['assigned_to_name']) : '<span class="text-muted">Unassigned</span>' ?>
            </td>
            <td style="font-size:0.8rem;white-space:nowrap;">
              <?= date('d M Y', strtotime($c['submitted_at'])) ?>
            </td>
            <td style="font-size:0.8rem;white-space:nowrap;color:#dc2626;font-weight:600;">
              <?= $c['last_reopened_at'] ? date('d M Y', strtotime($c['last_reopened_at'])) : '—' ?>
            </td>
            <td style="text-align:center;">
              <span style="background:rgba(220,38,38,0.1);color:#dc2626;font-weight:700;
                           padding:0.15rem 0.6rem;border-radius:999px;font-size:0.8rem;">
                ×<?= $c['reopen_count'] ?>
              </span>
            </td>
            <td>
              <div style="display:flex;gap:0.4rem;">
                <a href="<?= APP_URL ?>/modules/complaints/view.php?id=<?= $c['id'] ?>"
                   class="btn btn-sm btn-outline" title="View">
                  <i class="fa fa-eye"></i>
                </a>
                <a href="<?= APP_URL ?>/modules/admin/assign.php?id=<?= $c['id'] ?>"
                   class="btn btn-sm btn-secondary" title="Re-assign">
                  <i class="fa fa-user-plus"></i>
                </a>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <?php endif; ?>

</div>

<!-- Print styles -->
<style>
@media print {
  .navbar, .site-footer, .btn, .page-header .btn { display: none !important; }
  body { background: #fff !important; color: #000 !important; }
  .card { box-shadow: none !important; border: 1px solid #ccc !important; }
  .page-header { background: #1a1a2e !important; -webkit-print-color-adjust: exact; }
}
</style>

<?php include __DIR__ . '/../includes/footer.php'; ?>
