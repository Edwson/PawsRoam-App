<?php
/**
 * API Endpoint for Creating a Business Review
 * Method: POST
 * Expected FormData: business_id, rating (1-5), [title], [comment], [review_photos_json_array_of_paths], csrf_token
 */

// Bootstrap
if (session_status() == PHP_SESSION_NONE) {
    session_start(['cookie_httponly' => true, 'cookie_secure' => isset($_SERVER['HTTPS']), 'cookie_samesite' => 'Lax']);
}
if (!defined('BASE_PATH')) { define('BASE_PATH', dirname(__DIR__, 4)); }
if (!defined('DS')) { define('DS', DIRECTORY_SEPARATOR); }

$required_files = [ /* ... core files ... */
    BASE_PATH . DS . 'config' . DS . 'constants.php', BASE_PATH . DS . 'config' . DS . 'database.php',
    BASE_PATH . DS . 'includes' . DS . 'functions.php', BASE_PATH . DS . 'includes' . DS . 'translation.php',
    BASE_PATH . DS . 'includes' . DS . 'auth.php'
];
foreach ($required_files as $file) {
    if (file_exists($file)) { require_once $file; }
    else { http_response_code(500); header('Content-Type: application/json'); error_log("CRITICAL: Create Review API missing $file"); echo json_encode(['success' => false, 'message' => 'Server config error.']); exit; }
}

$current_api_language = $GLOBALS['current_language'] ?? DEFAULT_LANGUAGE ?? 'en';
header('Content-Type: application/json');

// --- Access Control & Request Method ---
require_login();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); echo json_encode(['success' => false, 'message' => __('error_method_not_allowed', [], $current_api_language)]); exit;
}

// --- CSRF Token Validation ---
if (!validate_csrf_token($_POST[CSRF_TOKEN_NAME ?? 'csrf_token'] ?? null)) {
    http_response_code(403); echo json_encode(['success' => false, 'message' => __('error_csrf_token_invalid', [], $current_api_language)]); exit;
}

// --- Input Collection & Validation (STUB - basic validation for now) ---
$user_id = current_user_id();
$business_id = filter_var($_POST['business_id'] ?? null, FILTER_VALIDATE_INT);
$rating = filter_var($_POST['rating'] ?? null, FILTER_VALIDATE_INT);
$title = trim($_POST['title'] ?? null);
$comment = trim($_POST['comment'] ?? null);
// $review_photos_json = $_POST['review_photos_json'] ?? null; // Assuming client sends a JSON string of photo paths

$errors = [];

if (!$business_id || $business_id <= 0) { $errors['business_id'] = __('error_review_invalid_business_id', [], $current_api_language); } // "Invalid business ID for review."
else {
    // Check if business exists and is active
    try {
        $db_check_biz = Database::getInstance()->getConnection();
        $stmt_check_biz = $db_check_biz->prepare("SELECT id FROM businesses WHERE id = :id AND status = 'active' LIMIT 1");
        $stmt_check_biz->bindParam(':id', $business_id, PDO::PARAM_INT);
        $stmt_check_biz->execute();
        if (!$stmt_check_biz->fetch()) {
            $errors['business_id'] = __('error_review_business_not_found_or_inactive', [], $current_api_language); // "Cannot review: Business not found or inactive."
        }
    } catch (PDOException $e) { /* Handled by general try-catch later */ throw $e; }
}

if ($rating === null || !is_numeric($rating) || $rating < 1 || $rating > 5) { $errors['rating'] = __('error_review_invalid_rating', [], $current_api_language); }
if ($title !== null && (strlen($title) > 255)) { $errors['title'] = __('error_review_title_too_long', [], $current_api_language); }
// Comment: Allow empty comment if title is provided, or enforce minimum if both are empty?
// For now, if comment is not empty, check min length.
if (!empty($comment) && strlen($comment) < 10) { $errors['comment'] = __('error_review_comment_too_short_if_provided', [], $current_api_language); } // "If providing a comment, it must be at least 10 characters."
elseif (strlen($comment) > 5000) { $errors['comment'] = __('error_review_comment_too_long', [], $current_api_language); }
if (empty($title) && empty($comment)) {
    $errors['comment'] = __('error_review_title_or_comment_required', [], $current_api_language); // "Please provide a title or a comment for your review."
}


