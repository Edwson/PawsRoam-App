<?php
require_once __DIR__ . '/../../../../includes/init.php';
require_once __DIR__ . '/../../../../includes/auth.php';
require_login_api(); // Ensure user is logged in

header('Content-Type: application/json');

$response = ['success' => false, 'message' => __('error_server_generic', [], $current_language)]; // Default error

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = __('error_method_not_allowed', [], $current_language);
    http_response_code(405);
    echo json_encode($response);
    exit;
}

// CSRF validation
if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
    $response['message'] = __('error_csrf_token_invalid', [], $current_language);
    http_response_code(403);
    echo json_encode($response);
    exit;
}

$post_id = filter_input(INPUT_POST, 'post_id', FILTER_VALIDATE_INT);
$content = trim($_POST['content'] ?? '');

$errors = [];

if (!$post_id) {
    $errors['post_id'] = __('error_invalid_post_id_for_edit_api', [], $current_language);
}

if (empty($content)) {
    $errors['content'] = __('error_post_content_required', [], $current_language);
} elseif (mb_strlen($content) < AppConfig::get('FORUM_POST_CONTENT_MIN_LENGTH', 10)) {
    $errors['content'] = sprintf(__('error_post_content_min_length_detailed', [], $current_language), AppConfig::get('FORUM_POST_CONTENT_MIN_LENGTH', 10));
} elseif (mb_strlen($content) > AppConfig::get('FORUM_POST_CONTENT_MAX_LENGTH', 10000)) {
    $errors['content'] = sprintf(__('error_post_content_max_length_detailed', [], $current_language), AppConfig::get('FORUM_POST_CONTENT_MAX_LENGTH', 10000));
}

if (!empty($errors)) {
    $response['message'] = __('error_validation_failed', [], $current_language);
    $response['errors'] = $errors;
    http_response_code(422); // Unprocessable Entity
    echo json_encode($response);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();

    // Fetch the post and topic details to check ownership and locked status
    $stmt_check = $db->prepare("
        SELECT fp.user_id, ft.is_locked
        FROM forum_posts fp
        JOIN forum_topics ft ON fp.topic_id = ft.id
        WHERE fp.id = :post_id AND fp.deleted_at IS NULL
    ");
    $stmt_check->bindParam(':post_id', $post_id, PDO::PARAM_INT);
    $stmt_check->execute();
    $post_data = $stmt_check->fetch(PDO::FETCH_ASSOC);

    if (!$post_data) {
        $response['message'] = __('error_post_not_found_or_deleted_api', [], $current_language);
        http_response_code(404);
        echo json_encode($response);
        exit;
    }

    if ($post_data['user_id'] !== current_user_id()) {
        // Check if user is admin/moderator - for future enhancement
        // if (!has_role(['admin', 'moderator'])) { ... }
        $response['message'] = __('error_post_edit_not_owner_api', [], $current_language);
        http_response_code(403); // Forbidden
        echo json_encode($response);
        exit;
    }

    if ($post_data['is_locked']) {
        // Admins/mods might be able to edit locked posts - for future enhancement
        // if (!has_role(['admin', 'moderator'])) { ... }
        $response['message'] = __('error_post_edit_topic_locked_api', [], $current_language);
        http_response_code(403); // Forbidden
        echo json_encode($response);
        exit;
    }

    // Optional: Time limit check (e.g., edit within 1 hour)
    // Consider adding `created_at` to the $stmt_check query
    // $created_at_timestamp = strtotime($post_data['created_at']); // Assuming created_at is fetched
    // $edit_time_limit_seconds = AppConfig::get('FORUM_POST_EDIT_TIME_LIMIT_SECONDS', 3600); // 1 hour default
    // if (time() - $created_at_timestamp > $edit_time_limit_seconds) {
    //     // Allow admins/mods to bypass this - future
    //     $response['message'] = __('error_post_edit_time_limit_exceeded_api', [], $current_language);
    //     http_response_code(403);
    //     echo json_encode($response);
    //     exit;
    // }


    // Update the post
    $stmt_update = $db->prepare("UPDATE forum_posts SET content = :content, updated_at = CURRENT_TIMESTAMP WHERE id = :post_id");
    $stmt_update->bindParam(':content', $content, PDO::PARAM_STR);
    $stmt_update->bindParam(':post_id', $post_id, PDO::PARAM_INT);

    if ($stmt_update->execute()) {
        if ($stmt_update->rowCount() > 0) {
            $response['success'] = true;
            $response['message'] = __('success_post_updated', [], $current_language);
            // Optionally, return the updated post data or a link to it
        } else {
            // This case might happen if content was identical, or post_id was valid but somehow didn't update
            $response['message'] = __('error_post_update_no_change_or_failed', [], $current_language);
            // Consider if this should be success true if no change, or keep as error/warning
        }
    } else {
        $response['message'] = __('error_post_update_failed_db', [], $current_language);
        http_response_code(500);
    }

} catch (PDOException $e) {
    error_log("PDOException in forums/posts/update.php: " . $e->getMessage());
    $response['message'] = __('error_post_update_failed_db', [], $current_language);
    http_response_code(500);
} catch (Exception $e) {
    error_log("Exception in forums/posts/update.php: " . $e->getMessage());
    $response['message'] = __('error_server_generic', [], $current_language);
    http_response_code(500);
}

echo json_encode($response);
