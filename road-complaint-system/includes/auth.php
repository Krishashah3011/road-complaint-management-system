<?php
// ============================================================
// includes/auth.php - Authentication & Session Middleware
// ============================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';

/**
 * Check if user is logged in
 */
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Get current logged-in user data
 */
function getCurrentUser(): ?array {
    if (!isLoggedIn()) return null;
    return $_SESSION['user'] ?? null;
}

/**
 * Check if current user has a given role
 */
function hasRole(string $role): bool {
    $user = getCurrentUser();
    return $user && $user['role'] === $role;
}

/**
 * Require login - redirect if not logged in
 */
function requireLogin(string $redirect = '/index.php'): void {
    if (!isLoggedIn()) {
        header('Location: ' . APP_URL . $redirect);
        exit;
    }
}

/**
 * Require specific role
 */
function requireRole(string $role): void {
    requireLogin();
    if (!hasRole($role)) {
        header('Location: ' . APP_URL . '/unauthorized.php');
        exit;
    }
}

/**
 * Require admin or staff
 */
function requireStaffOrAdmin(): void {
    requireLogin();
    $user = getCurrentUser();
    if (!$user || !in_array($user['role'], ['admin', 'staff'])) {
        header('Location: ' . APP_URL . '/unauthorized.php');
        exit;
    }
}

/**
 * Log in a user
 */
function loginUser(array $user): void {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user'] = [
        'id'    => $user['id'],
        'name'  => $user['name'],
        'email' => $user['email'],
        'role'  => $user['role_name'],
        'phone' => $user['phone'],
    ];
    session_regenerate_id(true);
}

/**
 * Log out current user
 */
function logoutUser(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

/**
 * Generate CSRF token
 */
function generateCSRF(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verifyCSRF(string $token): bool {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Sanitize input
 */
function sanitize(string $input): string {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

/**
 * Generate unique complaint number
 */
function generateComplaintNo(): string {
    return 'CMP' . date('Y') . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
}
