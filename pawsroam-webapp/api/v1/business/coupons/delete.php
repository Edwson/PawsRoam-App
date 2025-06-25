<?php
require_once __DIR__ . '/../../../../../includes/init.php';
require_once __DIR__ . '/../../../../../includes/auth.php';
require_role_api(['business_admin', 'super_admin']);

header('Content-Type: application/json');
$response = ['success' => false, 'message' => __('error_server_generic', [], $current_language)];
$current_user_id = current_user_id();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { // Using POST for state change + CSRF
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

$coupon_id = filter_input(INPUT_POST, 'coupon_id', FILTER_VALIDATE_INT);

if (!$coupon_id) {
    $response['message'] = __('error_coupon_id_required_for_delete', [], $current_language);
    http_response_code(422);
    echo json_encode($response);
    exit;
}

// --- Database Interaction (Stub) ---
try {
    // $db = Database::getInstance()->getConnection();
    // Begin transaction

    // Verify ownership if business_admin or if coupon exists for super_admin
    // $stmt_check_coupon = $db->prepare("SELECT c.business_id, b.owner_user_id FROM coupons c JOIN businesses b ON c.business_id = b.id WHERE c.id = :coupon_id");
    // $stmt_check_coupon->bindParam(':coupon_id', $coupon_id, PDO::PARAM_INT);
    // $stmt_check_coupon->execute();
    // $coupon_owner_data = $stmt_check_coupon->fetch(PDO::FETCH_ASSOC);

    // if (!$coupon_owner_data) {
    //    $response['message'] = __('error_coupon_not_found_for_delete_stub', [], $current_language);
    //    http_response_code(404);
    //    echo json_encode($response); exit;
    // }
    // if (current_user_role() === 'business_admin' && $coupon_owner_data['owner_user_id'] !== $current_user_id) {
    //    $response['message'] = __('error_coupon_delete_not_owner_stub', [], $current_language);
    //    http_response_code(403);
    //    echo json_encode($response); exit;
    // }

    // Perform soft delete (e.g., set status to 'inactive' or 'deleted') or hard delete
    // For now, let's assume changing status to 'inactive' as a form of "deletion" by admin
    // $stmt_delete = $db->prepare("UPDATE coupons SET status = 'inactive', updated_at = CURRENT_TIMESTAMP WHERE id = :coupon_id");
    // $stmt_delete->bindParam(':coupon_id', $coupon_id, PDO::PARAM_INT);
    // $stmt_delete->execute();
    // if ($stmt_delete->rowCount() > 0) { ... }

    // Commit transaction

    // STUBBED RESPONSE
    $response['success'] = true;
    $response['message'] = __('success_coupon_deleted_stub', [], $current_language); // "Coupon deleted/inactivated successfully (Stub)"
    $response['coupon_id'] = $coupon_id;

} catch (PDOException $e) {
    // Rollback transaction
    error_log("PDOException in coupons/delete.php: " . $e->getMessage());
    $response['message'] = __('error_coupon_delete_failed_db_stub', [], $current_language);
    http_response_code(500);
} catch (Exception $e) {
    // Rollback transaction
    error_log("Exception in coupons/delete.php: " . $e->getMessage());
    $response['message'] = __('error_server_generic', [], $current_language);
    http_response_code(500);
}

echo json_encode($response);
