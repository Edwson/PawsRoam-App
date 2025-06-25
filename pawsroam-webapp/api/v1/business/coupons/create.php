<?php
require_once __DIR__ . '/../../../../../includes/init.php';
require_once __DIR__ . '/../../../../../includes/auth.php';
require_role_api(['business_admin', 'super_admin']); // Business admins or super admins can create

header('Content-Type: application/json');
$response = ['success' => false, 'message' => __('error_server_generic', [], $current_language)];
$current_user_id = current_user_id();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = __('error_method_not_allowed', [], $current_language);
    http_response_code(405);
    echo json_encode($response);
    exit;
}

if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
    $response['message'] = __('error_csrf_token_invalid', [], $current_language);
    http_response_code(403);
    echo json_encode($response);
    exit;
}

// --- Input Validation (Example - to be expanded) ---
$business_id = filter_input(INPUT_POST, 'business_id', FILTER_VALIDATE_INT);
$title = trim($_POST['title'] ?? '');
$discount_type = trim($_POST['discount_type'] ?? '');
// ... other fields: description, code, discount_value, start_date, end_date, etc.

$errors = [];

if (!$business_id) {
    $errors['business_id'] = __('error_coupon_business_id_required', [], $current_language);
} else {
    // TODO: Verify that the current_user_id owns this business_id if they are a 'business_admin'
    // Super_admin can create for any business.
    if (current_user_role() === 'business_admin') {
        // $stmt_check_owner = $db->prepare("SELECT owner_user_id FROM businesses WHERE id = :business_id");
        // $stmt_check_owner->bindParam(':business_id', $business_id, PDO::PARAM_INT);
        // $stmt_check_owner->execute();
        // $owner = $stmt_check_owner->fetchColumn();
        // if ($owner !== $current_user_id) {
        //     $errors['business_id'] = __('error_coupon_business_not_owned', [], $current_language);
        // }
    }
}

if (empty($title)) {
    $errors['title'] = __('error_coupon_title_required', [], $current_language);
}
if (empty($discount_type)) {
    $errors['discount_type'] = __('error_coupon_discount_type_required', [], $current_language);
}
// TODO: Add comprehensive validation for all coupon fields based on discount_type

if (!empty($errors)) {
    $response['message'] = __('error_validation_failed', [], $current_language);
    $response['errors'] = $errors;
    http_response_code(422);
    echo json_encode($response);
    exit;
}

// --- Database Interaction (Stub) ---
try {
    // $db = Database::getInstance()->getConnection();
    // Begin transaction
    // Prepare statement to insert into `coupons` table
    // Handle translatable fields (title, description) by potentially inserting into `translations` table
    // Commit transaction

    // STUBBED RESPONSE
    $response['success'] = true;
    $response['message'] = __('success_coupon_created_stub', [], $current_language); // "Coupon created successfully (Stub)"
    $response['coupon_id'] = rand(100, 999); // Placeholder new coupon ID
    http_response_code(201); // Created

} catch (PDOException $e) {
    // Rollback transaction if started
    error_log("PDOException in coupons/create.php: " . $e->getMessage());
    $response['message'] = __('error_coupon_create_failed_db_stub', [], $current_language); // "Failed to create coupon (DB Error Stub)"
    http_response_code(500);
} catch (Exception $e) {
    // Rollback transaction if started
    error_log("Exception in coupons/create.php: " . $e->getMessage());
    $response['message'] = __('error_server_generic', [], $current_language);
    http_response_code(500);
}

echo json_encode($response);
