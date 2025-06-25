<?php
/**
 * PawsRoam Web Application Entry Point
 *
 * This file handles basic application setup, routing, and page loading.
 * Version: 1.0.0
 */

// --- Basic Configuration & Setup ---

// Define a base path for the application if not already defined (e.g., by a bootstrap file)
if (!defined('BASE_PATH')) {
    define('BASE_PATH', __DIR__);
}
if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}

// Start session if not already started (important for auth, flash messages, etc.)
if (session_status() == PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true, // Mitigate XSS
        'cookie_secure' => isset($_SERVER['HTTPS']), // Send cookie only over HTTPS
        'cookie_samesite' => 'Lax' // Mitigate CSRF
    ]);
}

// Error Reporting (adjust for production vs. development)
// For development:
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// For production (in a real setup, this would be configured via php.ini or server config):
// ini_set('display_errors', 0);
// ini_set('log_errors', 1);
// error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
// ini_set('error_log', BASE_PATH . DS . 'logs' . DS . 'php_error.log'); // Ensure 'logs' dir exists and is writable

// Autoloading & Core Includes
// In a more complex app, Composer's autoloader would be primary.
// For now, manual includes for key files.
require_once BASE_PATH . DS . 'config' . DS . 'constants.php'; // System constants, API keys path
require_once BASE_PATH . DS . 'config' . DS . 'database.php'; // Database class
require_once BASE_PATH . DS . 'includes' . DS . 'functions.php'; // Utility functions
require_once BASE_PATH . DS . 'includes' . DS . 'translation.php'; // i18n functions
require_once BASE_PATH . DS . 'includes' . DS . 'auth.php'; // Authentication system (basic stubs for now)

// Initialize Database Connection (example, might be lazy-loaded elsewhere)
// $dbInstance = Database::getInstance();
// $pdo = $dbInstance->getConnection();

// Set default timezone (important for date/time functions)
date_default_timezone_set(DEFAULT_TIMEZONE); // DEFAULT_TIMEZONE should be in constants.php

// --- Routing ---
// Simple router based on a query parameter (e.g., ?page=home or ?_url=/home from .htaccess)

$requestedPath = '/'; // Default path

// Check for path from .htaccess rewrite (e.g., _url parameter)
if (isset($_GET['_url'])) {
    $requestedPath = filter_var(rtrim($_GET['_url'], '/'), FILTER_SANITIZE_URL);
    if (empty($requestedPath)) {
        $requestedPath = '/';
    }
} elseif (isset($_GET['page'])) { // Fallback to simple ?page= parameter if no _url
    $requestedPath = '/' . filter_var(trim($_GET['page']), FILTER_SANITIZE_STRING);
}


// Define available routes and their corresponding page files
// This is a very basic router. A more robust router would handle HTTP methods, parameters, etc.
$routes = [
    '/' => 'pages/home.php',
    '/home' => 'pages/home.php',
    '/search' => 'pages/search.php',
    '/business' => 'pages/business-detail.php', // Example: /business?id=123 or /business/slug-name
    '/pawssafe' => 'pages/pawssafe.php',
    '/pawsconnect' => 'pages/pawsconnect.php',
    '/profile' => 'pages/profile.php', // User profile
    '/pet-profile' => 'pages/pet-profile.php',
    '/booking' => 'pages/booking.php',

    // Auth pages
    '/login' => 'pages/auth/login.php',
    '/register' => 'pages/auth/register.php',
    '/logout' => 'api/v1/auth/logout.php', // Logout might be an API call or a simple script
    '/forgot-password' => 'pages/auth/forgot-password.php',

    // Admin pages (these should have auth checks within the files themselves)
    '/admin' => 'pages/admin/dashboard.php',
    '/admin/dashboard' => 'pages/admin/dashboard.php',
    '/admin/users' => 'pages/admin/users.php',
    '/admin/businesses' => 'pages/admin/businesses.php',
    '/admin/pawssafe-providers' => 'pages/admin/pawssafe.php',
    '/admin/analytics' => 'pages/admin/analytics.php',

    // API endpoint examples (usually handled by a dedicated API router or .htaccess)
    // For simplicity, we can list some direct script paths if not using a full API router.
    // '/api/v1/business/search' => 'api/v1/business/search.php',
    // ... other API routes
];

