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
            <?php if (is_logged_in() && !$topic['is_locked']): ?>
            <a href="#replyForm" class="btn btn-primary shadow-sm disabled" title="<?php echo e(__('tooltip_reply_to_topic_soon', [], $GLOBALS['current_language'] ?? 'en')); // "Reply to this topic (Feature coming soon)" ?>" aria-disabled="true">
                <i class="bi bi-reply-fill me-2"></i><?php echo e(__('button_reply_to_topic', [], $GLOBALS['current_language'] ?? 'en')); // "Reply to Topic" ?>
            </a>
            <?php elseif ($topic['is_locked']): ?>
            <span class="badge bg-warning text-dark p-2"><i class="bi bi-lock-fill me-1"></i> <?php echo e(__('topic_is_locked_message', [], $GLOBALS['current_language'] ?? 'en')); // "Topic Locked" ?></span>
            <?php endif; ?>
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
                        </div>
                        <small class="text-muted">#<?php echo e($post['id']); // Post ID or running number ?></small>
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
                            <textarea class="form-control" name="content" rows="5" placeholder="<?php echo e(__('forum_topic_reply_placeholder', [], $GLOBALS['current_language'] ?? 'en')); // "Enter your reply..." ?>" required disabled></textarea>
                            <small class="form-text text-muted"><?php echo e(__('forum_topic_markdown_supported_note', [], $GLOBALS['current_language'] ?? 'en')); // "Basic Markdown is supported. (Feature coming soon)" ?></small>
                        </div>
                        <button type="submit" class="btn btn-primary disabled" aria-disabled="true"><?php echo e(__('button_submit_reply', [], $GLOBALS['current_language'] ?? 'en')); // "Submit Reply" ?></button>
                         <small class="ms-2 text-muted"><?php echo e(__('forum_reply_feature_stub_note', [], $GLOBALS['current_language'] ?? 'en')); // "(Reply functionality is a stub)" ?></small>
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
</div>
<style>.forum-post-content { white-space: pre-wrap; word-break: break-word; }</style>
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
