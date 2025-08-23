<?php
// Security Logs anzeigen
$filter = $_GET['filter'] ?? 'all';
$limit = (int)($_GET['limit'] ?? 100);
$page = (int)($_GET['page'] ?? 1);
$offset = ($page - 1) * $limit;

$conn = get_db_connection();

// Filter-Query erstellen
$whereClause = '';
$params = [];
$paramTypes = '';

if ($filter !== 'all') {
    $whereClause = 'WHERE event_type = ?';
    $params[] = $filter;
    $paramTypes .= 's';
}

// Gesamt-Anzahl für Pagination
$countQuery = "SELECT COUNT(*) as total FROM security_logs $whereClause";
if ($params) {
    $countStmt = $conn->prepare($countQuery);
    $countStmt->bind_param($paramTypes, ...$params);
    $countStmt->execute();
    $totalLogs = $countStmt->get_result()->fetch_assoc()['total'];
    $countStmt->close();
} else {
    $totalLogs = $conn->query($countQuery)->fetch_assoc()['total'];
}

// Logs laden
$query = "SELECT sl.*, au.username as admin_username 
          FROM security_logs sl 
          LEFT JOIN admin_users au ON sl.admin_user_id = au.id 
          $whereClause 
          ORDER BY sl.created_at DESC 
          LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$paramTypes .= 'ii';

$stmt = $conn->prepare($query);
$stmt->bind_param($paramTypes, ...$params);
$stmt->execute();
$logs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Event-Typen für Filter
$eventTypes = $conn->query("SELECT DISTINCT event_type, COUNT(*) as count FROM security_logs GROUP BY event_type ORDER BY count DESC")->fetch_all(MYSQLI_ASSOC);

$conn->close();

// Pagination
$totalPages = ceil($totalLogs / $limit);
?>

