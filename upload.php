<?php
// upload.php

require_once 'config.php';

// --- Validierung ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_json_error('Method not allowed.', 405);
}

if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    send_json_error('File upload error or no file provided.', 400);
}

// --- Variablen verarbeiten ---
$file = $_FILES['file'];

// NEU: Dateigröße und Typ validieren
if ($file['size'] > MAX_FILE_SIZE) {
    send_json_error('Datei ist zu groß. Max. ' . (MAX_FILE_SIZE / 1024 / 1024) . ' MB erlaubt.', 413);
}

// MIME-Typ serverseitig prüfen (sicherer als der vom Client gesendete Typ)
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mimeType = $finfo->file($file['tmp_name']);

if (!in_array($mimeType, ALLOWED_MIME_TYPES, true)) {
    // Temporäre Datei löschen, da sie nicht verarbeitet wird
    unlink($file['tmp_name']);
    send_json_error('Dieser Dateityp ist nicht erlaubt.', 415);
}
$isOneTime = isset($_POST['oneTimeDownload']) && $_POST['oneTimeDownload'] === 'true';

// --- Neue Datei-IDs und Tokens generieren ---
$fileId = bin2hex(random_bytes(12)); // Erhöhte Länge für mehr Sicherheit
$secretToken = bin2hex(random_bytes(32));

// Originaldateinamen bereinigen, um Sicherheitsprobleme zu vermeiden
$originalFilename = sanitize_filename($file['name']);
$storedFilename = $fileId;
$uploadPath = UPLOAD_DIR . $storedFilename;

// --- Upload-Verzeichnis prüfen und ggf. erstellen ---
if (!is_dir(UPLOAD_DIR)) {
    if (!mkdir(UPLOAD_DIR, 0755, true)) {
        error_log('Konnte Upload-Verzeichnis nicht erstellen: ' . UPLOAD_DIR);
        send_json_error('Server-Fehler: Upload-Verzeichnis konnte nicht erstellt werden.', 500);
    }
}

// --- Datei verschieben ---
if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
    error_log('Konnte hochgeladene Datei nicht verschieben nach: ' . $uploadPath);
    send_json_error('Datei konnte nicht gespeichert werden. Prüfen Sie die Ordnerberechtigungen.', 500);
}

// --- Datenbankeintrag vorbereiten und ausführen ---
$conn = null;
try {
    $conn = get_db_connection();

    $expiry = $_POST['expiry'] ?? '24h'; // Default to 24h
    $expiryLabels = [
        '1h' => '1 Stunde',
        '24h' => '24 Stunden',
        '7d' => '7 Tage',
        '30d' => '30 Tage'
    ];
    $expiryLabel = $expiryLabels[$expiry] ?? '24 Stunden';

    // --- Gültigkeitsdauer berechnen ---
    $deleteAt = new DateTime();
    switch ($expiry) {
        case '1h':
            $deleteAt->modify('+1 hour');
            break;
        case '7d':
            $deleteAt->modify('+7 days');
            break;
        case '30d':
            $deleteAt->modify('+30 days');
            break;
        case '24h':
        default:
            $deleteAt->modify('+24 hours');
            break;
    }
    $delete_at_formatted = $deleteAt->format('Y-m-d H:i:s');

    $stmt = $conn->prepare(
        "INSERT INTO files (id, secret_token, original_filename, is_onetime, delete_at) VALUES (?, ?, ?, ?, ?)"
    );
    $stmt->bind_param('sssis', $fileId, $secretToken, $originalFilename, $isOneTime, $delete_at_formatted);
    
    $stmt->execute();

    // Erfolgsantwort senden
    $downloadUrl = rtrim(APP_URL, '/') . '/download.php?id=' . $fileId;
    $deleteUrl = rtrim(APP_URL, '/') . '/delete.php?id=' . $fileId . '&token=' . $secretToken;

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'status' => 'success',
        'downloadUrl' => $downloadUrl,
        'deleteUrl' => $deleteUrl,
        'expiresAt' => $deleteAt->format(DateTime::ATOM), // ISO-8601 format
        'expiryLabel' => $expiryLabel
    ]);

} catch (mysqli_sql_exception $e) {
    // Fehlerfall: Hochgeladene Datei wieder löschen
    if (file_exists($uploadPath)) {
        unlink($uploadPath);
    }
    error_log('Datenbankfehler bei Upload: ' . $e->getMessage());
    send_json_error('Datenbankeintrag konnte nicht erstellt werden.', 500);

} finally {
    if (isset($stmt)) {
        $stmt->close();
    }
    if ($conn) {
        $conn->close();
    }
}