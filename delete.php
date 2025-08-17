<?php
// delete.php - Sicheres Löschen von Dateien

require_once 'config.php';
require_once 'security.php';

// Security Headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

// --- Parameter validieren ---
if (!isset($_GET['id']) || !isset($_GET['token'])) {
    die("Fehler: Unvollständiger Lösch-Link.");
}

// File-ID validieren
$fileId_validation = CactusDropSecurity::validateParameter($_GET['id'], 'file_id', null, true);
if (!$fileId_validation['valid']) {
    die("Fehler: Ungültige Datei-ID.");
}

// Token validieren
$token_validation = CactusDropSecurity::validateParameter($_GET['token'], 'token', null, true);
if (!$token_validation['valid']) {
    die("Fehler: Ungültiger Lösch-Token.");
}

$fileId = $fileId_validation['value'];
$secretToken = $token_validation['value'];

$conn = get_db_connection();

// --- Nach Datei mit ID UND korrektem Token suchen ---
$stmt = $conn->prepare("SELECT id FROM files WHERE id = ? AND secret_token = ?");
$stmt->bind_param('ss', $fileId, $secretToken);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // --- Datei gefunden, jetzt sicher löschen ---
    $filePath = UPLOAD_DIR . $fileId;

    // Path Traversal verhindern
    $realPath = realpath($filePath);
    $uploadDirReal = realpath(UPLOAD_DIR);
    
    if ($realPath && $uploadDirReal && strpos($realPath, $uploadDirReal) === 0) {
        // 1. Physische Datei sicher löschen
        if (file_exists($filePath)) {
            if (!unlink($filePath)) {
                error_log('[CactusDrop] Failed to delete file: ' . $filePath);
            }
        }
    }
    
    // 2. Datenbankeintrag löschen
    $delStmt = $conn->prepare("DELETE FROM files WHERE id = ?");
    $delStmt->bind_param('s', $fileId);
    if (!$delStmt->execute()) {
        error_log('[CactusDrop] Failed to delete DB entry for file: ' . $fileId);
    }
    $delStmt->close();

    $message = "Die Datei wurde erfolgreich und unwiderruflich gelöscht.";
    $isError = false;

} else {
    $message = "Löschen fehlgeschlagen. Der Link ist ungültig oder die Datei wurde bereits gelöscht.";
    $isError = true;
}

$stmt->close();
$conn->close();

// --- Erfolgs- oder Fehlermeldung anzeigen ---
echo '
<!DOCTYPE html>
<html lang="de" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Datei gelöscht</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: "Inter", sans-serif; }</style>
</head>
<body class="bg-gray-900 text-gray-200 flex items-center justify-center min-h-screen p-4">
    <div class="w-full max-w-md mx-auto text-center">
        <h1 class="text-3xl font-bold mb-4 ' . ($isError ? 'text-red-400' : 'text-green-400') . '">
            ' . ($isError ? 'Aktion fehlgeschlagen' : 'Erfolgreich gelöscht') . '
        </h1>
        <p class="text-gray-300 text-lg mb-8">' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</p>
        <a href="' . htmlspecialchars(rtrim(APP_URL, '/'), ENT_QUOTES, 'UTF-8') . '/" class="bg-green-600 hover:bg-green-500 text-white font-bold py-3 px-6 rounded-lg transition-all">
            Zurück zur Startseite
        </a>
    </div>
</body>
</html>';

?>