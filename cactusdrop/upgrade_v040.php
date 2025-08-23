<?php
/**
 * CactusDrop v0.4.0 - Database Upgrade Script
 * 
 * Erweitert die Datenbank für:
 * - Admin Panel
 * - Multi-Language Support  
 * - Extended Expiry Options
 * - Enhanced Security Logging
 */

require_once 'config.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🌵 CactusDrop v0.4.0 - Database Upgrade</h1>\n";
echo "<pre>\n";

$conn = get_db_connection();
$errors = [];
$success = [];

// Transactional upgrade
$conn->autocommit(false);

try {
    echo "Starting database upgrade to v0.4.0...\n\n";
    
    // 1. Erweitere files Tabelle
    echo "1. Erweitere 'files' Tabelle...\n";
    
    $alterFiles = [
        "ADD COLUMN file_size BIGINT DEFAULT NULL AFTER original_filename",
        "ADD COLUMN mime_type VARCHAR(100) DEFAULT NULL AFTER file_size", 
        "ADD COLUMN upload_ip VARCHAR(45) DEFAULT NULL AFTER mime_type",
        "ADD COLUMN downloads_count INT DEFAULT 0 AFTER upload_ip",
        "ADD COLUMN last_download_at TIMESTAMP NULL AFTER downloads_count",
        "ADD COLUMN user_agent VARCHAR(500) DEFAULT NULL AFTER last_download_at",
        "ADD COLUMN expiry_hours INT DEFAULT 24 AFTER user_agent",
        "ADD COLUMN language VARCHAR(5) DEFAULT 'de' AFTER expiry_hours"
    ];
    
    foreach ($alterFiles as $alter) {
        try {
            $conn->query("ALTER TABLE files $alter");
            echo "  ✅ $alter\n";
            $success[] = "files: $alter";
        } catch (mysqli_sql_exception $e) {
            if (strpos($e->getMessage(), "Duplicate column name") !== false) {
                echo "  ⚠️  $alter (bereits vorhanden)\n";
            } else {
                throw $e;
            }
        }
    }
    
    // Indizes hinzufügen
    $indexes = [
        "ADD INDEX idx_upload_ip (upload_ip)",
        "ADD INDEX idx_created_at (created_at)",
        "ADD INDEX idx_downloads (downloads_count)",
        "ADD INDEX idx_language (language)",
        "ADD INDEX idx_expiry_hours (expiry_hours)"
    ];
    
    foreach ($indexes as $index) {
        try {
            $conn->query("ALTER TABLE files $index");
            echo "  ✅ Index: $index\n";
            $success[] = "files index: $index";
        } catch (mysqli_sql_exception $e) {
            if (strpos($e->getMessage(), "Duplicate key name") !== false) {
                echo "  ⚠️  Index bereits vorhanden: $index\n";
            } else {
                throw $e;
            }
        }
    }
    
    echo "\n2. Erstelle Admin-Tabellen...\n";
    
    // 2. Admin Users Tabelle
    $adminUsersTable = "CREATE TABLE IF NOT EXISTS admin_users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        email VARCHAR(255) DEFAULT NULL,
        is_active TINYINT(1) DEFAULT 1,
        last_login_at TIMESTAMP NULL,
        last_login_ip VARCHAR(45) DEFAULT NULL,
        failed_login_attempts INT DEFAULT 0,
        locked_until TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_username (username),
        INDEX idx_active (is_active)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    if ($conn->query($adminUsersTable)) {
        echo "  ✅ admin_users Tabelle erstellt\n";
        $success[] = "admin_users table created";
    } else {
        throw new Exception("Fehler beim Erstellen der admin_users Tabelle: " . $conn->error);
    }
    
    // 3. Admin Sessions Tabelle  
    $adminSessionsTable = "CREATE TABLE IF NOT EXISTS admin_sessions (
        id VARCHAR(64) PRIMARY KEY,
        user_id INT NOT NULL,
        ip_address VARCHAR(45) NOT NULL,
        user_agent VARCHAR(500) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        expires_at TIMESTAMP NOT NULL,
        is_active TINYINT(1) DEFAULT 1,
        FOREIGN KEY (user_id) REFERENCES admin_users(id) ON DELETE CASCADE,
        INDEX idx_user_id (user_id),
        INDEX idx_expires (expires_at),
        INDEX idx_active (is_active)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    if ($conn->query($adminSessionsTable)) {
        echo "  ✅ admin_sessions Tabelle erstellt\n";
        $success[] = "admin_sessions table created";
    } else {
        throw new Exception("Fehler beim Erstellen der admin_sessions Tabelle: " . $conn->error);
    }
    
    // 4. Security Logs Tabelle (erweitert)
    $securityLogsTable = "CREATE TABLE IF NOT EXISTS security_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        event_type VARCHAR(50) NOT NULL,
        ip_address VARCHAR(45) NOT NULL,
        user_agent VARCHAR(500) DEFAULT NULL,
        details JSON DEFAULT NULL,
        file_id VARCHAR(16) DEFAULT NULL,
        admin_user_id INT DEFAULT NULL,
        severity ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_event_type (event_type),
        INDEX idx_ip_address (ip_address),
        INDEX idx_severity (severity),
        INDEX idx_created_at (created_at),
        INDEX idx_file_id (file_id),
        FOREIGN KEY (admin_user_id) REFERENCES admin_users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    if ($conn->query($securityLogsTable)) {
        echo "  ✅ security_logs Tabelle erstellt\n";
        $success[] = "security_logs table created";
    } else {
        throw new Exception("Fehler beim Erstellen der security_logs Tabelle: " . $conn->error);
    }
    
    // 5. System Settings Tabelle
    echo "\n3. Erstelle System-Settings...\n";
    
    $systemSettingsTable = "CREATE TABLE IF NOT EXISTS system_settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        setting_key VARCHAR(100) NOT NULL UNIQUE,
        setting_value TEXT DEFAULT NULL,
        setting_type ENUM('string', 'integer', 'boolean', 'json') DEFAULT 'string',
        description VARCHAR(255) DEFAULT NULL,
        category VARCHAR(50) DEFAULT 'general',
        is_public TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_category (category),
        INDEX idx_public (is_public)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    if ($conn->query($systemSettingsTable)) {
        echo "  ✅ system_settings Tabelle erstellt\n";
        $success[] = "system_settings table created";
    } else {
        throw new Exception("Fehler beim Erstellen der system_settings Tabelle: " . $conn->error);
    }
    
    // 6. Standard-Einstellungen
    echo "\n4. Füge Standard-Einstellungen hinzu...\n";
    
    $defaultSettings = [
        ['default_expiry_hours', '24', 'integer', 'Standard Ablaufzeit in Stunden', 'uploads', 1],
        ['max_expiry_hours', '168', 'integer', 'Maximale Ablaufzeit in Stunden (7 Tage)', 'uploads', 1],
        ['available_expiry_options', '[1,6,12,24,48,72,168]', 'json', 'Verfügbare Ablaufzeit-Optionen in Stunden', 'uploads', 1],
        ['default_language', 'de', 'string', 'Standard-Sprache', 'localization', 1],
        ['available_languages', '["de","en"]', 'json', 'Verfügbare Sprachen', 'localization', 1],
        ['admin_session_timeout', '1800', 'integer', 'Admin-Session Timeout in Sekunden (30 Min)', 'admin', 0],
        ['max_failed_login_attempts', '5', 'integer', 'Maximale fehlgeschlagene Login-Versuche', 'admin', 0],
        ['lockout_duration_minutes', '15', 'integer', 'Sperrzeit nach fehlgeschlagenen Logins (Minuten)', 'admin', 0],
        ['enable_statistics', '1', 'boolean', 'Statistiken erfassen aktivieren', 'admin', 0],
        ['log_retention_days', '30', 'integer', 'Log-Aufbewahrungszeit in Tagen', 'admin', 0]
    ];
    
    $settingsStmt = $conn->prepare("INSERT INTO system_settings (setting_key, setting_value, setting_type, description, category, is_public) VALUES (?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE updated_at = CURRENT_TIMESTAMP");
    
    foreach ($defaultSettings as $setting) {
        $settingsStmt->bind_param('sssssi', $setting[0], $setting[1], $setting[2], $setting[3], $setting[4], $setting[5]);
        if ($settingsStmt->execute()) {
            echo "  ✅ Setting: {$setting[0]}\n";
            $success[] = "setting: {$setting[0]}";
        }
    }
    $settingsStmt->close();
    
    // 7. Standard Admin-User erstellen
    echo "\n5. Erstelle Standard-Admin...\n";
    
    $adminStmt = $conn->prepare("INSERT INTO admin_users (username, password_hash, email) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE updated_at = CURRENT_TIMESTAMP");
    $adminUsername = 'admin';
    $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
    $adminEmail = 'admin@example.com';
    
    $adminStmt->bind_param('sss', $adminUsername, $adminPassword, $adminEmail);
    if ($adminStmt->execute()) {
        echo "  ✅ Standard-Admin erstellt (admin / admin123)\n";
        echo "  ⚠️  WICHTIG: Passwort nach erstem Login ändern!\n";
        $success[] = "admin user created";
    }
    $adminStmt->close();
    
    // 8. Upload Statistics Tabelle
    echo "\n6. Erstelle Upload-Statistiken Tabelle...\n";
    
    $uploadStatsTable = "CREATE TABLE IF NOT EXISTS upload_stats (
        id INT AUTO_INCREMENT PRIMARY KEY,
        date DATE NOT NULL,
        hour TINYINT NOT NULL,
        uploads_count INT DEFAULT 0,
        total_size_mb DECIMAL(10,2) DEFAULT 0,
        unique_ips INT DEFAULT 0,
        downloads_count INT DEFAULT 0,
        language VARCHAR(5) DEFAULT 'de',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY unique_date_hour_lang (date, hour, language),
        INDEX idx_date (date),
        INDEX idx_language (language)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    if ($conn->query($uploadStatsTable)) {
        echo "  ✅ upload_stats Tabelle erstellt\n";
        $success[] = "upload_stats table created";
    } else {
        throw new Exception("Fehler beim Erstellen der upload_stats Tabelle: " . $conn->error);
    }
    
    // 9. Cleanup Events
    echo "\n7. Erstelle Cleanup-Events...\n";
    
    $cleanupEvent = "CREATE EVENT IF NOT EXISTS cleanup_expired_sessions
    ON SCHEDULE EVERY 1 HOUR
    DO
    BEGIN
        DELETE FROM admin_sessions WHERE expires_at < NOW();
        DELETE FROM security_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY);
    END";
    
    if ($conn->query($cleanupEvent)) {
        echo "  ✅ Cleanup-Event erstellt\n";
        $success[] = "cleanup event created";
    } else {
        echo "  ⚠️  Cleanup-Event konnte nicht erstellt werden (MySQL Events möglicherweise deaktiviert)\n";
    }
    
    // Commit transaction
    $conn->commit();
    echo "\n" . str_repeat("=", 60) . "\n";
    echo "✅ DATABASE UPGRADE ERFOLGREICH ABGESCHLOSSEN!\n";
    echo str_repeat("=", 60) . "\n\n";
    
    echo "📊 Upgrade-Zusammenfassung:\n";
    echo "- " . count($success) . " Operationen erfolgreich\n";
    echo "- 0 Fehler aufgetreten\n\n";
    
    echo "🎯 Neue Features verfügbar:\n";
    echo "- 🔐 Admin Panel: /admin.php\n";
    echo "- 🌐 Multi-Language Support (DE/EN)\n";
    echo "- ⏰ Erweiterte Ablaufzeiten (1h - 1 Woche)\n";
    echo "- 📈 Detaillierte Statistiken\n";
    echo "- 🛡️ Enhanced Security Logging\n\n";
    
    echo "🔑 Admin-Zugang:\n";
    echo "- URL: " . (defined('APP_URL') ? APP_URL : 'https://ihre-domain.de') . "/admin.php\n";
    echo "- Benutzername: admin\n";
    echo "- Passwort: admin123\n";
    echo "- ⚠️  WICHTIG: Passwort nach erstem Login ändern!\n\n";
    
    echo "🚀 CactusDrop v0.4.0 ist bereit!\n";
    
} catch (Exception $e) {
    $conn->rollback();
    echo "\n❌ UPGRADE FEHLGESCHLAGEN:\n";
    echo "Fehler: " . $e->getMessage() . "\n";
    echo "\nDatenbank wurde zurückgesetzt.\n";
    $errors[] = $e->getMessage();
} finally {
    $conn->autocommit(true);
    $conn->close();
}

echo "</pre>\n";

if (empty($errors)) {
    echo '<div style="background: #065f46; color: #a7f3d0; padding: 15px; border-radius: 8px; margin: 20px 0;">';
    echo '<h3>🎉 Upgrade erfolgreich!</h3>';
    echo '<p>Sie können nun das Admin Panel verwenden: <a href="admin.php" style="color: #34d399; text-decoration: underline;">Admin Panel öffnen</a></p>';
    echo '<p>Oder zur Haupt-Anwendung: <a href="index.php" style="color: #34d399; text-decoration: underline;">CactusDrop öffnen</a></p>';
    echo '</div>';
} else {
    echo '<div style="background: #7f1d1d; color: #fca5a5; padding: 15px; border-radius: 8px; margin: 20px 0;">';
    echo '<h3>❌ Upgrade fehlgeschlagen</h3>';
    echo '<p>Bitte prüfen Sie die Fehlermeldungen oben und kontaktieren Sie den Support.</p>';
    echo '</div>';
}
?>