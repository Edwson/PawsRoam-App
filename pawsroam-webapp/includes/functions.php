<?php
/**
 * PawsRoam Utility Functions
 *
 * This file contains various helper and utility functions used throughout the application.
 */

// Ensure session is started, as CSRF tokens rely on it.
if (session_status() == PHP_SESSION_NONE) {
    // It's generally better to start sessions at the very beginning of the request (e.g., in index.php or a bootstrap file).
    // However, including it here as a safeguard if this file is included before a session is explicitly started.
    // Consider session configuration options for security.
    session_start([
        'cookie_httponly' => true,
        'cookie_secure' => isset($_SERVER['HTTPS']), // Assumes HTTPS for secure cookies
        'cookie_samesite' => 'Lax'
    ]);
}

if (!defined('CSRF_TOKEN_NAME')) {
    // Default CSRF token name, can be overridden in constants.php if needed
    define('CSRF_TOKEN_NAME', $_ENV['CSRF_TOKEN_NAME'] ?? 'csrf_token');
}

/**
 * Generates a CSRF token, stores it in the session, and returns it.
 * If a token already exists in the session, it returns the existing one
 * unless $force_regenerate is true.
 *
 * @param bool $force_regenerate If true, a new token will be generated even if one exists.
 * @return string The CSRF token.
 */
function generate_csrf_token($force_regenerate = false) {
    $token_name = CSRF_TOKEN_NAME;
    if ($force_regenerate || !isset($_SESSION[$token_name])) {
        try {
            $_SESSION[$token_name] = bin2hex(random_bytes(32));
        } catch (Exception $e) {
            // Handle error if random_bytes fails (highly unlikely)
            error_log('CSRF token generation failed: ' . $e->getMessage());
            // Fallback or rethrow, depending on security policy
            // For now, let's use a less secure fallback if random_bytes is unavailable (PHP < 7)
            // This is NOT recommended for production if random_bytes is expected.
            $_SESSION[$token_name] = sha1(uniqid(mt_rand(), true));
        }
    }
    return $_SESSION[$token_name];
}

/**
 * Validates a given CSRF token against the one stored in the session.
 * To prevent timing attacks, use hash_equals for comparison.
 * The token in the session is typically cleared after successful validation
 * for one-time use tokens, but this depends on the application's strategy.
 * For simplicity here, we are not clearing it automatically, allowing the same token
 * to be valid for the lifetime of the session or until regenerated.
 * For stricter one-time use, you would unset($_SESSION[CSRF_TOKEN_NAME]) after validation.
 *
 * @param string|null $token_from_form The token received from the form submission.
 * @return bool True if the token is valid, false otherwise.
 */
function validate_csrf_token($token_from_form = null) {
    $token_name = CSRF_TOKEN_NAME;
    if ($token_from_form === null && isset($_POST[$token_name])) {
        $token_from_form = $_POST[$token_name];
    } elseif ($token_from_form === null && isset($_GET[$token_name])) { // Less common for state-changing actions
        $token_from_form = $_GET[$token_name];
    }


    if (empty($token_from_form) || !isset($_SESSION[$token_name])) {
        error_log("CSRF validation failed: Form token or session token missing. Form token provided: " . ($token_from_form ? 'Yes' : 'No') . ", Session token set: " . (isset($_SESSION[$token_name]) ? 'Yes' : 'No'));
        return false;
    }

    $session_token = $_SESSION[$token_name];

    if (hash_equals($session_token, $token_from_form)) {
        // Optional: For one-time tokens, unset the session token after successful validation
        // unset($_SESSION[$token_name]); // This makes tokens single-use. Be careful with AJAX or multi-step forms.
        return true;
    } else {
        error_log("CSRF validation failed: Token mismatch. Session token: " . $session_token . ", Form token: " . $token_from_form);
        // To help debug, but be careful about logging actual tokens in production logs if they are sensitive.
        // Consider logging only a hash or a portion if needed for diagnostics.
    }

    return false;
}

/**
 * Outputs a hidden input field with the CSRF token.
 * Call this function inside your HTML forms.
 *
 * @param bool $regenerate_if_not_set If true, generates a token if one isn't already set.
 *                                    It's generally better to ensure token is generated on page load.
 * @return string HTML hidden input field for the CSRF token.
 */
