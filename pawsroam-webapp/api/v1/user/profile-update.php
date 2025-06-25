<?php
/**
 * API Endpoint for Updating User Profile
 * Method: POST
 * Expected FormData: username, language_preference, timezone,
 *                    [current_password, new_password, confirm_new_password],
 *                    csrf_token, user_id (for confirmation, though session user_id is primary)
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
    else {
        http_response_code(500); header('Content-Type: application/json');
        error_log("CRITICAL: Profile Update API failed to load core file: " . $file);
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

// --- Input Collection & User Verification ---
$user_id_from_session = current_user_id();
$user_id_from_form = (int)($_POST['user_id'] ?? 0);

if ($user_id_from_session !== $user_id_from_form) {
    http_response_code(403); // Forbidden - trying to update someone else's profile?
    error_log("Profile Update API: Session user ID ({$user_id_from_session}) does not match form user ID ({$user_id_from_form}).");
    echo json_encode(['success' => false, 'message' => __('error_profile_update_mismatch', [], $current_api_language)]); // "Profile update authorization failed."
    exit;
}

$current_user_data = null; // Fetch current user data for comparison and password check
try {
    $db_check = Database::getInstance()->getConnection();
    $stmt_check = $db_check->prepare("SELECT username, email, password FROM users WHERE id = :id");
    $stmt_check->bindParam(':id', $user_id_from_session, PDO::PARAM_INT);
    $stmt_check->execute();
    $current_user_data = $stmt_check->fetch(PDO::FETCH_ASSOC);
    if (!$current_user_data) {
        // Should not happen if user is logged in and ID is valid
        throw new Exception("Current user data not found in DB for ID: {$user_id_from_session}");
    }
} catch (Exception $e) {
    error_log("Profile Update API: Error fetching current user data: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => __('error_server_generic', [], $current_api_language)]);
    exit;
}


$username = trim($_POST['username'] ?? '');
$language_preference = trim($_POST['language_preference'] ?? '');
$timezone = trim($_POST['timezone'] ?? '');

$current_password = $_POST['current_password'] ?? ''; // Not trimmed
$new_password = $_POST['new_password'] ?? '';       // Not trimmed
$confirm_new_password = $_POST['confirm_new_password'] ?? ''; // Not trimmed

$errors = [];
$fields_to_update = [];
$update_password_flag = false;

// --- Input Validation ---

// Username
if (empty($username)) {
    $errors['username'] = __('error_username_required', [], $current_api_language);
} elseif (strlen($username) < 3 || strlen($username) > 25) {
    $errors['username'] = __('error_username_length', [], $current_api_language);
} elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
    $errors['username'] = __('error_username_format', [], $current_api_language);
} elseif ($username !== $current_user_data['username']) {
    // Check uniqueness if username is being changed
    try {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT id FROM users WHERE username = :username AND id != :user_id LIMIT 1");
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':user_id', $user_id_from_session, PDO::PARAM_INT);
        $stmt->execute();
        if ($stmt->fetch()) {
            $errors['username'] = __('error_username_taken', [], $current_api_language); // "This username is already taken."
        } else {
            $fields_to_update['username'] = $username;
        }
    } catch (PDOException $e) { /* Handled by general try-catch later */ throw $e; }
}

// Language Preference
$available_languages_map = defined('SUPPORTED_LANGUAGES_MAP') ? SUPPORTED_LANGUAGES_MAP : ['en' => 'English', 'jp' => '日本語', 'tw' => '繁體中文'];
if (empty($language_preference)) {
    $errors['language_preference'] = __('error_language_preference_required', [], $current_api_language); // "Language preference is required."
} elseif (!array_key_exists($language_preference, $available_languages_map)) {
    $errors['language_preference'] = __('error_language_preference_invalid', [], $current_api_language); // "Invalid language selected."
} else {
    $fields_to_update['language_preference'] = $language_preference;
}

// Timezone
$available_timezones = DateTimeZone::listIdentifiers(DateTimeZone::ALL);
if (empty($timezone)) {
    $errors['timezone'] = __('error_timezone_required', [], $current_api_language); // "Timezone is required."
} elseif (!in_array($timezone, $available_timezones)) {
    $errors['timezone'] = __('error_timezone_invalid', [], $current_api_language); // "Invalid timezone selected."
} else {
    $fields_to_update['timezone'] = $timezone;
}

