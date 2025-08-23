<?php
/**
 * CactusDrop v0.4.0 - DSGVO Betroffenenrechte Interface
 * 
 * Selbstbedienungs-Portal für Betroffenenrechte:
 * - Auskunftsrecht (Art. 15 DSGVO)
 * - Löschrecht (Art. 17 DSGVO) 
 * - Datenportabilität (Art. 20 DSGVO)
 */

require_once 'config.php';
require_once 'privacy.php';

$action = $_GET['action'] ?? 'overview';
$message = '';
$error = '';

// CSRF-Schutz
session_start();
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Request-Handler
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF-Token prüfen
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Sicherheitsfehler. Bitte versuchen Sie es erneut.";
    } else {
        switch ($_POST['privacy_request']) {
            case 'data_inquiry':
                $result = handleDataInquiry($_POST);
                if ($result['success']) {
                    $message = $result['message'];
                } else {
                    $error = $result['message'];
                }
                break;
                
            case 'data_deletion':
                $result = handleDataDeletion($_POST);
                if ($result['success']) {
                    $message = $result['message'];
                } else {
                    $error = $result['message'];
                }
                break;
                
            case 'data_export':
                $result = handleDataExport($_POST);
                if ($result['success']) {
                    // File download wird direkt gestartet
                    exit;
                } else {
                    $error = $result['message'];
                }
                break;
        }
    }
}

