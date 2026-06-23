<?php
// ============================================================
// config/db.php - Database Connection Configuration
// ============================================================

define('DB_HOST', 'localhost');
define('DB_USER', 'your MySQL username');
define('DB_PASS', 'your MySQL password');
define('DB_NAME', 'road_complaint_db');
define('DB_CHARSET', 'utf8mb4');

// Application Constants
define('APP_NAME', 'RoadFix Portal');
define('APP_URL', 'http://localhost:8082/road-complaint-system');
define('UPLOAD_PATH', __DIR__ . '/../uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5 MB
define('ALLOWED_TYPES', ['image/jpeg', 'image/png', 'application/pdf']);
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'pdf']);

// SLA Constants (in hours)
define('SLA_INITIAL_RESPONSE', 7);
define('SLA_RESOLUTION', 48);
define('REPEATED_COMPLAINT_DAYS', 7);

/**
 * Get PDO database connection (singleton)
 */
function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";port=3308;dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die(json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]));
        }
    }
    return $pdo;
}
