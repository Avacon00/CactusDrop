<?php
/**
 * CactusDrop v0.4.0 - DSGVO Privacy Cleanup Script
 * 
 * Automatische Datenbereinigung für DSGVO-Compliance
 * Kann per Cronjob ausgeführt werden: 0 2 * * * /usr/bin/php privacy_cleanup.php
 */

require_once 'config.php';
require_once 'privacy.php';

// Nur CLI-Ausführung erlauben (Sicherheit)
if (php_sapi_name() !== 'cli' && !defined('ALLOW_WEB_CLEANUP')) {
    // Web-Zugriff nur mit Admin-Login
    session_start();
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        http_response_code(403);
        die('Access denied. This script should be run via CLI or Admin Panel.');
    }
}

echo "🛡️ CactusDrop DSGVO Privacy Cleanup - " . date('Y-m-d H:i:s') . "\n";
echo str_repeat("=", 60) . "\n";

try {
    // 1. Privacy-Modus prüfen
    $privacyEnabled = PrivacyManager::isPrivacyModeEnabled();
    echo "Privacy-Modus: " . ($privacyEnabled ? "✅ Aktiviert" : "❌ Deaktiviert") . "\n";
    
    if (!$privacyEnabled) {
        echo "⚠️  Privacy-Modus ist deaktiviert. Cleanup wird übersprungen.\n";
        exit(0);
    }
    
    // 2. Automatische Datenbereinigung durchführen
    echo "\n🧹 Starte automatische Datenbereinigung...\n";
    $cleanedItems = PrivacyManager::cleanupExpiredData();
    echo "✅ $cleanedItems Einträge bereinigt.\n";
    
    // 3. Spezielle DSGVO-Bereinigungen
    echo "\n🔒 DSGVO-spezifische Bereinigungen...\n";
    
    $conn = get_db_connection();
    
    // 3.1 Anonymisierung von alten IPs (falls noch nicht geschehen)
    $stmt = $conn->prepare("UPDATE files SET upload_ip = ? WHERE upload_ip IS NOT NULL AND upload_ip NOT LIKE '%.0' AND upload_ip != 'anonymized'");
    $anonymizedIP = 'anonymized';
    $stmt->bind_param('s', $anonymizedIP);
    $stmt->execute();
    $anonymizedFiles = $stmt->affected_rows;
    $stmt->close();
    
    if ($anonymizedFiles > 0) {
        echo "✅ $anonymizedFiles Datei-IPs anonymisiert.\n";
    }
    
    // 3.2 User-Agent Bereinigung
    $stmt = $conn->prepare("UPDATE files SET user_agent = ? WHERE user_agent IS NOT NULL AND LENGTH(user_agent) > 20");
    $genericUA = 'Browser';
    $stmt->bind_param('s', $genericUA);
    $stmt->execute();
    $cleanedUAs = $stmt->affected_rows;
    $stmt->close();
    
    if ($cleanedUAs > 0) {
        echo "✅ $cleanedUAs User-Agents bereinigt.\n";
    }
    
    // 3.3 Verwaiste Session-Daten löschen
    $result = $conn->query("SHOW TABLES LIKE 'admin_sessions'");
    if ($result->num_rows > 0) {
        $stmt = $conn->prepare("DELETE FROM admin_sessions WHERE expires_at < NOW() OR created_at < DATE_SUB(NOW(), INTERVAL 7 DAY)");
        $stmt->execute();
        $cleanedSessions = $stmt->affected_rows;
        $stmt->close();
        
        if ($cleanedSessions > 0) {
            echo "✅ $cleanedSessions verwaiste Sessions gelöscht.\n";
        }
    }
    
    // 3.4 Upload-Stats bereinigen (aggregierte Daten nach 1 Jahr löschen)
    $result = $conn->query("SHOW TABLES LIKE 'upload_stats'");
    if ($result->num_rows > 0) {
        $stmt = $conn->prepare("DELETE FROM upload_stats WHERE date < DATE_SUB(CURDATE(), INTERVAL 365 DAY)");
        $stmt->execute();
        $cleanedStats = $stmt->affected_rows;
        $stmt->close();
        
        if ($cleanedStats > 0) {
            echo "✅ $cleanedStats alte Statistik-Einträge gelöscht.\n";
        }
    }
    
    // 4. Cleanup-Log erstellen
    $totalCleaned = $cleanedItems + ($anonymizedFiles ?? 0) + ($cleanedUAs ?? 0) + ($cleanedSessions ?? 0) + ($cleanedStats ?? 0);
    
    if (class_exists('GDPRAdminSecurity')) {
        GDPRAdminSecurity::logSecurityEvent(
            'privacy_cleanup_automatic',
            "Automatic GDPR cleanup completed: $totalCleaned items processed",
            'low'
        );
    }
    
    // 5. Statistiken anzeigen
    echo "\n📊 Cleanup-Statistiken:\n";
    echo "- Abgelaufene Dateien/Logs: $cleanedItems\n";
    echo "- Anonymisierte IPs: " . ($anonymizedFiles ?? 0) . "\n";
    echo "- Bereinigte User-Agents: " . ($cleanedUAs ?? 0) . "\n";
    echo "- Gelöschte Sessions: " . ($cleanedSessions ?? 0) . "\n";
    echo "- Alte Statistiken: " . ($cleanedStats ?? 0) . "\n";
    echo "- Gesamt bearbeitet: $totalCleaned\n";
    
    // 6. Speicherplatz-Info
    $uploadDir = defined('UPLOAD_DIR') ? UPLOAD_DIR : './uploads/';
    if (is_dir($uploadDir)) {
        $files = glob($uploadDir . '*');
        $totalSize = 0;
        $fileCount = 0;
        
        foreach ($files as $file) {
            if (is_file($file)) {
                $totalSize += filesize($file);
                $fileCount++;
            }
        }
        
        echo "\n💾 Upload-Verzeichnis:\n";
        echo "- Dateien: $fileCount\n";
        echo "- Größe: " . formatBytes($totalSize) . "\n";
    }
    
    $conn->close();
    
    echo "\n✅ DSGVO Privacy Cleanup erfolgreich abgeschlossen!\n";
    echo "Nächster Lauf empfohlen: " . date('Y-m-d H:i', strtotime('+1 day')) . "\n";
    
} catch (Exception $e) {
    echo "\n❌ Fehler beim Privacy Cleanup:\n";
    echo $e->getMessage() . "\n";
    
    if (class_exists('GDPRAdminSecurity')) {
        GDPRAdminSecurity::logSecurityEvent(
            'privacy_cleanup_failed',
            "Privacy cleanup failed: " . $e->getMessage(),
            'high'
        );
    }
    
    exit(1);
}

// Hilfsfunktion für Dateigröße
function formatBytes($size, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB'];
    for ($i = 0; $size >= 1024 && $i < count($units) - 1; $i++) {
        $size /= 1024;
    }
    return round($size, $precision) . ' ' . $units[$i];
}

echo str_repeat("=", 60) . "\n";
echo "🌵 CactusDrop Privacy Cleanup beendet.\n";
?>