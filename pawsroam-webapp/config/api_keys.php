<?php
/**
 * API Keys Management for PawsRoam
 *
 * This file defines constants for various API keys used throughout the application.
 * It attempts to load these keys from environment variables (expected to be populated
 * from a .env file by a loader, e.g., in database.php or a dedicated bootstrap process
 * that loads variables into $_ENV or using getenv()).
 *
 * IMPORTANT:
 * 1. Ensure you have a .env file in the project root (pawsroam-webapp/.env).
 *    You can copy pawsroam-webapp/config/.env.example to pawsroam-webapp/.env to get started.
 * 2. Fill in your actual API keys in the pawsroam-webapp/.env file.
 * 3. Ensure your .env file is listed in .gitignore to prevent committing sensitive keys.
 * 4. The .env loading mechanism is currently part of the Database class constructor
 *    (pawsroam-webapp/config/database.php). This means for these constants to be
 *    correctly populated from .env, the Database class must have been instantiated
 *    at least once, or a similar .env loading logic must run before this file is included
 *    if used independently of the Database class instantiation.
 *    Alternatively, ensure your web server environment directly provides these $_ENV variables.
 */

// --- Google Maps API Key ---
// Used for displaying maps, geocoding, etc.
// Get your key from: https://console.cloud.google.com/google/maps-apis/overview
if (!defined('GOOGLE_MAPS_API_KEY')) {
    // Prefer getenv() as it's more standard for environment variables,
    // but fall back to $_ENV if getenv() returns false or empty.
    $googleMapsKey = getenv('GOOGLE_MAPS_API_KEY');
    if (empty($googleMapsKey) && isset($_ENV['GOOGLE_MAPS_API_KEY'])) {
        $googleMapsKey = $_ENV['GOOGLE_MAPS_API_KEY'];
    }
    define('GOOGLE_MAPS_API_KEY', $googleMapsKey ?: ''); // Fallback to empty string if not found

    // Conditional logging based on environment type (APP_ENV should be set in .env)
    $appEnv = getenv('APP_ENV') ?: ($_ENV['APP_ENV'] ?? 'development');
    if (empty(GOOGLE_MAPS_API_KEY) && $appEnv !== 'development') {
        error_log('CRITICAL: GOOGLE_MAPS_API_KEY is not set in a non-development environment. Mapping features will fail.');
    } elseif (empty(GOOGLE_MAPS_API_KEY) && $appEnv === 'development') {
        error_log('Warning (Development): GOOGLE_MAPS_API_KEY is not set. Mapping features may not work as expected.');
    }
}

// --- Google Gemini AI API Key ---
// Used for the PawsAI Assistant.
// Get your key from: https://aistudio.google.com/app/apikey
if (!defined('GEMINI_AI_API_KEY')) {
    $geminiAiKey = getenv('GEMINI_AI_API_KEY');
    if (empty($geminiAiKey) && isset($_ENV['GEMINI_AI_API_KEY'])) {
        $geminiAiKey = $_ENV['GEMINI_AI_API_KEY'];
    }
    define('GEMINI_AI_API_KEY', $geminiAiKey ?: ''); // Fallback to empty string

    $appEnv = getenv('APP_ENV') ?: ($_ENV['APP_ENV'] ?? 'development');
    if (empty(GEMINI_AI_API_KEY) && $appEnv !== 'development') {
        error_log('CRITICAL: GEMINI_AI_API_KEY is not set in a non-development environment. PawsAI features will fail.');
    } elseif (empty(GEMINI_AI_API_KEY) && $appEnv === 'development') {
        error_log('Warning (Development): GEMINI_AI_API_KEY is not set. PawsAI features may not work as expected.');
    }
}

/**
 * Helper function to retrieve an API key constant.
 * This is not strictly necessary as constants are global, but can be useful for abstraction
 * or if you want to add more logic around key retrieval later.
 *
 * Example: get_api_key('GOOGLE_MAPS_API_KEY');
 */
if (!function_exists('get_api_key')) {
    function get_api_key($key_name, $default = null) {
        if (defined($key_name)) {
            return constant($key_name);
        }
        // Optionally log if a key is requested but not defined
        // error_log("API Key '{$key_name}' requested but not defined.");
        return $default;
    }
}

// This file should not produce any output.
?>
