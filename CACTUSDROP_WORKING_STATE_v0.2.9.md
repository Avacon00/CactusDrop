# 🌵 CactusDrop v0.2.9 - FUNKTIONIERENDER ZUSTAND (HOTFIX REFERENZ)

> **⚠️ WICHTIG: Diese Datei NIEMALS committen! Nur lokale Referenz für Hotfixes!**
> 
> **Datum:** 2025-08-20 23:30 CET  
> **Status:** VOLLSTÄNDIG FUNKTIONAL - Multi-Upload implementiert  
> **Zweck:** Referenz für Hotfixes und Wiederherstellung bei Problemen  

---

## 🎯 **AKTUELLE FUNKTIONIERENDE FEATURES**

### ✅ **Multi-Upload System (FUNKTIONIERT PERFEKT)**
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

## 🔧 **KRITISCHE KONFIGURATION (NICHT ÄNDERN!)**

### **Database Config (config.php)**
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'd04408a9');
define('DB_PASS', 'nShYxzGUCeoUWJ42NSef'); 
define('DB_NAME', 'd04408a9');
define('APP_URL', 'https://schuttehub.de');
define('UPLOAD_DIR', __DIR__ . '/uploads/');
```

### **Database Schema (FUNKTIONIERT)**
```sql
-- Haupttabelle (KOMPATIBLE VERSION)
CREATE TABLE files (
    id VARCHAR(16) PRIMARY KEY,
    secret_token VARCHAR(64) NOT NULL,
    original_filename VARCHAR(255) NOT NULL,
    is_onetime TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    delete_at TIMESTAMP NOT NULL
);

-- Rate Limiting
CREATE TABLE rate_limits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### **File Permissions (WICHTIG)**
```
uploads/ - 0755 (Directory)
files in uploads/ - 0644
config.php - 0644 (aber Zugangsdaten schützen)
```

---

## 📁 **DATEI-STRUKTUR (VOLLSTÄNDIG)**

```
cactusdrop/
├── index.html              ✅ MULTI-UPLOAD FRONTEND
├── upload.php              ✅ KOMPATIBLES BACKEND  
├── download.php            ✅ DUAL-MODE DOWNLOAD
├── config.php              ✅ PRODUKTIONS-CONFIG
├── security.php            ✅ SICHERHEITSMODUL
├── delete.php              ✅ SICHERES LÖSCHEN
├── cleanup.php             ✅ CRONJOB-BEREINIGUNG
├── installer.php           ✅ SELF-EXTRACTING
├── csrf_token.php          ✅ CSRF-API
├── update_database.php     ✅ SCHEMA-MIGRATION
├── test_db.php             ✅ DB-CONNECTION-TEST
├── manifest.json           ✅ PWA-MANIFEST
├── sw.js                   ✅ SERVICE-WORKER
└── uploads/                ✅ UPLOAD-DIRECTORY
```

---

## 🚨 **HOTFIX CHECKLISTE (BEIM DEBUGGEN)**

### **1. Multi-Upload kaputt?**
**Prüfe diese Zeilen in index.html:**
- Zeile 46: `<input type="file" id="file-input" multiple>`
- Zeile 207: `if (e.dataTransfer.files.length > 0) handleFiles(e.dataTransfer.files);`
- Zeile 210: `if (e.target.files.length > 0) handleFiles(e.target.files);`
- Zeile 254-257: Single vs Multi Detection Logic

### **2. Upload-Fehler HTTP 500?**
**Prüfe upload.php:**
- Zeile 83: SQL-Statement `INSERT INTO files (id, secret_token, original_filename, is_onetime, delete_at)`
- Zeile 94: Parameter-Binding `bind_param('sssis', $fileId, $secretToken, $originalFilename, $isOneTime, $deleteAt)`
- DB-Schema: KEINE `password_hash` Spalte verwenden!

### **3. Download schlägt fehl?**
**Prüfe download.php:**
- Zeile 302: `var fileId = <?php echo json_encode($fileId); ?>;`
- Zeile 317: `var originalFilename = <?php echo json_encode($file['original_filename']); ?>;`
- Fragment-Parsing: Zeile 227-228 Key-Fragment-Logic

### **4. Verschlüsselung kaputt?**
**Prüfe diese JavaScript-Funktionen:**
- `generateAesKey()` - Zeile 573-577
- `encryptData()` - Zeile 591-593  
- `base64ToBuffer()` - Zeile 234-242 (download.php)
- `bufferToBase64()` - Zeile 481 (index.html)

---

## 🔐 **SICHERHEITS-EINSTELLUNGEN (AKTIV)**

### **Rate Limiting (security.php)**
```php
const MAX_UPLOADS_PER_IP = 10;      // 10 Uploads pro Stunde
const RATE_LIMIT_WINDOW = 3600;     // 1 Stunde Window
const MAX_FILE_SIZE = 100 * 1024 * 1024; // 100MB Limit
```

