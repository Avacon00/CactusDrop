<?php
// delete.php

require_once 'config.php';

// Hilfsfunktion für eine konsistente UI, ähnlich zu download.php
function show_status_page($title, $message, $isError = true) {
    $redirectUrl = rtrim(APP_URL, '/') . '/';
    $titleColor = $isError ? 'text-red-400' : 'text-green-400';
    $title = htmlspecialchars($title);
    $message = htmlspecialchars($message);

    echo <<<HTML
    <!DOCTYPE html>
    <html lang="de" class="dark">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>{$title} - CactusDrop</title>
        <script src="https://cdn.tailwindcss.com"></script>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
        <style>body { font-family: "Inter", sans-serif; }</style>
    </head>
    <body class="bg-gray-900 text-gray-200 flex items-center justify-center min-h-screen p-4">
        <div class="w-full max-w-md mx-auto text-center">
            <h1 class="text-3xl font-bold mb-4 {$titleColor}">{$title}</h1>
            <p class="text-gray-300 text-lg mb-8">{$message}</p>
            <a href="{$redirectUrl}" class="bg-green-600 hover:bg-green-500 text-white font-bold py-3 px-6 rounded-lg transition-all">
                Zurück zur Startseite
            </a>
        </div>
    </body>
    </html>
HTML;
    exit;
}

// --- ID und Token validieren ---
if (empty($_GET['id']) || empty($_GET['token'])) {
    show_status_page('Fehler', 'Unvollständiger oder ungültiger Lösch-Link.', true);
}

$fileId = $_GET['id'];
$userToken = $_GET['token'];
$conn = null;

try {
    $conn = get_db_connection();

    // --- Datei-Informationen abrufen ---
    $stmt = $conn->prepare("SELECT secret_token FROM files WHERE id = ?");
    $stmt->bind_param('s', $fileId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        show_status_page('Fehler beim Löschen', 'Der Link ist ungültig oder die Datei wurde bereits gelöscht.', true);
    }

    $file = $result->fetch_assoc();
    $dbToken = $file['secret_token'];
    $stmt->close();

    // --- SICHERHEITS-FIX: Token sicher vergleichen ---
    if (hash_equals($dbToken, $userToken)) {
        // Token stimmt überein, Datei löschen
        $filePath = UPLOAD_DIR . $fileId;
        if (file_exists($filePath)) {
            unlink($filePath);
        }

        $delStmt = $conn->prepare("DELETE FROM files WHERE id = ?");
        $delStmt->bind_param('s', $fileId);
        $delStmt->execute();
        $delStmt->close();

        show_status_page('Erfolgreich gelöscht', 'Die Datei wurde erfolgreich und unwiderruflich gelöscht.', false);
    } else {
        // Token stimmt nicht überein
        show_status_page('Fehler beim Löschen', 'Der Lösch-Link ist ungültig.', true);
    }

} catch (mysqli_sql_exception $e) {
    error_log('Fehler bei delete.php: ' . $e->getMessage());
    show_status_page('Serverfehler', 'Ein unerwarteter Fehler ist aufgetreten. Die Aktion konnte nicht ausgeführt werden.', true);
} finally {
    if ($conn) {
        $conn->close();
    }
}