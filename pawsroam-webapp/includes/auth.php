<?php
/**
 * PawsRoam Authentication Functions
 *
 * This file contains functions related to user authentication, session management,
 * and role checking.
 */

// Ensure session is started, as auth functions rely heavily on it.
if (session_status() == PHP_SESSION_NONE) {
    // This should ideally be handled by a central bootstrap/index.php file
    // to ensure consistent session configuration.
    session_start([
        'cookie_httponly' => true,
        'cookie_secure' => isset($_SERVER['HTTPS']), // Send cookie only over HTTPS if site is HTTPS
        'cookie_samesite' => 'Lax' // Provides a good balance of security and usability
    ]);
}

// Ensure functions.php (with redirect, base_url, e) is available.
// This path assumes auth.php and functions.php are in the same 'includes' directory.
if (file_exists(__DIR__ . '/functions.php')) {
    // Use require_once to prevent multiple inclusions if already included by index.php
    require_once __DIR__ . '/functions.php';
} else {
    // Log an error or handle if critical functions are missing.
    // This might happen if file structure changes or if this file is used in an unexpected context.
    error_log("Warning: functions.php not found from auth.php. Some auth functions might fail.");
}


/**
 * Checks if a user is currently logged in by verifying 'user_id' in session.
 *
 * @return bool True if logged in, false otherwise.
 */
function is_logged_in() {
    // Additionally, consider checking a timestamp for session expiry if implementing manual timeout.
    // For example: if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT_SECONDS)) { logout_user(); return false; }
    // $_SESSION['last_activity'] = time(); // Update on each authenticated action.
    return isset($_SESSION['user_id']) && is_numeric($_SESSION['user_id']);
}

/**
 * Gets the ID of the currently logged-in user.
 *
 * @return int|null User ID if logged in, null otherwise.
 */