// Determine the page to load
$pageToLoad = BASE_PATH . DS . 'pages' . DS . '404.php'; // Default to 404 page
$pageFound = false;

if (array_key_exists($requestedPath, $routes)) {
    $filePath = BASE_PATH . DS . $routes[$requestedPath];
    if (file_exists($filePath)) {
        $pageToLoad = $filePath;
        $pageFound = true;
    } else {
        error_log("Routing error: File {$filePath} not found for route '{$requestedPath}'.");
    }
} else {
    // More advanced routing: check for dynamic parts like /business/{id} or /business/{slug}
    // This part would require a more sophisticated router.
    // For now, direct matches only.
    // Example for a simple dynamic route:
    // if (preg_match('#^/business/([a-zA-Z0-9_-]+)$#', $requestedPath, $matches)) {
    //     $_GET['slug'] = $matches[1]; // or $_GET['id']
    //     $filePath = BASE_PATH . DS . 'pages' . DS . 'business-detail.php';
    //     if (file_exists($filePath)) {
    //         $pageToLoad = $filePath;
    //         $pageFound = true;
    //     }
    // }
    // Add similar checks for other dynamic routes

    // More specific routing for /business/{identifier}
    // This will try to match /business/slug-name-here or /business/123
    if (preg_match('#^/business/([a-zA-Z0-9_-]+)$#', $requestedPath, $matches)) {
        $identifier = $matches[1];
        if (is_numeric($identifier)) {
            $_GET['id'] = (int)$identifier; // Pass as 'id' if numeric
        } else {
            $_GET['slug'] = $identifier; // Pass as 'slug' if string
        }
        $filePath = BASE_PATH . DS . 'pages' . DS . 'business-detail.php';
        if (file_exists($filePath)) {
            $pageToLoad = $filePath;
            $pageFound = true;
        } else {
            error_log("Routing error: business-detail.php not found for identifier '{$identifier}'.");
        }
    }
    // Route for adding a new pet
    elseif ($requestedPath === '/pets/add') {
        $filePath = BASE_PATH . DS . 'pages' . DS . 'pets' . DS . 'add-pet.php';
        if (file_exists($filePath)) {
            $pageToLoad = $filePath;
            $pageFound = true;
        } else {
            error_log("Routing error: add-pet.php not found for route '/pets/add'.");
        }
    }
    // Route for editing or viewing a pet: /pets/edit/{id} or /pets/view/{id}
    elseif (preg_match('#^/pets/(edit|view)/([0-9]+)$#', $requestedPath, $matches)) {
        $action = $matches[1]; // 'edit' or 'view'
        $pet_id = (int)$matches[2];
        $_GET['id'] = $pet_id; // Pass pet_id as 'id' to the page script

        if ($action === 'edit') {
            $filePath = BASE_PATH . DS . 'pages' . DS . 'pets' . DS . 'edit-pet.php';
        } elseif ($action === 'view') {
            $filePath = BASE_PATH . DS . 'pages' . DS . 'pets' . DS . 'view-pet.php';
        }
        // else: action unknown, will fall through to 404 if $filePath not set

        if (isset($filePath) && file_exists($filePath)) {
            $pageToLoad = $filePath;
            $pageFound = true;
        } else {
            error_log("Routing error: Pet page for action '{$action}' with ID '{$pet_id}' not found. File: " . ($filePath ?? 'N/A'));
        }
    }
    // Example for /pets/add, /pets/edit/{id}, /pets/view/{id} (for future)
    // elseif (preg_match('#^/pets/add$#', $requestedPath)) {
    //     // $filePath = BASE_PATH . DS . 'pages' . DS . 'pets' . DS . 'add-edit-pet.php'; // Assuming a combined add/edit form
    //     // $_GET['action'] = 'add';
    //     // if (file_exists($filePath)) { $pageToLoad = $filePath; $pageFound = true; }
    // }
    // elseif (preg_match('#^/pets/(edit|view)/([0-9]+)$#', $requestedPath, $matches)) {
        // $action = $matches[1]; // 'edit' or 'view'
        // $pet_id = (int)$matches[2];
        // $_GET['action'] = $action;
        // $_GET['pet_id'] = $pet_id;
        // if ($action === 'edit') {
        //     $filePath = BASE_PATH . DS . 'pages' . DS . 'pets' . DS . 'add-edit-pet.php';
        // } else { // view
        //     $filePath = BASE_PATH . DS . 'pages' . DS . 'pets' . DS . 'view-pet.php';
        // }
        // if (file_exists($filePath)) { $pageToLoad = $filePath; $pageFound = true; }
    }


}


