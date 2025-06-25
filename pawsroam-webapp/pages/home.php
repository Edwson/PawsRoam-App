<?php
// This page is intended to be included by index.php.
// Assumes $current_language, $pageTitle (can be overridden), and core functions are available.

// Override page title for Home
$pageTitle = __('page_title_home', [], $GLOBALS['current_language'] ?? 'en'); // "Welcome to PawsRoam"

// Any other page-specific PHP logic can go here.
// For example, fetching some initial data to display alongside or below the map.

?>

<div class="container-fluid p-0"> <?php // Full width container for the map section ?>
    <section id="hero-map-section" class="position-relative">
        <div id="map" style="height: 70vh; min-height: 400px; background-color: #e9e9e9;">
            <!-- Map will be initialized here by assets/js/maps.js -->
            <div class="map-loading-placeholder d-flex flex-column justify-content-center align-items-center h-100">
                <div class="spinner-border text-primary-orange" role="status" style="width: 3rem; height: 3rem;">
                    <span class="visually-hidden"><?php echo e(__('Loading map...', [], $GLOBALS['current_language'] ?? 'en')); ?></span>
                </div>
                <p class="mt-3 text-muted fs-5"><?php echo e(__('Initializing interactive map...', [], $GLOBALS['current_language'] ?? 'en')); ?></p>
            </div>
        </div>

        <?php /* Example: Search bar overlay on the map - more complex, for later
        <div class="map-search-overlay position-absolute top-0 start-50 translate-middle-x p-3" style="z-index: 10; width:clamp(300px, 60%, 600px); margin-top: 20px;">
            <form id="mapSearchForm" class="d-flex shadow">
                <input class="form-control form-control-lg me-2" type="search"
                       placeholder="<?php echo e(__('Search pet-friendly places...', [], $GLOBALS['current_language'] ?? 'en')); ?>"
                       aria-label="Search map">
                <button class="btn btn-primary btn-lg" type="submit">
                    <i class="bi bi-search"></i>
                </button>
            </form>
        </div>
        */ ?>
         <div id="search-map-area-button-container" class="position-absolute text-center pb-3" style="z-index: 10; bottom: 10px; left: 50%; transform: translateX(-50%);">
             {/* This button's visibility will be controlled by maps.js */}
            <button id="search-map-area-button" class="btn btn-light btn-lg shadow d-none">
                <i class="bi bi-arrow-clockwise me-2"></i><?php echo e(__('Search this area', [], $GLOBALS['current_language'] ?? 'en')); ?>
            </button>
        </div>
    </section>
</div>


