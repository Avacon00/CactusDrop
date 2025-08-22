# 🌵 CactusDrop v0.3.0 - FUNKTIONIERENDER ZUSTAND (WEBINSTALLER UPDATE)

> **⚠️ WICHTIG: Diese Datei NIEMALS committen! Nur lokale Referenz für Hotfixes!**
> 
> **Datum:** 2025-08-22 - 15:00 CET  
> **Status:** VOLLSTÄNDIG FUNKTIONAL - Webinstaller v0.3.0 implementiert  
> **Zweck:** Referenz für Hotfixes und Wiederherstellung bei Problemen  

---

## 🎯 **AKTUELLE FUNKTIONIERENDE FEATURES**

### ✅ **Webinstaller v0.3.0 (BRANDNEU)**
- **3-Schritt-Prozess:** Moderne UI mit Fortschrittsbalken (installer.php)
- **ZIP-Extraktion:** `cactusdrop.zip` → `cactusdrop/` Ordner automatisch
- **AJAX-DB-Test:** Live-Feedback mit präzisen Fehlermeldungen
- **Auto-Konfiguration:** `config.php` wird automatisch generiert
- **Konfetti-Animation:** "Good Job!" mit grüner Animation bei Erfolg
- **Dateiberechtigungen:** 755/644 automatisch gesetzt
- **Identisches Design:** Exakt gleiche Styles wie CactusDrop (Tailwind + Inter)

### ✅ **Multi-Upload System (STABIL)**
- **Datei-Input:** `<input type="file" id="file-input" multiple>` (index.html:46)
- **Auto-Detection:** `handleFiles()` erkennt Single vs Multi (index.html:251-280)
- **Bulk-Processing:** `proceedWithBulkUpload()` verarbeitet Arrays (index.html:311-385)
- **Results-UI:** `showBulkUploadResults()` zeigt Zusammenfassung (index.html:387-446)
- **JSON-Export:** `downloadResultsAsJson()` für Link-Download (index.html:458-479)

### ✅ **E2E-Verschlüsselung (STABIL)**
- **Single-File:** `proceedWithUpload()` (index.html:484-571)
- **Schlüssel-Generation:** `generateAesKey()` (index.html:573-577)
- **Passwort-Ableitung:** `deriveKeyFromPassword()` (index.html:579-589)
- **Verschlüsselung:** `encryptData()` mit AES-GCM (index.html:591-593)

### ✅ **Upload-Backend (KOMPATIBEL)**
- **Datei:** `upload.php` - Kompatible Version für alte DB-Struktur
- **Validierung:** Zeile 25-48 (Größe, Extensions, Sicherheit)
- **ID-Generation:** Zeile 58-59 (sichere random_bytes)
- **DB-Insert:** Zeile 83-94 (ohne password_hash für Kompatibilität)

### ✅ **Download-System (VOLLSTÄNDIG)**
- **Datei:** `download.php` - Dual-Mode (Page + Raw-API)
- **Raw-Download:** Zeile 50-116 (verschlüsselte Dateien)
- **Entschlüsselung:** Zeile 259-343 (Browser-basiert)
- **Passwort-Support:** Zeile 270-286 (PBKDF2-Ableitung)

### ✅ **Security Module (v0.2.8)**
- **Datei:** `security.php` - Umfassendes Sicherheitssystem
- **Rate-Limiting:** `checkRateLimit()` Zeile 244-274
- **File-Validation:** `validateUploadedFile()` Zeile 71-117
- **MIME-Whitelist:** Konstante Zeile 17-60
- **CSRF-Protection:** Zeile 344-363

---

## 🚀 **WEBINSTALLER-ARCHITEKTUR (v0.3.0)**

### **installer.php - Hauptdatei**
```php
define('CACTUSDROP_VERSION', '0.3.0');

// 3-Schritt-Struktur:
// Schritt 1: extractFilesFromZip() → cactusdrop/ erstellen
// Schritt 2: AJAX testDatabase() → Live-Feedback
// Schritt 3: createDatabaseAndConfig() → Good Job!
```

### **Schritt 1: ZIP-Extraktion (Zeile 82-131)**
- **ZIP-Validierung:** `file_exists('cactusdrop.zip')`
- **PHP-Extension-Check:** `extension_loaded('zip')`
- **Target-Ordner:** `$targetDir = 'cactusdrop/';`
- **Berechtigungen:** `setCorrectPermissions()` (755/644)
- **Error-Handling:** Try-Catch mit detaillierten Meldungen

