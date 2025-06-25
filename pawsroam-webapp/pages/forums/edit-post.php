<?php
require_login();

$pageTitle = __('page_title_edit_forum_post', [], $GLOBALS['current_language'] ?? 'en'); // "Edit Forum Post"
$user_id = current_user_id();
$post_id = filter_var($_GET['post_id'] ?? null, FILTER_VALIDATE_INT);
$post_data = null;
$topic_data = null; // To get topic slug for cancel link
$error_message = null;

if (!$post_id) {
    $error_message = __('error_forum_invalid_post_id_for_edit', [], $GLOBALS['current_language'] ?? 'en'); // "No post specified or invalid ID for editing."
    http_response_code(400);
} else {
    try {
        $db = Database::getInstance()->getConnection();
        // Fetch post and also its topic's slug and category slug for breadcrumbs/cancel link
        $stmt = $db->prepare(
            "SELECT fp.*, ft.slug as topic_slug, ft.title as topic_title, fc.slug as category_slug, fc.name as category_name
             FROM forum_posts fp
             JOIN forum_topics ft ON fp.topic_id = ft.id
             JOIN forum_categories fc ON ft.category_id = fc.id
             WHERE fp.id = :post_id AND fp.deleted_at IS NULL LIMIT 1"
        );
        $stmt->bindParam(':post_id', $post_id, PDO::PARAM_INT);
        $stmt->execute();
        $post_data = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$post_data) {
            $error_message = __('error_forum_post_not_found_or_deleted', [], $GLOBALS['current_language'] ?? 'en'); // "Post not found or it has been deleted."
            http_response_code(404);
        } elseif ($post_data['user_id'] !== $user_id && !has_role(['super_admin', 'moderator'])) { // Check ownership or admin/mod role
            $error_message = __('error_forum_post_edit_unauthorized', [], $GLOBALS['current_language'] ?? 'en'); // "You are not authorized to edit this post."
            http_response_code(403);
            $post_data = null; // Don't proceed with form
        } else {
            $pageTitle = __('page_title_edit_forum_post_num %s', [], $GLOBALS['current_language'] ?? 'en'); // sprintf("Edit Post #%s", $post_id)
        }
    } catch (PDOException $e) {
        error_log("Database error fetching post for edit (ID: {$post_id}, User: {$user_id}): " . $e->getMessage());
        $error_message = __('error_forum_post_load_failed_db', [], $GLOBALS['current_language'] ?? 'en'); // "Could not load post for editing due to a database error."
        http_response_code(500);
    }
}

if (empty($_SESSION[CSRF_TOKEN_NAME ?? 'csrf_token'])) { generate_csrf_token(true); }

$cancel_url = $post_data ? base_url('/forums/topic/' . e($post_data['topic_slug']) . '#post-' . $post_data['id']) : base_url('/pawsconnect');

?>

