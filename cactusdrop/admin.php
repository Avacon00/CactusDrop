<?php
/**
 * CactusDrop v0.4.0 - Admin Panel
 * 
 * Statistiken, Security-Logs, System-Management
 * Sicheres Login-System mit Session-Management
 */

session_start();
require_once 'config.php';
require_once 'privacy.php';

// Admin-spezifische Security-Klasse
class AdminSecurity {
    
    public static function isLoggedIn() {
        return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
    }
    
    public static function requireLogin() {
        if (!self::isLoggedIn()) {
            header('Location: admin.php?action=login');
            exit;
        }
    }
    
    public static function login($username, $password) {
        try {
            $conn = get_db_connection();
            
            // Prüfen ob admin_users Tabelle existiert
            $result = $conn->query("SHOW TABLES LIKE 'admin_users'");
            if ($result->num_rows === 0) {
                $conn->close();
                return ['success' => false, 'message' => 'Admin-System noch nicht installiert. Bitte führen Sie upgrade_v040.php aus.'];
            }
            
            // User validieren (vereinfacht für Kompatibilität)
            $stmt = $conn->prepare("SELECT id, username, password_hash, is_active FROM admin_users WHERE username = ?");
            if (!$stmt) {
                $conn->close();
                return ['success' => false, 'message' => 'Datenbankfehler beim Login.'];
            }
            
            $stmt->bind_param('s', $username);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                self::logSecurityEvent('admin_login_failed', "Failed login attempt for user: $username");
                $conn->close();
                return ['success' => false, 'message' => 'Ungültige Anmeldedaten.'];
            }
            
            $user = $result->fetch_assoc();
            if (!password_verify($password, $user['password_hash'])) {
                self::logSecurityEvent('admin_login_failed', "Failed login attempt for user: $username");
                $conn->close();
                return ['success' => false, 'message' => 'Ungültige Anmeldedaten.'];
            }
            
            if (!$user['is_active']) {
                self::logSecurityEvent('admin_login_blocked', "Login attempt for inactive user: $username");
                $conn->close();
                return ['success' => false, 'message' => 'Benutzerkonto ist deaktiviert.'];
            }
            
            // Session erstellen (optional, falls Tabelle existiert)
            $sessionId = bin2hex(random_bytes(32));
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            
            $sessionResult = $conn->query("SHOW TABLES LIKE 'admin_sessions'");
            if ($sessionResult->num_rows > 0) {
                $expiresAt = date('Y-m-d H:i:s', time() + 1800); // 30 Minuten
                $stmt = $conn->prepare("INSERT INTO admin_sessions (id, user_id, ip_address, user_agent, expires_at) VALUES (?, ?, ?, ?, ?)");
                if ($stmt) {
                    $stmt->bind_param('sisss', $sessionId, $user['id'], $ip, $_SERVER['HTTP_USER_AGENT'], $expiresAt);
                    $stmt->execute();
                    $stmt->close();
                }
            }
            
            // Login-Zeit aktualisieren
            $stmt = $conn->prepare("UPDATE admin_users SET last_login_at = NOW(), last_login_ip = ? WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param('si', $ip, $user['id']);
                $stmt->execute();
                $stmt->close();
            }
            
            // Session setzen
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_user_id'] = $user['id'];
            $_SESSION['admin_username'] = $user['username'];
            $_SESSION['admin_session_id'] = $sessionId;
            
            self::logSecurityEvent('admin_login_success', "Successful login for user: {$user['username']}", 'low');
            
            $conn->close();
            return ['success' => true, 'message' => 'Erfolgreich angemeldet.'];
            
        } catch (Exception $e) {
            error_log("Admin login error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Systemfehler beim Login.'];
        }
    }
    
    public static function logout() {
        if (isset($_SESSION['admin_session_id'])) {
            $conn = get_db_connection();
            $stmt = $conn->prepare("UPDATE admin_sessions SET is_active = 0 WHERE id = ?");
            $stmt->bind_param('s', $_SESSION['admin_session_id']);
            $stmt->execute();
            $conn->close();
        }
        
        session_destroy();
        header('Location: admin.php?action=login');
        exit;
    }
    
    public static function logSecurityEvent($eventType, $details, $severity = 'medium', $fileId = null) {
        try {
            $conn = get_db_connection();
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
            $adminUserId = $_SESSION['admin_user_id'] ?? null;
            
            // Prüfen ob security_logs Tabelle existiert
            $result = $conn->query("SHOW TABLES LIKE 'security_logs'");
            if ($result->num_rows === 0) {
                $conn->close();
                return; // Tabelle existiert noch nicht
            }
            
            $stmt = $conn->prepare("INSERT INTO security_logs (event_type, ip_address, user_agent, details, file_id, admin_user_id, severity) VALUES (?, ?, ?, ?, ?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param('sssssis', $eventType, $ip, $userAgent, $details, $fileId, $adminUserId, $severity);
                $stmt->execute();
                $stmt->close();
            }
            $conn->close();
        } catch (Exception $e) {
            // Fehler ignorieren, Security Logging ist optional
            error_log("Security logging failed: " . $e->getMessage());
        }
    }
}

