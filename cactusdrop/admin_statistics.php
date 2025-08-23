<?php
/**
 * CactusDrop v0.4.0 - Statistics & Analytics
 * 
 * Upload-Statistiken, Nutzungsanalyse, Performance-Metriken
 */

$conn = get_db_connection();

// Statistiken sammeln
$stats = [];

try {
    // Upload-Statistiken
    $result = $conn->query("SELECT 
        COUNT(*) as total_uploads,
        COUNT(CASE WHEN created_at >= CURDATE() THEN 1 END) as today_uploads,
        COUNT(CASE WHEN created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN 1 END) as week_uploads,
        COUNT(CASE WHEN created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) THEN 1 END) as month_uploads,
        COUNT(CASE WHEN is_onetime = 1 THEN 1 END) as onetime_uploads
    FROM files");
    $uploadStats = $result->fetch_assoc();
    
    // File-Größen Statistiken
    $result = $conn->query("SELECT 
        COALESCE(SUM(CASE WHEN COLUMN_EXISTS('files', 'file_size') THEN file_size ELSE 0 END), 0) as total_size,
        COALESCE(AVG(CASE WHEN COLUMN_EXISTS('files', 'file_size') THEN file_size ELSE 0 END), 0) as avg_size,
        COALESCE(MAX(CASE WHEN COLUMN_EXISTS('files', 'file_size') THEN file_size ELSE 0 END), 0) as max_size
    FROM files");
    $sizeStats = $result->fetch_assoc();
    
    // Security-Statistiken
    $result = $conn->query("SELECT 
        COUNT(*) as total_events,
        COUNT(CASE WHEN created_at >= CURDATE() THEN 1 END) as today_events,
        COUNT(CASE WHEN severity = 'high' THEN 1 END) as high_severity,
        COUNT(CASE WHEN severity = 'medium' THEN 1 END) as medium_severity,
        COUNT(CASE WHEN severity = 'low' THEN 1 END) as low_severity
    FROM security_logs");
    $securityStats = $result->fetch_assoc();
    
    // Admin-Aktivitäten (falls Tabelle existiert)
    $adminStats = ['total_logins' => 0, 'active_sessions' => 0];
    $result = $conn->query("SHOW TABLES LIKE 'admin_sessions'");
    if ($result->num_rows > 0) {
        $result = $conn->query("SELECT 
            COUNT(*) as total_logins,
            COUNT(CASE WHEN expires_at > NOW() AND is_active = 1 THEN 1 END) as active_sessions
        FROM admin_sessions");
        $adminStats = $result->fetch_assoc();
    }
    
    // Top-Dateitypen
    $topExtensions = [];
    $result = $conn->query("SELECT 
        SUBSTRING_INDEX(original_filename, '.', -1) as extension,
        COUNT(*) as count
    FROM files 
    WHERE original_filename LIKE '%.%'
    GROUP BY extension 
    ORDER BY count DESC 
    LIMIT 5");
    while ($row = $result->fetch_assoc()) {
        $topExtensions[] = $row;
    }
    
    // Wöchentliche Upload-Trends
    $weeklyTrends = [];
    $result = $conn->query("SELECT 
        DATE(created_at) as date,
        COUNT(*) as uploads
    FROM files 
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    GROUP BY DATE(created_at)
    ORDER BY date ASC");
    while ($row = $result->fetch_assoc()) {
        $weeklyTrends[] = $row;
    }
    
} catch (Exception $e) {
    error_log("Statistics error: " . $e->getMessage());
    // Fallback-Werte
    $uploadStats = ['total_uploads' => 0, 'today_uploads' => 0, 'week_uploads' => 0, 'month_uploads' => 0, 'onetime_uploads' => 0];
    $sizeStats = ['total_size' => 0, 'avg_size' => 0, 'max_size' => 0];
    $securityStats = ['total_events' => 0, 'today_events' => 0, 'high_severity' => 0, 'medium_severity' => 0, 'low_severity' => 0];
    $adminStats = ['total_logins' => 0, 'active_sessions' => 0];
    $topExtensions = [];
    $weeklyTrends = [];
}

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
    
    <!-- Key Metrics -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        
        <!-- Total Uploads -->
        <div class="bg-gradient-to-r from-blue-600 to-blue-800 rounded-xl p-4 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-blue-100 text-sm"><?php echo t('total_uploads', 'Gesamt Uploads'); ?></p>
                    <p class="text-2xl font-bold"><?php echo number_format($uploadStats['total_uploads']); ?></p>
                    <p class="text-xs text-blue-100 mt-1">
                        +<?php echo number_format($uploadStats['today_uploads']); ?> <?php echo t('today', 'heute'); ?>
                    </p>
                </div>
                <div class="bg-white/20 p-2 rounded-lg">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"/>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Storage Used -->
        <div class="bg-gradient-to-r from-purple-600 to-purple-800 rounded-xl p-4 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-purple-100 text-sm"><?php echo t('storage_used', 'Speicher verwendet'); ?></p>
                    <p class="text-2xl font-bold"><?php echo formatFileSize($sizeStats['total_size']); ?></p>
                    <p class="text-xs text-purple-100 mt-1">
                        Ø <?php echo formatFileSize($sizeStats['avg_size']); ?> <?php echo t('per_file', 'pro Datei'); ?>
                    </p>
                </div>
                <div class="bg-white/20 p-2 rounded-lg">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"/>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Security Events -->
        <div class="bg-gradient-to-r from-green-600 to-green-800 rounded-xl p-4 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-green-100 text-sm"><?php echo t('security_events', 'Security Events'); ?></p>
                    <p class="text-2xl font-bold"><?php echo number_format($securityStats['total_events']); ?></p>
                    <p class="text-xs text-green-100 mt-1">
                        <?php echo number_format($securityStats['high_severity']); ?> <?php echo t('high_priority', 'hohe Priorität'); ?>
                    </p>
                </div>
                <div class="bg-white/20 p-2 rounded-lg">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Active Sessions -->
        <div class="bg-gradient-to-r from-yellow-600 to-yellow-800 rounded-xl p-4 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-yellow-100 text-sm"><?php echo t('admin_sessions', 'Admin Sessions'); ?></p>
                    <p class="text-2xl font-bold"><?php echo number_format($adminStats['active_sessions']); ?></p>
                    <p class="text-xs text-yellow-100 mt-1">
                        <?php echo number_format($adminStats['total_logins']); ?> <?php echo t('total_logins', 'Gesamt-Logins'); ?>
                    </p>
                </div>
                <div class="bg-white/20 p-2 rounded-lg">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Detailed Statistics -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        
        <!-- Upload Trends -->
        <div class="bg-gray-800 rounded-xl p-6 border border-gray-700">
            <h3 class="text-lg font-semibold text-white mb-4"><?php echo t('upload_trends', 'Upload-Trends'); ?></h3>
            
            <div class="space-y-4">
                <div class="flex justify-between items-center">
                    <span class="text-gray-300"><?php echo t('last_7_days', 'Letzte 7 Tage'); ?></span>
                    <span class="text-2xl font-bold text-blue-400"><?php echo number_format($uploadStats['week_uploads']); ?></span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-300"><?php echo t('last_30_days', 'Letzte 30 Tage'); ?></span>
                    <span class="text-2xl font-bold text-green-400"><?php echo number_format($uploadStats['month_uploads']); ?></span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-300"><?php echo t('onetime_downloads', 'Einmalig-Downloads'); ?></span>
                    <span class="text-2xl font-bold text-purple-400"><?php echo number_format($uploadStats['onetime_uploads']); ?></span>
                </div>
            </div>

            <!-- Weekly Chart (simplified) -->
            <div class="mt-6">
                <h4 class="text-sm font-medium text-gray-400 mb-3"><?php echo t('daily_uploads_week', 'Tägliche Uploads (Woche)'); ?></h4>
                <div class="flex items-end gap-2 h-20">
                    <?php for ($i = 6; $i >= 0; $i--): ?>
                        <?php 
                        $date = date('Y-m-d', strtotime("-$i days"));
                        $uploads = 0;
                        foreach ($weeklyTrends as $trend) {
                            if ($trend['date'] === $date) {
                                $uploads = $trend['uploads'];
                                break;
                            }
                        }
                        $height = $uploads > 0 ? max(8, ($uploads / max($uploadStats['week_uploads'], 1)) * 64) : 4;
                        ?>
                        <div class="flex-1 bg-blue-500 rounded-t" style="height: <?php echo $height; ?>px" 
                             title="<?php echo date('d.m', strtotime($date)); ?>: <?php echo $uploads; ?> <?php echo t('uploads', 'Uploads'); ?>">
                        </div>
                    <?php endfor; ?>
                </div>
                <div class="flex justify-between text-xs text-gray-500 mt-2">
                    <span><?php echo date('d.m', strtotime('-6 days')); ?></span>
                    <span><?php echo t('today', 'Heute'); ?></span>
                </div>
            </div>
        </div>

        <!-- File Types -->
        <div class="bg-gray-800 rounded-xl p-6 border border-gray-700">
            <h3 class="text-lg font-semibold text-white mb-4"><?php echo t('popular_file_types', 'Beliebte Dateitypen'); ?></h3>
            
            <div class="space-y-3">
                <?php foreach ($topExtensions as $index => $ext): ?>
                    <?php 
                    $percentage = $uploadStats['total_uploads'] > 0 ? ($ext['count'] / $uploadStats['total_uploads']) * 100 : 0;
                    $colors = ['bg-blue-500', 'bg-green-500', 'bg-purple-500', 'bg-yellow-500', 'bg-red-500'];
                    $color = $colors[$index] ?? 'bg-gray-500';
                    ?>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-3 h-3 <?php echo $color; ?> rounded-full"></div>
                            <span class="text-gray-300 uppercase font-mono text-sm"><?php echo htmlspecialchars($ext['extension']); ?></span>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="w-24 bg-gray-700 rounded-full h-2">
                                <div class="<?php echo $color; ?> h-2 rounded-full" style="width: <?php echo min(100, $percentage); ?>%"></div>
                            </div>
                            <span class="text-white font-semibold text-sm w-8 text-right"><?php echo $ext['count']; ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <?php if (empty($topExtensions)): ?>
                    <p class="text-gray-400 text-center py-4"><?php echo t('no_data_available', 'Keine Daten verfügbar'); ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Security Overview -->
    <div class="bg-gray-800 rounded-xl p-6 border border-gray-700">
        <h3 class="text-lg font-semibold text-white mb-4"><?php echo t('security_overview', 'Sicherheitsübersicht'); ?></h3>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-red-900/30 border border-red-700 rounded-lg p-4">
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-3 h-3 bg-red-500 rounded-full"></div>
                    <span class="text-red-300 font-medium"><?php echo t('high_severity', 'Hohe Priorität'); ?></span>
                </div>
                <p class="text-2xl font-bold text-red-400"><?php echo number_format($securityStats['high_severity']); ?></p>
                <p class="text-xs text-red-300/70"><?php echo t('critical_events', 'Kritische Ereignisse'); ?></p>
            </div>
            
            <div class="bg-yellow-900/30 border border-yellow-700 rounded-lg p-4">
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-3 h-3 bg-yellow-500 rounded-full"></div>
                    <span class="text-yellow-300 font-medium"><?php echo t('medium_severity', 'Mittlere Priorität'); ?></span>
                </div>
                <p class="text-2xl font-bold text-yellow-400"><?php echo number_format($securityStats['medium_severity']); ?></p>
                <p class="text-xs text-yellow-300/70"><?php echo t('warning_events', 'Warnungen'); ?></p>
            </div>
            
            <div class="bg-green-900/30 border border-green-700 rounded-lg p-4">
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                    <span class="text-green-300 font-medium"><?php echo t('low_severity', 'Niedrige Priorität'); ?></span>
                </div>
                <p class="text-2xl font-bold text-green-400"><?php echo number_format($securityStats['low_severity']); ?></p>
                <p class="text-xs text-green-300/70"><?php echo t('info_events', 'Info-Ereignisse'); ?></p>
            </div>
        </div>
    </div>

    <!-- System Information -->
    <div class="bg-gray-800 rounded-xl p-6 border border-gray-700">
        <h3 class="text-lg font-semibold text-white mb-4"><?php echo t('system_information', 'System-Informationen'); ?></h3>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 text-sm">
            <div>
                <p class="text-gray-400"><?php echo t('php_version', 'PHP Version'); ?></p>
                <p class="text-white font-semibold"><?php echo PHP_VERSION; ?></p>
            </div>
            <div>
                <p class="text-gray-400"><?php echo t('server_software', 'Server Software'); ?></p>
                <p class="text-white font-semibold"><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?></p>
            </div>
            <div>
                <p class="text-gray-400"><?php echo t('upload_max_filesize', 'Max Upload-Größe'); ?></p>
                <p class="text-white font-semibold"><?php echo ini_get('upload_max_filesize'); ?></p>
            </div>
            <div>
                <p class="text-gray-400"><?php echo t('memory_limit', 'Memory Limit'); ?></p>
                <p class="text-white font-semibold"><?php echo ini_get('memory_limit'); ?></p>
            </div>
        </div>
    </div>
</div>