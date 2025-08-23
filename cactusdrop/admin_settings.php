<?php
// Settings-Handler
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_settings'])) {
    $conn = get_db_connection();
    $updated = 0;
    
    foreach ($_POST as $key => $value) {
        if ($key !== 'update_settings' && strpos($key, 'setting_') === 0) {
            $settingKey = substr($key, 8); // Remove 'setting_' prefix
            $stmt = $conn->prepare("UPDATE system_settings SET setting_value = ?, updated_at = NOW() WHERE setting_key = ?");
            $stmt->bind_param('ss', $value, $settingKey);
            if ($stmt->execute() && $stmt->affected_rows > 0) {
                $updated++;
            }
            $stmt->close();
        }
    }
    
    $conn->close();
    $success = "✅ $updated Einstellungen aktualisiert.";
    AdminSecurity::logSecurityEvent('admin_settings_updated', "Updated $updated settings", 'low');
}

// Load settings
$settings = getSystemSettings();
$settingsByCategory = [];
foreach ($settings as $setting) {
    $settingsByCategory[$setting['category']][] = $setting;
}
?>

<div class="max-w-4xl">
    <?php if (isset($success)): ?>
        <div class="bg-green-900/50 border border-green-700 text-green-300 px-4 py-3 rounded-lg mb-6 fade-in">
            <?php echo $success; ?>
        </div>
    <?php endif; ?>

    <form method="POST" class="space-y-8">
        <?php foreach ($settingsByCategory as $category => $categorySettings): ?>
            <div class="bg-gray-800 rounded-xl p-6 border border-gray-700">
                <h3 class="text-xl font-semibold mb-6 text-white capitalize flex items-center gap-2">
                    <?php
                    $categoryIcons = [
                        'uploads' => '📤',
                        'localization' => '🌐', 
                        'admin' => '👤',
                        'general' => '⚙️'
                    ];
                    echo ($categoryIcons[$category] ?? '⚙️') . ' ' . ucfirst($category);
                    ?>
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <?php foreach ($categorySettings as $setting): ?>
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-300">
                                <?php echo htmlspecialchars($setting['description'] ?: $setting['setting_key']); ?>
                            </label>
                            
                            <?php if ($setting['setting_type'] === 'boolean'): ?>
                                <select name="setting_<?php echo $setting['setting_key']; ?>" class="w-full bg-gray-700 border-gray-600 rounded-md p-2 text-white focus:ring-2 focus:ring-green-500">
                                    <option value="0" <?php echo $setting['setting_value'] == '0' ? 'selected' : ''; ?>>Deaktiviert</option>
                                    <option value="1" <?php echo $setting['setting_value'] == '1' ? 'selected' : ''; ?>>Aktiviert</option>
                                </select>
                            
                            <?php elseif ($setting['setting_type'] === 'integer'): ?>
                                <input type="number" 
                                       name="setting_<?php echo $setting['setting_key']; ?>" 
                                       value="<?php echo htmlspecialchars($setting['setting_value']); ?>"
                                       class="w-full bg-gray-700 border-gray-600 rounded-md p-2 text-white focus:ring-2 focus:ring-green-500 focus:border-green-500"
                                       min="1">
                            
                            <?php elseif ($setting['setting_key'] === 'available_languages'): ?>
                                <div class="text-sm text-gray-400">
                                    <p>Aktuell: <?php echo htmlspecialchars($setting['setting_value']); ?></p>
                                    <p class="text-xs">Verfügbare Sprachen: de (Deutsch), en (English)</p>
                                </div>
                                <input type="hidden" name="setting_<?php echo $setting['setting_key']; ?>" value='["de","en"]'>
                            
                            <?php elseif ($setting['setting_key'] === 'available_expiry_options'): ?>
                                <textarea name="setting_<?php echo $setting['setting_key']; ?>" 
                                          rows="3"
                                          class="w-full bg-gray-700 border-gray-600 rounded-md p-2 text-white focus:ring-2 focus:ring-green-500 focus:border-green-500 font-mono text-xs"
                                          placeholder='[1,6,12,24,48,72,168]'><?php echo htmlspecialchars($setting['setting_value']); ?></textarea>
                                <p class="text-xs text-gray-500">JSON-Array mit Stunden-Werten</p>
                            
                            <?php elseif ($setting['setting_key'] === 'default_language'): ?>
                                <select name="setting_<?php echo $setting['setting_key']; ?>" class="w-full bg-gray-700 border-gray-600 rounded-md p-2 text-white focus:ring-2 focus:ring-green-500">
                                    <option value="de" <?php echo $setting['setting_value'] === 'de' ? 'selected' : ''; ?>>🇩🇪 Deutsch</option>
                                    <option value="en" <?php echo $setting['setting_value'] === 'en' ? 'selected' : ''; ?>>🇺🇸 English</option>
                                </select>
                            
                            <?php else: ?>
                                <input type="text" 
                                       name="setting_<?php echo $setting['setting_key']; ?>" 
                                       value="<?php echo htmlspecialchars($setting['setting_value']); ?>"
                                       class="w-full bg-gray-700 border-gray-600 rounded-md p-2 text-white focus:ring-2 focus:ring-green-500 focus:border-green-500">
                            <?php endif; ?>

                            <p class="text-xs text-gray-500">
                                Key: <?php echo $setting['setting_key']; ?> | 
                                Type: <?php echo $setting['setting_type']; ?> | 
                                Updated: <?php echo date('d.m.Y H:i', strtotime($setting['updated_at'])); ?>
                            </p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>

        <div class="bg-gray-800 rounded-xl p-6 border border-gray-700">
            <h3 class="text-xl font-semibold mb-4 text-white">💾 Änderungen speichern</h3>
            <p class="text-gray-400 mb-4">
                Einstellungsänderungen werden sofort übernommen. Ein Neustart ist nicht erforderlich.
            </p>
            
            <div class="flex gap-4">
                <button type="submit" name="update_settings" value="1" 
                        class="bg-green-600 hover:bg-green-500 text-white font-bold py-2 px-6 rounded-lg transition-colors">
                    💾 Speichern
                </button>
                
                <button type="button" onclick="location.reload()" 
                        class="bg-gray-600 hover:bg-gray-500 text-white font-bold py-2 px-6 rounded-lg transition-colors">
                    🔄 Zurücksetzen
                </button>
            </div>
        </div>
    </form>

    <!-- System Info -->
    <div class="bg-gray-800 rounded-xl p-6 border border-gray-700 mt-8">
        <h3 class="text-xl font-semibold mb-4 text-white">📊 System-Informationen</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
            <div class="bg-gray-900 p-3 rounded-lg">
                <p class="text-gray-400">CactusDrop Version</p>
                <p class="font-semibold text-green-400">v0.4.0</p>
            </div>
            <div class="bg-gray-900 p-3 rounded-lg">
                <p class="text-gray-400">PHP Version</p>
                <p class="font-semibold text-blue-400"><?php echo PHP_VERSION; ?></p>
            </div>
            <div class="bg-gray-900 p-3 rounded-lg">
                <p class="text-gray-400">Upload Max Size</p>
                <p class="font-semibold text-yellow-400"><?php echo ini_get('upload_max_filesize'); ?></p>
            </div>
            <div class="bg-gray-900 p-3 rounded-lg">
                <p class="text-gray-400">Memory Limit</p>
                <p class="font-semibold text-purple-400"><?php echo ini_get('memory_limit'); ?></p>
            </div>
            <div class="bg-gray-900 p-3 rounded-lg">
                <p class="text-gray-400">Aktuelle Sprache</p>
                <p class="font-semibold text-gray-300">
                    <?php 
                    $currentLang = getCurrentLang();
                    echo ($currentLang === 'de' ? '🇩🇪 Deutsch' : '🇺🇸 English');
                    ?>
                </p>
            </div>
            <div class="bg-gray-900 p-3 rounded-lg">
                <p class="text-gray-400">Server Time</p>
                <p class="font-semibold text-gray-300"><?php echo date('d.m.Y H:i:s'); ?></p>
            </div>
        </div>
    </div>

    <!-- Language Management -->
    <div class="bg-gray-800 rounded-xl p-6 border border-gray-700 mt-8">
        <h3 class="text-xl font-semibold mb-4 text-white">🌐 Sprach-Management</h3>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <h4 class="font-semibold text-gray-300 mb-2">Verfügbare Sprachen</h4>
                <div class="space-y-2">
                    <div class="flex items-center justify-between p-2 bg-gray-900 rounded">
                        <span class="flex items-center gap-2">
                            <span>🇩🇪</span>
                            <span>Deutsch (de)</span>
                        </span>
                        <span class="text-green-400 text-sm">✅ Aktiv</span>
                    </div>
                    <div class="flex items-center justify-between p-2 bg-gray-900 rounded">
                        <span class="flex items-center gap-2">
                            <span>🇺🇸</span>
                            <span>English (en)</span>
                        </span>
                        <span class="text-green-400 text-sm">✅ Aktiv</span>
                    </div>
                </div>
            </div>
            
            <div>
                <h4 class="font-semibold text-gray-300 mb-2">Sprach-Switch testen</h4>
                <div class="flex gap-2">
                    <a href="?lang=de" class="px-3 py-2 bg-blue-600 hover:bg-blue-500 text-white rounded text-sm transition-colors">
                        🇩🇪 Deutsch
                    </a>
                    <a href="?lang=en" class="px-3 py-2 bg-blue-600 hover:bg-blue-500 text-white rounded text-sm transition-colors">
                        🇺🇸 English
                    </a>
                </div>
                <p class="text-xs text-gray-500 mt-2">
                    Aktuell: <?php echo getCurrentLang(); ?>
                </p>
            </div>
        </div>
    </div>
</div>