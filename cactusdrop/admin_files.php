<?php
/**
 * CactusDrop v0.4.0 - File Management
 * 
 * Verwaltet hochgeladene Dateien - Anzeige, Löschen, Statistiken
 */

// File-Handler
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['file_action'])) {
    $conn = get_db_connection();
    
    switch ($_POST['file_action']) {
        case 'delete_file':
            $fileId = $_POST['file_id'] ?? '';
            
            if ($fileId) {
                // File aus DB und Filesystem löschen
                $stmt = $conn->prepare("SELECT * FROM files WHERE id = ?");
                $stmt->bind_param('s', $fileId);
                $stmt->execute();
                $file = $stmt->get_result()->fetch_assoc();
                $stmt->close();
                
                if ($file) {
                    // Physische Datei löschen
                    $uploadDir = defined('UPLOAD_DIR') ? UPLOAD_DIR : './uploads/';
                    $filePath = $uploadDir . $fileId;
                    if (file_exists($filePath)) {
                        unlink($filePath);
                    }
                    
                    // DB-Eintrag löschen
                    $stmt = $conn->prepare("DELETE FROM files WHERE id = ?");
                    $stmt->bind_param('s', $fileId);
                    $stmt->execute();
                    $stmt->close();
                    
                    GDPRAdminSecurity::logSecurityEvent('admin_file_deleted', "File deleted: $fileId ({$file['original_filename']})", 'low');
                    $success = "✅ " . t('file_deleted_success', 'Datei erfolgreich gelöscht.');
                }
            }
            break;
    }
    
    $conn->close();
}

// Dateien laden mit Paginierung
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

$conn = get_db_connection();

// Gesamt-Anzahl für Paginierung
$result = $conn->query("SELECT COUNT(*) as total FROM files");
$totalFiles = $result->fetch_assoc()['total'];
$totalPages = ceil($totalFiles / $perPage);

// Dateien laden
$stmt = $conn->prepare("SELECT * FROM files ORDER BY created_at DESC LIMIT ? OFFSET ?");
$stmt->bind_param('ii', $perPage, $offset);
$stmt->execute();
$files = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// File-Statistiken
$stats = [];
$result = $conn->query("SELECT 
    COUNT(*) as total_files,
    COUNT(CASE WHEN is_onetime = 1 THEN 1 END) as onetime_files,
    COUNT(CASE WHEN delete_at < NOW() THEN 1 END) as expired_files,
    SUM(CASE WHEN COLUMN_EXISTS('files', 'file_size') THEN file_size ELSE 0 END) as total_size
FROM files");
$stats = $result->fetch_assoc();

$conn->close();

function formatFileSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' B';
    }
}

function COLUMN_EXISTS($table, $column) {
    // Vereinfachte Prüfung - in production sollte das dynamisch geprüft werden
    return true;
}
?>

