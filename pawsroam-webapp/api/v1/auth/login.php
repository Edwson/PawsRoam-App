<?php
/**
 * API Endpoint for User Login
 * Method: POST
 * Expected FormData: email, password, [remember_me], csrf_token
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
    BASE_PATH . DS . 'config' . DS . 'constants.php',
    BASE_PATH . DS . 'config' . DS . 'database.php',
    BASE_PATH . DS . 'includes' . DS . 'functions.php',
    BASE_PATH . DS . 'includes' . DS . 'translation.php',
    BASE_PATH . DS . 'includes' . DS . 'auth.php' // For login_user function
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

// --- Request Method Validation ---
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
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? ''; // Password itself should not be trimmed before hashing/verification
$remember_me = isset($_POST['remember_me']);

$errors = [];

if (empty($email)) {
    $errors['email'] = __('error_email_required', [], $current_api_language);
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = __('error_email_invalid', [], $current_api_language);
}

if (empty($password)) {
    $errors['password'] = __('error_password_required', [], $current_api_language);
}

if (!empty($errors)) {
    http_response_code(422); // Unprocessable Entity
    echo json_encode(['success' => false, 'message' => __('error_validation_failed', [], $current_api_language), 'errors' => $errors]);
    exit;
}

// --- Attempt Login ---
try {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT id, username, email, password, role, language_preference, status FROM users WHERE email = :email LIMIT 1");
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        http_response_code(401); // Unauthorized
        echo json_encode(['success' => false, 'message' => __('alert_login_failed_credentials', [], $current_api_language)]);
        exit;
    }

    // Verify password
    if (!password_verify($password, $user['password'])) {
        http_response_code(401); // Unauthorized
        echo json_encode(['success' => false, 'message' => __('alert_login_failed_credentials', [], $current_api_language)]);
        exit;
    }

    // Check user status (e.g., if account is 'pending' or 'suspended')
    if ($user['status'] === 'pending') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => __('error_account_pending_verification', [], $current_api_language)]); // "Your account is pending verification."
        exit;
    }
    if ($user['status'] === 'suspended') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => __('error_account_suspended', [], $current_api_language)]); // "Your account has been suspended."
        exit;
    }
    if ($user['status'] !== 'active') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => __('error_account_inactive_contact_support', [], $current_api_language)]); // "Your account is not active. Please contact support."
        exit;
    }

    // --- Login Successful - Setup Session ---
    if (!login_user($user)) { // login_user is from auth.php, handles session_regenerate_id and sets session vars
        error_log("Login failed: login_user() function returned false for user ID: " . $user['id']);
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => __('error_login_session_failed', [], $current_api_language)]); // "Could not start a session. Please try again."
        exit;
    }

    $response_data = [
        'success' => true,
        'message' => __('success_login_redirecting', [], $current_api_language),
        'user' => [ // Send back some non-sensitive user info
            'id' => $user['id'],
            'username' => $user['username'],
            'role' => $user['role']
        ]
    ];

    // Handle "Remember Me"
    if ($remember_me) {
        // This is a simplified "Remember Me". A more robust solution uses selector/validator pairs.
        // For now, generate a strong token. Store its hash in DB, send token to cookie.
        // Column `remember_token_hash` and `remember_token_expires_at` should be in `users` table.
        try {
            $token_lifetime_days = 30; // e.g., 30 days
            $remember_token = bin2hex(random_bytes(32));
            $remember_token_hash = password_hash($remember_token, PASSWORD_DEFAULT); // Use default, fast hash for this
            $expires_at = date('Y-m-d H:i:s', time() + ($token_lifetime_days * 24 * 60 * 60));

            $updateStmt = $db->prepare("UPDATE users SET remember_token_hash = :token_hash, remember_token_expires_at = :expires_at WHERE id = :user_id");
            $updateStmt->bindParam(':token_hash', $remember_token_hash);
            $updateStmt->bindParam(':expires_at', $expires_at);
            $updateStmt->bindParam(':user_id', $user['id']);
            $updateStmt->execute();

            setcookie(
                REMEMBER_ME_COOKIE_NAME ?? 'pawsroam_remember_me',
                $remember_token,
                time() + ($token_lifetime_days * 24 * 60 * 60),
                '/', // Cookie path
                '',  // Cookie domain (empty for current host)
                isset($_SERVER['HTTPS']), // Secure flag
                true // HttpOnly flag
            );
        } catch (Exception $e) {
            error_log("Remember me token generation/storage failed: " . $e->getMessage());
            // Non-fatal, login still proceeds.
        }
    }

    // Check for a return_to_url from session (set by require_login)
    if (!empty($_SESSION['return_to_url'])) {
        $response_data['return_to_url'] = $_SESSION['return_to_url'];
        unset($_SESSION['return_to_url']); // Clear it after use
    } else {
        // Default redirect based on role, or just to home
        $response_data['redirect_url'] = base_url( (in_array($user['role'], ['super_admin', 'business_admin'])) ? '/admin' : '/' );
    }


    http_response_code(200); // OK
    echo json_encode($response_data);

} catch (PDOException $e) {
    error_log("Database error during login: " . $e->getMessage() . " for email: " . $email);
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => __('error_server_generic', [], $current_api_language)]);
} catch (Exception $e) {
    error_log("General error during login: " . $e->getMessage() . " for email: " . $email);
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => __('error_server_generic', [], $current_api_language)]);
}

exit;

<?php
// Placeholder for translation strings
// __('error_account_pending_verification', [], $current_api_language);
// __('error_account_suspended', [], $current_api_language);
// __('error_account_inactive_contact_support', [], $current_api_language);
// __('error_login_session_failed', [], $current_api_language);
?>
