<?php
require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../includes/auth.php';
require_login(); // User must be logged in to edit a post

$post_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$topic_id = null; // Will be fetched with the post
$post = null;
$topic = null;
$category = null;
$can_edit = false;
$error_message = '';
$page_title = __('page_title_edit_post_default', [], $current_language); // Default title

if (!$post_id) {
    $_SESSION['error_flash'] = __('error_invalid_post_id_for_edit', [], $current_language);
    redirect_to(get_route_url('pawsconnect')); // Redirect to main forum page or last known good page
}

try {
    $db = Database::getInstance()->getConnection();

    // Fetch post details along with topic and category for breadcrumbs and checks
    $stmt_post = $db->prepare("
        SELECT
            fp.*,
            ft.title AS topic_title,
            ft.slug AS topic_slug,
            ft.is_locked AS topic_is_locked,
            fc.name AS category_name,
            fc.slug AS category_slug
        FROM forum_posts fp
        JOIN forum_topics ft ON fp.topic_id = ft.id
        JOIN forum_categories fc ON ft.category_id = fc.id
        WHERE fp.id = :post_id AND fp.deleted_at IS NULL
    ");
    $stmt_post->bindParam(':post_id', $post_id, PDO::PARAM_INT);
    $stmt_post->execute();
    $post = $stmt_post->fetch(PDO::FETCH_ASSOC);

    if ($post) {
        $topic_id = $post['topic_id'];
        $page_title = sprintf(__('page_title_edit_post_dynamic', [], $current_language), $post_id); // "Editing Post #X"

        // Check ownership and if topic is locked
        if ($post['user_id'] !== current_user_id()) {
            $error_message = __('error_post_edit_not_owner', [], $current_language);
        } elseif ($post['topic_is_locked']) {
            $error_message = __('error_post_edit_topic_locked', [], $current_language);
        } else {
            // Optional: Add time limit for editing (e.g., post can only be edited within 1 hour)
            // $created_at = new DateTime($post['created_at']);
            // $now = new DateTime();
            // $interval = $now->getTimestamp() - $created_at->getTimestamp();
            // if ($interval > 3600) { // 1 hour in seconds
            //     $error_message = __('error_post_edit_time_limit_exceeded', [], $current_language);
            // } else {
            //     $can_edit = true;
            // }
            $can_edit = true; // For now, allow edit if owner and topic not locked
        }

        // Fetch topic and category for breadcrumbs
        $topic = ['slug' => $post['topic_slug'], 'title' => $post['topic_title']];
        $category = ['slug' => $post['category_slug'], 'name' => $post['category_name']];

    } else {
        $error_message = __('error_post_not_found_or_deleted', [], $current_language);
    }

} catch (Exception $e) {
    error_log("Error fetching post for edit: " . $e->getMessage());
    $error_message = __('error_server_generic_page_load', [], $current_language);
}

if (!empty($error_message) && !$can_edit) {
    $_SESSION['error_flash'] = $error_message;
    if ($topic_id && $post && $post['topic_slug']) {
        redirect_to(get_route_url('view_topic', ['slug' => $post['topic_slug']]));
    } else {
        redirect_to(get_route_url('pawsconnect'));
    }
}

// CSRF token
$csrf_token = generate_csrf_token();

// Include header
include_once __DIR__ . '/../../includes/header.php';
?>

<div class="container mt-5">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= get_route_url('pawsconnect') ?>"><?= __('breadcrumb_pawsconnect_home', [], $current_language) ?></a></li>
            <?php if ($category): ?>
                <li class="breadcrumb-item"><a href="<?= get_route_url('view_category', ['slug' => $category['slug']]) ?>"><?= htmlspecialchars($category['name']) ?></a></li>
            <?php endif; ?>
            <?php if ($topic): ?>
                <li class="breadcrumb-item"><a href="<?= get_route_url('view_topic', ['slug' => $topic['slug']]) ?>"><?= htmlspecialchars($topic['title']) ?></a></li>
            <?php endif; ?>
            <li class="breadcrumb-item active" aria-current="page"><?= $page_title ?></li>
        </ol>
    </nav>

    <h2><?= $page_title ?></h2>

    <?php if ($post && $can_edit): ?>
        <form id="editPostForm" method="POST">
            <?= csrf_input_field($csrf_token) ?>
            <input type="hidden" name="post_id" value="<?= htmlspecialchars($post['id']) ?>">

            <div class="mb-3">
                <label for="post_content" class="form-label"><?= __('edit_post_label_content', [], $current_language) ?></label>
                <textarea class="form-control" id="post_content" name="content" rows="10" required><?= htmlspecialchars($post['content']) ?></textarea>
                <div class="invalid-feedback" id="content_error"></div>
            </div>

            <div id="form-error-alert" class="alert alert-danger d-none" role="alert"></div>
            <div id="form-success-alert" class="alert alert-success d-none" role="alert"></div>

            <button type="submit" class="btn btn-primary" id="submitEditPost">
                <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                <?= __('button_save_changes', [], $current_language) ?>
            </button>
            <a href="<?= get_route_url('view_topic', ['slug' => $post['topic_slug']]) ?>" class="btn btn-secondary"><?= __('button_cancel', [], $current_language) ?></a>
        </form>
    <?php else: ?>
        <div class="alert alert-danger">
            <?= $error_message ?: __('error_cannot_edit_post_generic', [], $current_language) ?>
        </div>
        <a href="<?= ($post && $post['topic_slug']) ? get_route_url('view_topic', ['slug' => $post['topic_slug']]) : get_route_url('pawsconnect') ?>" class="btn btn-primary"><?= __('button_back_to_topic_or_forums', [], $current_language) ?></a>
    <?php endif; ?>
</div>

<?php
// Include footer
include_once __DIR__ . '/../../includes/footer.php';
?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const editPostForm = document.getElementById('editPostForm');
    const submitButton = document.getElementById('submitEditPost');
    const spinner = submitButton.querySelector('.spinner-border');
    const formErrorAlert = document.getElementById('form-error-alert');
    const formSuccessAlert = document.getElementById('form-success-alert');
    const contentError = document.getElementById('content_error');

    if (editPostForm) {
        editPostForm.addEventListener('submit', function(event) {
            event.preventDefault();
            clearErrors();

            submitButton.disabled = true;
            spinner.classList.remove('d-none');
            formErrorAlert.classList.add('d-none');
            formSuccessAlert.classList.add('d-none');

            const formData = new FormData(editPostForm);
            const postId = formData.get('post_id');

            fetch('<?= get_api_route_url('v1/forums/posts/update') ?>', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    formSuccessAlert.textContent = data.message || '<?= __('success_post_updated_redirecting', [], $current_language) ?>';
                    formSuccessAlert.classList.remove('d-none');
                    // Redirect back to the topic page after a short delay
                    setTimeout(() => {
                        window.location.href = '<?= ($post && $post['topic_slug']) ? get_route_url('view_topic', ['slug' => $post['topic_slug']]) . '#post-' . $post['id'] : get_route_url('pawsconnect') ?>';
                    }, 2000);
                } else {
                    formErrorAlert.textContent = data.message || '<?= __('error_post_update_failed_unknown', [], $current_language) ?>';
                    formErrorAlert.classList.remove('d-none');
                    if (data.errors) {
                        for (const field in data.errors) {
                            const errorField = document.getElementById(field + '_error');
                            if (errorField) {
                                errorField.textContent = data.errors[field];
                                document.getElementById('post_' + field)?.classList.add('is-invalid');
                            }
                        }
                    }
                    submitButton.disabled = false;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                formErrorAlert.textContent = '<?= __('error_post_update_failed_network', [], $current_language) ?>';
                formErrorAlert.classList.remove('d-none');
                submitButton.disabled = false;
            })
            .finally(() => {
                spinner.classList.add('d-none');
            });
        });
    }

    function clearErrors() {
        contentError.textContent = '';
        document.getElementById('post_content')?.classList.remove('is-invalid');
        formErrorAlert.classList.add('d-none');
        formSuccessAlert.classList.add('d-none');
    }
});
</script>
</body>
</html>
