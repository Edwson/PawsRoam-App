<?php
// This page is intended to be included by index.php.
// Assumes $current_language, $pageTitle, and core functions/auth are available.

// Access Control: Only super_admin and business_admin can access.
// require_login() is implicitly called by require_role().
require_role(['super_admin', 'business_admin']);

$user_id = current_user_id();
$user_role = current_user_role();
$username = current_username(); // Get username for welcome message
$pageTitle = __('page_title_admin_dashboard', [], $GLOBALS['current_language'] ?? 'en');

// Placeholder data for dashboard stats (will be dynamic later)
$stats = [
    'total_users' => 0, // Super admin only
    'pending_businesses' => 0, // Super admin: all pending; Business admin: their pending if applicable
    'active_pawssafe_providers' => 0, // Super admin only
    'total_active_venues' => 0, // Super admin: all active; Business admin: their active
];

// Simulate fetching some stats (replace with actual DB queries later)
// This is just for the stub's appearance.
/*
if ($user_id) {
    try {
        $db = Database::getInstance()->getConnection();
        if (has_role('super_admin')) {
            // $stmt_users = $db->query("SELECT COUNT(*) FROM users");
            // $stats['total_users'] = (int)$stmt_users->fetchColumn();

            // $stmt_pending_biz = $db->query("SELECT COUNT(*) FROM businesses WHERE status = 'pending'");
            // $stats['pending_businesses'] = (int)$stmt_pending_biz->fetchColumn();

            // $stmt_providers = $db->query("SELECT COUNT(*) FROM pawssafe_providers WHERE status = 'active'");
            // $stats['active_pawssafe_providers'] = (int)$stmt_providers->fetchColumn();

            // $stmt_total_active_venues = $db->query("SELECT COUNT(*) FROM businesses WHERE status = 'active'");
            // $stats['total_active_venues'] = (int)$stmt_total_active_venues->fetchColumn();
            $stats = ['total_users' => 123, 'pending_businesses' => 5, 'active_pawssafe_providers' => 15, 'total_active_venues' => 78]; // Dummy data
        } elseif (has_role('business_admin')) {
            // For business_admin, stats might be specific to their owned businesses
            // $stmt_owned_pending_biz = $db->prepare("SELECT COUNT(*) FROM businesses WHERE owner_user_id = :owner_id AND status = 'pending'");
            // $stmt_owned_pending_biz->bindParam(':owner_id', $user_id, PDO::PARAM_INT);
            // $stmt_owned_pending_biz->execute();
            // $stats['pending_businesses'] = (int)$stmt_owned_pending_biz->fetchColumn();

            // $stmt_owned_active_venues = $db->prepare("SELECT COUNT(*) FROM businesses WHERE owner_user_id = :owner_id AND status = 'active'");
            // $stmt_owned_active_venues->bindParam(':owner_id', $user_id, PDO::PARAM_INT);
            // $stmt_owned_active_venues->execute();
            // $stats['total_active_venues'] = (int)$stmt_owned_active_venues->fetchColumn();
             $stats = ['pending_businesses' => 2, 'total_active_venues' => 10]; // Dummy data for business admin
        }
    } catch (PDOException $e) {
        error_log("Admin Dashboard: Database error fetching stats - " . $e->getMessage());
    }
}
*/
// Using hardcoded stub data for now
if (has_role('super_admin')) {
    $stats = ['total_users' => 123, 'pending_businesses' => 5, 'active_pawssafe_providers' => 15, 'total_active_venues' => 78];
} elseif (has_role('business_admin')) {
    $stats = ['pending_businesses' => 2, 'total_active_venues' => 10];
}


?>