// --- Language Handling ---
// Determine current language (example: from user preference, session, or browser)
// $current_language = determine_current_language(); // This function would be in functions.php or auth.php
// For now, hardcode or get from a simple mechanism:
if (isset($_SESSION['current_language'])) {
    $current_language = $_SESSION['current_language'];
} elseif (isset($_COOKIE['pawsroam_lang'])) {
    $current_language = filter_var($_COOKIE['pawsroam_lang'], FILTER_SANITIZE_STRING);
} else {
    // Basic browser language detection (Accept-Language header)
    // $http_accept_language = isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2) : DEFAULT_LANGUAGE;
    // $current_language = in_array($http_accept_language, SUPPORTED_LANGUAGES) ? $http_accept_language : DEFAULT_LANGUAGE;
    $current_language = DEFAULT_LANGUAGE; // DEFAULT_LANGUAGE should be in constants.php
}
// Ensure $current_language is globally available for the __() function
$GLOBALS['current_language'] = $current_language;


// --- Page Rendering ---

// Include common header (if not a specific API endpoint that outputs JSON, etc.)
// Check if the request is for an API endpoint - these might not need HTML header/footer
$isApiRequest = strpos($requestedPath, '/api/') === 0;

if (!$isApiRequest && file_exists(BASE_PATH . DS . 'includes' . DS . 'header.php')) {
    // Pass variables to header if needed, e.g., page title
    // $pageTitle = "Welcome to PawsRoam"; // Default title, can be overridden by specific pages
    include BASE_PATH . DS . 'includes' . DS . 'header.php';
}

// Load the determined page content
if ($pageFound) {
    include $pageToLoad;
} else {
    // If no specific route matched and it's not an API request, show 404.
    // API requests not found by router should typically return a JSON 404 from an API handler.
    if (!$isApiRequest) {
        http_response_code(404); // Set 404 HTTP status code
        if (file_exists(BASE_PATH . DS . 'pages' . DS . '404.php')) {
            include BASE_PATH . DS . 'pages' . DS . '404.php';
        } else {
            echo "<h1>404 - Page Not Found</h1><p>Sorry, the page you are looking for does not exist.</p>";
        }
    } else {
        // For API requests not found by this simple router,
        // a more robust API gateway/router would handle this.
        // For now, if an API path was in $routes but file not found, it was logged.
        // If it wasn't in $routes and isApiRequest is true, it means it's an unhandled API path.
        http_response_code(404);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'API endpoint not found', 'path' => $requestedPath]);
    }
}


// Include common footer (if not an API endpoint)
if (!$isApiRequest && file_exists(BASE_PATH . DS . 'includes' . DS . 'footer.php')) {
    include BASE_PATH . DS . 'includes' . DS . 'footer.php';
}

exit; // End script execution
?>
