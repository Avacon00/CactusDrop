<?php
// cleanup.php
// Dieses Skript wird von einem Cronjob ausgeführt.

require_once 'config.php';

$conn = get_db_connection();
$now = date('Y-m-d H:i:s');

// Alle Dateien finden, die jetzt gelöscht werden sollen
$result = $conn->query("SELECT id FROM files WHERE delete_at <= '{$now}'");

if ($result->num_rows > 0) {
    echo "Finde " . $result->num_rows . " abgelaufene Dateien...\n";
    while ($row = $result->fetch_assoc()) {
        $fileId = $row['id'];
        $filePath = UPLOAD_DIR . $fileId;

        // Datei vom Server löschen
        if (file_exists($filePath)) {
            unlink($filePath);
            echo "Datei gelöscht: " . $filePath . "\n";
        }

        // Eintrag aus der Datenbank löschen
        $stmt = $conn->prepare("DELETE FROM files WHERE id = ?");
        $stmt->bind_param('s', $fileId);
        $stmt->execute();
        $stmt->close();
        echo "Datenbankeintrag gelöscht für ID: " . $fileId . "\n";
    }
} else {
    echo "Keine abgelaufenen Dateien gefunden.\n";
}

$conn->close();
echo "Cleanup beendet.";