<div class="space-y-6">
    
    <?php if (isset($success)): ?>
        <div class="bg-green-900/50 border border-green-700 text-green-300 px-4 py-3 rounded-lg fade-in">
            <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>

    <!-- File Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-gray-800 rounded-xl p-4 border border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-400 text-sm"><?php echo t('total_files', 'Gesamt Dateien'); ?></p>
                    <p class="text-2xl font-bold text-blue-400"><?php echo number_format($stats['total_files']); ?></p>
                </div>
                <div class="bg-blue-500/20 p-2 rounded-lg">
                    <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-gray-800 rounded-xl p-4 border border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-400 text-sm"><?php echo t('onetime_files', 'Einmalig-Downloads'); ?></p>
                    <p class="text-2xl font-bold text-green-400"><?php echo number_format($stats['onetime_files']); ?></p>
                </div>
                <div class="bg-green-500/20 p-2 rounded-lg">
                    <svg class="w-5 h-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-gray-800 rounded-xl p-4 border border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-400 text-sm"><?php echo t('expired_files', 'Abgelaufene Dateien'); ?></p>
                    <p class="text-2xl font-bold text-red-400"><?php echo number_format($stats['expired_files']); ?></p>
                </div>
                <div class="bg-red-500/20 p-2 rounded-lg">
                    <svg class="w-5 h-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-gray-800 rounded-xl p-4 border border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-400 text-sm"><?php echo t('storage_used', 'Speicher verwendet'); ?></p>
                    <p class="text-2xl font-bold text-purple-400"><?php echo formatFileSize($stats['total_size'] ?? 0); ?></p>
                </div>
                <div class="bg-purple-500/20 p-2 rounded-lg">
                    <svg class="w-5 h-5 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Files Table -->
    <div class="bg-gray-800 rounded-xl border border-gray-700 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-700">
            <h3 class="text-lg font-semibold text-white"><?php echo t('uploaded_files', 'Hochgeladene Dateien'); ?></h3>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-700">
                    <tr class="text-left text-xs font-medium text-gray-300 uppercase tracking-wider">
                        <th class="px-4 py-3"><?php echo t('filename', 'Dateiname'); ?></th>
                        <th class="px-4 py-3"><?php echo t('uploaded', 'Hochgeladen'); ?></th>
                        <th class="px-4 py-3"><?php echo t('expires', 'Läuft ab'); ?></th>
                        <th class="px-4 py-3"><?php echo t('type', 'Typ'); ?></th>
                        <th class="px-4 py-3"><?php echo t('actions', 'Aktionen'); ?></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-600">
                    <?php foreach ($files as $file): ?>
                        <tr class="hover:bg-gray-700/50">
                            <td class="px-4 py-3 text-sm">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-8 w-8 bg-gray-600 rounded-lg flex items-center justify-center mr-3">
                                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-white font-medium truncate max-w-xs"><?php echo htmlspecialchars($file['original_filename']); ?></p>
                                        <p class="text-xs text-gray-400"><?php echo $file['id']; ?></p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-300">
                                <?php echo date('d.m.Y H:i', strtotime($file['created_at'])); ?>
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <?php 
                                $isExpired = strtotime($file['delete_at']) < time();
                                $expiredClass = $isExpired ? 'text-red-400' : 'text-gray-300';
                                ?>
                                <span class="<?php echo $expiredClass; ?>">
                                    <?php echo date('d.m.Y H:i', strtotime($file['delete_at'])); ?>
                                    <?php if ($isExpired): ?>
                                        <span class="text-xs">(<?php echo t('expired', 'Abgelaufen'); ?>)</span>
                                    <?php endif; ?>
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <?php if ($file['is_onetime']): ?>
                                    <span class="bg-green-900 text-green-300 px-2 py-1 rounded-full text-xs">
                                        <?php echo t('onetime', 'Einmalig'); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="bg-blue-900 text-blue-300 px-2 py-1 rounded-full text-xs">
                                        <?php echo t('persistent', 'Persistent'); ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <div class="flex gap-2">
                                    <a href="download.php?id=<?php echo $file['id']; ?>" 
                                       class="text-blue-400 hover:text-blue-300 transition-colors" 
                                       title="<?php echo t('view_file', 'Datei anzeigen'); ?>">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                    </a>
                                    
                                    <form method="POST" class="inline" 
                                          onsubmit="return confirm('<?php echo t('confirm_delete_file', 'Datei wirklich löschen?'); ?>')">
                                        <input type="hidden" name="file_action" value="delete_file">
                                        <input type="hidden" name="file_id" value="<?php echo $file['id']; ?>">
                                        <button type="submit" 
                                                class="text-red-400 hover:text-red-300 transition-colors"
                                                title="<?php echo t('delete_file', 'Datei löschen'); ?>">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <div class="px-6 py-3 border-t border-gray-700 flex items-center justify-between">
                <p class="text-sm text-gray-400">
                    <?php echo t('showing_files', 'Zeige Dateien'); ?> <?php echo (($page - 1) * $perPage + 1); ?>-<?php echo min($page * $perPage, $totalFiles); ?> <?php echo t('of', 'von'); ?> <?php echo $totalFiles; ?>
                </p>
                
                <div class="flex gap-2">
                    <?php if ($page > 1): ?>
                        <a href="?action=files&page=<?php echo $page - 1; ?>" 
                           class="bg-gray-700 hover:bg-gray-600 text-white px-3 py-1 rounded text-sm transition-colors">
                            <?php echo t('previous', 'Zurück'); ?>
                        </a>
                    <?php endif; ?>
                    
                    <span class="text-gray-400 px-3 py-1 text-sm">
                        <?php echo t('page', 'Seite'); ?> <?php echo $page; ?> <?php echo t('of', 'von'); ?> <?php echo $totalPages; ?>
                    </span>
                    
                    <?php if ($page < $totalPages): ?>
                        <a href="?action=files&page=<?php echo $page + 1; ?>" 
                           class="bg-gray-700 hover:bg-gray-600 text-white px-3 py-1 rounded text-sm transition-colors">
                            <?php echo t('next', 'Weiter'); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>