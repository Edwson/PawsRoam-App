<?php
/**
 * API Endpoint for User Registration
 * Method: POST
 * Expected FormData: username, email, password, confirm_password, agree_terms, csrf_token
 */

// Bootstrap: Load essential files and configurations
// This assumes index.php or a similar bootstrap process has already run if this API is routed through it.
// If accessed directly, we need to ensure core components are loaded.
if (session_status() == PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_secure' => isset($_SERVER['HTTPS']),
        'cookie_samesite' => 'Lax'
    ]);
}

// Define BASE_PATH if not already set (common in direct API endpoint access)
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__, 4)); // Adjust depth as necessary from /api/v1/auth/ to project root
}
if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}

// Load dependencies - paths relative to this file's location or using BASE_PATH
$required_files = [
    BASE_PATH . DS . 'config' . DS . 'constants.php',
    BASE_PATH . DS . 'config' . DS . 'database.php',
    BASE_PATH . DS . 'includes' . DS . 'functions.php',
    BASE_PATH . DS . 'includes' . DS . 'translation.php', // For error messages
    // auth.php might not be directly needed here, but good to have for consistency if any auth checks were relevant
];
foreach ($required_files as $file) {
    if (file_exists($file)) {
        require_once $file;
    } else {
        // Handle missing critical file - perhaps log and exit with a generic error
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Server configuration error. Please try again later. File missing: ' . basename($file)]);
        exit;
    }
}

// Set default language for API error messages if not available from a user session
$current_api_language = $GLOBALS['current_language'] ?? DEFAULT_LANGUAGE ?? 'en';

header('Content-Type: application/json');

// --- Request Method Validation ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['success' => false, 'message' => __('error_method_not_allowed', [], $current_api_language)]);
    exit;
}

// --- CSRF Token Validation ---
if (!validate_csrf_token($_POST[CSRF_TOKEN_NAME ?? 'csrf_token'] ?? null)) {
    http_response_code(403); // Forbidden
    echo json_encode(['success' => false, 'message' => __('error_csrf_token_invalid', [], $current_api_language)]);
    exit;
}

// --- Input Collection & Basic Sanitization ---
$username = trim($_POST['username'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? ''; // No trim for password before validation
$confirm_password = $_POST['confirm_password'] ?? '';
$agree_terms = isset($_POST['agree_terms']); // Check if checkbox was ticked

$errors = [];

// --- Input Validation ---

// Username
if (empty($username)) {
    $errors['username'] = __('error_username_required', [], $current_api_language);
} elseif (strlen($username) < 3 || strlen($username) > 25) {
    $errors['username'] = __('error_username_length', [], $current_api_language);
} elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
    $errors['username'] = __('error_username_format', [], $current_api_language);
}

// Email
if (empty($email)) {
    $errors['email'] = __('error_email_required', [], $current_api_language);
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = __('error_email_invalid', [], $current_api_language);
} else {
    // Check for email uniqueness
    try {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        if ($stmt->fetch()) {
            $errors['email'] = __('error_email_taken', [], $current_api_language);
        }
    } catch (PDOException $e) {
        error_log("Database error during email check: " . $e->getMessage());
        // Don't expose DB error directly to user, but signal a server-side issue
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => __('error_server_generic', [], $current_api_language)]);
        exit;
    }
}

// Password
if (empty($password)) {
    $errors['password'] = __('error_password_required', [], $current_api_language);
} elseif (strlen($password) < 8) {
    $errors['password'] = __('error_password_min_length', [], $current_api_language);
}
// Add more password complexity rules if desired (e.g., regex for uppercase, number, symbol)
// Example:
/*
elseif (!preg_match('/[A-Z]/', $password) ||
        !preg_match('/[a-z]/', $password) ||
        !preg_match('/[0-9]/', $password) ||
        !preg_match('/[\W_]/', $password) // \W is non-word chars, _ is underscore
) {
    $errors['password'] = __('error_password_complexity', [], $current_api_language);
}
*/

// Confirm Password
if (empty($confirm_password)) {
    $errors['confirm_password'] = __('error_confirm_password_required', [], $current_api_language);
} elseif ($password !== $confirm_password) {
    $errors['confirm_password'] = __('error_passwords_do_not_match', [], $current_api_language);
}

