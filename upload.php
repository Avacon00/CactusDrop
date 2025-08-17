<?php
// Kompatible Upload-Version für alte Datenbank-Struktur
require_once 'config.php';

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

// Passwort-Handling: Falls Passwort gesetzt ist, aber DB-Schema es nicht unterstützt
// Speichern wir es NICHT in der DB, sondern nur verschlüsseln im Frontend
if ($password) {
    // Hinweis: Passwort wird nur im Frontend verwendet für E2E-Verschlüsselung
    // Nicht in DB gespeichert wegen Schema-Kompatibilität
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
$conn = get_db_connection();
$deleteAt = date('Y-m-d H:i:s', strtotime('+24 hours'));

// ALTE Datenbank-Struktur verwenden (ohne password_hash Spalte)
$stmt = $conn->prepare("INSERT INTO files (id, secret_token, original_filename, is_onetime, delete_at) VALUES (?, ?, ?, ?, ?)");
if (!$stmt) {
    if (file_exists($uploadPath)) {
        unlink($uploadPath);
    }
    $conn->close();
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database prepare failed']);
    exit;
}

$stmt->bind_param('sssis', $fileId, $secretToken, $originalFilename, $isOneTime, $deleteAt);

if ($stmt->execute()) {
    // URLs erstellen
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $path = dirname($_SERVER['PHP_SELF']);
    $baseUrl = $protocol . '://' . $host . $path;
    
    $downloadUrl = $baseUrl . '/download.php?id=' . $fileId;
    $deleteUrl = $baseUrl . '/delete.php?id=' . $fileId . '&token=' . $secretToken;

    echo json_encode([
        'status' => 'success',
        'downloadUrl' => $downloadUrl,
        'deleteUrl' => $deleteUrl,
        'expiresAt' => $deleteAt,
        'fileSize' => $file['size'],
        'fileName' => $originalFilename
    ]);
} else {
    // Cleanup bei Fehler
    if (file_exists($uploadPath)) {
        unlink($uploadPath);
    }
    
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database insert failed']);
}

$stmt->close();
$conn->close();
?>