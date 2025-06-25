<?php
// Included by index.php
require_once __DIR__ . '/../../../includes/auth.php';
require_role(['business_admin', 'super_admin']);

$pageTitle = __('page_title_create_coupon', [], $current_language);
$csrf_token = generate_csrf_token(); // For form submission

// For super_admin, they might need to select a business.
// For business_admin, their business_id should be pre-selected or fetched.
$businesses_for_select = [];
if (has_role('super_admin')) {
    // In a real app, fetch businesses:
    // $db = Database::getInstance()->getConnection();
    // $stmt_businesses = $db->query("SELECT id, name FROM businesses WHERE status = 'active' ORDER BY name ASC");
    // $businesses_for_select = $stmt_businesses->fetchAll(PDO::FETCH_ASSOC);
    $businesses_for_select = [ // Stub
        ['id' => 1, 'name' => 'Demo Pet Cafe (SuperAdmin Select)'],
        ['id' => 2, 'name' => 'Another Pet Store (SuperAdmin Select)']
    ];
} elseif (has_role('business_admin')) {
    // Fetch businesses owned by this admin
    // $db = Database::getInstance()->getConnection();
    // $stmt_businesses = $db->prepare("SELECT id, name FROM businesses WHERE owner_user_id = :owner_id AND status = 'active' ORDER BY name ASC");
    // $stmt_businesses->bindParam(':owner_id', current_user_id(), PDO::PARAM_INT);
    // $stmt_businesses->execute();
    // $businesses_for_select = $stmt_businesses->fetchAll(PDO::FETCH_ASSOC);
     $businesses_for_select = [ // Stub
        ['id' => 1, 'name' => 'My Pet Cafe (BusinessAdmin Owned)']
    ];
}

