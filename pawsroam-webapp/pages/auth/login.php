<?php
// This page is intended to be included by index.php, which handles:
// - Session start, core includes, $current_language, $pageTitle.

// Safeguards for direct access (not recommended)
if (session_status() == PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_secure' => isset($_SERVER['HTTPS']),
        'cookie_samesite' => 'Lax'
    ]);
}
if (!function_exists('__')) {
    if (file_exists(__DIR__ . '/../../includes/translation.php')) require_once __DIR__ . '/../../includes/translation.php';
    else die('Translation system not loaded.');
}
if (!function_exists('csrf_input_field') || !function_exists('base_url') || !function_exists('e')) {
    if (file_exists(__DIR__ . '/../../includes/functions.php')) require_once __DIR__ . '/../../includes/functions.php';
    else die('Core functions not loaded.');
}
if (!defined('CSRF_TOKEN_NAME')) {
    define('CSRF_TOKEN_NAME', 'csrf_token'); // Fallback
}

// Ensure CSRF token is available for the form
if (empty($_SESSION[CSRF_TOKEN_NAME])) {
    generate_csrf_token(true);
}

// Page specific variables
$pageTitle = __('page_title_login', [], $GLOBALS['current_language'] ?? 'en'); // "Log In to PawsRoam"

// Check for any status messages from redirects (e.g., after registration)
$status_message = '';
$status_type = '';
if (isset($_GET['status'])) {
    if ($_GET['status'] === 'registered') {
        $status_message = __('alert_registration_success', [], $GLOBALS['current_language'] ?? 'en'); // Re-use from common
        $status_type = 'success';
    } elseif ($_GET['status'] === 'logged_out') {
        $status_message = __('alert_logout_success', [], $GLOBALS['current_language'] ?? 'en');
        $status_type = 'success';
    }
    // Add more statuses as needed
}

?>

