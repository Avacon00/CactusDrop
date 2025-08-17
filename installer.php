<?php
/**
 * CactusDrop Self-Extracting Installer v0.2.8
 * 
 * Ein kompakter One-File-Installer für CactusDrop
 * Einfach diese Datei hochladen und aufrufen!
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

define('CACTUSDROP_VERSION', '0.2.8');
define('MIN_PHP_VERSION', '7.4.0');

$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
$errors = [];
$warnings = [];
$success = [];

// ======== EMBEDDED FILES (kompakt gespeichert) ========
function getEmbeddedFiles() {
    return [
        'index.html' => '<!DOCTYPE html>
<html lang="de" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CactusDrop - Anonymes & Sicheres Filesharing</title>
    <meta name="description" content="Sicheres & anonymes Filesharing mit End-to-End-Verschlüsselung.">
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#2f855a">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: \'Inter\', sans-serif; }
        .dragover { border-color: #2f855a; background-color: rgba(47, 133, 90, 0.1); }
        .hidden { display: none; }
        .fade-in { animation: fadeIn 0.5s ease-in-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body class="bg-gray-900 text-gray-200 flex flex-col items-center justify-center min-h-screen p-4">
    <div id="app" class="w-full max-w-md mx-auto">
        <header class="text-center mb-8">
            <div class="flex items-center justify-center gap-3 mb-2">
                <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#2f855a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 9V2m0 7v13m0-13c-2.833 0-5 2.167-5 5v0c0 2.833 2.167 5 5 5m0-10c2.833 0 5 2.167 5 5v0c0 2.833-2.167 5-5 5m-5 5h10"/>
                </svg>
                <h1 class="text-4xl font-bold text-gray-100">Cactus<span class="text-green-500">Drop</span></h1>
            </div>
            <p class="text-gray-400">Dateien mit End-to-End-Verschlüsselung teilen.</p>
        </header>
        <main id="main-content" class="w-full bg-gray-800 rounded-2xl shadow-lg p-6">
            <div id="upload-view">
                <div id="drop-zone" class="relative w-full h-48 border-2 border-dashed border-gray-600 rounded-xl flex flex-col items-center justify-center text-center cursor-pointer transition-all duration-300 hover:border-green-500 hover:bg-gray-700/50">
                    <input type="file" id="file-input" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                    <div class="space-y-2 pointer-events-none">
                        <svg class="mx-auto h-12 w-12 text-gray-500" stroke="currentColor" fill="none" viewBox="0 0 48 48"><path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4V12a4 4 0 014-4h12l4 4h12a4 4 0 014 4z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                        <p class="text-gray-400"><span class="font-semibold text-green-400">Datei hochladen</span> oder per Drag & Drop</p>
                        <p class="text-xs text-gray-500">Dateien werden im Browser verschlüsselt</p>
                    </div>
                </div>
                <div class="mt-6 space-y-4">
                    <div class="flex items-start">
                        <input id="password-protect" type="checkbox" class="h-4 w-4 text-green-600 bg-gray-700 border-gray-600 rounded mt-1">
                        <div class="ml-3">
                            <label for="password-protect" class="font-medium text-gray-300">Zusätzlicher Passwortschutz</label>
                            <p class="text-gray-500 text-sm">Verschlüsselt den E2EE-Schlüssel zusätzlich.</p>
                        </div>
                    </div>
                    <input type="password" id="password-input" placeholder="Passwort eingeben..." class="hidden w-full bg-gray-700 border-gray-600 rounded-md p-2 text-white focus:ring-2 focus:ring-green-500">
                    <div class="flex items-start">
                        <input id="one-time-download" type="checkbox" class="h-4 w-4 text-green-600 bg-gray-700 border-gray-600 rounded mt-1">
                        <div class="ml-3">
                            <label for="one-time-download" class="font-medium text-gray-300">Einmal-Download</label>
                            <p class="text-gray-500 text-sm">Der Link wird nach dem ersten Download ungültig.</p>
                        </div>
                    </div>
                </div>
            </div>
            <div id="progress-view" class="hidden text-center">
                <p class="font-semibold text-lg mb-2" id="file-name-progress">Datei wird vorbereitet...</p>
                <p class="text-sm text-gray-400 mb-4" id="file-size-progress"></p>
                <div id="encryption-status" class="text-sm text-yellow-400 mb-4">Verschlüssele Datei im Browser...</div>
                <div class="w-full bg-gray-700 rounded-full h-2.5">
                    <div id="progress-bar" class="bg-green-500 h-2.5 rounded-full transition-all duration-300" style="width: 0%"></div>
                </div>
                <p class="mt-4 text-lg font-bold text-green-400" id="progress-percentage">0%</p>
            </div>
            <div id="success-view" class="hidden text-center fade-in">
                <h2 class="text-2xl font-bold text-green-400 mb-4">Upload erfolgreich!</h2>
                <p class="text-gray-400 mb-4">Ihr sicherer E2E-verschlüsselter Link ist 24h gültig:</p>
                <div class="relative bg-gray-900 rounded-lg p-3 flex items-center mb-4">
                    <input id="download-link" type="text" readonly class="w-full bg-transparent text-gray-300 border-0 focus:ring-0 text-sm pr-12">
                    <button id="copy-button" class="absolute right-2 top-1/2 -translate-y-1/2 p-2 rounded-md bg-gray-700 hover:bg-gray-600 text-gray-300">
                        <svg id="copy-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>
                        <svg id="check-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#2f855a" stroke-width="3" class="hidden"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    </button>
                </div>
                <div class="bg-gray-900 p-4 rounded-lg mb-6 flex flex-col items-center">
                    <p class="text-sm text-gray-400 mb-3">Oder teilen Sie via QR-Code:</p>
                    <div id="qr-code" class="bg-white p-2 rounded-md"></div>
                </div>
                <div class="flex flex-col gap-4">
                    <a id="kill-link-button" href="#" target="_blank" class="w-full bg-red-800/80 hover:bg-red-700 text-white font-bold py-2 px-4 rounded-lg text-center">Link sofort löschen</a>
                    <button id="share-another-button" class="w-full bg-green-600 hover:bg-green-500 text-white font-bold py-2 px-4 rounded-lg">Weitere Datei teilen</button>
                </div>
            </div>
            <div id="error-view" class="hidden text-center fade-in bg-red-900/50 border border-red-700 p-4 rounded-lg">
                <h2 class="text-xl font-bold text-red-300 mb-2">Fehlgeschlagen</h2>
                <p id="error-message" class="text-red-300">Etwas ist schiefgelaufen. Bitte versuchen Sie es erneut.</p>
                <button id="try-again-button" class="mt-4 bg-gray-600 hover:bg-gray-500 text-white font-bold py-2 px-4 rounded-lg">Erneut versuchen</button>
            </div>
        </main>
    </div>

<script>
document.addEventListener("DOMContentLoaded", () => {
    const uploadView = document.getElementById("upload-view");
    const progressView = document.getElementById("progress-view");
    const successView = document.getElementById("success-view");
    const errorView = document.getElementById("error-view");
    const dropZone = document.getElementById("drop-zone");
    const fileInput = document.getElementById("file-input");
    const passwordProtectCheckbox = document.getElementById("password-protect");
    const passwordInput = document.getElementById("password-input");
    const oneTimeCheckbox = document.getElementById("one-time-download");
    
    passwordProtectCheckbox.addEventListener("change", () => {
        passwordInput.classList.toggle("hidden", !passwordProtectCheckbox.checked);
        if (passwordProtectCheckbox.checked) passwordInput.focus();
    });
    
    dropZone.addEventListener("dragover", e => { e.preventDefault(); dropZone.classList.add("dragover"); });
    dropZone.addEventListener("dragleave", e => { e.preventDefault(); dropZone.classList.remove("dragover"); });
    dropZone.addEventListener("drop", e => {
        e.preventDefault();
        dropZone.classList.remove("dragover");
        if (e.dataTransfer.files.length > 0) handleFile(e.dataTransfer.files[0]);
    });
    fileInput.addEventListener("change", e => {
        if (e.target.files.length > 0) handleFile(e.target.files[0]);
    });
    
    async function handleFile(file) {
        uploadView.classList.add("hidden");
        progressView.classList.remove("hidden");
        
        document.getElementById("file-name-progress").textContent = file.name;
        document.getElementById("file-size-progress").textContent = `${(file.size / 1024 / 1024).toFixed(2)} MB`;
        
        try {
            const fileBuffer = await file.arrayBuffer();
            const { key: fileKey, iv: fileIv } = await generateAesKey();
            const encryptedFileBuffer = await encryptData(fileBuffer, fileKey, fileIv);
            const encryptedBlob = new Blob([encryptedFileBuffer]);
            
            const exportedFileKey = await crypto.subtle.exportKey("raw", fileKey);
            const keyFragment = `${bufferToBase64(exportedFileKey)}.${bufferToBase64(fileIv)}`;
            
            const serverResponse = await uploadFile(encryptedBlob, file.name, oneTimeCheckbox.checked);
            const finalUrl = `${serverResponse.downloadUrl}#${keyFragment}`;
            
            showSuccessView(finalUrl, serverResponse.deleteUrl);
        } catch (err) {
            showErrorView(err.message);
        }
    }
    
    async function generateAesKey() {
        const key = await crypto.subtle.generateKey({ name: "AES-GCM", length: 256 }, true, ["encrypt", "decrypt"]);
        const iv = crypto.getRandomValues(new Uint8Array(12));
        return { key, iv };
    }
    
    async function encryptData(data, key, iv) {
        return await crypto.subtle.encrypt({ name: "AES-GCM", iv: iv }, key, data);
    }
    
    const bufferToBase64 = (buffer) => btoa(String.fromCharCode(...new Uint8Array(buffer)));
    
    function uploadFile(blob, originalFilename, isOneTime) {
        return new Promise((resolve, reject) => {
            const formData = new FormData();
            formData.append("file", blob, originalFilename);
            if (isOneTime) formData.append("oneTimeDownload", "true");
            
            const xhr = new XMLHttpRequest();
            xhr.open("POST", "upload.php", true);
            xhr.onload = () => {
                if (xhr.status === 200) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        response.status === "success" ? resolve(response) : reject(new Error(response.message));
                    } catch (e) {
                        reject(new Error("Ungültige Serverantwort."));
                    }
                } else {
                    reject(new Error(`Serverfehler: ${xhr.statusText}`));
                }
            };
            xhr.onerror = () => reject(new Error("Netzwerkfehler."));
            xhr.send(formData);
        });
    }
    
    function showSuccessView(finalUrl, deleteUrl) {
        progressView.classList.add("hidden");
        successView.classList.remove("hidden");
        document.getElementById("download-link").value = finalUrl;
        document.getElementById("kill-link-button").href = deleteUrl;
        
        const qrImg = document.createElement("img");
        qrImg.src = `https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=${encodeURIComponent(finalUrl)}`;
        document.getElementById("qr-code").appendChild(qrImg);
    }
    
    function showErrorView(message) {
        progressView.classList.add("hidden");
        errorView.classList.remove("hidden");
        document.getElementById("error-message").textContent = message;
    }
});
</script>
</body>
</html>',
        
        'manifest.json' => '{
  "name": "CactusDrop",
  "short_name": "CactusDrop",
  "description": "Sicheres & anonymes Filesharing mit End-to-End-Verschlüsselung.",
  "start_url": ".",
  "display": "standalone",
  "background_color": "#1a202c",
  "theme_color": "#2f855a",
  "icons": [
    {
      "src": "data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTkyIiBoZWlnaHQ9IjE5MiIgdmlld0JveD0iMCAwIDI0IDI0IiBmaWxsPSJub25lIiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciPjxwYXRoIGQ9Ik0xMiA5VjJtMCA3djEzbTAtMTNjLTIuODMzIDAtNSAyLjE2Ny01IDV2MGMwIDIuODMzIDIuMTY3IDUgNSA1bTAtMTBjMi44MzMgMCA1IDIuMTY3IDUgNXYwYzAgMi44MzMtMi4xNjcgNS01IDVtLTUgNWgxMCIgc3Ryb2tlPSIjMmY4NTVhIiBzdHJva2Utd2lkdGg9IjIiIHN0cm9rZS1saW5lY2FwPSJyb3VuZCIgc3Ryb2tlLWxpbmVqb2luPSJyb3VuZCIvPjwvc3ZnPg==",
      "sizes": "192x192",
      "type": "image/svg+xml"
    }
  ]
}',
        
        'sw.js' => 'const CACHE_NAME = "cactusdrop-cache-v1";
const urlsToCache = ["/", "/index.html"];

self.addEventListener("install", event => {
  event.waitUntil(
    caches.open(CACHE_NAME).then(cache => {
      return cache.addAll(urlsToCache);
    })
  );
  self.skipWaiting();
});

self.addEventListener("fetch", event => {
  if (event.request.method !== "GET") return;
  event.respondWith(
    caches.match(event.request).then(response => {
      return response || fetch(event.request);
    })
  );
});'
    ];
}

// ======== STEP HANDLERS ========

if ($step === 1) {
    performSystemCheck();
}

if ($step === 2 && $_SERVER['REQUEST_METHOD'] === 'POST') {
    extractFiles();
    if (empty($errors)) {
        header('Location: ?step=3');
        exit;
    }
}

if ($step === 3 && $_SERVER['REQUEST_METHOD'] === 'POST') {
    configureDatabaseAndInstall();
    if (empty($errors)) {
        header('Location: ?step=4');
        exit;
    }
}

if ($step === 4 && isset($_POST['finalize'])) {
    finalizeInstallation();
}

// ======== FUNCTIONS ========

function performSystemCheck() {
    global $errors, $warnings, $success;
    
    if (version_compare(PHP_VERSION, MIN_PHP_VERSION, '>=')) {
        $success[] = "✅ PHP Version " . PHP_VERSION . " ist ausreichend.";
    } else {
        $errors[] = "❌ Veraltete PHP Version! Benötigt wird " . MIN_PHP_VERSION . " oder neuer.";
    }
    
    $required_extensions = ['mysqli', 'json', 'mbstring'];
    foreach ($required_extensions as $ext) {
        if (extension_loaded($ext)) {
            $success[] = "✅ PHP-Erweiterung '{$ext}' ist verfügbar.";
        } else {
            $errors[] = "❌ PHP-Erweiterung '{$ext}' fehlt!";
        }
    }
    
    if (is_writable('.')) {
        $success[] = "✅ Hauptverzeichnis ist beschreibbar.";
    } else {
        $errors[] = "❌ Hauptverzeichnis ist nicht beschreibbar.";
    }
    
    $memory_limit = ini_get('memory_limit');
    $upload_max = ini_get('upload_max_filesize');
    $success[] = "📊 Memory Limit: {$memory_limit}, Upload Max: {$upload_max}";
}

function extractFiles() {
    global $errors, $success;
    
    try {
        $embedded_files = getEmbeddedFiles();
        
        if (!is_dir('uploads')) {
            if (!mkdir('uploads', 0755, true)) {
                throw new Exception("Konnte 'uploads' Verzeichnis nicht erstellen.");
            }
            $success[] = "📁 Upload-Verzeichnis erstellt.";
        }
        
        $htaccess_content = "# CactusDrop Security\\nDeny from all\\n<Files \"*.php\">\\nDeny from all\\n</Files>";
        if (file_put_contents('uploads/.htaccess', $htaccess_content)) {
            $success[] = "🛡 Sicherheitsdatei .htaccess erstellt.";
        }
        
        foreach ($embedded_files as $filename => $content) {
            if (file_put_contents($filename, $content)) {
                chmod($filename, 0644);
                $success[] = "📄 {$filename} extrahiert.";
            } else {
                $errors[] = "❌ Konnte {$filename} nicht schreiben.";
            }
        }
        
        // PHP Backend-Dateien erstellen
        createPHPFiles();
        
    } catch (Exception $e) {
        $errors[] = "❌ " . $e->getMessage();
    }
}

function createPHPFiles() {
    global $success, $errors;
    
    // upload.php
    $upload_php = '<?php
require_once "config.php";
header("Content-Type: application/json");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Method not allowed."]);
    exit;
}

if (!isset($_FILES["file"]) || $_FILES["file"]["error"] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "File upload error."]);
    exit;
}

$file = $_FILES["file"];
$isOneTime = isset($_POST["oneTimeDownload"]) && $_POST["oneTimeDownload"] === "true";

$fileId = bin2hex(random_bytes(8));
$secretToken = bin2hex(random_bytes(32));
$originalFilename = basename($file["name"]);
$uploadPath = UPLOAD_DIR . $fileId;

if (!move_uploaded_file($file["tmp_name"], $uploadPath)) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Could not save file."]);
    exit;
}

$conn = get_db_connection();
$deleteAt = date("Y-m-d H:i:s", strtotime("+24 hours"));

$stmt = $conn->prepare("INSERT INTO files (id, secret_token, original_filename, is_onetime, delete_at) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("sssis", $fileId, $secretToken, $originalFilename, $isOneTime, $deleteAt);

if ($stmt->execute()) {
    $downloadUrl = rtrim(APP_URL, "/") . "/download.php?id=" . $fileId;
    $deleteUrl = rtrim(APP_URL, "/") . "/delete.php?id=" . $fileId . "&token=" . $secretToken;
    
    echo json_encode([
        "status" => "success",
        "downloadUrl" => $downloadUrl,
        "deleteUrl" => $deleteUrl,
        "expiresAt" => $deleteAt
    ]);
} else {
    unlink($uploadPath);
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Database error."]);
}

$stmt->close();
$conn->close();
?>';
    
    // download.php
    $download_php = '<?php
require_once "config.php";

if (isset($_GET["raw"]) && $_GET["raw"] === "true") {
    if (!isset($_GET["id"])) { http_response_code(400); exit; }
    
    $fileId = $_GET["id"];
    $conn = get_db_connection();
    $stmt = $conn->prepare("SELECT id, is_onetime FROM files WHERE id = ?");
    $stmt->bind_param("s", $fileId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        http_response_code(404);
        exit;
    }
    
    $file = $result->fetch_assoc();
    $filePath = UPLOAD_DIR . $file["id"];
    
    if (file_exists($filePath)) {
        header("Content-Type: application/octet-stream");
        header("Content-Length: " . filesize($filePath));
        readfile($filePath);
        
        if ($file["is_onetime"]) {
            $delStmt = $conn->prepare("DELETE FROM files WHERE id = ?");
            $delStmt->bind_param("s", $fileId);
            $delStmt->execute();
            $delStmt->close();
            unlink($filePath);
        }
    } else {
        http_response_code(404);
    }
    
    $stmt->close();
    $conn->close();
    exit;
}

if (!isset($_GET["id"])) {
    die("No file ID provided.");
}

$fileId = $_GET["id"];
$conn = get_db_connection();
$stmt = $conn->prepare("SELECT original_filename, delete_at FROM files WHERE id = ?");
$stmt->bind_param("s", $fileId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("File not found or expired.");
}

$file = $result->fetch_assoc();
$stmt->close();
$conn->close();

echo "<!DOCTYPE html>
<html lang=\"de\">
<head>
    <meta charset=\"UTF-8\">
    <title>Download: " . htmlspecialchars($file["original_filename"]) . "</title>
    <script src=\"https://cdn.tailwindcss.com\"></script>
</head>
<body class=\"bg-gray-900 text-white p-8\">
    <div class=\"max-w-md mx-auto bg-gray-800 p-6 rounded-lg\">
        <h1 class=\"text-2xl font-bold mb-4\">Download bereit</h1>
        <p class=\"mb-4\">" . htmlspecialchars($file["original_filename"]) . "</p>
        <button onclick=\"downloadFile()\" class=\"w-full bg-green-600 hover:bg-green-500 text-white font-bold py-2 px-4 rounded\">
            Datei entschlüsseln & herunterladen
        </button>
        <p class=\"text-xs text-gray-400 mt-4\">Die Entschlüsselung findet in Ihrem Browser statt.</p>
    </div>
    
    <script>
    async function downloadFile() {
        try {
            const keyFragment = window.location.hash.substring(1);
            if (!keyFragment) throw new Error(\"Kein Schlüssel gefunden.\");
            
            const parts = keyFragment.split(\".\");
            const keyData = new Uint8Array(atob(parts[0]).split(\"\").map(c => c.charCodeAt(0)));
            const ivData = new Uint8Array(atob(parts[1]).split(\"\").map(c => c.charCodeAt(0)));
            
            const key = await crypto.subtle.importKey(\"raw\", keyData, { name: \"AES-GCM\" }, false, [\"decrypt\"]);
            
            const response = await fetch(\"download.php?id=$fileId&raw=true\");
            const encryptedData = await response.arrayBuffer();
            
            const decryptedData = await crypto.subtle.decrypt({ name: \"AES-GCM\", iv: ivData }, key, encryptedData);
            
            const blob = new Blob([decryptedData]);
            const url = URL.createObjectURL(blob);
            const a = document.createElement(\"a\");
            a.href = url;
            a.download = \"" . addslashes($file["original_filename"]) . "\";
            a.click();
            URL.revokeObjectURL(url);
            
        } catch (error) {
            alert(\"Entschlüsselung fehlgeschlagen: \" + error.message);
        }
    }
    </script>
</body>
</html>";
?>';
    
    // delete.php
    $delete_php = '<?php
require_once "config.php";

if (!isset($_GET["id"]) || !isset($_GET["token"])) {
    die("Invalid delete link.");
}

$fileId = $_GET["id"];
$secretToken = $_GET["token"];
$conn = get_db_connection();

$stmt = $conn->prepare("SELECT id FROM files WHERE id = ? AND secret_token = ?");
$stmt->bind_param("ss", $fileId, $secretToken);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $filePath = UPLOAD_DIR . $fileId;
    if (file_exists($filePath)) unlink($filePath);
    
    $delStmt = $conn->prepare("DELETE FROM files WHERE id = ?");
    $delStmt->bind_param("s", $fileId);
    $delStmt->execute();
    $delStmt->close();
    
    echo "Datei erfolgreich gelöscht.";
} else {
    echo "Löschlink ungültig oder Datei bereits gelöscht.";
}

$stmt->close();
$conn->close();
?>';
    
    // cleanup.php
    $cleanup_php = '<?php
require_once "config.php";

$conn = get_db_connection();
$now = date("Y-m-d H:i:s");

$result = $conn->query("SELECT id FROM files WHERE delete_at <= \\"$now\\"");

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $fileId = $row["id"];
        $filePath = UPLOAD_DIR . $fileId;
        
        if (file_exists($filePath)) unlink($filePath);
        
        $stmt = $conn->prepare("DELETE FROM files WHERE id = ?");
        $stmt->bind_param("s", $fileId);
        $stmt->execute();
        $stmt->close();
    }
    echo "Cleanup completed. Deleted " . $result->num_rows . " expired files.\\n";
} else {
    echo "No expired files found.\\n";
}

$conn->close();
?>';
    
    $files = [
        'upload.php' => $upload_php,
        'download.php' => $download_php,
        'delete.php' => $delete_php,
        'cleanup.php' => $cleanup_php
    ];
    
    foreach ($files as $filename => $content) {
        if (file_put_contents($filename, $content)) {
            chmod($filename, 0644);
            $success[] = "📄 {$filename} erstellt.";
        } else {
            $errors[] = "❌ Konnte {$filename} nicht erstellen.";
        }
    }
}

function configureDatabaseAndInstall() {
    global $errors, $success;
    
    $db_host = $_POST['db_host'] ?? 'localhost';
    $db_name = $_POST['db_name'] ?? '';
    $db_user = $_POST['db_user'] ?? '';
    $db_pass = $_POST['db_pass'] ?? '';
    
    if (empty($db_name) || empty($db_user)) {
        $errors[] = "❌ Datenbank-Name und Benutzer sind erforderlich.";
        return;
    }
    
    try {
        $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
        if ($conn->connect_error) {
            throw new Exception("Datenbankverbindung fehlgeschlagen: " . $conn->connect_error);
        }
        $success[] = "✅ Datenbankverbindung erfolgreich.";
        
        $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        $path = dirname($_SERVER['PHP_SELF']);
        $app_url = rtrim("{$protocol}://{$host}{$path}", '/');
        
        $config_content = createConfigFile($db_host, $db_user, $db_pass, $db_name, $app_url);
        if (!file_put_contents('config.php', $config_content)) {
            throw new Exception("Konnte config.php nicht erstellen.");
        }
        $success[] = "✅ config.php erstellt.";
        
        $sql_table = "CREATE TABLE IF NOT EXISTS `files` (
          `id` varchar(16) NOT NULL,
          `secret_token` varchar(64) NOT NULL,
          `original_filename` varchar(255) NOT NULL,
          `password_hash` varchar(255) DEFAULT NULL,
          `is_onetime` tinyint(1) NOT NULL DEFAULT 0,
          `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
          `delete_at` timestamp NOT NULL,
          PRIMARY KEY (`id`),
          KEY `idx_delete_at` (`delete_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        
        if (!$conn->query($sql_table)) {
            throw new Exception("Datenbanktabelle konnte nicht erstellt werden: " . $conn->error);
        }
        $success[] = "✅ Datenbanktabelle erstellt.";
        
        $conn->close();
        
    } catch (Exception $e) {
        $errors[] = "❌ " . $e->getMessage();
    }
}

function createConfigFile($db_host, $db_user, $db_pass, $db_name, $app_url) {
    return "<?php
// config.php - CactusDrop v" . CACTUSDROP_VERSION . "

define('DB_HOST', '{$db_host}');
define('DB_USER', '{$db_user}');
define('DB_PASS', '{$db_pass}');
define('DB_NAME', '{$db_name}');

define('APP_URL', '{$app_url}');
define('UPLOAD_DIR', __DIR__ . '/uploads/');

date_default_timezone_set('Europe/Berlin');

function get_db_connection() {
    \$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if (\$conn->connect_error) {
        die('Datenbankverbindung fehlgeschlagen: ' . \$conn->connect_error);
    }
    return \$conn;
}
?>";
}

function finalizeInstallation() {
    global $success, $errors;
    
    try {
        $htaccess_root = "# CactusDrop Security
RewriteEngine On
Header always set X-Content-Type-Options nosniff
Header always set X-Frame-Options DENY

<Files \"config.php\">
    Deny from all
</Files>
<Files \"cleanup.php\">
    Deny from all
</Files>";
        
        if (file_put_contents('.htaccess', $htaccess_root)) {
            $success[] = "✅ Root .htaccess erstellt.";
        }
        
        if (@unlink(__FILE__)) {
            $success[] = "✅ Installer gelöscht.";
            echo "<script>setTimeout(() => window.location.href = './index.html', 3000);</script>";
        } else {
            $errors[] = "⚠️ Installer konnte nicht automatisch gelöscht werden. Bitte manuell löschen!";
        }
        
    } catch (Exception $e) {
        $errors[] = "❌ " . $e->getMessage();
    }
}

?>
<!DOCTYPE html>
<html lang="de" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CactusDrop Self-Extracting Installer v<?php echo CACTUSDROP_VERSION; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-gray-900 text-gray-200 flex items-center justify-center min-h-screen p-4">
    <div class="w-full max-w-2xl mx-auto bg-gray-800 rounded-2xl shadow-lg p-8">
        
        <header class="text-center mb-8">
            <div class="flex items-center justify-center gap-3 mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#2f855a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 9V2m0 7v13m0-13c-2.833 0-5 2.167-5 5v0c0 2.833 2.167 5 5 5m0-10c2.833 0 5 2.167 5 5v0c0 2.833-2.167 5-5 5m-5 5h10"/>
                </svg>
                <h1 class="text-4xl font-bold text-gray-100">Cactus<span class="text-green-500">Drop</span></h1>
            </div>
            <p class="text-gray-400">Self-Extracting Installer v<?php echo CACTUSDROP_VERSION; ?></p>
            <div class="w-full bg-gray-700 rounded-full h-2.5 mt-4">
                <div class="bg-green-500 h-2.5 rounded-full transition-all duration-500" style="width: <?php echo ($step / 4 * 100); ?>%"></div>
            </div>
        </header>

        <main>
            <?php
            if (!empty($errors)) {
                echo '<div class="bg-red-900/50 border border-red-700 text-red-300 px-4 py-3 rounded-lg mb-6">';
                echo '<h3 class="font-bold mb-2">Fehler:</h3>';
                foreach ($errors as $error) { echo "<p>{$error}</p>"; }
                echo '</div>';
            }
            
            if (!empty($warnings)) {
                echo '<div class="bg-yellow-900/50 border border-yellow-700 text-yellow-300 px-4 py-3 rounded-lg mb-6">';
                echo '<h3 class="font-bold mb-2">Warnungen:</h3>';
                foreach ($warnings as $warning) { echo "<p>{$warning}</p>"; }
                echo '</div>';
            }
            
            if (!empty($success)) {
                echo '<div class="bg-green-900/50 border border-green-700 text-green-300 px-4 py-3 rounded-lg mb-6">';
                foreach ($success as $s) { echo "<p>{$s}</p>"; }
                echo '</div>';
            }
            ?>

            <?php if ($step === 1): ?>
                <h2 class="text-2xl font-bold text-center mb-4 text-green-400">🌵 CactusDrop Self-Extracting Installer</h2>
                <p class="text-center text-gray-400 mb-6">
                    Dieser One-File-Installer enthält das komplette CactusDrop-System und installiert es automatisch.
                </p>
                
                <div class="bg-gray-900 p-4 rounded-lg mb-6">
                    <h3 class="font-semibold mb-3 text-green-400">System-Check:</h3>
                    <div class="space-y-1 text-sm">
                        <?php foreach ($success as $s): ?>
                            <div><?php echo $s; ?></div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <?php if (empty($errors)): ?>
                    <form method="POST" action="?step=2">
                        <button type="submit" class="w-full bg-green-600 hover:bg-green-500 text-white font-bold py-3 px-4 rounded-lg transition-all">
                            🚀 Installation starten
                        </button>
                    </form>
                <?php else: ?>
                    <p class="text-center text-red-400">Bitte beheben Sie die Probleme vor dem Fortfahren.</p>
                <?php endif; ?>

            <?php elseif ($step === 2): ?>
                <h2 class="text-2xl font-bold text-center mb-4 text-green-400">📦 Dateien extrahieren</h2>
                <p class="text-center text-gray-400 mb-6">CactusDrop wird jetzt extrahiert und eingerichtet.</p>

            <?php elseif ($step === 3): ?>
                <h2 class="text-2xl font-bold text-center mb-4 text-green-400">🛠 Datenbank konfigurieren</h2>
                <p class="text-center text-gray-400 mb-6">Geben Sie Ihre MySQL/MariaDB-Zugangsdaten ein.</p>
                
                <form method="POST" action="?step=3" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-1">Datenbank-Host</label>
                            <input type="text" name="db_host" value="localhost" required 
                                   class="w-full bg-gray-700 border-gray-600 rounded-md p-3 text-white focus:ring-2 focus:ring-green-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-1">Datenbank-Name *</label>
                            <input type="text" name="db_name" required 
                                   class="w-full bg-gray-700 border-gray-600 rounded-md p-3 text-white focus:ring-2 focus:ring-green-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-1">Datenbank-Benutzer *</label>
                            <input type="text" name="db_user" required 
                                   class="w-full bg-gray-700 border-gray-600 rounded-md p-3 text-white focus:ring-2 focus:ring-green-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-1">Datenbank-Passwort</label>
                            <input type="password" name="db_pass" 
                                   class="w-full bg-gray-700 border-gray-600 rounded-md p-3 text-white focus:ring-2 focus:ring-green-500">
                        </div>
                    </div>
                    
                    <div class="bg-blue-900/50 border border-blue-700 text-blue-300 px-4 py-3 rounded-lg">
                        <p><strong>APP_URL wird automatisch erkannt:</strong> <?php 
                            $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
                            echo rtrim("{$protocol}://{$_SERVER['HTTP_HOST']}" . dirname($_SERVER['PHP_SELF']), '/');
                        ?></p>
                    </div>
                    
                    <button type="submit" class="w-full bg-green-600 hover:bg-green-500 text-white font-bold py-3 px-4 rounded-lg transition-all">
                        ⚙️ Installation abschließen
                    </button>
                </form>

            <?php elseif ($step === 4): ?>
                <h2 class="text-2xl font-bold text-center mb-4 text-green-400">🎉 Installation abgeschlossen!</h2>
                <p class="text-center text-gray-400 mb-6">CactusDrop wurde erfolgreich installiert!</p>
                
                <div class="bg-yellow-900/50 border border-yellow-700 text-yellow-300 px-4 py-3 rounded-lg mb-6">
                    <h3 class="font-bold mb-2">📋 Nächste Schritte:</h3>
                    <ol class="list-decimal list-inside space-y-1">
                        <li>Installer löschen (Sicherheit)</li>
                        <li>Cronjob für cleanup.php einrichten</li>
                        <li>HTTPS aktivieren (für PWA)</li>
                    </ol>
                </div>

                <form method="POST" action="?step=4" class="space-y-4">
                    <input type="hidden" name="finalize" value="1">
                    <button type="submit" class="w-full bg-red-700 hover:bg-red-600 text-white font-bold py-3 px-4 rounded-lg transition-all">
                        🗑 Installer löschen & zu CactusDrop
                    </button>
                </form>
                
                <div class="text-center mt-4">
                    <a href="./index.html" class="text-green-400 hover:text-green-300 underline">
                        Manuell zu CactusDrop →
                    </a>
                </div>
            <?php endif; ?>
        </main>

        <footer class="text-center mt-8 text-xs text-gray-500">
            <p>CactusDrop Self-Extracting Installer v<?php echo CACTUSDROP_VERSION; ?></p>
        </footer>
    </div>
</body>
</html>