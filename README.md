# 🌵 CactusDrop Free4 ALL

**Sicheres & anonymes Filesharing mit End-to-End-Verschlüsselung.**

**Sicher in der Wüste des Internets 🌵**

CactusDrop ist eine selbst-gehostete, sichere File-Sharing-Anwendung, die Dateien mit client-seitiger End-to-End-Verschlüsselung teilt. Alle Dateien werden automatisch nach 24 Stunden gelöscht.

![Version](https://img.shields.io/badge/version-0.2.8-green)
![PHP](https://img.shields.io/badge/PHP-7.4+-blue)
![Security](https://img.shields.io/badge/security-hardened-red)
![License](https://img.shields.io/badge/license-MIT-brightgreen)

> **🆕 Version 0.2.8 Update:** Umfassende Sicherheitsverbesserungen mit Input-Validierung, Rate-Limiting und CSRF-Schutz!

## ✨ Features

### 🔐 Sicherheit
- **End-to-End-Verschlüsselung** (AES-GCM 256-bit) direkt im Browser
- **Client-seitige Verschlüsselung** - Server erhält niemals unverschlüsselte Daten
- **Optionaler Passwortschutz** mit PBKDF2-Schlüsselableitung
- **Einmal-Download-Option** - Link wird nach erstem Download gelöscht
- **Automatische Löschung** nach 24 Stunden
- **🆕 Rate-Limiting** - 10 Uploads pro IP/Stunde
- **🆕 Input-Validierung** - Umfassende Datei- und Parameter-Prüfung
- **🆕 CSRF-Protection** - Token-basierter Schutz vor Cross-Site-Angriffen
- **🆕 MIME-Type-Validierung** - Whitelist-basierte Dateityp-Kontrolle
- **🆕 Path-Traversal-Schutz** - Sichere Dateipfad-Verarbeitung

### 🚀 Benutzerfreundlichkeit
- **Drag & Drop** Interface
- **Progressive Web App** (PWA) - installierbar als native App
- **QR-Code Generation** für einfaches Teilen
- **Responsive Design** - optimiert für Mobile und Desktop
- **Dark Mode** Design
- **Deutsche Lokalisierung**

### 🛠 Technisch
- **Keine Registrierung** erforderlich
- **Anonyme Uploads** ohne Tracking
- **Cronjob-basierte Bereinigung** abgelaufener Dateien
- **Minimale Serveranforderungen**

## 🏗 Systemanforderungen

- **PHP 7.4+** mit MySQLi-Erweiterung
- **MySQL/MariaDB** Datenbank
- **Webserver** (Apache/Nginx) mit mod_rewrite
- **HTTPS** empfohlen für PWA-Features

## 📦 Installation

### Option 1: Self-Extracting Installer (Empfohlen) 🆕
Der neue One-File-Installer vereinfacht die Installation erheblich:

1. **Laden Sie nur eine Datei hoch:** `installer.php`
2. **Öffnen Sie den Installer:** `https://ihre-domain.de/installer.php`
3. **Folgen Sie dem 4-Schritt-Prozess:**
   - ✅ Systemvoraussetzungen prüfen
   - 📦 Alle Dateien automatisch extrahieren  
   - 🗄 Datenbankverbindung konfigurieren
   - 🛡 Sicherheitseinstellungen anwenden
   - 🗑 Installer löscht sich selbst

### Option 2: Manuelle Installation
Laden Sie alle Projektdateien auf Ihren Webserver hoch und öffnen Sie:

```
https://ihre-domain.de/install.php
```

### 🆕 Database Update (für bestehende Installationen)
Falls Sie bereits eine ältere Version nutzen:

```bash
php update_database.php
```

Das Update-Script erweitert das Schema um:
- Rate-Limiting Tabelle
- Security-Logs
- Erweiterte Metadaten (Dateigröße, MIME-Type, Upload-IP)

### 3. Cronjob einrichten
Für die automatische Bereinigung abgelaufener Dateien:

```bash
# Täglich um 3:00 Uhr ausführen
0 3 * * * /usr/bin/php /pfad/zu/ihrer/installation/cleanup.php
```

### 4. Sicherheit
- Löschen Sie `install.php` nach der Installation
- Stellen Sie sicher, dass das `/uploads/` Verzeichnis nicht direkt zugänglich ist
- Verwenden Sie HTTPS für Produktionsumgebungen

## 🔧 Konfiguration

### config.php
```php
// Datenbank-Einstellungen
define('DB_HOST', 'localhost');
define('DB_USER', 'ihr_db_user');
define('DB_PASS', 'ihr_db_passwort');
define('DB_NAME', 'ihre_datenbank');

// Anwendungs-URL
define('APP_URL', 'https://ihre-domain.de');

// Upload-Verzeichnis
define('UPLOAD_DIR', __DIR__ . '/uploads/');
```

## 📂 Projektstruktur

```
cactusdrop/
├── index.html                    # Haupt-Anwendung (Upload-Interface)
├── upload.php                    # Upload-Handler (kompatible Version)
├── download.php                  # Download-Handler (kompatible Version)
├── delete.php                    # Datei-Löschung 
├── cleanup.php                   # Cronjob für automatische Bereinigung
├── config.php                    # Konfigurationsdatei
├── security.php                  # 🆕 Zentrales Sicherheitsmodul (optional)
├── csrf_token.php                # 🆕 CSRF-Token API (optional)
├── installer.php                 # 🆕 Self-Extracting One-File-Installer
├── update_database.php           # 🆕 Database Schema Update Script
├── test_db.php                   # Datenbankverbindungstest
├── manifest.json                 # PWA-Manifest
├── sw.js                         # Service Worker für PWA
└── uploads/                      # Verschlüsselte Dateien (vom Web verborgen)
```

## 🔒 Sicherheitsarchitektur

### Verschlüsselungsflow
1. **Upload**: Datei wird im Browser mit AES-GCM verschlüsselt
2. **Schlüssel**: Verschlüsselungsschlüssel bleibt im Browser (im URL-Fragment)
3. **Server**: Erhält nur verschlüsselte Daten
4. **Download**: Entschlüsselung erfolgt wieder im Browser
5. **Passwort**: Optional zusätzliche Verschlüsselung des Schlüssels

### Keine Server-seitige Entschlüsselung
Der Server kann niemals auf die Originaldateien zugreifen, da:
- Verschlüsselung erfolgt client-seitig
- Schlüssel werden nie an den Server übertragen
- Nur verschlüsselte Daten werden gespeichert

## 🔧 Wartung & Monitoring
```

### Logs überwachen
- Webserver-Logs für Upload/Download-Aktivitäten
- Cronjob-Logs für Cleanup-Prozess
- PHP-Fehlerprotokolle für Debug-Informationen

## 🚀 Verwendung

### Datei hochladen
1. Datei per Drag & Drop oder Klick auswählen
2. Optional: Passwortschutz aktivieren
3. Optional: Einmal-Download aktivieren
4. Upload startet automatisch mit Verschlüsselung
5. Sicheren Link teilen

### Datei herunterladen
1. Link öffnen
2. Bei Passwortschutz: Passwort eingeben
3. Download startet nach Entschlüsselung im Browser
4. Bei Einmal-Download: Link wird automatisch gelöscht

### Häufige Probleme (Legacy)
**Upload funktioniert nicht:**
- PHP file_uploads aktiviert?
- Upload-Verzeichnis beschreibbar?
- Ausreichend Speicherplatz?

**Entschlüsselung schlägt fehl:**
- Browser unterstützt Web Crypto API?
- HTTPS aktiviert? (erforderlich für PWA)
- Link vollständig (inklusive #-Fragment)?

**Automatische Löschung funktioniert nicht:**
- Cronjob korrekt eingerichtet?
- PHP-CLI verfügbar?
- Dateiberechtigungen korrekt?

## 🛡 Sicherheitsempfehlungen
### ✅ Bereits implementiert (v0.2.8)
1. **✅ Rate Limiting** - 10 Uploads pro IP/Stunde (automatisch aktiv)
2. **✅ Input-Validierung** - Umfassende Datei- und Parameter-Prüfung
3. **✅ CSRF-Protection** - Token-basierter Schutz vor Angriffen
4. **✅ MIME-Type-Validierung** - Nur erlaubte Dateitypen
5. **✅ Path-Traversal-Schutz** - Sichere Dateipfad-Verarbeitung
6. **✅ Security Headers** - X-Content-Type-Options, X-Frame-Options

### 🔧 Zusätzliche Empfehlungen
7. **HTTPS verwenden** - Essentiell für sichere Übertragung
8. **Regelmäßige Updates** - PHP und Datenbank aktuell halten
9. **Backup-Strategie** - Regelmäßige Datenbank-Backups
10. **Monitoring** - Security-Logs überwachen
11. **Firewall** - Upload-Verzeichnis vor direktem Zugriff schützen

## 📈 Performance-Tipps
- **Webserver-Caching** für statische Assets aktivieren
- **Gzip-Kompression** für bessere Ladezeiten
- **CDN** für TailwindCSS (bereits implementiert)
- **Database-Indizes** für bessere Query-Performance

## 📄 Lizenz
Dieses Projekt steht unter der MIT-Lizenz. Siehe LICENSE-Datei für Details.

### 🐛 Debug-History (Für Referenz)

## 🆕 Changelog v0.2.8
### ✅ Neu hinzugefügt
- **Self-Extracting Installer** - One-File-Installation wie bei WordPress
- **Umfassendes Security-Modul** (`security.php`) mit allen Validierungsfunktionen
- **Rate-Limiting** - Schutz vor Spam und DoS-Angriffen
- **CSRF-Protection** - Token-basierte Absicherung aller Requests
- **Input-Validierung** - Sichere Parameter- und Dateivalidierung
- **MIME-Type-Whitelist** - Nur erlaubte Dateitypen werden akzeptiert
- **Path-Traversal-Schutz** - Verhindert Directory-Traversal-Angriffe
- **Security-Headers** - Moderne Browser-Sicherheitsfeatures
- **Database Update Script** - Automatische Schema-Erweiterung
- **Security-Logs** - Monitoring verdächtiger Aktivitäten

### 🐛 Sicherheitslücken behoben
- **CVE-potentielle Upload-Schwachstellen** - Durch Whitelist-Validierung
- **XSS-Risiken** - Durch sichere Output-Encoding
- **CSRF-Angriffe** - Durch Token-Validierung
- **Path-Traversal** - Durch sichere Pfad-Validierung
- **DoS-Angriffe** - Durch Rate-Limiting

## 🆘 Support

Bei Problemen oder Fragen:
1. README und Troubleshooting-Sektion prüfen
2. Security-Logs analysieren (`security_logs` Tabelle)
3. Server-Logs analysieren
4. Issue im Repository erstellen

## 🎯 Roadmap

- [x] **Rate-Limiting** ✅ (v0.2.8)
- [x] **Input-Validierung** ✅ (v0.2.8)
- [x] **Self-Extracting Installer** ✅ (v0.2.8)
--- mehr folgt die Wochen / Monate

**CactusDrop** - Sicher in der Wüste des Internets 🌵