<div class="container my-4 my-md-5">
    <?php if ($post_data): ?>
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?php echo e(base_url('/pawsconnect')); ?>"><?php echo e(__('breadcrumb_pawsconnect_home', [], $GLOBALS['current_language'] ?? 'en')); ?></a></li>
            <li class="breadcrumb-item"><a href="<?php echo e(base_url('/forums/category/' . e($post_data['category_slug']))); ?>"><?php echo e($post_data['category_name']); ?></a></li>
            <li class="breadcrumb-item"><a href="<?php echo e(base_url('/forums/topic/' . e($post_data['topic_slug']))); ?>"><?php echo e($post_data['topic_title']); ?></a></li>
            <li class="breadcrumb-item active" aria-current="page"><?php echo e(sprintf(__('breadcrumb_edit_post %s', [], $GLOBALS['current_language'] ?? 'en'), $post_id)); // "Edit Post #X" ?></li>
        </ol>
    </nav>
    <?php endif; ?>

    <div class="row mb-3 align-items-center">
        <div class="col">
            <h1 class="display-6 fw-bold"><?php echo e($pageTitle); ?></h1>
        </div>
    </div>

    <?php if ($error_message): ?>
        <div class="alert alert-danger shadow-sm" role="alert">
            <h4 class="alert-heading"><i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo e(__('error_oops_title', [], $GLOBALS['current_language'] ?? 'en')); ?></h4>
            <p><?php echo e($error_message); ?></p>
            <a href="<?php echo e($cancel_url); ?>" class="btn btn-secondary"><?php echo e(__('button_back_to_topic_or_forums', [], $GLOBALS['current_language'] ?? 'en')); // "Return to Topic/Forums" ?></a>
        </div>
    <?php elseif ($post_data): ?>
    <div class="card shadow-lg border-0">
        <div class="card-header bg-primary-orange text-white py-3">
            <h2 class="h4 mb-0"><i class="bi bi-pencil-fill me-2"></i><?php echo e(__('edit_post_form_title', [], $GLOBALS['current_language'] ?? 'en')); // "Edit Your Post" ?></h2>
        </div>
        <div class="card-body p-4 p-md-5">
            <form id="editPostForm" action="<?php echo e(base_url('/api/v1/forums/posts/update.php')); ?>" method="POST" novalidate>
                <?php echo csrf_input_field(); ?>
                <input type="hidden" name="post_id" value="<?php echo e($post_data['id']); ?>">
                <div id="editPostFormMessages" class="mb-3" role="alert" aria-live="assertive"></div>

                <div class="form-floating mb-3">
                    <textarea class="form-control" id="post_content" name="content" style="height: 250px" placeholder="<?php echo e(__('edit_post_placeholder_content', [], $GLOBALS['current_language'] ?? 'en')); // "Edit your message..." ?>" required minlength="5"><?php echo e($post_data['content']); ?></textarea>
                    <label for="post_content"><?php echo e(__('edit_post_label_content', [], $GLOBALS['current_language'] ?? 'en')); // "Your Message" ?></label>
                    <small class="form-text text-muted"><?php echo e(__('forum_topic_markdown_supported_note', [], $GLOBALS['current_language'] ?? 'en')); ?></small>
                    <div class="invalid-feedback" id="contentError"></div>
                </div>

                <div class="mt-4 pt-2">
                    <button type="submit" class="btn btn-primary btn-lg px-5" id="submitEditPostBtn">
                        <span class="button-text"><?php echo e(__('button_save_changes', [], $GLOBALS['current_language'] ?? 'en')); // "Save Changes" ?></span>
                        <span class="spinner-border spinner-border-sm ms-2 d-none" role="status" aria-hidden="true"></span>
                    </button>
                    <a href="<?php echo e($cancel_url); ?>" class="btn btn-link text-muted ms-2"><?php echo e(__('button_cancel', [], $GLOBALS['current_language'] ?? 'en')); ?></a>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const editPostForm = document.getElementById('editPostForm');
    if (!editPostForm) return;

    const submitBtn = document.getElementById('submitEditPostBtn');
    const btnText = submitBtn.querySelector('.button-text');
    const spinner = submitBtn.querySelector('.spinner-border');
    const formMessages = document.getElementById('editPostFormMessages');

    function clearValidationUI() { /* ... */ }
    function displayFormMessage(message, type = 'danger', isHtml = false) { /* ... */ }
    function displayFieldErrors(errors) { /* ... */ }
    function escapeHtml(unsafe) { if (typeof unsafe !== 'string') return ''; return unsafe.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;"); }
    function displayFormMessage(message, type = 'danger', isHtml = false) {if (!formMessages) return; formMessages.innerHTML = `<div class="alert alert-${type} alert-dismissible fade show" role="alert">${isHtml ? message : escapeHtml(message)}<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>`;}


    editPostForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        // clearValidationUI(); // Implement this based on other forms

        if(btnText) btnText.textContent = '<?php echo e(addslashes(__('state_text_processing', [], $GLOBALS['current_language'] ?? 'en'))); ?>';
        if(spinner) spinner.classList.remove('d-none');
        submitBtn.disabled = true;

        const formData = new FormData(editPostForm);
        try {
            const response = await fetch(editPostForm.action, {
                method: 'POST', body: formData, headers: {'Accept': 'application/json'}
            });
            const result = await response.json();

            if (response.ok && result.success) {
                displayFormMessage(result.message || '<?php echo e(addslashes(__('edit_post_success_message', [], $GLOBALS['current_language'] ?? 'en' ))); // "Post updated successfully!" ?>', 'success');
                // Redirect back to the topic view, anchored to the post
                setTimeout(() => {
                    window.location.href = `<?php echo e($cancel_url); // Already has #post-id if post_data exists ?>`;
                }, 1500);
            } else {
                let errMsg = result.message || '<?php echo e(addslashes(__('edit_post_error_unknown', [], $GLOBALS['current_language'] ?? 'en' ))); // "Failed to update post. Please check errors." ?>';
                if (result.errors) {
                    // displayFieldErrors(result.errors); // Implement this
                     let errorText = "<?php echo e(addslashes(__('error_validation_summary', [], $GLOBALS['current_language'] ?? 'en' ))); ?>\n";
                    for(const field in result.errors){ errorText += `- ${result.errors[field]}\n`;}
                    displayFormMessage(errorText.replace(/\n/g, '<br>'), 'danger', true);
                } else {
                    displayFormMessage(errMsg, 'danger');
                }
            }
        } catch (error) {
            console.error("Edit post submission error:", error);
            displayFormMessage('<?php echo e(addslashes(__('edit_post_error_network', [], $GLOBALS['current_language'] ?? 'en' ))); // "Network error updating post." ?>', 'danger');
        } finally {
            if(btnText) btnText.textContent = '<?php echo e(addslashes(__('button_save_changes', [], $GLOBALS['current_language'] ?? 'en'))); ?>';
            if(spinner) spinner.classList.add('d-none');
            submitBtn.disabled = false;
        }
    });
});
</script>
<?php
// Translation Placeholders
// __('page_title_edit_forum_post', [], $GLOBALS['current_language'] ?? 'en');
// __('error_invalid_post_id_for_edit', [], $GLOBALS['current_language'] ?? 'en');
// __('error_forum_post_not_found_or_deleted', [], $GLOBALS['current_language'] ?? 'en');
// __('error_forum_post_edit_unauthorized', [], $GLOBALS['current_language'] ?? 'en');
// __('page_title_edit_forum_post_num %s', [], $GLOBALS['current_language'] ?? 'en');
// __('error_forum_post_load_failed_db', [], $GLOBALS['current_language'] ?? 'en');
// __('breadcrumb_edit_post %s', [], $GLOBALS['current_language'] ?? 'en');
// __('button_back_to_topic_or_forums', [], $GLOBALS['current_language'] ?? 'en');
// __('edit_post_form_title', [], $GLOBALS['current_language'] ?? 'en');
// __('edit_post_placeholder_content', [], $GLOBALS['current_language'] ?? 'en');
// __('edit_post_label_content', [], $GLOBALS['current_language'] ?? 'en');
// __('button_save_changes', [], $GLOBALS['current_language'] ?? 'en');
// __('edit_post_success_message', [], $GLOBALS['current_language'] ?? 'en');
// __('edit_post_error_unknown', [], $GLOBALS['current_language'] ?? 'en');
// __('edit_post_error_network', [], $GLOBALS['current_language'] ?? 'en');
?>
