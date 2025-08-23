-- CactusDrop v0.4.0 - Erweiterte Admin-Schema
-- Für Statistiken, Security-Logs und erweiterte Features

-- Erweiterte files Tabelle mit mehr Metadaten
ALTER TABLE files 
ADD COLUMN file_size BIGINT DEFAULT NULL AFTER original_filename,
ADD COLUMN mime_type VARCHAR(100) DEFAULT NULL AFTER file_size,
ADD COLUMN upload_ip VARCHAR(45) DEFAULT NULL AFTER mime_type,
ADD COLUMN downloads_count INT DEFAULT 0 AFTER upload_ip,
ADD COLUMN last_download_at TIMESTAMP NULL AFTER downloads_count,
ADD COLUMN user_agent VARCHAR(500) DEFAULT NULL AFTER last_download_at,
ADD COLUMN expiry_hours INT DEFAULT 24 AFTER user_agent,
ADD COLUMN language VARCHAR(5) DEFAULT 'de' AFTER expiry_hours,
ADD INDEX idx_upload_ip (upload_ip),
ADD INDEX idx_created_at (created_at),
ADD INDEX idx_downloads (downloads_count),
ADD INDEX idx_language (language);

-- Admin-Benutzer Tabelle
CREATE TABLE IF NOT EXISTS admin_users (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Admin-Sessions Tabelle
CREATE TABLE IF NOT EXISTS admin_sessions (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Security-Logs Tabelle (erweitert)
CREATE TABLE IF NOT EXISTS security_logs (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Upload-Statistiken Tabelle (aggregierte Daten)
CREATE TABLE IF NOT EXISTS upload_stats (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- System-Einstellungen Tabelle
CREATE TABLE IF NOT EXISTS system_settings (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Standard-Einstellungen
INSERT INTO system_settings (setting_key, setting_value, setting_type, description, category, is_public) VALUES
('default_expiry_hours', '24', 'integer', 'Standard Ablaufzeit in Stunden', 'uploads', 1),
('max_expiry_hours', '168', 'integer', 'Maximale Ablaufzeit in Stunden (7 Tage)', 'uploads', 1),
('available_expiry_options', '[1,6,12,24,48,72,168]', 'json', 'Verfügbare Ablaufzeit-Optionen in Stunden', 'uploads', 1),
('default_language', 'de', 'string', 'Standard-Sprache', 'localization', 1),
('available_languages', '["de","en"]', 'json', 'Verfügbare Sprachen', 'localization', 1),
('admin_session_timeout', '1800', 'integer', 'Admin-Session Timeout in Sekunden (30 Min)', 'admin', 0),
('max_failed_login_attempts', '5', 'integer', 'Maximale fehlgeschlagene Login-Versuche', 'admin', 0),
('lockout_duration_minutes', '15', 'integer', 'Sperrzeit nach fehlgeschlagenen Logins (Minuten)', 'admin', 0),
('enable_statistics', '1', 'boolean', 'Statistiken erfassen aktivieren', 'admin', 0),
('log_retention_days', '30', 'integer', 'Log-Aufbewahrungszeit in Tagen', 'admin', 0)
ON DUPLICATE KEY UPDATE updated_at = CURRENT_TIMESTAMP;

-- Standard Admin-User erstellen (Passwort: admin123 - MUSS nach Installation geändert werden!)
INSERT INTO admin_users (username, password_hash, email) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@example.com')
ON DUPLICATE KEY UPDATE updated_at = CURRENT_TIMESTAMP;

-- Cleanup-Jobs für automatische Wartung
DELIMITER //
CREATE EVENT IF NOT EXISTS cleanup_expired_sessions
ON SCHEDULE EVERY 1 HOUR
DO
BEGIN
    DELETE FROM admin_sessions WHERE expires_at < NOW();
    DELETE FROM security_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY);
END //
DELIMITER ;