### **Schritt 2: AJAX-Datenbanktest (Zeile 23-66)**
- **Handler:** `$_POST['action'] === 'test_db'`
- **Live-Test:** `new mysqli()` mit Verbindungstest
- **JSON-Response:** `{'success': true/false, 'message': '...'}`
- **UI-Update:** Button-Status + Weiter-Button aktivieren/deaktivieren

### **Schritt 3: Konfiguration + Schema (Zeile 148-220)**
- **DB-Schema:** `CREATE TABLE files` + `rate_limits` automatisch
- **config.php:** Auto-generiert mit korrekten Pfaden
- **APP_URL:** Automatische Erkennung `$protocol://$host/cactusdrop`
- **Security:** Passwort-Escaping mit `addslashes()`

### **UI/UX-Features (Zeile 247-512)**
- **Design:** Identisch zu index.html (bg-gray-900, Inter Font, Tailwind)
- **Fortschrittsbalken:** `width: <?php echo ($step / 3 * 100); ?>%`
- **Error-Messages:** Fade-in Animationen mit korrekten Farben
- **Konfetti:** 50 grüne Partikel mit 3s Animation
- **Auto-Redirect:** Nach 5s zu `cactusdrop/index.html`

---

## 📦 **CACTUSDROP.ZIP INHALT**

### **Enthaltene Dateien (9 Core-Files):**
- ✅ **index.html** (51KB) - Multi-Upload Frontend
- ✅ **upload.php** (4KB) - Kompatibles Backend
- ✅ **download.php** (14KB) - Download + Entschlüsselung
- ✅ **delete.php** (3.5KB) - Sichere Dateilöschung
- ✅ **cleanup.php** (1KB) - Cronjob-Bereinigung
- ✅ **security.php** (15KB) - Sicherheitsmodul
- ✅ **csrf_token.php** (596B) - CSRF-API
- ✅ **manifest.json** (552B) - PWA-Manifest
- ✅ **sw.js** (1.5KB) - Service Worker

### **ZIP-Erstellung (PowerShell-Command):**
```powershell
Compress-Archive -Path 'index.html', 'upload.php', 'download.php', 'delete.php', 'cleanup.php', 'security.php', 'csrf_token.php', 'manifest.json', 'sw.js' -DestinationPath 'cactusdrop.zip' -Force
```

---

## 🔧 **KRITISCHE KONFIGURATION (AUTO-GENERIERT)**

### **Database Config (installer.php erstellt automatisch)**
```php
// config.php - CactusDrop v0.3.0
define('DB_HOST', '{$host}');
define('DB_USER', '{$user}');
define('DB_PASS', '{$pass_escaped}');
define('DB_NAME', '{$name}');
define('APP_URL', '{$app_url}');
define('UPLOAD_DIR', __DIR__ . '/uploads/');
```