// Action-Handler
$action = $_GET['action'] ?? 'dashboard';
$error = '';
$success = '';

// Login-Handler
if ($action === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = AdminSecurity::login($_POST['username'] ?? '', $_POST['password'] ?? '');
    if ($result['success']) {
        header('Location: admin.php?action=dashboard');
        exit;
    } else {
        $error = $result['message'];
    }
}

// Logout-Handler
if ($action === 'logout') {
    AdminSecurity::logout();
}

// Für alle anderen Actions: Login erforderlich
if ($action !== 'login') {
    AdminSecurity::requireLogin();
}

// Statistics-Helper
function getUploadStats($period = 'today') {
    $conn = get_db_connection();
    
    switch ($period) {
        case 'today':
            $query = "SELECT COUNT(*) as count, SUM(file_size) as total_size, COUNT(DISTINCT upload_ip) as unique_ips FROM files WHERE DATE(created_at) = CURDATE()";
            break;
        case 'week':
            $query = "SELECT COUNT(*) as count, SUM(file_size) as total_size, COUNT(DISTINCT upload_ip) as unique_ips FROM files WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
            break;
        case 'month':
            $query = "SELECT COUNT(*) as count, SUM(file_size) as total_size, COUNT(DISTINCT upload_ip) as unique_ips FROM files WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
            break;
        default:
            $query = "SELECT COUNT(*) as count, SUM(file_size) as total_size, COUNT(DISTINCT upload_ip) as unique_ips FROM files";
    }
    
    $result = $conn->query($query);
    $stats = $result->fetch_assoc();
    $conn->close();
    
    return [
        'uploads' => $stats['count'] ?? 0,
        'total_size' => $stats['total_size'] ?? 0,
        'unique_ips' => $stats['unique_ips'] ?? 0,
        'avg_size' => $stats['count'] > 0 ? ($stats['total_size'] / $stats['count']) : 0
    ];
}