// Password Change Validation (only if new_password is provided)
if (!empty($new_password)) {
    $update_password_flag = true;
    if (empty($current_password)) {
        $errors['current_password'] = __('error_current_password_required_for_change', [], $current_api_language); // "Current password is required to set a new one."
    } elseif (!password_verify($current_password, $current_user_data['password'])) {
        $errors['current_password'] = __('error_current_password_incorrect', [], $current_api_language); // "Incorrect current password."
    }

    if (strlen($new_password) < 8) {
        $errors['new_password'] = __('error_password_min_length', [], $current_api_language);
    }
    // Add other complexity rules for new_password if desired, matching registration.
    /*
    elseif (!preg_match('/[A-Z]/', $new_password) || !preg_match('/[a-z]/', $new_password) ||
            !preg_match('/[0-9]/', $new_password) || !preg_match('/[\W_]/', $new_password)) {
        $errors['new_password'] = __('error_password_complexity', [], $current_api_language);
    }
    */

    if ($new_password !== $confirm_new_password) {
        $errors['confirm_new_password'] = __('error_passwords_do_not_match', [], $current_api_language);
    }

    if (!isset($errors['current_password']) && !isset($errors['new_password']) && !isset($errors['confirm_new_password'])) {
        $hashed_new_password = password_hash($new_password, PASSWORD_ARGON2ID);
        if ($hashed_new_password === false) {
             error_log("Profile Update API: Password hashing failed for user ID: {$user_id_from_session}");
             // This is a server error, not a user validation error.
             http_response_code(500);
             echo json_encode(['success' => false, 'message' => __('error_server_generic', [], $current_api_language)]);
             exit;
        }
        $fields_to_update['password'] = $hashed_new_password;
    }
}


// --- Process Update or Return Errors ---
if (!empty($errors)) {
    http_response_code(422); // Unprocessable Entity
    echo json_encode(['success' => false, 'message' => __('error_validation_failed', [], $current_api_language), 'errors' => $errors]);
    exit;
}

if (empty($fields_to_update) && !$update_password_flag) { // Check if password flag was set but failed validation
    http_response_code(200); // OK, but nothing to update
    echo json_encode(['success' => true, 'message' => __('profile_update_no_changes', [], $current_api_language)]); // "No changes detected to update."
    exit;
}
if (empty($fields_to_update) && $update_password_flag && !isset($fields_to_update['password'])){
    // This means new password was provided, but it failed validation, and no other fields changed.
    // Errors would have been sent already. This state should ideally not be reached if errors were sent.
    // However, as a safeguard:
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => __('error_validation_failed', [], $current_api_language), 'errors' => $errors]); // Resend errors if any
    exit;
}


// --- Database Update ---
try {
    $db = Database::getInstance()->getConnection();
    $sql_parts = [];
    foreach (array_keys($fields_to_update) as $field) {
        $sql_parts[] = "{$field} = :{$field}";
    }

    if (empty($sql_parts)) { // Should be caught by "no changes" check above, but defensive
        http_response_code(200);
        echo json_encode(['success' => true, 'message' => __('profile_update_no_changes', [], $current_api_language)]);
        exit;
    }

    $sql = "UPDATE users SET " . implode(', ', $sql_parts) . " WHERE id = :user_id";
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':user_id', $user_id_from_session, PDO::PARAM_INT);

    foreach ($fields_to_update as $field => $value) {
        $stmt->bindParam(":{$field}", $fields_to_update[$field]); // Bind actual value
    }

    if ($stmt->execute()) {
        // Update session if relevant fields changed
        if (isset($fields_to_update['username'])) {
            $_SESSION['username'] = $fields_to_update['username'];
        }
        if (isset($fields_to_update['language_preference'])) {
            $_SESSION['language_preference'] = $fields_to_update['language_preference'];
            $GLOBALS['current_language'] = $fields_to_update['language_preference']; // Update global for current request too
        }
        // Timezone is not typically stored in session for direct use by PHP's date functions,
        // but could be if app logic requires it beyond user display.

        http_response_code(200); // OK
        echo json_encode([
            'success' => true,
            'message' => __('profile_update_success', [], $current_api_language), // "Profile updated successfully!"
            'updated_fields' => array_keys($fields_to_update) // Inform client what changed
        ]);
    } else {
        error_log("Profile Update API: DB execution error for user ID {$user_id_from_session}.");
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => __('profile_update_failed_db', [], $current_api_language)]); // "Failed to update profile due to a database error."
    }

} catch (PDOException $e) {
    error_log("Profile Update API (PDOException): " . $e->getMessage() . " for user ID {$user_id_from_session}.");
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => __('error_server_generic', [], $current_api_language)]);
} catch (Exception $e) {
    error_log("Profile Update API (Exception): " . $e->getMessage() . " for user ID {$user_id_from_session}.");
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => __('error_server_generic', [], $current_api_language)]);
}

exit;

<?php
// Translation string placeholders for this API
// __('error_profile_update_mismatch', [], $current_api_language);
// __('error_username_taken', [], $current_api_language); // Reused
// __('error_language_preference_required', [], $current_api_language);
// __('error_language_preference_invalid', [], $current_api_language);
// __('error_timezone_required', [], $current_api_language);
// __('error_timezone_invalid', [], $current_api_language);
// __('error_current_password_required_for_change', [], $current_api_language);
// __('error_current_password_incorrect', [], $current_api_language);
// __('profile_update_no_changes', [], $current_api_language);
// __('profile_update_success', [], $current_api_language);
// __('profile_update_failed_db', [], $current_api_language);
?>
