<?php
// Included by index.php
require_once __DIR__ . '/../../../includes/auth.php';
require_role(['business_admin', 'super_admin']);

$pageTitle = __('page_title_edit_coupon_stub', [], $current_language); // "Edit Coupon (Stub)"
$csrf_token = generate_csrf_token();
$coupon_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$coupon_data_stub = null; // To be fetched or stubbed

if (!$coupon_id) {
    $_SESSION['error_flash'] = __('error_coupon_id_missing_for_edit_stub', [], $current_language);
    redirect_to(get_route_url('admin_coupons_manage'));
}

// STUB: Fetch coupon data to pre-fill form.
// In a real app, an API call or direct DB fetch would happen here,
// including ownership verification for business_admin.
$coupon_data_stub = [
    'id' => $coupon_id,
    'business_id' => 1, // Stub
    'title' => '15% Off Weekend Special (Fetched Stub)',
    'description' => 'Enjoy a 15% discount on all services this weekend only!',
    'code' => 'WEEKEND15',
    'discount_type' => 'percentage',
    'discount_value' => '15.00',
    'item_name_if_free' => null,
    'service_upgrade_details' => null,
    'start_date' => date('Y-m-d\TH:i', strtotime('+1 day')),
    'end_date' => date('Y-m-d\TH:i', strtotime('+3 day')),
    'usage_limit_total' => 100,
    'usage_limit_per_user' => 1,
    'min_spend_amount' => '20.00',
    'status' => 'active'
];
// Simulate business selection for super_admin (as in create form)
$businesses_for_select = [];
if (has_role('super_admin')) {
    $businesses_for_select = [
        ['id' => 1, 'name' => 'Demo Pet Cafe (SuperAdmin Select)'],
        ['id' => 2, 'name' => 'Another Pet Store (SuperAdmin Select)']
    ];
} elseif (has_role('business_admin')) {
     $businesses_for_select = [
        ['id' => 1, 'name' => 'My Pet Cafe (BusinessAdmin Owned)']
    ];
}


