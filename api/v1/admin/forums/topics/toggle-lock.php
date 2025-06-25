<?php
require_once __DIR__ . '/../../../../../includes/init.php';
require_once __DIR__ . '/../../../../../includes/auth.php';
require_role_api(['super_admin', 'admin']); // Ensure user is an admin

header('Content-Type: application/json');
$response = ['success' => false, 'message' => __('error_server_generic', [], $current_language)];
$current_user_id = current_user_id();

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

if (!$topic_id) {
    $response['message'] = __('error_invalid_topic_id_for_lock_api', [], $current_language);
    http_response_code(422);
    echo json_encode($response);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();

    // Fetch current lock status
    $stmt_check = $db->prepare("SELECT is_locked FROM forum_topics WHERE id = :topic_id AND deleted_at IS NULL");
    $stmt_check->bindParam(':topic_id', $topic_id, PDO::PARAM_INT);
    $stmt_check->execute();
    $topic_status = $stmt_check->fetch(PDO::FETCH_ASSOC);

    if (!$topic_status) {
        $response['message'] = __('error_topic_not_found_or_deleted_api', [], $current_language); // Reusing existing string
        http_response_code(404);
        echo json_encode($response);
        exit;
    }

    $new_lock_status = !$topic_status['is_locked'];
    $locked_at_value = $new_lock_status ? 'CURRENT_TIMESTAMP' : 'NULL';
    $locked_by_user_id_value = $new_lock_status ? $current_user_id : null;

    $sql = "UPDATE forum_topics
            SET is_locked = :is_locked,
                locked_at = " . ($new_lock_status ? "CURRENT_TIMESTAMP" : "NULL") . ",
                locked_by_user_id = :locked_by_user_id
            WHERE id = :topic_id";

    $stmt_toggle = $db->prepare($sql);
    $stmt_toggle->bindParam(':is_locked', $new_lock_status, PDO::PARAM_BOOL);
    $stmt_toggle->bindParam(':locked_by_user_id', $locked_by_user_id_value, PDO::PARAM_INT); // This will bind NULL if $new_lock_status is false
    $stmt_toggle->bindParam(':topic_id', $topic_id, PDO::PARAM_INT);

    if ($stmt_toggle->execute()) {
        if ($stmt_toggle->rowCount() > 0) {
            $response['success'] = true;
            $response['is_locked'] = $new_lock_status;
            $response['message'] = $new_lock_status ? __('success_topic_locked', [], $current_language) : __('success_topic_unlocked', [], $current_language);
        } else {
            // This might happen if the status was already the new_lock_status (e.g. concurrent requests)
            // Or if topic_id was valid but somehow didn't update (should not happen if fetched above)
            $response['message'] = __('error_topic_lock_status_no_change', [], $current_language);
            // We can still return success true and the current actual status if needed
            $response['is_locked'] = $topic_status['is_locked']; // Return the actual current status
        }
    } else {
        $response['message'] = __('error_topic_lock_update_failed_db', [], $current_language);
        http_response_code(500);
    }

} catch (PDOException $e) {
    error_log("PDOException in admin/forums/topics/toggle-lock.php: " . $e->getMessage());
    $response['message'] = __('error_topic_lock_update_failed_db', [], $current_language);
    http_response_code(500);
} catch (Exception $e) {
    error_log("Exception in admin/forums/topics/toggle-lock.php: " . $e->getMessage());
    $response['message'] = __('error_server_generic', [], $current_language);
    http_response_code(500);
}

echo json_encode($response);
