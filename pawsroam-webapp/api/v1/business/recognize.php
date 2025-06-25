<?php
/**
 * API Endpoint for Recognizing a Business
 * Method: POST
 * Expected FormData: business_id (int, required), [recognition_type (string)], [comment (string)], csrf_token
 */

// Bootstrap
if (session_status() == PHP_SESSION_NONE) {
    session_start(['cookie_httponly' => true, 'cookie_secure' => isset($_SERVER['HTTPS']), 'cookie_samesite' => 'Lax']);
}

if (!defined('BASE_PATH')) { define('BASE_PATH', dirname(__DIR__, 4)); }
if (!defined('DS')) { define('DS', DIRECTORY_SEPARATOR); }

$required_files = [
    BASE_PATH . DS . 'config' . DS . 'constants.php',
    BASE_PATH . DS . 'config' . DS . 'database.php',
    BASE_PATH . DS . 'includes' . DS . 'functions.php',
    BASE_PATH . DS . 'includes' . DS . 'translation.php',
    BASE_PATH . DS . 'includes' . DS . 'auth.php'
];
foreach ($required_files as $file) {
    if (file_exists($file)) { require_once $file; }
    else {
        http_response_code(500); header('Content-Type: application/json');
        error_log("CRITICAL: Recognize Business API failed to load core file: " . $file);
        echo json_encode(['success' => false, 'message' => 'Server configuration error.']); exit;
    }
}

$current_api_language = $GLOBALS['current_language'] ?? DEFAULT_LANGUAGE ?? 'en';
header('Content-Type: application/json');

// --- Access Control & Request Method Validation ---
require_login();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => __('error_method_not_allowed', [], $current_api_language)]);
    exit;
}

// --- CSRF Token Validation ---
if (!validate_csrf_token($_POST[CSRF_TOKEN_NAME ?? 'csrf_token'] ?? null)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => __('error_csrf_token_invalid', [], $current_api_language)]);
    exit;
}

// --- Input Collection & Validation ---
$user_id = current_user_id();
$business_id = filter_var($_POST['business_id'] ?? null, FILTER_VALIDATE_INT);
$recognition_type = trim($_POST['recognition_type'] ?? 'general'); // Default to 'general'
$comment = trim($_POST['comment'] ?? null);

$errors = [];