<div class="max-w-7xl">
    <!-- Filter und Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <!-- Gesamt-Logs -->
        <div class="bg-gray-800 rounded-xl p-4 border border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-400 text-sm">Gesamt-Events</p>
                    <p class="text-2xl font-bold text-blue-400"><?php echo number_format($totalLogs); ?></p>
                </div>
                <div class="bg-blue-500/20 p-2 rounded-lg">
                    <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Kritische Events -->
        <?php
        $criticalCount = 0;
        foreach ($eventTypes as $et) {
            if (strpos($et['event_type'], 'failed') !== false || strpos($et['event_type'], 'blocked') !== false) {
                $criticalCount += $et['count'];
            }
        }
        ?>
        <div class="bg-gray-800 rounded-xl p-4 border border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-400 text-sm">Kritische Events</p>
                    <p class="text-2xl font-bold text-red-400"><?php echo number_format($criticalCount); ?></p>
                </div>
                <div class="bg-red-500/20 p-2 rounded-lg">
                    <svg class="w-5 h-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L5.268 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Login-Events -->
        <?php
        $loginCount = 0;
        foreach ($eventTypes as $et) {
            if (strpos($et['event_type'], 'login') !== false) {
                $loginCount += $et['count'];
            }
        }
        ?>
        <div class="bg-gray-800 rounded-xl p-4 border border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-400 text-sm">Login-Events</p>
                    <p class="text-2xl font-bold text-green-400"><?php echo number_format($loginCount); ?></p>
                </div>
                <div class="bg-green-500/20 p-2 rounded-lg">
                    <svg class="w-5 h-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m0 0a2 2 0 012 2m-2-2a2 2 0 00-2 2m2-2a2 2 0 012 2M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Heute -->
        <?php
        $todayCount = $conn->query("SELECT COUNT(*) as count FROM security_logs WHERE DATE(created_at) = CURDATE()")->fetch_assoc()['count'] ?? 0;
        ?>
        <div class="bg-gray-800 rounded-xl p-4 border border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-400 text-sm">Heute</p>
                    <p class="text-2xl font-bold text-yellow-400"><?php echo number_format($todayCount); ?></p>
                </div>
                <div class="bg-yellow-500/20 p-2 rounded-lg">
                    <svg class="w-5 h-5 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Controls -->
    <div class="bg-gray-800 rounded-xl p-4 border border-gray-700 mb-6">
        <div class="flex flex-wrap items-center gap-4">
            <div class="flex items-center gap-2">
                <label class="text-sm font-medium text-gray-300">Event-Typ:</label>
                <select onchange="updateFilter('filter', this.value)" class="bg-gray-700 border-gray-600 rounded p-1 text-sm text-white">
                    <option value="all" <?php echo $filter === 'all' ? 'selected' : ''; ?>>Alle Events</option>
                    <?php foreach ($eventTypes as $type): ?>
                        <option value="<?php echo htmlspecialchars($type['event_type']); ?>" <?php echo $filter === $type['event_type'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($type['event_type']); ?> (<?php echo $type['count']; ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="flex items-center gap-2">
                <label class="text-sm font-medium text-gray-300">Anzahl:</label>
                <select onchange="updateFilter('limit', this.value)" class="bg-gray-700 border-gray-600 rounded p-1 text-sm text-white">
                    <option value="50" <?php echo $limit === 50 ? 'selected' : ''; ?>>50</option>
                    <option value="100" <?php echo $limit === 100 ? 'selected' : ''; ?>>100</option>
                    <option value="250" <?php echo $limit === 250 ? 'selected' : ''; ?>>250</option>
                    <option value="500" <?php echo $limit === 500 ? 'selected' : ''; ?>>500</option>
                </select>
            </div>

            <button onclick="location.reload()" class="px-3 py-1 bg-blue-600 hover:bg-blue-500 text-white rounded text-sm transition-colors">
                🔄 Aktualisieren
            </button>
        </div>
    </div>

    <!-- Security Logs Tabelle -->
    <div class="bg-gray-800 rounded-xl border border-gray-700 overflow-hidden">
        <div class="p-4 border-b border-gray-700">
            <h3 class="text-lg font-semibold text-white">
                🛡️ Security Logs 
                <?php if ($filter !== 'all'): ?>
                    <span class="text-sm font-normal text-gray-400">- gefiltert nach: <?php echo htmlspecialchars($filter); ?></span>
                <?php endif; ?>
            </h3>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-700">
                    <tr class="text-gray-300">
                        <th class="text-left py-3 px-4">Zeit</th>
                        <th class="text-left py-3 px-4">Event-Typ</th>
                        <th class="text-left py-3 px-4">IP-Adresse</th>
                        <th class="text-left py-3 px-4">Severity</th>
                        <th class="text-left py-3 px-4">Admin</th>
                        <th class="text-left py-3 px-4">Details</th>
                        <th class="text-left py-3 px-4">User-Agent</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logs)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-8 text-gray-500">
                                Keine Security-Logs gefunden.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($logs as $log): ?>
                            <tr class="border-b border-gray-700/50 hover:bg-gray-700/30 transition-colors">
                                <td class="py-3 px-4 text-gray-300">
                                    <div class="font-mono text-xs">
                                        <?php echo date('d.m.Y', strtotime($log['created_at'])); ?>
                                    </div>
                                    <div class="font-mono text-xs text-gray-500">
                                        <?php echo date('H:i:s', strtotime($log['created_at'])); ?>
                                    </div>
                                </td>
                                <td class="py-3 px-4">
                                    <?php
                                    $eventColors = [
                                        'admin_login_success' => 'bg-green-500/20 text-green-400',
                                        'admin_login_failed' => 'bg-red-500/20 text-red-400',
                                        'admin_login_blocked' => 'bg-red-500/20 text-red-400',
                                        'upload_success' => 'bg-blue-500/20 text-blue-400',
                                        'download_success' => 'bg-purple-500/20 text-purple-400',
                                        'file_deleted' => 'bg-orange-500/20 text-orange-400',
                                        'rate_limit_exceeded' => 'bg-red-500/20 text-red-400',
                                    ];
                                    $colorClass = $eventColors[$log['event_type']] ?? 'bg-gray-500/20 text-gray-400';
                                    ?>
                                    <span class="px-2 py-1 rounded text-xs font-medium <?php echo $colorClass; ?>">
                                        <?php echo htmlspecialchars($log['event_type']); ?>
                                    </span>
                                </td>
                                <td class="py-3 px-4 font-mono text-gray-300 text-xs">
                                    <?php echo htmlspecialchars($log['ip_address']); ?>
                                </td>
                                <td class="py-3 px-4">
                                    <?php
                                    $severityColors = [
                                        'low' => 'bg-green-500/20 text-green-400',
                                        'medium' => 'bg-yellow-500/20 text-yellow-400',
                                        'high' => 'bg-orange-500/20 text-orange-400',
                                        'critical' => 'bg-red-500/20 text-red-400'
                                    ];
                                    $severityClass = $severityColors[$log['severity']] ?? 'bg-gray-500/20 text-gray-400';
                                    ?>
                                    <span class="px-2 py-1 rounded text-xs font-medium <?php echo $severityClass; ?>">
                                        <?php echo ucfirst($log['severity']); ?>
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-gray-300">
                                    <?php if ($log['admin_username']): ?>
                                        <span class="text-green-400 font-medium"><?php echo htmlspecialchars($log['admin_username']); ?></span>
                                    <?php else: ?>
                                        <span class="text-gray-500">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3 px-4 text-gray-400 max-w-md">
                                    <div class="truncate" title="<?php echo htmlspecialchars($log['details'] ?? ''); ?>">
                                        <?php echo htmlspecialchars($log['details'] ?? ''); ?>
                                    </div>
                                </td>
                                <td class="py-3 px-4 text-gray-500 text-xs max-w-xs">
                                    <div class="truncate" title="<?php echo htmlspecialchars($log['user_agent'] ?? ''); ?>">
                                        <?php echo htmlspecialchars($log['user_agent'] ?? ''); ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <div class="p-4 border-t border-gray-700 flex items-center justify-between">
                <div class="text-sm text-gray-400">
                    Zeige <?php echo number_format($offset + 1); ?> - <?php echo number_format(min($offset + $limit, $totalLogs)); ?> von <?php echo number_format($totalLogs); ?> Events
                </div>
                
                <div class="flex gap-2">
                    <?php if ($page > 1): ?>
                        <a href="?action=security&filter=<?php echo urlencode($filter); ?>&limit=<?php echo $limit; ?>&page=<?php echo ($page - 1); ?>" 
                           class="px-3 py-1 bg-gray-600 hover:bg-gray-500 text-white rounded text-sm transition-colors">
                            ← Zurück
                        </a>
                    <?php endif; ?>

                    <span class="px-3 py-1 bg-green-600 text-white rounded text-sm">
                        <?php echo $page; ?> / <?php echo $totalPages; ?>
                    </span>

                    <?php if ($page < $totalPages): ?>
                        <a href="?action=security&filter=<?php echo urlencode($filter); ?>&limit=<?php echo $limit; ?>&page=<?php echo ($page + 1); ?>" 
                           class="px-3 py-1 bg-gray-600 hover:bg-gray-500 text-white rounded text-sm transition-colors">
                            Weiter →
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function updateFilter(param, value) {
    const url = new URL(window.location);
    url.searchParams.set(param, value);
    url.searchParams.set('page', '1'); // Reset to first page
    window.location = url.toString();
}
</script>