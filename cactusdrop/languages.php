<?php
/**
 * CactusDrop v0.4.0 - Multi-Language Support
 * 
 * Unterstützte Sprachen: Deutsch (DE), English (EN)
 * Automatische Browser-Erkennung + Manual Override
 */

class LanguageManager {
    private static $currentLang = 'de';
    private static $translations = [];
    private static $availableLanguages = [
        'de' => ['name' => 'Deutsch', 'flag' => '🇩🇪'],
        'en' => ['name' => 'English', 'flag' => '🇺🇸']
    ];

    /**
     * Initialisiert das Sprachsystem
     */
    public static function init() {
        // 1. URL-Parameter prüfen
        if (isset($_GET['lang']) && array_key_exists($_GET['lang'], self::$availableLanguages)) {
            self::$currentLang = $_GET['lang'];
            setcookie('cactusdrop_lang', self::$currentLang, time() + (365 * 24 * 60 * 60), '/');
        }
        // 2. Cookie prüfen
        elseif (isset($_COOKIE['cactusdrop_lang']) && array_key_exists($_COOKIE['cactusdrop_lang'], self::$availableLanguages)) {
            self::$currentLang = $_COOKIE['cactusdrop_lang'];
        }
        // 3. Browser-Sprache erkennen
        else {
            $browserLang = self::detectBrowserLanguage();
            if (array_key_exists($browserLang, self::$availableLanguages)) {
                self::$currentLang = $browserLang;
            }
        }
        
        self::loadTranslations();
    }

    /**
     * Browser-Sprache erkennen
     */
    private static function detectBrowserLanguage() {
        if (!isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            return 'de';
        }
        
        $langs = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
        foreach ($langs as $lang) {
            $lang = strtolower(substr(trim($lang), 0, 2));
            if (array_key_exists($lang, self::$availableLanguages)) {
                return $lang;
            }
        }
        
        return 'de'; // Fallback
    }

