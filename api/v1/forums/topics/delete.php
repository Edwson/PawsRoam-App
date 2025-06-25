<?php
require_once __DIR__ . '/../../../../includes/init.php';
require_once __DIR__ . '/../../../../includes/auth.php';
require_login_api();

header('Content-Type: application/json');
$response = ['success' => false, 'message' => __('error_server_generic', [], $current_language)];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
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

$topic_id = filter_input(INPUT_POST, 'topic_id', FILTER_VALIDATE_INT);
$current_user_id = current_user_id();

if (!$topic_id) {
    $response['message'] = __('error_invalid_topic_id_for_delete_api', [], $current_language);
    http_response_code(422);
    echo json_encode($response);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
    $db->beginTransaction();

    // Fetch topic details to check ownership
    $stmt_topic_check = $db->prepare("
        SELECT user_id, category_id, is_locked
        FROM forum_topics
        WHERE id = :topic_id AND deleted_at IS NULL
    ");
    $stmt_topic_check->bindParam(':topic_id', $topic_id, PDO::PARAM_INT);
    $stmt_topic_check->execute();
    $topic_data = $stmt_topic_check->fetch(PDO::FETCH_ASSOC);

    if (!$topic_data) {
        $response['message'] = __('error_topic_not_found_or_already_deleted_api', [], $current_language);
        http_response_code(404);
        $db->rollBack();
        echo json_encode($response);
        exit;
    }

    // Ownership check (admins/mods will bypass this in a future enhancement)
    // For now, only topic owner can delete.
    if ($topic_data['user_id'] !== $current_user_id /* && !has_role(['admin', 'moderator']) */) {
        $response['message'] = __('error_topic_delete_not_owner_api', [], $current_language);
        http_response_code(403);
        $db->rollBack();
        echo json_encode($response);
        exit;
    }

    // Optional: Check if topic is locked by an admin.
    // Regular users might not be able to delete a topic if an admin has specifically locked it for other reasons.
    // if ($topic_data['is_locked'] && !has_role(['admin', 'moderator']) && $topic_data['locked_by_user_id'] !== $current_user_id) {
    //     $response['message'] = __('error_topic_delete_locked_by_admin_api', [], $current_language);
    //     http_response_code(403);
    //     $db->rollBack();
    //     echo json_encode($response);
    //     exit;
    // }


    // Soft delete the topic
    $stmt_delete_topic = $db->prepare("
        UPDATE forum_topics
        SET deleted_at = CURRENT_TIMESTAMP, deleted_by_user_id = :user_id
        WHERE id = :topic_id
    ");
    $stmt_delete_topic->bindParam(':user_id', $current_user_id, PDO::PARAM_INT);
    $stmt_delete_topic->bindParam(':topic_id', $topic_id, PDO::PARAM_INT);
    $stmt_delete_topic->execute();

    if ($stmt_delete_topic->rowCount() > 0) {
        // Optional: Soft delete all posts within this topic as well.
        // This makes recovery cleaner if a topic is undeleted.
        // However, it could be slow for topics with many posts.
        // For now, just deleting the topic is enough to hide it.
        // If implemented:
        // $stmt_delete_posts = $db->prepare("UPDATE forum_posts SET deleted_at = CURRENT_TIMESTAMP, deleted_by_user_id = :user_id WHERE topic_id = :topic_id AND deleted_at IS NULL");
        // $stmt_delete_posts->bindParam(':user_id', $current_user_id, PDO::PARAM_INT);
        // $stmt_delete_posts->bindParam(':topic_id', $topic_id, PDO::PARAM_INT);
        // $stmt_delete_posts->execute();
        // $response['posts_deleted_count'] = $stmt_delete_posts->rowCount();

        // Update category metadata (topic_count, post_count, last_topic_id)
        // This is complex as it requires recalculating based on non-deleted topics/posts.
        // For simplicity, we might run a separate maintenance script for this, or update on category view.
        // For now, we'll skip direct update here to avoid performance hit on delete.
        // A more robust solution would use triggers or a queue system for these updates.

        $db->commit();
        $response['success'] = true;
        $response['message'] = __('success_topic_deleted', [], $current_language);
        $response['category_id'] = $topic_data['category_id']; // For potential redirect
    } else {
        $db->rollBack();
        $response['message'] = __('error_topic_delete_failed_db', [], $current_language);
        // This could happen if the topic was deleted between the check and the update.
    }

} catch (PDOException $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log("PDOException in forums/topics/delete.php: " . $e->getMessage());
    $response['message'] = __('error_topic_delete_failed_db', [], $current_language);
    http_response_code(500);
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log("Exception in forums/topics/delete.php: " . $e->getMessage());
    $response['message'] = __('error_server_generic', [], $current_language);
    http_response_code(500);
}

echo json_encode($response);
