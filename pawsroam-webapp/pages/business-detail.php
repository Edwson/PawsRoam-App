<?php
// This page is intended to be included by index.php.
// Assumes $current_language, $pageTitle, and core functions are available.

// Page-specific PHP logic
$business_identifier = null;
$business_data = null;
$error_message = null;
$business_name_for_title = __('page_title_business_detail_default', [], $GLOBALS['current_language'] ?? 'en');
$user_has_recognized = false;
$user_existing_review = null;

if (isset($_GET['slug'])) {
    $business_identifier = trim($_GET['slug']);
    $identifier_type = 'slug';
} elseif (isset($_GET['id'])) {
    $business_identifier = (int)$_GET['id'];
    $identifier_type = 'id';
}

if ($business_identifier) {
    try {
        $db = Database::getInstance()->getConnection();
        if ($identifier_type === 'slug') {
            $stmt = $db->prepare("SELECT * FROM businesses WHERE slug = :slug AND status = 'active' LIMIT 1");
            $stmt->bindParam(':slug', $business_identifier);
        } else {
            $stmt = $db->prepare("SELECT * FROM businesses WHERE id = :id AND status = 'active' LIMIT 1");
            $stmt->bindParam(':id', $business_identifier, PDO::PARAM_INT);
        }
        $stmt->execute();
        $business_data = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$business_data) {
            $error_message = __('error_business_not_found', [], $GLOBALS['current_language'] ?? 'en');
            http_response_code(404);
        } else {
            $business_name_for_title = e($business_data['name']);
            $pageTitle = $business_name_for_title . " - " . (defined('APP_NAME') ? APP_NAME : 'PawsRoam');
            $business_display_name = $business_data['name'];
            $business_display_description = $business_data['description'];

            if (is_logged_in()) {
                $user_id_check = current_user_id();
                if ($user_id_check) {
                    $stmt_check_recognition = $db->prepare("SELECT id FROM business_recognitions WHERE user_id = :user_id AND business_id = :business_id LIMIT 1");
                    $stmt_check_recognition->bindParam(':user_id', $user_id_check, PDO::PARAM_INT);
                    $stmt_check_recognition->bindParam(':business_id', $business_data['id'], PDO::PARAM_INT);
                    $stmt_check_recognition->execute();
                    if ($stmt_check_recognition->fetch()) { $user_has_recognized = true; }

                    $stmt_check_review = $db->prepare("SELECT id, rating, title, comment, status FROM business_reviews WHERE user_id = :user_id AND business_id = :business_id AND (status = 'approved' OR status = 'pending') ORDER BY created_at DESC LIMIT 1");
                    $stmt_check_review->bindParam(':user_id', $user_id_check, PDO::PARAM_INT);
                    $stmt_check_review->bindParam(':business_id', $business_data['id'], PDO::PARAM_INT);
                    $stmt_check_review->execute();
                    $user_existing_review = $stmt_check_review->fetch(PDO::FETCH_ASSOC);
                }
            }
        }
    } catch (PDOException $e) {
        error_log("Database error fetching business details (ID/Slug: " . e($business_identifier) . "): " . $e->getMessage());
        $error_message = __('error_server_generic_page_load', [], $GLOBALS['current_language'] ?? 'en');
        http_response_code(500);
    } catch (Exception $e) {
        error_log("General error fetching business details (ID/Slug: " . e($business_identifier) . "): " . $e->getMessage());
        $error_message = __('error_server_generic_page_load', [], $GLOBALS['current_language'] ?? 'en');
        http_response_code(500);
    }
} else {
    $error_message = __('error_no_business_specified', [], $GLOBALS['current_language'] ?? 'en');
    http_response_code(400);
}

if (empty($pageTitle) && $error_message) { $pageTitle = __('page_title_error', [], $GLOBALS['current_language'] ?? 'en') . " - " . (defined('APP_NAME') ? APP_NAME : 'PawsRoam'); }
elseif (empty($pageTitle) && !$business_identifier) { $pageTitle = __('page_title_business_detail_default', [], $GLOBALS['current_language'] ?? 'en') . " - " . (defined('APP_NAME') ? APP_NAME : 'PawsRoam');}

