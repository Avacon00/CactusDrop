<?php
require_once 'config.php'; // Stellt send_json_error und get_db_connection bereit

// Eine Hilfsfunktion, um eine stilvolle Fehler- oder Informationsseite anzuzeigen und das Skript zu beenden.
function showInfoPage($title, $message, $isError = true) {
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
        <meta http-equiv="refresh" content="5;url={$redirectUrl}">
        <script src="https://cdn.tailwindcss.com"></script>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
        <style>body { font-family: "Inter", sans-serif; }</style>
    </head>
    <body class="bg-gray-900 text-gray-200 flex items-center justify-center min-h-screen p-4">
        <div class="w-full max-w-md mx-auto text-center">
            <h1 class="text-3xl font-bold mb-4 {$titleColor}">{$title}</h1>
            <p class="text-gray-300 text-lg mb-8">{$message} Sie werden in 5 Sekunden zur Startseite weitergeleitet.</p>
            <a href="{$redirectUrl}" class="bg-green-600 hover:bg-green-500 text-white font-bold py-3 px-6 rounded-lg transition-all">
                Sofort zur Startseite
            </a>
        </div>
    </body>
    </html>
HTML;
    exit;
}

// TEIL 1: Auslieferung der rohen, verschlüsselten Datei (API-Endpunkt für das Frontend)
if (isset($_GET['raw']) && $_GET['raw'] === 'true') {
    if (!isset($_GET['id'])) {
        send_json_error('Keine Datei-ID angegeben.', 400);
    }
    $fileId = $_GET['id'];
    $conn = null;

    try {
        $conn = get_db_connection();
        $stmt = $conn->prepare("SELECT id, is_onetime, delete_at FROM files WHERE id = ?");
        $stmt->bind_param('s', $fileId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            send_json_error('Datei nicht gefunden oder bereits gelöscht.', 404);
        }
        $file = $result->fetch_assoc();
        $stmt->close();

        // Sicherheitsprüfung: Ist der Link bereits abgelaufen?
        if (new DateTime() > new DateTime($file['delete_at'])) {
             send_json_error('Dieser Link ist abgelaufen.', 410); // 410 Gone
        }

        $filePath = UPLOAD_DIR . $file['id'];
        if (!file_exists($filePath)) {
            send_json_error('Datei auf dem Server nicht gefunden.', 404);
        }

        // Datei ausliefern
        header('Content-Type: application/octet-stream');
        header('Content-Length: ' . filesize($filePath));
        header('Content-Disposition: attachment; filename="data.bin"'); // Generischer Dateiname
        
        // Wichtig: Sorgt dafür, dass das Skript weiterläuft, auch wenn der User die Verbindung trennt.
        ignore_user_abort(true);
        
        // Datei an den Client senden
        readfile($filePath);

        // NEU: Bei Einmal-Download wird die Datei nach dem Senden sofort gelöscht.
        if ($file['is_onetime']) {
            // Physische Datei löschen
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            // Datenbankeintrag löschen
            $delStmt = $conn->prepare("DELETE FROM files WHERE id = ?");
            $delStmt->bind_param('s', $fileId);
            $delStmt->execute();
            $delStmt->close();
        }

    } catch (mysqli_sql_exception $e) {
        error_log('Fehler bei Raw-Download: ' . $e->getMessage());
        send_json_error('Datenbankfehler beim Dateizugriff.', 500);
    } finally {
        if ($conn) $conn->close();
    }
    exit;
}

// TEIL 2: Auslieferung der HTML-Entschlüsselungsseite für den Benutzer
if (!isset($_GET['id']) || empty($_GET['id'])) {
    showInfoPage("Fehler", "Keine Datei-ID angegeben.");
}
$fileId = $_GET['id'];
$file = null;