<div class="container my-4 my-md-5">
    <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
        <h1 class="display-6 fw-bold"><?php echo e($pageTitle); ?></h1>
        <span class="badge bg-primary-orange fs-6 rounded-pill px-3 py-2 shadow-sm"><?php echo e(sprintf(__('role_display_text %s', [], $GLOBALS['current_language'] ?? 'en'), e(ucfirst(str_replace('_', ' ', $user_role))))); ?></span>
    </div>

    <p class="lead text-muted mb-5"><?php echo e(sprintf(__('admin_dashboard_welcome_user %s', [], $GLOBALS['current_language'] ?? 'en'), e($username))); ?></p>

    <!-- Quick Stats Section -->
    <section id="quick-stats" class="mb-5">
        <h2 class="h4 mb-3 fw-semibold text-text-dark"><?php echo e(__('admin_dashboard_quick_stats_title', [], $GLOBALS['current_language'] ?? 'en')); ?></h2>
        <div class="row g-4">
            <?php if (has_role('super_admin')): ?>
            <div class="col-md-6 col-lg-3">
                <div class="card text-center shadow-sm stat-card h-100">
                    <div class="card-body d-flex flex-column justify-content-center align-items-center py-4">
                        <i class="bi bi-people-fill fs-1 text-primary-blue mb-3"></i>
                        <h3 class="card-title display-5 fw-bold mb-1"><?php echo e($stats['total_users']); ?></h3>
                        <p class="card-text text-muted mb-0"><?php echo e(__('admin_stat_total_users', [], $GLOBALS['current_language'] ?? 'en')); ?></p>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if (has_role(['super_admin', 'business_admin'])): ?>
            <div class="col-md-6 col-lg-3">
                <div class="card text-center shadow-sm stat-card h-100">
                    <a href="<?php echo e(base_url('/admin/businesses?filter=pending')); ?>" class="text-decoration-none stretched-link" aria-label="<?php echo e(__('admin_stat_pending_businesses_link_aria', [], $GLOBALS['current_language'] ?? 'en')); // "View pending businesses" ?>">
                        <div class="card-body d-flex flex-column justify-content-center align-items-center py-4">
                            <i class="bi bi-building-exclamation fs-1 text-warning mb-3"></i>
                            <h3 class="card-title display-5 fw-bold mb-1"><?php echo e($stats['pending_businesses']); ?></h3>
                            <p class="card-text text-muted mb-0"><?php echo e(__('admin_stat_pending_businesses', [], $GLOBALS['current_language'] ?? 'en')); ?></p>
                        </div>
                    </a>
                </div>
            </div>
             <div class="col-md-6 col-lg-3">
                <div class="card text-center shadow-sm stat-card h-100">
                     <a href="<?php echo e(base_url('/admin/businesses?filter=active')); ?>" class="text-decoration-none stretched-link" aria-label="<?php echo e(__('admin_stat_total_venues_link_aria', [], $GLOBALS['current_language'] ?? 'en')); // "View active venues" ?>">
                        <div class="card-body d-flex flex-column justify-content-center align-items-center py-4">
                            <i class="bi bi-shop fs-1 text-primary-orange mb-3"></i>
                            <h3 class="card-title display-5 fw-bold mb-1"><?php echo e($stats['total_active_venues']); ?></h3>
                            <p class="card-text text-muted mb-0"><?php echo e(__('admin_stat_total_active_venues', [], $GLOBALS['current_language'] ?? 'en')); ?></p>
                        </div>
                    </a>
                </div>
            </div>
            <?php endif; ?>

            <?php if (has_role('super_admin')): ?>
             <div class="col-md-6 col-lg-3">
                <div class="card text-center shadow-sm stat-card h-100">
                     <a href="<?php echo e(base_url('/admin/pawssafe')); ?>" class="text-decoration-none stretched-link" aria-label="<?php echo e(__('admin_stat_active_pawssafe_link_aria', [], $GLOBALS['current_language'] ?? 'en')); // "View active PawsSafe providers" ?>">
                        <div class="card-body d-flex flex-column justify-content-center align-items-center py-4">
                            <i class="bi bi-shield-fill-check fs-1 text-success mb-3"></i>
                            <h3 class="card-title display-5 fw-bold mb-1"><?php echo e($stats['active_pawssafe_providers']); ?></h3>
                            <p class="card-text text-muted mb-0"><?php echo e(__('admin_stat_active_pawssafe', [], $GLOBALS['current_language'] ?? 'en')); ?></p>
                        </div>
                    </a>
                </div>
            </div>
            <?php endif; ?>
        </div>
         <p class="mt-4 text-center"><small class="text-muted fst-italic"><?php echo e(__('admin_dashboard_stats_stub_note', [], $GLOBALS['current_language'] ?? 'en')); ?></small></p>
    </section>

    <!-- Management Links Section -->
    <section id="management-links">
        <h2 class="h4 mb-4 fw-semibold text-text-dark"><?php echo e(__('admin_dashboard_management_sections_title', [], $GLOBALS['current_language'] ?? 'en')); ?></h2>
        <div class="list-group shadow-sm rounded">
            <?php if (has_role('super_admin')): ?>
                <a href="<?php echo e(base_url('/admin/users')); ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-3 disabled" aria-disabled="true" title="<?php echo e(__('admin_link_disabled_tooltip', [], $GLOBALS['current_language'] ?? 'en')); // "This feature is coming soon!" ?>">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-person-lines-fill me-3 fs-3 text-primary-blue align-middle"></i>
                        <div>
                            <span class="fw-semibold fs-5"><?php echo e(__('admin_link_manage_users', [], $GLOBALS['current_language'] ?? 'en')); ?></span>
                            <small class="d-block text-muted"><?php echo e(__('admin_link_manage_users_desc', [], $GLOBALS['current_language'] ?? 'en')); ?></small>
                        </div>
                    </div>
                    <i class="bi bi-chevron-right fs-5 text-muted"></i>
                </a>
            <?php endif; ?>

            <?php if (has_role(['super_admin', 'business_admin'])): ?>
                <a href="<?php echo e(base_url('/admin/businesses')); ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-3 disabled" aria-disabled="true" title="<?php echo e(__('admin_link_disabled_tooltip', [], $GLOBALS['current_language'] ?? 'en')); ?>">
                     <div class="d-flex align-items-center">
                        <i class="bi bi-building-gear me-3 fs-3 text-primary-orange align-middle"></i>
                        <div>
                            <span class="fw-semibold fs-5"><?php echo e(__('admin_link_manage_businesses', [], $GLOBALS['current_language'] ?? 'en')); ?></span>
                            <small class="d-block text-muted"><?php echo e(__('admin_link_manage_businesses_desc', [], $GLOBALS['current_language'] ?? 'en')); ?></small>
                        </div>
                    </div>
                    <i class="bi bi-chevron-right fs-5 text-muted"></i>
                </a>
            <?php endif; ?>

            <?php if (has_role(['super_admin', 'business_admin'])): ?>
                <a href="<?php echo e(get_route_url('admin_coupons_manage')); ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-3" title="<?php echo e(__('admin_link_manage_coupons_tooltip_stub', [], $GLOBALS['current_language'] ?? 'en')); ?>">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-tags-fill me-3 fs-3 text-warning align-middle"></i> <?php // Using tags icon for coupons ?>
                        <div>
                            <span class="fw-semibold fs-5"><?php echo e(__('admin_link_manage_coupons_stub', [], $GLOBALS['current_language'] ?? 'en')); ?></span>
                            <small class="d-block text-muted"><?php echo e(__('admin_link_manage_coupons_desc_stub', [], $GLOBALS['current_language'] ?? 'en')); ?></small>
                        </div>
                    </div>
                    <i class="bi bi-chevron-right fs-5 text-muted"></i>
                </a>
            <?php endif; ?>

            <?php if (has_role('super_admin')): ?>
                <a href="<?php echo e(base_url('/admin/pawssafe')); ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-3 disabled" aria-disabled="true" title="<?php echo e(__('admin_link_disabled_tooltip', [], $GLOBALS['current_language'] ?? 'en')); ?>">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-shield-shaded me-3 fs-3 text-success align-middle"></i>
                        <div>
                            <span class="fw-semibold fs-5"><?php echo e(__('admin_link_manage_pawssafe', [], $GLOBALS['current_language'] ?? 'en')); ?></span>
                            <small class="d-block text-muted"><?php echo e(__('admin_link_manage_pawssafe_desc', [], $GLOBALS['current_language'] ?? 'en')); ?></small>
                        </div>
                    </div>
                    <i class="bi bi-chevron-right fs-5 text-muted"></i>
                </a>
                <a href="<?php echo e(base_url('/admin/reviews')); ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-3" title="<?php echo e(__('admin_link_manage_reviews_tooltip', [], $GLOBALS['current_language'] ?? 'en')); // "Moderate and manage user reviews" ?>">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-chat-square-text-fill me-3 fs-3 text-primary align-middle"></i>
                        <div>
                            <span class="fw-semibold fs-5"><?php echo e(__('admin_link_manage_reviews', [], $GLOBALS['current_language'] ?? 'en')); // "Manage Reviews" ?></span>
                            <small class="d-block text-muted"><?php echo e(__('admin_link_manage_reviews_desc', [], $GLOBALS['current_language'] ?? 'en')); // "Approve, reject, or edit user-submitted reviews." ?></small>
                        </div>
                    </div>
                    <i class="bi bi-chevron-right fs-5 text-muted"></i>
                </a>
                <a href="<?php echo e(base_url('/admin/forums')); // Placeholder for actual forum moderation page ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-3 disabled" aria-disabled="true" title="<?php echo e(__('admin_link_disabled_tooltip', [], $GLOBALS['current_language'] ?? 'en')); ?>">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-chat-dots-fill me-3 fs-3 text-success align-middle"></i> <?php // Using chat-dots for forums ?>
                        <div>
                            <span class="fw-semibold fs-5"><?php echo e(__('admin_link_forum_moderation', [], $GLOBALS['current_language'] ?? 'en')); ?></span>
                            <small class="d-block text-muted"><?php echo e(__('admin_link_forum_moderation_desc', [], $GLOBALS['current_language'] ?? 'en')); ?></small>
                        </div>
                    </div>
                    <i class="bi bi-chevron-right fs-5 text-muted"></i>
                </a>
                 <a href="<?php echo e(base_url('/admin/translations')); ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-3 disabled" aria-disabled="true" title="<?php echo e(__('admin_link_disabled_tooltip', [], $GLOBALS['current_language'] ?? 'en')); ?>">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-translate me-3 fs-3 text-info align-middle"></i>
                        <div>
                            <span class="fw-semibold fs-5"><?php echo e(__('admin_link_manage_translations', [], $GLOBALS['current_language'] ?? 'en')); ?></span>
                            <small class="d-block text-muted"><?php echo e(__('admin_link_manage_translations_desc', [], $GLOBALS['current_language'] ?? 'en')); ?></small>
                        </div>
                    </div>
                    <i class="bi bi-chevron-right fs-5 text-muted"></i>
                </a>
                <a href="<?php echo e(base_url('/admin/analytics')); ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-3 disabled" aria-disabled="true" title="<?php echo e(__('admin_link_disabled_tooltip', [], $GLOBALS['current_language'] ?? 'en')); ?>">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-bar-chart-line-fill me-3 fs-3 text-purple align-middle" style="color: #6f42c1;"></i> <?php // Custom color for purple ?>
                        <div>
                            <span class="fw-semibold fs-5"><?php echo e(__('admin_link_view_analytics', [], $GLOBALS['current_language'] ?? 'en')); ?></span>
                            <small class="d-block text-muted"><?php echo e(__('admin_link_view_analytics_desc', [], $GLOBALS['current_language'] ?? 'en')); ?></small>
                        </div>
                    </div>
                    <i class="bi bi-chevron-right fs-5 text-muted"></i>
                </a>
                 <a href="<?php echo e(base_url('/admin/settings')); ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-3 disabled" aria-disabled="true" title="<?php echo e(__('admin_link_disabled_tooltip', [], $GLOBALS['current_language'] ?? 'en')); ?>">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-gear-fill me-3 fs-3 text-secondary align-middle"></i>
                        <div>
                            <span class="fw-semibold fs-5"><?php echo e(__('admin_link_system_settings', [], $GLOBALS['current_language'] ?? 'en')); ?></span>
                            <small class="d-block text-muted"><?php echo e(__('admin_link_system_settings_desc', [], $GLOBALS['current_language'] ?? 'en')); ?></small>
                        </div>
                    </div>
                    <i class="bi bi-chevron-right fs-5 text-muted"></i>
                </a>
            <?php endif; ?>
        </div>
        <p class="mt-3 text-center"><small class="text-muted fst-italic"><?php echo e(__('admin_dashboard_links_stub_note', [], $GLOBALS['current_language'] ?? 'en')); ?></small></p>
    </section>
