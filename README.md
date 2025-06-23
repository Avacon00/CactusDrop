# 🌵 CactusDrop - Sicheres & Anonymes Filesharing

![CactusDrop UI](https://user-images.githubusercontent.com/12345/screenshot.png) <!-- Ersetzen Sie dies durch einen echten Screenshot-Link -->

CactusDrop ist eine selbst-gehostete Webanwendung für sicheres und anonymes Teilen von Dateien mit serverseitiger Speicherung und clientseitiger End-to-End-Verschlüsselung (E2EE).

---

## ✨ Hauptmerkmale

-   **🔒 Clientseitige E2E-Verschlüsselung:** Dateien werden direkt im Browser mit dem Web Crypto API (AES-GCM) ver- und entschlüsselt. Der Server speichert nur verschlüsselte Daten und hat nie Zugriff auf den Schlüssel.
-   **⏱️ Einstellbare Gültigkeitsdauer:** Wählen Sie vor dem Upload, wie lange ein Link gültig sein soll (1 Stunde, 24 Stunden, 7 Tage oder 30 Tage).
-   **💣 Einmal-Download:** Erstellen Sie Links, die sich nach dem ersten erfolgreichen Download selbst zerstören. Die Datei wird dabei sofort vom Server gelöscht.
-   **🔑 Optionaler Passwortschutz:** Schützen Sie den Entschlüsselungs-Schlüssel zusätzlich mit einem Passwort (PBKDF2-Ableitung).
-   **📱 QR-Code-Unterstützung:** Teilen Sie Links einfach und schnell auf mobilen Geräten durch das Scannen eines QR-Codes.
-   **🗑️ Sofortige Löschung:** Jeder Upload generiert einen einzigartigen Lösch-Link, mit dem die Datei jederzeit manuell vom Server entfernt werden kann.
-   **🌐 PWA-Unterstützung:** Installieren Sie CactusDrop als Progressive Web App auf Ihrem Desktop oder Mobilgerät für ein natives App-Gefühl.
-   **🎨 Modernes UI:** Eine saubere, responsive und benutzerfreundliche Oberfläche, die mit Tailwind CSS erstellt wurde.
-   **🖱️ Drag & Drop:** Ziehen Sie Dateien einfach in das Browserfenster, um den Upload zu starten.

---

## 🚀 Technologie-Stack

-   **Frontend:** HTML5, Vanilla JavaScript, Tailwind CSS
-   **Backend:** PHP 8+
-   **Datenbank:** MySQL / MariaDB
-   **Verschlüsselung:** Web Crypto API (AES-GCM, PBKDF2)
-   **Webserver:** Apache, Nginx oder jeder andere Server, der PHP unterstützt.

---

## 🔧 Installation

1.  **Repository herunterladen:**
    Laden Sie die Projektdateien herunter und entpacken Sie sie in das Stammverzeichnis Ihres Webservers.

2.  **Datenbank einrichten:**
    -   Erstellen Sie eine neue MySQL/MariaDB-Datenbank (z.B. `cactusdrop`).
    -   Erstellen Sie einen Datenbankbenutzer mit den entsprechenden Rechten für diese Datenbank.

3.  **Konfiguration:**
    -   Kopieren Sie die Datei `config.example.php` zu `config.php` (falls noch nicht geschehen).
    -   Öffnen Sie `config.php` und tragen Sie Ihre Datenbank-Zugangsdaten (`DB_HOST`, `DB_USER`, `DB_PASS`, `DB_NAME`) sowie die öffentliche URL Ihrer Anwendung (`APP_URL`) ein.
    -   **Empfehlung:** Nutzen Sie stattdessen Umgebungsvariablen für mehr Sicherheit.

4.  **Installer ausführen:**
    -   Rufen Sie in Ihrem Browser die Datei `install.php` auf (z.B. `https://ihre-domain.de/install.php`).
    -   Das Skript prüft die Konfiguration und erstellt die notwendige Datenbanktabelle.

5.  **🚨 WICHTIG: Installer löschen:**
    -   **Löschen Sie nach der erfolgreichen Installation unbedingt die Datei `install.php` von Ihrem Server, um Missbrauch zu verhindern!**

---

## ⚙️ Konfiguration

Alle wichtigen Einstellungen werden in der `config.php` vorgenommen. Hier können Sie unter anderem anpassen:

-   `MAX_FILE_SIZE`: Die maximal erlaubte Dateigröße in Bytes.
-   `ALLOWED_MIME_TYPES`: Ein Array von erlaubten MIME-Typen für den Upload.
-   `UPLOAD_DIR`: Das Verzeichnis, in dem die verschlüsselten Dateien gespeichert werden.

---

## 🛡️ Sicherheit

-   **End-to-End-Verschlüsselung:** Der Entschlüsselungsschlüssel wird als Hash-Fragment (`#`) an die URL angehängt und verlässt niemals den Browser des Clients. Der Server sieht ihn nie.
-   **Bereinigte Dateinamen:** Hochgeladene Dateinamen werden bereinigt, um Path-Traversal-Angriffe zu verhindern.
-   **Serverseitige Validierung:** MIME-Typ und Dateigröße werden zusätzlich auf dem Server geprüft.
-   **Sichere Tokens:** Lösch-Tokens werden mit `hash_equals()` verglichen, um Timing-Angriffe zu mitigieren.

---

## 📄 Lizenz

Dieses Projekt steht unter der MIT-Lizenz. Details finden Sie in der `LICENSE`-Datei.
