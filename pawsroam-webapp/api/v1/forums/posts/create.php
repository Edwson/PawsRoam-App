<?php
/**
 * API Endpoint for Creating a New Forum Post/Reply (STUB)
 * Method: POST
 * Expected FormData: topic_id, content, [parent_post_id], csrf_token
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
    else { http_response_code(500); header('Content-Type: application/json'); error_log("CRITICAL: Create Post API missing $file"); echo json_encode(['success' => false, 'message' => 'Server config error.']); exit; }
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
// TODO: Implement full validation (topic_id exists and is not locked, content length/rules, parent_post_id exists within same topic if provided)
// --- Input Collection ---
$user_id = current_user_id();
$topic_id = filter_var($_POST['topic_id'] ?? null, FILTER_VALIDATE_INT);
$content = trim($_POST['content'] ?? '');
// $parent_post_id = filter_var($_POST['parent_post_id'] ?? null, FILTER_VALIDATE_INT); // For threaded replies later

$errors = [];

// --- Input Validation ---
if (!$topic_id) {
    $errors['topic_id'] = __('error_forum_invalid_topic_id_for_reply', [], $current_api_language); // "Invalid topic ID for reply."
} else {
    // Check if topic exists and is not locked
    try {
        $db_check = Database::getInstance()->getConnection();
        $stmt_topic_check = $db_check->prepare("SELECT id, is_locked FROM forum_topics WHERE id = :id");
        $stmt_topic_check->bindParam(':id', $topic_id, PDO::PARAM_INT);
        $stmt_topic_check->execute();
        $topic_data = $stmt_topic_check->fetch(PDO::FETCH_ASSOC);
        if (!$topic_data) {
            $errors['topic_id'] = __('error_forum_topic_not_found_for_reply', [], $current_api_language); // "Topic not found for reply."
        } elseif ($topic_data['is_locked']) {
            $errors['topic_id'] = __('error_forum_topic_locked_cannot_reply_api', [], $current_api_language); // "Cannot reply: This topic is locked."
        }
    } catch (PDOException $e) { /* Handled by general try-catch */ throw $e; }
}

if (empty($content)) {
    $errors['content'] = __('error_forum_reply_content_required', [], $current_api_language); // "Reply content is required."
} elseif (strlen($content) < 5) { // Shorter min length for replies typically
    $errors['content'] = __('error_forum_reply_content_too_short %d', [], $current_api_language, ['min' => 5]); // "Reply must be at least %d characters."
} elseif (strlen($content) > 10000) { // Generous limit
    $errors['content'] = __('error_forum_reply_content_too_long %d', [], $current_api_language, ['max' => 10000]); // "Reply cannot exceed %d characters."
}
// TODO: Basic profanity check for content

// TODO: If $parent_post_id is provided, validate it exists in the same $topic_id.

if (!empty($errors)) {
    http_response_code(422); // Unprocessable Entity
    echo json_encode(['success' => false, 'message' => __('error_validation_failed', [], $current_api_language), 'errors' => $errors]);
    exit;
}

// --- Database Operations (Transaction) ---
$db = Database::getInstance()->getConnection();
try {
    $db->beginTransaction();

    // 1. Insert new post into forum_posts
    $stmt_post = $db->prepare(
        "INSERT INTO forum_posts (topic_id, user_id, content, created_at, updated_at)
         VALUES (:topic_id, :user_id, :content, NOW(), NOW())"
        // Add parent_post_id here if implementing threaded replies
    );
    $stmt_post->bindParam(':topic_id', $topic_id, PDO::PARAM_INT);
    $stmt_post->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt_post->bindParam(':content', $content);
    // $stmt_post->bindParam(':parent_post_id', $parent_post_id); // For threaded
    $stmt_post->execute();
    $new_post_id = $db->lastInsertId();

    if (!$new_post_id) {
        throw new Exception("Failed to create new post record.");
    }

    // 2. Update forum_topics: last_post_id, post_count, updated_at
    $stmt_update_topic = $db->prepare(
        "UPDATE forum_topics
         SET last_post_id = :last_post_id,
             post_count = post_count + 1,
             updated_at = NOW()
         WHERE id = :topic_id"
    );
    $stmt_update_topic->bindParam(':last_post_id', $new_post_id, PDO::PARAM_INT);
    $stmt_update_topic->bindParam(':topic_id', $topic_id, PDO::PARAM_INT);
    $stmt_update_topic->execute();

    // TODO: Update category post counts / last activity (more complex, can be deferred or handled by triggers)

    $db->commit();

    // Fetch the newly created post with user details for the response
    $stmt_get_post = $db->prepare(
        "SELECT fp.*, u.username as author_username, u.avatar_path as author_avatar_path
         FROM forum_posts fp
         JOIN users u ON fp.user_id = u.id
         WHERE fp.id = :post_id"
    );
    $stmt_get_post->bindParam(':post_id', $new_post_id, PDO::PARAM_INT);
    $stmt_get_post->execute();
    $created_post_data = $stmt_get_post->fetch(PDO::FETCH_ASSOC);

    $author_avatar_url = base_url('/assets/images/placeholders/avatar_placeholder_50.png');
    if (!empty($created_post_data['author_avatar_path']) && defined('UPLOADS_BASE_URL')) {
        $author_avatar_url = rtrim(UPLOADS_BASE_URL, '/') . '/' . ltrim($created_post_data['author_avatar_path'], '/');
    }

    http_response_code(201); // Created
    echo json_encode([
        'success' => true,
        'message' => __('success_forum_reply_posted', [], $current_api_language), // "Reply posted successfully!"
        'post' => [
            'id' => (int)$created_post_data['id'],
            'topic_id' => (int)$created_post_data['topic_id'],
            'user_id' => (int)$created_post_data['user_id'],
            'author_username' => e($created_post_data['author_username']),
            'author_avatar_url' => e($author_avatar_url),
            'content' => nl2br(e($created_post_data['content'])), // Prepare for HTML display
            'created_at_formatted' => date("M j, Y, g:i A", strtotime($created_post_data['created_at'])),
            'created_at_iso' => date(DateTime::ATOM, strtotime($created_post_data['created_at']))
        ]
    ]);

} catch (PDOException $e) {
    if ($db->inTransaction()) { $db->rollBack(); }
    error_log("Create Post API (PDOException): " . $e->getMessage());
    http_response_code(500); echo json_encode(['success' => false, 'message' => __('error_server_generic', [], $current_api_language)]);
} catch (Exception $e) {
    if ($db->inTransaction()) { $db->rollBack(); }
    error_log("Create Post API (Exception): " . $e->getMessage());
    http_response_code(500); echo json_encode(['success' => false, 'message' => __('error_server_generic', [], $current_api_language)]);
}
exit;

<?php
// Translation placeholders
// __('error_forum_invalid_topic_id_for_reply', [], $current_api_language);
// __('error_forum_topic_not_found_for_reply', [], $current_api_language);
// __('error_forum_topic_locked_cannot_reply_api', [], $current_api_language);
// __('error_forum_reply_content_required', [], $current_api_language);
// __('error_forum_reply_content_too_short %d', [], $current_api_language);
// __('error_forum_reply_content_too_long %d', [], $current_api_language);
// __('success_forum_reply_posted', [], $current_api_language);
?>