// Agree to Terms
if (!$agree_terms) {
    $errors['agree_terms'] = __('error_agree_terms_required', [], $current_api_language);
}


// --- Process Registration or Return Errors ---
if (!empty($errors)) {
    http_response_code(422); // Unprocessable Entity (validation errors)
    echo json_encode(['success' => false, 'message' => __('error_validation_failed', [], $current_api_language), 'errors' => $errors]);
    exit;
}

// --- All Validations Passed - Create User ---
try {
    $hashed_password = password_hash($password, PASSWORD_ARGON2ID);
    if ($hashed_password === false) {
        throw new Exception("Password hashing failed.");
    }

    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare(
        "INSERT INTO users (username, email, password, role, status, language_preference, timezone, created_at)
         VALUES (:username, :email, :password, :role, :status, :language_preference, :timezone, NOW())"
    );

    $default_role = 'user';
    $default_status = 'active'; // Or 'pending' if email verification is implemented
    $default_lang_pref = DEFAULT_LANGUAGE ?? 'en'; // From constants.php
    $default_timezone = DEFAULT_TIMEZONE ?? 'UTC'; // From constants.php

    $stmt->bindParam(':username', $username);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':password', $hashed_password);
    $stmt->bindParam(':role', $default_role);
    $stmt->bindParam(':status', $default_status);
    $stmt->bindParam(':language_preference', $default_lang_pref);
    $stmt->bindParam(':timezone', $default_timezone);

    if ($stmt->execute()) {
        $user_id = $db->lastInsertId();
        // Optional: Automatically log the user in after registration
        // if (function_exists('login_user')) {
        //    login_user(['id' => $user_id, 'username' => $username, 'role' => $default_role, 'language_preference' => $default_lang_pref]);
        // }
        // Optional: Send a welcome email (would require mailer setup)

        http_response_code(201); // Created
        echo json_encode([
            'success' => true,
            'message' => __('success_registration', [], $current_api_language),
            'user_id' => $user_id, // Optionally return user_id
            'redirect_url' => base_url('/login?status=registered') // Suggest redirect to login
        ]);
    } else {
        error_log("User registration failed: DB execution error. Email: " . $email);
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => __('error_registration_failed_db', [], $current_api_language)]);
    }

} catch (PDOException $e) {
    error_log("Database error during registration: " . $e->getMessage() . " for email: " . $email);
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => __('error_server_generic', [], $current_api_language)]);
} catch (Exception $e) { // Catch other exceptions like password_hash failure
    error_log("General error during registration: " . $e->getMessage() . " for email: " . $email);
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => __('error_server_generic', [], $current_api_language)]);
}

exit;

<?php
// Placeholder for translation strings used in this API endpoint
// __('error_method_not_allowed', [], $current_api_language);
// __('error_csrf_token_invalid', [], $current_api_language);
// __('error_username_required', [], $current_api_language);
// __('error_username_length', [], $current_api_language); // "Username must be between 3 and 25 characters."
// __('error_username_format', [], $current_api_language); // "Username can only contain letters, numbers, and underscores."
// __('error_email_required', [], $current_api_language);
// __('error_email_invalid', [], $current_api_language);
// __('error_email_taken', [], $current_api_language); // "This email address is already registered."
// __('error_password_required', [], $current_api_language);
// __('error_password_min_length', [], $current_api_language); // "Password must be at least 8 characters long."
// __('error_password_complexity', [], $current_api_language); // "Password must include uppercase, lowercase, number, and a special character."
// __('error_confirm_password_required', [], $current_api_language);
// __('error_passwords_do_not_match', [], $current_api_language);
// __('error_agree_terms_required', [], $current_api_language); // "You must agree to the terms and conditions."
// __('error_validation_failed', [], $current_api_language); // "Registration failed due to validation errors."
// __('success_registration', [], $current_api_language); // "Registration successful! You can now log in."
// __('error_registration_failed_db', [], $current_api_language); // "Registration failed due to a database error. Please try again."
// __('error_server_generic', [], $current_api_language); // "An unexpected server error occurred. Please try again later."
?>
