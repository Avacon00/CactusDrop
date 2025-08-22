# 🌵 CactusDrop v0.3.0

**Sicheres & anonymes Filesharing mit End-to-End-Verschlüsselung**

CactusDrop ist eine selbst-gehostete, sichere File-Sharing-Anwendung, die Dateien mit client-seitiger End-to-End-Verschlüsselung teilt. Alle Dateien werden automatisch nach 24 Stunden gelöscht.

![Version](https://img.shields.io/badge/version-0.3.0-green)
![PHP](https://img.shields.io/badge/PHP-7.4+-blue)
![Security](https://img.shields.io/badge/security-hardened-red)
![License](https://img.shields.io/badge/license-MIT-brightgreen)

> **🆕 Version 0.3.0 Update:** Moderner 3-Schritt Webinstaller mit AJAX-Datenbankprüfung und Konfetti-Animation!

## ✨ Features

### 🔐 Sicherheit
- **End-to-End-Verschlüsselung** (AES-GCM 256-bit) direkt im Browser
- **Client-seitige Verschlüsselung** - Server erhält niemals unverschlüsselte Daten
- **Optionaler Passwortschutz** mit PBKDF2-Schlüsselableitung
- **Einmal-Download-Option** - Link wird nach erstem Download gelöscht
- **Automatische Löschung** nach 24 Stunden
- **Rate-Limiting** - 10 Uploads pro IP/Stunde
- **Input-Validierung** - Umfassende Datei- und Parameter-Prüfung
- **CSRF-Protection** - Token-basierter Schutz vor Cross-Site-Angriffen
- **MIME-Type-Validierung** - Whitelist-basierte Dateityp-Kontrolle
- **Path-Traversal-Schutz** - Sichere Dateipfad-Verarbeitung

### 🚀 Benutzerfreundlichkeit
- **Multi-File Upload** - Mehrere Dateien parallel verschlüsseln und hochladen
- **Bulk-Upload-Results** - Übersicht aller Upload-Links mit JSON-Export
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

- **PHP 7.4+** mit MySQLi- und ZIP-Erweiterung
- **MySQL/MariaDB** Datenbank
- **Webserver** (Apache/Nginx) mit mod_rewrite
- **HTTPS** empfohlen für PWA-Features

## 📦 Installation

### 🚀 Webinstaller v0.3.0 (Empfohlen)
Der neue 3-Schritt Webinstaller macht die Installation super einfach:

1. **Upload:** Nur 2 Dateien auf Ihren Server hochladen:
   - `installer.php` (der Webinstaller)
   - `cactusdrop.zip` (alle CactusDrop-Dateien)

2. **Installation:** Browser öffnen → `https://ihre-domain.de/installer.php`

3. **3-Schritt-Prozess:**
   - **📦 Schritt 1:** Dateien extrahieren (`cactusdrop.zip` → `cactusdrop/` Ordner)
   - **🛠 Schritt 2:** Datenbank prüfen (AJAX-Test mit Live-Feedback)
   - **🎉 Schritt 3:** Good Job! (Konfetti-Animation + Weiterleitung)

4. **Fertig:** CactusDrop läuft unter `https://ihre-domain.de/cactusdrop/`

### Webinstaller-Features:
- ✅ **Identisches Design** zu CactusDrop (Farben, Fonts, Layout)
- ✅ **AJAX-Datenbanktest** mit präzisen Fehlermeldungen
- ✅ **Live-Feedback** - "Weiter" Button erst nach erfolgreicher DB-Verbindung
- ✅ **Automatische Konfiguration** - config.php wird automatisch erstellt
- ✅ **Konfetti-Animation** bei erfolgreichem Abschluss
- ✅ **Dateiberechtigungen** werden automatisch gesetzt (755/644)
- ✅ **Datenbank-Schema** wird automatisch erstellt

### Option 2: Manuelle Installation
Laden Sie alle Projektdateien auf Ihren Webserver hoch und konfigurieren Sie `config.php` manuell.

### 3. Cronjob einrichten
Für die automatische Bereinigung abgelaufener Dateien:

```bash
# Täglich um 3:00 Uhr ausführen
0 3 * * * /usr/bin/php /pfad/zu/ihrer/installation/cactusdrop/cleanup.php
```

### 4. Sicherheit
- Löschen Sie `installer.php` und `cactusdrop.zip` nach der Installation
- Stellen Sie sicher, dass das `/uploads/` Verzeichnis nicht direkt zugänglich ist
- Verwenden Sie HTTPS für Produktionsumgebungen

## 🔧 Konfiguration

### config.php (automatisch erstellt vom Installer)
```php
// Datenbank-Einstellungen
define('DB_HOST', 'localhost');
define('DB_USER', 'ihr_db_user');
define('DB_PASS', 'ihr_db_passwort');
define('DB_NAME', 'ihre_datenbank');

// Anwendungs-URL (automatisch erkannt)
define('APP_URL', 'https://ihre-domain.de/cactusdrop');

// Upload-Verzeichnis
define('UPLOAD_DIR', __DIR__ . '/uploads/');
```

## 📂 Projektstruktur

```
cactusdrop/ (erstellt vom Installer)
├── index.html                    # Haupt-Anwendung (Multi-Upload-Interface)
├── upload.php                    # Upload-Handler (kompatible Version)
├── download.php                  # Download-Handler (kompatible Version)
├── delete.php                    # Datei-Löschung 
├── cleanup.php                   # Cronjob für automatische Bereinigung
├── config.php                    # Konfigurationsdatei (auto-generiert)
├── security.php                  # Zentrales Sicherheitsmodul
├── csrf_token.php                # CSRF-Token API
├── manifest.json                 # PWA-Manifest
├── sw.js                         # Service Worker für PWA
└── uploads/                      # Verschlüsselte Dateien (vom Web verborgen)

# Installer-Dateien (nach Installation löschen)
installer.php                     # 3-Schritt Webinstaller
cactusdrop.zip                    # Alle CactusDrop-Dateien gepackt
```

## 🔒 Sicherheitsarchitektur

### Verschlüsselungsflow
1. **Upload**: Datei wird im Browser mit AES-GCM verschlüsselt
2. **Schlüssel**: Verschlüsselungsschlüssel bleibt im Browser (im URL-Fragment)
3. **Server**: Erhält nur verschlüsselte Daten
4. **Download**: Entschlüsselung erfolgt wieder im Browser
5. **Passwort**: Optional zusätzliche Verschlüsselung des Schlüssels

### Multi-File Upload (v0.3.0)
- **Parallel-Verschlüsselung** - Mehrere Dateien gleichzeitig im Browser
- **Bulk-Progress-UI** - Live-Fortschritt für jeden Upload
- **Results-Dashboard** - Übersicht aller Upload-Links
- **JSON-Export** - Alle Links als JSON-Datei herunterladen
- **Individual Copy** - Jeder Link einzeln kopierbar

### Keine Server-seitige Entschlüsselung
Der Server kann niemals auf die Originaldateien zugreifen, da:
- Verschlüsselung erfolgt client-seitig
- Schlüssel werden nie an den Server übertragen
- Nur verschlüsselte Daten werden gespeichert

## 🔧 Wartung & Monitoring

### Erweiterte Datenbankstruktur (automatisch erstellt)
```sql
-- Haupttabelle (kompatible Version für v0.3.0)
CREATE TABLE files (
  id varchar(16) NOT NULL PRIMARY KEY,
  secret_token varchar(64) NOT NULL,
  original_filename varchar(255) NOT NULL,
  is_onetime tinyint(1) DEFAULT 0,
  created_at timestamp DEFAULT CURRENT_TIMESTAMP,
  delete_at timestamp NOT NULL,
  KEY idx_delete_at (delete_at)
);

-- Rate-Limiting
CREATE TABLE rate_limits (
  id int(11) NOT NULL AUTO_INCREMENT,
  ip_address varchar(45) NOT NULL,
  created_at timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_ip_created (ip_address, created_at)
);
```

### Logs überwachen
- Webserver-Logs für Upload/Download-Aktivitäten
- Cronjob-Logs für Cleanup-Prozess
- PHP-Fehlerprotokolle für Debug-Informationen

## 🚀 Verwendung

### Multi-File Upload (Neu in v0.3.0)
1. **Mehrere Dateien auswählen** per Drag & Drop oder Dateiauswahl
2. **Bulk-Upload startet** - Alle Dateien werden parallel verschlüsselt
3. **Live-Progress** - Fortschritt für jede Datei einzeln sichtbar
4. **Results-Dashboard** - Übersicht aller generierten Links
5. **Export-Optionen:**
   - Alle Links einzeln kopieren
   - JSON-Export aller Links
   - QR-Codes für mobile Nutzung

### Single-File Upload
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

## 🔍 Fehlerbehebung & Installer-Probleme

### Webinstaller-Troubleshooting

#### **Problem: 'cactusdrop.zip' nicht gefunden**
**Lösung:** Beide Dateien in dasselbe Verzeichnis hochladen:
- `installer.php`
- `cactusdrop.zip`

#### **Problem: ZIP-Extraktion schlägt fehl**
**Lösung:** 
- PHP ZIP-Erweiterung aktivieren: `php -m | grep zip`
- Schreibrechte prüfen: `chmod 755 verzeichnis/`

#### **Problem: Datenbankverbindung schlägt fehl**
**Lösung:**
- Zugangsdaten aus dem Hosting-Panel verwenden
- Host oft: `localhost` oder spezielle IP
- Bei all-inkl.com: Benutzername oft gleich Datenbankname

#### **Problem: Konfetti funktioniert nicht**
**Lösung:** 
- JavaScript aktiviert?
- Browser-Kompatibilität (IE wird nicht unterstützt)
- Nach 5 Sekunden automatische Weiterleitung

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

# ZIP-Inhalt prüfen
unzip -l cactusdrop.zip
```

## 🛡 Sicherheitsempfehlungen

### ✅ Bereits implementiert (v0.3.0)
1. **✅ Rate Limiting** - 10 Uploads pro IP/Stunde (automatisch aktiv)
2. **✅ Input-Validierung** - Umfassende Datei- und Parameter-Prüfung
3. **✅ MIME-Type-Validierung** - Nur erlaubte Dateitypen
4. **✅ Path-Traversal-Schutz** - Sichere Dateipfad-Verarbeitung
5. **✅ Multi-File Security** - Bulk-Upload mit gleichen Sicherheitsstandards

### 🔧 Zusätzliche Empfehlungen
6. **HTTPS verwenden** - Essentiell für sichere Übertragung
7. **Regelmäßige Updates** - PHP und Datenbank aktuell halten
8. **Backup-Strategie** - Regelmäßige Datenbank-Backups
9. **Firewall** - Upload-Verzeichnis vor direktem Zugriff schützen
10. **Installer löschen** - Nach Installation `installer.php` und `cactusdrop.zip` entfernen

## 📈 Performance-Tipps

- **Webserver-Caching** für statische Assets aktivieren
- **Gzip-Kompression** für bessere Ladezeiten
- **CDN** für TailwindCSS (bereits implementiert)
- **Database-Indizes** für bessere Query-Performance
- **Bulk-Upload-Limits** - Bei vielen großen Dateien Server-Timeout beachten

## 🤝 Mitwirken

1. Fork das Repository
2. Feature-Branch erstellen
3. Änderungen committen
4. Pull Request erstellen

## 📄 Lizenz

Dieses Projekt steht unter der MIT-Lizenz. Siehe LICENSE-Datei für Details.

## 🆕 Changelog v0.3.0

### ✅ Neu hinzugefügt
- **🚀 Moderner 3-Schritt Webinstaller** - Komplett überarbeitete Installation
- **📱 AJAX-Datenbankprüfung** - Live-Feedback mit präzisen Fehlermeldungen
- **🎉 Konfetti-Animation** - "Good Job!" mit grüner Konfetti-Animation
- **🎨 Identisches Design** - Installer verwendet exakt die gleichen Styles wie CactusDrop
- **📦 ZIP-basierte Installation** - Alle Dateien in `cactusdrop.zip` gepackt
- **⚙️ Automatische Konfiguration** - `config.php` wird automatisch generiert
- **🔒 Dateiberechtigungen** - Automatisches Setzen von 755/644 Rechten
- **🗂 Saubere Ordnerstruktur** - Installation in `cactusdrop/` Ordner

### 🔧 Installer-Features
- **Fortschrittsbalken** - Visuelle Schritt-für-Schritt Anzeige
- **Error-Handling** - Detaillierte Fehlermeldungen bei jedem Schritt
- **Responsives Design** - Funktioniert auf Desktop und Mobile
- **Automatische Weiterleitung** - Nach Installation zu CactusDrop
- **One-File-Lösung** - Nur 2 Dateien zum Upload nötig

### 🎯 Verbessert
- **Installation Process** - Von 4-Schritt auf 3-Schritt reduziert
- **User Experience** - Moderne UI mit Animationen und Feedback
- **Error Messages** - Präzise DB-Fehlermeldungen (Access denied, Host unreachable, etc.)
- **Security** - Installer validiert alle Eingaben und erstellt sichere config.php

### 🐛 Behoben
- **DB-Test ohne Weiterleitung** - Button bleibt deaktiviert bis erfolgreiche Verbindung
- **Konfetti-Timing** - Animation startet sofort bei Erfolg
- **Responsive Layout** - Installer funktioniert auf allen Bildschirmgrößen
- **Auto-URL-Detection** - APP_URL wird automatisch korrekt erkannt

## 🆘 Support

Bei Problemen oder Fragen:
1. README und Troubleshooting-Sektion prüfen
2. Installer-Debug: Browser DevTools (F12) verwenden
3. Server-Logs analysieren
4. Issue im Repository erstellen

## 🎯 Roadmap

- [x] **Multi-File Upload** ✅ (v0.2.9)
- [x] **Moderner Webinstaller** ✅ (v0.3.0)
- [x] **AJAX-Datenbankprüfung** ✅ (v0.3.0)
- [x] **Konfetti-Animation** ✅ (v0.3.0)
- [ ] Admin-Panel für Statistiken und Security-Logs
- [ ] Erweiterte Ablaufzeit-Optionen
- [ ] Multi-Language-Support
- [ ] API-Endpoints
- [ ] Docker-Container

---

**CactusDrop v0.3.0** - Jetzt mit dem modernsten Webinstaller! 🌵✨