    /**
     * Übersetzungen laden
     */
    private static function loadTranslations() {
        self::$translations = [
            'de' => [
                // Allgemein
                'app_name' => 'CactusDrop',
                'app_subtitle' => 'Dateien mit End-to-End-Verschlüsselung teilen.',
                'upload' => 'Hochladen',
                'download' => 'Herunterladen',
                'delete' => 'Löschen',
                'cancel' => 'Abbrechen',
                'continue' => 'Weiter',
                'back' => 'Zurück',
                'close' => 'Schließen',
                'copy' => 'Kopieren',
                'copied' => 'Kopiert!',
                'error' => 'Fehler',
                'success' => 'Erfolgreich',
                'loading' => 'Laden...',

                // Upload
                'upload_files' => 'Dateien hochladen',
                'upload_drag_drop' => 'oder per Drag & Drop',
                'upload_multiple_info' => 'Mehrere Dateien werden parallel im Browser verschlüsselt',
                'upload_multiple_tip' => '💡 Mehrere Dateien gleichzeitig auswählen für Bulk-Upload',
                'password_protect' => 'Zusätzlicher Passwortschutz',
                'password_protect_info' => 'Verschlüsselt den E2EE-Schlüssel zusätzlich.',
                'password_placeholder' => 'Passwort eingeben...',
                'one_time_download' => 'Einmal-Download',
                'one_time_download_info' => 'Der Link wird nach dem ersten Download ungültig.',
                'expiry_time' => 'Ablaufzeit',
                'expiry_1h' => '1 Stunde',
                'expiry_6h' => '6 Stunden',
                'expiry_12h' => '12 Stunden',
                'expiry_24h' => '24 Stunden (Standard)',
                'expiry_48h' => '2 Tage',
                'expiry_72h' => '3 Tage',
                'expiry_168h' => '1 Woche',

                // Progress
                'file_preparing' => 'Datei wird vorbereitet...',
                'encrypting_browser' => 'Verschlüssele Datei im Browser...',
                'uploading_file' => 'Lade Datei hoch...',
                'upload_complete' => 'Upload abgeschlossen!',
                'processing_multiple' => 'Mehrere Dateien werden verarbeitet...',
                'encrypting_files' => 'Verschlüssele {{current}} von {{total}} Dateien',
                'overall_progress' => 'Gesamtfortschritt',

                // Success
                'upload_successful' => 'Upload erfolgreich!',
                'secure_link_valid' => 'Ihr sicherer E2E-verschlüsselter Link ist {{hours}}h gültig:',
                'share_qr_code' => 'Oder teilen Sie via QR-Code:',
                'delete_link_immediately' => 'Link sofort löschen',
                'share_another_file' => 'Weitere Datei teilen',
                'bulk_upload_successful' => 'Bulk-Upload erfolgreich!',
                'files_uploaded_count' => '{{count}} Dateien erfolgreich hochgeladen:',
                'copy_all_links' => 'Alle Links kopieren',
                'download_links_json' => 'Links als JSON herunterladen',
                'expires_in' => 'Läuft ab in {{time}}',

                // Download
                'download_ready' => 'Download bereit',
                'decrypt_download' => 'Datei entschlüsseln & herunterladen',
                'password_required' => 'Passwort erforderlich',
                'enter_password' => 'Passwort eingeben',
                'decrypt_info' => 'Die Entschlüsselung findet in Ihrem Browser statt.',
                'downloading' => 'Wird heruntergeladen...',
                'decrypting' => 'Entschlüssele Datei...',
                'preparing_key' => 'Schlüssel wird vorbereitet...',
                'loading_encrypted' => 'Lade verschlüsselte Daten...',
                'starting_download' => 'Download wird gestartet...',

                // Errors
                'file_not_found' => 'Datei nicht gefunden',
                'file_expired' => 'Diese Datei ist abgelaufen oder wurde bereits heruntergeladen.',
                'file_deleted_onetime' => 'Diese Datei wurde nach dem ersten Download automatisch gelöscht.',
                'invalid_password' => 'Falsches Passwort oder beschädigter Link.',
                'decryption_failed' => 'Entschlüsselung fehlgeschlagen',
                'upload_failed' => 'Upload fehlgeschlagen',
                'file_too_large' => 'Datei zu groß. Maximum: {{max}}',
                'file_type_not_allowed' => 'Dateityp nicht erlaubt',
                'network_error' => 'Netzwerkfehler. Bitte versuchen Sie es erneut.',
                'something_went_wrong' => 'Etwas ist schiefgelaufen. Bitte versuchen Sie es erneut.',
                'try_again' => 'Erneut versuchen',

                // Admin
                'admin_panel' => 'Admin Panel',
                'login' => 'Anmelden',
                'logout' => 'Abmelden',
                'username' => 'Benutzername',
                'password' => 'Passwort',
                'dashboard' => 'Dashboard',
                'statistics' => 'Statistiken',
                'security_logs' => 'Security Logs',
                'file_management' => 'Datei-Management',
                'settings' => 'Einstellungen',
                
                // DSGVO Privacy
                'privacy_policy' => 'Datenschutzerklärung',
                'privacy_policy_subtitle' => 'Transparenz über unsere Datenverarbeitung',
                'data_controller' => 'Verantwortlicher',
                'responsible_entity' => 'Verantwortliche Stelle',
                'data_processing_overview' => 'Überblick Datenverarbeitung',
                'data_processing_description' => 'CactusDrop verarbeitet Ihre Daten ausschließlich zum Zweck der temporären Dateispeicherung und -freigabe. Wir setzen Privacy-by-Design um.',
                'processed_data_types' => 'Verarbeitete Datenarten',
                'uploaded_files' => 'Hochgeladene Dateien (temporär verschlüsselt)',
                'file_metadata' => 'Datei-Metadaten (Name, Größe, Typ)',
                'anonymized_ip_addresses' => 'Anonymisierte IP-Adressen',
                'browser_information' => 'Browser-Typ (anonymisiert)',
                'access_timestamps' => 'Zugriffszeitpunkte',
                'processing_purposes' => 'Verarbeitungszwecke',
                'purpose_file_sharing' => 'Bereitstellung der Datei-Sharing-Funktionalität',
                'purpose_security' => 'IT-Sicherheit und Missbrauchsprävention',
                'purpose_system_stability' => 'Gewährleistung der Systemstabilität',
                'purpose_abuse_prevention' => 'Verhinderung von Spam und Missbrauch',
                'legal_basis' => 'Rechtsgrundlagen',
                'contract_fulfillment' => 'Vertragserfüllung',
                'contract_fulfillment_desc' => 'Art. 6 Abs. 1 lit. b DSGVO - Erfüllung des Nutzungsvertrags',
                'legitimate_interests' => 'Berechtigte Interessen',
                'legitimate_interests_desc' => 'Art. 6 Abs. 1 lit. f DSGVO - IT-Sicherheit und Betriebssicherheit',
                'data_retention' => 'Speicherdauer',
                'files_retention' => 'Dateien: Automatische Löschung nach gewählter Ablaufzeit (1h-1 Woche)',
                'logs_retention' => 'Security-Logs: Maximal 30 Tage (konfigurierbar)',
                'automatic_deletion' => 'Vollständig automatisierte Löschung ohne manuelle Eingriffe',
                'privacy_by_design' => 'Privacy-by-Design',
                'e2e_encryption' => 'Ende-zu-Ende-Verschlüsselung',
                'e2e_encryption_desc' => 'Alle Dateien werden im Browser verschlüsselt und bleiben für uns unlesbar',
                'ip_anonymization' => 'IP-Anonymisierung',
                'ip_anonymization_desc' => 'IP-Adressen werden sofort anonymisiert (letztes Oktett entfernt)',
                'auto_expiry' => 'Automatische Löschung',
                'auto_expiry_desc' => 'Daten werden automatisch nach Ablauf der gewählten Zeit gelöscht',
                'your_rights' => 'Ihre Rechte',
                'right_to_information' => 'Auskunftsrecht (Art. 15 DSGVO)',
                'right_to_information_desc' => 'Erfahren Sie, welche Daten über Ihre Datei gespeichert sind',
                'request_information' => 'Auskunft anfordern',
                'right_to_deletion' => 'Löschrecht (Art. 17 DSGVO)',
                'right_to_deletion_desc' => 'Lassen Sie Ihre Daten vor Ablauf löschen',
                'request_deletion' => 'Löschung anfordern',
                'right_to_portability' => 'Datenportabilität (Art. 20 DSGVO)',
                'right_to_portability_desc' => 'Exportieren Sie Ihre Daten in strukturiertem Format',
                'export_data' => 'Daten exportieren',
                'security_measures' => 'Sicherheitsmaßnahmen',
                'security_transport_encryption' => 'HTTPS-Transportverschlüsselung für alle Verbindungen',
                'security_access_controls' => 'Strenge Zugangskontrollen und Admin-Authentifizierung',
                'security_monitoring' => 'Kontinuierliches Security-Monitoring',
                'security_regular_updates' => 'Regelmäßige Sicherheitsupdates',
                'third_parties' => 'Drittanbieter',
                'no_third_party_sharing' => 'Ihre Daten werden nicht an Drittanbieter weitergegeben. CactusDrop läuft vollständig auf eigenen Servern.',
                'contact_data_protection' => 'Kontakt Datenschutz',
                'data_protection_contact_info' => 'Bei Fragen zum Datenschutz wenden Sie sich an unseren Datenschutzbeauftragten:',
                'complaint_authority' => 'Aufsichtsbehörde',
                'complaint_authority_info' => 'Ihr zuständiger Landesdatenschutzbeauftragter',
                'policy_updates' => 'Änderungen dieser Datenschutzerklärung',
                'policy_updates_desc' => 'Diese Datenschutzerklärung kann bei rechtlichen Änderungen oder neuen Funktionen aktualisiert werden. Die aktuelle Version finden Sie immer hier.',
                'last_updated' => 'Letzte Aktualisierung',
                'version' => 'Version',
                'exercise_rights' => 'Rechte ausüben',
                'back_to_cactusdrop' => 'Zurück zu CactusDrop',
                'gdpr_compliant' => 'DSGVO-konform',
                
                // Time units
                'minutes' => 'Minuten',
                'hours' => 'Stunden',
                'days' => 'Tage',
                'weeks' => 'Wochen',
                'ago' => 'vor',

                // PWA
                'install_app' => 'Als App installieren',
                'add_to_homescreen' => 'Zum Homescreen hinzufügen'
            ],

            'en' => [
                // General
                'app_name' => 'CactusDrop',
                'app_subtitle' => 'Share files with end-to-end encryption.',
                'upload' => 'Upload',
                'download' => 'Download',
                'delete' => 'Delete',
                'cancel' => 'Cancel',
                'continue' => 'Continue',
                'back' => 'Back',
                'close' => 'Close',
                'copy' => 'Copy',
                'copied' => 'Copied!',
                'error' => 'Error',
                'success' => 'Success',
                'loading' => 'Loading...',

                // Upload
                'upload_files' => 'Upload files',
                'upload_drag_drop' => 'or drag & drop',
                'upload_multiple_info' => 'Multiple files are encrypted in parallel in your browser',
                'upload_multiple_tip' => '💡 Select multiple files for bulk upload',
                'password_protect' => 'Additional password protection',
                'password_protect_info' => 'Encrypts the E2EE key additionally.',
                'password_placeholder' => 'Enter password...',
                'one_time_download' => 'One-time download',
                'one_time_download_info' => 'The link becomes invalid after first download.',
                'expiry_time' => 'Expiry time',
                'expiry_1h' => '1 hour',
                'expiry_6h' => '6 hours',
                'expiry_12h' => '12 hours',
                'expiry_24h' => '24 hours (default)',
                'expiry_48h' => '2 days',
                'expiry_72h' => '3 days',
                'expiry_168h' => '1 week',

                // Progress
                'file_preparing' => 'Preparing file...',
                'encrypting_browser' => 'Encrypting file in browser...',
                'uploading_file' => 'Uploading file...',
                'upload_complete' => 'Upload complete!',
                'processing_multiple' => 'Processing multiple files...',
                'encrypting_files' => 'Encrypting {{current}} of {{total}} files',
                'overall_progress' => 'Overall progress',

                // Success
                'upload_successful' => 'Upload successful!',
                'secure_link_valid' => 'Your secure E2E-encrypted link is valid for {{hours}}h:',
                'share_qr_code' => 'Or share via QR code:',
                'delete_link_immediately' => 'Delete link immediately',
                'share_another_file' => 'Share another file',
                'bulk_upload_successful' => 'Bulk upload successful!',
                'files_uploaded_count' => '{{count}} files uploaded successfully:',
                'copy_all_links' => 'Copy all links',
                'download_links_json' => 'Download links as JSON',
                'expires_in' => 'Expires in {{time}}',

                // Download
                'download_ready' => 'Download ready',
                'decrypt_download' => 'Decrypt & download file',
                'password_required' => 'Password required',
                'enter_password' => 'Enter password',
                'decrypt_info' => 'Decryption happens in your browser.',
                'downloading' => 'Downloading...',
                'decrypting' => 'Decrypting file...',
                'preparing_key' => 'Preparing key...',
                'loading_encrypted' => 'Loading encrypted data...',
                'starting_download' => 'Starting download...',

                // Errors
                'file_not_found' => 'File not found',
                'file_expired' => 'This file has expired or was already downloaded.',
                'file_deleted_onetime' => 'This file was automatically deleted after first download.',
                'invalid_password' => 'Wrong password or corrupted link.',
                'decryption_failed' => 'Decryption failed',
                'upload_failed' => 'Upload failed',
                'file_too_large' => 'File too large. Maximum: {{max}}',
                'file_type_not_allowed' => 'File type not allowed',
                'network_error' => 'Network error. Please try again.',
                'something_went_wrong' => 'Something went wrong. Please try again.',
                'try_again' => 'Try again',

                // Admin
                'admin_panel' => 'Admin Panel',
                'login' => 'Login',
                'logout' => 'Logout',
                'username' => 'Username',
                'password' => 'Password',
                'dashboard' => 'Dashboard',
                'statistics' => 'Statistics',
                'security_logs' => 'Security Logs',
                'file_management' => 'File Management',
                'settings' => 'Settings',
                
                // GDPR Privacy
                'privacy_policy' => 'Privacy Policy',
                'privacy_policy_subtitle' => 'Transparency about our data processing',
                'data_controller' => 'Data Controller',
                'responsible_entity' => 'Responsible Entity',
                'data_processing_overview' => 'Data Processing Overview',
                'data_processing_description' => 'CactusDrop processes your data exclusively for temporary file storage and sharing. We implement Privacy-by-Design.',
                'processed_data_types' => 'Types of Processed Data',
                'uploaded_files' => 'Uploaded files (temporarily encrypted)',
                'file_metadata' => 'File metadata (name, size, type)',
                'anonymized_ip_addresses' => 'Anonymized IP addresses',
                'browser_information' => 'Browser type (anonymized)',
                'access_timestamps' => 'Access timestamps',
                'processing_purposes' => 'Processing Purposes',
                'purpose_file_sharing' => 'Providing file sharing functionality',
                'purpose_security' => 'IT security and abuse prevention',
                'purpose_system_stability' => 'Ensuring system stability',
                'purpose_abuse_prevention' => 'Prevention of spam and abuse',
                'legal_basis' => 'Legal Basis',
                'contract_fulfillment' => 'Contract Fulfillment',
                'contract_fulfillment_desc' => 'Art. 6 para. 1 lit. b GDPR - Fulfillment of usage contract',
                'legitimate_interests' => 'Legitimate Interests',
                'legitimate_interests_desc' => 'Art. 6 para. 1 lit. f GDPR - IT security and operational security',
                'data_retention' => 'Data Retention',
                'files_retention' => 'Files: Automatic deletion after selected expiry time (1h-1 week)',
                'logs_retention' => 'Security logs: Maximum 30 days (configurable)',
                'automatic_deletion' => 'Fully automated deletion without manual intervention',
                'privacy_by_design' => 'Privacy-by-Design',
                'e2e_encryption' => 'End-to-End Encryption',
                'e2e_encryption_desc' => 'All files are encrypted in the browser and remain unreadable to us',
                'ip_anonymization' => 'IP Anonymization',
                'ip_anonymization_desc' => 'IP addresses are immediately anonymized (last octet removed)',
                'auto_expiry' => 'Automatic Deletion',
                'auto_expiry_desc' => 'Data is automatically deleted after the selected time expires',
                'your_rights' => 'Your Rights',
                'right_to_information' => 'Right to Information (Art. 15 GDPR)',
                'right_to_information_desc' => 'Learn what data is stored about your file',
                'request_information' => 'Request information',
                'right_to_deletion' => 'Right to Deletion (Art. 17 GDPR)',
                'right_to_deletion_desc' => 'Have your data deleted before expiry',
                'request_deletion' => 'Request deletion',
                'right_to_portability' => 'Data Portability (Art. 20 GDPR)',
                'right_to_portability_desc' => 'Export your data in structured format',
                'export_data' => 'Export data',
                'security_measures' => 'Security Measures',
                'security_transport_encryption' => 'HTTPS transport encryption for all connections',
                'security_access_controls' => 'Strict access controls and admin authentication',
                'security_monitoring' => 'Continuous security monitoring',
                'security_regular_updates' => 'Regular security updates',
                'third_parties' => 'Third Parties',
                'no_third_party_sharing' => 'Your data is not shared with third parties. CactusDrop runs entirely on our own servers.',
                'contact_data_protection' => 'Data Protection Contact',
                'data_protection_contact_info' => 'For data protection questions, contact our data protection officer:',
                'complaint_authority' => 'Supervisory Authority',
                'complaint_authority_info' => 'Your competent state data protection officer',
                'policy_updates' => 'Changes to this Privacy Policy',
                'policy_updates_desc' => 'This privacy policy may be updated for legal changes or new features. The current version can always be found here.',
                'last_updated' => 'Last updated',
                'version' => 'Version',
                'exercise_rights' => 'Exercise rights',
                'back_to_cactusdrop' => 'Back to CactusDrop',
                'gdpr_compliant' => 'GDPR compliant',

                // Time units
                'minutes' => 'minutes',
                'hours' => 'hours',
                'days' => 'days',
                'weeks' => 'weeks',
                'ago' => 'ago',

                // PWA
                'install_app' => 'Install as App',
                'add_to_homescreen' => 'Add to homescreen'
            ]
        ];
    }