?>
<div class="container my-4 my-md-5">
    <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
        <h1 class="display-6 fw-bold"><?= $pageTitle ?></h1>
        <a href="<?= get_route_url('admin_coupons_manage') ?>" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i><?= __('button_back_to_manage_coupons', [], $current_language) ?>
        </a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-4">
            <form id="createCouponForm" method="POST"> <!-- JS will handle submission to API -->
                <?= csrf_input_field($csrf_token) ?>

                <?php if (has_role('super_admin') && count($businesses_for_select) > 1): ?>
                <div class="mb-3">
                    <label for="business_id" class="form-label"><?= __('coupon_form_business_label', [], $current_language) ?> <span class="text-danger">*</span></label>
                    <select class="form-select" id="business_id" name="business_id" required>
                        <option value=""><?= __('select_placeholder_business', [], $current_language) ?></option>
                        <?php foreach($businesses_for_select as $business): ?>
                            <option value="<?= e($business['id']) ?>"><?= e($business['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="invalid-feedback" data-field="business_id"></div>
                </div>
                <?php elseif (count($businesses_for_select) === 1): ?>
                    <input type="hidden" name="business_id" value="<?= e($businesses_for_select[0]['id']) ?>">
                    <p><em><?= __('coupon_creating_for_business_stub %s', [e($businesses_for_select[0]['name'])], $current_language) ?></em></p>
                <?php else: // Business admin with no active businesses ?>
                     <div class="alert alert-warning"><?= __('coupon_form_no_business_warning_stub', [], $current_language) ?></div>
                     <?php /* Disable form or parts of it */ ?>
                <?php endif; ?>


                <div class="mb-3">
                    <label for="title" class="form-label"><?= __('coupon_form_title_label', [], $current_language) ?> <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="title" name="title" required placeholder="<?= __('coupon_form_title_placeholder_stub', [], $current_language) ?>">
                    <div class="invalid-feedback" data-field="title"></div>
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label"><?= __('coupon_form_description_label', [], $current_language) ?></label>
                    <textarea class="form-control" id="description" name="description" rows="3" placeholder="<?= __('coupon_form_description_placeholder_stub', [], $current_language) ?>"></textarea>
                    <div class="invalid-feedback" data-field="description"></div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="code" class="form-label"><?= __('coupon_form_code_label', [], $current_language) ?></label>
                        <input type="text" class="form-control" id="code" name="code" placeholder="<?= __('coupon_form_code_placeholder_stub', [], $current_language) ?>">
                        <small class="form-text text-muted"><?= __('coupon_form_code_help_stub', [], $current_language) ?></small>
                        <div class="invalid-feedback" data-field="code"></div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="discount_type" class="form-label"><?= __('coupon_form_discount_type_label', [], $current_language) ?> <span class="text-danger">*</span></label>
                        <select class="form-select" id="discount_type" name="discount_type" required>
                            <option value=""><?= __('select_placeholder_discount_type', [], $current_language) ?></option>
                            <option value="percentage"><?= __('coupon_type_percentage', [], $current_language) ?></option>
                            <option value="fixed_amount"><?= __('coupon_type_fixed_amount', [], $current_language) ?></option>
                            <option value="free_item"><?= __('coupon_type_free_item', [], $current_language) ?></option>
                            <option value="service_upgrade"><?= __('coupon_type_service_upgrade', [], $current_language) ?></option>
                        </select>
                        <div class="invalid-feedback" data-field="discount_type"></div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="discount_value" class="form-label"><?= __('coupon_form_discount_value_label', [], $current_language) ?> <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="discount_value" name="discount_value" required placeholder="<?= __('coupon_form_discount_value_placeholder_stub', [], $current_language) ?>">
                        <small class="form-text text-muted"><?= __('coupon_form_discount_value_help_stub', [], $current_language) ?></small>
                        <div class="invalid-feedback" data-field="discount_value"></div>
                    </div>
                </div>

                <!-- Conditional fields based on discount_type (stub for JS to handle) -->
                <div id="freeItemDetails" class="mb-3 d-none">
                     <label for="item_name_if_free" class="form-label"><?= __('coupon_form_item_name_label_stub', [], $current_language) ?></label>
                     <input type="text" class="form-control" id="item_name_if_free" name="item_name_if_free" placeholder="<?= __('coupon_form_item_name_placeholder_stub', [], $current_language) ?>">
                </div>
                 <div id="serviceUpgradeDetails" class="mb-3 d-none">
                     <label for="service_upgrade_details" class="form-label"><?= __('coupon_form_service_upgrade_label_stub', [], $current_language) ?></label>
                     <textarea class="form-control" id="service_upgrade_details" name="service_upgrade_details" rows="2" placeholder="<?= __('coupon_form_service_upgrade_placeholder_stub', [], $current_language) ?>"></textarea>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="start_date" class="form-label"><?= __('coupon_form_start_date_label', [], $current_language) ?> <span class="text-danger">*</span></label>
                        <input type="datetime-local" class="form-control" id="start_date" name="start_date" required>
                        <div class="invalid-feedback" data-field="start_date"></div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="end_date" class="form-label"><?= __('coupon_form_end_date_label', [], $current_language) ?> <span class="text-danger">*</span></label>
                        <input type="datetime-local" class="form-control" id="end_date" name="end_date" required>
                        <div class="invalid-feedback" data-field="end_date"></div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="usage_limit_total" class="form-label"><?= __('coupon_form_usage_limit_total_label', [], $current_language) ?></label>
                        <input type="number" class="form-control" id="usage_limit_total" name="usage_limit_total" min="0" placeholder="<?= __('coupon_form_usage_limit_placeholder_stub', [], $current_language) ?>">
                        <small class="form-text text-muted"><?= __('coupon_form_usage_limit_total_help_stub', [], $current_language) ?></small>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="usage_limit_per_user" class="form-label"><?= __('coupon_form_usage_limit_user_label', [], $current_language) ?></label>
                        <input type="number" class="form-control" id="usage_limit_per_user" name="usage_limit_per_user" min="0" placeholder="1" value="1">
                         <small class="form-text text-muted"><?= __('coupon_form_usage_limit_user_help_stub', [], $current_language) ?></small>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="min_spend_amount" class="form-label"><?= __('coupon_form_min_spend_label_stub', [], $current_language) ?></label>
                    <input type="number" step="0.01" class="form-control" id="min_spend_amount" name="min_spend_amount" min="0" placeholder="0.00">
                </div>

                <div class="mb-3">
                    <label class="form-label"><?= __('coupon_form_status_label', [], $current_language) ?></label>
                    <select class="form-select" name="status">
                        <option value="active"><?= __('coupon_status_active', [], $current_language) ?></option>
                        <option value="inactive" selected><?= __('coupon_status_inactive', [], $current_language) ?></option>
                    </select>
                </div>

                <hr class="my-4">
                <div id="form-error-alert" class="alert alert-danger d-none" role="alert"></div>
                <div id="form-success-alert" class="alert alert-success d-none" role="alert"></div>

                <button type="submit" class="btn btn-primary btn-lg w-100" id="submitCreateCoupon">
                     <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                    <?= __('button_create_coupon_submit', [], $current_language) ?>
                </button>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('createCouponForm');
    const submitButton = document.getElementById('submitCreateCoupon');
    const spinner = submitButton.querySelector('.spinner-border');
    const formErrorAlert = document.getElementById('form-error-alert');
    const formSuccessAlert = document.getElementById('form-success-alert');
    const discountTypeSelect = document.getElementById('discount_type');
    const freeItemDetailsDiv = document.getElementById('freeItemDetails');
    const serviceUpgradeDetailsDiv = document.getElementById('serviceUpgradeDetails');

    if(discountTypeSelect) {
        discountTypeSelect.addEventListener('change', function() {
            freeItemDetailsDiv.classList.add('d-none');
            serviceUpgradeDetailsDiv.classList.add('d-none');
            if (this.value === 'free_item') {
                freeItemDetailsDiv.classList.remove('d-none');
            } else if (this.value === 'service_upgrade') {
                serviceUpgradeDetailsDiv.classList.remove('d-none');
            }
        });
    }

    if (form) {
        form.addEventListener('submit', function(event) {
            event.preventDefault();
            clearAllErrors();
            submitButton.disabled = true;
            spinner.classList.remove('d-none');
            formErrorAlert.classList.add('d-none');
            formSuccessAlert.classList.add('d-none');

            const formData = new FormData(form);

            fetch('<?= get_api_route_url('v1/business/coupons/create') ?>', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    formSuccessAlert.textContent = data.message || '<?= __('success_coupon_created_redirecting_stub', [], $current_language) ?>';
                    formSuccessAlert.classList.remove('d-none');
                    form.reset(); // Reset form fields
                     // Redirect to manage page or edit page for the new coupon
                    setTimeout(() => {
                        window.location.href = '<?= get_route_url('admin_coupons_manage') ?>';
                    }, 2000);
                } else {
                    formErrorAlert.textContent = data.message || '<?= __('error_coupon_create_failed_unknown_stub', [], $current_language) ?>';
                    formErrorAlert.classList.remove('d-none');
                    if (data.errors) {
                        for (const field in data.errors) {
                            const errorField = form.querySelector(`.invalid-feedback[data-field="${field}"]`);
                            const inputField = form.querySelector(`[name="${field}"]`);
                            if (errorField) {
                                errorField.textContent = data.errors[field];
                            }
                            if (inputField) {
                                inputField.classList.add('is-invalid');
                            }
                        }
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                formErrorAlert.textContent = '<?= __('error_coupon_create_failed_network_stub', [], $current_language) ?>';
                formErrorAlert.classList.remove('d-none');
            })
            .finally(() => {
                submitButton.disabled = false;
                spinner.classList.add('d-none');
            });
        });
    }
    function clearAllErrors() {
        form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
        form.querySelectorAll('.invalid-feedback').forEach(el => el.textContent = '');
        formErrorAlert.classList.add('d-none');
        formSuccessAlert.classList.add('d-none');
    }
});
</script>
