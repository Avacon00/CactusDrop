<?php
// DSGVO Privacy Management
require_once 'privacy.php';

// Privacy-Handler
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['privacy_action'])) {
    $conn = get_db_connection();
    
    switch ($_POST['privacy_action']) {
        case 'update_settings':
            $privacyMode = isset($_POST['privacy_mode_enabled']) ? '1' : '0';
            $logRetention = max(1, min((int)$_POST['log_retention_days'], 90));
            $minimalLogging = isset($_POST['minimal_logging']) ? '1' : '0';
            $anonymizeIPs = isset($_POST['anonymize_ips']) ? '1' : '0';
            
            $settings = [
                ['privacy_mode_enabled', $privacyMode],
                ['log_retention_days', $logRetention],
                ['minimal_logging_enabled', $minimalLogging],
                ['anonymize_ips_enabled', $anonymizeIPs]
            ];
            
            foreach ($settings as $setting) {
                $stmt = $conn->prepare("INSERT INTO system_settings (setting_key, setting_value, setting_type, description, category) VALUES (?, ?, 'boolean', 'DSGVO Privacy Setting', 'privacy') ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
                $stmt->bind_param('ss', $setting[0], $setting[1]);
                $stmt->execute();
                $stmt->close();
            }
            
            GDPRAdminSecurity::logSecurityEvent('privacy_settings_updated', "Privacy settings modified", 'low');
            $success = "✅ Datenschutz-Einstellungen aktualisiert.";
            break;
            
        case 'cleanup_data':
            $cleaned = PrivacyManager::cleanupExpiredData();
            GDPRAdminSecurity::logSecurityEvent('privacy_cleanup_manual', "Manual data cleanup: $cleaned items removed", 'low');
            $success = "✅ Datenbereinigung abgeschlossen. $cleaned Einträge entfernt.";
            break;
            
        case 'anonymize_existing':
            // Bestehende Daten anonymisieren
            $stmt = $conn->prepare("UPDATE security_logs SET ip_address = ?, user_agent = ? WHERE ip_address != 'anonymized'");
            $anonymizedIP = 'anonymized';
            $anonymizedUA = 'anonymized';
            $stmt->bind_param('ss', $anonymizedIP, $anonymizedUA);
            $stmt->execute();
            $affected = $stmt->affected_rows;
            $stmt->close();
            
            GDPRAdminSecurity::logSecurityEvent('privacy_data_anonymized', "Anonymized $affected existing log entries", 'medium');
            $success = "✅ $affected bestehende Log-Einträge anonymisiert.";
            break;
    }
    
    $conn->close();
}

// Privacy-Statistiken laden
$privacyStats = PrivacyManager::getPrivacyStats();
$settings = getSystemSettings();
$privacySettings = [];
foreach ($settings as $setting) {
    if ($setting['category'] === 'privacy') {
        $privacySettings[$setting['setting_key']] = $setting['setting_value'];
    }
}
?>

<div class="max-w-6xl">
    <?php if (isset($success)): ?>
        <div class="bg-green-900/50 border border-green-700 text-green-300 px-4 py-3 rounded-lg mb-6 fade-in">
            <?php echo $success; ?>
        </div>
    <?php endif; ?>

    <!-- Privacy Dashboard -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-gray-800 rounded-xl p-4 border border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-400 text-sm">Privacy Modus</p>
                    <p class="text-2xl font-bold <?php echo $privacyStats['privacy_mode'] ? 'text-green-400' : 'text-red-400'; ?>">
                        <?php echo $privacyStats['privacy_mode'] ? 'Aktiv' : 'Inaktiv'; ?>
                    </p>
                </div>
                <div class="<?php echo $privacyStats['privacy_mode'] ? 'bg-green-500/20' : 'bg-red-500/20'; ?> p-2 rounded-lg">
                    <svg class="w-5 h-5 <?php echo $privacyStats['privacy_mode'] ? 'text-green-400' : 'text-red-400'; ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-gray-800 rounded-xl p-4 border border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-400 text-sm">Anonymisierte IPs</p>
                    <p class="text-2xl font-bold text-blue-400"><?php echo number_format($privacyStats['anonymized_ips']); ?></p>
                </div>
                <div class="bg-blue-500/20 p-2 rounded-lg">
                    <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"/>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-gray-800 rounded-xl p-4 border border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-400 text-sm">Log-Aufbewahrung</p>
                    <p class="text-2xl font-bold text-yellow-400"><?php echo $privacyStats['log_retention_days']; ?> Tage</p>
                </div>
                <div class="bg-yellow-500/20 p-2 rounded-lg">
                    <svg class="w-5 h-5 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-gray-800 rounded-xl p-4 border border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-400 text-sm">Gesamt-Logs</p>
                    <p class="text-2xl font-bold text-purple-400"><?php echo number_format($privacyStats['total_logs']); ?></p>
                </div>
                <div class="bg-purple-500/20 p-2 rounded-lg">
                    <svg class="w-5 h-5 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Privacy Settings -->
    <div class="bg-gray-800 rounded-xl p-6 border border-gray-700 mb-6">
        <h3 class="text-xl font-semibold mb-6 text-white">🛡️ DSGVO Datenschutz-Einstellungen</h3>
        
        <form method="POST" class="space-y-6">
            <input type="hidden" name="privacy_action" value="update_settings">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Privacy Modus -->
                <div class="space-y-4">
                    <h4 class="font-semibold text-gray-300">Grundeinstellungen</h4>
                    
                    <div class="flex items-center">
                        <input type="checkbox" name="privacy_mode_enabled" id="privacy_mode" 
                               <?php echo ($privacySettings['privacy_mode_enabled'] ?? '1') === '1' ? 'checked' : ''; ?>
                               class="mr-3 rounded bg-gray-700 border-gray-600 text-green-600 focus:ring-green-500">
                        <label for="privacy_mode" class="text-sm text-gray-300">
                            <strong>Privacy-Modus aktivieren</strong><br>
                            <span class="text-xs text-gray-500">Reduziert Datensammlung auf Minimum</span>
                        </label>
                    </div>
                    
                    <div class="flex items-center">
                        <input type="checkbox" name="anonymize_ips" id="anonymize_ips" 
                               <?php echo ($privacySettings['anonymize_ips_enabled'] ?? '1') === '1' ? 'checked' : ''; ?>
                               class="mr-3 rounded bg-gray-700 border-gray-600 text-green-600 focus:ring-green-500">
                        <label for="anonymize_ips" class="text-sm text-gray-300">
                            <strong>IP-Adressen anonymisieren</strong><br>
                            <span class="text-xs text-gray-500">Entfernt letztes Oktett (DSGVO-konform)</span>
                        </label>
                    </div>
                    
                    <div class="flex items-center">
                        <input type="checkbox" name="minimal_logging" id="minimal_logging" 
                               <?php echo ($privacySettings['minimal_logging_enabled'] ?? '0') === '1' ? 'checked' : ''; ?>
                               class="mr-3 rounded bg-gray-700 border-gray-600 text-green-600 focus:ring-green-500">
                        <label for="minimal_logging" class="text-sm text-gray-300">
                            <strong>Minimales Logging</strong><br>
                            <span class="text-xs text-gray-500">Nur kritische Sicherheitsereignisse loggen</span>
                        </label>
                    </div>
                </div>
                
                <!-- Aufbewahrungsfristen -->
                <div class="space-y-4">
                    <h4 class="font-semibold text-gray-300">Aufbewahrungsfristen</h4>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Log-Aufbewahrung (Tage)</label>
                        <input type="number" name="log_retention_days" min="1" max="90" 
                               value="<?php echo $privacySettings['log_retention_days'] ?? 30; ?>"
                               class="w-full bg-gray-700 border-gray-600 rounded-md p-2 text-white focus:ring-2 focus:ring-green-500">
                        <p class="text-xs text-gray-500 mt-1">DSGVO: Max. 90 Tage für Security-Logs</p>
                    </div>
                    
                    <div class="bg-blue-900/30 border border-blue-700 rounded-lg p-3">
                        <p class="text-xs text-blue-300">
                            <strong>ℹ️ DSGVO-Hinweis:</strong> Personenbezogene Daten werden automatisch nach Ablauf gelöscht. 
                            IP-Adressen werden bei Aktivierung sofort anonymisiert.
                        </p>
                    </div>
                </div>
            </div>
            
            <div class="flex gap-4">
                <button type="submit" class="bg-green-600 hover:bg-green-500 text-white font-bold py-2 px-6 rounded-lg transition-colors">
                    💾 Einstellungen speichern
                </button>
            </div>
        </form>
    </div>

    <!-- Data Management Actions -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Datenbereinigung -->
        <div class="bg-gray-800 rounded-xl p-6 border border-gray-700">
            <h3 class="text-lg font-semibold mb-4 text-white">🧹 Datenbereinigung</h3>
            <p class="text-gray-400 text-sm mb-4">
                Entfernt automatisch abgelaufene Dateien und alte Logs gemäß DSGVO-Aufbewahrungsfristen.
            </p>
            
            <form method="POST" class="inline">
                <input type="hidden" name="privacy_action" value="cleanup_data">
                <button type="submit" class="bg-blue-600 hover:bg-blue-500 text-white font-bold py-2 px-4 rounded-lg transition-colors">
                    🗑️ Bereinigung starten
                </button>
            </form>
            
            <?php if ($privacyStats['oldest_log']): ?>
                <p class="text-xs text-gray-500 mt-2">
                    Ältester Log: <?php echo date('d.m.Y H:i', strtotime($privacyStats['oldest_log'])); ?>
                </p>
            <?php endif; ?>
        </div>

        <!-- Bestehende Daten anonymisieren -->
        <div class="bg-gray-800 rounded-xl p-6 border border-gray-700">
            <h3 class="text-lg font-semibold mb-4 text-white">🔒 Daten-Anonymisierung</h3>
            <p class="text-gray-400 text-sm mb-4">
                Anonymisiert alle bestehenden IP-Adressen und User-Agents in den Security-Logs rückwirkend.
            </p>
            
            <form method="POST" class="inline" onsubmit="return confirm('Bestehende Daten anonymisieren? Diese Aktion kann nicht rückgängig gemacht werden.')">
                <input type="hidden" name="privacy_action" value="anonymize_existing">
                <button type="submit" class="bg-orange-600 hover:bg-orange-500 text-white font-bold py-2 px-4 rounded-lg transition-colors">
                    🛡️ Daten anonymisieren
                </button>
            </form>
            
            <p class="text-xs text-orange-300 mt-2">
                ⚠️ Diese Aktion ist nicht umkehrbar!
            </p>
        </div>
    </div>

    <!-- DSGVO Compliance Status -->
    <div class="bg-gray-800 rounded-xl p-6 border border-gray-700 mt-6">
        <h3 class="text-lg font-semibold mb-4 text-white">📋 DSGVO-Compliance Status</h3>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-green-900/30 border border-green-700 rounded-lg p-3">
                <div class="flex items-center gap-2 mb-2">
                    <svg class="w-5 h-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    <span class="font-semibold text-green-400">Technische Maßnahmen</span>
                </div>
                <ul class="text-xs text-green-300 space-y-1">
                    <li>✅ IP-Anonymisierung</li>
                    <li>✅ Automatische Löschung</li>
                    <li>✅ Privacy-by-Default</li>
                    <li>✅ Datenminimierung</li>
                </ul>
            </div>
            
            <div class="bg-yellow-900/30 border border-yellow-700 rounded-lg p-3">
                <div class="flex items-center gap-2 mb-2">
                    <svg class="w-5 h-5 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L5.268 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                    </svg>
                    <span class="font-semibold text-yellow-400">Empfohlene Ergänzungen</span>
                </div>
                <ul class="text-xs text-yellow-300 space-y-1">
                    <li>📄 Datenschutzerklärung</li>
                    <li>📋 Verarbeitungsverzeichnis</li>
                    <li>👤 Betroffenenrechte-Portal</li>
                    <li>📊 Privacy-Dashboard für User</li>
                </ul>
            </div>
            
            <div class="bg-blue-900/30 border border-blue-700 rounded-lg p-3">
                <div class="flex items-center gap-2 mb-2">
                    <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="font-semibold text-blue-400">Rechtsgrundlagen</span>
                </div>
                <ul class="text-xs text-blue-300 space-y-1">
                    <li>🛡️ Berechtigtes Interesse (Security)</li>
                    <li>⚖️ Rechtliche Verpflichtung</li>
                    <li>🤝 Vertragserfüllung</li>
                    <li>📝 Einwilligung (optional)</li>
                </ul>
            </div>
        </div>
    </div>
</div>