if (!$coupon_data_stub) { // If fetching failed in real app
    $_SESSION['error_flash'] = __('error_coupon_not_found_for_edit_stub', [], $current_language);
    redirect_to(get_route_url('admin_coupons_manage'));
}
$pageTitle = sprintf(__('page_title_edit_coupon_dynamic_stub %s', [], $current_language), e($coupon_data_stub['title']));

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
            <form id="editCouponForm" method="POST"> <!-- JS will handle submission to API -->
                <?= csrf_input_field($csrf_token) ?>
                <input type="hidden" name="coupon_id" value="<?= e($coupon_data_stub['id']) ?>">

                <?php if (has_role('super_admin') && count($businesses_for_select) > 0): ?>
                <div class="mb-3">
                    <label for="business_id" class="form-label"><?= __('coupon_form_business_label', [], $current_language) ?> <span class="text-danger">*</span></label>
                    <select class="form-select" id="business_id" name="business_id" required>
                        <option value=""><?= __('select_placeholder_business', [], $current_language) ?></option>
                        <?php foreach($businesses_for_select as $business): ?>
                            <option value="<?= e($business['id']) ?>" <?= ($coupon_data_stub['business_id'] == $business['id']) ? 'selected' : '' ?>><?= e($business['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="invalid-feedback" data-field="business_id"></div>
                </div>
                <?php else: // Business admin view - business is fixed or implicitly their own ?>
                    <input type="hidden" name="business_id" value="<?= e($coupon_data_stub['business_id']) ?>">
                    <?php // Optionally display the business name if needed ?>
                <?php endif; ?>

                <div class="mb-3">
                    <label for="title" class="form-label"><?= __('coupon_form_title_label', [], $current_language) ?> <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="title" name="title" required value="<?= e($coupon_data_stub['title']) ?>">
                    <div class="invalid-feedback" data-field="title"></div>
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label"><?= __('coupon_form_description_label', [], $current_language) ?></label>
                    <textarea class="form-control" id="description" name="description" rows="3"><?= e($coupon_data_stub['description']) ?></textarea>
                    <div class="invalid-feedback" data-field="description"></div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="code" class="form-label"><?= __('coupon_form_code_label', [], $current_language) ?></label>
                        <input type="text" class="form-control" id="code" name="code" value="<?= e($coupon_data_stub['code']) ?>">
                         <small class="form-text text-muted"><?= __('coupon_form_code_help_stub', [], $current_language) ?></small>
                        <div class="invalid-feedback" data-field="code"></div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="discount_type" class="form-label"><?= __('coupon_form_discount_type_label', [], $current_language) ?> <span class="text-danger">*</span></label>
                        <select class="form-select" id="discount_type" name="discount_type" required>
                            <option value="percentage" <?= $coupon_data_stub['discount_type'] === 'percentage' ? 'selected' : '' ?>><?= __('coupon_type_percentage', [], $current_language) ?></option>
                            <option value="fixed_amount" <?= $coupon_data_stub['discount_type'] === 'fixed_amount' ? 'selected' : '' ?>><?= __('coupon_type_fixed_amount', [], $current_language) ?></option>
                            <option value="free_item" <?= $coupon_data_stub['discount_type'] === 'free_item' ? 'selected' : '' ?>><?= __('coupon_type_free_item', [], $current_language) ?></option>
                            <option value="service_upgrade" <?= $coupon_data_stub['discount_type'] === 'service_upgrade' ? 'selected' : '' ?>><?= __('coupon_type_service_upgrade', [], $current_language) ?></option>
                        </select>
                        <div class="invalid-feedback" data-field="discount_type"></div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="discount_value" class="form-label"><?= __('coupon_form_discount_value_label', [], $current_language) ?> <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="discount_value" name="discount_value" required value="<?= e($coupon_data_stub['discount_value']) ?>">
                        <small class="form-text text-muted"><?= __('coupon_form_discount_value_help_stub', [], $current_language) ?></small>
                        <div class="invalid-feedback" data-field="discount_value"></div>
                    </div>
                </div>

                <div id="freeItemDetails" class="mb-3 <?= $coupon_data_stub['discount_type'] !== 'free_item' ? 'd-none' : '' ?>">
                     <label for="item_name_if_free" class="form-label"><?= __('coupon_form_item_name_label_stub', [], $current_language) ?></label>
                     <input type="text" class="form-control" id="item_name_if_free" name="item_name_if_free" value="<?= e($coupon_data_stub['item_name_if_free']) ?>">
                </div>
                 <div id="serviceUpgradeDetails" class="mb-3 <?= $coupon_data_stub['discount_type'] !== 'service_upgrade' ? 'd-none' : '' ?>">
                     <label for="service_upgrade_details" class="form-label"><?= __('coupon_form_service_upgrade_label_stub', [], $current_language) ?></label>
                     <textarea class="form-control" id="service_upgrade_details" name="service_upgrade_details" rows="2"><?= e($coupon_data_stub['service_upgrade_details']) ?></textarea>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="start_date" class="form-label"><?= __('coupon_form_start_date_label', [], $current_language) ?> <span class="text-danger">*</span></label>
                        <input type="datetime-local" class="form-control" id="start_date" name="start_date" required value="<?= e(date('Y-m-d\TH:i', strtotime($coupon_data_stub['start_date']))) ?>">
                        <div class="invalid-feedback" data-field="start_date"></div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="end_date" class="form-label"><?= __('coupon_form_end_date_label', [], $current_language) ?> <span class="text-danger">*</span></label>
                        <input type="datetime-local" class="form-control" id="end_date" name="end_date" required value="<?= e(date('Y-m-d\TH:i', strtotime($coupon_data_stub['end_date']))) ?>">
                        <div class="invalid-feedback" data-field="end_date"></div>
                    </div>
                </div>

                <div class="row">
                     <div class="col-md-6 mb-3">
                        <label for="usage_limit_total" class="form-label"><?= __('coupon_form_usage_limit_total_label', [], $current_language) ?></label>
                        <input type="number" class="form-control" id="usage_limit_total" name="usage_limit_total" min="0" value="<?= e($coupon_data_stub['usage_limit_total']) ?>">
                        <small class="form-text text-muted"><?= __('coupon_form_usage_limit_total_help_stub', [], $current_language) ?></small>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="usage_limit_per_user" class="form-label"><?= __('coupon_form_usage_limit_user_label', [], $current_language) ?></label>
                        <input type="number" class="form-control" id="usage_limit_per_user" name="usage_limit_per_user" min="0" value="<?= e($coupon_data_stub['usage_limit_per_user']) ?>">
                        <small class="form-text text-muted"><?= __('coupon_form_usage_limit_user_help_stub', [], $current_language) ?></small>
                    </div>
                </div>
                 <div class="mb-3">
                    <label for="min_spend_amount" class="form-label"><?= __('coupon_form_min_spend_label_stub', [], $current_language) ?></label>
                    <input type="number" step="0.01" class="form-control" id="min_spend_amount" name="min_spend_amount" min="0" value="<?= e($coupon_data_stub['min_spend_amount']) ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label"><?= __('coupon_form_status_label', [], $current_language) ?></label>
                    <select class="form-select" name="status">
                        <option value="active" <?= $coupon_data_stub['status'] === 'active' ? 'selected' : '' ?>><?= __('coupon_status_active', [], $current_language) ?></option>
                        <option value="inactive" <?= $coupon_data_stub['status'] === 'inactive' ? 'selected' : '' ?>><?= __('coupon_status_inactive', [], $current_language) ?></option>
                        <option value="expired" <?= $coupon_data_stub['status'] === 'expired' ? 'selected' : '' ?>><?= __('coupon_status_expired', [], $current_language) ?></option>
                        <option value="fully_redeemed" <?= $coupon_data_stub['status'] === 'fully_redeemed' ? 'selected' : '' ?>><?= __('coupon_status_fully_redeemed', [], $current_language) ?></option>
                    </select>
                </div>

                <hr class="my-4">
                <div id="form-error-alert" class="alert alert-danger d-none" role="alert"></div>
                <div id="form-success-alert" class="alert alert-success d-none" role="alert"></div>

                <button type="submit" class="btn btn-primary btn-lg w-100" id="submitEditCoupon">
                    <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                    <?= __('button_update_coupon_submit', [], $current_language) ?>
                </button>
            </form>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('editCouponForm');
    const submitButton = document.getElementById('submitEditCoupon');
    const spinner = submitButton.querySelector('.spinner-border');
    const formErrorAlert = document.getElementById('form-error-alert');
    const formSuccessAlert = document.getElementById('form-success-alert');
    const discountTypeSelect = document.getElementById('discount_type');
    const freeItemDetailsDiv = document.getElementById('freeItemDetails');
    const serviceUpgradeDetailsDiv = document.getElementById('serviceUpgradeDetails');

    function toggleConditionalFields() {
        freeItemDetailsDiv.classList.add('d-none');
        serviceUpgradeDetailsDiv.classList.add('d-none');
        if (discountTypeSelect.value === 'free_item') {
            freeItemDetailsDiv.classList.remove('d-none');
        } else if (discountTypeSelect.value === 'service_upgrade') {
            serviceUpgradeDetailsDiv.classList.remove('d-none');
        }
    }

    if(discountTypeSelect) {
        discountTypeSelect.addEventListener('change', toggleConditionalFields);
        toggleConditionalFields(); // Initial check on page load
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

            fetch('<?= get_api_route_url('v1/business/coupons/update') ?>', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    formSuccessAlert.textContent = data.message || '<?= __('success_coupon_updated_redirecting_stub', [], $current_language) ?>';
                    formSuccessAlert.classList.remove('d-none');
                    // Optionally redirect or just show success
                     setTimeout(() => {
                         window.location.href = '<?= get_route_url('admin_coupons_manage') ?>';
                     }, 1500);
                } else {
                    formErrorAlert.textContent = data.message || '<?= __('error_coupon_update_failed_unknown_stub', [], $current_language) ?>';
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
                formErrorAlert.textContent = '<?= __('error_coupon_update_failed_network_stub', [], $current_language) ?>';
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
