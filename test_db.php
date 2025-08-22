<?php
require_once 'config.php'; // Lädt Ihre Zugangsdaten

echo "Versuche, zur Datenbank zu verbinden...<br>";

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die("<strong>Verbindung fehlgeschlagen!</strong> Fehler: " . $conn->connect_error);
} else {
    echo "<strong>Verbindung erfolgreich!</strong> Die Datenbank-Zugangsdaten stimmen.";
}

$conn->close();
?>