<?php
/**
 * CactusDrop Webinstaller v0.3.0
 * 
 * 3-Schritt Webinstaller mit modernem UI
 * - Schritt 1: Dateien extrahieren (cactusdrop.zip → cactusdrop/)
 * - Schritt 2: Datenbank prüfen 
 * - Schritt 3: Good Job + Konfetti + Weiterleitung
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

define('CACTUSDROP_VERSION', '0.4.0');

$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
$errors = [];
$warnings = [];
$success = [];
$ajaxResponse = null;

// AJAX Handler für Datenbank-Test
if (isset($_POST['action']) && $_POST['action'] === 'test_db') {
    header('Content-Type: application/json');
    
    $db_host = trim($_POST['db_host'] ?? '');
    $db_name = trim($_POST['db_name'] ?? '');
    $db_user = trim($_POST['db_user'] ?? '');
    $db_pass = trim($_POST['db_pass'] ?? '');
    
    if (empty($db_host) || empty($db_name) || empty($db_user)) {
        echo json_encode([
            'success' => false, 
            'message' => 'Host, Datenbankname und Benutzername sind erforderlich.'
        ]);
        exit;
    }
    
    try {
        // Detaillierte Verbindungsprüfung
        $conn = @new mysqli($db_host, $db_user, $db_pass, $db_name);
        
        if ($conn->connect_error) {
            throw new Exception("Connection failed: " . $conn->connect_error);
        }
        
        // Test-Query ausführen
        $result = $conn->query("SELECT 1 as test");
        if (!$result) {
            throw new Exception("Database access failed: " . $conn->error);
        }
        
        // Berechtigungen testen
        $result = $conn->query("SHOW TABLES");
        if (!$result) {
            throw new Exception("No permission to show tables: " . $conn->error);
        }
        
        $conn->close();
        
        echo json_encode([
            'success' => true,
            'message' => '✅ Datenbankverbindung erfolgreich! Berechtigungen OK.'
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => '❌ Verbindung fehlgeschlagen: ' . $e->getMessage() . "\n\nTipp: Prüfen Sie Benutzername, Passwort und Datenbankberechtigungen."
        ]);
    }
    exit;
}

// Step Handlers
if ($step === 1 && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['extract'])) {
    if (extractFilesFromZip()) {
        $step = 2;
    }
}

if ($step === 2 && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['configure'])) {
    if (createDatabaseAndConfig()) {
        $step = 3;
    }
}

// Functions
function extractFilesFromZip() {
    global $success, $errors;
    
    $zipFile = 'cactusdrop.zip';
    $targetDir = 'cactusdrop/';
    
    if (!file_exists($zipFile)) {
        $errors[] = "❌ Datei 'cactusdrop.zip' nicht gefunden!";
        return false;
    }
    
    if (!extension_loaded('zip')) {
        $errors[] = "❌ PHP ZIP-Erweiterung ist nicht verfügbar!";
        return false;
    }
    
    try {
        $zip = new ZipArchive;
        if ($zip->open($zipFile) !== TRUE) {
            throw new Exception("Konnte ZIP-Datei nicht öffnen.");
        }
        
        // Target-Verzeichnis erstellen
        if (!is_dir($targetDir)) {
            if (!mkdir($targetDir, 0755, true)) {
                throw new Exception("Konnte Zielverzeichnis '$targetDir' nicht erstellen.");
            }
        }
        
        // Extrahieren
        if (!$zip->extractTo($targetDir)) {
            throw new Exception("Extrahierung fehlgeschlagen.");
        }
        
        $extractedFiles = $zip->numFiles;
        $zip->close();
        
        // Berechtigungen setzen
        setCorrectPermissions($targetDir);
        
        $success[] = "✅ {$extractedFiles} Dateien erfolgreich extrahiert.";
        $success[] = "✅ Dateiberechtigungen gesetzt (755/644).";
        
        return true;
        
    } catch (Exception $e) {
        $errors[] = "❌ " . $e->getMessage();
        return false;
    }
}

function setCorrectPermissions($dir) {
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    
    foreach ($iterator as $item) {
        if ($item->isDir()) {
            chmod($item->getRealPath(), 0755);
        } else {
            chmod($item->getRealPath(), 0644);
        }
    }
}

function createDatabaseAndConfig() {
    global $success, $errors;
    
    $db_host = trim($_POST['db_host'] ?? '');
    $db_name = trim($_POST['db_name'] ?? '');
    $db_user = trim($_POST['db_user'] ?? '');
    $db_pass = trim($_POST['db_pass'] ?? '');
    
    if (empty($db_host) || empty($db_name) || empty($db_user)) {
        $errors[] = "❌ Alle Datenbankfelder sind erforderlich.";
        return false;
    }
    
    try {
        // Verbindung testen
        $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
        if ($conn->connect_error) {
            throw new Exception("Datenbankverbindung: " . $conn->connect_error);
        }
        
        // v0.4.0 Schema mit Enterprise Features
        $schema_sql = "CREATE TABLE IF NOT EXISTS `files` (
            `id` varchar(16) NOT NULL,
            `secret_token` varchar(64) NOT NULL,
            `original_filename` varchar(255) NOT NULL,
            `file_size` bigint DEFAULT NULL,
            `mime_type` varchar(100) DEFAULT NULL,
            `upload_ip` varchar(45) DEFAULT NULL,
            `downloads_count` int DEFAULT 0,
            `last_download_at` timestamp NULL,
            `user_agent` varchar(500) DEFAULT NULL,
            `expiry_hours` int DEFAULT 24,
            `language` varchar(5) DEFAULT 'de',
            `is_onetime` tinyint(1) NOT NULL DEFAULT 0,
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            `delete_at` timestamp NOT NULL,
            PRIMARY KEY (`id`),
            KEY `idx_delete_at` (`delete_at`),
            KEY `idx_upload_ip` (`upload_ip`),
            KEY `idx_created_at` (`created_at`),
            KEY `idx_downloads` (`downloads_count`),
            KEY `idx_language` (`language`),
            KEY `idx_expiry_hours` (`expiry_hours`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        
        CREATE TABLE IF NOT EXISTS `rate_limits` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `ip_address` varchar(45) NOT NULL,
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            PRIMARY KEY (`id`),
            KEY `idx_ip_time` (`ip_address`, `created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        
        CREATE TABLE IF NOT EXISTS `admin_users` (
            `id` int AUTO_INCREMENT PRIMARY KEY,
            `username` varchar(50) NOT NULL UNIQUE,
            `password_hash` varchar(255) NOT NULL,
            `email` varchar(255) DEFAULT NULL,
            `is_active` tinyint(1) DEFAULT 1,
            `last_login_at` timestamp NULL,
            `last_login_ip` varchar(45) DEFAULT NULL,
            `failed_login_attempts` int DEFAULT 0,
            `locked_until` timestamp NULL,
            `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX `idx_username` (`username`),
            INDEX `idx_active` (`is_active`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        
        CREATE TABLE IF NOT EXISTS `admin_sessions` (
            `id` varchar(64) PRIMARY KEY,
            `user_id` int NOT NULL,
            `ip_address` varchar(45) NOT NULL,
            `user_agent` varchar(500) DEFAULT NULL,
            `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
            `expires_at` timestamp NOT NULL,
            `is_active` tinyint(1) DEFAULT 1,
            FOREIGN KEY (`user_id`) REFERENCES `admin_users`(`id`) ON DELETE CASCADE,
            INDEX `idx_user_id` (`user_id`),
            INDEX `idx_expires` (`expires_at`),
            INDEX `idx_active` (`is_active`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        
        CREATE TABLE IF NOT EXISTS `security_logs` (
            `id` int AUTO_INCREMENT PRIMARY KEY,
            `event_type` varchar(50) NOT NULL,
            `ip_address` varchar(45) NOT NULL,
            `user_agent` varchar(500) DEFAULT NULL,
            `details` json DEFAULT NULL,
            `file_id` varchar(16) DEFAULT NULL,
            `admin_user_id` int DEFAULT NULL,
            `severity` enum('low', 'medium', 'high', 'critical') DEFAULT 'medium',
            `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_event_type` (`event_type`),
            INDEX `idx_ip_address` (`ip_address`),
            INDEX `idx_severity` (`severity`),
            INDEX `idx_created_at` (`created_at`),
            INDEX `idx_file_id` (`file_id`),
            FOREIGN KEY (`admin_user_id`) REFERENCES `admin_users`(`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        
        CREATE TABLE IF NOT EXISTS `system_settings` (
            `id` int AUTO_INCREMENT PRIMARY KEY,
            `setting_key` varchar(100) NOT NULL UNIQUE,
            `setting_value` text DEFAULT NULL,
            `setting_type` enum('string', 'integer', 'boolean', 'json') DEFAULT 'string',
            `description` varchar(255) DEFAULT NULL,
            `category` varchar(50) DEFAULT 'general',
            `is_public` tinyint(1) DEFAULT 0,
            `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX `idx_category` (`category`),
            INDEX `idx_public` (`is_public`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        
        CREATE TABLE IF NOT EXISTS `upload_stats` (
            `id` int AUTO_INCREMENT PRIMARY KEY,
            `date` date NOT NULL,
            `hour` tinyint NOT NULL,
            `uploads_count` int DEFAULT 0,
            `total_size_mb` decimal(10,2) DEFAULT 0,
            `unique_ips` int DEFAULT 0,
            `downloads_count` int DEFAULT 0,
            `language` varchar(5) DEFAULT 'de',
            `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY `unique_date_hour_lang` (`date`, `hour`, `language`),
            INDEX `idx_date` (`date`),
            INDEX `idx_language` (`language`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        
        if (!$conn->multi_query($schema_sql)) {
            throw new Exception("Schema-Erstellung: " . $conn->error);
        }
        
        // Warten auf alle Queries
        do {
            $conn->store_result();
        } while ($conn->next_result());
        
        // config.php erstellen
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        $path = rtrim(dirname($_SERVER['PHP_SELF']), '/') . '/cactusdrop';
        $app_url = "{$protocol}://{$host}{$path}";
        
        $config_content = createConfigContent($db_host, $db_user, $db_pass, $db_name, $app_url);
        
        if (!file_put_contents('cactusdrop/config.php', $config_content)) {
            throw new Exception("Konnte config.php nicht erstellen.");
        }
        
        // Standard-Einstellungen hinzufügen (NACH Schema-Erstellung)
        $defaultSettings = [
            // Upload Settings
            ['default_expiry_hours', '24', 'integer', 'Standard Ablaufzeit in Stunden', 'uploads', 1],
            ['max_expiry_hours', '168', 'integer', 'Maximale Ablaufzeit in Stunden (7 Tage)', 'uploads', 1],
            ['available_expiry_options', '[1,6,12,24,48,72,168]', 'json', 'Verfügbare Ablaufzeit-Optionen in Stunden', 'uploads', 1],
            
            // Localization
            ['default_language', 'de', 'string', 'Standard-Sprache', 'localization', 1],
            ['available_languages', '["de","en"]', 'json', 'Verfügbare Sprachen', 'localization', 1],
            
            // Admin Settings
            ['admin_session_timeout', '1800', 'integer', 'Admin-Session Timeout in Sekunden (30 Min)', 'admin', 0],
            ['max_failed_login_attempts', '5', 'integer', 'Maximale fehlgeschlagene Login-Versuche', 'admin', 0],
            ['lockout_duration_minutes', '15', 'integer', 'Sperrzeit nach fehlgeschlagenen Logins (Minuten)', 'admin', 0],
            ['enable_statistics', '1', 'boolean', 'Statistiken erfassen aktivieren', 'admin', 0],
            ['log_retention_days', '30', 'integer', 'Log-Aufbewahrungszeit in Tagen', 'admin', 0],
            
            // DSGVO Privacy Settings (Privacy-by-Default)
            ['privacy_mode_enabled', '1', 'boolean', 'DSGVO Privacy-Modus aktivieren', 'privacy', 1],
            ['anonymize_ips_enabled', '1', 'boolean', 'IP-Adressen automatisch anonymisieren', 'privacy', 1],
            ['minimal_logging_enabled', '0', 'boolean', 'Nur kritische Events loggen', 'privacy', 1],
            ['auto_cleanup_enabled', '1', 'boolean', 'Automatische Datenbereinigung aktivieren', 'privacy', 1],
            ['user_consent_required', '0', 'boolean', 'Benutzer-Einwilligung für Statistiken erforderlich', 'privacy', 1]
        ];
        
        $settingsStmt = $conn->prepare("INSERT INTO system_settings (setting_key, setting_value, setting_type, description, category, is_public) VALUES (?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE updated_at = CURRENT_TIMESTAMP");
        
        foreach ($defaultSettings as $setting) {
            $settingsStmt->bind_param('sssssi', $setting[0], $setting[1], $setting[2], $setting[3], $setting[4], $setting[5]);
            $settingsStmt->execute();
        }
        $settingsStmt->close();
        
        // Standard Admin-User erstellen
        $adminStmt = $conn->prepare("INSERT INTO admin_users (username, password_hash, email) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE updated_at = CURRENT_TIMESTAMP");
        $adminUsername = 'admin';
        $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $adminEmail = 'admin@example.com';
        
        $adminStmt->bind_param('sss', $adminUsername, $adminPassword, $adminEmail);
        $adminStmt->execute();
        $adminStmt->close();
        
        // Verbindung erst JETZT schließen
        $conn->close();

        $success[] = "✅ v0.4.0 Datenbank-Schema erstellt."; 
        $success[] = "✅ Enterprise Features aktiviert."; 
        $success[] = "✅ Standard-Admin erstellt (admin/admin123).";
        $success[] = "✅ Konfigurationsdatei erstellt.";
        $success[] = "✅ WICHTIG: Admin-Panel unter /admin.php verfügbar!";
        
        return true;
        
    } catch (Exception $e) {
        $errors[] = "❌ " . $e->getMessage();
        return false;
    }
}

function createConfigContent($host, $user, $pass, $name, $url) {
    $pass_escaped = addslashes($pass);
    return "<?php
// config.php - CactusDrop v" . CACTUSDROP_VERSION . "

define('DB_HOST', '{$host}');
define('DB_USER', '{$user}');
define('DB_PASS', '{$pass_escaped}');
define('DB_NAME', '{$name}');

define('APP_URL', '{$url}');
define('UPLOAD_DIR', __DIR__ . '/uploads/');

date_default_timezone_set('Europe/Berlin');

function get_db_connection() {
    \$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if (\$conn->connect_error) {
        die('Datenbankverbindung fehlgeschlagen: ' . \$conn->connect_error);
    }
    return \$conn;
}
?>";
}
?>
<!DOCTYPE html>
<html lang="de" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CactusDrop Installer v<?php echo CACTUSDROP_VERSION; ?></title>
    
    <!-- Styles & Fonts (identisch zu index.html) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .fade-in { animation: fadeIn 0.5s ease-in-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        .hidden { display: none; }
        
        /* Konfetti Animation */
        .confetti {
            position: fixed;
            width: 10px;
            height: 10px;
            background: #2f855a;
            animation: confetti-fall 3s linear infinite;
            z-index: 1000;
        }
        .confetti:nth-child(2n) { background: #10b981; animation-delay: -0.5s; }
        .confetti:nth-child(3n) { background: #34d399; animation-delay: -1s; }
        .confetti:nth-child(4n) { background: #6ee7b7; animation-delay: -1.5s; }
        
        @keyframes confetti-fall {
            0% { transform: translateY(-100vh) rotate(0deg); opacity: 1; }
            100% { transform: translateY(100vh) rotate(720deg); opacity: 0; }
        }
    </style>
</head>
<body class="bg-gray-900 text-gray-200 flex flex-col items-center justify-center min-h-screen p-4">

    <div id="app" class="w-full max-w-md mx-auto">
        
        <!-- Header (identisch zu index.html) -->
        <header class="text-center mb-8">
            <div class="flex items-center justify-center gap-3 mb-2">
                <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#2f855a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 9V2m0 7v13m0-13c-2.833 0-5 2.167-5 5v0c0 2.833 2.167 5 5 5m0-10c2.833 0 5 2.167 5 5v0c0 2.833-2.167 5-5 5m-5 5h10"/>
                </svg>
                <h1 class="text-4xl font-bold text-gray-100">Cactus<span class="text-green-500">Drop</span></h1>
            </div>
            <p class="text-gray-400">Webinstaller v<?php echo CACTUSDROP_VERSION; ?></p>
            
            <!-- Progress Bar -->
            <div class="w-full bg-gray-700 rounded-full h-2.5 mt-4">
                <div class="bg-green-500 h-2.5 rounded-full transition-all duration-500" style="width: <?php echo ($step / 3 * 100); ?>%"></div>
            </div>
            <p class="text-xs text-gray-500 mt-1">Schritt <?php echo $step; ?> von 3</p>
        </header>

        <main id="main-content" class="w-full bg-gray-800 rounded-2xl shadow-lg p-6 transition-all duration-300">
            
            <?php
            // Error/Success Messages
            if (!empty($errors)) {
                echo '<div class="bg-red-900/50 border border-red-700 text-red-300 px-4 py-3 rounded-lg mb-6 fade-in">';
                foreach ($errors as $error) { echo "<p class='mb-1'>{$error}</p>"; }
                echo '</div>';
            }
            
            if (!empty($success)) {
                echo '<div class="bg-green-900/50 border border-green-700 text-green-300 px-4 py-3 rounded-lg mb-6 fade-in">';
                foreach ($success as $s) { echo "<p class='mb-1'>{$s}</p>"; }
                echo '</div>';
            }
            ?>

            <?php if ($step === 1): ?>
                <!-- SCHRITT 1: Dateien extrahieren -->
                <div class="text-center">
                    <h2 class="text-2xl font-bold mb-4 text-green-400">📦 Schritt 1: Dateien extrahieren</h2>
                    <p class="text-gray-400 mb-6">CactusDrop wird aus 'cactusdrop.zip' in den Ordner 'cactusdrop/' extrahiert.</p>
                    
                    <div class="bg-gray-900 rounded-lg p-4 mb-6 text-left">
                        <h3 class="font-semibold text-green-400 mb-2">Was passiert:</h3>
                        <ul class="text-sm text-gray-300 space-y-1">
                            <li>✓ ZIP-Datei wird extrahiert</li>
                            <li>✓ Ordner 'cactusdrop/' wird erstellt</li>
                            <li>✓ Dateiberechtigungen werden gesetzt (755/644)</li>
                        </ul>
                    </div>
                    
                    <?php if (file_exists('cactusdrop.zip')): ?>
                        <form method="POST" action="?step=1">
                            <button type="submit" name="extract" class="w-full bg-green-600 hover:bg-green-500 text-white font-bold py-3 px-4 rounded-lg transition-all">
                                🚀 Dateien extrahieren
                            </button>
                        </form>
                    <?php else: ?>
                        <div class="bg-red-900/50 border border-red-700 text-red-300 px-4 py-3 rounded-lg">
                            <p class="font-bold">❌ 'cactusdrop.zip' nicht gefunden!</p>
                            <p class="text-sm mt-2">Bitte laden Sie die ZIP-Datei in dasselbe Verzeichnis wie den Installer hoch.</p>
                        </div>
                    <?php endif; ?>
                </div>

            <?php elseif ($step === 2): ?>
                <!-- SCHRITT 2: Datenbank prüfen -->
                <div>
                    <h2 class="text-2xl font-bold mb-4 text-green-400 text-center">🛠 Schritt 2: Datenbank prüfen</h2>
                    <p class="text-gray-400 mb-6 text-center">Geben Sie Ihre MySQL/MariaDB-Zugangsdaten ein.</p>
                    
                    <form method="POST" action="?step=2" id="db-form" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-1">DB-Host</label>
                            <input type="text" name="db_host" id="db_host" value="localhost" required 
                                   class="w-full bg-gray-700 border-gray-600 rounded-md p-3 text-white focus:ring-2 focus:ring-green-500 focus:border-green-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-1">DB-Name *</label>
                            <input type="text" name="db_name" id="db_name" required 
                                   class="w-full bg-gray-700 border-gray-600 rounded-md p-3 text-white focus:ring-2 focus:ring-green-500 focus:border-green-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-1">Benutzername *</label>
                            <input type="text" name="db_user" id="db_user" required 
                                   class="w-full bg-gray-700 border-gray-600 rounded-md p-3 text-white focus:ring-2 focus:ring-green-500 focus:border-green-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-1">Passwort</label>
                            <input type="password" name="db_pass" id="db_pass" 
                                   class="w-full bg-gray-700 border-gray-600 rounded-md p-3 text-white focus:ring-2 focus:ring-green-500 focus:border-green-500">
                        </div>
                        
                        <!-- Test Button -->
                        <button type="button" id="test-db-btn" class="w-full bg-blue-600 hover:bg-blue-500 text-white font-bold py-2 px-4 rounded-lg transition-all mb-4">
                            🔍 Verbindung prüfen
                        </button>
                        
                        <!-- Status Message -->
                        <div id="db-status" class="hidden rounded-lg px-4 py-3 mb-4"></div>
                        
                        <!-- Continue Button (disabled initially) -->
                        <button type="submit" name="configure" id="continue-btn" disabled 
                                class="w-full bg-gray-600 text-gray-400 font-bold py-3 px-4 rounded-lg transition-all cursor-not-allowed">
                            ⏭ Weiter (erst Verbindung prüfen)
                        </button>
                        
                        <!-- Emergency Continue (if AJAX fails) -->
                        <button type="button" id="force-continue-btn" onclick="enableContinue()" 
                                class="w-full bg-yellow-600 hover:bg-yellow-500 text-white font-bold py-2 px-4 rounded-lg transition-all mt-2" style="display: none;">
                            ⚠️ Trotzdem fortfahren (bei AJAX-Problemen)
                        </button>
                    </form>
                </div>

            <?php elseif ($step === 3): ?>
                <!-- SCHRITT 3: Good Job + Konfetti -->
                <div class="text-center">
                    <div id="confetti-container"></div>
                    
                    <h2 class="text-4xl font-bold mb-4 text-green-400">🎉 Good Job!</h2>
                    <p class="text-xl text-gray-300 mb-6">CactusDrop wurde erfolgreich installiert!</p>
                    
                    <div class="bg-green-900/50 border border-green-700 rounded-lg p-4 mb-6">
                        <p class="text-green-300 font-semibold mb-2">Installation abgeschlossen ✅</p>
                        <p class="text-sm text-green-200">Sie werden automatisch zu CactusDrop weitergeleitet...</p>
                    </div>
                    
                    <div class="bg-gray-900 rounded-lg p-4 mb-6 text-left">
                        <h3 class="font-semibold text-blue-400 mb-2">🚀 v0.4.0 Enterprise Features:</h3>
                        <ul class="text-sm text-green-300 space-y-1">
                            <li>✅ Admin Panel (/admin.php)</li>
                            <li>✅ Multi-Language Support (DE/EN)</li>
                            <li>✅ Erweiterte Ablaufzeiten (1h-1 Woche)</li>
                            <li>✅ Security Logs & Statistiken</li>
                        </ul>
                    </div>
                    
                    <div class="bg-red-900/50 border border-red-700 rounded-lg p-4 mb-6 text-left">
                        <h3 class="font-semibold text-red-400 mb-2">🔐 Admin-Zugang:</h3>
                        <ul class="text-sm text-red-300 space-y-1">
                            <li>• URL: /admin.php</li>
                            <li>• Benutzername: admin</li>
                            <li>• Passwort: admin123</li>
                            <li>• ⚠️ Passwort nach erstem Login ändern!</li>
                        </ul>
                    </div>
                    
                    <div class="bg-gray-900 rounded-lg p-4 mb-6 text-left">
                        <h3 class="font-semibold text-blue-400 mb-2">📋 Nächste Schritte:</h3>
                        <ul class="text-sm text-gray-300 space-y-1">
                            <li>• Installer-Datei löschen (Sicherheit)</li>
                            <li>• Admin-Passwort ändern</li>
                            <li>• Cronjob für cleanup.php einrichten</li>
                            <li>• HTTPS aktivieren (für PWA-Features)</li>
                        </ul>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <a href="cactusdrop/index.php" class="inline-block bg-green-600 hover:bg-green-500 text-white font-bold py-3 px-4 rounded-lg transition-all text-center">
                            🌵 CactusDrop öffnen
                        </a>
                        <a href="cactusdrop/admin.php" class="inline-block bg-blue-600 hover:bg-blue-500 text-white font-bold py-3 px-4 rounded-lg transition-all text-center">
                            🔐 Admin Panel
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </main>

        <footer class="text-center mt-8 text-xs text-gray-500">
            <p>CactusDrop Webinstaller v<?php echo CACTUSDROP_VERSION; ?></p>
        </footer>
    </div>

    <script>
    <?php if ($step === 2): ?>
    // AJAX Datenbank-Test
    document.getElementById('test-db-btn').addEventListener('click', function() {
        const btn = this;
        const status = document.getElementById('db-status');
        const continueBtn = document.getElementById('continue-btn');
        
        btn.disabled = true;
        btn.innerHTML = '🔄 Prüfe...';
        status.classList.add('hidden');
        
        const formData = new FormData();
        formData.append('action', 'test_db');
        formData.append('db_host', document.getElementById('db_host').value);
        formData.append('db_name', document.getElementById('db_name').value);
        formData.append('db_user', document.getElementById('db_user').value);
        formData.append('db_pass', document.getElementById('db_pass').value);
        
        fetch('', { method: 'POST', body: formData })
        .then(response => response.json())
        .then(data => {
            status.classList.remove('hidden');
            if (data.success) {
                status.className = 'bg-green-900/50 border border-green-700 text-green-300 rounded-lg px-4 py-3 mb-4';
                status.innerHTML = data.message;
                continueBtn.disabled = false;
                continueBtn.className = 'w-full bg-green-600 hover:bg-green-500 text-white font-bold py-3 px-4 rounded-lg transition-all';
                continueBtn.innerHTML = '⏭ Weiter';
            } else {
                status.className = 'bg-red-900/50 border border-red-700 text-red-300 rounded-lg px-4 py-3 mb-4';
                status.innerHTML = data.message;
                continueBtn.disabled = true;
                continueBtn.className = 'w-full bg-gray-600 text-gray-400 font-bold py-3 px-4 rounded-lg transition-all cursor-not-allowed';
                continueBtn.innerHTML = '⏭ Weiter (erst Verbindung prüfen)';
            }
        })
        .catch(error => {
            status.classList.remove('hidden');
            status.className = 'bg-red-900/50 border border-red-700 text-red-300 rounded-lg px-4 py-3 mb-4';
            status.innerHTML = '❌ AJAX-Fehler beim Testen. Versuchen Sie "Trotzdem fortfahren" wenn Ihre Daten korrekt sind.';
            
            // Notfall-Button anzeigen
            document.getElementById('force-continue-btn').style.display = 'block';
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = '🔍 Verbindung prüfen';
        });
    });
    
    // Emergency Continue Function (when AJAX fails but connection works)
    function enableContinue() {
        const continueBtn = document.getElementById('continue-btn');
        const status = document.getElementById('db-status');
        
        continueBtn.disabled = false;
        continueBtn.className = 'w-full bg-yellow-600 hover:bg-yellow-500 text-white font-bold py-3 px-4 rounded-lg transition-all';
        continueBtn.innerHTML = '⚠️ Fortfahren (auf eigene Gefahr)';
        
        status.classList.remove('hidden');
        status.className = 'bg-yellow-900/50 border border-yellow-700 text-yellow-300 rounded-lg px-4 py-3 mb-4';
        status.innerHTML = '⚠️ AJAX-Test übersprungen. Wenn die Installation fehlschlägt, überprüfen Sie Ihre Datenbankdaten.';
        
        // Emergency button ausblenden
        document.getElementById('force-continue-btn').style.display = 'none';
    }
    <?php endif; ?>

    <?php if ($step === 3): ?>
    // Konfetti-Animation
    function createConfetti() {
        const container = document.getElementById('confetti-container');
        const colors = ['#2f855a', '#10b981', '#34d399', '#6ee7b7'];
        
        for (let i = 0; i < 50; i++) {
            setTimeout(() => {
                const confetti = document.createElement('div');
                confetti.className = 'confetti';
                confetti.style.left = Math.random() * 100 + 'vw';
                confetti.style.animationDuration = (Math.random() * 2 + 2) + 's';
                confetti.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
                container.appendChild(confetti);
                
                // Remove after animation
                setTimeout(() => {
                    if (confetti.parentNode) {
                        confetti.parentNode.removeChild(confetti);
                    }
                }, 5000);
            }, i * 100);
        }
    }
    
    // Start confetti and redirect
    createConfetti();
    setTimeout(() => {
        window.location.href = 'cactusdrop/index.php';
    }, 5000);
    <?php endif; ?>
    </script>

</body>
</html>