try {
    $conn = get_db_connection();
    $stmt = $conn->prepare("SELECT original_filename, delete_at, is_onetime FROM files WHERE id = ?");
    $stmt->bind_param('s', $fileId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        showInfoPage("Link ungültig", "Diese Datei existiert nicht oder wurde bereits gelöscht.");
    }
    $file = $result->fetch_assoc();
    $stmt->close();
    $conn->close();

    if (new DateTime() > new DateTime($file['delete_at'])) {
        showInfoPage("Link abgelaufen", "Die Gültigkeit dieses Links ist abgelaufen.");
    }
} catch (mysqli_sql_exception $e) {
    error_log('Fehler bei Download-Seite: ' . $e->getMessage());
    showInfoPage("Serverfehler", "Ein Fehler ist aufgetreten. Bitte versuchen Sie es später erneut.");
}

?>
<!DOCTYPE html>
<html lang="de" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Datei entschlüsseln & herunterladen</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: "Inter", sans-serif; }</style>
</head>
<body class="bg-gray-900 text-gray-200 flex items-center justify-center min-h-screen p-4">
    <div id="download-app" class="w-full max-w-md mx-auto bg-gray-800 rounded-2xl shadow-lg p-8 text-center">
        
        <div id="main-view">
            <h1 class="text-2xl font-bold text-white mb-2">Bereit zum Download</h1>
            <p id="filename-display" class="text-gray-400 mb-6 break-words"><?php echo htmlspecialchars($file['original_filename']); ?></p>

            <div id="password-form" class="hidden">
                 <p class="text-center text-gray-400 mb-4">Diese Datei ist zusätzlich passwortgeschützt.</p>
                <input type="password" id="password-input" placeholder="Passwort eingeben" class="w-full bg-gray-700 border-gray-600 rounded-md p-3 text-white focus:ring-2 focus:ring-green-500 focus:border-green-500 mb-2">
                <p id="password-error" class="text-red-400 text-sm mb-4 h-5"></p>
            </div>
            <button id="decrypt-button" class="w-full bg-green-600 hover:bg-green-500 text-white font-bold py-3 px-4 rounded-lg transition-all">
                Datei entschlüsseln & herunterladen
            </button>
            <p class="text-xs text-gray-500 mt-4">Die Entschlüsselung findet sicher in Ihrem Browser statt.</p>
        </div>

        <div id="progress-view" class="hidden">
             <p id="status-text" class="text-lg text-yellow-400 mb-4">Lade verschlüsselte Daten...</p>
             <div class="w-full bg-gray-700 rounded-full h-2.5">
                <div id="progress-bar" class="bg-green-500 h-2.5 rounded-full transition-all duration-300" style="width: 0%"></div>
            </div>
        </div>
        
        <div id="success-done-view" class="hidden">
            <h2 class="text-2xl font-bold text-green-400 mb-4">Erfolgreich!</h2>
            <p class="text-gray-300 mb-6">Die Datei wurde heruntergeladen und der Link wurde unwiderruflich gelöscht.</p>
            <a href="<?php echo rtrim(APP_URL, '/'); ?>/" class="w-full bg-green-600 hover:bg-green-500 text-white font-bold py-3 px-4 rounded-lg transition-all mb-4 inline-block">
                Weitere Datei teilen
            </a>
            <p class="text-xs text-gray-500">Sie werden in 5 Sekunden weitergeleitet...</p>
        </div>

        <div id="error-view" class="hidden">
            <h2 class="text-xl font-bold text-red-400 mb-2">Fehler</h2>
            <p id="error-message" class="text-gray-300">Der Link scheint ungültig oder beschädigt zu sein.</p>
             <a href="<?php echo rtrim(APP_URL, '/'); ?>/" class="mt-6 w-full bg-green-600 hover:bg-green-500 text-white font-bold py-3 px-4 rounded-lg transition-all mb-4 inline-block">
                Zurück zur Startseite
            </a>
        </div>
    </div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const mainView = document.getElementById('main-view');
    const decryptButton = document.getElementById('decrypt-button');
    const progressView = document.getElementById('progress-view');
    const errorView = document.getElementById('error-view');
    const successDoneView = document.getElementById('success-done-view');
    const passwordForm = document.getElementById('password-form');
    const passwordInput = document.getElementById('password-input');
    const passwordError = document.getElementById('password-error');
    const statusText = document.getElementById('status-text');
    const progressBar = document.getElementById('progress-bar');
    const errorMessage = document.getElementById('error-message');
    
    const keyFragment = window.location.hash.substring(1);
    const isPasswordProtected = keyFragment.startsWith('p.');
    
    if (isPasswordProtected) {
        passwordForm.classList.remove('hidden');
    }

    const base64ToBuffer = (b64) => Uint8Array.from(atob(b64), c => c.charCodeAt(0));
    
    async function decryptData(data, key, iv) {
        return await crypto.subtle.decrypt({ name: 'AES-GCM', iv: iv }, key, data);
    }
    
    async function deriveKeyFromPassword(password, salt) {
        const encoder = new TextEncoder();
        const keyMaterial = await crypto.subtle.importKey('raw', encoder.encode(password), { name: 'PBKDF2' }, false, ['deriveKey']);
        return await crypto.subtle.deriveKey(
            { name: 'PBKDF2', salt: salt, iterations: 100000, hash: 'SHA-256' },
            keyMaterial,
            { name: 'AES-GCM', length: 256 },
            true, ['encrypt', 'decrypt']
        );
    }

    decryptButton.addEventListener('click', async () => {
        passwordError.textContent = ''; 
        mainView.classList.add('hidden');
        progressView.classList.remove('hidden');

        try {
            if (!keyFragment) throw new Error('Kein Entschlüsselungsschlüssel im Link gefunden.');
            
            statusText.textContent = 'Schlüssel wird vorbereitet...';
            let fileKey, fileIv;

            if (isPasswordProtected) {
                if (!passwordInput.value) throw new Error('Passwort erforderlich.');
                const parts = keyFragment.split('.');
                const salt = base64ToBuffer(parts[1]);
                const keyIv = base64ToBuffer(parts[2]);
                const encryptedKeyData = base64ToBuffer(parts[3]);
                fileIv = base64ToBuffer(parts[4]);
                const passwordKey = await deriveKeyFromPassword(passwordInput.value, salt);
                const decryptedKeyData = await decryptData(encryptedKeyData, passwordKey, keyIv);
                fileKey = await crypto.subtle.importKey('raw', decryptedKeyData, { name: 'AES-GCM' }, true, ['decrypt']);
            } else {
                const parts = keyFragment.split('.');
                const keyData = base64ToBuffer(parts[0]);
                fileIv = base64ToBuffer(parts[1]);
                fileKey = await crypto.subtle.importKey('raw', keyData, { name: 'AES-GCM' }, true, ['decrypt']);
            }

            statusText.textContent = 'Lade verschlüsselte Daten...';
            const fileId = '<?php echo $fileId; ?>';
            const response = await fetch(`download.php?id=${fileId}&raw=true`);

            if (!response.ok) {
                 if(response.status === 404) throw new Error(`Datei nicht gefunden. Sie wurde möglicherweise bereits heruntergeladen.`);
                 throw new Error(`Server konnte Datei nicht bereitstellen (Status: ${response.status})`);
            }
            
            const encryptedBuffer = await response.arrayBuffer();
            progressBar.style.width = '50%';
            statusText.textContent = 'Entschlüssele Datei in Ihrem Browser...';
            const decryptedBuffer = await decryptData(encryptedBuffer, fileKey, fileIv);
            progressBar.style.width = '100%';
            statusText.textContent = 'Download wird gestartet...';
                        const originalFilename = <?php echo json_encode($file['original_filename']); ?>;
            triggerDownload(decryptedBuffer, originalFilename);

            progressView.classList.add('hidden');
            
            <?php if ($file['is_onetime']): ?>
                mainView.classList.add('hidden');
                successDoneView.classList.remove('hidden');
                setTimeout(() => { window.location.href = '<?php echo rtrim(APP_URL, '/'); ?>/'; }, 5000);
            <?php else: ?>
                mainView.classList.remove('hidden');
            <?php endif; ?>

        } catch (err) {
            console.error('Decryption failed:', err);
            progressView.classList.add('hidden');
            mainView.classList.remove('hidden'); 
            passwordError.textContent = err.message || "Falsches Passwort oder beschädigter Link.";
        }
    });

    function triggerDownload(decryptedBuffer, filename) {
        const blob = new Blob([decryptedBuffer]);
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    }
});
</script>
</body>
</html>
