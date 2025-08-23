<?php
/**
 * CactusDrop v0.4.0 - DSGVO Privacy Functions
 * 
 * Privacy-by-Design Implementation
 * IP-Anonymisierung und Datenschutz-Tools
 */

class PrivacyManager {
    
    /**
     * IP-Adresse DSGVO-konform anonymisieren
     * Entfernt letztes Oktett (IPv4) oder letzte 64 Bits (IPv6)
     */
    public static function anonymizeIP($ip) {
        if (empty($ip) || $ip === 'unknown') {
            return 'unknown';
        }
        
        // IPv4 Anonymisierung (letztes Oktett = 0)
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $parts = explode('.', $ip);
            if (count($parts) === 4) {
                $parts[3] = '0';
                return implode('.', $parts);
            }
        }
        
        // IPv6 Anonymisierung (letzte 64 Bits = 0)
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $parts = explode(':', $ip);
            if (count($parts) >= 4) {
                // Setze die letzten 4 Gruppen auf 0
                for ($i = max(0, count($parts) - 4); $i < count($parts); $i++) {
                    $parts[$i] = '0';
                }
                return implode(':', $parts);
            }
        }
        
        return 'anonymized';
    }
    
    /**
     * User-Agent anonymisieren - nur Browser-Familie behalten
     */
    public static function anonymizeUserAgent($userAgent) {
        if (empty($userAgent)) {
            return null;
        }
        
        // Nur Browser-Familie extrahieren
        if (strpos($userAgent, 'Chrome') !== false) return 'Chrome';
        if (strpos($userAgent, 'Firefox') !== false) return 'Firefox';
        if (strpos($userAgent, 'Safari') !== false) return 'Safari';
        if (strpos($userAgent, 'Edge') !== false) return 'Edge';
        if (strpos($userAgent, 'Opera') !== false) return 'Opera';
        
        return 'Other';
    }
    
    /**
     * Sichere IP-Adresse abrufen und anonymisieren
     */
    public static function getAnonymizedClientIP() {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        
        // Proxy-Headers prüfen (falls vorhanden)
        $headers = [
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'HTTP_CLIENT_IP'
        ];
        
        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $proxyIP = trim(explode(',', $_SERVER[$header])[0]);
                if (filter_var($proxyIP, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    $ip = $proxyIP;
                    break;
                }
            }
        }
        
        return self::anonymizeIP($ip);
    }
    
    /**
     * Prüfen ob Privacy-Modus aktiviert ist
     */
    public static function isPrivacyModeEnabled() {
        $conn = get_db_connection();
        $result = $conn->query("SELECT setting_value FROM system_settings WHERE setting_key = 'privacy_mode_enabled'");
        
        if ($result && $row = $result->fetch_assoc()) {
            $enabled = $row['setting_value'] === '1';
            $conn->close();
            return $enabled;
        }
        
        $conn->close();
        return true; // Privacy-by-Default
    }
    
    /**
     * Automatische Datenbereinigung durchführen
     */
    public static function cleanupExpiredData() {
        $conn = get_db_connection();
        $cleaned = 0;
        
        try {
            // 1. Abgelaufene Dateien löschen
            $stmt = $conn->prepare("SELECT id FROM files WHERE delete_at < NOW()");
            $stmt->execute();
            $expiredFiles = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            
            foreach ($expiredFiles as $file) {
                $uploadDir = defined('UPLOAD_DIR') ? UPLOAD_DIR : './uploads/';
                $filePath = $uploadDir . $file['id'];
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }
            
            $stmt = $conn->prepare("DELETE FROM files WHERE delete_at < NOW()");
            $stmt->execute();
            $cleaned += $stmt->affected_rows;
            $stmt->close();
            
            // 2. Alte Security-Logs löschen (DSGVO-Aufbewahrungsfristen)
            $retentionDays = self::getLogRetentionDays();
            $stmt = $conn->prepare("DELETE FROM security_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)");
            $stmt->bind_param('i', $retentionDays);
            $stmt->execute();
            $cleaned += $stmt->affected_rows;
            $stmt->close();
            
            // 3. Abgelaufene Admin-Sessions löschen
            $stmt = $conn->prepare("DELETE FROM admin_sessions WHERE expires_at < NOW() OR is_active = 0");
            $stmt->execute();
            $cleaned += $stmt->affected_rows;
            $stmt->close();
            
            // 4. Alte Upload-Stats bereinigen (nach 1 Jahr)
            $stmt = $conn->prepare("DELETE FROM upload_stats WHERE date < DATE_SUB(CURDATE(), INTERVAL 365 DAY)");
            $stmt->execute();
            $cleaned += $stmt->affected_rows;
            $stmt->close();
            
        } catch (Exception $e) {
            error_log("Privacy cleanup error: " . $e->getMessage());
        }
        
        $conn->close();
        return $cleaned;
    }
    
    /**
     * Log-Aufbewahrungszeit abrufen
     */
    public static function getLogRetentionDays() {
        $conn = get_db_connection();
        $result = $conn->query("SELECT setting_value FROM system_settings WHERE setting_key = 'log_retention_days'");
        
        if ($result && $row = $result->fetch_assoc()) {
            $days = (int)$row['setting_value'];
            $conn->close();
            return max(1, min($days, 90)); // Min 1 Tag, Max 90 Tage
        }
        
        $conn->close();
        return 30; // Default: 30 Tage
    }
    
    /**
     * DSGVO-konformes Security-Event-Logging
     */
    public static function logSecurityEvent($eventType, $details = '', $severity = 'medium', $fileId = null, $adminUserId = null) {
        // Prüfen ob Logging aktiviert ist
        if (!self::isPrivacyModeEnabled()) {
            return; // Kein Logging im Privacy-Modus
        }
        
        try {
            $conn = get_db_connection();
            
            // Prüfen ob security_logs Tabelle existiert
            $result = $conn->query("SHOW TABLES LIKE 'security_logs'");
            if ($result->num_rows === 0) {
                $conn->close();
                return;
            }
            
            // DSGVO-konforme Daten sammeln
            $anonymizedIP = self::getAnonymizedClientIP();
            $anonymizedUA = self::anonymizeUserAgent($_SERVER['HTTP_USER_AGENT'] ?? '');
            
            $stmt = $conn->prepare("INSERT INTO security_logs (event_type, ip_address, user_agent, details, file_id, admin_user_id, severity) VALUES (?, ?, ?, ?, ?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param('sssssis', $eventType, $anonymizedIP, $anonymizedUA, $details, $fileId, $adminUserId, $severity);
                $stmt->execute();
                $stmt->close();
            }
            
            $conn->close();
        } catch (Exception $e) {
            error_log("GDPR-compliant security logging failed: " . $e->getMessage());
        }
    }
    
    /**
     * Datenschutz-Dashboard Daten
     */
    public static function getPrivacyStats() {
        $conn = get_db_connection();
        $stats = [
            'privacy_mode' => self::isPrivacyModeEnabled(),
            'log_retention_days' => self::getLogRetentionDays(),
            'total_logs' => 0,
            'anonymized_ips' => 0,
            'oldest_log' => null,
            'cleanup_last_run' => null
        ];
        
        try {
            // Log-Statistiken
            $result = $conn->query("SELECT COUNT(*) as total FROM security_logs");
            if ($result) {
                $stats['total_logs'] = $result->fetch_assoc()['total'];
            }
            
            // Anonymisierte IPs zählen
            $result = $conn->query("SELECT COUNT(*) as anonymized FROM security_logs WHERE ip_address LIKE '%.0' OR ip_address = 'anonymized'");
            if ($result) {
                $stats['anonymized_ips'] = $result->fetch_assoc()['anonymized'];
            }
            
            // Ältester Log
            $result = $conn->query("SELECT MIN(created_at) as oldest FROM security_logs");
            if ($result && $row = $result->fetch_assoc()) {
                $stats['oldest_log'] = $row['oldest'];
            }
            
        } catch (Exception $e) {
            error_log("Privacy stats error: " . $e->getMessage());
        }
        
        $conn->close();
        return $stats;
    }
}

// DSGVO-konforme AdminSecurity-Klasse (nur wenn AdminSecurity verfügbar ist)
if (class_exists('AdminSecurity')) {
    class GDPRAdminSecurity extends AdminSecurity {
        
        public static function logSecurityEvent($eventType, $details = '', $severity = 'medium', $fileId = null) {
            PrivacyManager::logSecurityEvent($eventType, $details, $severity, $fileId, $_SESSION['admin_user_id'] ?? null);
        }
    }
} else {
    // Fallback wenn AdminSecurity nicht verfügbar ist
    class GDPRAdminSecurity {
        
        public static function logSecurityEvent($eventType, $details = '', $severity = 'medium', $fileId = null) {
            PrivacyManager::logSecurityEvent($eventType, $details, $severity, $fileId, null);
        }
    }
}
?>