if (empty($_SESSION[CSRF_TOKEN_NAME ?? 'csrf_token']) && function_exists('generate_csrf_token')) { generate_csrf_token(true); }
?>

<div class="container my-4 my-md-5">
    <?php if ($error_message): ?>
        <div class="alert alert-danger shadow-sm" role="alert">
            <h4 class="alert-heading"><i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo e(__('error_oops_title', [], $GLOBALS['current_language'] ?? 'en')); ?></h4>
            <p><?php echo e($error_message); ?></p>
            <hr>
            <p class="mb-0"><?php echo e(__('try_again_later_or_contact_support_text', [], $GLOBALS['current_language'] ?? 'en')); ?> <a href="<?php echo e(base_url('/')); ?>" class="alert-link fw-bold"><?php echo e(__('go_to_homepage_link_text', [], $GLOBALS['current_language'] ?? 'en')); ?></a>.</p>
        </div>
    <?php elseif ($business_data): ?>
        <div class="row g-4 g-lg-5">
            <div class="col-lg-8">
                <article class="business-detail-content bg-white p-4 shadow-sm rounded">
                    <header class="mb-4 border-bottom pb-3">
                        <h1 class="display-5 fw-bold text-primary-orange"><?php echo e($business_display_name); ?></h1>
                        <div class="mb-2 text-muted d-flex align-items-center flex-wrap">
                            <span class="pawstar-rating me-3" title="<?php echo e(sprintf(__('%d/3 PawStars', [], $GLOBALS['current_language'] ?? 'en'), (int)$business_data['pawstar_rating'])); ?>">
                                <?php for ($i = 1; $i <= 3; $i++): ?>
                                    <i class="bi <?php echo ($i <= (int)$business_data['pawstar_rating']) ? 'bi-star-fill text-warning' : 'bi-star text-body-tertiary'; ?>"></i>
                                <?php endfor; ?>
                            </span>
                            <span class="me-3"><i class="bi bi-award me-1"></i><span id="totalRecognitionsCount"><?php echo e($business_data['total_recognitions'] ?? 0); ?></span> <?php echo e(__('recognitions_text', [], $GLOBALS['current_language'] ?? 'en')); ?></span>
                             <span id="averageReviewRatingDisplay" class="me-3"></span> <?php // Placeholder for average review rating ?>
                        </div>
                        <p class="text-muted fst-italic"><?php echo e(__('address_placeholder_short', [], $GLOBALS['current_language'] ?? 'en')); ?></p>
                    </header>

                    <?php if (!empty($business_display_description)): ?>
                        <section id="business-description" class="mb-4">
                            <h2 class="h4 mb-3 text-text-dark"><?php echo e(__('about_this_place_title', [], $GLOBALS['current_language'] ?? 'en')); ?></h2>
                            <div class="formatted-text lead fs-6"><?php echo nl2br(e($business_display_description)); ?></div>
                        </section>
                    <?php endif; ?>
                    <hr class="my-4">
                    <section id="pet-policies" class="mb-4">
                        <h3 class="h5 mb-3 fw-semibold text-text-dark"><?php echo e(__('pet_policies_title', [], $GLOBALS['current_language'] ?? 'en')); ?></h3>
                         <div class="list-group list-group-flush">
                            <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <?php echo e(__('allows_off_leash_label', [], $GLOBALS['current_language'] ?? 'en')); ?>
                                <span class="badge bg-<?php echo $business_data['allows_off_leash'] ? 'success' : 'danger'; ?> rounded-pill"><?php echo $business_data['allows_off_leash'] ? e(__('yes', [], $GLOBALS['current_language'] ?? 'en')) : e(__('no', [], $GLOBALS['current_language'] ?? 'en')); ?></span>
                            </div>
                             <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <?php echo e(__('has_water_bowls_label', [], $GLOBALS['current_language'] ?? 'en')); ?>
                                <span class="badge bg-<?php echo $business_data['has_water_bowls'] ? 'success' : 'danger'; ?> rounded-pill"><?php echo $business_data['has_water_bowls'] ? e(__('yes', [], $GLOBALS['current_language'] ?? 'en')) : e(__('no', [], $GLOBALS['current_language'] ?? 'en')); ?></span>
                            </div>
                            <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <?php echo e(__('has_pet_menu_label', [], $GLOBALS['current_language'] ?? 'en')); ?>
                                <span class="badge bg-<?php echo $business_data['has_pet_menu'] ? 'success' : 'danger'; ?> rounded-pill"><?php echo $business_data['has_pet_menu'] ? e(__('yes', [], $GLOBALS['current_language'] ?? 'en')) : e(__('no', [], $GLOBALS['current_language'] ?? 'en')); ?></span>
                            </div>
                             <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <?php echo e(__('pet_size_limit_label', [], $GLOBALS['current_language'] ?? 'en')); ?>
                                <span class="text-muted"><?php echo e(ucfirst($business_data['pet_size_limit'] ?? 'Any')); ?></span>
                            </div>
                            <?php if (!empty($business_data['weight_limit_kg'])): ?>
                            <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <?php echo e(__('weight_limit_kg_label', [], $GLOBALS['current_language'] ?? 'en')); ?>
                                <span class="text-muted"><?php echo e($business_data['weight_limit_kg']); ?> kg</span>
                            </div>
                            <?php endif; ?>
                        </div>
                        <p class="mt-3 text-muted fst-italic small"><?php echo e(__('policy_note_contact_business', [], $GLOBALS['current_language'] ?? 'en')); ?></p>
                    </section>
                    <hr class="my-4">
                    <section id="amenities" class="mb-4">
                        <h3 class="h5 mb-3 fw-semibold text-text-dark"><?php echo e(__('amenities_title', [], $GLOBALS['current_language'] ?? 'en')); ?></h3>
                        <p class="text-muted"><?php echo e(__('amenities_placeholder_text', [], $GLOBALS['current_language'] ?? 'en')); ?></p>
                    </section>
                    <hr class="my-4">
                    <section id="photos" class="mb-4">
                         <h3 class="h5 mb-3 fw-semibold text-text-dark"><?php echo e(__('photos_title', [], $GLOBALS['current_language'] ?? 'en')); ?></h3>
                        <p class="text-muted"><?php echo e(__('photos_placeholder_text', [], $GLOBALS['current_language'] ?? 'en')); ?></p>
                    </section>
                    <hr class="my-4">

                    <section id="reviewsAndForm" class="mb-4">
                        <h3 class="h5 mb-3 fw-semibold text-text-dark"><?php echo e(__('user_reviews_title', [], $GLOBALS['current_language'] ?? 'en')); ?></h3>
                        <div id="reviewSubmissionSection" class="mb-4">
                        <?php if (is_logged_in()): ?>
                            <?php if ($user_existing_review): ?>
                                <div class="alert alert-info shadow-sm rounded">
                                    <h4 class="alert-heading h6 fw-semibold"><?php echo e(__('review_already_submitted_title', [], $GLOBALS['current_language'] ?? 'en')); ?></h4>
                                    <p class="mb-1"><?php echo e(sprintf(__('review_your_rating_text %s', [], $GLOBALS['current_language'] ?? 'en'), $user_existing_review['rating'])); ?></p>
                                    <?php if ($user_existing_review['status'] === 'pending'): ?>
                                        <p class="mb-1"><em><i class="bi bi-hourglass-split me-1"></i><?php echo e(__('review_status_pending_message', [], $GLOBALS['current_language'] ?? 'en')); ?></em></p>
                                    <?php elseif ($user_existing_review['status'] === 'approved'): ?>
                                         <p class="mb-1"><em><i class="bi bi-check-circle-fill me-1 text-success"></i><?php echo e(__('review_status_approved_message', [], $GLOBALS['current_language'] ?? 'en')); ?></em></p>
                                    <?php elseif ($user_existing_review['status'] === 'rejected'): ?>
                                         <p class="mb-1"><em><i class="bi bi-x-circle-fill me-1 text-danger"></i><?php echo e(__('review_status_rejected_message', [], $GLOBALS['current_language'] ?? 'en')); ?></em></p>
                                    <?php endif; ?>
                                    <hr class="my-2"><p class="mb-0 small text-muted"><?php echo e(__('review_edit_functionality_soon_text', [], $GLOBALS['current_language'] ?? 'en')); ?></small></p>
                                </div>
                            <?php else: ?>
                                <div class="card shadow-sm border-0">
                                    <div class="card-body bg-light rounded p-4">
                                        <h4 class="h6 mb-3 fw-semibold"><?php echo e(__('write_your_review_title', [], $GLOBALS['current_language'] ?? 'en')); ?></h4>
                                        <form id="submitReviewForm" action="<?php echo e(base_url('/api/v1/reviews/create.php')); ?>" method="POST" novalidate>
                                            <?php echo csrf_input_field(); ?>
                                            <input type="hidden" name="business_id" value="<?php echo e($business_data['id']); ?>">
                                            <div id="reviewFormMessages" class="mb-3" role="alert" aria-live="assertive"></div>
                                            <div class="mb-3">
                                                <label class="form-label d-block fw-medium"><?php echo e(__('review_rating_label', [], $GLOBALS['current_language'] ?? 'en')); ?> <span class="text-danger">*</span></label>
                                                <div class="star-rating-input fs-3">
                                                    <?php for ($s = 5; $s >= 1; $s--): ?>
                                                    <input type="radio" id="rating-<?php echo $s; ?>" name="rating" value="<?php echo $s; ?>" required class="form-check-input"/>
                                                    <label for="rating-<?php echo $s; ?>" title="<?php echo e(sprintf(__('%d stars_rating_title', [], $GLOBALS['current_language'] ?? 'en'), $s)); ?>"><i class="bi bi-star-fill"></i></label>
                                                    <?php endfor; ?>
                                                </div>
                                                <div class="invalid-feedback" id="ratingError"></div>
                                            </div>
                                            <div class="form-floating mb-3">
                                                <input type="text" class="form-control" id="review_title" name="title" placeholder="<?php echo e(__('review_title_placeholder', [], $GLOBALS['current_language'] ?? 'en')); ?>">
                                                <label for="review_title"><?php echo e(__('review_title_label', [], $GLOBALS['current_language'] ?? 'en')); ?></label>
                                                <div class="invalid-feedback" id="titleError"></div>
                                            </div>
                                            <div class="form-floating mb-3">
                                                <textarea class="form-control" id="review_comment" name="comment" style="height: 120px" placeholder="<?php echo e(__('review_comment_placeholder', [], $GLOBALS['current_language'] ?? 'en')); ?>"></textarea>
                                                <label for="review_comment"><?php echo e(__('review_comment_label', [], $GLOBALS['current_language'] ?? 'en')); ?></label>
                                                <div class="invalid-feedback" id="commentError"></div>
                                            </div>
                                            <button type="submit" class="btn btn-primary" id="submitReviewBtn">
                                                <span class="button-text"><?php echo e(__('review_submit_button', [], $GLOBALS['current_language'] ?? 'en')); ?></span>
                                                <span class="spinner-border spinner-border-sm ms-2 d-none" role="status" aria-hidden="true"></span>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="alert alert-light border shadow-sm text-center p-4">
                                <p class="mb-2"><?php
                                $login_url = base_url('/login?return_to=' . urlencode($_SERVER['REQUEST_URI'] ?? ''));
                                $login_link = '<a href="'.e($login_url).'" class="alert-link fw-bold">'.e(__('review_login_link_text', [], $GLOBALS['current_language'] ?? 'en')).'</a>';
                                $register_link = '<a href="'.base_url('/register').'" class="alert-link fw-bold">'.e(__('review_register_link_text', [], $GLOBALS['current_language'] ?? 'en')).'</a>';
                                echo sprintf(e(__('review_login_prompt %s or %s', [], $GLOBALS['current_language'] ?? 'en')), $login_link, $register_link);
                                ?></p>
                            </div>
                        <?php endif; ?>
                        </div>

                        <hr class="my-4">
                        <div id="reviewsListContainer" class="mb-4">
                            <div class="text-center py-3 review-list-initial-message">
                                <div class="spinner-border text-primary-orange" role="status" style="width: 2rem; height: 2rem;">
                                    <span class="visually-hidden"><?php echo e(__('reviews_loading_placeholder', [], $GLOBALS['current_language'] ?? 'en')); ?></span>
                                </div>
                                <p class="mt-2 text-muted"><?php echo e(__('reviews_loading_placeholder', [], $GLOBALS['current_language'] ?? 'en')); ?></p>
                            </div>
                        </div>
                        <div id="reviewsPaginationControls" class="text-center d-none mt-4">
                             <button class="btn btn-outline-secondary btn-lg" id="loadMoreReviewsBtn">
                                <i class="bi bi-arrow-down-circle me-2"></i><?php echo e(__('button_load_more_reviews', [], $GLOBALS['current_language'] ?? 'en')); ?>
                             </button>
                        </div>
                    </section>
                </article>
            </div>
            <?php /* Sidebar column */ ?>
            <div class="col-lg-4">
                 <aside class="business-detail-sidebar sticky-lg-top bg-light p-4 rounded shadow-sm" style="top: 100px;">
                    <?php /* Map, Contact, Actions sections as before */ ?>
                 </aside>
            </div>
        </div>
    <?php endif; ?>