    /**
     * Übersetzung abrufen
     */
    public static function get($key, $params = []) {
        $translation = self::$translations[self::$currentLang][$key] ?? self::$translations['de'][$key] ?? $key;
        
        // Parameter ersetzen
        foreach ($params as $param => $value) {
            $translation = str_replace('{{' . $param . '}}', $value, $translation);
        }
        
        return $translation;
    }

    /**
     * Kurze Übersetzungsfunktion
     */
    public static function t($key, $params = []) {
        return self::get($key, $params);
    }

    /**
     * Aktuelle Sprache
     */
    public static function getCurrentLanguage() {
        return self::$currentLang;
    }

    /**
     * Verfügbare Sprachen
     */
    public static function getAvailableLanguages() {
        return self::$availableLanguages;
    }

    /**
     * Language-Switch HTML generieren
     */
    public static function getLanguageSwitch($currentUrl = null) {
        if ($currentUrl === null) {
            $currentUrl = $_SERVER['REQUEST_URI'];
        }
        
        $html = '<div class="language-switch flex items-center gap-2">';
        
        foreach (self::$availableLanguages as $langCode => $langInfo) {
            $isActive = ($langCode === self::$currentLang);
            
            // URL mit Language-Parameter erstellen
            $separator = (strpos($currentUrl, '?') !== false) ? '&' : '?';
            $langUrl = $currentUrl . $separator . 'lang=' . $langCode;
            
            $activeClass = $isActive ? 'bg-green-600 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600';
            
            $html .= '<a href="' . htmlspecialchars($langUrl) . '" 
                        class="' . $activeClass . ' px-3 py-1 rounded-md text-sm font-medium transition-colors flex items-center gap-1"
                        title="' . htmlspecialchars($langInfo['name']) . '">';
            $html .= '<span>' . $langInfo['flag'] . '</span>';
            $html .= '<span>' . strtoupper($langCode) . '</span>';
            $html .= '</a>';
        }
        
        $html .= '</div>';
        
        return $html;
    }

