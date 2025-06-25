<?php
// This page is intended to be included by index.php, which handles:
// - Session start (session_start())
// - Loading of core includes (constants.php, functions.php, translation.php, auth.php)
// - Setting $current_language
// - Setting $pageTitle (can be overridden here)

// Safeguards if this file is accessed directly (not recommended for production)
if (session_status() == PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_secure' => isset($_SERVER['HTTPS']),
        'cookie_samesite' => 'Lax'
    ]);
}
// Ensure essential functions and variables are available
if (!function_exists('__')) {
    // Attempt to load, but this path might be incorrect if functions.php isn't where expected relative to this file
    // This highlights why a central bootstrap (index.php) is crucial.
    if (file_exists(__DIR__ . '/../../includes/translation.php')) require_once __DIR__ . '/../../includes/translation.php';
    else die('Translation system not loaded.');
}
if (!function_exists('csrf_input_field') || !function_exists('base_url') || !function_exists('e')) {
    if (file_exists(__DIR__ . '/../../includes/functions.php')) require_once __DIR__ . '/../../includes/functions.php';
    else die('Core functions not loaded.');
}
if (!defined('CSRF_TOKEN_NAME')) {
    // Define a fallback if constants.php wasn't loaded by index.php
    define('CSRF_TOKEN_NAME', 'csrf_token');
}


// Generate CSRF token specifically for this form instance if not already available in session
// It's often better to generate it once per session or on specific triggers.
// For this form, ensure it's available.
if (empty($_SESSION[CSRF_TOKEN_NAME])) {
    generate_csrf_token(true); // Force regenerate if not set, to ensure form always has one
}


// Page specific variables
$pageTitle = __('page_title_register', [], $GLOBALS['current_language'] ?? 'en'); // "Register - PawsRoam"

// Note: The actual HTML <head>, <body> tags, and header/footer inclusions
// are expected to be handled by a global header.php and footer.php,
// which are included by the main index.php controller.
// This file should primarily output the content unique to the registration page.
?>

