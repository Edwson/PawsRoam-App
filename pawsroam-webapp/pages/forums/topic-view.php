<?php
// Forum Topic View Page
// Included by index.php

$pageTitle = __('page_title_forum_topic_default', [], $GLOBALS['current_language'] ?? 'en'); // "View Topic"
$topic_slug = $_GET['topic_slug'] ?? null; // Set by router in index.php
$topic = null;
$category = null; // For breadcrumbs
$posts = [];
$pagination = ['current_page' => 1, 'total_pages' => 1, 'total_items' => 0, 'items_per_page' => 15]; // Default pagination for posts
$error_message = null;

if (empty($topic_slug)) {
    $error_message = __('error_forum_no_topic_slug_provided', [], $GLOBALS['current_language'] ?? 'en'); // "No topic specified."
    http_response_code(400);
} else {
    try {
        $db = Database::getInstance()->getConnection();

        // Fetch topic details AND its category details for breadcrumbs
        $stmt_topic = $db->prepare(
            "SELECT ft.*, fc.name as category_name, fc.slug as category_slug
             FROM forum_topics ft
             JOIN forum_categories fc ON ft.category_id = fc.id
             WHERE ft.slug = :slug LIMIT 1"
        );
        $stmt_topic->bindParam(':slug', $topic_slug);
        $stmt_topic->execute();
        $topic = $stmt_topic->fetch(PDO::FETCH_ASSOC);

        if (!$topic) {
            $error_message = __('error_forum_topic_not_found', [], $GLOBALS['current_language'] ?? 'en'); // "The requested topic was not found."
            http_response_code(404);
        } else {
            $pageTitle = e($topic['title']) . " - " . e($topic['category_name']);
            $category = ['name' => $topic['category_name'], 'slug' => $topic['category_slug']]; // Store category for breadcrumbs

            // Increment view count (simple increment, could be made more robust against bots/repeat views later)
            $stmt_inc_views = $db->prepare("UPDATE forum_topics SET view_count = view_count + 1 WHERE id = :topic_id");
            $stmt_inc_views->bindParam(':topic_id', $topic['id'], PDO::PARAM_INT);
            $stmt_inc_views->execute();

            // Fetch posts for this topic with pagination
            $current_page = filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT, ['options' => ['default' => 1, 'min_range' => 1]]);
            $items_per_page = 15;
            $offset = ($current_page - 1) * $items_per_page;

            $stmt_post_count = $db->prepare("SELECT COUNT(*) FROM forum_posts WHERE topic_id = :topic_id AND deleted_at IS NULL");
            $stmt_post_count->bindParam(':topic_id', $topic['id'], PDO::PARAM_INT);
            $stmt_post_count->execute();
            $total_posts = (int)$stmt_post_count->fetchColumn();

            $pagination['total_items'] = $total_posts;
            $pagination['items_per_page'] = $items_per_page;
            $pagination['current_page'] = $current_page;
            $pagination['total_pages'] = ceil($total_posts / $items_per_page);

            $sql_posts = "SELECT fp.*, u.username as author_username, u.avatar_path as author_avatar_path
                          FROM forum_posts fp
                          JOIN users u ON fp.user_id = u.id
                          WHERE fp.topic_id = :topic_id AND fp.deleted_at IS NULL
                          ORDER BY fp.created_at ASC
                          LIMIT :limit OFFSET :offset";

            $stmt_posts = $db->prepare($sql_posts);
            $stmt_posts->bindParam(':topic_id', $topic['id'], PDO::PARAM_INT);
            $stmt_posts->bindParam(':limit', $items_per_page, PDO::PARAM_INT);
            $stmt_posts->bindParam(':offset', $offset, PDO::PARAM_INT);
            $stmt_posts->execute();
            $posts = $stmt_posts->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $e) {
        error_log("Database error on topic view page (Slug: {$topic_slug}): " . $e->getMessage());
        $error_message = __('error_forums_topic_load_failed_db', [], $GLOBALS['current_language'] ?? 'en'); // "Could not load topic details."
    }
}
?>

