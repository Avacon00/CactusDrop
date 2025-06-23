<?php
// config.php - Automatisch generiert vom CactusDrop Installer

// --- Datenbank-Konfiguration ---
define('DB_HOST', 'localhost');
define('DB_USER', 'd0440a46');
define('DB_PASS', '5zAnRTP3CDKf5WrNjnuF');
define('DB_NAME', 'd0440a46');

// --- App-Konfiguration ---
define('APP_URL', 'https://schuttehub.de/v2');
define('UPLOAD_DIR', __DIR__ . '/uploads/');
date_default_timezone_set('Europe/Berlin');

// --- Datei-Upload-Limits (können hier angepasst werden) ---
define('FILE_EXPIRATION_HOURS', 24); // Gültigkeit in Stunden
define('MAX_FILE_SIZE', 100 * 1024 * 1024); // 100 MB

// --- Erlaubte Dateitypen ---
// WICHTIG: Eine zu offene Konfiguration kann ein Sicherheitsrisiko sein.
define('ALLOWED_MIME_TYPES', [
    // Bilder
    'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml',
    // Dokumente
    'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'application/vnd.ms-powerpoint', 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
    'application/vnd.oasis.opendocument.text', 'application/vnd.oasis.opendocument.spreadsheet', 'application/vnd.oasis.opendocument.presentation',
    'text/plain', 'text/csv',
    // Archive
    'application/zip', 'application/x-rar-compressed', 'application/x-7z-compressed', 'application/x-tar', 'application/gzip',
    // Audio/Video
    'video/mp4', 'video/quicktime', 'video/x-msvideo', 'video/x-matroska',
    'audio/mpeg', 'audio/wav', 'audio/ogg', 'audio/flac',
    // Andere
    'application/json', 'application/xml', 'application/octet-stream'
]);

// --- Hilfsfunktionen ---

/**
 * Sendet eine standardisierte JSON-Fehlerantwort und beendet das Skript.
 */
function send_json_error($message, $statusCode = 500) {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['status' => 'error', 'message' => $message]);
    exit;
}

/**
 * Bereinigt einen Dateinamen, um ihn sicher zu speichern.
 */
function sanitize_filename($filename) {
    $basename = basename($filename);
    $sanitized = preg_replace('/[^\pL\pN\s._-]/u', '_', $basename);
    $sanitized = trim($sanitized, '._-');
    if (empty($sanitized)) {
        return 'unbenannte_datei';
    }
    return $sanitized;
}

/**
 * Stellt eine Datenbankverbindung her und gibt sie zurück.
 */
function get_db_connection() {
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        $conn->set_charset('utf8mb4');
        return $conn;
    } catch (mysqli_sql_exception $e) {
        error_log('DB Connection Error: ' . $e->getMessage());
        send_json_error('Fehler beim Verbinden mit der Datenbank.', 500);
    }
}