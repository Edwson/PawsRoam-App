<?php
// Admin Review Management Page
require_role(['super_admin']); // Only super admins can manage all reviews

$pageTitle = __('page_title_admin_reviews', [], $GLOBALS['current_language'] ?? 'en'); // "Review Management - Admin"

// Placeholder for fetching reviews based on filters (status, etc.)
// $status_filter = $_GET['status'] ?? 'pending'; // Example filter
// $reviews = []; // Fetch reviews here based on filter and pagination

?>

<div class="container my-4 my-md-5">
    <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
        <h1 class="display-6 fw-bold"><?php echo e($pageTitle); ?></h1>
        <div>
            <?php // Future: Add buttons for bulk actions or filters ?>
        </div>
    </div>

    <p class="lead text-muted"><?php echo e(__('admin_reviews_description', [], $GLOBALS['current_language'] ?? 'en')); // "Moderate user-submitted reviews for businesses. Approve, reject, or edit reviews." ?></p>

    <!-- Filters Section (Placeholder) -->
    <div class="card shadow-sm mb-4">
        <div class="card-header">
            <?php echo e(__('admin_reviews_filter_title', [], $GLOBALS['current_language'] ?? 'en')); // "Filter Reviews" ?>
        </div>
        <div class="card-body">
            <form id="filterReviewsForm" class="row gx-3 gy-2 align-items-center">
                <div class="col-sm-4">
                    <label class="visually-hidden" for="reviewStatusFilter"><?php echo e(__('admin_reviews_filter_status_label', [], $GLOBALS['current_language'] ?? 'en')); // "Status" ?></label>
                    <select class="form-select" id="reviewStatusFilter" name="status">
                        <option value="all" selected><?php echo e(__('admin_reviews_filter_status_all', [], $GLOBALS['current_language'] ?? 'en')); // "All Statuses" ?></option>
                        <option value="pending"><?php echo e(__('admin_reviews_filter_status_pending', [], $GLOBALS['current_language'] ?? 'en')); // "Pending" ?></option>
                        <option value="approved"><?php echo e(__('admin_reviews_filter_status_approved', [], $GLOBALS['current_language'] ?? 'en')); // "Approved" ?></option>
                        <option value="rejected"><?php echo e(__('admin_reviews_filter_status_rejected', [], $GLOBALS['current_language'] ?? 'en')); // "Rejected" ?></option>
                    </select>
                </div>
                <div class="col-sm-4">
                    <label class="visually-hidden" for="businessNameFilter"><?php echo e(__('admin_reviews_filter_business_label', [], $GLOBALS['current_language'] ?? 'en')); // "Business Name" ?></label>
                    <input type="text" class="form-control" id="businessNameFilter" name="business_name" placeholder="<?php echo e(__('admin_reviews_filter_business_placeholder', [], $GLOBALS['current_language'] ?? 'en')); // "Filter by Business Name..." ?>">
                </div>
                <div class="col-sm-2">
                    <button type="submit" class="btn btn-primary w-100" disabled title="<?php echo e(__('admin_reviews_filter_button_tooltip', [], $GLOBALS['current_language'] ?? 'en')); // "Filtering not yet implemented" ?>"><?php echo e(__('button_filter', [], $GLOBALS['current_language'] ?? 'en')); // "Filter" ?></button>
                </div>
            </form>
        </div>
    </div>


    <!-- Reviews List (Placeholder) -->
    <div class="card shadow-sm">
        <div class="card-header">
            <?php echo e(__('admin_reviews_list_title_pending', [], $GLOBALS['current_language'] ?? 'en')); // "Pending Reviews" ?> (Stub - will be dynamic)
        </div>
        <div class="card-body">
            <p class="text-center text-muted py-5">
                <i class="bi bi-hourglass-split fs-1 mb-3 d-block"></i>
                <?php echo e(__('admin_reviews_placeholder_text', [], $GLOBALS['current_language'] ?? 'en')); // "A list of reviews (pending, approved, rejected) with actions to approve/reject will appear here. Filtering options will be available. This functionality is currently a stub." ?>
            </p>
            <?php /* Example structure for a review item - for future implementation
            <div class="review-item border-bottom py-3">
                <div class="d-flex justify-content-between">
                    <div>
                        <h5 class="mb-1">Review Title by UserX for Business Y</h5>
                        <small class="text-muted">Rating: 4/5 | Submitted: YYYY-MM-DD</small>
                    </div>
                    <div>
                        <span class="badge bg-warning text-dark">Pending</span>
                    </div>
                </div>
                <p class="mt-2 mb-2">Review comment text goes here...</p>
                <div>
                    <button class="btn btn-sm btn-success disabled">Approve</button>
                    <button class="btn btn-sm btn-danger disabled ms-1">Reject</button>
                    <button class="btn btn-sm btn-outline-secondary disabled ms-1">Edit</button>
                </div>
            </div>
            */ ?>
        </div>
        <div class="card-footer text-center">
             <?php // Placeholder for pagination ?>
             <small class="text-muted"><?php echo e(__('admin_reviews_pagination_placeholder', [], $GLOBALS['current_language'] ?? 'en')); // "Pagination for reviews will appear here." ?></small>
        </div>
    </div>
</div>

<?php
// Translation placeholders
// __('page_title_admin_reviews', [], $GLOBALS['current_language'] ?? 'en');
// __('admin_reviews_description', [], $GLOBALS['current_language'] ?? 'en');
// __('admin_reviews_filter_title', [], $GLOBALS['current_language'] ?? 'en');
// __('admin_reviews_filter_status_label', [], $GLOBALS['current_language'] ?? 'en');
// __('admin_reviews_filter_status_all', [], $GLOBALS['current_language'] ?? 'en');
// __('admin_reviews_filter_status_pending', [], $GLOBALS['current_language'] ?? 'en');
// __('admin_reviews_filter_status_approved', [], $GLOBALS['current_language'] ?? 'en');
// __('admin_reviews_filter_status_rejected', [], $GLOBALS['current_language'] ?? 'en');
// __('admin_reviews_filter_business_label', [], $GLOBALS['current_language'] ?? 'en');
// __('admin_reviews_filter_business_placeholder', [], $GLOBALS['current_language'] ?? 'en');
// __('admin_reviews_filter_button_tooltip', [], $GLOBALS['current_language'] ?? 'en');
// __('button_filter', [], $GLOBALS['current_language'] ?? 'en'); // Reusable
// __('admin_reviews_list_title_pending', [], $GLOBALS['current_language'] ?? 'en');
// __('admin_reviews_placeholder_text', [], $GLOBALS['current_language'] ?? 'en');
// __('admin_reviews_pagination_placeholder', [], $GLOBALS['current_language'] ?? 'en');
?>
