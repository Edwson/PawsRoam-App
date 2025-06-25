<?php
require_login();

$pageTitle = __('page_title_new_topic', [], $GLOBALS['current_language'] ?? 'en'); // "Start a New Discussion Topic"
$categories = [];
$error_message = null;
$preselected_category_id = filter_var($_GET['category_id'] ?? null, FILTER_VALIDATE_INT);

try {
    $db = Database::getInstance()->getConnection();
    $stmt_cat = $db->query("SELECT id, name FROM forum_categories ORDER BY sort_order ASC, name ASC");
    if ($stmt_cat) {
        $categories = $stmt_cat->fetchAll(PDO::FETCH_ASSOC);
    } else {
        error_log("New Topic Page: Failed to prepare or execute query for forum_categories.");
        $error_message = __('error_forums_categories_load_failed', [], $GLOBALS['current_language'] ?? 'en'); // "Could not load categories for new topic."
    }
    if (empty($categories) && !$error_message) {
         $error_message = __('error_forums_no_categories_to_post_in', [], $GLOBALS['current_language'] ?? 'en'); // "No categories available to post a topic in."
    }

} catch (PDOException $e) {
    error_log("Database error fetching categories for new topic page: " . $e->getMessage());
    $error_message = __('error_forums_categories_load_failed', [], $GLOBALS['current_language'] ?? 'en');
}

if (empty($_SESSION[CSRF_TOKEN_NAME ?? 'csrf_token'])) { generate_csrf_token(true); }
?>

