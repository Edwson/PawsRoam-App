<?php
/**
 * API Endpoint for Uploading User Avatar
 * Method: POST
 * Expected FormData: user_avatar (file), csrf_token, user_id (for confirmation)
 */

// Bootstrap
if (session_status() == PHP_SESSION_NONE) {
    session_start(['cookie_httponly' => true, 'cookie_secure' => isset($_SERVER['HTTPS']), 'cookie_samesite' => 'Lax']);
}
if (!defined('BASE_PATH')) { define('BASE_PATH', dirname(__DIR__, 4)); }
if (!defined('DS')) { define('DS', DIRECTORY_SEPARATOR); }

$required_files = [
    BASE_PATH . DS . 'config' . DS . 'constants.php',
    BASE_PATH . DS . 'config' . DS . 'database.php',
    BASE_PATH . DS . 'includes' . DS . 'functions.php',
    BASE_PATH . DS . 'includes' . DS . 'translation.php',
    BASE_PATH . DS . 'includes' . DS . 'auth.php'
];
foreach ($required_files as $file) {
    if (file_exists($file)) { require_once $file; }
    else { http_response_code(500); header('Content-Type: application/json'); error_log("CRITICAL: Avatar Upload API missing $file"); echo json_encode(['success' => false, 'message' => 'Server config error.']); exit; }
}

$current_api_language = $GLOBALS['current_language'] ?? DEFAULT_LANGUAGE ?? 'en';
header('Content-Type: application/json');

// --- Access Control & Request Method ---
require_login();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); echo json_encode(['success' => false, 'message' => __('error_method_not_allowed', [], $current_api_language)]); exit;
}

// --- CSRF Token Validation ---
if (!validate_csrf_token($_POST[CSRF_TOKEN_NAME ?? 'csrf_token'] ?? null)) {
    http_response_code(403); echo json_encode(['success' => false, 'message' => __('error_csrf_token_invalid', [], $current_api_language)]); exit;
}

// --- User Verification ---
$user_id_from_session = current_user_id();
$user_id_from_form = (int)($_POST['user_id'] ?? 0);

if ($user_id_from_session !== $user_id_from_form) {
    http_response_code(403);
    error_log("Avatar Upload API: Session user ID ({$user_id_from_session}) does not match form user ID ({$user_id_from_form}).");
    echo json_encode(['success' => false, 'message' => __('error_avatar_upload_auth_failed', [], $current_api_language)]); // "Avatar upload authorization failed."
    exit;
}

// --- File Upload Processing ---
if (!isset($_FILES['user_avatar']) || $_FILES['user_avatar']['error'] === UPLOAD_ERR_NO_FILE) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => __('error_avatar_no_file_selected', [], $current_api_language)]); // "No avatar file was selected for upload."
    exit;
}

$target_user_avatar_dir = 'user-avatars' . DS . $user_id_from_session; // e.g., uploads/user-avatars/123/
$allowed_mimes_serialized = defined('USER_AVATAR_ALLOWED_MIME_TYPES') ? USER_AVATAR_ALLOWED_MIME_TYPES : serialize(['image/jpeg', 'image/png']);
$allowed_mimes = unserialize($allowed_mimes_serialized);
$max_size = (defined('DEFAULT_MAX_UPLOAD_SIZE_MB') ? DEFAULT_MAX_UPLOAD_SIZE_MB * 1024 * 1024 : 2 * 1024 * 1024);

$upload_result = handle_file_upload(
    'user_avatar',
    $target_user_avatar_dir,
    $allowed_mimes,
    $max_size,
    'user_avatar_' . $user_id_from_session . '_' // Filename prefix
);

if (!$upload_result['success']) {
    http_response_code(422); // Unprocessable Entity - validation error from upload helper
    echo json_encode(['success' => false, 'message' => $upload_result['message'], 'errors' => ['user_avatar' => $upload_result['message']]]);
    exit;
}

// --- Database Update ---
$new_avatar_db_path = $upload_result['filepath']; // Relative path for DB

try {
    $db = Database::getInstance()->getConnection();

    // Fetch old avatar path to delete it after successful DB update
    $stmt_old_avatar = $db->prepare("SELECT avatar_path FROM users WHERE id = :user_id");
    $stmt_old_avatar->bindParam(':user_id', $user_id_from_session, PDO::PARAM_INT);
    $stmt_old_avatar->execute();
    $old_avatar_path = $stmt_old_avatar->fetchColumn();

    // Update user's avatar_path in DB
    $stmt_update = $db->prepare("UPDATE users SET avatar_path = :avatar_path WHERE id = :user_id");
    $stmt_update->bindParam(':avatar_path', $new_avatar_db_path);
    $stmt_update->bindParam(':user_id', $user_id_from_session, PDO::PARAM_INT);

    if ($stmt_update->execute()) {
        // Delete old avatar file if it existed and is different from new one (though new name is unique)
        if (!empty($old_avatar_path) && $old_avatar_path !== $new_avatar_db_path && defined('UPLOADS_BASE_PATH')) {
            $full_old_avatar_server_path = rtrim(UPLOADS_BASE_PATH, DS) . DS . ltrim($old_avatar_path, DS);
            if (file_exists($full_old_avatar_server_path) && is_file($full_old_avatar_server_path)) {
                if (!unlink($full_old_avatar_server_path)) {
                    error_log("Avatar Upload API: Failed to delete old avatar file: {$full_old_avatar_server_path}");
                    // Non-fatal, main avatar updated.
                } else {
                     error_log("Avatar Upload API: Successfully deleted old avatar: {$full_old_avatar_server_path}");
                }
            }
        }

        $full_new_avatar_url = rtrim(UPLOADS_BASE_URL, '/') . '/' . ltrim($new_avatar_db_path, '/');

        http_response_code(200); // OK
        echo json_encode([
            'success' => true,
            'message' => __('profile_success_avatar_uploaded_api', [], $current_api_language), // "Avatar uploaded and updated successfully!"
            'new_avatar_url' => $full_new_avatar_url, // Full URL for client to use in preview
            'avatar_path_db' => $new_avatar_db_path // Relative path stored in DB
        ]);
    } else {
        error_log("Avatar Upload API: DB execution error updating avatar path for user ID {$user_id_from_session}.");
        // If DB update failed, attempt to delete the newly uploaded file to prevent orphans
        if (defined('UPLOADS_BASE_PATH') && !empty($upload_result['full_server_path']) && file_exists($upload_result['full_server_path'])) {
            unlink($upload_result['full_server_path']);
        }
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => __('profile_error_avatar_db_update_failed', [], $current_api_language)]); // "Failed to update profile with new avatar due to a database error."
    }

} catch (PDOException $e) {
    error_log("Avatar Upload API (PDOException): " . $e->getMessage() . " for user ID {$user_id_from_session}.");
    // Attempt to delete uploaded file if DB fails
    if (defined('UPLOADS_BASE_PATH') && !empty($upload_result['full_server_path']) && file_exists($upload_result['full_server_path'])) {
        unlink($upload_result['full_server_path']);
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => __('error_server_generic', [], $current_api_language)]);
} catch (Exception $e) {
    error_log("Avatar Upload API (Exception): " . $e->getMessage() . " for user ID {$user_id_from_session}.");
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => __('error_server_generic', [], $current_api_language)]);
}
exit;

<?php
// Translation placeholders
// __('error_avatar_upload_auth_failed', [], $current_api_language);
// __('error_avatar_no_file_selected', [], $current_api_language);
// __('profile_success_avatar_uploaded_api', [], $current_api_language);
// __('profile_error_avatar_db_update_failed', [], $current_api_language);
?>
