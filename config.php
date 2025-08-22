<?php
// config.php

// --- DATENBANK-EINSTELLUNGEN ---
// Tragen Sie hier Ihre Datenbank-Zugangsdaten von all-inkl.com ein
define('DB_HOST', 'localhost');
define('DB_USER', 'd04408a9');    // <-- HIER Ihre Daten eintragen
define('DB_PASS', 'nShYxzGUCeoUWJ42NSef'); // <-- HIER Ihre Daten eintragen
define('DB_NAME', 'd04408a9');     // <-- HIER Ihre Daten eintragen

// --- ANWENDUNGS-EINSTELLUNGEN ---
define('APP_URL', 'https://schuttehub.de'); // Tragen Sie hier Ihre Domain ein

// Das Verzeichnis, in dem die Dateien gespeichert werden.
define('UPLOAD_DIR', __DIR__ . '/uploads/');

// Zeitzone setzen
date_default_timezone_set('Europe/Berlin');

// Datenbankverbindung herstellen
function get_db_connection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    return $conn;
}