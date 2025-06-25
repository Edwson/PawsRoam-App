<?php
/**
 * API Endpoint for Deleting a Pet Profile
 * Method: POST
 * Expected FormData: pet_id (int, required), csrf_token
 */

// Bootstrap
if (session_status() == PHP_SESSION_NONE) {
    session_start(['cookie_httponly' => true, 'cookie_secure' => isset($_SERVER['HTTPS']), 'cookie_samesite' => 'Lax']);
}

if (!defined('BASE_PATH')) { define('BASE_PATH', dirname(__DIR__, 4)); }
if (!defined('DS')) { define('DS', DIRECTORY_SEPARATOR); }

$required_files = [
    BASE_PATH . DS . 'config' . DS . 'constants.php', // For UPLOADS_BASE_PATH
    BASE_PATH . DS . 'config' . DS . 'database.php',
    BASE_PATH . DS . 'includes' . DS . 'functions.php',
    BASE_PATH . DS . 'includes' . DS . 'translation.php',
    BASE_PATH . DS . 'includes' . DS . 'auth.php'
];
foreach ($required_files as $file) {
    if (file_exists($file)) { require_once $file; }
    else {
        http_response_code(500); header('Content-Type: application/json');
        error_log("CRITICAL: Delete Pet API failed to load core file: " . $file);
        echo json_encode(['success' => false, 'message' => 'Server configuration error.']); exit;
    }
}

$current_api_language = $GLOBALS['current_language'] ?? DEFAULT_LANGUAGE ?? 'en';
header('Content-Type: application/json');

// --- Access Control & Request Method Validation ---
require_login();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => __('error_method_not_allowed', [], $current_api_language)]);
    exit;
}

// --- CSRF Token Validation ---
if (!validate_csrf_token($_POST[CSRF_TOKEN_NAME ?? 'csrf_token'] ?? null)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => __('error_csrf_token_invalid', [], $current_api_language)]);
    exit;
}

// --- Input Collection & Validation ---
$user_id = current_user_id();
$pet_id = filter_var($_POST['pet_id'] ?? null, FILTER_VALIDATE_INT);

if (!$pet_id || $pet_id <= 0) {
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'message' => __('error_invalid_pet_id_provided', [], $current_api_language)]); // "Invalid or missing pet ID."
    exit;
}

// --- Process Deletion ---
$db = Database::getInstance()->getConnection();
try {
    // 1. Fetch pet details to verify ownership and get avatar_path
    $stmt_fetch = $db->prepare("SELECT user_id, avatar_path FROM user_pets WHERE id = :pet_id LIMIT 1");
    $stmt_fetch->bindParam(':pet_id', $pet_id, PDO::PARAM_INT);
    $stmt_fetch->execute();
    $pet_data = $stmt_fetch->fetch(PDO::FETCH_ASSOC);

    if (!$pet_data) {
        http_response_code(404); // Not Found
        echo json_encode(['success' => false, 'message' => __('error_pet_not_found_for_deletion', [], $current_api_language)]); // "Pet profile not found."
        exit;
    }

    // 2. Verify ownership
    if ((int)$pet_data['user_id'] !== $user_id) {
        http_response_code(403); // Forbidden
        error_log("Delete Pet API: User {$user_id} attempted to delete pet ID {$pet_id} owned by user {$pet_data['user_id']}.");
        echo json_encode(['success' => false, 'message' => __('error_pet_delete_unauthorized', [], $current_api_language)]); // "You are not authorized to delete this pet profile."
        exit;
    }

    $avatar_path_to_delete = $pet_data['avatar_path'];

    // 3. Delete pet record from database
    $stmt_delete = $db->prepare("DELETE FROM user_pets WHERE id = :pet_id AND user_id = :user_id"); // Extra user_id check for safety
    $stmt_delete->bindParam(':pet_id', $pet_id, PDO::PARAM_INT);
    $stmt_delete->bindParam(':user_id', $user_id, PDO::PARAM_INT);

    if (!$stmt_delete->execute()) {
        error_log("Delete Pet API: DB execution error deleting pet ID {$pet_id} for user ID {$user_id}.");
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => __('error_pet_delete_failed_db', [], $current_api_language)]); // "Failed to delete pet profile due to a database error."
        exit;
    }

    if ($stmt_delete->rowCount() === 0) {
        // This case means the pet was not found OR didn't belong to the user,
        // though the earlier check should have caught this. It's a safeguard.
        http_response_code(404);
        error_log("Delete Pet API: Pet ID {$pet_id} not found for user ID {$user_id} during delete, or already deleted.");
        echo json_encode(['success' => false, 'message' => __('error_pet_not_found_for_deletion', [], $current_api_language)]);
        exit;
    }

    // 4. Delete associated avatar file, if it exists
    $file_deletion_message = null;
    if (!empty($avatar_path_to_delete) && defined('UPLOADS_BASE_PATH')) {
        $full_avatar_path = rtrim(UPLOADS_BASE_PATH, DS) . DS . ltrim($avatar_path_to_delete, DS);
        if (file_exists($full_avatar_path) && is_file($full_avatar_path)) {
            if (unlink($full_avatar_path)) {
                $file_deletion_message = __('success_pet_avatar_deleted', [], $current_api_language); // "Associated avatar file deleted."
                error_log("Delete Pet API: Successfully deleted avatar file: {$full_avatar_path}");
            } else {
                $file_deletion_message = __('error_pet_avatar_delete_failed', [], $current_api_language); // "Could not delete associated avatar file. Please check server permissions."
                error_log("Delete Pet API: Failed to delete avatar file: {$full_avatar_path}. Check permissions.");
            }
        } else {
            // Avatar path was in DB, but file not found on server. Log this.
            error_log("Delete Pet API: Avatar file not found for deletion: {$full_avatar_path} (Path from DB: {$avatar_path_to_delete})");
            $file_deletion_message = __('info_pet_avatar_not_found_on_disk', [], $current_api_language); // "Avatar file not found on disk, record deleted."
        }
    }

    http_response_code(200); // OK
    $response = [
        'success' => true,
        'message' => __('success_pet_profile_deleted', [], $current_api_language), // "Pet profile deleted successfully."
        'pet_id' => $pet_id
    ];
    if ($file_deletion_message) {
        $response['file_status'] = $file_deletion_message;
    }
    echo json_encode($response);

} catch (PDOException $e) {
    error_log("Delete Pet API (PDOException): " . $e->getMessage() . " for Pet ID {$pet_id}, User {$user_id}.");
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => __('error_server_generic', [], $current_api_language)]);
} catch (Exception $e) {
    error_log("Delete Pet API (Exception): " . $e->getMessage() . " for Pet ID {$pet_id}, User {$user_id}.");
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => __('error_server_generic', [], $current_api_language)]);
}

exit;

<?php
// Translation string placeholders
// __('error_invalid_pet_id_provided', [], $current_api_language);
// __('error_pet_not_found_for_deletion', [], $current_api_language);
// __('error_pet_delete_unauthorized', [], $current_api_language);
// __('error_pet_delete_failed_db', [], $current_api_language);
// __('success_pet_avatar_deleted', [], $current_api_language);
// __('error_pet_avatar_delete_failed', [], $current_api_language);
// __('info_pet_avatar_not_found_on_disk', [], $current_api_language);
// __('success_pet_profile_deleted', [], $current_api_language);
?>
