<?php
require_once 'languages.php';
?>
<!DOCTYPE html>
<html lang="<?php echo getCurrentLang(); ?>" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t('app_name'); ?> - <?php echo t('app_subtitle'); ?></title>
    
    <!-- PWA & SEO -->
    <meta name="description" content="<?php echo t('app_subtitle'); ?>">
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#2f855a">
    <link rel="apple-touch-icon" href="icon-192x192.png">

    <!-- Styles & Fonts -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .dragover { border-color: #2f855a; background-color: rgba(47, 133, 90, 0.1); }
        .countdown-segment { background-color: #2d3748; }
        .fade-in { animation: fadeIn 0.5s ease-in-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        .hidden { display: none; }
    </style>

    <script>
        <?php echo LanguageManager::getJavaScriptTranslations(); ?>
    </script>
</head>
<body class="bg-gray-900 text-gray-200 flex flex-col items-center justify-center min-h-screen p-4">

    <div id="app" class="w-full max-w-md mx-auto">
        
        <!-- Header -->
        <header class="text-center mb-8">
            <div class="flex items-center justify-center gap-3 mb-2">
                <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#2f855a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 9V2m0 7v13m0-13c-2.833 0-5 2.167-5 5v0c0 2.833 2.167 5 5 5m0-10c2.833 0 5 2.167 5 5v0c0 2.833-2.167 5-5 5m-5 5h10"/>
                </svg>
                <h1 class="text-4xl font-bold text-gray-100">Cactus<span class="text-green-500">Drop</span></h1>
            </div>
            <p class="text-gray-400"><?php echo t('app_subtitle'); ?></p>
        </header>

        <main id="main-content" class="w-full bg-gray-800 rounded-2xl shadow-lg p-6 transition-all duration-300">
            
            <!-- Upload View -->
            <div id="upload-view">
                <div id="drop-zone" class="relative w-full h-48 border-2 border-dashed border-gray-600 rounded-xl flex flex-col items-center justify-center text-center cursor-pointer transition-all duration-300 hover:border-green-500 hover:bg-gray-700/50">
                    <input type="file" id="file-input" multiple class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                    <div class="space-y-2 pointer-events-none">
                        <svg class="mx-auto h-12 w-12 text-gray-500" stroke="currentColor" fill="none" viewBox="0 0 48 48" aria-hidden="true">
                            <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4V12a4 4 0 014-4h12l4 4h12a4 4 0 014 4z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
                        </svg>
                        <p class="text-gray-400">
                            <span class="font-semibold text-green-400"><?php echo t('upload_files'); ?></span> 
                            <?php echo t('upload_drag_drop'); ?>
                        </p>
                        <p class="text-xs text-gray-500"><?php echo t('upload_multiple_info'); ?></p>
                        <p class="text-xs text-blue-400 mt-1"><?php echo t('upload_multiple_tip'); ?></p>
                    </div>
                </div>

                <!-- Upload Options -->
                <div class="mt-6 space-y-4">
                    <!-- Expiry Time Selection (NEW) -->
                    <div class="relative">
                        <label class="block text-sm font-medium text-gray-300 mb-2"><?php echo t('expiry_time'); ?></label>
                        <select id="expiry-time" class="w-full bg-gray-700 border-gray-600 rounded-md p-2 text-white focus:ring-2 focus:ring-green-500 focus:border-green-500">
                            <option value="1"><?php echo t('expiry_1h'); ?></option>
                            <option value="6"><?php echo t('expiry_6h'); ?></option>
                            <option value="12"><?php echo t('expiry_12h'); ?></option>
                            <option value="24" selected><?php echo t('expiry_24h'); ?></option>
                            <option value="48"><?php echo t('expiry_48h'); ?></option>
                            <option value="72"><?php echo t('expiry_72h'); ?></option>
                            <option value="168"><?php echo t('expiry_168h'); ?></option>
                        </select>
                    </div>

                    <!-- Password Protection -->
                    <div class="relative flex items-start">
                        <div class="flex items-center h-5">
                            <input id="password-protect" type="checkbox" class="focus:ring-green-500 h-4 w-4 text-green-600 bg-gray-700 border-gray-600 rounded">
                        </div>
                        <div class="ml-3 text-sm">
                            <label for="password-protect" class="font-medium text-gray-300"><?php echo t('password_protect'); ?></label>
                            <p class="text-gray-500"><?php echo t('password_protect_info'); ?></p>
                        </div>
                    </div>
                    <input type="password" id="password-input" placeholder="<?php echo t('password_placeholder'); ?>" class="hidden w-full bg-gray-700 border-gray-600 rounded-md p-2 text-white focus:ring-2 focus:ring-green-500 focus:border-green-500">

                    <!-- One-time Download -->
                    <div class="relative flex items-start">
                        <div class="flex items-center h-5">
                            <input id="one-time-download" type="checkbox" class="focus:ring-green-500 h-4 w-4 text-green-600 bg-gray-700 border-gray-600 rounded">
                        </div>
                        <div class="ml-3 text-sm">
                            <label for="one-time-download" class="font-medium text-gray-300"><?php echo t('one_time_download'); ?></label>
                            <p class="text-gray-500"><?php echo t('one_time_download_info'); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Single File Progress View -->
            <div id="progress-view" class="hidden text-center">
                <p class="font-semibold text-lg mb-2" id="file-name-progress"><?php echo t('file_preparing'); ?></p>
                <p class="text-sm text-gray-400 mb-4" id="file-size-progress"></p>
                <div id="encryption-status" class="text-sm text-yellow-400 mb-4"><?php echo t('encrypting_browser'); ?></div>
                <div class="w-full bg-gray-700 rounded-full h-2.5">
                    <div id="progress-bar" class="bg-green-500 h-2.5 rounded-full transition-all duration-300" style="width: 0%"></div>
                </div>
                <p class="mt-4 text-lg font-bold text-green-400" id="progress-percentage">0%</p>
            </div>

            <!-- Bulk Upload Progress View -->
            <div id="bulk-progress-view" class="hidden">
                <div class="text-center mb-6">
                    <p class="font-semibold text-lg mb-2"><?php echo t('processing_multiple'); ?></p>
                    <p class="text-sm text-gray-400" id="bulk-status">
                        <span data-translate="encrypting_files"><?php echo t('encrypting_files', ['current' => '<span id="current-file-index">0</span>', 'total' => '<span id="total-files">0</span>']); ?></span>
                    </p>
                </div>
                
                <div class="mb-6">
                    <div class="flex justify-between text-sm text-gray-400 mb-1">
                        <span><?php echo t('overall_progress'); ?></span>
                        <span id="overall-percentage">0%</span>
                    </div>
                    <div class="w-full bg-gray-700 rounded-full h-2.5">
                        <div id="overall-progress-bar" class="bg-green-500 h-2.5 rounded-full transition-all duration-300" style="width: 0%"></div>
                    </div>
                </div>

                <div id="file-progress-list" class="space-y-2 max-h-64 overflow-y-auto"></div>
            </div>

            <!-- Single File Success View -->
            <div id="success-view" class="hidden text-center fade-in">
                <h2 class="text-2xl font-bold text-green-400 mb-4"><?php echo t('upload_successful'); ?></h2>
                <p class="text-gray-400 mb-4" id="success-message"><?php echo t('secure_link_valid', ['hours' => '24']); ?></p>
                
                <div class="relative bg-gray-900 rounded-lg p-3 flex items-center mb-4">
                    <input id="download-link" type="text" readonly class="w-full bg-transparent text-gray-300 border-0 focus:ring-0 text-sm pr-12">
                    <button id="copy-button" class="absolute right-2 top-1/2 -translate-y-1/2 p-2 rounded-md bg-gray-700 hover:bg-gray-600 text-gray-300 transition-colors">
                        <svg id="copy-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                            <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                        </svg>
                        <svg id="check-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#2f855a" stroke-width="3" class="hidden">
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                    </button>
                </div>

                <!-- QR Code -->
                <div class="bg-gray-900 p-4 rounded-lg mb-6 flex flex-col items-center">
                    <p class="text-sm text-gray-400 mb-3"><?php echo t('share_qr_code'); ?></p>
                    <div id="qr-code" class="bg-white p-2 rounded-md"></div>
                </div>

                <!-- Countdown -->
                <div class="mb-6 text-center">
                    <p class="text-sm text-gray-400 mb-2"><?php echo t('expires_in', ['time' => '']); ?></p>
                    <div class="flex justify-center items-center gap-2 text-lg font-mono">
                        <div class="countdown-segment px-3 py-2 rounded-md">
                            <span id="countdown-hours">23</span>h
                        </div>
                        <div class="countdown-segment px-3 py-2 rounded-md">
                            <span id="countdown-minutes">59</span>m
                        </div>
                        <div class="countdown-segment px-3 py-2 rounded-md">
                            <span id="countdown-seconds">59</span>s
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex flex-col gap-3">
                    <a id="kill-link-button" href="#" target="_blank" class="w-full bg-red-800/80 hover:bg-red-700 text-white font-bold py-2 px-4 rounded-lg text-center transition-colors">
                        <?php echo t('delete_link_immediately'); ?>
                    </a>
                    <button id="share-another-button" class="w-full bg-green-600 hover:bg-green-500 text-white font-bold py-2 px-4 rounded-lg transition-colors">
                        <?php echo t('share_another_file'); ?>
                    </button>
                </div>
            </div>

            <!-- Bulk Success View -->
            <div id="bulk-success-view" class="hidden text-center fade-in">
                <h2 class="text-2xl font-bold text-green-400 mb-4"><?php echo t('bulk_upload_successful'); ?></h2>
                <p class="text-gray-400 mb-4" id="bulk-success-message"></p>

                <!-- Actions -->
                <div class="flex gap-2 mb-6">
                    <button id="copy-all-links-button" class="flex-1 bg-blue-600 hover:bg-blue-500 text-white font-bold py-2 px-4 rounded-lg text-sm transition-colors">
                        <?php echo t('copy_all_links'); ?>
                    </button>
                    <button id="download-list-button" class="flex-1 bg-purple-600 hover:bg-purple-500 text-white font-bold py-2 px-4 rounded-lg text-sm transition-colors">
                        <?php echo t('download_links_json'); ?>
                    </button>
                </div>

                <!-- Links List -->
                <div id="bulk-downloads-list" class="bg-gray-900 rounded-lg p-4 mb-6 max-h-64 overflow-y-auto space-y-2"></div>

                <!-- Countdown -->
                <div class="mb-6 text-center">
                    <p class="text-sm text-gray-400 mb-2"><?php echo t('expires_in', ['time' => '']); ?></p>
                    <div class="flex justify-center items-center gap-2 text-lg font-mono">
                        <div class="countdown-segment px-3 py-2 rounded-md">
                            <span id="bulk-countdown-hours">23</span>h
                        </div>
                        <div class="countdown-segment px-3 py-2 rounded-md">
                            <span id="bulk-countdown-minutes">59</span>m
                        </div>
                    </div>
                </div>

                <button id="bulk-share-another-button" class="w-full bg-green-600 hover:bg-green-500 text-white font-bold py-2 px-4 rounded-lg transition-colors">
                    <?php echo t('share_another_file'); ?>
                </button>
            </div>

            <!-- Error View -->
            <div id="error-view" class="hidden text-center fade-in">
                <div class="bg-red-900/50 border border-red-700 p-4 rounded-lg">
                    <h2 class="text-xl font-bold text-red-300 mb-2"><?php echo t('error'); ?></h2>
                    <p id="error-message" class="text-red-300 mb-4"><?php echo t('something_went_wrong'); ?></p>
                    <button id="try-again-button" class="bg-gray-600 hover:bg-gray-500 text-white font-bold py-2 px-4 rounded-lg transition-colors">
                        <?php echo t('try_again'); ?>
                    </button>
                </div>
            </div>

        </main>

        <!-- Admin Link -->
        <div class="text-center mt-4">
            <a href="admin.php" class="text-xs text-gray-500 hover:text-gray-400 transition-colors">Admin Panel</a>
        </div>
    </div>

<script>
// Enhanced CactusDrop v0.4.0 with Multi-Language and Extended Expiry
document.addEventListener('DOMContentLoaded', () => {
    // UI Elements
    const uploadView = document.getElementById('upload-view');
    const progressView = document.getElementById('progress-view');
    const bulkProgressView = document.getElementById('bulk-progress-view');
    const successView = document.getElementById('success-view');
    const bulkSuccessView = document.getElementById('bulk-success-view');
    const errorView = document.getElementById('error-view');
    const dropZone = document.getElementById('drop-zone');
    const fileInput = document.getElementById('file-input');
    
    // Form Elements
    const passwordProtectCheckbox = document.getElementById('password-protect');
    const passwordInput = document.getElementById('password-input');
    const oneTimeCheckbox = document.getElementById('one-time-download');
    const expiryTimeSelect = document.getElementById('expiry-time');
    
    // Progress Elements
    const fileNameProgress = document.getElementById('file-name-progress');
    const fileSizeProgress = document.getElementById('file-size-progress');
    const encryptionStatus = document.getElementById('encryption-status');
    const progressBar = document.getElementById('progress-bar');
    const progressPercentage = document.getElementById('progress-percentage');
    
    // Language support
    function t(key, params = {}) {
        let text = window.CACTUSDROP_LANG[key] || key;
        for (const [param, value] of Object.entries(params)) {
            text = text.replace(new RegExp(`\\{\\{${param}\\}\\}`, 'g'), value);
        }
        return text;
    }

    // Enhanced file handling with expiry time
    function getSelectedExpiryHours() {
        return parseInt(expiryTimeSelect.value) || 24;
    }

    // Password protection toggle
    passwordProtectCheckbox.addEventListener('change', () => {
        passwordInput.classList.toggle('hidden', !passwordProtectCheckbox.checked);
        if (passwordProtectCheckbox.checked) {
            passwordInput.focus();
        }
    });

    // Drag & Drop handlers
    dropZone.addEventListener('dragover', (e) => {
        e.preventDefault();
        dropZone.classList.add('dragover');
    });

    dropZone.addEventListener('dragleave', (e) => {
        e.preventDefault();
        dropZone.classList.remove('dragover');
    });

    dropZone.addEventListener('drop', (e) => {
        e.preventDefault();
        dropZone.classList.remove('dragover');
        if (e.dataTransfer.files.length > 0) {
            handleFiles(e.dataTransfer.files);
        }
    });

    fileInput.addEventListener('change', (e) => {
        if (e.target.files.length > 0) {
            handleFiles(e.target.files);
        }
    });

    // File handling logic
    function handleFiles(files) {
        if (files.length === 1) {
            handleSingleFile(files[0]);
        } else {
            handleMultipleFiles(files);
        }
    }

    async function handleSingleFile(file) {
        uploadView.classList.add('hidden');
        progressView.classList.remove('hidden');
        errorView.classList.add('hidden');

        fileNameProgress.textContent = file.name;
        fileSizeProgress.textContent = `${(file.size / 1024 / 1024).toFixed(2)} MB`;
        
        try {
            // Update status messages with translations
            encryptionStatus.textContent = t('encrypting_browser');
            
            const expiryHours = getSelectedExpiryHours();
            const result = await uploadFile(file, expiryHours);
            
            showSuccess(result, expiryHours);
        } catch (error) {
            showError(error.message);
        }
    }

    async function handleMultipleFiles(files) {
        uploadView.classList.add('hidden');
        bulkProgressView.classList.remove('hidden');
        errorView.classList.add('hidden');

        const expiryHours = getSelectedExpiryHours();
        const results = [];
        
        document.getElementById('total-files').textContent = files.length;
        document.getElementById('current-file-index').textContent = '0';

        try {
            for (let i = 0; i < files.length; i++) {
                document.getElementById('current-file-index').textContent = i + 1;
                
                const result = await uploadFile(files[i], expiryHours);
                results.push({
                    filename: files[i].name,
                    success: true,
                    ...result
                });

                const progress = ((i + 1) / files.length) * 100;
                document.getElementById('overall-progress-bar').style.width = progress + '%';
                document.getElementById('overall-percentage').textContent = Math.round(progress) + '%';
            }

            showBulkSuccess(results, expiryHours);
        } catch (error) {
            showError(error.message);
        }
    }

    // Enhanced upload function with expiry support
    async function uploadFile(file, expiryHours = 24) {
        // Encrypt file
        const fileBuffer = await file.arrayBuffer();
        const { key: fileKey, iv: fileIv } = await generateAesKey();
        const encryptedFileBuffer = await encryptData(fileBuffer, fileKey, fileIv);
        const encryptedBlob = new Blob([encryptedFileBuffer]);

        // Generate key fragment
        const exportedFileKey = await crypto.subtle.exportKey("raw", fileKey);
        let keyFragment = `${bufferToBase64(exportedFileKey)}.${bufferToBase64(fileIv)}`;

        // Password protection
        if (passwordProtectCheckbox.checked && passwordInput.value) {
            const password = passwordInput.value;
            const salt = crypto.getRandomValues(new Uint8Array(16));
            const keyIv = crypto.getRandomValues(new Uint8Array(12));
            
            const passwordKey = await deriveKeyFromPassword(password, salt);
            const encryptedKey = await encryptData(exportedFileKey, passwordKey, keyIv);
            
            keyFragment = `p.${bufferToBase64(salt)}.${bufferToBase64(keyIv)}.${bufferToBase64(encryptedKey)}.${bufferToBase64(fileIv)}`;
        }

        // Upload to server
        const formData = new FormData();
        formData.append('file', encryptedBlob, file.name);
        formData.append('expiry_hours', expiryHours.toString());
        if (oneTimeCheckbox.checked) {
            formData.append('oneTimeDownload', 'true');
        }

        const response = await fetch('upload.php', {
            method: 'POST',
            body: formData
        });

        if (!response.ok) {
            throw new Error(t('network_error'));
        }

        const result = await response.json();
        if (result.status !== 'success') {
            throw new Error(result.message || t('upload_failed'));
        }

        const finalUrl = `${result.downloadUrl}#${keyFragment}`;
        
        return {
            downloadUrl: finalUrl,
            deleteUrl: result.deleteUrl,
            expiresAt: result.expiresAt
        };
    }

    function showSuccess(result, expiryHours) {
        progressView.classList.add('hidden');
        successView.classList.remove('hidden');
        
        document.getElementById('download-link').value = result.downloadUrl;
        document.getElementById('kill-link-button').href = result.deleteUrl;
        
        // Update success message with correct expiry time
        const successMessage = document.getElementById('success-message');
        successMessage.textContent = t('secure_link_valid', { hours: expiryHours });
        
        // Generate QR Code
        const qrCodeContainer = document.getElementById('qr-code');
        qrCodeContainer.innerHTML = '';
        const qrImg = document.createElement('img');
        qrImg.src = `https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=${encodeURIComponent(result.downloadUrl)}`;
        qrCodeContainer.appendChild(qrImg);
        
        // Start countdown
        startCountdown(expiryHours * 60 * 60); // Convert to seconds
    }

    function showBulkSuccess(results, expiryHours) {
        bulkProgressView.classList.add('hidden');
        bulkSuccessView.classList.remove('hidden');
        
        const successCount = results.filter(r => r.success).length;
        document.getElementById('bulk-success-message').textContent = t('files_uploaded_count').replace('{{count}}', successCount);
        
        // Populate links list
        const linksList = document.getElementById('bulk-downloads-list');
        linksList.innerHTML = '';
        
        results.forEach((result, index) => {
            if (result.success) {
                const linkItem = document.createElement('div');
                linkItem.className = 'flex items-center gap-2 p-2 bg-gray-800 rounded text-sm';
                linkItem.innerHTML = `
                    <span class="flex-1 truncate text-gray-300">${result.filename}</span>
                    <button onclick="copyToClipboard('${result.downloadUrl}')" class="px-2 py-1 bg-green-600 hover:bg-green-500 text-white rounded text-xs transition-colors">
                        ${t('copy')}
                    </button>
                `;
                linksList.appendChild(linkItem);
            }
        });
        
        // Bulk countdown
        startBulkCountdown(expiryHours * 60 * 60);
    }

    function showError(message) {
        progressView.classList.add('hidden');
        bulkProgressView.classList.add('hidden');
        errorView.classList.remove('hidden');
        
        document.getElementById('error-message').textContent = message;
    }

    // Copy functionality
    function copyToClipboard(text) {
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(text).then(() => {
                showCopyFeedback();
            });
        } else {
            // Fallback
            const textArea = document.createElement('textarea');
            textArea.value = text;
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();
            try {
                document.execCommand('copy');
                showCopyFeedback();
            } catch (err) {
                console.error('Copy failed:', err);
            }
            document.body.removeChild(textArea);
        }
    }

    function showCopyFeedback() {
        const copyIcon = document.getElementById('copy-icon');
        const checkIcon = document.getElementById('check-icon');
        
        copyIcon.classList.add('hidden');
        checkIcon.classList.remove('hidden');
        
        setTimeout(() => {
            copyIcon.classList.remove('hidden');
            checkIcon.classList.add('hidden');
        }, 2000);
    }

    // Event Listeners
    document.getElementById('copy-button').addEventListener('click', () => {
        copyToClipboard(document.getElementById('download-link').value);
    });

    document.getElementById('share-another-button').addEventListener('click', () => {
        location.reload();
    });

    document.getElementById('bulk-share-another-button').addEventListener('click', () => {
        location.reload();
    });

    document.getElementById('try-again-button').addEventListener('click', () => {
        location.reload();
    });

    // Countdown functions
    function startCountdown(totalSeconds) {
        const hoursEl = document.getElementById('countdown-hours');
        const minutesEl = document.getElementById('countdown-minutes');
        const secondsEl = document.getElementById('countdown-seconds');
        
        const interval = setInterval(() => {
            if (totalSeconds <= 0) {
                clearInterval(interval);
                return;
            }
            
            const hours = Math.floor(totalSeconds / 3600);
            const minutes = Math.floor((totalSeconds % 3600) / 60);
            const seconds = totalSeconds % 60;
            
            hoursEl.textContent = hours.toString().padStart(2, '0');
            minutesEl.textContent = minutes.toString().padStart(2, '0');
            secondsEl.textContent = seconds.toString().padStart(2, '0');
            
            totalSeconds--;
        }, 1000);
    }

    function startBulkCountdown(totalSeconds) {
        const hoursEl = document.getElementById('bulk-countdown-hours');
        const minutesEl = document.getElementById('bulk-countdown-minutes');
        
        const interval = setInterval(() => {
            if (totalSeconds <= 0) {
                clearInterval(interval);
                return;
            }
            
            const hours = Math.floor(totalSeconds / 3600);
            const minutes = Math.floor((totalSeconds % 3600) / 60);
            
            hoursEl.textContent = hours.toString().padStart(2, '0');
            minutesEl.textContent = minutes.toString().padStart(2, '0');
            
            totalSeconds -= 60; // Update every minute for bulk
        }, 60000);
    }

    // Crypto functions
    async function generateAesKey() {
        const key = await crypto.subtle.generateKey(
            { name: "AES-GCM", length: 256 },
            true,
            ["encrypt", "decrypt"]
        );
        const iv = crypto.getRandomValues(new Uint8Array(12));
        return { key, iv };
    }

    async function encryptData(data, key, iv) {
        return await crypto.subtle.encrypt(
            { name: "AES-GCM", iv: iv },
            key,
            data
        );
    }

    async function deriveKeyFromPassword(password, salt) {
        const encoder = new TextEncoder();
        const keyMaterial = await crypto.subtle.importKey(
            "raw",
            encoder.encode(password),
            { name: "PBKDF2" },
            false,
            ["deriveBits", "deriveKey"]
        );
        
        return await crypto.subtle.deriveKey(
            {
                name: "PBKDF2",
                salt: salt,
                iterations: 100000,
                hash: "SHA-256"
            },
            keyMaterial,
            { name: "AES-GCM", length: 256 },
            true,
            ["encrypt", "decrypt"]
        );
    }

    const bufferToBase64 = (buffer) => {
        return btoa(String.fromCharCode(...new Uint8Array(buffer)));
    };

    // Global functions
    window.copyToClipboard = copyToClipboard;
});
</script>

    <!-- Bottom Navigation -->
    <div class="fixed bottom-4 right-4 flex items-center gap-2">
        <!-- Language Switch Button -->
        <button id="lang-switch-btn" onclick="toggleLanguage()" class="bg-gray-700 hover:bg-gray-600 text-white p-2 rounded-lg transition-colors flex items-center gap-2">
            <span id="current-lang-flag"><?php echo getCurrentLang() === 'de' ? '🇩🇪' : '🇺🇸'; ?></span>
            <span id="current-lang-text"><?php echo getCurrentLang() === 'de' ? 'DE' : 'EN'; ?></span>
        </button>
        
        <!-- Privacy Link -->
        <a href="privacy_policy.php" class="bg-gray-600 hover:bg-gray-500 text-white p-2 rounded-lg transition-colors" title="<?php echo t('privacy_policy'); ?>">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
            </svg>
        </a>
        
        <!-- Admin Panel Link -->
        <a href="admin.php" class="bg-blue-600 hover:bg-blue-500 text-white p-2 rounded-lg transition-colors" title="<?php echo t('admin_panel'); ?>">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
            </svg>
        </a>
    </div>

    <script>
    function toggleLanguage() {
        const currentLang = '<?php echo getCurrentLang(); ?>';
        const newLang = currentLang === 'de' ? 'en' : 'de';
        const url = new URL(window.location);
        url.searchParams.set('lang', newLang);
        window.location = url.toString();
    }
    </script>

</body>
</html>