// Datenauskunft (Art. 15 DSGVO)
function handleDataInquiry($data) {
    $fileId = trim($data['file_id'] ?? '');
    
    if (empty($fileId)) {
        return ['success' => false, 'message' => 'Datei-ID ist erforderlich.'];
    }
    
    try {
        $conn = get_db_connection();
        
        // Datei-Informationen abrufen (anonymisiert)
        $stmt = $conn->prepare("SELECT id, original_filename, file_size, mime_type, created_at, delete_at, downloads_count, expiry_hours, language FROM files WHERE id = ?");
        $stmt->bind_param('s', $fileId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $conn->close();
            return ['success' => false, 'message' => 'Datei nicht gefunden oder bereits gelöscht.'];
        }
        
        $fileData = $result->fetch_assoc();
        $stmt->close();
        
        // Security-Logs für diese Datei (anonymisiert)
        $stmt = $conn->prepare("SELECT event_type, severity, created_at FROM security_logs WHERE file_id = ? ORDER BY created_at DESC LIMIT 10");
        $stmt->bind_param('s', $fileId);
        $stmt->execute();
        $logs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        $conn->close();
        
        // Datenschutz-konforme Auskunft erstellen
        $inquiry = [
            'file_info' => [
                'id' => $fileData['id'],
                'filename' => $fileData['original_filename'],
                'size' => formatBytes($fileData['file_size'] ?? 0),
                'type' => $fileData['mime_type'] ?? 'unknown',
                'uploaded_at' => $fileData['created_at'],
                'expires_at' => $fileData['delete_at'],
                'downloads' => $fileData['downloads_count'] ?? 0,
                'expiry_hours' => $fileData['expiry_hours'] ?? 24,
                'language' => $fileData['language'] ?? 'de'
            ],
            'security_events' => array_map(function($log) {
                return [
                    'event' => $log['event_type'],
                    'severity' => $log['severity'],
                    'timestamp' => $log['created_at']
                ];
            }, $logs),
            'data_processing_info' => [
                'purpose' => 'Temporäre Dateispeicherung und -freigabe',
                'legal_basis' => 'Vertragserfüllung (Art. 6 Abs. 1 lit. b DSGVO)',
                'retention_period' => $fileData['expiry_hours'] . ' Stunden',
                'recipients' => 'Nur Personen mit gültigem Download-Link',
                'your_rights' => [
                    'Löschung vor Ablauf (Art. 17 DSGVO)',
                    'Datenportabilität (Art. 20 DSGVO)',
                    'Beschwerde bei Aufsichtsbehörde'
                ]
            ]
        ];
        
        // Privacy-Logging
        PrivacyManager::logSecurityEvent('gdpr_data_inquiry', "Data inquiry for file: $fileId", 'low', $fileId);
        
        $_SESSION['inquiry_result'] = $inquiry;
        return ['success' => true, 'message' => 'Datenauskunft erfolgreich erstellt.'];
        
    } catch (Exception $e) {
        error_log("GDPR data inquiry error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Fehler bei der Datenauskunft.'];
    }
}

// Datenlöschung (Art. 17 DSGVO)
function handleDataDeletion($data) {
    $fileId = trim($data['file_id'] ?? '');
    $deleteToken = trim($data['delete_token'] ?? '');
    
    if (empty($fileId) || empty($deleteToken)) {
        return ['success' => false, 'message' => 'Datei-ID und Lösch-Token sind erforderlich.'];
    }
    
    try {
        $conn = get_db_connection();
        
        // Token validieren
        $stmt = $conn->prepare("SELECT id, secret_token, original_filename FROM files WHERE id = ? AND secret_token = ?");
        $stmt->bind_param('ss', $fileId, $deleteToken);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $conn->close();
            return ['success' => false, 'message' => 'Ungültige Datei-ID oder Lösch-Token.'];
        }
        
        $fileData = $result->fetch_assoc();
        $stmt->close();
        
        // Datei physisch löschen
        $uploadDir = defined('UPLOAD_DIR') ? UPLOAD_DIR : './uploads/';
        $filePath = $uploadDir . $fileId;
        if (file_exists($filePath)) {
            unlink($filePath);
        }
        
        // Datenbank-Eintrag löschen
        $stmt = $conn->prepare("DELETE FROM files WHERE id = ?");
        $stmt->bind_param('s', $fileId);
        $stmt->execute();
        $stmt->close();
        
        // Zugehörige Logs anonymisieren (nicht löschen für Security)
        $stmt = $conn->prepare("UPDATE security_logs SET file_id = NULL, details = 'GDPR deletion request' WHERE file_id = ?");
        $stmt->bind_param('s', $fileId);
        $stmt->execute();
        $stmt->close();
        
        $conn->close();
        
        // Privacy-Logging
        PrivacyManager::logSecurityEvent('gdpr_data_deletion', "GDPR deletion request for file: {$fileData['original_filename']}", 'medium');
        
        return ['success' => true, 'message' => "Datei '{$fileData['original_filename']}' wurde gemäß DSGVO Art. 17 gelöscht."];
        
    } catch (Exception $e) {
        error_log("GDPR data deletion error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Fehler bei der Datenlöschung.'];
    }
}

// Datenexport (Art. 20 DSGVO)
function handleDataExport($data) {
    $fileId = trim($data['file_id'] ?? '');
    
    if (empty($fileId)) {
        return ['success' => false, 'message' => 'Datei-ID ist erforderlich.'];
    }
    
    try {
        $conn = get_db_connection();
        
        // Vollständige Datei-Metadaten abrufen
        $stmt = $conn->prepare("SELECT * FROM files WHERE id = ?");
        $stmt->bind_param('s', $fileId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $conn->close();
            return ['success' => false, 'message' => 'Datei nicht gefunden.'];
        }
        
        $fileData = $result->fetch_assoc();
        $stmt->close();
        
        // Anonymisierte Security-Logs
        $stmt = $conn->prepare("SELECT event_type, severity, created_at, details FROM security_logs WHERE file_id = ? ORDER BY created_at DESC");
        $stmt->bind_param('s', $fileId);
        $stmt->execute();
        $logs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        $conn->close();
        
        // JSON-Export erstellen
        $exportData = [
            'export_info' => [
                'generated_at' => date('c'),
                'format' => 'JSON',
                'gdpr_article' => 'Art. 20 DSGVO - Recht auf Datenübertragbarkeit',
                'data_controller' => 'CactusDrop File Sharing Service'
            ],
            'file_metadata' => [
                'id' => $fileData['id'],
                'original_filename' => $fileData['original_filename'],
                'file_size' => $fileData['file_size'],
                'mime_type' => $fileData['mime_type'],
                'upload_ip' => 'anonymized', // IP immer anonymisiert im Export
                'user_agent' => $fileData['user_agent'],
                'downloads_count' => $fileData['downloads_count'],
                'expiry_hours' => $fileData['expiry_hours'],
                'language' => $fileData['language'],
                'is_onetime' => (bool)$fileData['is_onetime'],
                'created_at' => $fileData['created_at'],
                'delete_at' => $fileData['delete_at'],
                'last_download_at' => $fileData['last_download_at']
            ],
            'security_events' => $logs,
            'privacy_notice' => [
                'ip_anonymization' => 'IP-Adressen wurden gemäß DSGVO anonymisiert',
                'data_retention' => 'Daten werden automatisch nach Ablaufzeit gelöscht',
                'encryption' => 'Dateien werden Ende-zu-Ende verschlüsselt übertragen'
            ]
        ];
        
        // JSON-Download starten
        $filename = 'cactusdrop_export_' . $fileId . '_' . date('Y-m-d') . '.json';
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen(json_encode($exportData, JSON_PRETTY_PRINT)));
        
        echo json_encode($exportData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        
        // Privacy-Logging
        PrivacyManager::logSecurityEvent('gdpr_data_export', "Data export for file: $fileId", 'low', $fileId);
        
        return ['success' => true];
        
    } catch (Exception $e) {
        error_log("GDPR data export error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Fehler beim Datenexport.'];
    }
}

function formatBytes($size, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB'];
    for ($i = 0; $size >= 1024 && $i < count($units) - 1; $i++) {
        $size /= 1024;
    }
    return round($size, $precision) . ' ' . $units[$i];
}
?>
<!DOCTYPE html>
<html lang="de" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DSGVO Betroffenenrechte - CactusDrop</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .fade-in { animation: fadeIn 0.5s ease-in-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body class="bg-gray-900 text-gray-200 min-h-screen p-4">

<div class="max-w-4xl mx-auto">
    <!-- Header -->
    <header class="text-center mb-8">
        <div class="flex items-center justify-center gap-3 mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#2f855a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
            </svg>
            <h1 class="text-3xl font-bold text-white">DSGVO Betroffenenrechte</h1>
        </div>
        <p class="text-gray-400">Ihre Rechte gemäß Datenschutz-Grundverordnung (DSGVO)</p>
    </header>

    <!-- Messages -->
    <?php if ($message): ?>
        <div class="bg-green-900/50 border border-green-700 text-green-300 px-4 py-3 rounded-lg mb-6 fade-in">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="bg-red-900/50 border border-red-700 text-red-300 px-4 py-3 rounded-lg mb-6 fade-in">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <!-- Betroffenenrechte Übersicht -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <!-- Auskunftsrecht -->
        <div class="bg-gray-800 rounded-xl p-6 border border-gray-700">
            <div class="flex items-center gap-3 mb-4">
                <div class="bg-blue-500/20 p-2 rounded-lg">
                    <svg class="w-6 h-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-white">Auskunftsrecht</h3>
            </div>
            <p class="text-gray-400 text-sm mb-4">
                Art. 15 DSGVO: Erfahren Sie, welche Daten über Ihre hochgeladene Datei gespeichert sind.
            </p>
            <a href="#inquiry" class="inline-block bg-blue-600 hover:bg-blue-500 text-white px-4 py-2 rounded-lg text-sm transition-colors">
                Datenauskunft anfragen
            </a>
        </div>

        <!-- Löschrecht -->
        <div class="bg-gray-800 rounded-xl p-6 border border-gray-700">
            <div class="flex items-center gap-3 mb-4">
                <div class="bg-red-500/20 p-2 rounded-lg">
                    <svg class="w-6 h-6 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-white">Löschrecht</h3>
            </div>
            <p class="text-gray-400 text-sm mb-4">
                Art. 17 DSGVO: Lassen Sie Ihre Datei und alle zugehörigen Daten sofort löschen.
            </p>
            <a href="#deletion" class="inline-block bg-red-600 hover:bg-red-500 text-white px-4 py-2 rounded-lg text-sm transition-colors">
                Datei löschen
            </a>
        </div>

        <!-- Datenportabilität -->
        <div class="bg-gray-800 rounded-xl p-6 border border-gray-700">
            <div class="flex items-center gap-3 mb-4">
                <div class="bg-green-500/20 p-2 rounded-lg">
                    <svg class="w-6 h-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-white">Datenportabilität</h3>
            </div>
            <p class="text-gray-400 text-sm mb-4">
                Art. 20 DSGVO: Exportieren Sie alle Metadaten Ihrer Datei in strukturiertem Format.
            </p>
            <a href="#export" class="inline-block bg-green-600 hover:bg-green-500 text-white px-4 py-2 rounded-lg text-sm transition-colors">
                Daten exportieren
            </a>
        </div>
    </div>

    <!-- Auskunftsrecht Form -->
    <div id="inquiry" class="bg-gray-800 rounded-xl p-6 border border-gray-700 mb-6">
        <h3 class="text-xl font-semibold mb-4 text-white">📋 Datenauskunft (Art. 15 DSGVO)</h3>
        <p class="text-gray-400 mb-6">
            Geben Sie die Datei-ID ein, um eine vollständige Übersicht über alle gespeicherten Daten zu erhalten.
        </p>
        
        <form method="POST" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <input type="hidden" name="privacy_request" value="data_inquiry">
            
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-2">Datei-ID</label>
                <input type="text" name="file_id" required 
                       class="w-full bg-gray-700 border-gray-600 rounded-md p-3 text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                       placeholder="z.B. a1b2c3d4e5f6g7h8">
                <p class="text-xs text-gray-500 mt-1">Die Datei-ID finden Sie in Ihrem Download- oder Lösch-Link</p>
            </div>
            
            <button type="submit" class="bg-blue-600 hover:bg-blue-500 text-white font-bold py-2 px-6 rounded-lg transition-colors">
                📋 Datenauskunft anfordern
            </button>
        </form>
    </div>

    <!-- Auskunft Ergebnis -->
    <?php if (isset($_SESSION['inquiry_result'])): ?>
        <div class="bg-gray-800 rounded-xl p-6 border border-gray-700 mb-6 fade-in">
            <h4 class="text-lg font-semibold mb-4 text-white">📊 Ihre Datenauskunft</h4>
            
            <?php $inquiry = $_SESSION['inquiry_result']; ?>
            
            <!-- Datei-Informationen -->
            <div class="mb-6">
                <h5 class="font-semibold text-gray-300 mb-2">Datei-Informationen</h5>
                <div class="bg-gray-900 rounded-lg p-4 space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-400">Dateiname:</span>
                        <span class="text-white"><?php echo htmlspecialchars($inquiry['file_info']['filename']); ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-400">Größe:</span>
                        <span class="text-white"><?php echo $inquiry['file_info']['size']; ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-400">Hochgeladen:</span>
                        <span class="text-white"><?php echo date('d.m.Y H:i', strtotime($inquiry['file_info']['uploaded_at'])); ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-400">Läuft ab:</span>
                        <span class="text-white"><?php echo date('d.m.Y H:i', strtotime($inquiry['file_info']['expires_at'])); ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-400">Downloads:</span>
                        <span class="text-white"><?php echo $inquiry['file_info']['downloads']; ?></span>
                    </div>
                </div>
            </div>

            <!-- Verarbeitungsinformationen -->
            <div class="mb-4">
                <h5 class="font-semibold text-gray-300 mb-2">Verarbeitungsinformationen</h5>
                <div class="bg-gray-900 rounded-lg p-4 space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-400">Zweck:</span>
                        <span class="text-white"><?php echo $inquiry['data_processing_info']['purpose']; ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-400">Rechtsgrundlage:</span>
                        <span class="text-white"><?php echo $inquiry['data_processing_info']['legal_basis']; ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-400">Aufbewahrung:</span>
                        <span class="text-white"><?php echo $inquiry['data_processing_info']['retention_period']; ?></span>
                    </div>
                </div>
            </div>
        </div>
        <?php unset($_SESSION['inquiry_result']); ?>
    <?php endif; ?>

    <!-- Löschrecht Form -->
    <div id="deletion" class="bg-gray-800 rounded-xl p-6 border border-gray-700 mb-6">
        <h3 class="text-xl font-semibold mb-4 text-white">🗑️ Datenlöschung (Art. 17 DSGVO)</h3>
        <p class="text-gray-400 mb-6">
            Lassen Sie Ihre Datei und alle zugehörigen Metadaten sofort und unwiderruflich löschen.
        </p>
        
        <form method="POST" class="space-y-4" onsubmit="return confirm('Datei wirklich löschen? Diese Aktion kann nicht rückgängig gemacht werden.')">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <input type="hidden" name="privacy_request" value="data_deletion">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Datei-ID</label>
                    <input type="text" name="file_id" required 
                           class="w-full bg-gray-700 border-gray-600 rounded-md p-3 text-white focus:ring-2 focus:ring-red-500 focus:border-red-500"
                           placeholder="z.B. a1b2c3d4e5f6g7h8">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Lösch-Token</label>
                    <input type="text" name="delete_token" required 
                           class="w-full bg-gray-700 border-gray-600 rounded-md p-3 text-white focus:ring-2 focus:ring-red-500 focus:border-red-500"
                           placeholder="Token aus Lösch-Link">
                </div>
            </div>
            
            <div class="bg-red-900/30 border border-red-700 rounded-lg p-3">
                <p class="text-xs text-red-300">
                    ⚠️ <strong>Wichtiger Hinweis:</strong> Die Löschung ist sofort und unwiderrufbar. 
                    Alle Metadaten werden gemäß DSGVO gelöscht oder anonymisiert.
                </p>
            </div>
            
            <button type="submit" class="bg-red-600 hover:bg-red-500 text-white font-bold py-2 px-6 rounded-lg transition-colors">
                🗑️ Datei endgültig löschen
            </button>
        </form>
    </div>

    <!-- Datenexport Form -->
    <div id="export" class="bg-gray-800 rounded-xl p-6 border border-gray-700 mb-6">
        <h3 class="text-xl font-semibold mb-4 text-white">📦 Datenexport (Art. 20 DSGVO)</h3>
        <p class="text-gray-400 mb-6">
            Exportieren Sie alle Metadaten Ihrer Datei in einem strukturierten, maschinenlesbaren Format (JSON).
        </p>
        
        <form method="POST" class="space-y-4">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <input type="hidden" name="privacy_request" value="data_export">
            
            <div>
                <label class="block text-sm font-medium text-gray-300 mb-2">Datei-ID</label>
                <input type="text" name="file_id" required 
                       class="w-full bg-gray-700 border-gray-600 rounded-md p-3 text-white focus:ring-2 focus:ring-green-500 focus:border-green-500"
                       placeholder="z.B. a1b2c3d4e5f6g7h8">
            </div>
            
            <div class="bg-green-900/30 border border-green-700 rounded-lg p-3">
                <p class="text-xs text-green-300">
                    📄 Der Export enthält alle Metadaten in DSGVO-konformer Form. 
                    IP-Adressen werden automatisch anonymisiert.
                </p>
            </div>
            
            <button type="submit" class="bg-green-600 hover:bg-green-500 text-white font-bold py-2 px-6 rounded-lg transition-colors">
                📦 Daten als JSON exportieren
            </button>
        </form>
    </div>

    <!-- Footer -->
    <footer class="text-center pt-8 text-xs text-gray-500">
        <p>CactusDrop v0.4.0 - DSGVO-konform | <a href="index.php" class="text-blue-400 hover:underline">Zurück zu CactusDrop</a></p>
    </footer>
</div>

</body>
</html>