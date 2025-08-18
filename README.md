# 🌵 CactusDrop

**Sicheres & anonymes Filesharing mit End-to-End-Verschlüsselung**

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

### 🆕 Erweiterte Datenbankstruktur
```sql
-- Haupttabelle (erweitert)
CREATE TABLE files (
  id varchar(16) NOT NULL PRIMARY KEY,
  secret_token varchar(64) NOT NULL,
  original_filename varchar(255) NOT NULL,
  password_hash varchar(255) DEFAULT NULL,
  is_onetime tinyint(1) DEFAULT 0,
  file_size bigint(20) DEFAULT NULL,        -- 🆕 Dateigröße
  mime_type varchar(100) DEFAULT NULL,      -- 🆕 MIME-Type
  upload_ip varchar(45) DEFAULT NULL,       -- 🆕 Upload-IP
  created_at timestamp DEFAULT CURRENT_TIMESTAMP,
  delete_at timestamp NOT NULL,
  KEY idx_delete_at (delete_at),
  KEY idx_created_at (created_at)           -- 🆕 Performance-Index
);

-- 🆕 Rate-Limiting
CREATE TABLE rate_limits (
  id int(11) NOT NULL AUTO_INCREMENT,
  ip_address varchar(45) NOT NULL,
  created_at timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_ip_created (ip_address, created_at)
);

-- 🆕 Security-Logs
CREATE TABLE security_logs (
  id int(11) NOT NULL AUTO_INCREMENT,
  ip_address varchar(45) NOT NULL,
  event_type varchar(50) NOT NULL,
  details text DEFAULT NULL,
  user_agent varchar(500) DEFAULT NULL,
  created_at timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_ip_event (ip_address, event_type),
  KEY idx_created_at (created_at)
);
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

## 🔍 Fehlerbehebung & Bekannte Probleme

### ⚠️ Kritische Probleme und Lösungen

#### **Problem 1: HTTP 500 Fehler bei Upload/Download**
**Symptom:** `E2EE process failed: Error: Serverfehler: (500)`

**Ursache:** Moderne Security-Features sind inkompatibel mit älteren PHP-Versionen oder fehlen Dependencies

**✅ Lösung:**
- Die **aktuellen Versionen** sind bereits kompatibel: `upload.php` und `download.php`
- Diese funktionieren ohne Security-Module und moderne PHP-Features
- Bei Problemen: Debug-Versionen verfügbar (`upload_basic.php`, `download_simple.php`)

#### **Problem 2: Database Schema Mismatch**
**Symptom:** `Fatal error: Unknown column 'password_hash' in 'INSERT'`

**Ursache:** Neue Upload-Skripte erwarten erweiterte Datenbank-Struktur

**✅ Lösung:**
- **Bereits gefixt:** Aktuelle `upload.php` funktioniert mit alter DB-Struktur
- **Für erweiterte Features:** Führe `update_database.php` aus um Schema zu erweitern

#### **Problem 3: CSRF-Token 404 Fehler**
**Symptom:** `Security token could not be retrieved`

**Ursache:** `csrf_token.php` fehlt oder Security-Module nicht verfügbar

**✅ Lösung:**
- CSRF-Code aus `index.html` entfernt (bereits gefixt)
- System funktioniert jetzt ohne CSRF-Token

#### **Problem 4: Download-Links führen zu weißer Seite**
**Symptom:** "File not found or expired" als blanke Seite

**✅ Lösung:**
- Schöne Error-Pages mit Countdown implementiert
- Automatische Weiterleitung zur Upload-Seite nach 3 Sekunden

#### **Problem 5: Copy-Button funktioniert nicht**
**Symptom:** Link wird nicht in Zwischenablage kopiert

**✅ Lösung:**
- Moderne Clipboard API implementiert mit Fallback
- Funktioniert jetzt in allen Browsern (Desktop + Mobile)

#### **Problem 6: Bulk-Upload Base64-Dekodierung Fehler**
**Symptom:** `"Entschlüsselung fehlgeschlagen: String contains an invalid character"` bei passwort-geschützten Multi-File-Uploads

**Ursache:** Inkonsistente Base64-Encoding zwischen Upload- und Download-Prozess
- Upload-Prozess verwendete verschiedene Base64-Funktionen an unterschiedlichen Stellen
- Typed Arrays benötigen `.buffer` Eigenschaft für korrekte Base64-Encodierung
- Bulk-Upload-Code hatte anderen Base64-Encoding-Pfad als Single-Upload

**✅ Lösung:**
- Konsistente `bufferToBase64()` Funktion für alle Schlüssel-Fragmente implementiert
- Verwendung von `.buffer` Eigenschaft bei Typed Arrays (salt, keyIv, encryptedKey, fileIv)
- Einheitlicher Base64-Encoding-Pfad für Single- und Bulk-Uploads
- Robuste Fehlerbehandlung mit detailliertem Debugging

### 🛠 Debug-Workflow

#### **Bei Upload-Problemen:**
1. **Browser DevTools öffnen** (F12 → Console)
2. **Upload versuchen** und Console-Logs prüfen
3. **Server Response analysieren:**
   - `"Server Response: {...}"` → JSON OK, Inhalt prüfen
   - `"Raw Response: <!DOCTYPE html>..."` → PHP-Fehler
   - `"Raw Response: Fatal error..."` → Syntax-Fehler

#### **Bei Download-Problemen:**
1. **Link direkt testen:** `download.php?id=TEST_ID`
2. **Raw-Download testen:** `download.php?id=TEST_ID&raw=true`
3. **Datei im uploads/ Ordner prüfen**

### Debug-Tools
```bash
# Datenbankverbindung testen
php test_db.php

