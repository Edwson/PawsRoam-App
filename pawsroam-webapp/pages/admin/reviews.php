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
        <div class="card-header d-flex justify-content-between align-items-center">
            <span id="reviewsListTitle"><?php echo e(__('admin_reviews_list_title_filtered', [], $GLOBALS['current_language'] ?? 'en')); // "Filtered Reviews" ?></span>
            <div id="reviewsListSpinner" class="spinner-border spinner-border-sm text-secondary d-none" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
        <div class="card-body p-0"> <?php // p-0 to make table flush with card borders ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th scope="col"><?php echo e(__('admin_review_col_id', [], $GLOBALS['current_language'] ?? 'en')); // "ID" ?></th>
                            <th scope="col"><?php echo e(__('admin_review_col_business', [], $GLOBALS['current_language'] ?? 'en')); // "Business" ?></th>
                            <th scope="col"><?php echo e(__('admin_review_col_author', [], $GLOBALS['current_language'] ?? 'en')); // "Author" ?></th>
                            <th scope="col"><?php echo e(__('admin_review_col_rating', [], $GLOBALS['current_language'] ?? 'en')); // "Rating" ?></th>
                            <th scope="col"><?php echo e(__('admin_review_col_comment', [], $GLOBALS['current_language'] ?? 'en')); // "Comment (Snippet)" ?></th>
                            <th scope="col"><?php echo e(__('admin_review_col_status', [], $GLOBALS['current_language'] ?? 'en')); // "Status" ?></th>
                            <th scope="col"><?php echo e(__('admin_review_col_date', [], $GLOBALS['current_language'] ?? 'en')); // "Date" ?></th>
                            <th scope="col" class="text-end"><?php echo e(__('admin_review_col_actions', [], $GLOBALS['current_language'] ?? 'en')); // "Actions" ?></th>
                        </tr>
                    </thead>
                    <tbody id="adminReviewsTableBody">
                        <tr>
                            <td colspan="8" class="text-center p-5 text-muted" id="adminReviewsTableMessage">
                                <div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div>
                                <p class="mt-2"><?php echo e(__('admin_reviews_loading_initial', [], $GLOBALS['current_language'] ?? 'en')); // "Loading reviews..." ?></p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer text-center bg-light" id="adminReviewsPaginationContainer">
            <?php // Pagination controls will be rendered here by JavaScript ?>
            <small class="text-muted"><?php echo e(__('admin_reviews_pagination_placeholder', [], $GLOBALS['current_language'] ?? 'en')); ?></small>
        </div>
    </div>
    <?php echo csrf_input_field(); // For action buttons ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const filterForm = document.getElementById('filterReviewsForm');
    const reviewsTableBody = document.getElementById('adminReviewsTableBody');
    const reviewsTableMessage = document.getElementById('adminReviewsTableMessage');
    const paginationContainer = document.getElementById('adminReviewsPaginationContainer');
    const listTitle = document.getElementById('reviewsListTitle');
    const listSpinner = document.getElementById('reviewsListSpinner');
    const csrfToken = document.querySelector('input[name="<?php echo e(CSRF_TOKEN_NAME ?? 'csrf_token'); ?>"]').value;

    let currentPage = 1;
    let currentFilters = { status: 'pending', business_name_search: '' }; // Default to pending

    // Set initial filter form value
    if(filterForm.status) filterForm.status.value = 'pending';


    async function loadAdminReviews(page = 1, filters = {}) {
        if(listSpinner) listSpinner.classList.remove('d-none');
        reviewsTableBody.innerHTML = `<tr><td colspan="8" class="text-center p-5"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></td></tr>`;
        paginationContainer.innerHTML = '';

        const params = new URLSearchParams({ page, limit: 15, ...filters });
        try {
            const response = await fetch(`<?php echo e(base_url('/api/v1/admin/reviews/list-all.php')); ?>?${params.toString()}`);
            const result = await response.json();

            reviewsTableBody.innerHTML = ''; // Clear loading/previous
            if (response.ok && result.success) {
                if (result.reviews && result.reviews.length > 0) {
                    result.reviews.forEach(review => renderReviewRow(review));
                } else {
                    reviewsTableBody.innerHTML = `<tr><td colspan="8" class="text-center p-5 text-muted"><?php echo e(__('admin_reviews_none_found_with_filters', [], $GLOBALS['current_language'] ?? 'en')); // "No reviews found matching your criteria." ?></td></tr>`;
                }
                renderPagination(result.pagination);
                if(listTitle) listTitle.textContent = `<?php echo e(__('admin_reviews_list_title_filtered', [], $GLOBALS['current_language'] ?? 'en')); ?> (${result.pagination.total_items})`;
            } else {
                reviewsTableBody.innerHTML = `<tr><td colspan="8" class="text-center p-5 text-danger">${escapeHtml(result.message || '<?php echo e(addslashes(__('admin_reviews_load_error', [], $GLOBALS['current_language'] ?? 'en' ))); // "Error loading reviews." ?>')}</td></tr>`;
            }
        } catch (error) {
            console.error("Error loading admin reviews:", error);
            reviewsTableBody.innerHTML = `<tr><td colspan="8" class="text-center p-5 text-danger"><?php echo e(addslashes(__('admin_reviews_load_network_error', [], $GLOBALS['current_language'] ?? 'en' ))); // "Network error loading reviews." ?></td></tr>`;
        } finally {
            if(listSpinner) listSpinner.classList.add('d-none');
        }
    }

    function renderReviewRow(review) {
        const row = reviewsTableBody.insertRow();
        row.setAttribute('id', `review-row-${review.id}`);
        row.innerHTML = `
            <td>${review.id}</td>
            <td><a href="<?php echo e(base_url('/business/')); ?>${escapeHtml(review.business_id)}" target="_blank">${escapeHtml(review.business_name)}</a></td>
            <td>${escapeHtml(review.author_username)}</td>
            <td class="text-center">${review.rating}/5</td>
            <td>${escapeHtml(review.comment_snippet || 'N/A')}</td>
            <td><span class="badge bg-${getStatusBadgeClass(review.status)}">${escapeHtml(review.status.charAt(0).toUpperCase() + review.status.slice(1))}</span></td>
            <td title="${escapeHtml(review.created_at)}">${new Date(review.created_at).toLocaleDateString()}</td>
            <td class="text-end review-actions">
                ${review.status === 'pending' ? `
                    <button class="btn btn-sm btn-success action-btn" data-action="approve" data-review-id="${review.id}" title="<?php echo e(__('admin_review_action_approve', [], $GLOBALS['current_language'] ?? 'en')); // "Approve Review" ?>"><i class="bi bi-check-lg"></i></button>
                    <button class="btn btn-sm btn-danger action-btn" data-action="reject" data-review-id="${review.id}" title="<?php echo e(__('admin_review_action_reject', [], $GLOBALS['current_language'] ?? 'en')); // "Reject Review" ?>"><i class="bi bi-x-lg"></i></button>
                ` : ''}
                ${review.status === 'approved' ? `
                    <button class="btn btn-sm btn-warning action-btn" data-action="reject" data-review-id="${review.id}" title="<?php echo e(__('admin_review_action_reject', [], $GLOBALS['current_language'] ?? 'en')); ?>"><i class="bi bi-hand-thumbs-down-fill"></i></button>
                ` : ''}
                ${review.status === 'rejected' ? `
                    <button class="btn btn-sm btn-info action-btn" data-action="approve" data-review-id="${review.id}" title="<?php echo e(__('admin_review_action_approve_rejected', [], $GLOBALS['current_language'] ?? 'en')); // "Approve this (previously rejected) Review" ?>"><i class="bi bi-hand-thumbs-up-fill"></i></button>
                ` : ''}
                <?php /* <button class="btn btn-sm btn-outline-secondary ms-1 action-btn" data-action="edit" data-review-id="${review.id}" title="Edit Review (Soon)" disabled><i class="bi bi-pencil-square"></i></button> */ ?>
            </td>
        `;
        // Add event listeners to new action buttons
        row.querySelectorAll('.action-btn').forEach(btn => btn.addEventListener('click', handleReviewAction));
    }

    async function handleReviewAction(event) {
        const button = event.currentTarget;
        const action = button.dataset.action;
        const reviewId = button.dataset.reviewId;
        const newStatus = action; // 'approve' becomes 'approved', 'reject' becomes 'rejected'

        if (!confirm(`<?php echo e(__('admin_review_action_confirm %s %s', [], $GLOBALS['current_language'] ?? 'en')); // "Are you sure you want to %s review ID %s?" ?>`.replace('%s', action).replace('%s', reviewId))) {
            return;
        }

        button.disabled = true;
        button.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';

        const formData = new FormData();
        formData.append('review_id', reviewId);
        formData.append('new_status', newStatus === 'approve' ? 'approved' : 'rejected');
        formData.append('<?php echo e(CSRF_TOKEN_NAME ?? 'csrf_token'); ?>', csrfToken);

        try {
            const response = await fetch(`<?php echo e(base_url('/api/v1/admin/reviews/update-status.php')); ?>`, {
                method: 'POST', body: formData, headers: {'Accept': 'application/json'}
            });
            const result = await response.json();
            if (response.ok && result.success) {
                // alert(result.message); // Or use a less intrusive notification
                // Refresh the specific row or the whole list
                loadAdminReviews(currentPage, currentFilters);
            } else {
                alert(result.message || `<?php echo e(addslashes(__('admin_review_action_failed %s', [], $GLOBALS['current_language'] ?? 'en' ))); // "Failed to %s review." ?>`.replace('%s', action));
                button.disabled = false; // Re-enable on failure
                button.innerHTML = action === 'approve' ? '<i class="bi bi-check-lg"></i>' : '<i class="bi bi-x-lg"></i>'; // Restore icon
            }
        } catch (error) {
            console.error("Error updating review status:", error);
            alert(`<?php echo e(addslashes(__('admin_review_action_network_error %s', [], $GLOBALS['current_language'] ?? 'en' ))); // "Network error trying to %s review." ?>`.replace('%s', action));
            button.disabled = false;
            button.innerHTML = action === 'approve' ? '<i class="bi bi-check-lg"></i>' : '<i class="bi bi-x-lg"></i>';
        }
    }

    function renderPagination(pagination) { /* ... JS for pagination controls ... */ }
    function getStatusBadgeClass(status) { /* ... JS for badge class based on status ... */ }
    function escapeHtml(unsafe) { /* ... JS escapeHtml ... */ }


    if (filterForm) {
        filterForm.addEventListener('submit', function(e) {
            e.preventDefault();
            currentPage = 1;
            currentFilters.status = filterForm.status.value === 'all' ? '' : filterForm.status.value;
            currentFilters.business_name_search = filterForm.business_name_search.value;
            loadAdminReviews(currentPage, currentFilters);
            // Update filter button to be enabled
            const filterButton = filterForm.querySelector('button[type="submit"]');
            if(filterButton) { filterButton.disabled = false; filterButton.removeAttribute('title');}
        });
    }

    // Initial load (defaulting to pending)
    loadAdminReviews(currentPage, currentFilters);
});
</script>

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