<div class="container my-4 my-md-5">
    <div class="row justify-content-center">
        <div class="col-md-9 col-lg-7 col-xl-6">
            <div class="card shadow-lg border-0">
                <div class="card-header bg-primary-orange text-white text-center">
                    <h1 class="h3 mb-0 py-2"><?php echo e($pageTitle); ?></h1>
                </div>
                <div class="card-body p-4 p-md-5">
                    <p class="text-center text-muted mb-4"><?php echo __('register_join_community_text', [], $GLOBALS['current_language'] ?? 'en'); // "Create your account to join our amazing pet-loving community!" ?></p>

                    <form id="registerForm" action="<?php echo e(base_url('/api/v1/auth/register.php')); ?>" method="POST" novalidate>
                        <?php echo csrf_input_field(); // Outputs the hidden CSRF token input ?>

                        <div id="form-messages" class="mb-3" role="alert" aria-live="assertive">
                            <!-- Dynamic messages (success/error) will be injected here by JavaScript -->
                        </div>

                        <div class="form-floating mb-3">
                            <input type="text" class="form-control" id="username" name="username" placeholder="<?php echo e(__('placeholder_username', [], $GLOBALS['current_language'] ?? 'en')); ?>" required autocomplete="username" minlength="3" maxlength="25" pattern="^[a-zA-Z0-9_]+$">
                            <label for="username"><?php echo e(__('label_username', [], $GLOBALS['current_language'] ?? 'en')); // "Username" ?></label>
                            <small class="form-text text-muted ps-1"><?php echo e(__('help_text_username', [], $GLOBALS['current_language'] ?? 'en')); // "3-25 characters, letters, numbers, and underscores only." ?></small>
                            <div class="invalid-feedback" id="usernameError"></div>
                        </div>

                        <div class="form-floating mb-3">
                            <input type="email" class="form-control" id="email" name="email" placeholder="<?php echo e(__('placeholder_email', [], $GLOBALS['current_language'] ?? 'en')); ?>" required autocomplete="email">
                            <label for="email"><?php echo e(__('label_email', [], $GLOBALS['current_language'] ?? 'en')); // "Email Address" ?></label>
                            <div class="invalid-feedback" id="emailError"></div>
                        </div>

                        <div class="form-floating mb-3">
                            <input type="password" class="form-control" id="password" name="password" placeholder="<?php echo e(__('placeholder_password', [], $GLOBALS['current_language'] ?? 'en')); ?>" required autocomplete="new-password" minlength="8">
                            <label for="password"><?php echo e(__('label_password', [], $GLOBALS['current_language'] ?? 'en')); // "Password" ?></label>
                            <small class="form-text text-muted ps-1"><?php echo e(__('help_text_password', [], $GLOBALS['current_language'] ?? 'en')); // "Min. 8 characters. Include uppercase, lowercase, number, and symbol for strength." ?></small>
                            <div class="invalid-feedback" id="passwordError"></div>
                        </div>

                        <div class="form-floating mb-4">
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="<?php echo e(__('placeholder_confirm_password', [], $GLOBALS['current_language'] ?? 'en')); ?>" required autocomplete="new-password" minlength="8">
                            <label for="confirm_password"><?php echo e(__('label_confirm_password', [], $GLOBALS['current_language'] ?? 'en')); // "Confirm Password" ?></label>
                            <div class="invalid-feedback" id="confirm_passwordError"></div>
                        </div>

                        <div class="form-group mb-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="" id="agree_terms" name="agree_terms" required>
                                <label class="form-check-label" for="agree_terms">
                                    <?php
                                    // Example for dynamic links (ensure these pages exist or are routed)
                                    $terms_page_url = base_url('/terms');
                                    $privacy_page_url = base_url('/privacy');

                                    $terms_link = '<a href="' . e($terms_page_url) . '" target="_blank" class="text-primary-orange">' . e(__('link_text_terms', [], $GLOBALS['current_language'] ?? 'en')) . '</a>'; // "Terms of Service"
                                    $privacy_link = '<a href="' . e($privacy_page_url) . '" target="_blank" class="text-primary-orange">' . e(__('link_text_privacy', [], $GLOBALS['current_language'] ?? 'en')) . '</a>'; // "Privacy Policy"

                                    // Using sprintf for better translation flexibility if order of links changes.
                                    echo sprintf(
                                        e(__('label_agree_terms_with_links %s %s', [], $GLOBALS['current_language'] ?? 'en')), // "I agree to the %s and the %s."
                                        $terms_link,
                                        $privacy_link
                                    );
                                    ?>
                                </label>
                                <div class="invalid-feedback" id="agree_termsError"></div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 btn-lg py-2" id="registerButton">
                            <span class="button-text"><?php echo e(__('button_create_account', [], $GLOBALS['current_language'] ?? 'en')); // "Create Account" ?></span>
                            <span class="spinner-border spinner-border-sm ms-2 d-none" role="status" aria-hidden="true"></span>
                        </button>
                    </form>
                </div>
                <div class="card-footer text-center py-3 bg-light">
                    <p class="mb-0"><?php echo e(__('text_already_have_account', [], $GLOBALS['current_language'] ?? 'en')); // "Already have an account?" ?> <a href="<?php echo e(base_url('/login')); ?>" class="fw-bold text-primary-orange"><?php echo e(__('link_text_login_now', [], $GLOBALS['current_language'] ?? 'en')); // "Log In Now" ?></a></p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const registerForm = document.getElementById('registerForm');
    if (!registerForm) return;

    const registerButton = document.getElementById('registerButton');
    const buttonText = registerButton.querySelector('.button-text');
    const spinner = registerButton.querySelector('.spinner-border');
    const formMessages = document.getElementById('form-messages');

    function clearValidationUI() {
        // Remove is-invalid class from all form controls
        registerForm.querySelectorAll('.form-control').forEach(el => el.classList.remove('is-invalid'));
        // Clear all previous error messages in .invalid-feedback divs
        registerForm.querySelectorAll('.invalid-feedback').forEach(el => el.textContent = '');
        // Clear general form messages
        if (formMessages) {
            formMessages.innerHTML = '';
            formMessages.className = 'mb-3'; // Reset class
        }
    }

    function displayFormMessage(message, type = 'danger', isHtml = false) {
        if (!formMessages) return;
        formMessages.innerHTML = `<div class="alert alert-${type} alert-dismissible fade show" role="alert">
                                    ${isHtml ? message : escapeHtml(message)}
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                 </div>`;
        formMessages.hidden = false;
    }

    function displayFieldErrors(errors) {
        for (const field in errors) {
            const inputElement = registerForm.querySelector(`[name="${field}"]`);
            const errorElement = document.getElementById(`${field}Error`); // Assumes error div IDs match fieldName + "Error"

            if (inputElement) inputElement.classList.add('is-invalid');
            if (errorElement) errorElement.textContent = errors[field];
            // For 'agree_terms', the input might be different
            if (field === 'agree_terms' && inputElement && errorElement) {
                 inputElement.classList.add('is-invalid'); // Or its container
                 errorElement.textContent = errors[field];
            }
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

    registerForm.addEventListener('submit', async function (event) {
        event.preventDefault();
        clearValidationUI();

        if (buttonText) buttonText.textContent = '<?php echo e(__('state_text_processing', [], $GLOBALS['current_language'] ?? 'en')); // "Processing..." ?>';
        if (spinner) spinner.classList.remove('d-none');
        registerButton.disabled = true;

        const formData = new FormData(registerForm);

        try {
            const response = await fetch(registerForm.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'Accept': 'application/json' // We expect a JSON response
                }
            });

            const result = await response.json();

            if (response.ok && result.success) {
                displayFormMessage(result.message || '<?php echo e(__('alert_registration_success', [], $GLOBALS['current_language'] ?? 'en')); // "Registration successful! Please check your email to verify your account or proceed to login." ?>', 'success');
                registerForm.reset(); // Clear the form on success
                // Optional: redirect after a delay
                if (result.redirect_url) {
                    setTimeout(() => { window.location.href = result.redirect_url; }, 3000);
                } else {
                     setTimeout(() => { window.location.href = '<?php echo e(base_url('/login')); ?>'; }, 3000);
                }
            } else {
                // Handle errors (validation or other server-side issues)
                let errorMessage = result.message || '<?php echo e(__('alert_registration_failed_unknown', [], $GLOBALS['current_language'] ?? 'en')); // "Registration failed. Please check the form and try again." ?>';
                if (result.errors) {
                    displayFieldErrors(result.errors);
                    // Focus the first field with an error
                    const firstErrorField = Object.keys(result.errors)[0];
                    if (firstErrorField) {
                        const fieldToFocus = registerForm.querySelector(`[name="${firstErrorField}"]`);
                        if (fieldToFocus) fieldToFocus.focus();
                    }
                }
                displayFormMessage(errorMessage, 'danger');
            }
        } catch (error) {
            console.error('Registration submission error:', error);
            displayFormMessage('<?php echo e(__('alert_registration_failed_network', [], $GLOBALS['current_language'] ?? 'en')); // "A network error occurred. Please check your connection and try again." ?>', 'danger');
        } finally {
            if (buttonText) buttonText.textContent = '<?php echo e(__('button_create_account', [], $GLOBALS['current_language'] ?? 'en')); ?>';
            if (spinner) spinner.classList.add('d-none');
            registerButton.disabled = false;
        }
    });
});
</script>
<?php
// Placeholder for translation strings used in this file (for easy extraction)
// __('page_title_register', [], $GLOBALS['current_language'] ?? 'en');
// __('register_join_community_text', [], $GLOBALS['current_language'] ?? 'en');
// __('placeholder_username', [], $GLOBALS['current_language'] ?? 'en');
// __('label_username', [], $GLOBALS['current_language'] ?? 'en');
// __('help_text_username', [], $GLOBALS['current_language'] ?? 'en');
// __('placeholder_email', [], $GLOBALS['current_language'] ?? 'en');
// __('label_email', [], $GLOBALS['current_language'] ?? 'en');
// __('placeholder_password', [], $GLOBALS['current_language'] ?? 'en');
// __('label_password', [], $GLOBALS['current_language'] ?? 'en');
// __('help_text_password', [], $GLOBALS['current_language'] ?? 'en');
// __('placeholder_confirm_password', [], $GLOBALS['current_language'] ?? 'en');
// __('label_confirm_password', [], $GLOBALS['current_language'] ?? 'en');
// __('link_text_terms', [], $GLOBALS['current_language'] ?? 'en');
// __('link_text_privacy', [], $GLOBALS['current_language'] ?? 'en');
// __('label_agree_terms_with_links %s %s', [], $GLOBALS['current_language'] ?? 'en');
// __('button_create_account', [], $GLOBALS['current_language'] ?? 'en');
// __('text_already_have_account', [], $GLOBALS['current_language'] ?? 'en');
// __('link_text_login_now', [], $GLOBALS['current_language'] ?? 'en');
// __('state_text_processing', [], $GLOBALS['current_language'] ?? 'en');
// __('alert_registration_success', [], $GLOBALS['current_language'] ?? 'en');
// __('alert_registration_failed_unknown', [], $GLOBALS['current_language'] ?? 'en');
// __('alert_registration_failed_network', [], $GLOBALS['current_language'] ?? 'en');
?>
