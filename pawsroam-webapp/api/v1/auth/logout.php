<?php
/**
 * API Endpoint for User Logout
 * Method: POST (recommended for actions that change state, even if simple like logout)
 *         GET could also be used if CSRF protection is via other means or if it's a simple link.
 *         For consistency with login/register, using POST and CSRF.
 * Expected FormData: csrf_token (if POST)
 */

// Bootstrap: Load essential files and configurations
if (session_status() == PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_secure' => isset($_SERVER['HTTPS']),
        'cookie_samesite' => 'Lax'
    ]);
}

if (!defined('BASE_PATH')) { define('BASE_PATH', dirname(__DIR__, 4)); }
if (!defined('DS')) { define('DS', DIRECTORY_SEPARATOR); }

$required_files = [
    BASE_PATH . DS . 'config' . DS . 'constants.php', // For CSRF_TOKEN_NAME, DEFAULT_LANGUAGE
    BASE_PATH . DS . 'includes' . DS . 'functions.php', // For validate_csrf_token, base_url
    BASE_PATH . DS . 'includes' . DS . 'translation.php', // For messages
    BASE_PATH . DS . 'includes' . DS . 'auth.php' // For logout_user function
];
foreach ($required_files as $file) {
    if (file_exists($file)) { require_once $file; }
    else {
        http_response_code(500); header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Server configuration error. Missing: ' . basename($file)]); exit;
    }
}

$current_api_language = $GLOBALS['current_language'] ?? DEFAULT_LANGUAGE ?? 'en';
header('Content-Type: application/json');

// --- Request Method and CSRF Token Validation (if using POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validate_csrf_token($_POST[CSRF_TOKEN_NAME ?? 'csrf_token'] ?? null)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => __('error_csrf_token_invalid', [], $current_api_language)]);
        exit;
    }
} elseif ($_SERVER['REQUEST_METHOD'] !== 'GET') { // Allow GET for simple logout links if preferred, but POST is safer
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => __('error_method_not_allowed', [], $current_api_language)]);
    exit;
}


// --- Perform Logout ---
// The logout_user function in auth.php handles session clearing and destruction.
// It no longer redirects by default.
logout_user(null); // Pass null to prevent auth.php from redirecting.

// Clear "Remember Me" cookie from the database if that logic is implemented
// This part is a placeholder until the DB part of "Remember Me" is done.
// For now, the cookie clearing is handled by logout_user() via setcookie with past expiry.
/*
if (isset($_COOKIE[REMEMBER_ME_COOKIE_NAME ?? 'pawsroam_remember_me'])) {
    $token_from_cookie = $_COOKIE[REMEMBER_ME_COOKIE_NAME ?? 'pawsroam_remember_me'];
    // In a full implementation: find the user_id associated with this token's selector part
    // and invalidate the token in the database.
    // For now, the cookie itself is cleared by logout_user's setcookie call.
    // $userId = current_user_id_before_logout_was_called; // This would need to be captured before session is destroyed
    // if ($userId) {
    //     try {
    //         $db = Database::getInstance()->getConnection();
    //         $stmt = $db->prepare("UPDATE users SET remember_token_hash = NULL, remember_token_expires_at = NULL WHERE id = :user_id");
    //         $stmt->bindParam(':user_id', $userId);
    //         $stmt->execute();
    //     } catch (PDOException $e) {
    //         error_log("Failed to clear remember_me token from DB for user ID {$userId} on logout: " . $e->getMessage());
    //     }
    // }
}
*/

// --- Respond with Success ---
http_response_code(200); // OK
echo json_encode([
    'success' => true,
    'message' => __('alert_logout_success', [], $current_api_language),
    'redirect_url' => base_url('/login?status=logged_out') // Suggest redirect to login page with a status message
]);

exit;
?>