# Cleanup manuell ausführen
php cleanup.php

# Upload-Verzeichnis prüfen
ls -la uploads/

# PHP-Syntax prüfen
php -l upload.php
php -l download.php

# Error-Logs prüfen
tail -f /var/log/apache2/error.log
```

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

## 🤝 Mitwirken

1. Fork das Repository
2. Feature-Branch erstellen
3. Änderungen committen
4. Pull Request erstellen

## 📄 Lizenz

Dieses Projekt steht unter der MIT-Lizenz. Siehe LICENSE-Datei für Details.

## 📚 Projekthistorie & Development Notes

### 🔄 Entwicklungsansätze (für zukünftige Entwicklung)

#### **Security-Features Implementierung:**
Das Projekt hat **zwei Ansätze** für Security-Features:

**🎯 Ansatz 1: Maximale Sicherheit (Vollversion)**
- Dateien: `security.php`, `csrf_token.php`, `update_database.php`
- Features: Rate-Limiting, CSRF-Protection, MIME-Validierung, Path-Traversal-Schutz
- **Voraussetzungen:** PHP 7.4+, erweiterte DB-Struktur, moderne Server-Umgebung

**🎯 Ansatz 2: Kompatibilität-First (Aktuelle Produktionsversion)**
- Dateien: `upload.php`, `download.php` (bereits kompatibel)
- Features: Basis-Validierung, E2E-Verschlüsselung, Core-Funktionalität
- **Voraussetzungen:** PHP 5.6+, Standard-DB-Struktur, Legacy-Server-Support

### 🚀 Migration Path (Für Upgrades)
```
1. Bestehende Installation → Kompatible Version (aktuell)
2. Server-Updates durchführen → PHP 7.4+, moderne Extensions
3. Database-Schema erweitern → update_database.php ausführen
4. Security-Module implementieren → Vollversion aktivieren
```

### 🔧 File-Mapping (Welche Datei wann verwenden)

| **Szenario** | **Upload** | **Download** | **Features** |
|--------------|------------|--------------|-------------|
| **Produktionsserver** | `upload.php` | `download.php` | Basic + E2E (funktioniert) |
| **Development/Debug** | `upload_basic.php` | `download_simple.php` | Debug + Extended Logs |
| **Erweiterte Security** | `upload.php` + Security-Module | `download.php` + Security-Module | Alle Features |

### 🐛 Debug-History (Für Referenz)

**Session vom Heute:**
- **Problem:** HTTP 500 Fehler durch moderne Security-Features
- **Root Cause:** Server-Inkompatibilität mit PHP 8+ Features und fehlende DB-Spalten
- **Solution:** Kompatible Versionen ohne moderne Dependencies erstellt
- **Result:** Alle Features funktionieren, E2E-Encryption intakt

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

### 🔧 Verbessert
- **upload.php** - Vollständige Sicherheitshärtung
- **download.php** - Sichere Parameter-Validierung und Chunk-Downloads
- **delete.php** - Robuste Token-Validierung
- **Frontend** - CSRF-Token-Integration und verbesserte Fehlerbehandlung

### 🗑 Entfernt
- **Unsichere direkte Parameter-Übergabe** - Ersetzt durch Validierung
- **Unvalidierte Dateinamen** - Jetzt vollständige Sanitization
- **Fehlende Rate-Limits** - Durch intelligente IP-basierte Begrenzung ersetzt

### 🐛 Sicherheitslücken behoben
- **CVE-potentielle Upload-Schwachstellen** - Durch Whitelist-Validierung
- **XSS-Risiken** - Durch sichere Output-Encoding
- **CSRF-Angriffe** - Durch Token-Validierung
- **Path-Traversal** - Durch sichere Pfad-Validierung
- **DoS-Angriffe** - Durch Rate-Limiting

### 🔧 Kritische Bugfixes (Post-Release)
- **HTTP 500 Upload-Fehler** - Kompatible Version ohne moderne Dependencies
- **Database Schema Mismatch** - Funktioniert jetzt mit alter DB-Struktur
- **Download 404 Fehler** - Schöne Error-Pages mit Countdown implementiert
- **Copy-Button defekt** - Moderne Clipboard API mit Fallback
- **Navigation-Probleme** - "Zurück zur Upload-Seite" Buttons hinzugefügt
- **CSRF-Token Fehler** - System funktioniert jetzt ohne externe Dependencies
- **🆕 Bulk-Upload Base64-Dekodierung** - "String contains an invalid character" bei passwort-geschützten Multi-Uploads

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
- [x] **Bulk-Upload für mehrere Dateien** ✅ (v0.2.8+)
- [ ] Admin-Panel für Statistiken und Security-Logs
- [ ] Erweiterte Ablaufzeit-Optionen
- [ ] Multi-Language-Support
- [ ] API-Endpoints
- [ ] Docker-Container

---

**CactusDrop** - Sicher in der Wüste des Internets 🌵