<div class="container my-4 my-md-5">
    <?php if ($error_message): ?>
        <div class="alert alert-danger" role="alert">
            <h4 class="alert-heading"><?php echo e(__('error_oops_title', [], $GLOBALS['current_language'] ?? 'en')); ?></h4>
            <p><?php echo e($error_message); ?></p>
            <a href="<?php echo e(base_url('/pawsconnect')); ?>" class="btn btn-primary"><?php echo e(__('button_back_to_forums_main', [], $GLOBALS['current_language'] ?? 'en')); ?></a>
        </div>
    <?php elseif ($topic && $category): ?>
        <nav aria-label="breadcrumb" class="mb-3">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo e(base_url('/pawsconnect')); ?>"><?php echo e(__('breadcrumb_pawsconnect_home', [], $GLOBALS['current_language'] ?? 'en')); ?></a></li>
                <li class="breadcrumb-item"><a href="<?php echo e(base_url('/forums/category/' . e($category['slug']))); ?>"><?php echo e($category['name']); ?></a></li>
                <li class="breadcrumb-item active" aria-current="page"><?php echo e($topic['title']); ?></li>
            </ol>
        </nav>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h2 fw-bold text-primary-orange mb-0"><?php echo e($topic['title']); ?></h1>
            <div class="d-flex align-items-center">
                <?php if (is_logged_in() && current_user_id() === $topic['user_id'] /* && !has_role('admin') etc. */ ): ?>
                    <button type="button" class="btn btn-sm btn-outline-danger me-2" id="deleteTopicBtn"
                            data-topic-id="<?php echo e($topic['id']); ?>"
                            data-category-slug="<?php echo e($category['slug']); ?>"
                            data-bs-toggle="modal" data-bs-target="#deleteTopicConfirmModal"
                            title="<?= __('tooltip_delete_this_topic', [], $current_language) ?>">
                        <i class="fas fa-trash-alt"></i> <span class="d-none d-md-inline"><?= __('button_delete_topic_short', [], $current_language) ?></span>
                    </button>
                <?php endif; ?>
                <?php if (has_role(['super_admin', 'admin'])): ?>
                    <button type="button" class="btn btn-sm btn-outline-warning me-2" id="toggleLockTopicBtn"
                            data-topic-id="<?php echo e($topic['id']); ?>"
                            data-is-locked="<?php echo e($topic['is_locked'] ? '1' : '0'); ?>"
                            title="<?= $topic['is_locked'] ? __('tooltip_unlock_this_topic', [], $current_language) : __('tooltip_lock_this_topic', [], $current_language) ?>">
                        <i class="fas <?= $topic['is_locked'] ? 'fa-unlock-alt' : 'fa-lock' ?>"></i>
                        <span class="d-none d-md-inline toggle-lock-text">
                            <?= $topic['is_locked'] ? __('button_unlock_topic_short', [], $current_language) : __('button_lock_topic_short', [], $current_language) ?>
                        </span>
                    </button>
                <?php endif; ?>
                <?php if (is_logged_in() && !$topic['is_locked']): ?>
                <a href="#replyForm" id="replyToTopicLink" class="btn btn-primary shadow-sm <?= ($topic['is_locked'] ? 'disabled' : '') // Initial state based on PHP ?>" title="<?php echo e(__('tooltip_reply_to_topic', [], $GLOBALS['current_language'] ?? 'en')); ?>" aria-disabled="<?= ($topic['is_locked'] ? 'true' : 'false') ?>">
                    <i class="bi bi-reply-fill me-2"></i><?php echo e(__('button_reply_to_topic', [], $GLOBALS['current_language'] ?? 'en')); // "Reply to Topic" ?>
                </a>
                <?php endif; // removed elseif for locked message, as it's handled by reply link state and a new dynamic message area ?>
                <span id="topicLockedStatusMessage" class="badge bg-warning text-dark p-2 ms-2 <?= $topic['is_locked'] ? '' : 'd-none' ?>"><i class="bi bi-lock-fill me-1"></i> <?= __('topic_is_locked_message', [], $GLOBALS['current_language'] ?? 'en') ?></span>
            </div>
        </div>

        <?php if (empty($posts) && $pagination['total_items'] === 0): ?>
            <div class="alert alert-info text-center py-4">
                <?php echo e(__('forum_topic_no_posts_yet', [], $GLOBALS['current_language'] ?? 'en')); // "This topic has no posts yet." ?>
                <?php if (is_logged_in() && !$topic['is_locked']): ?>
                    <br><?php echo e(__('forum_topic_be_first_to_reply', [], $GLOBALS['current_language'] ?? 'en')); // "Be the first to reply!" ?>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <?php foreach ($posts as $post): ?>
                <?php
                    $author_avatar = base_url('/assets/images/placeholders/avatar_placeholder_50.png');
                    if (!empty($post['author_avatar_path']) && defined('UPLOADS_BASE_URL')) {
                        // Assuming avatar_path is like 'user-avatars/USER_ID/filename.jpg'
                        $author_avatar = rtrim(UPLOADS_BASE_URL, '/') . '/' . ltrim($post['author_avatar_path'], '/');
                    }
                ?>
                <div class="card mb-3 shadow-sm forum-post" id="post-<?php echo e($post['id']); ?>">
                    <div class="card-header bg-light py-2 px-3 d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center">
                            <img src="<?php echo e($author_avatar); ?>" alt="<?php echo e(e($post['author_username'])); ?>" class="rounded-circle me-2" style="width: 32px; height: 32px; object-fit: cover;">
                            <strong class="me-2"><?php echo e($post['author_username']); ?></strong>
                            <small class="text-muted" title="<?php echo e(date(DateTime::ATOM, strtotime($post['created_at']))); ?>">
                                <?php echo e(date("M j, Y, g:i A", strtotime($post['created_at']))); ?>
                            </small>
                            <?php if ($post['updated_at'] && $post['updated_at'] !== $post['created_at']): ?>
                                <small class="text-muted ms-2 fst-italic">(<?= __('forum_post_edited_at %s', [display_time_ago($post['updated_at'], $current_language)], $current_language) ?>)</small>
                            <?php endif; ?>
                        </div>
                        <div class="d-flex align-items-center">
                            <?php if (is_logged_in() && $post['user_id'] === current_user_id() && !$topic['is_locked']): ?>
                                <a href="<?= get_route_url('edit_post', ['id' => $post['id']]) ?>" class="btn btn-sm btn-outline-secondary me-2" title="<?= __('tooltip_edit_this_post', [], $current_language) ?>">
                                    <i class="fas fa-edit"></i> <span class="d-none d-md-inline"><?= __('button_edit_post_short', [], $current_language) ?></span>
                                </a>
                                <button type="button" class="btn btn-sm btn-outline-danger delete-post-btn"
                                        data-post-id="<?php echo e($post['id']); ?>"
                                        data-bs-toggle="modal" data-bs-target="#deletePostConfirmModal"
                                        title="<?= __('tooltip_delete_this_post', [], $current_language) ?>">
                                    <i class="fas fa-trash-alt"></i> <span class="d-none d-md-inline"><?= __('button_delete_post_short', [], $current_language) ?></span>
                                </button>
                            <?php endif; ?>
                            <small class="text-muted">#<?php echo e($post['id']); // Post ID or running number ?></small>
                        </div>
                    </div>
                    <div class="card-body p-3 forum-post-content">
                        <?php echo nl2br(e($post['content'])); // Basic display, Markdown later ?>
                    </div>
                    <?php /* Post footer for actions - future
                    <div class="card-footer bg-light py-1 px-3 text-end">
                        <button class="btn btn-sm btn-link text-muted disabled">Quote</button>
                        <button class="btn btn-sm btn-link text-muted disabled">Report</button>
                    </div>
                    */ ?>
                </div>
            <?php endforeach; ?>

            <!-- Pagination for Posts (Stub) -->
            <?php if ($pagination['total_pages'] > 1): ?>
            <nav aria-label="Posts pagination" class="mt-4 d-flex justify-content-center">
                <ul class="pagination">
                    <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                        <li class="page-item <?php echo ($i == $pagination['current_page']) ? 'active' : ''; ?>">
                            <a class="page-link" href="<?php echo e(base_url('/forums/topic/' . e($topic['slug']) . '?page=' . $i)); ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
            <?php endif; ?>
        <?php endif; ?>

        <hr class="my-5">

        <!-- Reply Form Placeholder -->
        <section id="replyForm" class="mt-4">
            <h3 class="h5 mb-3"><?php echo e(__('forum_topic_reply_form_title', [], $GLOBALS['current_language'] ?? 'en')); // "Post a Reply" ?></h3>
            <?php if (is_logged_in()): ?>
                <?php if ($topic['is_locked']): ?>
                    <div class="alert alert-warning"><?php echo e(__('forum_topic_locked_cannot_reply', [], $GLOBALS['current_language'] ?? 'en')); // "This topic is locked. No new replies can be posted." ?></div>
                <?php else: ?>
                    <form action="<?php echo e(base_url('/api/v1/forums/posts/create.php')); ?>" method="POST" id="postReplyForm">
                        <?php echo csrf_input_field(); ?>
                        <input type="hidden" name="topic_id" value="<?php echo e($topic['id']); ?>">
                        <div class="mb-3">
                            <textarea class="form-control" name="content" rows="5" placeholder="<?php echo e(__('forum_topic_reply_placeholder', [], $GLOBALS['current_language'] ?? 'en')); // "Enter your reply..." ?>" required <?= $topic['is_locked'] ? 'disabled' : '' ?>></textarea>
                            <small class="form-text text-muted"><?php echo e(__('forum_topic_markdown_supported_note', [], $GLOBALS['current_language'] ?? 'en')); // "Basic Markdown is supported. (Feature coming soon)" ?></small>
                        </div>
                        <button type="submit" class="btn btn-primary" <?= $topic['is_locked'] ? 'disabled' : '' ?> aria-disabled="<?= $topic['is_locked'] ? 'true' : 'false' ?>"><?php echo e(__('button_submit_reply', [], $GLOBALS['current_language'] ?? 'en')); // "Submit Reply" ?></button>
                         <small class="ms-2 text-muted"><?php echo e(__('forum_reply_feature_stub_note', [], $GLOBALS['current_language'] ?? 'en')); // "(Reply functionality is a stub for now, will be enabled with JS later)" ?></small>
                    </form>
                <?php endif; ?>
            <?php else: ?>
                <div class="alert alert-info">
                    <?php
                    $login_url_reply = base_url('/login?return_to=' . urlencode($_SERVER['REQUEST_URI'] ?? ''));
                    $login_link_reply = '<a href="'.e($login_url_reply).'" class="alert-link fw-bold">'.e(__('review_login_link_text', [], $GLOBALS['current_language'] ?? 'en')).'</a>';
                    echo sprintf(e(__('forum_topic_login_to_reply %s', [], $GLOBALS['current_language'] ?? 'en')), $login_link_reply); // "%s to post a reply."
                    ?>
                </div>
            <?php endif; ?>
        </section>
    <?php endif; ?>

    <!-- Delete Post Confirmation Modal -->
    <div class="modal fade" id="deletePostConfirmModal" tabindex="-1" aria-labelledby="deletePostConfirmModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deletePostConfirmModalLabel"><?= __('modal_title_delete_post_confirm', [], $current_language) ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= __('button_close', [], $current_language) ?>"></button>
                </div>
                <div class="modal-body">
                    <?= __('modal_body_delete_post_warning', [], $current_language) ?>
                    <span id="deletePostIdDisplay" class="fw-bold"></span>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= __('button_cancel', [], $current_language) ?></button>
                    <button type="button" class="btn btn-danger" id="confirmDeletePostBtn"><?= __('button_delete_confirm_post', [], $current_language) ?></button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Topic Confirmation Modal -->
    <div class="modal fade" id="deleteTopicConfirmModal" tabindex="-1" aria-labelledby="deleteTopicConfirmModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteTopicConfirmModalLabel"><?= __('modal_title_delete_topic_confirm', [], $current_language) ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= __('button_close', [], $current_language) ?>"></button>
                </div>
                <div class="modal-body">
                    <?= __('modal_body_delete_topic_warning', [], $current_language) ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= __('button_cancel', [], $current_language) ?></button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteTopicBtn"><?= __('button_delete_confirm_topic', [], $current_language) ?></button>
                </div>
            </div>
        </div>
    </div>