<div class="container my-4 my-md-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6 col-xl-5">
            <div class="card shadow-lg border-0">
                <div class="card-header bg-primary-orange text-white text-center">
                    <h1 class="h3 mb-0 py-2"><?php echo e($pageTitle); ?></h1>
                </div>
                <div class="card-body p-4 p-md-5">
                    <p class="text-center text-muted mb-4"><?php echo e(__('login_welcome_text', [], $GLOBALS['current_language'] ?? 'en')); ?></p>

                    <form id="loginForm" action="<?php echo e(base_url('/api/v1/auth/login.php')); ?>" method="POST" novalidate>
                        <?php echo csrf_input_field(); ?>

                        <div id="form-messages" class="mb-3" role="alert" aria-live="assertive">
                            <?php if ($status_message): ?>
                                <div class="alert alert-<?php echo e($status_type); ?>"><?php echo e($status_message); ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="form-floating mb-3">
                            <input type="email" class="form-control" id="email" name="email" placeholder="<?php echo e(__('placeholder_email', [], $GLOBALS['current_language'] ?? 'en')); ?>" required autocomplete="email" value="<?php echo e($_GET['email'] ?? ''); /* Pre-fill if redirected with email */ ?>">
                            <label for="email"><?php echo e(__('label_email', [], $GLOBALS['current_language'] ?? 'en')); ?></label>
                            <div class="invalid-feedback" id="emailError"></div>
                        </div>

                        <div class="form-floating mb-3">
                            <input type="password" class="form-control" id="password" name="password" placeholder="<?php echo e(__('placeholder_password', [], $GLOBALS['current_language'] ?? 'en')); ?>" required autocomplete="current-password">
                            <label for="password"><?php echo e(__('label_password', [], $GLOBALS['current_language'] ?? 'en')); ?></label>
                            <div class="invalid-feedback" id="passwordError"></div>
                        </div>

                        <div class="row mb-4">
                            <div class="col">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="1" id="remember_me" name="remember_me">
                                    <label class="form-check-label" for="remember_me">
                                        <?php echo e(__('label_remember_me', [], $GLOBALS['current_language'] ?? 'en')); ?>
                                    </label>
                                </div>
                            </div>
                            <div class="col text-end">
                                <a href="<?php echo e(base_url('/forgot-password')); ?>" class="text-primary-orange small"><?php echo e(__('link_text_forgot_password', [], $GLOBALS['current_language'] ?? 'en')); ?></a>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 btn-lg py-2" id="loginButton">
                            <span class="button-text"><?php echo e(__('button_login', [], $GLOBALS['current_language'] ?? 'en')); ?></span>
                            <span class="spinner-border spinner-border-sm ms-2 d-none" role="status" aria-hidden="true"></span>
                        </button>
                    </form>
                </div>
                <div class="card-footer text-center py-3 bg-light">
                    <p class="mb-0"><?php echo e(__('text_dont_have_account', [], $GLOBALS['current_language'] ?? 'en')); ?> <a href="<?php echo e(base_url('/register')); ?>" class="fw-bold text-primary-orange"><?php echo e(__('link_text_register_now', [], $GLOBALS['current_language'] ?? 'en')); ?></a></p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const loginForm = document.getElementById('loginForm');
    if (!loginForm) return;

    const loginButton = document.getElementById('loginButton');
    const buttonText = loginButton.querySelector('.button-text');
    const spinner = loginButton.querySelector('.spinner-border');
    const formMessages = document.getElementById('form-messages');

    // Pre-fill email if it's in URL params (e.g., from failed attempt or other redirect)
    const urlParams = new URLSearchParams(window.location.search);
    const emailFromUrl = urlParams.get('email');
    if (emailFromUrl && loginForm.email) {
        loginForm.email.value = emailFromUrl;
    }


    function clearValidationUI() {
        loginForm.querySelectorAll('.form-control').forEach(el => el.classList.remove('is-invalid'));
        loginForm.querySelectorAll('.invalid-feedback').forEach(el => el.textContent = '');
        // Clear general form messages, but preserve server-rendered status messages if any
        const dynamicAlert = formMessages.querySelector('.alert-danger, .alert-warning'); // Only remove dynamic alerts
        if (dynamicAlert) dynamicAlert.remove();
    }

    function displayFormMessage(message, type = 'danger', isHtml = false) {
        if (!formMessages) return;
        // Remove previous dynamic messages first
        const existingAlert = formMessages.querySelector('.alert-danger, .alert-warning');
        if (existingAlert) existingAlert.remove();

        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
        alertDiv.setAttribute('role', 'alert');
        alertDiv.innerHTML = `${isHtml ? message : escapeHtml(message)}
                             <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>`;
        formMessages.appendChild(alertDiv); // Append new message
        formMessages.hidden = false;
    }

    function displayFieldErrors(errors) {
        for (const field in errors) {
            const inputElement = loginForm.querySelector(`[name="${field}"]`);
            const errorElement = document.getElementById(`${field}Error`);

            if (inputElement) inputElement.classList.add('is-invalid');
            if (errorElement) errorElement.textContent = errors[field];
        }
    }

    function escapeHtml(unsafe) {
        if (typeof unsafe !== 'string') return '';
        return unsafe
             .replace(/&/g, "&amp;")
             .replace(/</g, "&lt;")
             .replace(/>/g, "&gt;")
             .replace(/"/g, "&quot;")
             .replace(/'/g, "&#039;");
    }

    loginForm.addEventListener('submit', async function (event) {
        event.preventDefault();
        clearValidationUI();

        if (buttonText) buttonText.textContent = '<?php echo e(__('state_text_processing', [], $GLOBALS['current_language'] ?? 'en')); ?>';
        if (spinner) spinner.classList.remove('d-none');
        loginButton.disabled = true;

        const formData = new FormData(loginForm);

        try {
            const response = await fetch(loginForm.action, {
                method: 'POST',
                body: formData,
                headers: { 'Accept': 'application/json' }
            });

            const result = await response.json();

            if (response.ok && result.success) {
                displayFormMessage(result.message || '<?php echo e(__('success_login_redirecting', [], $GLOBALS['current_language'] ?? 'en')); ?>', 'success');
                // Redirect based on API response or to a default dashboard/home
                let redirectTo = result.redirect_url || '<?php echo base_url('/'); ?>'; // Default to homepage
                if (result.user && result.user.role === 'super_admin' || result.user.role === 'business_admin') {
                    // redirectTo = '<?php echo base_url('/admin'); ?>'; // Example: redirect admins to admin dashboard
                }
                // Check for a 'return_to_url' from server (if it was set pre-login)
                if (result.return_to_url) {
                    redirectTo = result.return_to_url;
                }

                setTimeout(() => { window.location.href = redirectTo; }, 1500);

            } else {
                let errorMessage = result.message || '<?php echo e(__('alert_login_failed_credentials', [], $GLOBALS['current_language'] ?? 'en')); ?>';
                if (result.errors) {
                    displayFieldErrors(result.errors);
                    // Focus the first field with an error if possible (usually email or password)
                    const firstErrorField = Object.keys(result.errors)[0];
                     if (firstErrorField && loginForm.querySelector(`[name="${firstErrorField}"]`)) {
                        loginForm.querySelector(`[name="${firstErrorField}"]`).focus();
                    } else if (loginForm.email) {
                        loginForm.email.focus();
                    }
                }
                displayFormMessage(errorMessage, 'danger');
            }
        } catch (error) {
            console.error('Login submission error:', error);
            displayFormMessage('<?php echo e(__('alert_login_failed_network', [], $GLOBALS['current_language'] ?? 'en')); ?>', 'danger');
        } finally {
            if (buttonText) buttonText.textContent = '<?php echo e(__('button_login', [], $GLOBALS['current_language'] ?? 'en')); ?>';
            if (spinner) spinner.classList.add('d-none');
            loginButton.disabled = false;
        }
    });
});
</script>
<?php
// Placeholder for translation strings used in this file
// __('page_title_login', [], $GLOBALS['current_language'] ?? 'en');
// __('login_welcome_text', [], $GLOBALS['current_language'] ?? 'en');
// __('alert_registration_success', [], $GLOBALS['current_language'] ?? 'en'); // Reused
// __('alert_logout_success', [], $GLOBALS['current_language'] ?? 'en');
// __('placeholder_email', [], $GLOBALS['current_language'] ?? 'en'); // Reused
// __('label_email', [], $GLOBALS['current_language'] ?? 'en'); // Reused
// __('placeholder_password', [], $GLOBALS['current_language'] ?? 'en'); // Reused
// __('label_password', [], $GLOBALS['current_language'] ?? 'en'); // Reused
// __('label_remember_me', [], $GLOBALS['current_language'] ?? 'en');
// __('link_text_forgot_password', [], $GLOBALS['current_language'] ?? 'en');
// __('button_login', [], $GLOBALS['current_language'] ?? 'en');
// __('text_dont_have_account', [], $GLOBALS['current_language'] ?? 'en');
// __('link_text_register_now', [], $GLOBALS['current_language'] ?? 'en');
// __('state_text_processing', [], $GLOBALS['current_language'] ?? 'en'); // Reused
// __('success_login_redirecting', [], $GLOBALS['current_language'] ?? 'en');
// __('alert_login_failed_credentials', [], $GLOBALS['current_language'] ?? 'en');
// __('alert_login_failed_network', [], $GLOBALS['current_language'] ?? 'en');
?>
