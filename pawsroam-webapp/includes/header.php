<?php
// This file is intended to be included by index.php at the beginning of page rendering.
// It assumes $current_language is set by index.php for the __() function.
// It also assumes constants.php (for GOOGLE_MAPS_API_KEY, APP_NAME) and
// functions.php (for base_url, e) are loaded.

// Safeguards for direct access or if index.php didn't set these (not ideal)
if (session_status() == PHP_SESSION_NONE) { session_start(); }
if (!function_exists('__')) {
    if(file_exists(__DIR__ . '/translation.php')) require_once __DIR__ . '/translation.php';
    else die("Translation system not found in header.");
}
if (!function_exists('base_url') || !function_exists('e')) {
    if(file_exists(__DIR__ . '/functions.php')) require_once __DIR__ . '/functions.php';
    else die("Core functions not found in header.");
}
if (!defined('APP_NAME')) {
     if(file_exists(dirname(__DIR__) . '/config/constants.php')) require_once dirname(__DIR__) . '/config/constants.php';
     else define('APP_NAME', 'PawsRoam'); // Fallback
}
if (!defined('GOOGLE_MAPS_API_KEY')) {
    if(file_exists(dirname(__DIR__) . '/config/api_keys.php')) require_once dirname(__DIR__) . '/config/api_keys.php';
    // GOOGLE_MAPS_API_KEY might still be empty if not set in .env, api_keys.php handles defining it as empty.
}


// $pageTitle should be set by the specific page script or by index.php before including this header.
// Default title if not set.
$page_title = $pageTitle ?? (defined('APP_NAME') ? APP_NAME : 'PawsRoam');
$html_lang = $GLOBALS['current_language'] ?? 'en';

// Determine active page for navigation highlighting (simple example)
$current_page_script = basename($_SERVER['PHP_SELF']); // e.g., index.php, search.php
$request_uri_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH); // e.g., /search, /admin/users

?>
<!DOCTYPE html>
<html lang="<?php echo e($html_lang); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?php echo e($page_title); ?></title>

    <meta name="description" content="<?php echo e(__('app_meta_description', [], $html_lang)); // "Discover pet-friendly places with PawsRoam!" ?>">
    <meta name="keywords" content="<?php echo e(__('app_meta_keywords', [], $html_lang)); // "pets, pet-friendly, travel, venues, map, community" ?>">

    <!-- PWA Manifest -->
    <link rel="manifest" href="<?php echo e(base_url('/manifest.json')); ?>">
    <meta name="theme-color" content="<?php echo e(defined('PWA_THEME_COLOR') ? PWA_THEME_COLOR : '#FF6B35'); ?>">

    <!-- Favicons (simplified, generate more sizes with a tool like realfavicongenerator.net) -->
    <link rel="icon" href="<?php echo e(base_url('/assets/images/favicons/favicon.ico')); ?>" sizes="any">
    <link rel="icon" href="<?php echo e(base_url('/assets/images/favicons/favicon.svg')); ?>" type="image/svg+xml">
    <link rel="apple-touch-icon" href="<?php echo e(base_url('/assets/images/favicons/apple-touch-icon.png')); ?>">


    <!-- Bootstrap CSS (assuming using Bootstrap 5 via CDN for now, or local if downloaded) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <!-- Bootstrap Icons (optional) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

    <!-- Popper.js (required by Bootstrap 5 Popovers, Dropdowns, Tooltips if using the bundle that doesn't include it) -->
    <!-- Bootstrap 5.3.x bundle includes Popper, so separate include might not be needed if using that specific bundle -->

    <!-- Main Application Stylesheet -->
    <link rel="stylesheet" href="<?php echo e(base_url('/assets/css/main.css?v=1.0.1')); ?>"> <!-- Cache busting version query -->
    <link rel="stylesheet" href="<?php echo e(base_url('/assets/css/responsive.css?v=1.0.1')); ?>">
    <?php /* <link rel="stylesheet" href="<?php echo e(base_url('/assets/css/components.css?v=1.0.1')); ?>"> */ ?>
    <?php /* <link rel="stylesheet" href="<?php echo e(base_url('/assets/css/admin.css?v=1.0.1')); ?>"> */ ?>


    <!-- Google Fonts (Inter as primary, from main.css variables) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Google Maps API Script -->
    <?php
        // Only include Google Maps script if API key is available AND on pages that need it.
        // This conditional loading can be more sophisticated (e.g., based on $page_needs_map variable set by page script)
        // For now, let's assume it's generally needed or app.js handles conditional loading.
        $google_maps_key = defined('GOOGLE_MAPS_API_KEY') ? GOOGLE_MAPS_API_KEY : '';
        if (!empty($google_maps_key)):
    ?>
    <script async defer src="https://maps.googleapis.com/maps/api/js?key=<?php echo e($google_maps_key); ?>&libraries=places,marker&callback=initPawsRoamAppMapGlobal"></script>
    <script>
        // This global callback can be used by maps.js or app.js to know when API is ready
        function initPawsRoamAppMapGlobal() {
            console.log("Google Maps API loaded and ready.");
            // You can dispatch a custom event here if preferred over a global function
            document.dispatchEvent(new CustomEvent('googleMapsApiReady'));
        }
    </script>
    <?php else: ?>
    <script>
        // Fallback or notification if Maps API key is missing
        function initPawsRoamAppMapGlobal() {
            console.warn("Google Maps API key is missing. Map functionality will be disabled.");
            document.dispatchEvent(new CustomEvent('googleMapsApiFailed'));
        }
         // Call it immediately if key is missing so dependent scripts know
        document.addEventListener('DOMContentLoaded', initPawsRoamAppMapGlobal);
    </script>
    <?php endif; ?>

    <!-- Placeholder for other head elements (e.g., custom page-specific CSS/JS) -->
    <?php if (isset($extra_head_content)) { echo $extra_head_content; } ?>

</head>
<body class="d-flex flex-column min-vh-100"> <?php // Bootstrap class for sticky footer with flexbox ?>

    <!-- Bootstrap JS Bundle (includes Popper for tooltips, popovers, dropdowns) -->
    <!-- Placed here or at the end of the body. End of body is often preferred for faster perceived page load. -->
    <!-- For simplicity in this phase, placing it in head after CSS. -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>

    <a href="#main-content" class="skip-link"><?php echo e(__('skip_to_main_content', [], $html_lang)); // "Skip to main content" ?></a>

    <header id="main-header" class="sticky-top">
        <?php include __DIR__ . '/navigation.php'; // Include the main navigation bar ?>
    </header>

    <main id="main-content" class="flex-shrink-0 py-4">
        <?php /* Flash messages display area - requires a flash message system in PHP (e.g., storing in session)
        if (function_exists('display_flash_messages')) {
            display_flash_messages();
        }
        */ ?>
<!-- End of header.php, content continues in the specific page file, then footer.php -->
<?php
// Placeholder for translation strings used in this file
// __('app_meta_description', [], $html_lang);
// __('app_meta_keywords', [], $html_lang);
// __('skip_to_main_content', [], $html_lang);
?>
