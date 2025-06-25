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
else { /* TODO: Check if business exists and is active */ }

if ($rating === null || $rating < 1 || $rating > 5) { $errors['rating'] = __('error_review_invalid_rating', [], $current_api_language); } // "Rating must be between 1 and 5."
if ($title !== null && (strlen($title) > 255)) { $errors['title'] = __('error_review_title_too_long', [], $current_api_language); } // "Review title is too long."
if ($comment !== null && strlen($comment) < 10 && !empty($comment)) { $errors['comment'] = __('error_review_comment_too_short', [], $current_api_language); } // "Comment is too short (min 10 chars)."
if ($comment !== null && strlen($comment) > 5000) { $errors['comment'] = __('error_review_comment_too_long', [], $current_api_language); } // "Comment is too long (max 5000 chars)."

// TODO: Validate $review_photos_json if provided (is valid JSON array of strings/paths)

// Check if user already reviewed this business
try {
    $db_check = Database::getInstance()->getConnection();
    $stmt_check = $db_check->prepare("SELECT id FROM business_reviews WHERE user_id = :user_id AND business_id = :business_id LIMIT 1");
    $stmt_check->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt_check->bindParam(':business_id', $business_id, PDO::PARAM_INT);
    $stmt_check->execute();
    if ($stmt_check->fetch()) {
        $errors['general'] = __('error_review_already_submitted', [], $current_api_language); // "You have already submitted a review for this business."
    }
} catch (PDOException $e) { /* Handled by general try-catch later */ throw $e; }


if (!empty($errors)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => __('error_validation_failed', [], $current_api_language), 'errors' => $errors]);
    exit;
}

// --- STUB: Database Insertion ---
try {
    $db = Database::getInstance()->getConnection();
    // For this stub, we'll just pretend it was inserted.
    // Actual insert:
    /*
    $stmt_insert = $db->prepare(
        "INSERT INTO business_reviews (user_id, business_id, rating, title, comment, review_photos, status, created_at, updated_at)
         VALUES (:user_id, :business_id, :rating, :title, :comment, :review_photos, 'pending', NOW(), NOW())"
    );
    $stmt_insert->bindParam(':user_id', $user_id);
    $stmt_insert->bindParam(':business_id', $business_id);
    $stmt_insert->bindParam(':rating', $rating);
    $stmt_insert->bindParam(':title', $title);
    $stmt_insert->bindParam(':comment', $comment);
    $stmt_insert->bindParam(':review_photos', $review_photos_json); // Assuming it's a JSON string
    $stmt_insert->execute();
    $new_review_id = $db->lastInsertId();
    */
    $new_review_id = rand(100, 999); // Dummy ID for stub

    // STUB for future: Update businesses.average_review_rating and businesses.total_review_count
    // This would ideally be in a transaction with the review insert.

    http_response_code(201); // Created
    echo json_encode([
        'success' => true,
        'message' => __('success_review_submitted_pending', [], $current_api_language), // "Your review has been submitted and is pending approval. Thank you!"
        'review_id' => $new_review_id
    ]);

} catch (PDOException $e) {
    error_log("Create Review API (PDOException): " . $e->getMessage());
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