if (!$business_id || $business_id <= 0) {
    $errors['business_id'] = __('error_invalid_business_id_provided', [], $current_api_language); // "Invalid or missing business ID."
} else {
    // Check if business exists and is active
    try {
        $db_check = Database::getInstance()->getConnection();
        $stmt_check = $db_check->prepare("SELECT id FROM businesses WHERE id = :id AND status = 'active' LIMIT 1");
        $stmt_check->bindParam(':id', $business_id, PDO::PARAM_INT);
        $stmt_check->execute();
        if (!$stmt_check->fetch()) {
            $errors['business_id'] = __('error_business_id_not_found_or_inactive', [], $current_api_language); // "Business not found or is not active."
        }
    } catch (PDOException $e) {
        error_log("Recognize API: DB error checking business existence: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => __('error_server_generic', [], $current_api_language)]);
        exit;
    }
}

// Validate recognition_type if specific types are enforced later
if (empty($recognition_type)) { // Should not happen due to default, but good check
    $errors['recognition_type'] = __('error_recognition_type_required', [], $current_api_language); // "Recognition type is required."
} elseif (strlen($recognition_type) > 50) {
     $errors['recognition_type'] = __('error_recognition_type_too_long', [], $current_api_language); // "Recognition type is too long."
}

if ($comment !== null && strlen($comment) > 1000) { // Max comment length
    $errors['comment'] = __('error_comment_too_long', [], $current_api_language); // "Comment exceeds maximum length of 1000 characters."
}

if (!empty($errors)) {
    http_response_code(422); // Unprocessable Entity
    echo json_encode(['success' => false, 'message' => __('error_validation_failed', [], $current_api_language), 'errors' => $errors]);
    exit;
}

// --- Process Recognition ---
$db = Database::getInstance()->getConnection();
try {
    $db->beginTransaction();

    // 1. Check if user already recognized this business (for this type, if types become distinct later)
    // For now, the UNIQUE KEY (user_id, business_id) handles the 'general' type implicitly.
    // If recognition_type becomes a factor in uniqueness, the unique key and this query need adjustment.
    $stmt_check_existing = $db->prepare(
        "SELECT id FROM business_recognitions WHERE user_id = :user_id AND business_id = :business_id LIMIT 1"
        // If types matter for uniqueness: "SELECT id FROM business_recognitions WHERE user_id = :user_id AND business_id = :business_id AND recognition_type = :recognition_type LIMIT 1"
    );
    $stmt_check_existing->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt_check_existing->bindParam(':business_id', $business_id, PDO::PARAM_INT);
    // $stmt_check_existing->bindParam(':recognition_type', $recognition_type); // If type matters
    $stmt_check_existing->execute();

    if ($stmt_check_existing->fetch()) {
        $db->rollBack(); // Not strictly necessary as no writes yet, but good practice.
        http_response_code(409); // Conflict
        echo json_encode(['success' => false, 'message' => __('error_business_already_recognized', [], $current_api_language)]); // "You have already recognized this business."
        exit;
    }

    // 2. Insert new recognition
    $stmt_insert = $db->prepare(
        "INSERT INTO business_recognitions (user_id, business_id, recognition_type, comment, created_at)
         VALUES (:user_id, :business_id, :recognition_type, :comment, NOW())"
    );
    $stmt_insert->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt_insert->bindParam(':business_id', $business_id, PDO::PARAM_INT);
    $stmt_insert->bindParam(':recognition_type', $recognition_type);
    $stmt_insert->bindParam(':comment', $comment); // PDO handles NULL
    $stmt_insert->execute();
    $recognition_id = $db->lastInsertId();

    // 3. Increment total_recognitions in businesses table
    $stmt_update_biz = $db->prepare(
        "UPDATE businesses SET total_recognitions = total_recognitions + 1 WHERE id = :business_id"
    );
    $stmt_update_biz->bindParam(':business_id', $business_id, PDO::PARAM_INT);
    $stmt_update_biz->execute();

    $db->commit();

    // Fetch new total recognitions to return (optional, but good for UI update)
    $stmt_new_total = $db->prepare("SELECT total_recognitions FROM businesses WHERE id = :business_id");
    $stmt_new_total->bindParam(':business_id', $business_id, PDO::PARAM_INT);
    $stmt_new_total->execute();
    $new_total_recognitions = $stmt_new_total->fetchColumn();

    http_response_code(201); // Created (or 200 OK if preferred for this action)
    echo json_encode([
        'success' => true,
        'message' => __('success_business_recognized', [], $current_api_language), // "Thank you for recognizing this business!"
        'recognition_id' => $recognition_id,
        'business_id' => $business_id,
        'total_recognitions' => (int)$new_total_recognitions
    ]);

} catch (PDOException $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    // Check for unique constraint violation (specific error code depends on DB, e.g., 23000 for MySQL)
    if ($e->getCode() == '23000') { // Integrity constraint violation
        http_response_code(409); // Conflict
        error_log("Recognize API (PDOException - Unique Constraint): " . $e->getMessage() . " for User {$user_id}, Business {$business_id}.");
        echo json_encode(['success' => false, 'message' => __('error_business_already_recognized_concurrent', [], $current_api_language)]); // "It seems you've just recognized this business. Thanks!"
    } else {
        error_log("Recognize API (PDOException): " . $e->getMessage() . " for User {$user_id}, Business {$business_id}.");
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => __('error_server_generic', [], $current_api_language)]);
    }
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log("Recognize API (Exception): " . $e->getMessage() . " for User {$user_id}, Business {$business_id}.");
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => __('error_server_generic', [], $current_api_language)]);
}

exit;

<?php
// Translation string placeholders
// __('error_invalid_business_id_provided', [], $current_api_language);
// __('error_business_id_not_found_or_inactive', [], $current_api_language);
// __('error_recognition_type_required', [], $current_api_language);
// __('error_recognition_type_too_long', [], $current_api_language);
// __('error_comment_too_long', [], $current_api_language);
// __('error_business_already_recognized', [], $current_api_language);
// __('error_business_already_recognized_concurrent', [], $current_api_language);
// __('success_business_recognized', [], $current_api_language);
?>