function csrf_input_field($regenerate_if_not_set = true) {
    $token_name = CSRF_TOKEN_NAME;
    $token = '';
    if (isset($_SESSION[$token_name])) {
        $token = $_SESSION[$token_name];
    } elseif ($regenerate_if_not_set) {
        $token = generate_csrf_token();
    }

    if (!empty($token)) {
        return '<input type="hidden" name="' . htmlspecialchars($token_name, ENT_QUOTES, 'UTF-8') . '" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }
    // If no token, perhaps log an error or return an empty string.
    // For security, a form should ideally not render if CSRF token cannot be generated/retrieved.
    error_log("CSRF input field could not be generated: Token is empty.");
    return '<!-- CSRF Token Error -->';
}


/**
 * Escapes HTML special characters for safe output.
 * A simple wrapper around htmlspecialchars.
 *
 * @param string|null $string The string to escape.
 * @return string The escaped string.
 */
function e($string) {
    if ($string === null) {
        return '';
    }
    return htmlspecialchars((string)$string, ENT_QUOTES, 'UTF-8');
}

/**
 * Redirects to a given URL.
 *
 * @param string $url The URL to redirect to.
 * @param int $status_code The HTTP status code for the redirect (default 302).
 */
function redirect($url, $status_code = 302) {
    // Ensure URL is not manipulating headers further if it comes from user input (though usually it's app-defined)
    // Basic sanitization for typical use cases.
    $url = filter_var($url, FILTER_SANITIZE_URL);
    if (headers_sent()) {
        // If headers already sent, use JavaScript redirect as a fallback (less reliable)
        echo "<script>window.location.href='{$url}';</script>";
        // Or display a manual link
        // echo "Headers already sent. Please <a href='{$url}'>click here to continue</a>.";
        error_log("Redirect failed: Headers already sent. Target URL: {$url}");
    } else {
        header("Location: {$url}", true, $status_code);
    }
    exit; // Important to stop script execution after redirect
}

/**
 * Returns the base URL of the application.
 * Assumes APP_URL is defined in constants.php or .env
 *
 * @param string $path Optional path to append to the base URL.
 * @return string The base URL, optionally with a path appended.
 */
function base_url($path = '') {
    $base = APP_URL ?? ''; // APP_URL should be defined (e.g. http://localhost:8000 or https://pawsroam.com)
    if (!empty($path)) {
        // Ensure no double slashes if $base ends with / and $path starts with /
        return rtrim($base, '/') . '/' . ltrim($path, '/');
    }
    return rtrim($base, '/');
}

/**
 * Dumps variable information for debugging and dies.
 * Only active if APP_DEBUG is true.
 *
 * @param mixed $var The variable to dump.
 */
function dd($var) {
    $appDebug = getenv('APP_DEBUG') === 'true' || ($_ENV['APP_DEBUG'] ?? false) === true || (defined('APP_DEBUG') && APP_DEBUG === true);
    if ($appDebug) {
        echo '<pre style="background-color: #f5f5f5; border: 1px solid #ccc; padding: 10px; margin: 10px; border-radius: 5px; font-family: monospace; white-space: pre-wrap; word-wrap: break-word;">';
        var_dump($var);
        echo '</pre>';
        // Optionally add a backtrace
        // debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        die("Debug dump complete.");
    }
}

// Add more utility functions as needed:
// - Date/time formatting
// - String manipulation
// - Array helpers
// - File system helpers (with caution)
// - etc.


/**
 * Handles a file upload. (STUB - needs full implementation for security and functionality)
 *
 * This function will eventually handle:
 * - Checking for upload errors.
 * - Validating file type and size.
 * - Generating a unique, safe filename.
 * - Creating the target directory if it doesn't exist.
 * - Moving the uploaded file to the target directory.
 *
 * @param string $file_input_name The name of the file input field in the form (e.g., 'avatar').
 * @param string $target_directory The base directory to upload files to (e.g., 'uploads/avatars/').
 *                                 This path should be relative to a defined UPLOADS_BASE_PATH or similar.
 * @param array $allowed_types An array of allowed MIME types (e.g., ['image/jpeg', 'image/png']).
 * @param int $max_size_bytes Maximum allowed file size in bytes.
 * @param string $new_filename_prefix Optional prefix for the new unique filename.
 * @return array Associative array with 'success' (bool), 'filepath' (string|null),
 *               'filename' (string|null), and 'message' (string).
 */
function handle_file_upload($file_input_name, $target_directory,
                            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'],
                            $max_size_bytes = 2097152, /* 2MB default */
                            $new_filename_prefix = 'file_') {

    // Use defined constants for base path and default limits
    $uploads_base_path = defined('UPLOADS_BASE_PATH') ? UPLOADS_BASE_PATH : BASE_PATH . DS . 'uploads';
    $default_max_size_bytes = (defined('DEFAULT_MAX_UPLOAD_SIZE_MB') ? DEFAULT_MAX_UPLOAD_SIZE_MB * 1024 * 1024 : 2 * 1024 * 1024);

    // Use passed $max_size_bytes if it's valid, otherwise default.
    $max_size_bytes = ($max_size_bytes > 0) ? $max_size_bytes : $default_max_size_bytes;

    if (!isset($_FILES[$file_input_name])) {
        return ['success' => false, 'message' => __('error_upload_no_file_input_name', [], $GLOBALS['current_language'] ?? 'en')];
    }

    $file = $_FILES[$file_input_name];

    // 1. Check for basic PHP upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $upload_errors = [
            UPLOAD_ERR_INI_SIZE   => __('error_upload_err_ini_size', [], $GLOBALS['current_language'] ?? 'en'),
            UPLOAD_ERR_FORM_SIZE  => __('error_upload_err_form_size', [], $GLOBALS['current_language'] ?? 'en'),
            UPLOAD_ERR_PARTIAL    => __('error_upload_err_partial', [], $GLOBALS['current_language'] ?? 'en'),
            UPLOAD_ERR_NO_FILE    => __('error_upload_err_no_file', [], $GLOBALS['current_language'] ?? 'en'),
            UPLOAD_ERR_NO_TMP_DIR => __('error_upload_err_no_tmp_dir', [], $GLOBALS['current_language'] ?? 'en'),
            UPLOAD_ERR_CANT_WRITE => __('error_upload_err_cant_write', [], $GLOBALS['current_language'] ?? 'en'),
            UPLOAD_ERR_EXTENSION  => __('error_upload_err_extension', [], $GLOBALS['current_language'] ?? 'en'),
        ];
        $error_message = $upload_errors[$file['error']] ?? __('error_upload_unknown', [], $GLOBALS['current_language'] ?? 'en');
        error_log("File upload error for '{$file_input_name}': Code {$file['error']} - {$error_message}. Original name: {$file['name']}");
        return ['success' => false, 'message' => $error_message, 'error_code' => $file['error']];
    }

    // UPLOAD_ERR_OK means file is present, so $file['name'] should not be empty.
    // If it somehow is, or tmp_name is empty, treat as error.
    if (empty($file['name']) || empty($file['tmp_name'])) {
         return ['success' => false, 'message' => __('error_upload_err_no_file', [], $GLOBALS['current_language'] ?? 'en')];
    }

    // 2. Validate file size
    if ($file['size'] > $max_size_bytes) {
        $max_size_mb_for_error = defined('DEFAULT_MAX_UPLOAD_SIZE_MB') ? DEFAULT_MAX_UPLOAD_SIZE_MB : ($max_size_bytes / (1024*1024));
        $error_message = sprintf(__('error_upload_too_large', [], $GLOBALS['current_language'] ?? 'en'), round($max_size_mb_for_error,1));
        return ['success' => false, 'message' => $error_message];
    }

    // 3. Validate MIME type (more reliable than $_FILES['type'])
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $actual_mime_type = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        // Unserialize $allowed_types if it's from a constant define('FOO', serialize([...]))
        $decoded_allowed_types = is_string($allowed_types) ? @unserialize($allowed_types) : $allowed_types;
        if (!is_array($decoded_allowed_types)) { $decoded_allowed_types = ['image/jpeg', 'image/png', 'image/gif']; } // Fallback

        if (!in_array($actual_mime_type, $decoded_allowed_types, true)) {
            $allowed_types_str = implode(', ', $decoded_allowed_types);
            $error_message = sprintf(__('error_upload_invalid_type', [], $GLOBALS['current_language'] ?? 'en'), $allowed_types_str);
            return ['success' => false, 'message' => $error_message . " (Detected: {$actual_mime_type})"];
        }
    } else {
        // Fallback to $_FILES['type'] if finfo is not available (less secure/reliable)
        // Log a warning if this fallback is used in production.
        if (APP_ENV !== 'development') {
            error_log("Warning: finfo_open is not available. Falling back to less reliable MIME type check for file uploads.");
        }
        $decoded_allowed_types = is_string($allowed_types) ? @unserialize($allowed_types) : $allowed_types;
        if (!is_array($decoded_allowed_types)) { $decoded_allowed_types = ['image/jpeg', 'image/png', 'image/gif']; }

        if (!in_array($file['type'], $decoded_allowed_types, true)) {
            $allowed_types_str = implode(', ', $decoded_allowed_types);
            $error_message = sprintf(__('error_upload_invalid_type', [], $GLOBALS['current_language'] ?? 'en'), $allowed_types_str);
            return ['success' => false, 'message' => $error_message . " (Reported by browser: {$file['type']})"];
        }
    }

    // 4. Generate a unique and safe filename
    $original_filename = basename($file['name']);
    $extension = strtolower(pathinfo($original_filename, PATHINFO_EXTENSION));
    // Basic sanitization for extension, ensure it's one of the expected image types if possible
    $safe_extensions_map = ['jpg', 'jpeg', 'png', 'gif']; // Whitelist extensions
    if (!in_array($extension, $safe_extensions_map)) {
        // If MIME was allowed but extension isn't common, could default or reject
        // For images, usually MIME is primary, but good to have a sensible extension.
        // If $actual_mime_type was 'image/jpeg', ensure extension is jpg/jpeg.
        // This part can be more complex based on strictness. For now, trust $extension from original if MIME passed.
        // A better approach would be to map MIME to a safe extension.
        // e.g. if ($actual_mime_type === 'image/jpeg') $extension = 'jpg';
    }
    if(empty($extension) && $actual_mime_type === 'image/jpeg') $extension = 'jpg';
    if(empty($extension) && $actual_mime_type === 'image/png') $extension = 'png';
    if(empty($extension) && $actual_mime_type === 'image/gif') $extension = 'gif';
    if(empty($extension)){ // Still no extension, reject
        return ['success' => false, 'message' => __('error_upload_unknown_file_type', [], $GLOBALS['current_language'] ?? 'en')]; // "Unknown file type or missing extension."
    }


    // Use a more robust unique name generator
    try {
        $unique_filename_stem = $new_filename_prefix . bin2hex(random_bytes(8)) . '_' . time();
    } catch (Exception $e) { // Fallback if random_bytes fails
        $unique_filename_stem = $new_filename_prefix . uniqid('', true) . '_' . time();
    }
    $unique_filename = $unique_filename_stem . '.' . $extension;


    // 5. Ensure target directory exists and is writable.
    // $target_directory is relative to $uploads_base_path, e.g., "pet-avatars/123/"
    $full_target_path = rtrim($uploads_base_path, DS) . DS . trim($target_directory, DS);

    if (!is_dir($full_target_path)) {
        if (!mkdir($full_target_path, 0755, true)) { // Create recursively with 0755 permissions
            error_log("File upload error: Failed to create target directory: {$full_target_path}");
            return ['success' => false, 'message' => __('error_upload_cannot_create_dir', [], $GLOBALS['current_language'] ?? 'en')]; // "Server error: Could not create upload directory."
        }
    }
    if (!is_writable($full_target_path)) {
        error_log("File upload error: Target directory is not writable: {$full_target_path}");
        return ['success' => false, 'message' => __('error_upload_dir_not_writable', [], $GLOBALS['current_language'] ?? 'en')]; // "Server error: Upload directory not writable."
    }

    // 6. Move the uploaded file.
    $destination_filepath_server = $full_target_path . DS . $unique_filename;

    if (move_uploaded_file($file['tmp_name'], $destination_filepath_server)) {
        // Filepath for DB should be relative to the UPLOADS_BASE_URL / UPLOADS_BASE_PATH
        // e.g., if $target_directory was "pet-avatars/123/", and UPLOADS_BASE_URL is "http://.../uploads"
        // then filepath for DB is "pet-avatars/123/unique_filename.jpg"
        $db_filepath = trim($target_directory, DS) . '/' . $unique_filename;

        return [
            'success' => true,
            'filepath' => $db_filepath, // Relative path for DB storage
            'filename' => $unique_filename, // Just the filename
            'full_server_path' => $destination_filepath_server, // For logging or further processing if needed
            'message' => __('success_upload_file_saved', [], $GLOBALS['current_language'] ?? 'en')
        ];
    } else {
        error_log("File upload error: move_uploaded_file failed for '{$file['tmp_name']}' to '{$destination_filepath_server}'. Check permissions and paths.");
        return ['success' => false, 'message' => __('error_upload_move_failed', [], $GLOBALS['current_language'] ?? 'en')];
    }
}


/**
 * Calculates the PawStar rating based on the total number of recognitions.
 * This is an initial simple logic. Future enhancements could include other factors
 * like amenities, verified status, review scores etc.
 *
 * @param int $total_recognitions The total number of community recognitions for a business.
 * @return int The calculated PawStar rating (0, 1, 2, or 3 stars).
 */
function calculate_pawstar_rating($total_recognitions) {
    $recognitions = (int)$total_recognitions;

    if ($recognitions >= 300) { // 300+ recognitions + exceptional service (service check is future)
        return 3; // 3 Stars
    } elseif ($recognitions >= 100) { // 100+ recognitions + extra amenities (amenities check is future)
        return 2; // 2 Stars
    } elseif ($recognitions >= 30) { // 30+ community recognitions
        return 1; // 1 Star
    } else {
        return 0; // 0 Stars (Nominee or not yet rated)
    }
}

?>
