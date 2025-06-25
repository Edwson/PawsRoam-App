<?php
/**
 * API Endpoint for Updating a Forum Post
 * Method: POST
 * Expected FormData: post_id, content, csrf_token
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
    else { http_response_code(500); header('Content-Type: application/json'); error_log("CRITICAL: Update Post API missing $file"); echo json_encode(['success' => false, 'message' => 'Server config error.']); exit; }
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

// --- Input Collection & Validation ---
$user_id = current_user_id();
$post_id = filter_var($_POST['post_id'] ?? null, FILTER_VALIDATE_INT);
$content = trim($_POST['content'] ?? '');
$errors = [];
$post_data = null;

if (!$post_id) {
    $errors['post_id'] = __('error_forum_invalid_post_id_for_edit_api', [], $current_api_language); // "Invalid post ID for update."
} else {
    // Fetch post to verify ownership or admin role
    try {
        $db_check = Database::getInstance()->getConnection();
        $stmt_post_check = $db_check->prepare("SELECT user_id, topic_id FROM forum_posts WHERE id = :post_id AND deleted_at IS NULL");
        $stmt_post_check->bindParam(':post_id', $post_id, PDO::PARAM_INT);
        $stmt_post_check->execute();
        $post_data = $stmt_post_check->fetch(PDO::FETCH_ASSOC);

        if (!$post_data) {
            $errors['post_id'] = __('error_forum_post_not_found_or_deleted_api', [], $current_api_language); // "Post not found or has been deleted."
        } elseif ($post_data['user_id'] !== $user_id && !has_role(['super_admin', 'moderator'])) {
            http_response_code(403); // Forbidden
            error_log("Update Post API: User {$user_id} attempted to edit post ID {$post_id} owned by user {$post_data['user_id']}.");
            echo json_encode(['success' => false, 'message' => __('error_forum_post_edit_unauthorized_api', [], $current_api_language)]); // "You are not authorized to edit this post."
            exit;
        }
    } catch (PDOException $e) { /* Handled by general try-catch */ throw $e; }
}

if (empty($content)) {
    $errors['content'] = __('error_forum_post_content_required', [], $current_api_language); // Reused: "Content is required."
} elseif (strlen($content) < 5) { // Min length for a post/reply
    $errors['content'] = __('error_forum_reply_content_too_short %d', [], $current_api_language, ['min' => 5]); // Reused
} elseif (strlen($content) > 20000) {
    $errors['content'] = __('error_forum_reply_content_too_long %d', [], $current_api_language, ['max' => 20000]); // Reused
}
// TODO: Basic profanity check for content

if (!empty($errors)) {
    http_response_code(422); // Unprocessable Entity
    echo json_encode(['success' => false, 'message' => __('error_validation_failed', [], $current_api_language), 'errors' => $errors]);
    exit;
}

// --- Database Update ---
$db = Database::getInstance()->getConnection();
try {
    $db->beginTransaction();

    // 1. Update the forum_posts table
    $stmt_update_post = $db->prepare(
        "UPDATE forum_posts SET content = :content, updated_at = NOW()
         WHERE id = :post_id"
         // Add AND user_id = :user_id if not allowing admin edits through this specific API path
    );
    $stmt_update_post->bindParam(':content', $content);
    $stmt_update_post->bindParam(':post_id', $post_id, PDO::PARAM_INT);
    // if ($post_data['user_id'] === $user_id) { // If owner is editing, bind their user_id for safety
    //     $stmt_update_post->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    // }
    $stmt_update_post->execute();

    if ($stmt_update_post->rowCount() === 0 && $post_data) { // Post existed but wasn't updated (e.g. content same or issue)
        // This might not be an error if content was identical. For simplicity, treat as success if no exception.
        // throw new Exception("Failed to update post record, or no changes made.");
    }

    // 2. Update the parent topic's updated_at and potentially content_preview if this is the first post
    // Also, if this post becomes the new last_post_id for the topic.
    // Check if this edited post is the current last_post_id for the topic
    $stmt_check_last = $db->prepare("SELECT last_post_id FROM forum_topics WHERE id = :topic_id");
    $stmt_check_last->bindParam(':topic_id', $post_data['topic_id'], PDO::PARAM_INT);
    $stmt_check_last->execute();
    $current_topic_last_post_id = $stmt_check_last->fetchColumn();

    $update_topic_fields = ["updated_at = NOW()"];
    if ($current_topic_last_post_id == $post_id) { // If this edited post IS the last post
        // Potentially update content_preview if this is also the *first* post of the topic and it's being edited.
        // This logic can get complex. For now, just update topic's updated_at.
        // If this is the *very first* post (i.e., post_count was 1), also update content_preview
        $stmt_first_post_check = $db->prepare("SELECT id FROM forum_posts WHERE topic_id = :topic_id ORDER BY created_at ASC LIMIT 1");
        $stmt_first_post_check->bindParam(':topic_id', $post_data['topic_id'], PDO::PARAM_INT);
        $stmt_first_post_check->execute();
        $first_post_in_topic = $stmt_first_post_check->fetchColumn();
        if ($first_post_in_topic == $post_id) {
            $new_content_preview = substr(strip_tags($content), 0, 200) . (strlen(strip_tags($content)) > 200 ? '...' : '');
            $update_topic_fields[] = "content_preview = :content_preview";
        }
    }
    // Always update topic's updated_at if a post within it is updated
    if (!empty($update_topic_fields)) {
        $stmt_update_topic_ts = $db->prepare(
            "UPDATE forum_topics SET " . implode(", ", $update_topic_fields) . " WHERE id = :topic_id"
        );
        $stmt_update_topic_ts->bindParam(':topic_id', $post_data['topic_id'], PDO::PARAM_INT);
        if (in_array("content_preview = :content_preview", $update_topic_fields)) {
            $stmt_update_topic_ts->bindParam(':content_preview', $new_content_preview);
        }
        $stmt_update_topic_ts->execute();
    }


    $db->commit();

    http_response_code(200); // OK
    echo json_encode([
        'success' => true,
        'message' => __('success_forum_post_updated', [], $current_api_language), // "Post updated successfully!"
        'post_id' => $post_id,
        'new_content_html' => nl2br(e($content)) // Send back processed content for UI update
    ]);

} catch (PDOException $e) {
    if ($db->inTransaction()) { $db->rollBack(); }
    error_log("Update Post API (PDOException): " . $e->getMessage());
    http_response_code(500); echo json_encode(['success' => false, 'message' => __('error_server_generic', [], $current_api_language)]);
} catch (Exception $e) {
    if ($db->inTransaction()) { $db->rollBack(); }
    error_log("Update Post API (Exception): " . $e->getMessage());
    http_response_code(500); echo json_encode(['success' => false, 'message' => __('error_server_generic', [], $current_api_language)]);
}
exit;

<?php
// Translation Placeholders
// __('error_forum_invalid_post_id_for_edit_api', [], $current_api_language);
// __('error_forum_post_not_found_or_deleted_api', [], $current_api_language);
// __('error_forum_post_edit_unauthorized_api', [], $current_api_language);
// __('success_forum_post_updated', [], $current_api_language);
// Reused: error_forum_post_content_required, error_forum_reply_content_too_short, error_forum_reply_content_too_long
?>
