<?php
// ============================================================
// api/save_preference.php
// AJAX: Save theme preference to cookie + DB
//
// POST /api/save_preference.php
// Body: theme=dark  OR  theme=light
// ============================================================

header('Content-Type: application/json');

require_once __DIR__ . '/../includes/auth.php';

$theme = trim($_POST['theme'] ?? $_GET['theme'] ?? '');

// Validate
if (!in_array($theme, ['light', 'dark'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid theme value.']);
    exit;
}

// Always set cookie (works for guests too) — 1 year
setcookie('theme', $theme, time() + 60 * 60 * 24 * 365, '/', '', false, false);

// If logged in, also persist to DB
if (isLoggedIn()) {
    $db  = getDB();
    $uid = $_SESSION['user_id'];

    // Upsert user_preferences
    $stmt = $db->prepare("
        INSERT INTO user_preferences (user_id, theme)
        VALUES (?, ?)
        ON DUPLICATE KEY UPDATE theme = VALUES(theme), updated_at = NOW()
    ");
    $stmt->execute([$uid, $theme]);
}

echo json_encode(['success' => true, 'theme' => $theme]);
