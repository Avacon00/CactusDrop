<?php
/**
 * CSRF Token API Endpoint
 * Stellt CSRF-Token für Frontend-Requests bereit
 */

require_once 'security.php';

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');

try {
    $token = CactusDropSecurity::generateCSRFToken();
    echo json_encode([
        'status' => 'success',
        'csrf_token' => $token
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Could not generate security token.'
    ]);
}
?>