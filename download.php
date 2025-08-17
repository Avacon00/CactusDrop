<?php
// Einfache, kompatible Version der download.php
require_once 'config.php';

// Einfache Fehlerseite ohne moderne Features
function showSimpleErrorPage($title, $message) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $path = dirname($_SERVER['PHP_SELF']);
    $redirectUrl = $protocol . '://' . $host . $path . '/';
    
    echo '<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>CactusDrop - ' . htmlspecialchars($title) . '</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>body { font-family: sans-serif; }</style>
</head>
<body class="bg-gray-900 text-gray-200 flex items-center justify-center min-h-screen p-4">
    <div class="w-full max-w-md mx-auto text-center bg-gray-800 rounded-lg p-8">
        <h1 class="text-2xl font-bold text-red-400 mb-4">' . htmlspecialchars($title) . '</h1>
        <p class="text-gray-300 mb-6">' . htmlspecialchars($message) . '</p>
        <div class="mb-4">
            <div class="text-sm text-gray-400 mb-2">Weiterleitung in:</div>
            <div class="text-2xl font-bold text-green-400" id="countdown">3</div>
        </div>
        <a href="' . htmlspecialchars($redirectUrl) . '" class="bg-green-600 hover:bg-green-500 text-white font-bold py-2 px-4 rounded">
            Zur Upload-Seite
        </a>
    </div>
    <script>
    var timeLeft = 3;
    var countdownElement = document.getElementById("countdown");
    var countdown = setInterval(function() {
        timeLeft--;
        countdownElement.textContent = timeLeft;
        if (timeLeft <= 0) {
            clearInterval(countdown);
            window.location.href = "' . $redirectUrl . '";
        }
    }, 1000);
    </script>
</body>
</html>';
    exit();
}

// TEIL 1: Raw-Download (verschlüsselte Datei ausliefern)
if (isset($_GET['raw']) && $_GET['raw'] === 'true') {
    if (!isset($_GET['id']) || empty($_GET['id'])) {
        http_response_code(400);
        exit();
    }
    
    $fileId = $_GET['id'];
    
    // Einfache ID-Validierung
    if (!preg_match('/^[a-f0-9]{16}$/', $fileId)) {
        http_response_code(400);
        exit();
    }
    
    $conn = get_db_connection();
    $stmt = $conn->prepare("SELECT id, is_onetime FROM files WHERE id = ?");
    $stmt->bind_param('s', $fileId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $stmt->close();
        $conn->close();
        http_response_code(404);
        showSimpleErrorPage("Datei nicht gefunden", "Diese Datei existiert nicht oder wurde bereits heruntergeladen.");
    }
    
    $file = $result->fetch_assoc();
    $stmt->close();
    
    $filePath = UPLOAD_DIR . $file['id'];
    
    if (!file_exists($filePath)) {
        $conn->close();
        http_response_code(404);
        showSimpleErrorPage("Datei nicht gefunden", "Die angeforderte Datei wurde bereits gelöscht oder ist abgelaufen.");
    }
    
    // Sichere Pfad-Prüfung
    $realPath = realpath($filePath);
    $uploadDirReal = realpath(UPLOAD_DIR);
    if (!$realPath || !$uploadDirReal || strpos($realPath, $uploadDirReal) !== 0) {
        $conn->close();
        http_response_code(403);
        exit();
    }
    
    // Datei ausliefern
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment');
    header('Content-Length: ' . filesize($filePath));
    header('Cache-Control: no-cache');
    
    readfile($filePath);
    
    // Einmal-Download löschen
    if ($file['is_onetime']) {
        $delStmt = $conn->prepare("DELETE FROM files WHERE id = ?");
        $delStmt->bind_param('s', $fileId);
        $delStmt->execute();
        $delStmt->close();
        unlink($filePath);
    }
    
    $conn->close();
    exit();
}

// TEIL 2: Download-Seite anzeigen
if (!isset($_GET['id']) || empty($_GET['id'])) {
    showSimpleErrorPage("Fehler", "Keine Datei-ID angegeben.");
}

$fileId = $_GET['id'];

