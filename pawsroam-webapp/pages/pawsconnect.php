<?php
// PawsConnect Community Main Page
// This page is intended to be included by index.php.

$pageTitle = __('page_title_pawsconnect_main', [], $GLOBALS['current_language'] ?? 'en');
$categories = [];
$error_message = null;

try {
    $db = Database::getInstance()->getConnection();
    // Ensure forum_categories table exists before querying
    // A more robust check might involve checking table existence if this is the very first run after schema setup
    $stmt = $db->query("SELECT id, name, slug, description FROM forum_categories ORDER BY sort_order ASC, name ASC");
    if ($stmt) {
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        // This case might occur if the table doesn't exist yet or there's a query error
        // For a production system, more specific error handling based on $db->errorInfo() would be good.
        error_log("PawsConnect Main Page: Failed to prepare or execute query for forum_categories.");
        $error_message = __('error_forums_load_failed_db', [], $GLOBALS['current_language'] ?? 'en');
    }
} catch (PDOException $e) {
    error_log("Database error fetching forum categories: " . $e->getMessage());
    // Check if it's a "table not found" error (MySQL error code 1146)
    if ($e->getCode() == '42S02' || (isset($e->errorInfo[1]) && $e->errorInfo[1] == 1146)) {
        $error_message = __('error_forums_not_setup_yet', [], $GLOBALS['current_language'] ?? 'en'); // "Forums are not yet set up. Please check back soon!"
    } else {
        $error_message = __('error_forums_load_failed_db', [], $GLOBALS['current_language'] ?? 'en');
    }
}

// For translated names/descriptions (future integration with translations table):
// This loop assumes that $categories is an array and each $category is an array.
// It's safer to check if $categories is indeed an array and not false/null.
if (is_array($categories)) {
    foreach ($categories as &$category) { // Use reference to modify original array elements
        // Assuming get_translated_field is a function you'll define or have
        // $category['name'] = get_translated_field('forum_category', $category['id'], 'name', $GLOBALS['current_language'] ?? 'en', $category['name']);
        // $category['description'] = get_translated_field('forum_category', $category['id'], 'description', $GLOBALS['current_language'] ?? 'en', $category['description']);
    }
    unset($category); // Important to unset reference after loop
}

?>

<div class="container my-4 my-md-5">
    <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
        <h1 class="display-5 fw-bold text-primary-orange"><?php echo e($pageTitle); ?></h1>
        <?php if (is_logged_in()): ?>
        <a href="<?php echo e(base_url('/forums/new-topic')); ?>" class="btn btn-primary btn-lg shadow-sm disabled" title="<?php echo e(__('tooltip_start_new_discussion_soon', [], $GLOBALS['current_language'] ?? 'en')); ?>" aria-disabled="true">
            <i class="bi bi-plus-circle-fill me-2"></i><?php echo e(__('button_start_new_discussion', [], $GLOBALS['current_language'] ?? 'en')); ?>
        </a>
        <?php else: ?>
        <a href="<?php echo e(base_url('/login?return_to=' . urlencode(base_url('/pawsconnect')))); ?>" class="btn btn-outline-primary btn-lg shadow-sm">
            <i class="bi bi-box-arrow-in-right me-2"></i><?php echo e(__('button_login_to_participate', [], $GLOBALS['current_language'] ?? 'en')); ?>
        </a>
        <?php endif; ?>
    </div>

    <?php if ($error_message): ?>
        <div class="alert alert-warning" role="alert"><i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo e($error_message); ?></div>
    <?php elseif (empty($categories)): ?>
        <div class="alert alert-info text-center shadow-sm py-5 rounded">
            <i class="bi bi-info-circle-fill fs-1 mb-3 d-block text-primary-blue"></i>
            <h4 class="alert-heading"><?php echo e(__('forums_no_categories_title', [], $GLOBALS['current_language'] ?? 'en')); ?></h4>
            <p><?php echo e(__('forums_no_categories_message', [], $GLOBALS['current_language'] ?? 'en')); ?></p>
        </div>
    <?php else: ?>
        <p class="lead text-muted mb-4 text-center col-md-10 mx-auto"><?php echo e(__('forums_welcome_intro', [], $GLOBALS['current_language'] ?? 'en')); ?></p>
        <div class="list-group shadow-sm rounded">
            <?php foreach ($categories as $category): ?>
                <a href="<?php echo e(base_url('/forums/category/' . e($category['slug']))); ?>" class="list-group-item list-group-item-action flex-column align-items-start py-3 px-4 category-link">
                    <div class="d-flex w-100 justify-content-between">
                        <h5 class="mb-1 text-primary-orange fw-bold"><?php echo e($category['name']); ?></h5>
                        <?php /* Placeholder for stats like topic/post count
                        <small class="text-muted">
                            <?php // echo sprintf(__('%d topics / %d posts', [], $GLOBALS['current_language'] ?? 'en'), $category['topic_count'] ?? 0, $category['post_count'] ?? 0); ?>
                        </small>
                        */ ?>
                    </div>
                    <p class="mb-1 text-muted"><?php echo nl2br(e($category['description'] ?? __('forum_category_no_description', [], $GLOBALS['current_language'] ?? 'en'))); ?></p>
                    <?php /* Placeholder for last post info
                    <small class="text-muted">Last post in "Topic Title" by UserX on Date</small>
                    */ ?>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
<style>
    .category-link:hover h5 { text-decoration: underline; }
</style>
<?php
// Translation placeholders
// __('page_title_pawsconnect_main', [], $GLOBALS['current_language'] ?? 'en');
// __('error_forums_load_failed_db', [], $GLOBALS['current_language'] ?? 'en');
// __('error_forums_not_setup_yet', [], $GLOBALS['current_language'] ?? 'en');
// __('tooltip_start_new_discussion_soon', [], $GLOBALS['current_language'] ?? 'en');
// __('button_start_new_discussion', [], $GLOBALS['current_language'] ?? 'en');
// __('button_login_to_participate', [], $GLOBALS['current_language'] ?? 'en');
// __('forums_no_categories_title', [], $GLOBALS['current_language'] ?? 'en');
// __('forums_no_categories_message', [], $GLOBALS['current_language'] ?? 'en');
// __('forums_welcome_intro', [], $GLOBALS['current_language'] ?? 'en');
// __('%d topics / %d posts', [], $GLOBALS['current_language'] ?? 'en');
// __('forum_category_no_description', [], $GLOBALS['current_language'] ?? 'en');
?>
