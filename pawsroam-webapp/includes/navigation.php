<?php
// This file is intended to be included by header.php
// It assumes core files like functions.php (for base_url, e) and auth.php (for is_logged_in, etc.)
// and translation.php (for __) have been loaded, typically by index.php.

// Safeguards if critical functions are missing (though index.php should ensure they are loaded)
if (!function_exists('is_logged_in')) {
    if (file_exists(__DIR__ . '/auth.php')) require_once __DIR__ . '/auth.php';
    else { echo "<!-- Auth system not loaded -->"; return; }
}
if (!function_exists('base_url')) {
    if (file_exists(__DIR__ . '/functions.php')) require_once __DIR__ . '/functions.php';
    else { echo "<!-- Core functions not loaded -->"; return; }
}
if (!function_exists('__')) {
    if (file_exists(__DIR__ . '/translation.php')) require_once __DIR__ . '/translation.php';
    else { echo "<!-- Translation system not loaded -->"; return; }
}

$current_nav_lang = $GLOBALS['current_language'] ?? 'en';
?>
<nav class="navbar navbar-expand-lg navbar-light bg-surface-light shadow-sm main-navigation">
    <div class="container">
        <a class="navbar-brand" href="<?php echo e(base_url('/')); ?>">
            <img src="<?php echo e(base_url('/assets/images/logos/pawsroam_logo_sm.png')); ?>" alt="<?php echo e(__('app_name', [], $current_nav_lang)); ?> Logo" height="40">
            <?php // echo e(__('app_name', [], $current_nav_lang)); // Optionally display app name text ?>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar"
                aria-controls="mainNavbar" aria-expanded="false" aria-label="<?php echo e(__('Toggle navigation', [], $current_nav_lang)); ?>">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="mainNavbar">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link active" aria-current="page" href="<?php echo e(base_url('/')); ?>"><?php echo e(__('nav_home', [], $current_nav_lang)); ?></a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo e(base_url('/search')); ?>"><?php echo e(__('nav_search', [], $current_nav_lang)); ?></a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo e(base_url('/pawssafe')); ?>"><?php echo e(__('nav_pawssafe', [], $current_nav_lang)); ?></a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo e(base_url('/pawsconnect')); ?>"><?php echo e(__('nav_community', [], $current_nav_lang)); ?></a>
                </li>
                <?php /* More main navigation items can be added here as features are built
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo e(base_url('/deals')); ?>"><?php echo e(__('nav_deals', [], $current_nav_lang)); ?></a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo e(base_url('/memorials')); ?>"><?php echo e(__('nav_memorials', [], $current_nav_lang)); ?></a>
                </li>
                */ ?>
            </ul>

            <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                <?php if (is_logged_in()): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <?php
                            $welcome_message = __('welcome_user', [], $current_nav_lang);
                            echo e(sprintf($welcome_message, current_username() ?? 'User'));
                            ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li><a class="dropdown-item" href="<?php echo e(base_url('/profile')); ?>"><?php echo e(__('nav_profile', [], $current_nav_lang)); ?></a></li>
                            <li><a class="dropdown-item" href="<?php echo e(base_url('/pet-profile')); ?>"><?php echo e(__('nav_my_pets', [], $current_nav_lang)); // "My Pets" ?></a></li>
                            <?php if (has_role(['super_admin', 'business_admin'])): ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="<?php echo e(base_url('/admin')); ?>"><?php echo e(__('nav_admin', [], $current_nav_lang)); ?></a></li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <!-- Logout link should ideally be a POST request or use JS to submit a form -->
                                <form id="logoutFormNav" action="<?php echo e(base_url('/api/v1/auth/logout.php')); ?>" method="POST" style="display: none;">
                                    <?php echo csrf_input_field(); ?>
                                </form>
                                <a class="dropdown-item" href="#" onclick="event.preventDefault(); document.getElementById('logoutFormNav').submit();">
                                    <?php echo e(__('nav_logout', [], $current_nav_lang)); ?>
                                </a>
                            </li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo e(base_url('/login')); ?>"><?php echo e(__('nav_login', [], $current_nav_lang)); ?></a>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-primary btn-sm ms-lg-2" href="<?php echo e(base_url('/register')); ?>" role="button"><?php echo e(__('nav_register', [], $current_nav_lang)); ?></a>
                    </li>
                <?php endif; ?>

                <?php /* Language switcher example (very basic) - requires more logic for actual switching
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="languageDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <?php echo strtoupper(e($current_nav_lang)); ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="languageDropdown">
                        <li><a class.dropdown-item href="?lang=en">English (EN)</a></li>
                        <li><a class.dropdown-item href="?lang=jp">日本語 (JP)</a></li>
                        <li><a class.dropdown-item href="?lang=tw">繁體中文 (TW)</a></li>
                    </ul>
                </li>
                */?>
            </ul>
        </div>
    </div>
</nav>
<?php
// Placeholder for translation strings used in this file
// __('app_name', [], $current_nav_lang);
// __('Toggle navigation', [], $current_nav_lang);
// __('nav_home', [], $current_nav_lang);
// __('nav_search', [], $current_nav_lang);
// __('nav_pawssafe', [], $current_nav_lang);
// __('nav_community', [], $current_nav_lang);
// __('nav_deals', [], $current_nav_lang);
// __('nav_memorials', [], $current_nav_lang);
// __('welcome_user', [], $current_nav_lang); // "Welcome, %s!"
// __('nav_profile', [], $current_nav_lang);
// __('nav_my_pets', [], $current_nav_lang);
// __('nav_admin', [], $current_nav_lang);
// __('nav_logout', [], $current_nav_lang);
// __('nav_login', [], $current_nav_lang);
// __('nav_register', [], $current_nav_lang);
?>
