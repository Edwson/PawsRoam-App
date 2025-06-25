<?php
/**
 * API Endpoint for Creating a New Forum Topic (STUB)
 * Method: POST
 * Expected FormData: category_id, title, content, csrf_token
 */

// Bootstrap
if (session_status() == PHP_SESSION_NONE) { session_start(/* ...options... */); }
if (!defined('BASE_PATH')) { define('BASE_PATH', dirname(__DIR__, 5)); } // Adjust depth
if (!defined('DS')) { define('DS', DIRECTORY_SEPARATOR); }

$required_files = [ /* ... core files ... */
    BASE_PATH . DS . 'config' . DS . 'constants.php', BASE_PATH . DS . 'config' . DS . 'database.php',
    BASE_PATH . DS . 'includes' . DS . 'functions.php', BASE_PATH . DS . 'includes' . DS . 'translation.php',
    BASE_PATH . DS . 'includes' . DS . 'auth.php'
];
foreach ($required_files as $file) {
    if (file_exists($file)) { require_once $file; }
    else { http_response_code(500); header('Content-Type: application/json'); error_log("CRITICAL: Create Topic API missing $file"); echo json_encode(['success' => false, 'message' => 'Server config error.']); exit; }
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

// --- STUBBED RESPONSE ---
// TODO: Implement full validation (category_id exists, title length, content length/rules)
// TODO: Implement slug generation for the new topic
// --- Input Collection ---
$user_id = current_user_id();
$category_id = filter_var($_POST['category_id'] ?? null, FILTER_VALIDATE_INT);
$title = trim($_POST['title'] ?? '');
$content = trim($_POST['content'] ?? ''); // Content for the first post

$errors = [];

// --- Input Validation ---
if (!$category_id) {
    $errors['category_id'] = __('error_forum_category_required', [], $current_api_language); // "Please select a category for your topic."
} else {
    // Check if category exists
    try {
        $db_check = Database::getInstance()->getConnection();
        $stmt_cat_check = $db_check->prepare("SELECT id FROM forum_categories WHERE id = :id");
        $stmt_cat_check->bindParam(':id', $category_id, PDO::PARAM_INT);
        $stmt_cat_check->execute();
        if (!$stmt_cat_check->fetch()) {
            $errors['category_id'] = __('error_forum_category_invalid', [], $current_api_language); // "The selected category is not valid."
        }
    } catch (PDOException $e) { /* Handled by general try-catch */ throw $e; }
}

if (empty($title)) {
    $errors['title'] = __('error_forum_topic_title_required', [], $current_api_language); // "Topic title is required."
} elseif (strlen($title) < 5) {
    $errors['title'] = __('error_forum_topic_title_too_short %d', [], $current_api_language, ['min' => 5]); // "Topic title must be at least %d characters."
} elseif (strlen($title) > 250) { // Max length slightly less than DB to allow for slug suffix if needed
    $errors['title'] = __('error_forum_topic_title_too_long %d', [], $current_api_language, ['max' => 250]); // "Topic title cannot exceed %d characters."
}
// TODO: Basic profanity check for title (simple str_ireplace for now, or more advanced later)

if (empty($content)) {
    $errors['content'] = __('error_forum_post_content_required', [], $current_api_language); // "The main content for your topic is required."
} elseif (strlen($content) < 10) {
    $errors['content'] = __('error_forum_post_content_too_short %d', [], $current_api_language, ['min' => 10]); // "Content must be at least %d characters."
} elseif (strlen($content) > 20000) { // Generous limit for a post
    $errors['content'] = __('error_forum_post_content_too_long %d', [], $current_api_language, ['max' => 20000]); // "Content cannot exceed %d characters."
}

if (!empty($errors)) {
    http_response_code(422); // Unprocessable Entity
    echo json_encode(['success' => false, 'message' => __('error_validation_failed', [], $current_api_language), 'errors' => $errors]);
    exit;
}

// --- Slug Generation ---
if (!function_exists('generate_slug')) { // Could be in functions.php
    function generate_slug($text, $table, $column = 'slug', $separator = '-') {
        $slug = strtolower($text);
        $slug = preg_replace('/[^a-z0-9]+/', $separator, $slug);
        $slug = trim($slug, $separator);
        if (empty($slug)) { $slug = 'topic'; } // Fallback for empty/special char only titles

        $db_slug = Database::getInstance()->getConnection();
        $original_slug = $slug;
        $counter = 1;
        while (true) {
            $stmt_slug = $db_slug->prepare("SELECT COUNT(*) FROM {$table} WHERE {$column} = :slug");
            $stmt_slug->bindParam(':slug', $slug);
            $stmt_slug->execute();
            if ((int)$stmt_slug->fetchColumn() === 0) {
                break;
            }
            $slug = $original_slug . $separator . $counter;
            $counter++;
        }
        return $slug;
    }
}
$topic_slug = generate_slug($title, 'forum_topics');
$content_preview = substr(strip_tags($content), 0, 200) . (strlen(strip_tags($content)) > 200 ? '...' : '');


// --- Database Operations (Transaction) ---
$db = Database::getInstance()->getConnection();
try {
    $db->beginTransaction();

    // 1. Insert into forum_topics
    $stmt_topic = $db->prepare(
        "INSERT INTO forum_topics (category_id, user_id, title, slug, content_preview, post_count, created_at, updated_at)
         VALUES (:category_id, :user_id, :title, :slug, :content_preview, 1, NOW(), NOW())"
    );
    $stmt_topic->bindParam(':category_id', $category_id, PDO::PARAM_INT);
    $stmt_topic->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt_topic->bindParam(':title', $title);
    $stmt_topic->bindParam(':slug', $topic_slug);
    $stmt_topic->bindParam(':content_preview', $content_preview);
    $stmt_topic->execute();
    $new_topic_id = $db->lastInsertId();

    if (!$new_topic_id) throw new Exception("Failed to create topic record.");

    // 2. Insert initial post into forum_posts
    $stmt_post = $db->prepare(
        "INSERT INTO forum_posts (topic_id, user_id, content, created_at, updated_at)
         VALUES (:topic_id, :user_id, :content, NOW(), NOW())"
    );
    $stmt_post->bindParam(':topic_id', $new_topic_id, PDO::PARAM_INT);
    $stmt_post->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt_post->bindParam(':content', $content);
    $stmt_post->execute();
    $new_post_id = $db->lastInsertId();

    if (!$new_post_id) throw new Exception("Failed to create initial post record.");

    // 3. Update forum_topics with last_post_id
    $stmt_update_topic = $db->prepare("UPDATE forum_topics SET last_post_id = :last_post_id WHERE id = :topic_id");
    $stmt_update_topic->bindParam(':last_post_id', $new_post_id, PDO::PARAM_INT);
    $stmt_update_topic->bindParam(':topic_id', $new_topic_id, PDO::PARAM_INT);
    $stmt_update_topic->execute();

    // TODO: Update category topic/post counts (can be complex, or via triggers/scheduled tasks)
    // For now, topic.post_count is initialized to 1.

    $db->commit();

    http_response_code(201); // Created
    echo json_encode([
        'success' => true,
        'message' => __('success_forum_topic_created', [], $current_api_language), // "Topic created successfully!"
        'topic_id' => (int)$new_topic_id,
        'topic_slug' => $topic_slug,
        'post_id' => (int)$new_post_id
    ]);

} catch (PDOException $e) {
    if ($db->inTransaction()) { $db->rollBack(); }
    error_log("Create Topic API (PDOException): " . $e->getMessage());
    http_response_code(500); echo json_encode(['success' => false, 'message' => __('error_server_generic', [], $current_api_language)]);
} catch (Exception $e) {
    if ($db->inTransaction()) { $db->rollBack(); }
    error_log("Create Topic API (Exception): " . $e->getMessage());
    http_response_code(500); echo json_encode(['success' => false, 'message' => __('error_server_generic', [], $current_api_language)]);
}
exit;

<?php
// Translation placeholders
// __('error_forum_category_required', [], $current_api_language);
// __('error_forum_category_invalid', [], $current_api_language);
// __('error_forum_topic_title_required', [], $current_api_language);
// __('error_forum_topic_title_too_short %d', [], $current_api_language);
// __('error_forum_topic_title_too_long %d', [], $current_api_language);
// __('error_forum_post_content_required', [], $current_api_language);
// __('error_forum_post_content_too_short %d', [], $current_api_language);
// __('error_forum_post_content_too_long %d', [], $current_api_language);
// __('success_forum_topic_created', [], $current_api_language);
// Translation placeholders
// __('error_forum_feature_not_implemented_yet', [], $current_api_language);
?>
