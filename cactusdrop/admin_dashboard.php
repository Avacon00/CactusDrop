<?php
// Dashboard-Statistiken laden
$todayStats = getUploadStats('today');
$weekStats = getUploadStats('week');
$monthStats = getUploadStats('month');
$allTimeStats = getUploadStats('all');

// Aktuelle System-Info
$conn = get_db_connection();

// Aktive Dateien
$result = $conn->query("SELECT COUNT(*) as active_files FROM files WHERE delete_at > NOW()");
$activeFiles = $result->fetch_assoc()['active_files'];

// Größe aktive Dateien
$result = $conn->query("SELECT SUM(file_size) as total_size FROM files WHERE delete_at > NOW()");
$activeSize = $result->fetch_assoc()['total_size'] ?? 0;

// Top-Upload-IPs heute
$result = $conn->query("SELECT upload_ip, COUNT(*) as uploads FROM files WHERE DATE(created_at) = CURDATE() GROUP BY upload_ip ORDER BY uploads DESC LIMIT 5");
$topIPs = $result->fetch_all(MYSQLI_ASSOC);

// Letzte Security-Events
$recentLogs = getSecurityLogs(10);

$conn->close();

function formatBytes($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    for ($i = 0; $bytes > 1024; $i++) {
        $bytes /= 1024;
    }
    return round($bytes, $precision) . ' ' . $units[$i];
}
?>

<!-- Dashboard Grid -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <!-- Today Stats -->
    <div class="bg-gray-800 rounded-xl p-6 border border-gray-700">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-400 text-sm">Heute</p>
                <p class="text-3xl font-bold text-green-400"><?php echo number_format($todayStats['uploads']); ?></p>
                <p class="text-gray-500 text-xs">Uploads</p>
            </div>
            <div class="bg-green-500/20 p-3 rounded-lg">
                <svg class="w-6 h-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                </svg>
            </div>
        </div>
        <div class="mt-2 text-xs text-gray-500">
            <?php echo formatBytes($todayStats['total_size']); ?> • <?php echo $todayStats['unique_ips']; ?> IPs
        </div>
    </div>

    <!-- Week Stats -->
    <div class="bg-gray-800 rounded-xl p-6 border border-gray-700">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-400 text-sm">7 Tage</p>
                <p class="text-3xl font-bold text-blue-400"><?php echo number_format($weekStats['uploads']); ?></p>
                <p class="text-gray-500 text-xs">Uploads</p>
            </div>
            <div class="bg-blue-500/20 p-3 rounded-lg">
                <svg class="w-6 h-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
            </div>
        </div>
        <div class="mt-2 text-xs text-gray-500">
            <?php echo formatBytes($weekStats['total_size']); ?> • <?php echo $weekStats['unique_ips']; ?> IPs
        </div>
    </div>

    <!-- Active Files -->
    <div class="bg-gray-800 rounded-xl p-6 border border-gray-700">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-400 text-sm">Aktive Dateien</p>
                <p class="text-3xl font-bold text-yellow-400"><?php echo number_format($activeFiles); ?></p>
                <p class="text-gray-500 text-xs">Nicht abgelaufen</p>
            </div>
            <div class="bg-yellow-500/20 p-3 rounded-lg">
                <svg class="w-6 h-6 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
            </div>
        </div>
        <div class="mt-2 text-xs text-gray-500">
            <?php echo formatBytes($activeSize); ?> Speicher
        </div>
    </div>

    <!-- All Time -->
    <div class="bg-gray-800 rounded-xl p-6 border border-gray-700">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-400 text-sm">Gesamt</p>
                <p class="text-3xl font-bold text-purple-400"><?php echo number_format($allTimeStats['uploads']); ?></p>
                <p class="text-gray-500 text-xs">Uploads</p>
            </div>
            <div class="bg-purple-500/20 p-3 rounded-lg">
                <svg class="w-6 h-6 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                </svg>
            </div>
        </div>
        <div class="mt-2 text-xs text-gray-500">
            <?php echo formatBytes($allTimeStats['total_size']); ?> • <?php echo $allTimeStats['unique_ips']; ?> IPs
        </div>
    </div>
</div>

