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

// --- STUB: Database Update ---
try {
    $db = Database::getInstance()->getConnection();
    // For this stub, we'll just pretend it was updated.
    // Actual update:
    /*
    $stmt_update = $db->prepare("UPDATE business_reviews SET status = :new_status, updated_at = NOW() WHERE id = :review_id");
    $stmt_update->bindParam(':new_status', $new_status);
    $stmt_update->bindParam(':review_id', $review_id, PDO::PARAM_INT);
    $stmt_update->execute();

    if ($stmt_update->rowCount() > 0) {
        // STUB for future: If status changed to 'approved' or from 'approved' to something else,
        // trigger recalculation of businesses.average_review_rating and businesses.total_review_count.
        // This could involve fetching the business_id for this review_id first.
        // Example: update_business_review_aggregates($business_id_of_this_review);

        http_response_code(200); // OK
        echo json_encode(['success' => true, 'message' => sprintf(__('success_admin_review_status_updated %s %s', [], $current_api_language), $review_id, $new_status)]); // "Review ID %s status updated to %s."
    } else {
        // Review not found or status was already the same
        http_response_code(404); // Or 304 Not Modified if status was same
        echo json_encode(['success' => false, 'message' => __('error_admin_review_not_found_or_no_change', [], $current_api_language)]); // "Review not found or status already set."
    }
    */

    // Dummy success for stub:
    http_response_code(200);
    echo json_encode(['success' => true, 'message' => sprintf(__('success_admin_review_status_updated %s %s', [], $current_api_language), $review_id, $new_status) . " (Stubbed)"]);


} catch (PDOException $e) {
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
