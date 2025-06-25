<?php
// Forum Category View Page
// Included by index.php

$pageTitle = __('page_title_forum_category_default', [], $GLOBALS['current_language'] ?? 'en'); // "Forum Category"
$category_slug = $_GET['category_slug'] ?? null; // Set by router in index.php
$category = null;
$topics = [];
$pagination = ['current_page' => 1, 'total_pages' => 1, 'total_items' => 0, 'items_per_page' => 20]; // Default pagination
$error_message = null;

if (empty($category_slug)) {
    $error_message = __('error_forum_no_category_slug_provided', [], $GLOBALS['current_language'] ?? 'en'); // "No category specified."
    http_response_code(400);
} else {
    try {
        $db = Database::getInstance()->getConnection();

        // Fetch category details
        $stmt_cat = $db->prepare("SELECT id, name, slug, description FROM forum_categories WHERE slug = :slug LIMIT 1");
        $stmt_cat->bindParam(':slug', $category_slug);
        $stmt_cat->execute();
        $category = $stmt_cat->fetch(PDO::FETCH_ASSOC);

        if (!$category) {
            $error_message = __('error_forum_category_not_found', [], $GLOBALS['current_language'] ?? 'en'); // "The requested forum category was not found."
            http_response_code(404);
        } else {
            $pageTitle = e($category['name']) . " - " . __('page_title_forum_category', [], $GLOBALS['current_language'] ?? 'en'); // "[Category Name] - Forum Category"

            // Fetch topics for this category with pagination
            $current_page = filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT, ['options' => ['default' => 1, 'min_range' => 1]]);
            $items_per_page = 20; // Define items per page
            $offset = ($current_page - 1) * $items_per_page;

            // Count total topics in this category
            $stmt_topic_count = $db->prepare("SELECT COUNT(*) FROM forum_topics WHERE category_id = :category_id");
            $stmt_topic_count->bindParam(':category_id', $category['id'], PDO::PARAM_INT);
            $stmt_topic_count->execute();
            $total_topics = (int)$stmt_topic_count->fetchColumn();

            $pagination['total_items'] = $total_topics;
            $pagination['items_per_page'] = $items_per_page;
            $pagination['current_page'] = $current_page;
            $pagination['total_pages'] = ceil($total_topics / $items_per_page);


            $sql_topics = "SELECT
                                ft.id, ft.title, ft.slug, ft.post_count, ft.view_count, ft.created_at as topic_created_at, ft.updated_at as topic_updated_at,
                                ft.is_sticky, ft.is_locked,
                                u.username as author_username, u.id as author_id,
                                lp.created_at as last_post_created_at,
                                lpu.username as last_post_author_username, lpu.id as last_post_author_id
                           FROM forum_topics ft
                           JOIN users u ON ft.user_id = u.id
                           LEFT JOIN forum_posts lp ON ft.last_post_id = lp.id
                           LEFT JOIN users lpu ON lp.user_id = lpu.id
                           WHERE ft.category_id = :category_id
                           ORDER BY ft.is_sticky DESC, COALESCE(lp.created_at, ft.updated_at) DESC
                           LIMIT :limit OFFSET :offset";
                           // Order by sticky, then by last post time (or topic update time if no posts yet)

            $stmt_topics = $db->prepare($sql_topics);
            $stmt_topics->bindParam(':category_id', $category['id'], PDO::PARAM_INT);
            $stmt_topics->bindParam(':limit', $items_per_page, PDO::PARAM_INT);
            $stmt_topics->bindParam(':offset', $offset, PDO::PARAM_INT);
            $stmt_topics->execute();
            $topics = $stmt_topics->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $e) {
        error_log("Database error on category view page (Slug: {$category_slug}): " . $e->getMessage());
        $error_message = __('error_forums_load_failed_db', [], $GLOBALS['current_language'] ?? 'en');
        // http_response_code(500); // Already set by index.php if this is caught there
    }
}
?>