</div>
<style>
.star-rating-input { display: inline-block; direction: rtl; }
.star-rating-input input[type="radio"] { display: none; }
.star-rating-input label { color: #ccc; cursor: pointer; transition: color 0.2s; padding: 0 0.05em; font-size:1.5em; /* Adjust star size */ }
.star-rating-input input[type="radio"]:checked ~ label,
.star-rating-input label:hover,
.star-rating-input label:hover ~ label { color: #ffc107; }
.star-rating-input.is-invalid label { /* For validation indication */ }
.review-item .card-body { padding: 1.25rem; }
.review-item .comment-text { white-space: pre-wrap; }
</style>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // ... (existing map init JS, recognize business JS) ...

    const submitReviewForm = document.getElementById('submitReviewForm');
    if (submitReviewForm) { /* ... existing JS for review form submission ... */ }

    const reviewsListContainer = document.getElementById('reviewsListContainer');
    const reviewsPaginationControls = document.getElementById('reviewsPaginationControls');
    const loadMoreReviewsBtn = document.getElementById('loadMoreReviewsBtn');
    let currentReviewPage = 1;
    let isLoadingReviews = false;
    const businessIdForReviews = <?php echo $business_data ? (int)$business_data['id'] : 'null'; ?>;

    async function loadBusinessReviews(businessId, page = 1, append = false) {
        if (isLoadingReviews || !businessId) return;
        isLoadingReviews = true;
        if(loadMoreReviewsBtn) { loadMoreReviewsBtn.disabled = true; loadMoreReviewsBtn.querySelector('.spinner-border') ? loadMoreReviewsBtn.querySelector('.spinner-border').classList.remove('d-none') : null; }

        const initialMessageEl = reviewsListContainer.querySelector('.review-list-initial-message');
        if (!append && !initialMessageEl) { // If not appending and no initial message, show spinner
            reviewsListContainer.innerHTML = '<div class="text-center py-3"><div class="spinner-border text-secondary" role="status"><span class="visually-hidden">Loading...</span></div></div>';
        }

        try {
            const response = await fetch(`<?php echo e(base_url('/api/v1/reviews/list.php')); ?>?business_id=${businessId}&page=${page}&limit=5`);
            const result = await response.json();

            if (!append || (append && page === 1)) { // Clear if first page load (append might be true if triggered by submit)
                reviewsListContainer.innerHTML = '';
            }
            if(initialMessageEl) initialMessageEl.remove();

            if (response.ok && result.success) {
                if (result.reviews && result.reviews.length > 0) {
                    result.reviews.forEach(review => {
                        const reviewCard = document.createElement('div');
                        reviewCard.className = 'card mb-3 shadow-sm border-0 review-item';
                        let reviewPhotosHTML = ''; // Placeholder for future photo display

                        let starsHTML = '';
                        for (let i = 1; i <= 5; i++) {
                            starsHTML += `<i class="bi ${i <= review.rating ? 'bi-star-fill text-warning' : 'bi-star text-body-tertiary'}"></i>`;
                        }

                        reviewCard.innerHTML = `
                            <div class="card-body">
                                <div class="d-flex align-items-start mb-2">
                                    <img src="${escapeHtml(review.author_avatar_url)}" alt="${escapeHtml(review.author_username)}" class="rounded-circle me-3" style="width:40px; height:40px; object-fit:cover;">
                                    <div>
                                        <h6 class="card-title mb-0 fw-bold">${escapeHtml(review.author_username)}</h6>
                                        <small class="text-muted" title="${escapeHtml(review.created_at_iso)}">${escapeHtml(review.created_at_formatted)}</small>
                                    </div>
                                    <div class="ms-auto review-stars fs-5" title="${review.rating}/5 stars">${starsHTML}</div>
                                </div>
                                ${review.title ? `<h5 class="h6 card-subtitle mb-2 fw-semibold">${escapeHtml(review.title)}</h5>` : ''}
                                <p class="card-text comment-text mb-0">${review.comment || ''}</p>
                                ${reviewPhotosHTML}
                            </div>`; // review.comment already has nl2br from API
                        reviewsListContainer.appendChild(reviewCard);
                    });

                    currentReviewPage = result.pagination.current_page;
                    if (result.pagination.current_page < result.pagination.total_pages) {
                        if(reviewsPaginationControls) reviewsPaginationControls.classList.remove('d-none');
                        if(loadMoreReviewsBtn) loadMoreReviewsBtn.disabled = false;
                    } else {
                        if(reviewsPaginationControls) reviewsPaginationControls.classList.add('d-none');
                    }
                } else if (page === 1) {
                    reviewsListContainer.innerHTML = '<p class="text-muted text-center py-4 fst-italic"><?php echo e(addslashes(__('reviews_none_found_message', [], $GLOBALS['current_language'] ?? 'en' ))); ?></p>';
                    if(reviewsPaginationControls) reviewsPaginationControls.classList.add('d-none');
                } else {
                     if(reviewsPaginationControls) reviewsPaginationControls.classList.add('d-none'); // No more pages
                }
            } else {
                if (page === 1) reviewsListContainer.innerHTML = `<p class="text-danger text-center py-3">${escapeHtml(result.message || 'Failed to load reviews.')}</p>`;
            }
        } catch (error) { /* ... error handling ... */ }
        finally {
            isLoadingReviews = false;
            if(loadMoreReviewsBtn) {
                loadMoreReviewsBtn.disabled = (currentReviewPage >= (result?.pagination?.total_pages || 1));
                loadMoreReviewsBtn.querySelector('.spinner-border') ? loadMoreReviewsBtn.querySelector('.spinner-border').classList.add('d-none') : null;
            }
        }
    }

    if (businessIdForReviews) {
        loadBusinessReviews(businessIdForReviews, 1);
    }

    if (loadMoreReviewsBtn && businessIdForReviews) {
        loadMoreReviewsBtn.addEventListener('click', function() {
            if(loadMoreReviewsBtn.querySelector('.spinner-border')) loadMoreReviewsBtn.querySelector('.spinner-border').classList.remove('d-none');
            loadBusinessReviews(businessIdForReviews, currentReviewPage + 1, true);
        });
    }
    // escapeHtml function as defined before
    function escapeHtml(unsafe) { /* ... */ }
    // Other helper functions for review form submission as defined before
    const submitReviewForm = document.getElementById('submitReviewForm');
    if (submitReviewForm) { /* ... existing JS for review form submission, ensure it calls loadBusinessReviews on success ... */ }

});
</script>
<?php // Translation placeholders... ?>