// TODO: Validate $review_photos_json if provided (is valid JSON array of strings/paths)
// For now, assume it's NULL or a pre-validated JSON string if sent by client.
$review_photos_for_db = null; // Placeholder

// Check if user already reviewed this business (only if no other errors yet)
if (empty($errors)) {
    try {
        $db_check_review = Database::getInstance()->getConnection();
        // Check for pending or approved reviews by this user for this business
        $stmt_check_review = $db_check_review->prepare("SELECT id FROM business_reviews WHERE user_id = :user_id AND business_id = :business_id AND (status = 'pending' OR status = 'approved') LIMIT 1");
        $stmt_check_review->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt_check_review->bindParam(':business_id', $business_id, PDO::PARAM_INT);
        $stmt_check_review->execute();
        if ($stmt_check_review->fetch()) {
            // Using a general error key, JS can display it in formMessages
            $errors['general'] = __('error_review_already_submitted', [], $current_api_language);
        }
    } catch (PDOException $e) { /* Handled by general try-catch later */ throw $e; }
}


if (!empty($errors)) {
    http_response_code(422); // Unprocessable Entity
    echo json_encode(['success' => false, 'message' => __('error_validation_failed', [], $current_api_language), 'errors' => $errors]);
    exit;
}

// --- Database Insertion ---
try {
    $db = Database::getInstance()->getConnection();
    $stmt_insert = $db->prepare(
        "INSERT INTO business_reviews (user_id, business_id, rating, title, comment, review_photos, status, created_at, updated_at)
         VALUES (:user_id, :business_id, :rating, :title, :comment, :review_photos, 'pending', NOW(), NOW())"
    );
    $stmt_insert->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt_insert->bindParam(':business_id', $business_id, PDO::PARAM_INT);
    $stmt_insert->bindParam(':rating', $rating, PDO::PARAM_INT);
    $stmt_insert->bindParam(':title', $title); // PDO handles NULL
    $stmt_insert->bindParam(':comment', $comment); // PDO handles NULL
    $stmt_insert->bindParam(':review_photos', $review_photos_for_db); // PDO handles NULL

    $stmt_insert->execute();
    $new_review_id = $db->lastInsertId();

    // Note: Updating business average rating & count should ideally happen when a review is APPROVED by an admin,
    // or via a scheduled task/trigger, not necessarily on initial pending submission.
    // For now, we skip this step.

    http_response_code(201); // Created
    echo json_encode([
        'success' => true,
        'message' => __('success_review_submitted_pending', [], $current_api_language),
        'review_id' => $new_review_id
    ]);

} catch (PDOException $e) {
    // Check for unique constraint violation (e.g., user already reviewed - race condition)
    if ($e->getCode() == '23000') { // MySQL integrity constraint violation
        error_log("Create Review API (PDOException - Unique Constraint): User {$user_id} already reviewed Business {$business_id}. " . $e->getMessage());
        http_response_code(409); // Conflict
        echo json_encode(['success' => false, 'message' => __('error_review_already_submitted', [], $current_api_language)]);
    } else {
        error_log("Create Review API (PDOException): " . $e->getMessage());
        http_response_code(500); echo json_encode(['success' => false, 'message' => __('error_server_generic', [], $current_api_language)]);
    }
} catch (Exception $e) {
    error_log("Create Review API (Exception): " . $e->getMessage());
    http_response_code(500); echo json_encode(['success' => false, 'message' => __('error_server_generic', [], $current_api_language)]);
} catch (Exception $e) {
    error_log("Create Review API (Exception): " . $e->getMessage());
    http_response_code(500); echo json_encode(['success' => false, 'message' => __('error_server_generic', [], $current_api_language)]);
}
exit;

<?php
// Translation placeholders
// __('error_review_invalid_business_id', [], $current_api_language);
// __('error_review_invalid_rating', [], $current_api_language);
// __('error_review_title_too_long', [], $current_api_language);
// __('error_review_comment_too_short', [], $current_api_language);
// __('error_review_comment_too_long', [], $current_api_language);
// __('error_review_already_submitted', [], $current_api_language);
// __('success_review_submitted_pending', [], $current_api_language);
?>
