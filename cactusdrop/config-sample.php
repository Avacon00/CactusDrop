<?php
// config-sample.php - CactusDrop v0.4.0 Configuration Template
// Kopiere diese Datei zu config.php und passe die Werte an

define('DB_HOST', 'localhost');
define('DB_USER', 'your_db_user');
define('DB_PASS', 'your_db_password');
define('DB_NAME', 'your_db_name');

define('APP_URL', 'https://your-domain.com/cactusdrop');
define('UPLOAD_DIR', __DIR__ . '/uploads/');

date_default_timezone_set('Europe/Berlin');

function get_db_connection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        die('Datenbankverbindung fehlgeschlagen: ' . $conn->connect_error);
    }
    return $conn;
}

// v0.4.0 Enterprise Functions
function getSystemSettings() {
    $conn = get_db_connection();
    $result = $conn->query("SELECT * FROM system_settings ORDER BY category, setting_key");
    $settings = [];
    if ($result) {
        $settings = $result->fetch_all(MYSQLI_ASSOC);
    }
    $conn->close();
    return $settings;
}

function getSetting($key, $default = null) {
    $conn = get_db_connection();
    $stmt = $conn->prepare("SELECT setting_value FROM system_settings WHERE setting_key = ?");
    $stmt->bind_param('s', $key);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $value = $row['setting_value'];
        $stmt->close();
        $conn->close();
        return $value;
    }
    
    $stmt->close();
    $conn->close();
    return $default;
}

// Language Support
function getCurrentLang() {
    if (isset($_GET['lang']) && in_array($_GET['lang'], ['de', 'en'])) {
        setcookie('cactusdrop_lang', $_GET['lang'], time() + (86400 * 365), '/');
        return $_GET['lang'];
    }
    
    if (isset($_COOKIE['cactusdrop_lang']) && in_array($_COOKIE['cactusdrop_lang'], ['de', 'en'])) {
        return $_COOKIE['cactusdrop_lang'];
    }
    
    // Browser detection
    if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
        $browserLangs = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
        foreach ($browserLangs as $lang) {
            $lang = trim(substr($lang, 0, 2));
            if (in_array($lang, ['de', 'en'])) {
                return $lang;
            }
        }
    }
    
    return 'de'; // Default
}

// Admin Security Class
class AdminSecurity {
    public static function logSecurityEvent($eventType, $details = '', $severity = 'medium', $fileId = null, $adminUserId = null) {
        $conn = get_db_connection();
        $stmt = $conn->prepare("INSERT INTO security_logs (event_type, ip_address, user_agent, details, file_id, admin_user_id, severity) VALUES (?, ?, ?, ?, ?, ?, ?)");
        
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        $stmt->bind_param('sssssis', $eventType, $ip, $userAgent, $details, $fileId, $adminUserId, $severity);
        $stmt->execute();
        $stmt->close();
        $conn->close();
    }
}
?>