<!-- Charts und Details -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <!-- Upload-Verlauf Chart -->
    <div class="bg-gray-800 rounded-xl p-6 border border-gray-700">
        <h3 class="text-lg font-semibold mb-4 text-white">Upload-Verlauf (7 Tage)</h3>
        <div class="h-64">
            <canvas id="uploadsChart"></canvas>
        </div>
    </div>

    <!-- Top Upload-IPs -->
    <div class="bg-gray-800 rounded-xl p-6 border border-gray-700">
        <h3 class="text-lg font-semibold mb-4 text-white">Top Upload-IPs (Heute)</h3>
        <div class="space-y-3">
            <?php if (empty($topIPs)): ?>
                <p class="text-gray-500 text-center py-8">Keine Uploads heute</p>
            <?php else: ?>
                <?php foreach ($topIPs as $ip): ?>
                    <div class="flex items-center justify-between py-2">
                        <div class="flex items-center gap-3">
                            <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                            <span class="font-mono text-sm text-gray-300"><?php echo htmlspecialchars($ip['upload_ip'] ?? 'Unknown'); ?></span>
                        </div>
                        <span class="text-green-400 font-semibold"><?php echo $ip['uploads']; ?></span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Recent Security Events -->
<div class="bg-gray-800 rounded-xl p-6 border border-gray-700">
    <h3 class="text-lg font-semibold mb-4 text-white">Letzte Security-Events</h3>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-gray-400 border-b border-gray-700">
                    <th class="text-left py-2">Zeit</th>
                    <th class="text-left py-2">Event</th>
                    <th class="text-left py-2">IP</th>
                    <th class="text-left py-2">Severity</th>
                    <th class="text-left py-2">Details</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recentLogs)): ?>
                    <tr>
                        <td colspan="5" class="text-center py-8 text-gray-500">Keine Security-Events</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($recentLogs as $log): ?>
                        <tr class="border-b border-gray-700/50 hover:bg-gray-700/50">
                            <td class="py-2 text-gray-400">
                                <?php echo date('H:i:s', strtotime($log['created_at'])); ?>
                            </td>
                            <td class="py-2">
                                <span class="px-2 py-1 rounded-full text-xs bg-blue-500/20 text-blue-400">
                                    <?php echo htmlspecialchars($log['event_type']); ?>
                                </span>
                            </td>
                            <td class="py-2 font-mono text-gray-300">
                                <?php echo htmlspecialchars($log['ip_address']); ?>
                            </td>
                            <td class="py-2">
                                <?php
                                $severityColors = [
                                    'low' => 'bg-green-500/20 text-green-400',
                                    'medium' => 'bg-yellow-500/20 text-yellow-400',
                                    'high' => 'bg-orange-500/20 text-orange-400',
                                    'critical' => 'bg-red-500/20 text-red-400'
                                ];
                                $colorClass = $severityColors[$log['severity']] ?? 'bg-gray-500/20 text-gray-400';
                                ?>
                                <span class="px-2 py-1 rounded-full text-xs <?php echo $colorClass; ?>">
                                    <?php echo ucfirst($log['severity']); ?>
                                </span>
                            </td>
                            <td class="py-2 text-gray-400 max-w-xs truncate">
                                <?php echo htmlspecialchars($log['details'] ?? ''); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Chart.js Script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Upload-Verlauf Chart
    const ctx = document.getElementById('uploadsChart').getContext('2d');
    
    // Daten für die letzten 7 Tage generieren
    const last7Days = [];
    const uploadData = [];
    
    for (let i = 6; i >= 0; i--) {
        const date = new Date();
        date.setDate(date.getDate() - i);
        last7Days.push(date.toLocaleDateString('de-DE', { weekday: 'short', month: 'short', day: 'numeric' }));
        // Hier würden normalerweise echte Daten aus der DB kommen
        uploadData.push(Math.floor(Math.random() * 50) + 10);
    }
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: last7Days,
            datasets: [{
                label: 'Uploads',
                data: uploadData,
                borderColor: '#10b981',
                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                borderWidth: 2,
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                x: {
                    grid: {
                        color: '#374151',
                        borderColor: '#374151'
                    },
                    ticks: {
                        color: '#9CA3AF'
                    }
                },
                y: {
                    grid: {
                        color: '#374151',
                        borderColor: '#374151'
                    },
                    ticks: {
                        color: '#9CA3AF'
                    }
                }
            }
        }
    });
});
</script>