function getSecurityLogs($limit = 50) {
    $conn = get_db_connection();
    $stmt = $conn->prepare("SELECT * FROM security_logs ORDER BY created_at DESC LIMIT ?");
    $stmt->bind_param('i', $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    $logs = $result->fetch_all(MYSQLI_ASSOC);
    $conn->close();
    return $logs;
}

function getSystemSettings() {
    $conn = get_db_connection();
    $result = $conn->query("SELECT * FROM system_settings ORDER BY category, setting_key");
    $settings = $result->fetch_all(MYSQLI_ASSOC);
    $conn->close();
    return $settings;
}

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CactusDrop Admin Panel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .fade-in { animation: fadeIn 0.5s ease-in-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-900 text-gray-200 min-h-screen">

<?php if ($action === 'login'): ?>
    <!-- LOGIN SCREEN -->
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="w-full max-w-md">
            <div class="text-center mb-8">
                <div class="flex items-center justify-center gap-3 mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#2f855a" stroke-width="2">
                        <path d="M12 9V2m0 7v13m0-13c-2.833 0-5 2.167-5 5v0c0 2.833 2.167 5 5 5m0-10c2.833 0 5 2.167 5 5v0c0 2.833-2.167 5-5 5m-5 5h10"/>
                    </svg>
                    <h1 class="text-3xl font-bold text-gray-100">CactusDrop</h1>
                </div>
                <p class="text-gray-400">Admin Panel</p>
            </div>

            <div class="bg-gray-800 rounded-2xl shadow-lg p-6">
                <h2 class="text-xl font-semibold mb-6 text-center">Anmelden</h2>

                <?php if ($error): ?>
                    <div class="bg-red-900/50 border border-red-700 text-red-300 px-4 py-3 rounded-lg mb-4 fade-in">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1">Benutzername</label>
                        <input type="text" name="username" required 
                               class="w-full bg-gray-700 border-gray-600 rounded-md p-3 text-white focus:ring-2 focus:ring-green-500 focus:border-green-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1">Passwort</label>
                        <input type="password" name="password" required 
                               class="w-full bg-gray-700 border-gray-600 rounded-md p-3 text-white focus:ring-2 focus:ring-green-500 focus:border-green-500">
                    </div>
                    <button type="submit" class="w-full bg-green-600 hover:bg-green-500 text-white font-bold py-3 px-4 rounded-lg transition-all">
                        🔓 Anmelden
                    </button>
                </form>

                <div class="mt-4 text-xs text-gray-500 text-center">
                    <p>Standard: admin / admin123</p>
                    <p class="text-red-400">⚠️ Passwort nach erstem Login ändern!</p>
                </div>
            </div>
        </div>
    </div>

<?php else: ?>
    <!-- ADMIN DASHBOARD -->
    <div class="flex h-screen bg-gray-900">
        <!-- Sidebar -->
        <div class="w-64 bg-gray-800 shadow-lg">
            <div class="p-4">
                <div class="flex items-center gap-2 mb-8">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#2f855a" stroke-width="2">
                        <path d="M12 9V2m0 7v13m0-13c-2.833 0-5 2.167-5 5v0c0 2.833 2.167 5 5 5m0-10c2.833 0 5 2.167 5 5v0c0 2.833-2.167 5-5 5m-5 5h10"/>
                    </svg>
                    <h1 class="text-xl font-bold text-white">CactusDrop</h1>
                </div>

                <nav class="space-y-2">
                    <a href="admin.php?action=dashboard" class="<?php echo $action === 'dashboard' ? 'bg-green-600' : 'hover:bg-gray-700'; ?> block px-3 py-2 rounded-md text-sm font-medium transition-colors">
                        📊 Dashboard
                    </a>
                    <a href="admin.php?action=statistics" class="<?php echo $action === 'statistics' ? 'bg-green-600' : 'hover:bg-gray-700'; ?> block px-3 py-2 rounded-md text-sm font-medium transition-colors">
                        📈 Statistiken
                    </a>
                    <a href="admin.php?action=security" class="<?php echo $action === 'security' ? 'bg-green-600' : 'hover:bg-gray-700'; ?> block px-3 py-2 rounded-md text-sm font-medium transition-colors">
                        🛡️ Security Logs
                    </a>
                    <a href="admin.php?action=files" class="<?php echo $action === 'files' ? 'bg-green-600' : 'hover:bg-gray-700'; ?> block px-3 py-2 rounded-md text-sm font-medium transition-colors">
                        📁 Datei-Management
                    </a>
                    <a href="admin.php?action=settings" class="<?php echo $action === 'settings' ? 'bg-green-600' : 'hover:bg-gray-700'; ?> block px-3 py-2 rounded-md text-sm font-medium transition-colors">
                        ⚙️ Einstellungen
                    </a>
                    <a href="admin.php?action=privacy" class="<?php echo $action === 'privacy' ? 'bg-green-600' : 'hover:bg-gray-700'; ?> block px-3 py-2 rounded-md text-sm font-medium transition-colors">
                        🛡️ DSGVO Privacy
                    </a>
                    <hr class="my-4 border-gray-700">
                    <a href="index.html" target="_blank" class="block px-3 py-2 rounded-md text-sm font-medium hover:bg-gray-700 transition-colors text-blue-400">
                        🌵 Zu CactusDrop
                    </a>
                    <a href="admin.php?action=logout" class="block px-3 py-2 rounded-md text-sm font-medium hover:bg-red-700 transition-colors text-red-400">
                        🔒 Abmelden
                    </a>
                </nav>
            </div>

            <div class="absolute bottom-4 left-4 text-xs text-gray-500">
                <p>Angemeldet als:</p>
                <p class="font-semibold text-green-400"><?php echo htmlspecialchars($_SESSION['admin_username']); ?></p>
            </div>
        </div>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <header class="bg-gray-800 shadow-sm border-b border-gray-700">
                <div class="px-6 py-4">
                    <h2 class="text-2xl font-bold text-white">
                        <?php
                        switch ($action) {
                            case 'dashboard': echo '📊 Dashboard'; break;
                            case 'statistics': echo '📈 Statistiken'; break;
                            case 'security': echo '🛡️ Security Logs'; break;
                            case 'files': echo '📁 Datei-Management'; break;
                            case 'settings': echo '⚙️ Einstellungen'; break;
                            default: echo '📊 Dashboard';
                        }
                        ?>
                    </h2>
                </div>
            </header>

            <main class="flex-1 overflow-y-auto p-6">
                <?php
                switch ($action) {
                    case 'dashboard':
                        include 'admin_dashboard.php';
                        break;
                    case 'statistics':
                        include 'admin_statistics.php';
                        break;
                    case 'security':
                        include 'admin_security.php';
                        break;
                    case 'files':
                        include 'admin_files.php';
                        break;
                    case 'settings':
                        include 'admin_settings.php';
                        break;
                    case 'privacy':
                        include 'admin_privacy.php';
                        break;
                    default:
                        include 'admin_dashboard.php';
                }
                ?>
            </main>
        </div>
    </div>
<?php endif; ?>

</body>
</html>