</div>
<style>.forum-post-content { white-space: pre-wrap; word-break: break-word; }</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const deletePostModalEl = document.getElementById('deletePostConfirmModal');
    const deletePostModal = deletePostModalEl ? new bootstrap.Modal(deletePostModalEl) : null;
    let postIdToDelete = null;

    const deleteTopicModalEl = document.getElementById('deleteTopicConfirmModal');
    const deleteTopicModal = deleteTopicModalEl ? new bootstrap.Modal(deleteTopicModalEl) : null;
    let topicIdToDelete = null;
    let categorySlugForRedirect = null;

    document.querySelectorAll('.delete-post-btn').forEach(button => {
        button.addEventListener('click', function() {
            postIdToDelete = this.dataset.postId;
            // Optionally display post ID in modal: document.getElementById('deletePostIdDisplay').textContent = `#${postIdToDelete}`;
        });
    });

    const confirmDeletePostButton = document.getElementById('confirmDeletePostBtn');
    if (confirmDeletePostButton) {
        confirmDeletePostButton.addEventListener('click', function() {
            if (!postIdToDelete) return;

            const submitButton = this;
            const originalButtonText = submitButton.innerHTML;
            submitButton.disabled = true;
            submitButton.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> <?= __('state_text_processing', [], $current_language) ?>`;

            const formData = new FormData();
            formData.append('topic_id', topicIdToDelete);
            formData.append('csrf_token', '<?= htmlspecialchars(generate_csrf_token(), ENT_QUOTES, 'UTF-8') ?>');

            fetch('<?= get_api_route_url('v1/forums/topics/delete') ?>', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if(deleteTopicModal) deleteTopicModal.hide();
                    showGlobalSuccessAlert(data.message || '<?= __('success_topic_deleted_js', [], $current_language) ?>');
                    // Redirect to the category page after deletion
                    setTimeout(() => {
                        const categoryViewUrl = `<?= get_route_url('view_category', ['slug' => 'PLACEHOLDER_CAT_SLUG']) ?>`;
                        window.location.href = categoryViewUrl.replace('PLACEHOLDER_CAT_SLUG', categorySlugForRedirect || '<?= $category['slug'] ?? 'general' ?>');
                    }, 2500);
                } else {
                    showGlobalErrorAlert(data.message || '<?= __('error_topic_delete_failed_js', [], $current_language) ?>');
                }
            })
            .catch(error => {
                console.error('Error deleting topic:', error);
                showGlobalErrorAlert('<?= __('error_topic_delete_network_js', [], $current_language) ?>');
            })
            .finally(() => {
                submitButton.disabled = false;
                submitButton.innerHTML = originalButtonText;
                topicIdToDelete = null;
                categorySlugForRedirect = null;
            });
        });
    }

    // Toggle Lock Topic Logic
    const toggleLockTopicButton = document.getElementById('toggleLockTopicBtn');
    if (toggleLockTopicButton) {
        toggleLockTopicButton.addEventListener('click', function() {
            const topicId = this.dataset.topicId;
            const currentlyLocked = this.dataset.isLocked === '1';
            const button = this;
            const buttonIcon = button.querySelector('i');
            const buttonTextSpan = button.querySelector('.toggle-lock-text');

            const originalButtonHTML = button.innerHTML; // Save full HTML to restore icon and text easily
            button.disabled = true;
            button.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> <?= __('state_text_processing', [], $current_language) ?>`;

            const formData = new FormData();
            formData.append('topic_id', topicId);
            formData.append('csrf_token', '<?= htmlspecialchars(generate_csrf_token(), ENT_QUOTES, 'UTF-8') ?>');

            fetch('<?= get_api_route_url('v1/admin/forums/topics/toggle-lock') ?>', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showGlobalSuccessAlert(data.message);
                    const newLockedState = data.is_locked;
                    button.dataset.isLocked = newLockedState ? '1' : '0';

                    // Update button appearance
                    if (buttonIcon) buttonIcon.className = `fas ${newLockedState ? 'fa-unlock-alt' : 'fa-lock'}`;
                    if (buttonTextSpan) buttonTextSpan.textContent = newLockedState ? '<?= __('button_unlock_topic_short', [], $current_language) ?>' : '<?= __('button_lock_topic_short', [], $current_language) ?>';
                    button.title = newLockedState ? '<?= __('tooltip_unlock_this_topic', [], $current_language) ?>' : '<?= __('tooltip_lock_this_topic', [], $current_language) ?>';

                    // Update UI elements based on new lock state
                    updatePageForLockState(newLockedState);

                } else {
                    showGlobalErrorAlert(data.message || '<?= __('error_topic_lock_toggle_failed_js', [], $current_language) ?>');
                    // Restore button to its state before click if failed but API gave a different current state
                    if (data.hasOwnProperty('is_locked') && (data.is_locked ? '1' : '0') !== button.dataset.isLocked) {
                         button.dataset.isLocked = data.is_locked ? '1' : '0';
                         if (buttonIcon) buttonIcon.className = `fas ${data.is_locked ? 'fa-unlock-alt' : 'fa-lock'}`;
                         if (buttonTextSpan) buttonTextSpan.textContent = data.is_locked ? '<?= __('button_unlock_topic_short', [], $current_language) ?>' : '<?= __('button_lock_topic_short', [], $current_language) ?>';
                         button.title = data.is_locked ? '<?= __('tooltip_unlock_this_topic', [], $current_language) ?>' : '<?= __('tooltip_lock_this_topic', [], $current_language) ?>';
                    }
                }
            })
            .catch(error => {
                console.error('Error toggling topic lock:', error);
                showGlobalErrorAlert('<?= __('error_topic_lock_toggle_network_js', [], $current_language) ?>');
            })
            .finally(() => {
                button.disabled = false;
                // Restore specific parts instead of full innerHTML to keep spinner logic simple
                const isLockedAfterAttempt = button.dataset.isLocked === '1';
                if (buttonIcon) buttonIcon.className = `fas ${isLockedAfterAttempt ? 'fa-unlock-alt' : 'fa-lock'}`;
                if (buttonTextSpan) buttonTextSpan.textContent = isLockedAfterAttempt ? '<?= __('button_unlock_topic_short', [], $current_language) ?>' : '<?= __('button_lock_topic_short', [], $current_language) ?>';
                // Spinner is removed by not re-adding it here. If you had text only, then set button.innerHTML = originalButtonHTML or specific text.
            });
        });
    }

    function updatePageForLockState(isLocked) {
        const replyToTopicLink = document.getElementById('replyToTopicLink');
        const replyFormSection = document.getElementById('replyForm'); // Assuming the whole section
        const topicLockedStatusMessage = document.getElementById('topicLockedStatusMessage');

        if (replyToTopicLink) {
            if (isLocked) {
                replyToTopicLink.classList.add('disabled');
                replyToTopicLink.setAttribute('aria-disabled', 'true');
            } else {
                replyToTopicLink.classList.remove('disabled');
                replyToTopicLink.removeAttribute('aria-disabled');
            }
        }

        if (replyFormSection) { // Show/hide reply form
             const replyTextarea = replyFormSection.querySelector('textarea');
             const replySubmitButton = replyFormSection.querySelector('button[type="submit"]');
             if (isLocked) {
                 if(replyTextarea) replyTextarea.disabled = true;
                 if(replySubmitButton) replySubmitButton.disabled = true;
                 // replyFormSection.classList.add('d-none'); // Or just disable inputs
             } else {
                 if(replyTextarea) replyTextarea.disabled = false;
                 if(replySubmitButton) replySubmitButton.disabled = false;
                 // replyFormSection.classList.remove('d-none');
             }
        }

        if (topicLockedStatusMessage) {
            if (isLocked) {
                topicLockedStatusMessage.classList.remove('d-none');
            } else {
                topicLockedStatusMessage.classList.add('d-none');
            }
        }

        // Disable/Enable Edit/Delete buttons for all posts
        document.querySelectorAll('.edit-post-btn, .delete-post-btn').forEach(btn => {
            // We only disable them if the topic is locked.
            // Re-enabling them depends on user ownership, which is checked by PHP on page load.
            // So, if topic is unlocked, these buttons become active *if* PHP rendered them (i.e., user is owner).
            // If topic is locked, they are always disabled.
            btn.disabled = isLocked;
            if(isLocked) {
                btn.classList.add('disabled'); // Visual cue for Bootstrap
                btn.setAttribute('aria-disabled', 'true');
            } else {
                // Check if it was originally enabled by PHP (i.e., user is owner)
                // This is tricky without storing original state. For now, just remove disabled.
                // PHP will ensure only owners see enabled buttons initially.
                // If a non-owner admin unlocks, buttons for other users remain hidden/disabled by PHP.
                btn.classList.remove('disabled');
                btn.removeAttribute('aria-disabled');
            }
        });
    }
        const originalButtonText = submitButton.innerHTML;
        submitButton.disabled = true;
        submitButton.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> <?= __('state_text_processing', [], $current_language) ?>`;

        const formData = new FormData();
        formData.append('post_id', postIdToDelete);
        formData.append('csrf_token', '<?= htmlspecialchars(generate_csrf_token(), ENT_QUOTES, 'UTF-8') ?>'); // Get fresh CSRF

        fetch('<?= get_api_route_url('v1/forums/posts/delete') ?>', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                deletePostModal.hide();
                const postElement = document.getElementById('post-' + postIdToDelete);
                if (postElement) {
                    if (data.is_first_post && data.topic_deleted) {
                        // If first post and topic deleted, redirect or show message and remove all posts.
                        // For now, simple page reload or redirect to category.
                        showGlobalSuccessAlert(data.message || '<?= __('success_topic_and_first_post_deleted_js', [], $current_language) ?>');
                        setTimeout(() => window.location.href = '<?= get_route_url('view_category', ['slug' => $category['slug']]) ?>', 2500);
                    } else {
                        // Soft delete effect: fade out and replace content, or just remove
                        postElement.style.opacity = '0.5';
                        postElement.innerHTML = `<div class="card-body text-muted fst-italic p-3"><?= __('text_post_deleted_placeholder', [], $current_language) ?></div>`;
                         showGlobalSuccessAlert(data.message || '<?= __('success_post_deleted_js', [], $current_language) ?>');
                        // Consider decrementing reply count if displayed dynamically
                    }
                }
            } else {
                showGlobalErrorAlert(data.message || '<?= __('error_post_delete_failed_js', [], $current_language) ?>');
            }
        })
        .catch(error => {
            console.error('Error deleting post:', error);
            showGlobalErrorAlert('<?= __('error_post_delete_network_js', [], $current_language) ?>');
        })
        .finally(() => {
            submitButton.disabled = false;
            submitButton.innerHTML = originalButtonText;
            postIdToDelete = null;
        });
    });

    // Helper function to show a global success alert (you might have a more robust system for this)
    function showGlobalSuccessAlert(message) {
        const alertDiv = document.createElement('div');
        alertDiv.className = 'alert alert-success alert-dismissible fade show position-fixed top-0 end-0 m-3 p-3';
        alertDiv.style.zIndex = '1055'; // Ensure it's above modals if any still linger
        alertDiv.setAttribute('role', 'alert');
        alertDiv.innerHTML = `${message}<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>`;
        document.body.appendChild(alertDiv);
        setTimeout(() => bootstrap.Alert.getOrCreateInstance(alertDiv).close(), 5000);
    }

    // Helper function to show a global error alert
    function showGlobalErrorAlert(message) {
        const alertDiv = document.createElement('div');
        alertDiv.className = 'alert alert-danger alert-dismissible fade show position-fixed top-0 end-0 m-3 p-3';
        alertDiv.style.zIndex = '1055';
        alertDiv.setAttribute('role', 'alert');
        alertDiv.innerHTML = `${message}<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>`;
        document.body.appendChild(alertDiv);
        setTimeout(() => bootstrap.Alert.getOrCreateInstance(alertDiv).close(), 7000);
    }
});
</script>

<?php
// Translation placeholders
// __('page_title_forum_topic_default', [], $GLOBALS['current_language'] ?? 'en');
// __('error_forum_no_topic_slug_provided', [], $GLOBALS['current_language'] ?? 'en');
// __('error_forum_topic_not_found', [], $GLOBALS['current_language'] ?? 'en');
// __('error_forums_topic_load_failed_db', [], $GLOBALS['current_language'] ?? 'en');
// __('tooltip_reply_to_topic_soon', [], $GLOBALS['current_language'] ?? 'en');
// __('button_reply_to_topic', [], $GLOBALS['current_language'] ?? 'en');
// __('topic_is_locked_message', [], $GLOBALS['current_language'] ?? 'en');
// __('forum_topic_no_posts_yet', [], $GLOBALS['current_language'] ?? 'en');
// __('forum_topic_be_first_to_reply', [], $GLOBALS['current_language'] ?? 'en');
// __('forum_topic_reply_form_title', [], $GLOBALS['current_language'] ?? 'en');
// __('forum_topic_locked_cannot_reply', [], $GLOBALS['current_language'] ?? 'en');
// __('forum_topic_reply_placeholder', [], $GLOBALS['current_language'] ?? 'en');
// __('forum_topic_markdown_supported_note', [], $GLOBALS['current_language'] ?? 'en');
// __('button_submit_reply', [], $GLOBALS['current_language'] ?? 'en');
// __('forum_reply_feature_stub_note', [], $GLOBALS['current_language'] ?? 'en');
// __('forum_topic_login_to_reply %s', [], $GLOBALS['current_language'] ?? 'en');
// Reused: breadcrumb_pawsconnect_home, error_oops_title, button_back_to_forums_main
?>
