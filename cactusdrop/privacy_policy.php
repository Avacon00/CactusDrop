<?php
require_once 'config.php';
require_once 'languages.php';
?>
<!DOCTYPE html>
<html lang="<?php echo getCurrentLang(); ?>" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t('privacy_policy'); ?> - CactusDrop</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .fade-in { animation: fadeIn 0.5s ease-in-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body class="bg-gray-900 text-gray-200 min-h-screen p-4">

<div class="max-w-4xl mx-auto">
    <!-- Header -->
    <header class="text-center mb-8">
        <div class="flex items-center justify-center gap-3 mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#2f855a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
            </svg>
            <h1 class="text-3xl font-bold text-white"><?php echo t('privacy_policy'); ?></h1>
        </div>
        <p class="text-gray-400"><?php echo t('privacy_policy_subtitle'); ?></p>
    </header>

    <!-- Privacy Policy Content -->
    <div class="bg-gray-800 rounded-xl p-8 border border-gray-700 prose prose-gray max-w-none">
        
        <h2 class="text-2xl font-semibold text-white mb-4">1. <?php echo t('data_controller'); ?></h2>
        <div class="bg-gray-900 rounded-lg p-4 mb-6">
            <p class="text-gray-300">
                <strong><?php echo t('responsible_entity'); ?>:</strong><br>
                [Ihr Name/Unternehmen]<br>
                [Ihre Adresse]<br>
                [E-Mail: datenschutz@ihre-domain.de]
            </p>
        </div>

        <h2 class="text-2xl font-semibold text-white mb-4">2. <?php echo t('data_processing_overview'); ?></h2>
        <p class="text-gray-300 mb-4">
            <?php echo t('data_processing_description'); ?>
        </p>

        <h3 class="text-xl font-semibold text-white mb-3">2.1 <?php echo t('processed_data_types'); ?></h3>
        <ul class="list-disc list-inside text-gray-300 mb-4 space-y-1">
            <li><?php echo t('uploaded_files'); ?></li>
            <li><?php echo t('file_metadata'); ?></li>
            <li><?php echo t('anonymized_ip_addresses'); ?></li>
            <li><?php echo t('browser_information'); ?></li>
            <li><?php echo t('access_timestamps'); ?></li>
        </ul>

        <h3 class="text-xl font-semibold text-white mb-3">2.2 <?php echo t('processing_purposes'); ?></h3>
        <div class="bg-blue-900/30 border border-blue-700 rounded-lg p-4 mb-6">
            <ul class="list-disc list-inside text-blue-200 space-y-1">
                <li><?php echo t('purpose_file_sharing'); ?></li>
                <li><?php echo t('purpose_security'); ?></li>
                <li><?php echo t('purpose_system_stability'); ?></li>
                <li><?php echo t('purpose_abuse_prevention'); ?></li>
            </ul>
        </div>

        <h2 class="text-2xl font-semibold text-white mb-4">3. <?php echo t('legal_basis'); ?></h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <div class="bg-gray-900 rounded-lg p-4">
                <h4 class="font-semibold text-green-400 mb-2"><?php echo t('contract_fulfillment'); ?></h4>
                <p class="text-gray-300 text-sm"><?php echo t('contract_fulfillment_desc'); ?></p>
            </div>
            <div class="bg-gray-900 rounded-lg p-4">
                <h4 class="font-semibold text-blue-400 mb-2"><?php echo t('legitimate_interests'); ?></h4>
                <p class="text-gray-300 text-sm"><?php echo t('legitimate_interests_desc'); ?></p>
            </div>
        </div>

        <h2 class="text-2xl font-semibold text-white mb-4">4. <?php echo t('data_retention'); ?></h2>
        <div class="bg-yellow-900/30 border border-yellow-700 rounded-lg p-4 mb-6">
            <ul class="list-disc list-inside text-yellow-200 space-y-1">
                <li><?php echo t('files_retention'); ?></li>
                <li><?php echo t('logs_retention'); ?></li>
                <li><?php echo t('automatic_deletion'); ?></li>
            </ul>
        </div>

        <h2 class="text-2xl font-semibold text-white mb-4">5. <?php echo t('privacy_by_design'); ?></h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="bg-green-900/30 border border-green-700 rounded-lg p-4">
                <h4 class="font-semibold text-green-400 mb-2">🔒 <?php echo t('e2e_encryption'); ?></h4>
                <p class="text-green-200 text-sm"><?php echo t('e2e_encryption_desc'); ?></p>
            </div>
            <div class="bg-green-900/30 border border-green-700 rounded-lg p-4">
                <h4 class="font-semibold text-green-400 mb-2">🛡️ <?php echo t('ip_anonymization'); ?></h4>
                <p class="text-green-200 text-sm"><?php echo t('ip_anonymization_desc'); ?></p>
            </div>
            <div class="bg-green-900/30 border border-green-700 rounded-lg p-4">
                <h4 class="font-semibold text-green-400 mb-2">⏰ <?php echo t('auto_expiry'); ?></h4>
                <p class="text-green-200 text-sm"><?php echo t('auto_expiry_desc'); ?></p>
            </div>
        </div>

        <h2 class="text-2xl font-semibold text-white mb-4">6. <?php echo t('your_rights'); ?></h2>
        <div class="space-y-4 mb-6">
            <div class="bg-gray-900 rounded-lg p-4">
                <h4 class="font-semibold text-blue-400 mb-2">📋 <?php echo t('right_to_information'); ?></h4>
                <p class="text-gray-300 text-sm mb-2"><?php echo t('right_to_information_desc'); ?></p>
                <a href="privacy_rights.php#inquiry" class="text-blue-400 hover:underline text-sm">→ <?php echo t('request_information'); ?></a>
            </div>
            
            <div class="bg-gray-900 rounded-lg p-4">
                <h4 class="font-semibold text-red-400 mb-2">🗑️ <?php echo t('right_to_deletion'); ?></h4>
                <p class="text-gray-300 text-sm mb-2"><?php echo t('right_to_deletion_desc'); ?></p>
                <a href="privacy_rights.php#deletion" class="text-red-400 hover:underline text-sm">→ <?php echo t('request_deletion'); ?></a>
            </div>
            
            <div class="bg-gray-900 rounded-lg p-4">
                <h4 class="font-semibold text-green-400 mb-2">📦 <?php echo t('right_to_portability'); ?></h4>
                <p class="text-gray-300 text-sm mb-2"><?php echo t('right_to_portability_desc'); ?></p>
                <a href="privacy_rights.php#export" class="text-green-400 hover:underline text-sm">→ <?php echo t('export_data'); ?></a>
            </div>
        </div>

        <h2 class="text-2xl font-semibold text-white mb-4">7. <?php echo t('security_measures'); ?></h2>
        <ul class="list-disc list-inside text-gray-300 mb-6 space-y-1">
            <li><?php echo t('security_transport_encryption'); ?></li>
            <li><?php echo t('security_access_controls'); ?></li>
            <li><?php echo t('security_monitoring'); ?></li>
            <li><?php echo t('security_regular_updates'); ?></li>
        </ul>

        <h2 class="text-2xl font-semibold text-white mb-4">8. <?php echo t('third_parties'); ?></h2>
        <div class="bg-gray-900 rounded-lg p-4 mb-6">
            <p class="text-gray-300"><?php echo t('no_third_party_sharing'); ?></p>
        </div>

        <h2 class="text-2xl font-semibold text-white mb-4">9. <?php echo t('contact_data_protection'); ?></h2>
        <div class="bg-blue-900/30 border border-blue-700 rounded-lg p-4 mb-6">
            <p class="text-blue-200">
                <?php echo t('data_protection_contact_info'); ?><br>
                <strong>E-Mail:</strong> datenschutz@ihre-domain.de<br>
                <strong><?php echo t('complaint_authority'); ?>:</strong> <?php echo t('complaint_authority_info'); ?>
            </p>
        </div>

        <h2 class="text-2xl font-semibold text-white mb-4">10. <?php echo t('policy_updates'); ?></h2>
        <p class="text-gray-300 mb-6">
            <?php echo t('policy_updates_desc'); ?>
        </p>

        <div class="bg-green-900/30 border border-green-700 rounded-lg p-4 mb-6">
            <p class="text-green-200 text-sm">
                <strong><?php echo t('last_updated'); ?>:</strong> <?php echo date('d.m.Y'); ?><br>
                <strong><?php echo t('version'); ?>:</strong> CactusDrop v0.4.0 DSGVO-Edition
            </p>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="flex flex-wrap gap-4 mt-8 justify-center">
        <a href="privacy_rights.php" class="bg-blue-600 hover:bg-blue-500 text-white font-bold py-3 px-6 rounded-lg transition-colors">
            🛡️ <?php echo t('exercise_rights'); ?>
        </a>
        <a href="index.php" class="bg-green-600 hover:bg-green-500 text-white font-bold py-3 px-6 rounded-lg transition-colors">
            🌵 <?php echo t('back_to_cactusdrop'); ?>
        </a>
    </div>

    <!-- Footer -->
    <footer class="text-center pt-8 text-xs text-gray-500">
        <p>CactusDrop v0.4.0 - <?php echo t('gdpr_compliant'); ?></p>
    </footer>
</div>

</body>
</html>