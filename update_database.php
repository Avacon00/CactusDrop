<?php
/**
 * Database Update Script für CactusDrop v0.2.8
 * Erweitert das Schema um Rate-Limiting und weitere Sicherheitsfeatures
 */

require_once 'config.php';

echo "🌵 CactusDrop Database Update Script\n";
echo "=====================================\n\n";

try {
    $conn = get_db_connection();
    echo "✅ Datenbankverbindung erfolgreich.\n\n";
    
    // 1. Rate-Limiting Tabelle erstellen
    echo "📊 Erstelle Rate-Limiting Tabelle...\n";
    $sql_rate_limits = "CREATE TABLE IF NOT EXISTS `rate_limits` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `ip_address` varchar(45) NOT NULL,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        PRIMARY KEY (`id`),
        KEY `idx_ip_created` (`ip_address`, `created_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    
    if ($conn->query($sql_rate_limits)) {
        echo "✅ Rate-Limiting Tabelle erstellt.\n";
    } else {
        throw new Exception("Rate-Limiting Tabelle: " . $conn->error);
    }
    
    // 2. Prüfen ob files Tabelle bereits existiert
    $result = $conn->query("SHOW TABLES LIKE 'files'");
    if ($result->num_rows === 0) {
        echo "📄 Erstelle Haupttabelle 'files'...\n";
        $sql_files = "CREATE TABLE IF NOT EXISTS `files` (
            `id` varchar(16) NOT NULL,
            `secret_token` varchar(64) NOT NULL,
            `original_filename` varchar(255) NOT NULL,
            `password_hash` varchar(255) DEFAULT NULL,
            `is_onetime` tinyint(1) NOT NULL DEFAULT 0,
            `file_size` bigint(20) DEFAULT NULL,
            `mime_type` varchar(100) DEFAULT NULL,
            `upload_ip` varchar(45) DEFAULT NULL,
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            `delete_at` timestamp NOT NULL,
            PRIMARY KEY (`id`),
            KEY `idx_delete_at` (`delete_at`),
            KEY `idx_created_at` (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        
        if ($conn->query($sql_files)) {
            echo "✅ Haupttabelle 'files' erstellt.\n";
        } else {
            throw new Exception("Haupttabelle: " . $conn->error);
        }
    } else {
        echo "📄 Prüfe bestehende 'files' Tabelle...\n";
        
        // Neue Spalten hinzufügen falls sie nicht existieren
        $columns_to_add = [
            'file_size' => 'ADD COLUMN `file_size` bigint(20) DEFAULT NULL',
            'mime_type' => 'ADD COLUMN `mime_type` varchar(100) DEFAULT NULL',
            'upload_ip' => 'ADD COLUMN `upload_ip` varchar(45) DEFAULT NULL'
        ];
        
        // Prüfen welche Spalten bereits existieren
        $existing_columns = [];
        $result = $conn->query("DESCRIBE files");
        while ($row = $result->fetch_assoc()) {
            $existing_columns[] = $row['Field'];
        }
        
        foreach ($columns_to_add as $column => $sql) {
            if (!in_array($column, $existing_columns)) {
                if ($conn->query("ALTER TABLE files $sql")) {
                    echo "✅ Spalte '$column' hinzugefügt.\n";
                } else {
                    echo "⚠️  Spalte '$column' konnte nicht hinzugefügt werden: " . $conn->error . "\n";
                }
            } else {
                echo "ℹ️  Spalte '$column' existiert bereits.\n";
            }
        }
        
        // Index für created_at hinzufügen falls er nicht existiert
        $result = $conn->query("SHOW INDEX FROM files WHERE Key_name = 'idx_created_at'");
        if ($result->num_rows === 0) {
            if ($conn->query("ALTER TABLE files ADD KEY `idx_created_at` (`created_at`)")) {
                echo "✅ Index 'idx_created_at' hinzugefügt.\n";
            } else {
                echo "⚠️  Index 'idx_created_at' konnte nicht hinzugefügt werden: " . $conn->error . "\n";
            }
        }
    }
    
    // 3. Security Logs Tabelle (optional für Monitoring)
    echo "🔒 Erstelle Security-Logs Tabelle...\n";
    $sql_security_logs = "CREATE TABLE IF NOT EXISTS `security_logs` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `ip_address` varchar(45) NOT NULL,
        `event_type` varchar(50) NOT NULL,
        `details` text DEFAULT NULL,
        `user_agent` varchar(500) DEFAULT NULL,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        PRIMARY KEY (`id`),
        KEY `idx_ip_event` (`ip_address`, `event_type`),
        KEY `idx_created_at` (`created_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    
    if ($conn->query($sql_security_logs)) {
        echo "✅ Security-Logs Tabelle erstellt.\n";
    } else {
        throw new Exception("Security-Logs Tabelle: " . $conn->error);
    }
    
    // 4. Database Cleanup (alte Einträge entfernen)
    echo "🧹 Bereinige veraltete Einträge...\n";
    
    // Rate-Limits älter als 24 Stunden löschen
    $cleanup_time = date('Y-m-d H:i:s', time() - (24 * 3600));
    $result = $conn->query("DELETE FROM rate_limits WHERE created_at < '$cleanup_time'");
    if ($result) {
        echo "✅ Veraltete Rate-Limit-Einträge bereinigt.\n";
    }
    
    // Security-Logs älter als 30 Tage löschen
    $cleanup_logs = date('Y-m-d H:i:s', time() - (30 * 24 * 3600));
    $result = $conn->query("DELETE FROM security_logs WHERE created_at < '$cleanup_logs'");
    if ($result) {
        echo "✅ Veraltete Security-Logs bereinigt.\n";
    }
    
    $conn->close();
    
    echo "\n🎉 Database Update erfolgreich abgeschlossen!\n";
    echo "\nNächste Schritte:\n";
    echo "1. Testen Sie die Upload-Funktionalität\n";
    echo "2. Prüfen Sie die Rate-Limiting-Funktionen\n";
    echo "3. Überwachen Sie die Security-Logs\n";
    echo "4. Löschen Sie dieses Update-Script nach erfolgreichem Test\n\n";
    
} catch (Exception $e) {
    echo "\n❌ Fehler beim Database Update: " . $e->getMessage() . "\n";
    echo "Bitte prüfen Sie die Datenbankverbindung und -berechtigungen.\n";
    exit(1);
}
?>