<div class="container mt-4 mb-5">
    <section id="welcome-intro" class="text-center py-5">
        <h1 class="display-4 fw-bold text-primary-orange mb-3"><?php echo e(__('welcome_to_pawsroam_title', [], $GLOBALS['current_language'] ?? 'en')); // "Welcome to PawsRoam!" ?></h1>
        <p class="lead col-lg-9 mx-auto text-muted">
            <?php echo e(__('discover_connect_explore_text', [], $GLOBALS['current_language'] ?? 'en')); // "Your ultimate guide to discovering pet-friendly places, connecting with a vibrant community, and exploring the world with your furry companions." ?>
        </p>
        <div class="d-grid gap-3 d-sm-flex justify-content-sm-center mt-4 pt-2">
            <a href="<?php echo e(base_url('/search')); ?>" class="btn btn-primary btn-lg px-5 py-3 shadow-sm"><?php echo e(__('find_places_button', [], $GLOBALS['current_language'] ?? 'en')); // "Find Pet-Friendly Places" ?></a>
            <?php if (!is_logged_in()): ?>
            <a href="<?php echo e(base_url('/register')); ?>" class="btn btn-outline-secondary btn-lg px-5 py-3 shadow-sm"><?php echo e(__('join_community_button', [], $GLOBALS['current_language'] ?? 'en')); // "Join Our Community" ?></a>
            <?php else: ?>
            <a href="<?php echo e(base_url('/pawsconnect')); ?>" class="btn btn-outline-secondary btn-lg px-5 py-3 shadow-sm"><?php echo e(__('explore_community_button', [], $GLOBALS['current_language'] ?? 'en')); // "Explore Community" ?></a>
            <?php endif; ?>
        </div>
    </section>

    <hr class="my-5">

    <section id="how-it-works" class="py-5">
        <h2 class="text-center display-6 mb-5"><?php echo e(__('how_pawsroam_works_title', [], $GLOBALS['current_language'] ?? 'en')); // "How PawsRoam Works" ?></h2>
        <div class="row g-4 g-lg-5 justify-content-center">
            <div class="col-lg-4 col-md-6">
                <div class="card text-center h-100 shadow border-0 feature-card">
                    <div class="card-body p-4 p-xl-5">
                        <div class="feature-icon bg-primary-green text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-4 shadow-sm" style="width: 80px; height: 80px;">
                            <i class="bi bi-compass-fill fs-1"></i>
                        </div>
                        <h3 class="h5 card-title fw-bold mb-3"><?php echo e(__('feature_discover_title', [], $GLOBALS['current_language'] ?? 'en')); // "Discover" ?></h3>
                        <p class="card-text text-muted"><?php echo e(__('feature_discover_text', [], $GLOBALS['current_language'] ?? 'en')); // "Easily find pet-friendly parks, cafes, hotels, and services near you or at your travel destination using our interactive map." ?></p>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="card text-center h-100 shadow border-0 feature-card">
                    <div class="card-body p-4 p-xl-5">
                         <div class="feature-icon bg-primary-blue text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-4 shadow-sm" style="width: 80px; height: 80px;">
                            <i class="bi bi-chat-dots-fill fs-1"></i>
                        </div>
                        <h3 class="h5 card-title fw-bold mb-3"><?php echo e(__('feature_connect_title', [], $GLOBALS['current_language'] ?? 'en')); // "Connect" ?></h3>
                        <p class="card-text text-muted"><?php echo e(__('feature_connect_text', [], $GLOBALS['current_language'] ?? 'en')); // "Join PawsConnect, our community forum. Share tips, arrange playdates, and connect with fellow pet lovers." ?></p>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="card text-center h-100 shadow border-0 feature-card">
                     <div class="card-body p-4 p-xl-5">
                        <div class="feature-icon bg-accent-yellow text-dark rounded-circle d-inline-flex align-items-center justify-content-center mb-4 shadow-sm" style="width: 80px; height: 80px;">
                            <i class="bi bi-shield-fill-check fs-1"></i>
                        </div>
                        <h3 class="h5 card-title fw-bold mb-3"><?php echo e(__('feature_pawssafe_title', [], $GLOBALS['current_language'] ?? 'en')); // "PawsSafe" ?></h3>
                        <p class="card-text text-muted"><?php echo e(__('feature_pawssafe_text', [], $GLOBALS['current_language'] ?? 'en')); // "Access our PawsSafe network for verified pet sitters and emergency care options when you need them most." ?></p>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
<style>
    .feature-card { transition: transform 0.3s ease-in-out, box-shadow 0.3s ease-in-out; }
    .feature-card:hover { transform: translateY(-10px); box-shadow: var(--box-shadow-lg) !important; }
    .feature-icon { /* For the icon background circles */ }
</style>
<?php
// Placeholder for translation strings used in this file
// __('page_title_home', [], $GLOBALS['current_language'] ?? 'en');
// __('Loading map...', [], $GLOBALS['current_language'] ?? 'en');
// __('Initializing interactive map...', [], $GLOBALS['current_language'] ?? 'en');
// __('Search pet-friendly places...', [], $GLOBALS['current_language'] ?? 'en'); // For map search overlay (future)
// __('Search this area', [], $GLOBALS['current_language'] ?? 'en');
// __('welcome_to_pawsroam_title', [], $GLOBALS['current_language'] ?? 'en');
// __('discover_connect_explore_text', [], $GLOBALS['current_language'] ?? 'en');
// __('find_places_button', [], $GLOBALS['current_language'] ?? 'en');
// __('join_community_button', [], $GLOBALS['current_language'] ?? 'en');
// __('explore_community_button', [], $GLOBALS['current_language'] ?? 'en'); // New for logged-in users
// __('how_pawsroam_works_title', [], $GLOBALS['current_language'] ?? 'en');
// __('feature_discover_title', [], $GLOBALS['current_language'] ?? 'en');
// __('feature_discover_text', [], $GLOBALS['current_language'] ?? 'en');
// __('feature_connect_title', [], $GLOBALS['current_language'] ?? 'en');
// __('feature_connect_text', [], $GLOBALS['current_language'] ?? 'en');
// __('feature_pawssafe_title', [], $GLOBALS['current_language'] ?? 'en');
// __('feature_pawssafe_text', [], $GLOBALS['current_language'] ?? 'en');
?>
