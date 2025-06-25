<?php
/**
 * PawsRoam Application Constants
 *
 * Define application-wide constants here.
 * Environment-specific configurations should ideally be in a .env file.
 */

// --- Filesystem Paths ---
// Ensure BASE_PATH is defined (it should be by index.php, but good for direct script access)
if (!defined('BASE_PATH')) {
    // If this constants.php is in /config, then __DIR__ is /config.
    // BASE_PATH should be the project root (pawsroam-webapp).
    define('BASE_PATH', dirname(__DIR__));
}
if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}

// --- Core Application Settings ---
define('APP_NAME', 'PawsRoam');

// APP_ENV: Attempt to get from environment variable, default to 'development'
define('APP_ENV', getenv('APP_ENV') ?: ($_ENV['APP_ENV'] ?? 'development'));

// APP_DEBUG: Attempt to get from environment variable, default to true for development, false otherwise
$appDebugDefault = (APP_ENV === 'development');
define('APP_DEBUG', filter_var(getenv('APP_DEBUG') ?: ($_ENV['APP_DEBUG'] ?? $appDebugDefault), FILTER_VALIDATE_BOOLEAN));

// APP_URL: Dynamically determine or use from .env
if (php_sapi_name() !== 'cli' && isset($_SERVER['HTTP_HOST'])) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $current_app_url = $protocol . $_SERVER['HTTP_HOST'];
    // If your app is in a subdirectory of the web root, you might need to adjust this.
    // For example, if index.php is at /pawsroam/index.php, and you want APP_URL to be http://domain.com/pawsroam
    // $subdir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
    // define('APP_URL', rtrim($current_app_url . $subdir, '/'));
    define('APP_URL', rtrim($current_app_url, '/')); // Assuming app runs at domain root for simplicity now
} else {
    // Fallback for CLI or if server variables are not set as expected
    define('APP_URL', rtrim(getenv('APP_URL') ?: ($_ENV['APP_URL'] ?? 'http://localhost:8000'), '/'));
}


// --- Locale & Translation ---
define('DEFAULT_LANGUAGE', getenv('DEFAULT_LANGUAGE') ?: ($_ENV['DEFAULT_LANGUAGE'] ?? 'en'));
define('SUPPORTED_LANGUAGES_MAP', [
    'en' => 'English',
    'jp' => '日本語 (Japanese)',
    'tw' => '繁體中文 (Traditional Chinese)',
    'ko' => '한국어 (Korean)',
    'th' => 'ไทย (Thai)',
    'de' => 'Deutsch (German)',
    'ar' => 'العربية (Arabic)'
]);
define('DEFAULT_TIMEZONE', getenv('DEFAULT_TIMEZONE') ?: ($_ENV['DEFAULT_TIMEZONE'] ?? 'UTC'));


// --- Security ---
define('CSRF_TOKEN_NAME', getenv('CSRF_TOKEN_NAME') ?: ($_ENV['CSRF_TOKEN_NAME'] ?? 'csrf_pawsroam_token'));
define('REMEMBER_ME_COOKIE_NAME', 'pawsroam_remember_token'); // Corrected name from previous plan
define('REMEMBER_ME_COOKIE_LIFETIME_DAYS', 30);
define('PASSWORD_ALGO', PASSWORD_ARGON2ID); // PHP's default is often good, but Argon2id is strong.
// define('PASSWORD_OPTIONS', ['memory_cost' => PASSWORD_ARGON2_DEFAULT_MEMORY_COST, 'time_cost' => PASSWORD_ARGON2_DEFAULT_TIME_COST, 'threads' => PASSWORD_ARGON2_DEFAULT_THREADS]);


// --- API Keys ---
// Actual keys are loaded via api_keys.php from .env.
// This file (constants.php) should be included before api_keys.php if api_keys.php relies on constants defined here.

// --- Uploads Configuration ---
// Absolute server path to the main 'uploads' directory.
// IMPORTANT: This directory MUST be writable by the web server process.
define('UPLOADS_BASE_PATH', BASE_PATH . DS . 'uploads');

// Web-accessible base URL for the 'uploads' directory.
// This configuration assumes that the 'pawsroam-webapp/uploads/' directory is directly
// accessible via the web (e.g., http://yourdomain.com/uploads/).
// If 'uploads' is (or should be) outside the web root for security, then files
// must be served via a PHP script that reads them and outputs their content.
// For simplicity in this phase, we assume direct web accessibility of the uploads directory.
define('UPLOADS_BASE_URL', APP_URL . '/uploads');

define('DEFAULT_MAX_UPLOAD_SIZE_MB', 2); // Default max file size for uploads in Megabytes (e.g., 2MB)
// Store allowed MIME types as serialized arrays for easy definition and unserialization in code.
define('PET_AVATAR_ALLOWED_MIME_TYPES', serialize(['image/jpeg', 'image/png', 'image/gif']));
define('BUSINESS_PHOTO_ALLOWED_MIME_TYPES', serialize(['image/jpeg', 'image/png'])); // Example

// --- Search Configuration ---
define('MAX_SEARCH_RADIUS_KM', (int)(getenv('MAX_SEARCH_RADIUS_KM') ?: ($_ENV['MAX_SEARCH_RADIUS_KM'] ?? 100)));
define('MAX_SEARCH_LIMIT', (int)(getenv('MAX_SEARCH_LIMIT') ?: ($_ENV['MAX_SEARCH_LIMIT'] ?? 50)));


// --- Other Application Specific Constants ---
// Example: define('ITEMS_PER_PAGE', 15);
define('SESSION_LIFETIME_MINUTES', (int)(getenv('SESSION_LIFETIME') ?: ($_ENV['SESSION_LIFETIME'] ?? 1440))); // From .env.example

// Ensure this file is included early in your application's bootstrap process (e.g., in index.php).
?>