function current_user_id() {
    return isset($_SESSION['user_id']) && is_numeric($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
}

/**
 * Gets the username of the currently logged-in user.
 *
 * @return string|null Username if logged in, null otherwise.
 */
function current_username() {
    return isset($_SESSION['username']) ? (string)$_SESSION['username'] : null;
}

/**
 * Gets the role of the currently logged-in user.
 *
 * @return string|null User role if logged in, null otherwise.
 */
function current_user_role() {
    return isset($_SESSION['role']) ? (string)$_SESSION['role'] : null;
}

/**
 * Gets the language preference of the currently logged-in user from session.
 *
 * @return string|null Language code if set, null otherwise.
 */
function current_user_language_pref() {
    return isset($_SESSION['language_preference']) ? (string)$_SESSION['language_preference'] : null;
}

/**
 * If the user is not logged in, redirects them to the specified URL (usually login page).
 * Stores the intended URL in session to redirect back after login.
 *
 * @param string $redirect_url_path The application-relative path to redirect to (e.g., '/login').
 */
function require_login($redirect_url_path = '/login') {
    if (!is_logged_in()) {
        if (session_status() == PHP_SESSION_ACTIVE) { // Ensure session is active before writing
             $_SESSION['return_to_url'] = $_SERVER['REQUEST_URI'] ?? '/';
        }

        if (function_exists('redirect') && function_exists('base_url')) {
            redirect(base_url($redirect_url_path));
        } else {
            // Basic fallback if redirect/base_url functions from functions.php aren't loaded
            // This assumes $redirect_url_path is an absolute path from web root or a full URL.
            // It's less robust.
            error_log("Warning: redirect() or base_url() function not available in require_login(). Using basic header redirect.");
            header("Location: " . $redirect_url_path);
            exit;
        }
    }
    // If logged in, consider updating last activity timestamp for session timeout logic
    // if (isset($_SESSION['user_id'])) $_SESSION['last_activity'] = time();
}

/**
 * Checks if the currently logged-in user has a specific role or one of an array of roles.
 *
 * @param string|array $required_role_or_roles A single role string (e.g., 'admin') or an array of role strings (e.g., ['admin', 'editor']).
 * @return bool True if the user is logged in and has at least one of the required roles, false otherwise.
 */
function has_role($required_role_or_roles) {
    if (!is_logged_in()) {
        return false;
    }

    $user_current_role = current_user_role();
    if ($user_current_role === null) {
        return false;
    }

    if (is_array($required_role_or_roles)) {
        return in_array($user_current_role, $required_role_or_roles, true);
    }
    return $user_current_role === (string)$required_role_or_roles;
}

/**
 * If the user does not have the required role(s), redirects them or shows a 403 Forbidden error.
 * Ensures the user is logged in first.
 *
 * @param string|array $required_role_or_roles Role(s) required.
 * @param string|null $redirect_url_path Application-relative path to redirect to if access is denied.
 *                                       If null, a 403 Forbidden error page/message is shown. Defaults to '/'.
 */
function require_role($required_role_or_roles, $redirect_url_path = '/') {
    require_login(); // Ensures user is logged in. If not, this will handle the redirect.

    if (!has_role($required_role_or_roles)) {
        http_response_code(403); // Set 403 Forbidden status

        if ($redirect_url_path !== null) {
            // Optionally, set a flash message if a system is in place
            // e.g., set_flash_message('error', 'Access Denied: You do not have the required permissions.');

            if (function_exists('redirect') && function_exists('base_url')) {
                redirect(base_url($redirect_url_path));
            } else {
                error_log("Warning: redirect() or base_url() function not available in require_role(). Using basic header redirect for 403.");
                header("Location: " . $redirect_url_path);
                exit;
            }
        } else {
            // Display a generic 403 error page or message.
            // Ideally, include a dedicated 403 error page:
            // if (file_exists(BASE_PATH . DS . 'pages' . DS . 'errors' . DS . '403.php')) {
            //     include BASE_PATH . DS . 'pages' . DS . 'errors' . DS . '403.php';
            // } else {
                echo "<h1>403 Forbidden</h1><p>You do not have permission to access this page or perform this action.</p>";
            // }
            exit;
        }
    }
}


/**
 * Logs a user in by setting up their session variables.
 * Called after successful authentication (e.g., password verification).
 *
 * @param array $user_data Associative array containing user details. Expected keys: 'id', 'username', 'role'. Optional: 'language_preference'.
 * @return bool True on successful session setup, false otherwise (e.g., if $user_data is incomplete).
 */
function login_user($user_data) {
    if (!is_array($user_data) || !isset($user_data['id']) || !isset($user_data['username']) || !isset($user_data['role'])) {
        error_log("Login attempt failed: Incomplete user data provided for session setup.");
        return false;
    }

    // Regenerate session ID upon login to prevent session fixation attacks.
    if (session_status() == PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
    }

    $_SESSION['user_id'] = (int)$user_data['id'];
    $_SESSION['username'] = (string)$user_data['username'];
    $_SESSION['role'] = (string)$user_data['role'];
    $_SESSION['language_preference'] = isset($user_data['language_preference']) ? (string)$user_data['language_preference'] : (defined('DEFAULT_LANGUAGE') ? DEFAULT_LANGUAGE : 'en');
    // $_SESSION['login_time'] = time(); // Optional: Store login time for activity tracking
    // $_SESSION['last_activity'] = time(); // Optional: For implementing session timeouts

    return true;
}

/**
 * Logs the current user out: clears session data, destroys session, and optionally redirects.
 *
 * @param string|null $redirect_url_path Application-relative path to redirect to after logout.
 *                                     If null, no redirect is performed by this function.
 * @return void
 */
function logout_user($redirect_url_path = null) { // Default to null, so API can control redirect via JSON
    // Unset all of the session variables specific to the user.
    // It's safer to unset specific keys rather than $_SESSION = array(); if other session data needs to persist.
    unset($_SESSION['user_id']);
    unset($_SESSION['username']);
    unset($_SESSION['role']);
    unset($_SESSION['language_preference']);
    unset($_SESSION['login_time']);
    unset($_SESSION['last_activity']);
    unset($_SESSION[CSRF_TOKEN_NAME ?? 'csrf_token']); // Clear CSRF token as well

    // If using "Remember Me" cookies, clear them here.
    // Example:
    // if (isset($_COOKIE['remember_me_token'])) { // This cookie name should be a constant
    //     setcookie(REMEMBER_ME_COOKIE_NAME ?? 'pawsroam_remember_me', '', time() - 3600, '/', '', isset($_SERVER['HTTPS']), true);
    // }
    // More robustly, clear the specific cookie name used
    $remember_me_cookie_name = defined('REMEMBER_ME_COOKIE_NAME') ? REMEMBER_ME_COOKIE_NAME : 'pawsroam_remember_me';
    if (isset($_COOKIE[$remember_me_cookie_name])) {
        setcookie($remember_me_cookie_name, '', time() - 3600, '/', '', isset($_SERVER['HTTPS']), true);
    }

    // Invalidate the remember me token in the database for the user being logged out
    // This requires user_id *before* session is fully cleared or just before this function.
    // Let's assume we capture it if needed or the calling context handles passing it.
    // For simplicity, if current_user_id() was called before session unset, it might still be available if not everything is cleared yet.
    // However, it's safer to pass $user_id explicitly if this function is to be generic.
    // For now, we'll rely on the session having user_id just before it's fully destroyed.
    $user_id_to_clear_token = current_user_id(); // Use the function to get it before unsetting

    // Unset all of the session variables specific to the user.
    unset($_SESSION['user_id']);
    unset($_SESSION['username']);
    unset($_SESSION['role']);
    unset($_SESSION['language_preference']);
    unset($_SESSION['login_time']);
    unset($_SESSION['last_activity']);
    unset($_SESSION[CSRF_TOKEN_NAME ?? 'csrf_token']); // Clear CSRF token as well

    // Destroy the session if you want to clear everything.
    // Be cautious if other parts of the application rely on session data not related to user login.
    if (session_status() == PHP_SESSION_ACTIVE) {
        session_destroy();
    }

    // After session is destroyed, if we had a user_id, clear their remember_me token from DB
    if ($user_id_to_clear_token && defined('DB_HOST')) { // Check DB_HOST to infer DB is usable
        try {
            // Need to get a DB instance. This is tricky if Database class relies on session for something,
            // but it shouldn't.
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("UPDATE users SET remember_token_hash = NULL, remember_token_expires_at = NULL WHERE id = :user_id");
            $stmt->bindParam(':user_id', $user_id_to_clear_token, PDO::PARAM_INT);
            $stmt->execute();
        } catch (PDOException $e) {
            error_log("Failed to clear remember_me token from DB for user ID {$user_id_to_clear_token} on logout: " . $e->getMessage());
            // Non-fatal for logout process itself, but should be logged.
        } catch (Exception $e) {
            error_log("General error clearing remember_me token from DB for user ID {$user_id_to_clear_token} on logout: " . $e->getMessage());
        }
    }

    // Start a new clean session for guest users or subsequent logins
    // This ensures that even after logout, a valid session (though empty of user data) exists
    // for things like CSRF tokens for the login form.
    if (session_status() == PHP_SESSION_NONE) {
        session_start([
            'cookie_httponly' => true,
            'cookie_secure' => isset($_SERVER['HTTPS']),
            'cookie_samesite' => 'Lax'
        ]);
    }


    if ($redirect_url_path !== null) {
        if (function_exists('redirect') && function_exists('base_url')) {
            redirect(base_url($redirect_url_path));
        } else {
            error_log("Warning: redirect() or base_url() function not available in logout_user(). Using basic header redirect.");
            header("Location: " . $redirect_url_path);
            exit;
        }
    }
}

// --- Further Authentication Features (Stubs or for later implementation) ---

/**
 * Checks and processes a "Remember Me" cookie if present and no active session.
 * This function would be called early in the request lifecycle (e.g., in index.php).
 */
/*
function process_remember_me_cookie() {
    if (is_logged_in() || !isset($_COOKIE['pawsroam_remember_token'])) {
        return;
    }
    $token_from_cookie = $_COOKIE['pawsroam_remember_token'];
    // 1. Validate token format.
    // 2. Extract selector and validator parts if using that scheme.
    // 3. Look up selector in database.
    // 4. If found, securely compare validator hash with the hash of the validator from cookie.
    // 5. If match and not expired:
    //    - Fetch user details for the user_id associated with the token.
    //    - login_user($user_details);
    //    - Issue a new remember me token (selector/validator pair) for the user to prevent reuse of old one.
    //    - Update database with new token, set new cookie.
    // 6. If token is invalid, expired, or selector/validator mismatch:
    //    - Clear the cookie: setcookie('pawsroam_remember_token', '', time() - 3600, '/', '', isset($_SERVER['HTTPS']), true);
    //    - If selector was found but validator mismatched, consider it a potential theft attempt and invalidate all remember tokens for that user.
}
*/

?>