// Einfache ID-Validierung
if (!preg_match('/^[a-f0-9]{16}$/', $fileId)) {
    showSimpleErrorPage("Fehler", "Ungültige Datei-ID.");
}

$conn = get_db_connection();
$stmt = $conn->prepare("SELECT original_filename, delete_at, is_onetime FROM files WHERE id = ?");
$stmt->bind_param('s', $fileId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();
    $conn->close();
    showSimpleErrorPage("Link ungültig", "Diese Datei existiert nicht oder wurde bereits gelöscht.");
}

$file = $result->fetch_assoc();
$stmt->close();
$conn->close();

// Zeitvalidierung
$now = time();
$deleteAt = strtotime($file['delete_at']);
if ($now > $deleteAt) {
    showSimpleErrorPage("Link abgelaufen", "Die Gültigkeit dieses Links ist abgelaufen.");
}

$safeName = htmlspecialchars($file['original_filename'], ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CactusDrop - Download</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>body { font-family: sans-serif; }</style>
</head>
<body class="bg-gray-900 text-gray-200 flex items-center justify-center min-h-screen p-4">
    <div id="download-app" class="w-full max-w-md mx-auto bg-gray-800 rounded-lg p-8 text-center">
        
        <div id="main-view">
            <h1 class="text-2xl font-bold text-white mb-2">Bereit zum Download</h1>
            <p class="text-gray-400 mb-6"><?php echo $safeName; ?></p>

            <div id="password-form" class="hidden">
                <p class="text-gray-400 mb-4">Diese Datei ist passwortgeschützt.</p>
                <input type="password" id="password-input" placeholder="Passwort eingeben" 
                       class="w-full bg-gray-700 border-gray-600 rounded p-2 text-white mb-2">
                <p id="password-error" class="text-red-400 text-sm mb-4"></p>
            </div>
            
            <button id="decrypt-button" class="w-full bg-green-600 hover:bg-green-500 text-white font-bold py-3 px-4 rounded mb-3">
                Datei entschlüsseln & herunterladen
            </button>
            <p class="text-xs text-gray-500 mb-4">Die Entschlüsselung findet in Ihrem Browser statt.</p>
            
            <!-- Zurück-Button -->
            <a href="<?php echo dirname($_SERVER['PHP_SELF']); ?>/" class="w-full bg-gray-600 hover:bg-gray-500 text-white font-medium py-2 px-4 rounded inline-block transition-all">
                ← Zurück zur Upload-Seite
            </a>
        </div>

        <div id="progress-view" class="hidden">
            <p id="status-text" class="text-lg text-yellow-400 mb-4">Lade verschlüsselte Daten...</p>
            <div class="w-full bg-gray-700 rounded-full h-2">
                <div id="progress-bar" class="bg-green-500 h-2 rounded-full" style="width: 0%"></div>
            </div>
        </div>
        
        <div id="success-view" class="hidden">
            <h2 class="text-2xl font-bold text-green-400 mb-4">Erfolgreich!</h2>
            <p class="text-gray-300 mb-6">Die Datei wurde heruntergeladen.</p>
            <a href="<?php echo dirname($_SERVER['PHP_SELF']); ?>/" class="w-full bg-green-600 hover:bg-green-500 text-white font-bold py-3 px-4 rounded inline-block">
                Weitere Datei teilen
            </a>
        </div>

        <div id="error-view" class="hidden">
            <h2 class="text-xl font-bold text-red-400 mb-2">Fehler</h2>
            <p id="error-message" class="text-gray-300 mb-4">Der Link ist ungültig oder beschädigt.</p>
            <a href="<?php echo dirname($_SERVER['PHP_SELF']); ?>/" class="w-full bg-green-600 hover:bg-green-500 text-white font-bold py-3 px-4 rounded inline-block">
                Zurück zur Upload-Seite
            </a>
        </div>
    </div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var mainView = document.getElementById('main-view');
    var decryptButton = document.getElementById('decrypt-button');
    var progressView = document.getElementById('progress-view');
    var errorView = document.getElementById('error-view');
    var successView = document.getElementById('success-view');
    var passwordForm = document.getElementById('password-form');
    var passwordInput = document.getElementById('password-input');
    var passwordError = document.getElementById('password-error');
    var statusText = document.getElementById('status-text');
    var progressBar = document.getElementById('progress-bar');
    var errorMessage = document.getElementById('error-message');
    
    var keyFragment = window.location.hash.substring(1);
    var isPasswordProtected = keyFragment.indexOf('p.') === 0;
    
    if (isPasswordProtected) {
        passwordForm.classList.remove('hidden');
    }

    function base64ToBuffer(b64) {
        var binary = atob(b64);
        var buffer = new ArrayBuffer(binary.length);
        var view = new Uint8Array(buffer);
        for (var i = 0; i < binary.length; i++) {
            view[i] = binary.charCodeAt(i);
        }
        return view;
    }
    
    async function decryptData(data, key, iv) {
        return await crypto.subtle.decrypt({ name: 'AES-GCM', iv: iv }, key, data);
    }
    
    async function deriveKeyFromPassword(password, salt) {
        var encoder = new TextEncoder();
        var keyMaterial = await crypto.subtle.importKey('raw', encoder.encode(password), { name: 'PBKDF2' }, false, ['deriveKey']);
        return await crypto.subtle.deriveKey(
            { name: 'PBKDF2', salt: salt, iterations: 100000, hash: 'SHA-256' },
            keyMaterial,
            { name: 'AES-GCM', length: 256 },
            true, ['encrypt', 'decrypt']
        );
    }

    decryptButton.addEventListener('click', async function() {
        passwordError.textContent = ''; 
        mainView.classList.add('hidden');
        progressView.classList.remove('hidden');

        try {
            if (!keyFragment) throw new Error('Kein Entschlüsselungsschlüssel im Link gefunden.');
            
            statusText.textContent = 'Schlüssel wird vorbereitet...';
            var fileKey, fileIv;

            if (isPasswordProtected) {
                if (!passwordInput.value) throw new Error('Passwort erforderlich.');
                var parts = keyFragment.split('.');
                var salt = base64ToBuffer(parts[1]);
                var keyIv = base64ToBuffer(parts[2]);
                var encryptedKeyData = base64ToBuffer(parts[3]);
                fileIv = base64ToBuffer(parts[4]);
                var passwordKey = await deriveKeyFromPassword(passwordInput.value, salt);
                var decryptedKeyData = await decryptData(encryptedKeyData, passwordKey, keyIv);
                fileKey = await crypto.subtle.importKey('raw', decryptedKeyData, { name: 'AES-GCM' }, true, ['decrypt']);
            } else {
                var parts = keyFragment.split('.');
                var keyData = base64ToBuffer(parts[0]);
                fileIv = base64ToBuffer(parts[1]);
                fileKey = await crypto.subtle.importKey('raw', keyData, { name: 'AES-GCM' }, true, ['decrypt']);
            }

            statusText.textContent = 'Lade verschlüsselte Daten...';
            var fileId = <?php echo json_encode($fileId); ?>;
            var response = await fetch(window.location.pathname + '?id=' + encodeURIComponent(fileId) + '&raw=true');

            if (!response.ok) {
                if(response.status === 404) throw new Error('Datei nicht gefunden. Sie wurde möglicherweise bereits heruntergeladen.');
                throw new Error('Server konnte Datei nicht bereitstellen (Status: ' + response.status + ')');
            }
            
            var encryptedBuffer = await response.arrayBuffer();
            progressBar.style.width = '50%';
            statusText.textContent = 'Entschlüssele Datei...';
            var decryptedBuffer = await decryptData(encryptedBuffer, fileKey, fileIv);
            progressBar.style.width = '100%';
            statusText.textContent = 'Download wird gestartet...';
            
            var originalFilename = <?php echo json_encode($file['original_filename']); ?>;
            var blob = new Blob([decryptedBuffer]);
            var url = URL.createObjectURL(blob);
            var a = document.createElement('a');
            a.href = url;
            a.download = originalFilename;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);

            progressView.classList.add('hidden');
            
            <?php if ($file['is_onetime']): ?>
                successView.classList.remove('hidden');
                setTimeout(function() { window.location.href = '/'; }, 5000);
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
});
</script>
</body>
</html>