<div class="container my-4 my-md-5">
    <?php if ($error_message): ?>
        <div class="alert alert-danger" role="alert">
            <h4 class="alert-heading"><?php echo e(__('error_oops_title', [], $GLOBALS['current_language'] ?? 'en')); ?></h4>
            <p><?php echo e($error_message); ?></p>
            <a href="<?php echo e(base_url('/pawsconnect')); ?>" class="btn btn-primary"><?php echo e(__('button_back_to_forums_main', [], $GLOBALS['current_language'] ?? 'en')); // "Back to Forums" ?></a>
        </div>
    <?php elseif ($category): ?>
        <nav aria-label="breadcrumb" class="mb-3">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo e(base_url('/pawsconnect')); ?>"><?php echo e(__('breadcrumb_pawsconnect_home', [], $GLOBALS['current_language'] ?? 'en')); // "PawsConnect" ?></a></li>
                <li class="breadcrumb-item active" aria-current="page"><?php echo e($category['name']); ?></li>
            </ol>
        </nav>

        <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
            <div>
                <h1 class="display-6 fw-bold text-primary-orange"><?php echo e($category['name']); ?></h1>
                <?php if (!empty($category['description'])): ?>
                    <p class="lead text-muted fs-6"><?php echo nl2br(e($category['description'])); ?></p>
                <?php endif; ?>
            </div>
            <?php if(is_logged_in()): ?>
            <a href="<?php echo e(base_url('/forums/new-topic?category_id=' . $category['id'])); ?>" class="btn btn-primary shadow-sm" title="<?php echo e(sprintf(__('tooltip_start_new_topic_in_category %s', [], $GLOBALS['current_language'] ?? 'en'), e($category['name']))); // "Start a new topic in [Category Name]" ?>">
                <i class="bi bi-plus-lg me-2"></i><?php echo e(__('button_new_topic', [], $GLOBALS['current_language'] ?? 'en')); // "New Topic" ?>
            </a>
            <?php endif; ?>
        </div>

        <?php if (empty($topics) && $pagination['total_items'] === 0): ?>
            <div class="alert alert-info text-center shadow-sm py-4">
                <i class="bi bi-chat-square-dots-fill fs-1 mb-3 d-block text-primary-blue"></i>
                <h4 class="alert-heading"><?php echo e(__('forum_category_no_topics_title', [], $GLOBALS['current_language'] ?? 'en')); // "No Topics Yet!" ?></h4>
                <p><?php echo e(__('forum_category_no_topics_message', [], $GLOBALS['current_language'] ?? 'en')); // "There are no topics in this category yet. Why not start the first one?" ?></p>
                <?php if(is_logged_in()): ?>
                 <a href="<?php echo e(base_url('/forums/new-topic?category_id=' . $category['id'])); ?>" class="btn btn-success mt-2" title="<?php echo e(__('tooltip_be_the_first_to_post_topic', [], $GLOBALS['current_language'] ?? 'en')); // "Be the first to post a topic in this category!" ?>">
                    <i class="bi bi-chat-plus-fill me-2"></i><?php echo e(__('button_start_first_topic', [], $GLOBALS['current_language'] ?? 'en')); // "Start the First Topic" ?>
                 </a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="list-group shadow-sm rounded forum-topic-list">
                <?php foreach ($topics as $topic): ?>
                    <a href="<?php echo e(base_url('/forums/topic/' . e($topic['slug']))); ?>" class="list-group-item list-group-item-action py-3 px-4">
                        <div class="d-flex w-100 justify-content-between">
                            <h5 class="mb-1 fw-semibold topic-title">
                                <?php if ($topic['is_sticky']): ?><i class="bi bi-pin-angle-fill text-success me-1" title="<?php echo e(__('topic_sticky_tooltip', [], $GLOBALS['current_language'] ?? 'en')); // "Sticky Topic" ?>"></i><?php endif; ?>
                                <?php if ($topic['is_locked']): ?><i class="bi bi-lock-fill text-danger me-1" title="<?php echo e(__('topic_locked_tooltip', [], $GLOBALS['current_language'] ?? 'en')); // "Locked Topic" ?>"></i><?php endif; ?>
                                <?php echo e($topic['title']); ?>
                            </h5>
                            <small class="text-muted text-nowrap"><?php echo e(sprintf(__('%d views', [], $GLOBALS['current_language'] ?? 'en'), $topic['view_count'])); ?></small>
                        </div>
                        <div class="d-flex w-100 justify-content-between align-items-center">
                            <small class="text-muted">
                                <?php echo e(__('topic_started_by %s', [], $GLOBALS['current_language'] ?? 'en')); // sprintf("Started by %s", e($topic['author_username'])) ?>
                                on <?php echo e(date("M j, Y", strtotime($topic['topic_created_at']))); ?>
                                &bull; <?php echo e(sprintf(__('%d replies', [], $GLOBALS['current_language'] ?? 'en'), (int)$topic['post_count'] > 0 ? (int)$topic['post_count']-1 : 0 )); ?>
                            </small>
                            <?php if (!empty($topic['last_post_created_at'])): ?>
                            <small class="text-muted text-end">
                                <?php echo e(__('topic_last_post_by %s', [], $GLOBALS['current_language'] ?? 'en')); // sprintf("Last post by %s", e($topic['last_post_author_username'] ?? 'N/A')) ?>
                                <br class="d-md-none"> <?php echo e(date("M j, Y g:ia", strtotime($topic['last_post_created_at']))); ?>
                            </small>
                            <?php else: ?>
                            <small class="text-muted text-end"><?php echo e(__('topic_no_replies_yet', [], $GLOBALS['current_language'] ?? 'en')); // "No replies yet" ?></small>
                            <?php endif; ?>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>

            <!-- Pagination (Stub) -->
            <?php if ($pagination['total_pages'] > 1): ?>
            <nav aria-label="Topics pagination" class="mt-4 d-flex justify-content-center">
                <ul class="pagination">
                    <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                        <li class="page-item <?php echo ($i == $pagination['current_page']) ? 'active' : ''; ?>">
                            <a class="page-link" href="<?php echo e(base_url('/forums/category/' . e($category['slug']) . '?page=' . $i)); ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
            <?php endif; ?>
        <?php endif; ?>
    <?php endif; ?>
