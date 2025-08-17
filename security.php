<?php
/**
 * CactusDrop Security Module
 * Zentrale Sicherheitsfunktionen für Input-Validierung und Schutz
 */

class CactusDropSecurity {
    
    // Konfiguration
    const MAX_FILE_SIZE = 100 * 1024 * 1024; // 100MB
    const MAX_FILENAME_LENGTH = 255;
    const RATE_LIMIT_WINDOW = 3600; // 1 Stunde
    const MAX_UPLOADS_PER_IP = 10;
    const MIN_PASSWORD_LENGTH = 8;
    
    // Erlaubte MIME-Types (Whitelist-Ansatz)
    const ALLOWED_MIME_TYPES = [
        // Dokumente
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'text/plain',
        'text/csv',
        'application/rtf',
        
        // Archive
        'application/zip',
        'application/x-rar-compressed',
        'application/x-7z-compressed',
        'application/gzip',
        'application/x-tar',
        
        // Bilder
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
        'image/svg+xml',
        'image/bmp',
        'image/tiff',
        
        // Audio
        'audio/mpeg',
        'audio/wav',
        'audio/ogg',
        'audio/flac',
        'audio/aac',
        
        // Video
        'video/mp4',
        'video/avi',
        'video/quicktime',
        'video/x-msvideo',
        'video/webm',
        'video/ogg'
    ];
    
    // Gefährliche Dateiendungen (Blacklist)
    const DANGEROUS_EXTENSIONS = [
        'php', 'php3', 'php4', 'php5', 'phtml', 'asp', 'aspx', 'jsp', 'js', 'vbs', 'py', 'pl', 'rb',
        'exe', 'bat', 'cmd', 'com', 'scr', 'msi', 'dll', 'sh', 'bash', 'ps1', 'jar', 'war'
    ];
    
    /**
     * Validiert eine hochgeladene Datei komplett
     */
    public static function validateUploadedFile($file) {
        $errors = [];
        
        // 1. Basis-Upload-Validierung
        if (!isset($file) || !is_array($file)) {
            $errors[] = "Keine gültige Datei empfangen.";
            return ['valid' => false, 'errors' => $errors];
        }
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = self::getUploadErrorMessage($file['error']);
            return ['valid' => false, 'errors' => $errors];
        }
        
        // 2. Dateigröße prüfen
        if ($file['size'] > self::MAX_FILE_SIZE) {
            $errors[] = "Datei zu groß. Maximum: " . self::formatBytes(self::MAX_FILE_SIZE);
        }
        
        if ($file['size'] <= 0) {
            $errors[] = "Datei ist leer.";
        }
        
        // 3. Dateiname validieren
        $filename_result = self::validateFilename($file['name']);
        if (!$filename_result['valid']) {
            $errors = array_merge($errors, $filename_result['errors']);
        }
        
        // 4. MIME-Type validieren
        $mime_result = self::validateMimeType($file['tmp_name'], $file['type']);
        if (!$mime_result['valid']) {
            $errors = array_merge($errors, $mime_result['errors']);
        }
        
