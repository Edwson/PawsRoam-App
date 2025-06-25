<?php
// This page is intended to be included by index.php.
// Assumes $current_language, $pageTitle, and core functions are available.

// Page-specific PHP logic
$business_identifier = null;
$business_data = null;
$error_message = null;
$business_name_for_title = __('page_title_business_detail_default', [], $GLOBALS['current_language'] ?? 'en'); // "Business Details"
$user_has_recognized = false; // Flag for "Recognize Business" button state

// Determine identifier type (slug or id) from GET parameters
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
        } else { // 'id'
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

            // Check if current logged-in user has already recognized this business
            if (is_logged_in()) {
                $user_id_check = current_user_id(); // Ensure this function exists and is loaded
                if ($user_id_check) { // Check if user_id is valid
                    $stmt_check_recognition = $db->prepare(
                        "SELECT id FROM business_recognitions WHERE user_id = :user_id AND business_id = :business_id LIMIT 1"
                    );
                    $stmt_check_recognition->bindParam(':user_id', $user_id_check, PDO::PARAM_INT);
                    $stmt_check_recognition->bindParam(':business_id', $business_data['id'], PDO::PARAM_INT);
                    $stmt_check_recognition->execute();
                    if ($stmt_check_recognition->fetch()) {
                        $user_has_recognized = true;
                    }
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

if (empty($pageTitle) && $error_message) {
    $pageTitle = __('page_title_error', [], $GLOBALS['current_language'] ?? 'en') . " - " . (defined('APP_NAME') ? APP_NAME : 'PawsRoam');
} elseif (empty($pageTitle) && !$business_identifier) {
     $pageTitle = __('page_title_business_detail_default', [], $GLOBALS['current_language'] ?? 'en') . " - " . (defined('APP_NAME') ? APP_NAME : 'PawsRoam');
}

// Ensure CSRF token is generated for the page if forms/actions need it
if (empty($_SESSION[CSRF_TOKEN_NAME ?? 'csrf_token']) && function_exists('generate_csrf_token')) {
    generate_csrf_token(true);
}
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
            <!-- Main Content Column -->
            <div class="col-lg-8">
                <article class="business-detail-content bg-white p-4 shadow-sm rounded">
                    <header class="mb-4 border-bottom pb-3">
                        <h1 class="display-5 fw-bold text-primary-orange"><?php echo e($business_display_name); ?></h1>
                        <div class="mb-2 text-muted d-flex align-items-center flex-wrap">
                            <span class="pawstar-rating me-3" title="<?php echo e((int)$business_data['pawstar_rating']); ?>/5 stars">
                                <?php for ($i = 1; $i <= 3; $i++): // PawStars are 0-3 ?>
                                    <i class="bi <?php echo ($i <= (int)$business_data['pawstar_rating']) ? 'bi-star-fill text-warning' : 'bi-star text-body-tertiary'; ?>"></i>
                                <?php endfor; ?>
                                <?php if((int)$business_data['pawstar_rating'] == 0): // Show 3 empty if 0 stars ?>
                                    <?php for ($i = 1; $i <= 3; $i++) { echo '<i class="bi bi-star text-body-tertiary"></i>'; } ?>
                                <?php endif; ?>
                            </span>
                            <span class="me-3"><i class="bi bi-award me-1"></i><span id="totalRecognitionsCount"><?php echo e($business_data['total_recognitions'] ?? 0); ?></span> <?php echo e(__('recognitions_text', [], $GLOBALS['current_language'] ?? 'en')); ?></span>
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
                    <section id="reviews" class="mb-4">
                        <h3 class="h5 mb-3 fw-semibold text-text-dark"><?php echo e(__('user_reviews_title', [], $GLOBALS['current_language'] ?? 'en')); ?></h3>
                        <p class="text-muted"><?php echo e(__('reviews_placeholder_text', [], $GLOBALS['current_language'] ?? 'en')); ?></p>
                    </section>
                </article>
            </div>

            <div class="col-lg-4">
                <aside class="business-detail-sidebar sticky-lg-top bg-light p-4 rounded shadow-sm" style="top: 100px;">
                    <div class="mb-4">
                        <div id="business-detail-map" class="rounded" style="height: 280px; background-color: #f0f0f0;">
                            <p class="d-flex justify-content-center align-items-center h-100 text-muted"><?php echo e(__('map_placeholder_text', [], $GLOBALS['current_language'] ?? 'en')); ?></p>
                        </div>
                    </div>
                    <div class="mb-4">
                        <h4 class="h5 mb-3 text-text-dark"><?php echo e(__('location_and_contact_title', [], $GLOBALS['current_language'] ?? 'en')); ?></h4>
                        <p><strong><i class="bi bi-geo-alt-fill me-2 text-primary-orange"></i><?php echo e(__('address_label', [], $GLOBALS['current_language'] ?? 'en')); ?></strong><br><span class="ms-4"><?php echo e(__('address_placeholder_text', [], $GLOBALS['current_language'] ?? 'en')); ?></span></p>
                        <p><strong><i class="bi bi-telephone-fill me-2 text-primary-orange"></i><?php echo e(__('phone_label', [], $GLOBALS['current_language'] ?? 'en')); ?></strong><br><a href="tel:+1234567890" class="ms-4 text-decoration-none"><?php echo e(__('phone_placeholder_text', [], $GLOBALS['current_language'] ?? 'en')); ?></a></p>
                        <p><strong><i class="bi bi-globe me-2 text-primary-orange"></i><?php echo e(__('website_label', [], $GLOBALS['current_language'] ?? 'en')); ?></strong><br><a href="#" target="_blank" rel="noopener noreferrer" class="ms-4 text-decoration-none"><?php echo e(__('website_placeholder_text', [], $GLOBALS['current_language'] ?? 'en')); ?></a></p>
                        <a href="#" class="btn btn-primary w-100 mt-2"><i class="bi bi-map-fill me-2"></i><?php echo e(__('get_directions_button', [], $GLOBALS['current_language'] ?? 'en')); ?></a>
                    </div>
                    <div>
                        <h4 class="h5 mb-3 text-text-dark"><?php echo e(__('actions_title', [], $GLOBALS['current_language'] ?? 'en')); ?></h4>
                        <?php echo csrf_input_field(); ?>
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item px-0">
                                <button id="recognizeBusinessBtn"
                                        class="btn <?php echo $user_has_recognized ? 'btn-success disabled' : 'btn-outline-success'; ?> w-100 text-start"
                                        data-business-id="<?php echo e($business_data['id']); ?>"
                                        <?php if (!is_logged_in()): ?>
                                            disabled title="<?php echo e(__('tooltip_login_to_recognize', [], $GLOBALS['current_language'] ?? 'en')); ?>"
                                        <?php elseif ($user_has_recognized): ?>
                                            disabled title="<?php echo e(__('tooltip_already_recognized', [], $GLOBALS['current_language'] ?? 'en')); ?>"
                                        <?php else: ?>
                                            title="<?php echo e(__('tooltip_recognize_this_place', [], $GLOBALS['current_language'] ?? 'en')); ?>"
                                        <?php endif; ?>>
                                    <i class="bi <?php echo $user_has_recognized ? 'bi-check-circle-fill' : 'bi-star'; ?> me-2"></i>
                                    <span class="button-text"><?php echo $user_has_recognized ? e(__('recognize_button_already_recognized_text', [], $GLOBALS['current_language'] ?? 'en')) : e(__('recognize_this_place_button', [], $GLOBALS['current_language'] ?? 'en')); ?></span>
                                    <span class="spinner-border spinner-border-sm ms-2 d-none" role="status" aria-hidden="true"></span>
                                </button>
                                <small id="recognizeStatusMsg" class="form-text d-block mt-1 ps-1"></small>
                            </li>
                            <li class="list-group-item px-0"><a href="#" class="text-decoration-none d-block disabled" title="Feature coming soon"><i class="bi bi-heart-fill me-2 text-danger"></i><?php echo e(__('add_to_favorites_button', [], $GLOBALS['current_language'] ?? 'en')); ?></a></li>
                            <li class="list-group-item px-0"><a href="#" class="text-decoration-none d-block disabled" title="Feature coming soon"><i class="bi bi-share-fill me-2 text-primary-blue"></i><?php echo e(__('share_this_place_button', [], $GLOBALS['current_language'] ?? 'en')); ?></a></li>
                            <li class="list-group-item px-0"><a href="#" class="text-decoration-none d-block disabled" title="Feature coming soon"><i class="bi bi-flag-fill me-2 text-warning"></i><?php echo e(__('report_issue_button', [], $GLOBALS['current_language'] ?? 'en')); ?></a></li>
                            <?php if (is_logged_in() && $business_data && ( (has_role(['business_admin']) && current_user_id() == $business_data['owner_user_id']) || has_role('super_admin') ) ): ?>
                                <li class="list-group-item px-0 mt-2 pt-2 border-top">
                                    <a href="<?php echo e(base_url('/admin/businesses/edit?id=' . $business_data['id'] )); ?>" class="btn btn-outline-secondary w-100"><i class="bi bi-pencil-square me-2"></i><?php echo e(__('edit_business_button_admin', [], $GLOBALS['current_language'] ?? 'en')); ?></a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </aside>
            </div>
        </div>
    <?php endif; ?>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const businessMapElement = document.getElementById('business-detail-map');
    const businessJSData = <?php echo $business_data ? json_encode([
        'latitude' => (float)$business_data['latitude'],
        'longitude' => (float)$business_data['longitude'],
        'name' => $business_data['name'],
        'pawstar_rating' => (int)$business_data['pawstar_rating']
    ], JSON_NUMERIC_CHECK) : 'null'; ?>;

    function initializeDetailMap() { /* ... existing map init JS ... */ }
    initializeDetailMap();
    document.addEventListener('pawsRoamGoogleMapsApiReady', initializeDetailMap);
    document.addEventListener('pawsRoamGoogleMapsApiFailed', function handleMapApiFailOnDetail() { /* ... */ });

    const recognizeBtn = document.getElementById('recognizeBusinessBtn');
    const recognizeStatusMsg = document.getElementById('recognizeStatusMsg');
    const csrfTokenInput = document.querySelector('input[name="<?php echo e(CSRF_TOKEN_NAME ?? 'csrf_token'); ?>"]');

    if (recognizeBtn && csrfTokenInput) {
        recognizeBtn.addEventListener('click', async function() {
            if (this.disabled || this.classList.contains('disabled')) return;

            const businessId = this.dataset.businessId;
            const csrfToken = csrfTokenInput.value;
            const buttonTextSpan = this.querySelector('.button-text');
            const spinnerSpan = this.querySelector('.spinner-border');
            const originalButtonText = buttonTextSpan.textContent;
            const iconElement = this.querySelector('i.bi');

            buttonTextSpan.textContent = '<?php echo e(addslashes(__('state_text_processing', [], $GLOBALS['current_language'] ?? 'en'))); ?>';
            spinnerSpan.classList.remove('d-none');
            this.disabled = true;
            if(recognizeStatusMsg) recognizeStatusMsg.textContent = '';

            const formData = new FormData();
            formData.append('business_id', businessId);
            formData.append('<?php echo e(CSRF_TOKEN_NAME ?? 'csrf_token'); ?>', csrfToken);

            try {
                const response = await fetch('<?php echo e(base_url("/api/v1/business/recognize.php")); ?>', {
                    method: 'POST', body: formData, headers: { 'Accept': 'application/json' }
                });
                const result = await response.json();

                if (response.ok && result.success) {
                    if(recognizeStatusMsg) {
                        recognizeStatusMsg.textContent = result.message || '<?php echo e(addslashes(__('recognize_success_message_short', [], $GLOBALS['current_language'] ?? 'en' ))); ?>';
                        recognizeStatusMsg.className = 'form-text text-success d-block mt-1 ps-1';
                    }
                    buttonTextSpan.textContent = '<?php echo e(addslashes(__('recognize_button_recognized_text', [], $GLOBALS['current_language'] ?? 'en' ))); ?>';
                    this.classList.remove('btn-outline-success');
                    this.classList.add('btn-success', 'disabled');
                    if(iconElement) { iconElement.classList.remove('bi-star'); iconElement.classList.add('bi-check-circle-fill'); }

                    const totalRecognitionsElement = document.getElementById('totalRecognitionsCount');
                    if (totalRecognitionsElement && result.total_recognitions !== undefined) {
                        totalRecognitionsElement.textContent = result.total_recognitions;
                    }
                    // Update PawStar rating display
                    if (result.new_pawstar_rating !== undefined) {
                        const pawstarRatingSpan = document.querySelector('.pawstar-rating');
                        if (pawstarRatingSpan) {
                            pawstarRatingSpan.title = result.new_pawstar_rating + "/3 PawStars"; // Max 3 PawStars
                            let starsHTML = '';
                            for (let i = 1; i <= 3; i++) { // Loop for 3 PawStars
                                starsHTML += `<i class="bi ${i <= result.new_pawstar_rating ? 'bi-star-fill text-warning' : 'bi-star text-body-tertiary'}"></i>`;
                            }
                             // Clear existing stars and add new ones
                            while(pawstarRatingSpan.firstChild && pawstarRatingSpan.firstChild.tagName === 'I'){
                                pawstarRatingSpan.removeChild(pawstarRatingSpan.firstChild);
                            }
                            pawstarRatingSpan.insertAdjacentHTML('afterbegin', starsHTML);
                        }
                    }
                } else {
                    if(recognizeStatusMsg) {
                        recognizeStatusMsg.textContent = result.message || '<?php echo e(addslashes(__('recognize_failed_message_short', [], $GLOBALS['current_language'] ?? 'en' ))); ?>';
                        recognizeStatusMsg.className = 'form-text text-danger d-block mt-1 ps-1';
                    }
                    buttonTextSpan.textContent = originalButtonText;
                    if(response.status === 409) { // Already recognized
                         buttonTextSpan.textContent = '<?php echo e(addslashes(__('recognize_button_already_recognized_text', [], $GLOBALS['current_language'] ?? 'en' ))); ?>';
                         this.classList.add('disabled', 'btn-success');
                         this.classList.remove('btn-outline-success');
                         if(iconElement) { iconElement.classList.remove('bi-star'); iconElement.classList.add('bi-check-circle-fill');}
                    } else {
                        this.disabled = false;
                    }
                }
            } catch (error) {
                console.error("Recognize business error:", error);
                if(recognizeStatusMsg) {
                    recognizeStatusMsg.textContent = '<?php echo e(addslashes(__('recognize_failed_network', [], $GLOBALS['current_language'] ?? 'en' ))); ?>';
                    recognizeStatusMsg.className = 'form-text text-danger d-block mt-1 ps-1';
                }
                buttonTextSpan.textContent = originalButtonText;
                this.disabled = false;
            } finally {
                spinnerSpan.classList.add('d-none');
            }
        });
    }
});
</script>
<?php // Translation placeholders... ?>