<div class="container my-4 my-md-5">
    <div class="row mb-3 align-items-center">
        <div class="col">
            <h1 class="display-6 fw-bold"><?php echo e($pageTitle); ?></h1>
        </div>
        <div class="col text-end">
             <a href="<?php echo e(base_url('/pawsconnect')); ?>" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left-circle me-1"></i><?php echo e(__('button_back_to_forums_main', [], $GLOBALS['current_language'] ?? 'en')); ?>
            </a>
        </div>
    </div>

    <?php if ($error_message): ?>
        <div class="alert alert-danger" role="alert"><i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo e($error_message); ?></div>
    <?php elseif (empty($categories)): ?>
         <div class="alert alert-warning" role="alert"><i class="bi bi-exclamation-circle-fill me-2"></i><?php echo e(__('error_forums_no_categories_to_post_in', [], $GLOBALS['current_language'] ?? 'en')); ?></div>
    <?php else: ?>
    <div class="card shadow-lg border-0">
        <div class="card-header bg-primary-orange text-white py-3">
            <h2 class="h4 mb-0"><i class="bi bi-chat-plus-fill me-2"></i><?php echo e(__('new_topic_form_title', [], $GLOBALS['current_language'] ?? 'en')); // "Create Your Topic" ?></h2>
        </div>
        <div class="card-body p-4 p-md-5">
            <form id="newTopicForm" action="<?php echo e(base_url('/api/v1/forums/topics/create.php')); ?>" method="POST" novalidate>
                <?php echo csrf_input_field(); ?>
                <div id="newTopicFormMessages" class="mb-3" role="alert" aria-live="assertive"></div>

                <div class="form-floating mb-3">
                    <select class="form-select" id="category_id" name="category_id" required>
                        <option value="" disabled <?php echo !$preselected_category_id ? 'selected' : ''; ?>><?php echo e(__('new_topic_select_category_placeholder', [], $GLOBALS['current_language'] ?? 'en')); // "-- Select a Category --" ?></option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo e($category['id']); ?>" <?php echo ($preselected_category_id == $category['id']) ? 'selected' : ''; ?>>
                                <?php echo e($category['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <label for="category_id"><?php echo e(__('new_topic_label_category', [], $GLOBALS['current_language'] ?? 'en')); // "Category" ?></label>
                    <div class="invalid-feedback" id="category_idError"></div>
                </div>

                <div class="form-floating mb-3">
                    <input type="text" class="form-control" id="topic_title" name="title" placeholder="<?php echo e(__('new_topic_placeholder_title', [], $GLOBALS['current_language'] ?? 'en')); // "Enter a descriptive title for your topic" ?>" required minlength="5" maxlength="255">
                    <label for="topic_title"><?php echo e(__('new_topic_label_title', [], $GLOBALS['current_language'] ?? 'en')); // "Topic Title" ?></label>
                    <div class="invalid-feedback" id="titleError"></div>
                </div>

                <div class="form-floating mb-3">
                    <textarea class="form-control" id="topic_content" name="content" style="height: 200px" placeholder="<?php echo e(__('new_topic_placeholder_content', [], $GLOBALS['current_language'] ?? 'en')); // "Start typing your message or question here..." ?>" required minlength="10"></textarea>
                    <label for="topic_content"><?php echo e(__('new_topic_label_content', [], $GLOBALS['current_language'] ?? 'en')); // "Your Message (First Post)" ?></label>
                    <small class="form-text text-muted"><?php echo e(__('forum_topic_markdown_supported_note', [], $GLOBALS['current_language'] ?? 'en')); ?></small>
                    <div class="invalid-feedback" id="contentError"></div>
                </div>

                <div class="mt-4 pt-2">
                    <button type="submit" class="btn btn-primary btn-lg px-5" id="submitNewTopicBtn">
                        <span class="button-text"><?php echo e(__('button_create_topic', [], $GLOBALS['current_language'] ?? 'en')); // "Create Topic" ?></span>
                        <span class="spinner-border spinner-border-sm ms-2 d-none" role="status" aria-hidden="true"></span>
                    </button>
                    <a href="<?php echo e(base_url($preselected_category_id && isset($categories) ? '/forums/category/' . ($categories[array_search($preselected_category_id, array_column($categories, 'id'))]['slug'] ?? '') : '/pawsconnect')); ?>" class="btn btn-link text-muted ms-2"><?php echo e(__('button_cancel', [], $GLOBALS['current_language'] ?? 'en')); ?></a>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const newTopicForm = document.getElementById('newTopicForm');
    if (!newTopicForm) return;

    const submitBtn = document.getElementById('submitNewTopicBtn');
    const btnText = submitBtn.querySelector('.button-text');
    const spinner = submitBtn.querySelector('.spinner-border');
    const formMessages = document.getElementById('newTopicFormMessages');

    function clearValidationUI() { /* ... */ } // Define or reuse
    function displayFormMessage(message, type = 'danger', isHtml = false) { /* ... */ } // Define or reuse
    function displayFieldErrors(errors) { /* ... */ } // Define or reuse
    function escapeHtml(unsafe) { /* ... */ } // Define or reuse

    newTopicForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        // clearValidationUI(); // Call helper

        if(btnText) btnText.textContent = '<?php echo e(addslashes(__('state_text_processing', [], $GLOBALS['current_language'] ?? 'en'))); ?>';
        if(spinner) spinner.classList.remove('d-none');
        submitBtn.disabled = true;

        const formData = new FormData(newTopicForm);
        try {
            const response = await fetch(newTopicForm.action, {
                method: 'POST', body: formData, headers: {'Accept': 'application/json'}
            });
            const result = await response.json();

            if (response.ok && result.success) {
                // displayFormMessage(result.message || 'Topic created successfully!', 'success'); // Or redirect
                window.location.href = `<?php echo e(base_url('/forums/topic/')); ?>${result.topic_slug}`;
            } else {
                let errMsg = result.message || '<?php echo e(addslashes(__('new_topic_error_unknown', [], $GLOBALS['current_language'] ?? 'en' ))); // "Failed to create topic. Please check errors." ?>';
                if (result.errors) {
                    // displayFieldErrors(result.errors); // Call helper
                    let errorText = "<?php echo e(addslashes(__('error_validation_summary', [], $GLOBALS['current_language'] ?? 'en' ))); ?>\n";
                    for(const field in result.errors){ errorText += `- ${result.errors[field]}\n`;}
                    displayFormMessage(errorText.replace(/\n/g, '<br>'), 'danger', true);
                } else {
                    displayFormMessage(errMsg, 'danger');
                }
            }
        } catch (error) {
            console.error("New topic submission error:", error);
            displayFormMessage('<?php echo e(addslashes(__('new_topic_error_network', [], $GLOBALS['current_language'] ?? 'en' ))); // "Network error creating topic." ?>', 'danger');
        } finally {
            if(btnText) btnText.textContent = '<?php echo e(addslashes(__('button_create_topic', [], $GLOBALS['current_language'] ?? 'en'))); ?>';
            if(spinner) spinner.classList.add('d-none');
            submitBtn.disabled = false;
        }
    });
    // Define helper functions (clearValidationUI, displayFormMessage, displayFieldErrors, escapeHtml) if not global
    function escapeHtml(unsafe) { if (typeof unsafe !== 'string') return ''; return unsafe.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;"); }
    function displayFormMessage(message, type = 'danger', isHtml = false) {if (!formMessages) return; formMessages.innerHTML = `<div class="alert alert-${type} alert-dismissible fade show" role="alert">${isHtml ? message : escapeHtml(message)}<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>`;}

});
</script>
<?php
// Translation Placeholders
// __('page_title_new_topic', [], $GLOBALS['current_language'] ?? 'en');
// __('error_forums_categories_load_failed', [], $GLOBALS['current_language'] ?? 'en');
// __('error_forums_no_categories_to_post_in', [], $GLOBALS['current_language'] ?? 'en');
// __('new_topic_form_title', [], $GLOBALS['current_language'] ?? 'en');
// __('new_topic_select_category_placeholder', [], $GLOBALS['current_language'] ?? 'en');
// __('new_topic_label_category', [], $GLOBALS['current_language'] ?? 'en');
// __('new_topic_placeholder_title', [], $GLOBALS['current_language'] ?? 'en');
// __('new_topic_label_title', [], $GLOBALS['current_language'] ?? 'en');
// __('new_topic_placeholder_content', [], $GLOBALS['current_language'] ?? 'en');
// __('new_topic_label_content', [], $GLOBALS['current_language'] ?? 'en');
// __('button_create_topic', [], $GLOBALS['current_language'] ?? 'en');
// __('new_topic_error_unknown', [], $GLOBALS['current_language'] ?? 'en');
// __('new_topic_error_network', [], $GLOBALS['current_language'] ?? 'en');
?>
