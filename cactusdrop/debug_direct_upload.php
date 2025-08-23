<?php
// Direkter Upload-Test ohne Frontend
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Upload-Debug Test</h2>";

// Teste config.php
echo "<h3>1. Config Test</h3>";
try {
    require_once 'config.php';
    echo "✓ config.php geladen<br>";
    
    $conn = get_db_connection();
    echo "✓ DB-Verbindung erfolgreich<br>";
    
    // Teste Tabellen-Schema
    $result = $conn->query("SHOW COLUMNS FROM files");
    $columns = [];
    while ($row = $result->fetch_assoc()) {
        $columns[] = $row['Field'];
    }
    echo "✓ Tabellen-Spalten: " . implode(', ', $columns) . "<br>";
    
    $conn->close();
    
} catch (Exception $e) {
    echo "❌ Config-Fehler: " . $e->getMessage() . "<br>";
}

// Teste privacy.php
echo "<h3>2. Privacy Test</h3>";
try {
    if (file_exists('privacy.php')) {
        require_once 'privacy.php';
        echo "✓ privacy.php geladen<br>";
        
        if (class_exists('PrivacyManager')) {
            echo "✓ PrivacyManager verfügbar<br>";
        }
        
        if (class_exists('GDPRAdminSecurity')) {
            echo "✓ GDPRAdminSecurity verfügbar<br>";
        }
    } else {
        echo "⚠️ privacy.php nicht gefunden<br>";
    }
} catch (Exception $e) {
    echo "❌ Privacy-Fehler: " . $e->getMessage() . "<br>";
}

// Teste Upload-Verzeichnis
echo "<h3>3. Upload-Dir Test</h3>";
$uploadDir = defined('UPLOAD_DIR') ? UPLOAD_DIR : './uploads/';
echo "Upload-Dir: " . $uploadDir . "<br>";

if (!is_dir($uploadDir)) {
    echo "⚠️ Upload-Dir existiert nicht, versuche zu erstellen...<br>";
    if (mkdir($uploadDir, 0755, true)) {
        echo "✓ Upload-Dir erstellt<br>";
    } else {
        echo "❌ Konnte Upload-Dir nicht erstellen<br>";
    }
} else {
    echo "✓ Upload-Dir existiert<br>";
}

if (is_writable($uploadDir)) {
    echo "✓ Upload-Dir ist beschreibbar<br>";
} else {
    echo "❌ Upload-Dir ist NICHT beschreibbar<br>";
}

// Teste Upload-Script direkt
echo "<h3>4. Upload-Script Test</h3>";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['test_file'])) {
    echo "<h4>Processing Upload...</h4>";
    
    // Backup globale Vars
    $backup_FILES = $_FILES;
    $backup_POST = $_POST;
    
    // Simuliere kleinen Upload
    $_FILES['file'] = $_FILES['test_file'];
    $_POST['expiry_hours'] = '24';
    
    // Capture Upload-Output
    ob_start();
    include 'upload.php';
    $output = ob_get_clean();
    
    echo "<h4>Upload-Ausgabe:</h4>";
    echo "<pre>" . htmlspecialchars($output) . "</pre>";
    
    // Restore
    $_FILES = $backup_FILES;
    $_POST = $backup_POST;
    
} else {
    echo '<form method="POST" enctype="multipart/form-data">
        <p>Test-Datei hochladen:</p>
        <input type="file" name="test_file" required>
        <button type="submit">Upload testen</button>
    </form>';
}

echo "<h3>5. PHP Info</h3>";
echo "PHP Version: " . PHP_VERSION . "<br>";
echo "upload_max_filesize: " . ini_get('upload_max_filesize') . "<br>";
echo "post_max_size: " . ini_get('post_max_size') . "<br>";
echo "max_execution_time: " . ini_get('max_execution_time') . "<br>";
?>