<?php
/**
 * API Endpoint for Admin to Update Review Status
 * Method: POST
 * Expected FormData: review_id (int, required), new_status (string 'approved' or 'rejected'), csrf_token
 */

// Bootstrap
if (session_status() == PHP_SESSION_NONE) { session_start(/* ...options... */); }
if (!defined('BASE_PATH')) { define('BASE_PATH', dirname(__DIR__, 5)); }
if (!defined('DS')) { define('DS', DIRECTORY_SEPARATOR); }

$required_files = [ /* ... core files ... */
    BASE_PATH . DS . 'config' . DS . 'constants.php', BASE_PATH . DS . 'config' . DS . 'database.php',
    BASE_PATH . DS . 'includes' . DS . 'functions.php', BASE_PATH . DS . 'includes' . DS . 'translation.php',
    BASE_PATH . DS . 'includes' . DS . 'auth.php'
];
foreach ($required_files as $file) {
    if (file_exists($file)) { require_once $file; }
    else { http_response_code(500); header('Content-Type: application/json'); error_log("CRITICAL: Admin Update Review Status API missing $file"); echo json_encode(['success' => false, 'message' => 'Server config error.']); exit; }
}

$current_api_language = $GLOBALS['current_language'] ?? DEFAULT_LANGUAGE ?? 'en';
header('Content-Type: application/json');

// --- Access Control & Request Method ---
require_role(['super_admin']);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); echo json_encode(['success' => false, 'message' => __('error_method_not_allowed', [], $current_api_language)]); exit;
}

// --- CSRF Token Validation ---
if (!validate_csrf_token($_POST[CSRF_TOKEN_NAME ?? 'csrf_token'] ?? null)) {
    http_response_code(403); echo json_encode(['success' => false, 'message' => __('error_csrf_token_invalid', [], $current_api_language)]); exit;
}

// --- Input Collection & Validation (STUB - basic for now) ---
$review_id = filter_var($_POST['review_id'] ?? null, FILTER_VALIDATE_INT);
$new_status = isset($_POST['new_status']) ? trim($_POST['new_status']) : null;

$errors = [];
$allowed_new_statuses = ['approved', 'rejected'];

if (!$review_id || $review_id <= 0) { $errors['review_id'] = __('error_admin_invalid_review_id_for_update', [], $current_api_language); } // "Invalid review ID provided for status update."
if (empty($new_status) || !in_array($new_status, $allowed_new_statuses)) { $errors['new_status'] = __('error_admin_invalid_new_status_for_review', [], $current_api_language); } // "Invalid new status provided for review."

// TODO: Check if review_id exists in DB before attempting update.

if (!empty($errors)) {
    http_response_code(422); // Unprocessable Entity
    echo json_encode(['success' => false, 'message' => __('error_validation_failed', [], $current_api_language), 'errors' => $errors]);
    exit;
}

// --- Process Status Update ---
$db = Database::getInstance()->getConnection();
try {
    // Fetch current review details to get business_id and old_status
    $stmt_fetch = $db->prepare("SELECT business_id, status FROM business_reviews WHERE id = :review_id");
    $stmt_fetch->bindParam(':review_id', $review_id, PDO::PARAM_INT);
    $stmt_fetch->execute();
    $review_details = $stmt_fetch->fetch(PDO::FETCH_ASSOC);

    if (!$review_details) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => __('error_admin_review_not_found_for_update', [], $current_api_language)]); // "Review ID not found."
        exit;
    }

    $old_status = $review_details['status'];
    $business_id = (int)$review_details['business_id'];

    if ($old_status === $new_status) {
        http_response_code(200); // Or 304 Not Modified, but 200 with message is fine for API
        echo json_encode(['success' => true, 'message' => __('info_admin_review_status_already_set %s', [], $current_api_language)]); // "Review status is already %s."
        exit;
    }

    $db->beginTransaction();

    $stmt_update = $db->prepare("UPDATE business_reviews SET status = :new_status, updated_at = NOW() WHERE id = :review_id");
    $stmt_update->bindParam(':new_status', $new_status);
    $stmt_update->bindParam(':review_id', $review_id, PDO::PARAM_INT);

    if (!$stmt_update->execute()) {
        $db->rollBack();
        throw new Exception("Failed to update review status in DB.");
    }

    // Update business aggregates if status changed to/from 'approved'
    $needs_aggregate_update = false;
    if (($old_status !== 'approved' && $new_status === 'approved') || ($old_status === 'approved' && $new_status !== 'approved')) {
        $needs_aggregate_update = true;
    }

    if ($needs_aggregate_update) {
        if (!update_business_review_aggregates($business_id)) {
            // Log this error, but the primary status update might have succeeded.
            // Depending on policy, you might rollback or just log. For now, log and proceed.
            error_log("Admin Update Review Status API: Failed to update business review aggregates for business ID {$business_id} after status change of review ID {$review_id}.");
            // If this is critical, you might throw an exception here to trigger rollback.
            // For now, we assume review status update is primary.
        }
    }

    $db->commit();

    http_response_code(200); // OK
    echo json_encode([
        'success' => true,
        'message' => sprintf(__('success_admin_review_status_updated %s %s', [], $current_api_language), $review_id, $new_status),
        'review_id' => $review_id,
        'new_status' => $new_status
    ]);

} catch (PDOException $e) {
    if ($db->inTransaction()) { $db->rollBack(); }
    error_log("Admin Update Review Status API (PDOException): " . $e->getMessage());
    http_response_code(500); echo json_encode(['success' => false, 'message' => __('error_server_generic', [], $current_api_language)]);
} catch (Exception $e) {
    error_log("Admin Update Review Status API (Exception): " . $e->getMessage());
    http_response_code(500); echo json_encode(['success' => false, 'message' => __('error_server_generic', [], $current_api_language)]);
}
exit;

<?php
// Translation placeholders
// __('error_admin_invalid_review_id_for_update', [], $current_api_language);
// __('error_admin_invalid_new_status_for_review', [], $current_api_language);
// __('success_admin_review_status_updated %s %s', [], $current_api_language); // Review ID %s status updated to %s.
// __('error_admin_review_not_found_or_no_change', [], $current_api_language);
?>
