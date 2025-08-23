<?php
// Production Upload-Version
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

require_once 'config.php';

// DSGVO-konforme Privacy-Funktionen laden (optional)
if (file_exists('privacy.php')) {
    require_once 'privacy.php';
}

header('Content-Type: application/json');

// Request-Methode prüfen
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed.']);
    exit;
}

// File-Upload prüfen
if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'File upload error or no file provided.']);
    exit;
}

$file = $_FILES['file'];
$password = isset($_POST['password']) && !empty($_POST['password']) ? $_POST['password'] : null;
$isOneTime = isset($_POST['oneTimeDownload']) && $_POST['oneTimeDownload'] === 'true';
$expiryHours = isset($_POST['expiry_hours']) ? (int)$_POST['expiry_hours'] : 24;

// Validate expiry hours (1-168 hours = 1 hour to 1 week)
if ($expiryHours < 1 || $expiryHours > 168) {
    $expiryHours = 24; // Default fallback
}

// Basis-Validierung
if ($file['size'] > 100 * 1024 * 1024) { // 100MB
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'File too large. Maximum: 100MB']);
    exit;
}

if ($file['size'] <= 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'File is empty']);
    exit;
}

// Filename bereinigen
$originalFilename = basename($file['name']);
$extension = strtolower(pathinfo($originalFilename, PATHINFO_EXTENSION));

// Gefährliche Extensions blockieren
$blocked = array('php', 'php3', 'php4', 'php5', 'phtml', 'asp', 'aspx', 'jsp', 'exe', 'bat', 'cmd', 'com', 'scr');
if (in_array($extension, $blocked)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'File type not allowed']);
    exit;
}

// Sichere IDs generieren
$fileId = bin2hex(random_bytes(8));
$secretToken = bin2hex(random_bytes(32));

// Upload-Directory
$uploadDir = defined('UPLOAD_DIR') ? UPLOAD_DIR : './uploads/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$uploadPath = $uploadDir . $fileId;

// Datei verschieben
if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Cannot save file']);
    exit;
}

chmod($uploadPath, 0644);

// Datenbank-Connection
try {
    $conn = get_db_connection();
    $deleteAt = date('Y-m-d H:i:s', strtotime("+{$expiryHours} hours"));

    // Einfache Schema-Erkennung mit besserer Fehlerbehandlung
    $hasExtendedSchema = false;
    $result = $conn->query("SHOW COLUMNS FROM files");
    $columns = [];
    while ($row = $result->fetch_assoc()) {
        $columns[] = $row['Field'];
    }
    
    // Prüfe ob alle v0.4.0 Spalten vorhanden sind
    $requiredV40Columns = ['file_size', 'mime_type', 'upload_ip', 'user_agent', 'expiry_hours', 'language'];
    $hasExtendedSchema = count(array_intersect($requiredV40Columns, $columns)) === count($requiredV40Columns);
    
    if ($hasExtendedSchema) {
        // v0.4.0 Schema verwenden
        $uploadIP = null; // Vereinfacht - keine Privacy-Features erstmal
        $userAgent = null;
        $currentLang = 'de';
        $mimeType = $file['type'] ?? 'application/octet-stream';
        
        $stmt = $conn->prepare("INSERT INTO files (id, secret_token, original_filename, file_size, mime_type, upload_ip, user_agent, expiry_hours, language, is_onetime, delete_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        $stmt->bind_param('sssisssisis', $fileId, $secretToken, $originalFilename, $file['size'], $mimeType, $uploadIP, $userAgent, $expiryHours, $currentLang, $isOneTime, $deleteAt);
    } else {
        // Fallback auf alte Struktur
        $stmt = $conn->prepare("INSERT INTO files (id, secret_token, original_filename, is_onetime, delete_at) VALUES (?, ?, ?, ?, ?)");
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        $stmt->bind_param('sssis', $fileId, $secretToken, $originalFilename, $isOneTime, $deleteAt);
    }

} catch (Exception $e) {
    if (file_exists($uploadPath)) {
        unlink($uploadPath);
    }
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage(), 'debug' => ['hasSchema' => $hasExtendedSchema ?? false, 'columns' => $columns ?? []]]);
    exit;
}

// Datenbank-Ausführung mit verbesserter Fehlerbehandlung
try {
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }
    
    // URLs erstellen
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $path = dirname($_SERVER['PHP_SELF']);
    $baseUrl = $protocol . '://' . $host . $path;
    
    $downloadUrl = $baseUrl . '/download.php?id=' . $fileId;
    $deleteUrl = $baseUrl . '/delete.php?id=' . $fileId . '&token=' . $secretToken;

    $stmt->close();
    $conn->close();

    echo json_encode([
        'status' => 'success',
        'downloadUrl' => $downloadUrl,
        'deleteUrl' => $deleteUrl,
        'expiresAt' => $deleteAt,
        'fileSize' => $file['size'],
        'fileName' => $originalFilename,
        'debug' => [
            'schema' => $hasExtendedSchema ? 'v0.4.0' : 'legacy',
            'columns' => count($columns)
        ]
    ]);

} catch (Exception $e) {
    // Cleanup bei Fehler
    if (file_exists($uploadPath)) {
        unlink($uploadPath);
    }
    
    if (isset($stmt)) {
        $stmt->close();
    }
    if (isset($conn)) {
        $conn->close();
    }
    
    http_response_code(500);
    echo json_encode([
        'status' => 'error', 
        'message' => 'Database execute failed: ' . $e->getMessage(),
        'debug' => [
            'hasSchema' => $hasExtendedSchema ?? false,
            'columns' => $columns ?? [],
            'fileId' => $fileId ?? 'unknown'
        ]
    ]);
}
?>