        // 5. Dateiinhalt scannen
        $content_result = self::scanFileContent($file['tmp_name']);
        if (!$content_result['valid']) {
            $errors = array_merge($errors, $content_result['errors']);
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'sanitized_name' => $filename_result['sanitized_name'] ?? $file['name']
        ];
    }
    
    /**
     * Validiert und bereinigt Dateinamen
     */
    public static function validateFilename($filename) {
        $errors = [];
        
        if (empty($filename)) {
            $errors[] = "Dateiname darf nicht leer sein.";
            return ['valid' => false, 'errors' => $errors];
        }
        
        if (strlen($filename) > self::MAX_FILENAME_LENGTH) {
            $errors[] = "Dateiname zu lang. Maximum: " . self::MAX_FILENAME_LENGTH . " Zeichen.";
        }
        
        // Gefährliche Zeichen entfernen
        $sanitized = preg_replace('/[^\w\s\-\.\(\)]/u', '', $filename);
        $sanitized = preg_replace('/\.{2,}/', '.', $sanitized); // Mehrere Punkte entfernen
        $sanitized = trim($sanitized, '. '); // Punkte/Leerzeichen am Anfang/Ende
        
        if (empty($sanitized)) {
            $errors[] = "Dateiname enthält nur ungültige Zeichen.";
            return ['valid' => false, 'errors' => $errors];
        }
        
        // Gefährliche Erweiterungen prüfen
        $extension = strtolower(pathinfo($sanitized, PATHINFO_EXTENSION));
        if (in_array($extension, self::DANGEROUS_EXTENSIONS)) {
            $errors[] = "Dateityp '.$extension' ist nicht erlaubt.";
        }
        
        // Reservierte Namen prüfen (Windows)
        $basename = pathinfo($sanitized, PATHINFO_FILENAME);
        $reserved_names = ['CON', 'PRN', 'AUX', 'NUL', 'COM1', 'COM2', 'COM3', 'COM4', 'COM5', 'COM6', 'COM7', 'COM8', 'COM9', 'LPT1', 'LPT2', 'LPT3', 'LPT4', 'LPT5', 'LPT6', 'LPT7', 'LPT8', 'LPT9'];
        if (in_array(strtoupper($basename), $reserved_names)) {
            $errors[] = "Dateiname ist ein reservierter Systemname.";
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'sanitized_name' => $sanitized
        ];
    }
    
    /**
     * Validiert MIME-Type mit mehreren Methoden
     */
    public static function validateMimeType($file_path, $declared_type) {
        $errors = [];
        
        // 1. Declared MIME-Type prüfen
        if (!in_array($declared_type, self::ALLOWED_MIME_TYPES)) {
            $errors[] = "MIME-Type '$declared_type' ist nicht erlaubt.";
        }
        
        // 2. Echten MIME-Type ermitteln (falls verfügbar)
        if (function_exists('finfo_file')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $real_type = finfo_file($finfo, $file_path);
            finfo_close($finfo);
            
            if ($real_type && !in_array($real_type, self::ALLOWED_MIME_TYPES)) {
                $errors[] = "Echter Dateityp '$real_type' ist nicht erlaubt.";
            }
            
            // MIME-Type-Spoofing prüfen
            if ($real_type && $declared_type !== $real_type) {
                // Toleranz für gängige Varianten
                $type_mappings = [
                    'application/x-zip-compressed' => 'application/zip',
                    'image/pjpeg' => 'image/jpeg'
                ];
                
                if (!isset($type_mappings[$declared_type]) || $type_mappings[$declared_type] !== $real_type) {
                    $errors[] = "MIME-Type-Spoofing erkannt: '$declared_type' vs '$real_type'.";
                }
            }
        }
        
        return ['valid' => empty($errors), 'errors' => $errors];
    }
    
    /**
     * Scannt Dateiinhalt nach verdächtigen Inhalten
     */
    public static function scanFileContent($file_path) {
        $errors = [];
        
        // Erste paar Bytes lesen um Dateiformat zu prüfen
        $handle = fopen($file_path, 'rb');
        if (!$handle) {
            $errors[] = "Datei konnte nicht gelesen werden.";
            return ['valid' => false, 'errors' => $errors];
        }
        
        $header = fread($handle, 1024);
        fclose($handle);
        
        // Nach eingebetteten Scripts suchen
        $dangerous_patterns = [
            '/<\?php/i',
            '/<script/i',
            '/<%/i',
            '/javascript:/i',
            '/vbscript:/i',
            '/on\w+\s*=/i', // Event-Handler
            '/eval\s*\(/i',
            '/exec\s*\(/i',
            '/system\s*\(/i'
        ];
        
        foreach ($dangerous_patterns as $pattern) {
            if (preg_match($pattern, $header)) {
                $errors[] = "Verdächtiger Code in Datei entdeckt.";
                break;
            }
        }
        
        return ['valid' => empty($errors), 'errors' => $errors];
    }
    
    /**
     * Rate Limiting für IP-Adressen
     */
    public static function checkRateLimit($ip_address) {
        $conn = get_db_connection();
        
        // Cleanup alter Einträge
        $cleanup_time = date('Y-m-d H:i:s', time() - self::RATE_LIMIT_WINDOW);
        $conn->query("DELETE FROM rate_limits WHERE created_at < '$cleanup_time'");
        
        // Aktuelle Uploads zählen
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM rate_limits WHERE ip_address = ? AND created_at >= ?");
        $stmt->bind_param('ss', $ip_address, $cleanup_time);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $current_count = $row['count'];
        
        $stmt->close();
        $conn->close();
        
        if ($current_count >= self::MAX_UPLOADS_PER_IP) {
            return [
                'allowed' => false,
                'message' => "Rate-Limit erreicht. Versuchen Sie es in einer Stunde erneut.",
                'remaining' => 0
            ];
        }
        
        return [
            'allowed' => true,
            'remaining' => self::MAX_UPLOADS_PER_IP - $current_count
        ];
    }
    
    /**
     * Rate-Limit-Eintrag hinzufügen
     */
    public static function recordUpload($ip_address) {
        $conn = get_db_connection();
        $stmt = $conn->prepare("INSERT INTO rate_limits (ip_address, created_at) VALUES (?, NOW())");
        $stmt->bind_param('s', $ip_address);
        $stmt->execute();
        $stmt->close();
        $conn->close();
    }
    
    /**
     * Parameter validieren und säubern
     */
    public static function validateParameter($value, $type, $max_length = null, $required = false) {
        if ($required && (empty($value) || trim($value) === '')) {
            return ['valid' => false, 'error' => 'Parameter ist erforderlich.'];
        }
        
        if (!$required && empty($value)) {
            return ['valid' => true, 'value' => null];
        }
        
        switch ($type) {
            case 'string':
                $clean_value = trim(strip_tags($value));
                if ($max_length && strlen($clean_value) > $max_length) {
                    return ['valid' => false, 'error' => "Text zu lang. Maximum: $max_length Zeichen."];
                }
                return ['valid' => true, 'value' => $clean_value];
                
            case 'email':
                $clean_value = filter_var(trim($value), FILTER_VALIDATE_EMAIL);
                if ($clean_value === false) {
                    return ['valid' => false, 'error' => 'Ungültige E-Mail-Adresse.'];
                }
                return ['valid' => true, 'value' => $clean_value];
                
            case 'boolean':
                return ['valid' => true, 'value' => ($value === 'true' || $value === '1' || $value === 1 || $value === true)];
                
            case 'password':
                if (strlen($value) < self::MIN_PASSWORD_LENGTH) {
                    return ['valid' => false, 'error' => "Passwort zu kurz. Minimum: " . self::MIN_PASSWORD_LENGTH . " Zeichen."];
                }
                return ['valid' => true, 'value' => $value]; // Passwort nicht trimmen
                
            case 'file_id':
                if (!preg_match('/^[a-f0-9]{16}$/', $value)) {
                    return ['valid' => false, 'error' => 'Ungültige Datei-ID.'];
                }
                return ['valid' => true, 'value' => $value];
                
            case 'token':
                if (!preg_match('/^[a-f0-9]{64}$/', $value)) {
                    return ['valid' => false, 'error' => 'Ungültiger Token.'];
                }
                return ['valid' => true, 'value' => $value];
                
            default:
                return ['valid' => false, 'error' => 'Unbekannter Parametertyp.'];
        }
    }
    
    /**
     * CSRF-Token generieren
     */
    public static function generateCSRFToken() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $token = bin2hex(random_bytes(32));
        $_SESSION['csrf_token'] = $token;
        return $token;
    }
    
    /**
     * CSRF-Token validieren
     */
    public static function validateCSRFToken($token) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Sichere Fehlerbehandlung
     */
    public static function handleSecurityError($error_type, $details = null) {
        // Log für Admins (aber nicht an Benutzer weiterleiten)
        error_log("[CactusDrop Security] $error_type: " . json_encode($details));
        
        // Generische Fehlermeldung für Benutzer
        switch ($error_type) {
            case 'upload_validation':
                return "Datei konnte nicht verarbeitet werden. Bitte prüfen Sie Format und Größe.";
            case 'rate_limit':
                return "Zu viele Anfragen. Versuchen Sie es später erneut.";
            case 'invalid_request':
                return "Ungültige Anfrage.";
            default:
                return "Ein Fehler ist aufgetreten. Versuchen Sie es erneut.";
        }
    }
    
    /**
     * IP-Adresse sicher ermitteln
     */
    public static function getClientIP() {
        // Berücksichtigt Proxy-Header, aber validiert sie
        $ip_headers = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'];
        
        foreach ($ip_headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = trim(explode(',', $_SERVER[$header])[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
    
    // Hilfsfunktionen
    private static function getUploadErrorMessage($error_code) {
        switch ($error_code) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                return "Datei zu groß.";
            case UPLOAD_ERR_PARTIAL:
                return "Upload unvollständig.";
            case UPLOAD_ERR_NO_FILE:
                return "Keine Datei ausgewählt.";
            case UPLOAD_ERR_NO_TMP_DIR:
                return "Server-Konfigurationsfehler.";
            case UPLOAD_ERR_CANT_WRITE:
                return "Datei konnte nicht gespeichert werden.";
            case UPLOAD_ERR_EXTENSION:
                return "Upload durch Erweiterung blockiert.";
            default:
                return "Unbekannter Upload-Fehler.";
        }
    }
    
    private static function formatBytes($bytes, $precision = 2) {
        $units = ['B', 'KB', 'MB', 'GB'];
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        return round($bytes, $precision) . ' ' . $units[$i];
    }
}
?>