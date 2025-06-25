<?php
require_once __DIR__ . '/../../../../includes/init.php';
require_once __DIR__ . '/../../../../includes/auth.php';
require_login_api();

header('Content-Type: application/json');
$response = ['success' => false, 'message' => __('error_server_generic', [], $current_language)];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { // Using POST for delete as it changes state, and CSRF is good
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

$post_id = filter_input(INPUT_POST, 'post_id', FILTER_VALIDATE_INT);
$current_user_id = current_user_id();

if (!$post_id) {
    $response['message'] = __('error_invalid_post_id_for_delete_api', [], $current_language);
    http_response_code(422);
    echo json_encode($response);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
    $db->beginTransaction();

    // Fetch post details to check ownership and if it's the first post of a topic
    $stmt_post_check = $db->prepare("
        SELECT fp.user_id, fp.topic_id, ft.first_post_id, ft.is_locked
        FROM forum_posts fp
        JOIN forum_topics ft ON fp.topic_id = ft.id
        WHERE fp.id = :post_id AND fp.deleted_at IS NULL
    ");
    $stmt_post_check->bindParam(':post_id', $post_id, PDO::PARAM_INT);
    $stmt_post_check->execute();
    $post_data = $stmt_post_check->fetch(PDO::FETCH_ASSOC);

    if (!$post_data) {
        $response['message'] = __('error_post_not_found_or_already_deleted_api', [], $current_language);
        http_response_code(404);
        $db->rollBack();
        echo json_encode($response);
        exit;
    }

    // Ownership check (admins/mods might bypass this in future)
    if ($post_data['user_id'] !== $current_user_id /* && !has_role(['admin', 'moderator']) */) {
        $response['message'] = __('error_post_delete_not_owner_api', [], $current_language);
        http_response_code(403);
        $db->rollBack();
        echo json_encode($response);
        exit;
    }

    // Check if topic is locked (users shouldn't delete posts in locked topics, mods might)
    if ($post_data['is_locked'] /* && !has_role(['admin', 'moderator']) */) {
        $response['message'] = __('error_post_delete_topic_locked_api', [], $current_language);
        http_response_code(403);
        $db->rollBack();
        echo json_encode($response);
        exit;
    }


    $is_first_post = ($post_data['first_post_id'] == $post_id);
    $topic_id_affected = $post_data['topic_id'];

    // Soft delete the post
    $stmt_delete_post = $db->prepare("UPDATE forum_posts SET deleted_at = CURRENT_TIMESTAMP, deleted_by_user_id = :user_id WHERE id = :post_id");
    $stmt_delete_post->bindParam(':user_id', $current_user_id, PDO::PARAM_INT);
    $stmt_delete_post->bindParam(':post_id', $post_id, PDO::PARAM_INT);
    $stmt_delete_post->execute();

    if ($stmt_delete_post->rowCount() === 0) {
        // Should not happen if post_data was fetched, but as a safeguard
        $response['message'] = __('error_post_delete_failed_db', [], $current_language);
        $db->rollBack();
        echo json_encode($response);
        exit;
    }

    $response['post_deleted'] = true;
    $response['is_first_post'] = $is_first_post; // Inform frontend if the whole topic might disappear

    if ($is_first_post) {
        // If it's the first post, soft delete the entire topic
        $stmt_delete_topic = $db->prepare("UPDATE forum_topics SET deleted_at = CURRENT_TIMESTAMP, deleted_by_user_id = :user_id WHERE id = :topic_id");
        $stmt_delete_topic->bindParam(':user_id', $current_user_id, PDO::PARAM_INT);
        $stmt_delete_topic->bindParam(':topic_id', $topic_id_affected, PDO::PARAM_INT);
        $stmt_delete_topic->execute();
        $response['topic_deleted'] = true;
        $response['message'] = __('success_topic_and_first_post_deleted', [], $current_language);
    } else {
        // If not the first post, update topic's post_count and potentially last_post_id
        $stmt_update_topic_meta = $db->prepare("
            UPDATE forum_topics
            SET post_count = (SELECT COUNT(*) FROM forum_posts WHERE topic_id = :topic_id AND deleted_at IS NULL),
                last_post_id = (SELECT id FROM forum_posts WHERE topic_id = :topic_id AND deleted_at IS NULL ORDER BY created_at DESC LIMIT 1),
                updated_at = CURRENT_TIMESTAMP
            WHERE id = :topic_id
        ");
        $stmt_update_topic_meta->bindParam(':topic_id', $topic_id_affected, PDO::PARAM_INT);
        $stmt_update_topic_meta->execute();
        $response['message'] = __('success_post_deleted', [], $current_language);
    }

    $db->commit();
    $response['success'] = true;

} catch (PDOException $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log("PDOException in forums/posts/delete.php: " . $e->getMessage());
    $response['message'] = __('error_post_delete_failed_db', [], $current_language);
    http_response_code(500);
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log("Exception in forums/posts/delete.php: " . $e->getMessage());
    $response['message'] = __('error_server_generic', [], $current_language);
    http_response_code(500);
}

echo json_encode($response);