    /**
     * JavaScript-Übersetzungen für Frontend
     */
    public static function getJavaScriptTranslations() {
        $jsTranslations = [];
        $currentTranslations = self::$translations[self::$currentLang];
        
        // Nur die für JavaScript relevanten Übersetzungen
        $jsKeys = [
            'uploading_file', 'encrypting_browser', 'file_preparing', 'upload_complete',
            'decrypting', 'downloading', 'starting_download', 'loading_encrypted',
            'copy', 'copied', 'error', 'success', 'network_error', 'try_again',
            'invalid_password', 'decryption_failed', 'file_not_found'
        ];
        
        foreach ($jsKeys as $key) {
            if (isset($currentTranslations[$key])) {
                $jsTranslations[$key] = $currentTranslations[$key];
            }
        }
        
        return 'window.CACTUSDROP_LANG = ' . json_encode($jsTranslations) . ';' . "\n";
    }
}

// Helper-Funktionen für Templates
function t($key, $params = []) {
    return LanguageManager::t($key, $params);
}

function getCurrentLang() {
    return LanguageManager::getCurrentLanguage();
}

function getLanguageSwitch($currentUrl = null) {
    return LanguageManager::getLanguageSwitch($currentUrl);
}

// Auto-Init bei Include
LanguageManager::init();
?>