<?php
// cleanup.php
// Dieses Skript wird von einem Cronjob ausgeführt, um abgelaufene Dateien zu löschen.

require_once 'config.php';

// Für Cronjobs ist error_log besser als echo
function log_message($message) {
    error_log(date('[Y-m-d H:i:s]') . ' - Cleanup: ' . $message);
}

log_message("Starte Cleanup-Prozess.");
$conn = null;

try {
    $conn = get_db_connection();
    $now = date('Y-m-d H:i:s');

    // --- SICHERHEITS-FIX: Prepared Statement verwenden ---
    $stmt = $conn->prepare("SELECT id FROM files WHERE delete_at <= ?");
    $stmt->bind_param('s', $now);
    $stmt->execute();
    $result = $stmt->get_result();

    $filesToDelete = [];
    while ($row = $result->fetch_assoc()) {
        $filesToDelete[] = $row['id'];
    }
    $stmt->close();

    if (empty($filesToDelete)) {
        log_message("Keine abgelaufenen Dateien gefunden.");
        exit;
    }

    log_message("Finde " . count($filesToDelete) . " abgelaufene Dateien zur Löschung.");

    // 1. Physische Dateien löschen
    foreach ($filesToDelete as $fileId) {
        $filePath = UPLOAD_DIR . $fileId;
        if (file_exists($filePath)) {
            if (unlink($filePath)) {
                log_message("Datei erfolgreich gelöscht: {$filePath}");
            } else {
                log_message("FEHLER beim Löschen der Datei: {$filePath}");
            }
        } else {
            log_message("Datei nicht gefunden, nur DB-Eintrag wird gelöscht: {$filePath}");
        }
    }

    // 2. Alle Datenbankeinträge auf einmal löschen (effizienter)
    $placeholders = implode(',', array_fill(0, count($filesToDelete), '?'));
    $types = str_repeat('s', count($filesToDelete));
    
    $delStmt = $conn->prepare("DELETE FROM files WHERE id IN ({$placeholders})");
    $delStmt->bind_param($types, ...$filesToDelete);
    
    if ($delStmt->execute()) {
        log_message($delStmt->affected_rows . " Datenbankeinträge erfolgreich gelöscht.");
    } else {
        log_message("FEHLER beim Löschen der Datenbankeinträge: " . $delStmt->error);
    }
    $delStmt->close();

} catch (mysqli_sql_exception $e) {
    log_message('FATALER FEHLER: ' . $e->getMessage());
} finally {
    if ($conn) {
        $conn->close();
    }
    log_message("Cleanup-Prozess beendet.");
}