</div>
<style>.topic-title:hover { text-decoration: underline; }</style>
<?php
// Translation placeholders
// __('page_title_forum_category_default', [], $GLOBALS['current_language'] ?? 'en');
// __('error_forum_no_category_slug_provided', [], $GLOBALS['current_language'] ?? 'en');
// __('error_forum_category_not_found', [], $GLOBALS['current_language'] ?? 'en');
// __('page_title_forum_category', [], $GLOBALS['current_language'] ?? 'en');
// __('button_back_to_forums_main', [], $GLOBALS['current_language'] ?? 'en');
// __('breadcrumb_pawsconnect_home', [], $GLOBALS['current_language'] ?? 'en');
// __('tooltip_start_new_topic_in_category_soon %s', [], $GLOBALS['current_language'] ?? 'en');
// __('button_new_topic', [], $GLOBALS['current_language'] ?? 'en');
// __('forum_category_no_topics_title', [], $GLOBALS['current_language'] ?? 'en');
// __('forum_category_no_topics_message', [], $GLOBALS['current_language'] ?? 'en');
// __('tooltip_be_the_first_to_post_topic_soon', [], $GLOBALS['current_language'] ?? 'en');
// __('button_start_first_topic', [], $GLOBALS['current_language'] ?? 'en');
// __('topic_sticky_tooltip', [], $GLOBALS['current_language'] ?? 'en');
// __('topic_locked_tooltip', [], $GLOBALS['current_language'] ?? 'en');
// __('%d views', [], $GLOBALS['current_language'] ?? 'en');
// __('topic_started_by %s', [], $GLOBALS['current_language'] ?? 'en');
// __('%d replies', [], $GLOBALS['current_language'] ?? 'en');
// __('topic_last_post_by %s', [], $GLOBALS['current_language'] ?? 'en');
// __('topic_no_replies_yet', [], $GLOBALS['current_language'] ?? 'en');
?>