</div>
<style>
    .stat-card { transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out; border:0; }
    .stat-card:hover { transform: translateY(-8px); box-shadow: var(--box-shadow-lg) !important; }
    .list-group-item-action { transition: background-color 0.15s ease-in-out; }
    .list-group-item-action.disabled { background-color: #f8f9fa; opacity: 0.7; cursor: not-allowed; }
    .list-group-item-action.disabled:hover { background-color: #f8f9fa; }
</style>

<?php
// Placeholder for translation strings
// __('page_title_admin_dashboard', [], $GLOBALS['current_language'] ?? 'en');
// __('role_display_text %s', [], $GLOBALS['current_language'] ?? 'en');
// __('admin_dashboard_welcome_user %s', [], $GLOBALS['current_language'] ?? 'en'); // %s for username
// __('admin_dashboard_quick_stats_title', [], $GLOBALS['current_language'] ?? 'en');
// __('admin_stat_total_users', [], $GLOBALS['current_language'] ?? 'en');
// __('admin_stat_pending_businesses', [], $GLOBALS['current_language'] ?? 'en');
// __('admin_stat_pending_businesses_link_aria', [], $GLOBALS['current_language'] ?? 'en');
// __('admin_stat_active_pawssafe', [], $GLOBALS['current_language'] ?? 'en');
// __('admin_stat_active_pawssafe_link_aria', [], $GLOBALS['current_language'] ?? 'en');
// __('admin_stat_total_active_venues', [], $GLOBALS['current_language'] ?? 'en'); // Changed from 'total_venues'
// __('admin_stat_total_venues_link_aria', [], $GLOBALS['current_language'] ?? 'en');
// __('admin_dashboard_stats_stub_note', [], $GLOBALS['current_language'] ?? 'en');
// __('admin_dashboard_management_sections_title', [], $GLOBALS['current_language'] ?? 'en');
// __('admin_link_manage_users', [], $GLOBALS['current_language'] ?? 'en');
// __('admin_link_manage_users_desc', [], $GLOBALS['current_language'] ?? 'en');
// __('admin_link_manage_businesses', [], $GLOBALS['current_language'] ?? 'en');
// __('admin_link_manage_businesses_desc', [], $GLOBALS['current_language'] ?? 'en');
// __('admin_link_manage_pawssafe', [], $GLOBALS['current_language'] ?? 'en');
// __('admin_link_manage_pawssafe_desc', [], $GLOBALS['current_language'] ?? 'en');
// __('admin_link_manage_translations', [], $GLOBALS['current_language'] ?? 'en');
// __('admin_link_manage_translations_desc', [], $GLOBALS['current_language'] ?? 'en');
// __('admin_link_view_analytics', [], $GLOBALS['current_language'] ?? 'en');
// __('admin_link_view_analytics_desc', [], $GLOBALS['current_language'] ?? 'en');
// __('admin_link_system_settings', [], $GLOBALS['current_language'] ?? 'en');
// __('admin_link_system_settings_desc', [], $GLOBALS['current_language'] ?? 'en');
// __('admin_link_disabled_tooltip', [], $GLOBALS['current_language'] ?? 'en');
// __('admin_dashboard_links_stub_note', [], $GLOBALS['current_language'] ?? 'en');
?>