### **Erlaubte MIME-Types (Zeile 17-60 security.php)**
```php
// Dokumente: pdf, doc, docx, xls, xlsx, ppt, pptx, txt, csv, rtf
// Archive: zip, rar, 7z, gzip, tar
// Bilder: jpeg, png, gif, webp, svg, bmp, tiff  
// Audio: mp3, wav, ogg, flac, aac
// Video: mp4, avi, mov, webm, ogg
```

### **Blocked Extensions (Zeile 63-66)**
```php
'php', 'php3', 'php4', 'php5', 'phtml', 'asp', 'aspx', 'jsp', 
'exe', 'bat', 'cmd', 'com', 'scr', 'msi', 'dll', 'sh', 'ps1'
```

---

## 🎨 **UI/UX FEATURES (FUNKTIONAL)**

### **Multi-Upload UI-Text (index.html)**
- Zeile 49: `"Dateien hochladen"` (Plural!)
- Zeile 50: `"Mehrere Dateien werden parallel im Browser verschlüsselt"`

### **Bulk-Results Interface**
- Datei-Liste mit Erfolg/Fehler-Status
- Individual Copy-Buttons pro Link  
- JSON-Export aller erfolgreichen Links
- Fortschrittsanzeige während Bulk-Upload

### **PWA-Features**
- Installierbar als App (manifest.json)
- Service Worker für Offline-Shell (sw.js)
- iOS-Installation-Modal (index.html:139-151)

---

## 🔄 **BEKANNTE WORKFLOWS (GETESTET)**

### **Single-File Upload**
1. Datei auswählen → `handleFiles([file])` → `handleFile(file)` → `proceedWithUpload(file)`
2. Verschlüsseln → Upload → Erfolg-Ansicht mit QR-Code

### **Multi-File Upload**  
1. Mehrere Dateien → `handleFiles(files)` → `proceedWithBulkUpload(files)`
2. Schleife: Verschlüsseln → Upload pro Datei
3. Bulk-Results mit Link-Liste und JSON-Export

### **Download-Flow**
1. Link aufrufen → download.php lädt Metadaten
2. "Entschlüsseln" → Raw-Download + Browser-Entschlüsselung  
3. File-Download startet automatisch

---

## 🛡️ **SECURITY VALIDIERUNG**

### **Input-Validation Pipeline**
1. `validateUploadedFile()` - Komplette Datei-Prüfung
2. `validateFilename()` - Dateiname-Säuberung  
3. `validateMimeType()` - MIME + Real-Type Check
4. `scanFileContent()` - Malware-Pattern-Scanning

### **Path-Traversal Protection (AKTIV)**
```php
// delete.php Zeile 45-48
$realPath = realpath($filePath);
$uploadDirReal = realpath(UPLOAD_DIR);
if (!$realPath || !$uploadDirReal || strpos($realPath, $uploadDirReal) !== 0) {
    // BLOCKED
}
```

---

## 📊 **MONITORING & LOGS**

### **Error Logging**
- `error_log('[CactusDrop] ...')` für kritische Fehler
- Security-Events in `security_logs` Tabelle
- Rate-Limiting in `rate_limits` Tabelle

### **Cleanup-Automation**
- `cleanup.php` für 24h Auto-Delete
- Cronjob: `0 3 * * * /usr/bin/php /pfad/cleanup.php`

---

## 🆘 **NOTFALL-WIEDERHERSTELLUNG**

### **Wenn Multi-Upload kaputt:**
1. Prüfe `handleFiles()` Function (index.html:251)
2. Prüfe `multiple` Attribut (index.html:46)  
3. Prüfe `proceedWithBulkUpload()` (index.html:311)

### **Wenn Upload-Backend kaputt:**
1. Prüfe SQL-Statement (upload.php:83)
2. Prüfe DB-Schema (KEINE password_hash!)
3. Prüfe bind_param Parameter-Count

### **Wenn Verschlüsselung kaputt:**
1. Prüfe Web Crypto API Support
2. Prüfe Base64-Encoding/Decoding  
3. Prüfe Key-Fragment-Parsing

---

## 🎯 **VERSION INFO**

- **CactusDrop Version:** 0.2.9 (mit Multi-Upload)
- **PHP Requirement:** 7.4+
- **Database:** MySQL/MariaDB 
- **Browser:** Modern browsers mit Web Crypto API
- **Security Level:** Production-Ready mit v0.2.8 Features

---

**🔥 ACHTUNG: Bei Änderungen IMMER gegen diese Referenz prüfen!**  
**📝 Diese Datei bei jedem Hotfix aktualisieren falls nötig.**

**Stand: 2025-08-20 - ALLES FUNKTIONIERT EINWANDFREI!** ✅