### **Database Schema (AUTO-CREATED)**
```sql
-- Kompatible Version (ohne password_hash)
CREATE TABLE IF NOT EXISTS `files` (
    `id` varchar(16) NOT NULL,
    `secret_token` varchar(64) NOT NULL,
    `original_filename` varchar(255) NOT NULL,
    `is_onetime` tinyint(1) NOT NULL DEFAULT 0,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    `delete_at` timestamp NOT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_delete_at` (`delete_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `rate_limits` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `ip_address` varchar(45) NOT NULL,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY (`id`),
    KEY `idx_ip_time` (`ip_address`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### **File Permissions (AUTO-SET)**
```
cactusdrop/ - 0755 (Directory)
*.php - 0644 (Files)  
*.html - 0644
*.js - 0644
*.json - 0644
uploads/ - 0755 (wird beim ersten Upload erstellt)
```

---

## 📁 **DATEI-STRUKTUR (v0.3.0)**

```
# INSTALLER-DATEIEN
├── installer.php              ✅ 3-SCHRITT WEBINSTALLER
├── cactusdrop.zip              ✅ ALLE CORE-DATEIEN GEPACKT

# NACH INSTALLATION (cactusdrop/ Ordner)
cactusdrop/
├── index.html                  ✅ MULTI-UPLOAD FRONTEND
├── upload.php                  ✅ KOMPATIBLES BACKEND  
├── download.php                ✅ DUAL-MODE DOWNLOAD
├── config.php                  ✅ AUTO-GENERIERTE CONFIG
├── security.php                ✅ SICHERHEITSMODUL
├── delete.php                  ✅ SICHERES LÖSCHEN
├── cleanup.php                 ✅ CRONJOB-BEREINIGUNG
├── csrf_token.php              ✅ CSRF-API
├── manifest.json               ✅ PWA-MANIFEST
├── sw.js                       ✅ SERVICE-WORKER
└── uploads/                    ✅ AUTO-CREATED UPLOAD-DIR
```

---

## 🚨 **HOTFIX CHECKLISTE (WEBINSTALLER-DEBUGGING)**

### **1. 'cactusdrop.zip nicht gefunden' Fehler**
**Prüfe diese Punkte:**
- Zeile 88: `if (!file_exists($zipFile))`
- Beide Dateien im gleichen Verzeichnis: `installer.php` + `cactusdrop.zip`
- Dateiberechtigungen: ZIP-Datei lesbar (644)?

### **2. ZIP-Extraktion schlägt fehl**
**Prüfe diese Zeilen:**
- Zeile 94: `extension_loaded('zip')` - PHP ZIP-Extension aktiv?
- Zeile 100: `$zip->open($zipFile)` - ZIP-Datei korrekt?
- Zeile 112: `$zip->extractTo($targetDir)` - Schreibrechte auf Ordner?

### **3. AJAX-Datenbanktest funktioniert nicht**
**Prüfe JavaScript (Zeile 432-475):**
- Browser DevTools → Console für Fehler
- Network Tab → POST-Request zu installer.php
- Response: JSON-Format korrekt?
- Button-Status: `disabled` Attribut wird korrekt gesetzt?

### **4. 'Weiter' Button bleibt deaktiviert**
**Prüfe AJAX-Response:**
- Zeile 455: `continueBtn.disabled = false` wird ausgeführt?
- Zeile 456: CSS-Klassen werden korrekt gesetzt?
- DB-Verbindung erfolgreich? Test mit separatem PHP-Script

### **5. Konfetti-Animation startet nicht**
**Prüfe Schritt 3 JavaScript (Zeile 480-507):**
- Zeile 484: `createConfetti()` wird aufgerufen?
- Browser unterstützt DOM-Manipulation?
- CSS-Animation (Zeile 265-281) korrekt geladen?

### **6. config.php wird nicht erstellt**
**Prüfe createDatabaseAndConfig() (Zeile 148-220):**
- Zeile 207: `file_put_contents('cactusdrop/config.php', $config_content)`
- Schreibrechte auf `cactusdrop/` Ordner?
- Zeile 223: `addslashes($pass)` für Passwort-Escaping

---

## 🔐 **INSTALLER-SICHERHEIT**

### **Input-Validierung (Zeile 32-38, 156-159)**
```php
if (empty($db_host) || empty($db_name) || empty($db_user)) {
    return false; // Erforderliche Felder prüfen
}
```

### **SQL-Injection-Schutz**
- Keine direkten User-Inputs in SQL-Queries
- `new mysqli()` mit parametrisierter Verbindung
- `addslashes()` für config.php Passwort-Escaping

### **Path-Traversal-Schutz**
- Feste Zielverzeichnisse: `cactusdrop/`
- Keine User-definierte Pfade
- ZIP-Extraktion nur in erlaubte Ordner

### **Error-Handling**
- Try-Catch für alle kritischen Operationen
- Detaillierte Fehlermeldungen ohne sensitive Daten
- Graceful Fallback bei Problemen

---

## 🎨 **UI/UX FEATURES (IDENTISCH ZU CACTUSDROP)**

### **Design-System**
- **Farbschema:** bg-gray-900, text-gray-200, green-500 Accents
- **Typografie:** Inter Font (Google Fonts)
- **Framework:** Tailwind CSS (CDN)
- **Layout:** max-w-md, rounded-2xl, shadow-lg

### **Animationen**
- **Fade-in:** Error/Success Messages (0.5s ease-in-out)
- **Konfetti:** 50 Partikel, 4 Grüntöne, 3s Fall-Animation
- **Transitions:** Button-Hover, Fortschrittsbalken (duration-500)

### **Responsive Design**
- **Mobile-First:** Funktioniert auf allen Bildschirmgrößen
- **Touch-Friendly:** Große Buttons, ausreichend Padding
- **Accessibility:** Semantic HTML, Focus-States

---

## 🔄 **INSTALLATION WORKFLOW (GETESTET)**

### **Kompletter Ablauf:**
1. **Upload:** `installer.php` + `cactusdrop.zip` auf Server
2. **Schritt 1:** Browser → `installer.php` → ZIP extrahieren
3. **Schritt 2:** DB-Daten eingeben → "Prüfen" → Live-Feedback
4. **Schritt 3:** "Weiter" → Schema + Config → Konfetti
5. **Redirect:** Nach 5s zu `cactusdrop/index.html`
6. **Cleanup:** Installer + ZIP manuell löschen

### **Alternative Flows:**
- **Fehler in Schritt 1:** ZIP nicht gefunden → Fehler-UI
- **Fehler in Schritt 2:** DB-Verbindung fehlgeschlagen → AJAX-Error
- **Fehler in Schritt 3:** Schema-Erstellung → PHP-Exception

---

## 🛡️ **SECURITY VALIDIERUNG**

### **Installer-Security-Pipeline**
1. **File-Validation:** ZIP-Datei existiert und ist gültig
2. **Extension-Check:** PHP ZIP-Extension verfügbar
3. **Permission-Check:** Schreibrechte auf Zielverzeichnis
4. **DB-Validation:** Verbindungstest vor Schema-Erstellung
5. **SQL-Safety:** Keine User-Inputs in direkten Queries

### **Path-Traversal Protection (AKTIV)**
```php
// Feste Zielverzeichnisse
$targetDir = 'cactusdrop/';
$configPath = 'cactusdrop/config.php';
// Keine User-Input-Pfade!
```

---

## 📊 **MONITORING & DEBUGGING**

### **Installer-Logs**
- **Browser Console:** JavaScript-Fehler und AJAX-Responses
- **Network Tab:** HTTP-Requests und Status-Codes
- **PHP Error-Log:** Server-seitige Fehler und Exceptions

### **Debug-Commands**
```bash
# ZIP-Inhalt prüfen
unzip -l cactusdrop.zip

# PHP-Extensions prüfen
php -m | grep zip

# Verzeichnis-Rechte prüfen
ls -la cactusdrop/

# Installation testen
curl -X POST installer.php -d "action=test_db&db_host=localhost..."
```

---

## 🆘 **NOTFALL-WIEDERHERSTELLUNG**

### **Wenn Installer kaputt:**
1. **Manual Fallback:** Alle Dateien aus ZIP einzeln hochladen
2. **config.php manuell:** Template aus Working State kopieren
3. **DB-Schema manuell:** SQL-Befehle einzeln ausführen

### **Wenn ZIP beschädigt:**
1. **Neue ZIP erstellen:** PowerShell-Command aus Working State
2. **Einzeldateien prüfen:** Jede Datei auf Syntax-Fehler testen
3. **Backup verwenden:** Letzte funktionierende Version

### **Wenn DB-Schema fehlschlägt:**
1. **Multi-Query-Problem:** Einzelne CREATE TABLE Statements
2. **Charset-Problem:** utf8mb4 → utf8 ändern
3. **Permission-Problem:** GRANT ALL auf Datenbank prüfen

---

## 🎯 **VERSION INFO**

- **CactusDrop Version:** 0.3.0 (mit Webinstaller)
- **Installer-Features:** 3-Schritt, AJAX-DB-Test, Konfetti
- **PHP Requirement:** 7.4+ (ZIP-Extension erforderlich)
- **Database:** MySQL/MariaDB 
- **Browser:** Modern browsers (IE nicht unterstützt)
- **Security Level:** Production-Ready mit Installer-Validation

---

**🔥 ACHTUNG: Bei Änderungen IMMER gegen diese Referenz prüfen!**  
**📝 Diese Datei bei jedem Major-Update aktualisieren.**

**Stand: 2025-08-22 - WEBINSTALLER v0.3.0 VOLLSTÄNDIG FUNKTIONAL!** ✅🌵