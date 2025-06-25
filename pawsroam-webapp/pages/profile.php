<?php
// This page is intended to be included by index.php.
// Assumes $current_language, $pageTitle, and core functions/auth are available.

require_login(); // Redirects to login if user is not authenticated.

$user_id = current_user_id();
$user_data = null;
$error_message = null;
$success_message = null; // For future update success messages

if ($user_id) {
    try {
        $db = Database::getInstance()->getConnection();
        // Fetch more user details if needed, e.g., from a user_profiles table
        $stmt = $db->prepare("SELECT id, username, email, language_preference, timezone, created_at, status FROM users WHERE id = :id LIMIT 1");
        $stmt->bindParam(':id', $user_id, PDO::PARAM_INT);
        $stmt->execute();
        $user_data = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user_data) {
            error_log("User profile page: User ID {$user_id} from session not found in database.");
            // This is a critical state inconsistency. Log out the user and redirect.
            logout_user(base_url('/login?status=session_error'));
            exit; // logout_user calls exit, but being explicit.
        }
    } catch (PDOException $e) {
        error_log("Database error fetching user profile for user ID {$user_id}: " . $e->getMessage());
        $error_message = __('error_profile_load_failed_db', [], $GLOBALS['current_language'] ?? 'en');
    } catch (Exception $e) {
        error_log("General error fetching user profile for user ID {$user_id}: " . $e->getMessage());
        $error_message = __('error_profile_load_failed_server', [], $GLOBALS['current_language'] ?? 'en');
    }
} else {
    // This case should ideally be caught by require_login() already.
    // If require_login() is bypassed or fails, this is a fallback.
    error_log("User profile page: current_user_id() returned null despite page requiring login.");
    $error_message = __('error_profile_not_logged_in', [], $GLOBALS['current_language'] ?? 'en');
}

$pageTitle = __('page_title_user_profile', [], $GLOBALS['current_language'] ?? 'en');
if ($error_message) {
    $pageTitle = __('page_title_error', [], $GLOBALS['current_language'] ?? 'en') . " - " . (defined('APP_NAME') ? APP_NAME : 'PawsRoam');
}

// Data for dropdowns
$available_languages = (defined('SUPPORTED_LANGUAGES_MAP') ? SUPPORTED_LANGUAGES_MAP : [
    'en' => 'English', 'jp' => '日本語', 'tw' => '繁體中文'
    // Add more from constants: ko, th, de, ar
]);
$available_timezones = DateTimeZone::listIdentifiers(DateTimeZone::ALL); // Consider caching this list

// --- Handle Profile Update (Stub - Actual logic would be via API) ---
// For this stub, we won't process POST here. JS will POST to an API.
// This section is just for conceptual reference if non-JS fallback was considered.
/*
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user_data && function_exists('validate_csrf_token')) {
    if (validate_csrf_token($_POST[CSRF_TOKEN_NAME ?? 'csrf_token'] ?? null)) {
        // ... (validation, DB update logic) ...
        // $success_message = "Profile updated successfully! (Stub)";
        // $user_data would need to be re-fetched or updated in place.
    } else {
        // $error_message = "Invalid security token. Profile update failed.";
    }
}
*/
?>

<div class="container my-4 my-md-5">
    <div class="row mb-4 align-items-center">
        <div class="col">
            <h1 class="display-6 fw-bold"><?php echo e($pageTitle); ?></h1>
        </div>
        <div class="col text-end">
             <?php if ($user_data): ?>
             <small class="text-muted"><?php echo e(__('profile_last_login_placeholder', [], $GLOBALS['current_language'] ?? 'en')); // "Last login: Today (Placeholder)" ?></small>
             <?php endif; ?>
        </div>
    </div>


    <?php if ($error_message): ?>
        <div class="alert alert-danger shadow-sm" role="alert">
            <h4 class="alert-heading"><i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo e(__('error_oops_title', [], $GLOBALS['current_language'] ?? 'en')); ?></h4>
            <p><?php echo e($error_message); ?></p>
        </div>
    <?php elseif ($user_data): ?>
        <?php if ($success_message): // For future use when form submits to self or for messages from other actions ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo e($success_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row g-4 g-lg-5">
            <!-- Profile Information Display/Edit Form -->
            <div class="col-lg-8">
                <form id="profileUpdateForm" action="<?php echo e(base_url('/api/v1/user/profile')); ?>" method="POST" class="needs-validation" novalidate> <?php // API endpoint for profile update ?>
                    <?php echo csrf_input_field(); ?>
                    <input type="hidden" name="user_id" value="<?php echo e($user_data['id']); ?>">

                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-light py-3">
                            <h2 class="h5 mb-0 fw-semibold text-text-dark"><i class="bi bi-person-badge me-2"></i><?php echo e(__('profile_section_account_details', [], $GLOBALS['current_language'] ?? 'en')); ?></h2>
                        </div>
                        <div class="card-body p-4">
                            <fieldset>
                                <legend class="visually-hidden"><?php echo e(__('profile_subsection_basic_info', [], $GLOBALS['current_language'] ?? 'en')); ?></legend>
                                <div class="mb-3 row align-items-center">
                                    <label for="profile_username" class="col-sm-4 col-md-3 col-form-label fw-medium"><?php echo e(__('label_username', [], $GLOBALS['current_language'] ?? 'en')); ?></label>
                                    <div class="col-sm-8 col-md-9">
                                        <input type="text" class="form-control" id="profile_username" name="username" value="<?php echo e($user_data['username']); ?>" required minlength="3" maxlength="25" pattern="^[a-zA-Z0-9_]+$">
                                        <div class="invalid-feedback" id="usernameError"></div>
                                        <small class="form-text text-muted"><?php echo e(__('help_text_username', [], $GLOBALS['current_language'] ?? 'en')); ?></small>
                                    </div>
                                </div>
                                <div class="mb-3 row align-items-center">
                                    <label for="profile_email_display" class="col-sm-4 col-md-3 col-form-label fw-medium"><?php echo e(__('label_email', [], $GLOBALS['current_language'] ?? 'en')); ?></label>
                                    <div class="col-sm-8 col-md-9">
                                        <input type="email" readonly class="form-control-plaintext" id="profile_email_display" value="<?php echo e($user_data['email']); ?>">
                                        <?php /* <button type="button" class="btn btn-sm btn-outline-secondary ms-2" id="changeEmailBtn" disabled title="Feature coming soon">Change Email</button> */ ?>
                                        <small class="form-text text-muted d-block"><?php echo e(__('profile_email_change_note', [], $GLOBALS['current_language'] ?? 'en')); ?></small>
                                    </div>
                                </div>
                                <div class="mb-3 row align-items-center">
                                    <label class="col-sm-4 col-md-3 col-form-label fw-medium"><?php echo e(__('profile_member_since', [], $GLOBALS['current_language'] ?? 'en')); ?></label>
                                    <div class="col-sm-8 col-md-9">
                                        <p class="form-control-plaintext mb-0"><?php echo e(date("F j, Y, g:i A T", strtotime($user_data['created_at']))); ?></p>
                                    </div>
                                </div>
                                <div class="mb-3 row align-items-center">
                                    <label class="col-sm-4 col-md-3 col-form-label fw-medium"><?php echo e(__('profile_account_status', [], $GLOBALS['current_language'] ?? 'en')); ?></label>
                                    <div class="col-sm-8 col-md-9">
                                        <p class="form-control-plaintext mb-0"><span class="badge fs-6 bg-<?php echo $user_data['status'] === 'active' ? 'success' : ($user_data['status'] === 'pending' ? 'warning text-dark' : 'danger'); ?>"><?php echo e(ucfirst($user_data['status'])); ?></span></p>
                                    </div>
                                </div>
                            </fieldset>
                        </div>
                    </div>

                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-light py-3">
                            <h2 class="h5 mb-0 fw-semibold text-text-dark"><i class="bi bi-sliders me-2"></i><?php echo e(__('profile_subsection_preferences', [], $GLOBALS['current_language'] ?? 'en')); ?></h2>
                        </div>
                        <div class="card-body p-4">
                            <fieldset>
                                <legend class="visually-hidden"><?php echo e(__('profile_subsection_preferences', [], $GLOBALS['current_language'] ?? 'en')); ?></legend>
                                <div class="mb-3 row align-items-center">
                                    <label for="profile_language_preference" class="col-sm-4 col-md-3 col-form-label fw-medium"><?php echo e(__('profile_language_label', [], $GLOBALS['current_language'] ?? 'en')); ?></label>
                                    <div class="col-sm-8 col-md-9">
                                        <select class="form-select" id="profile_language_preference" name="language_preference">
                                            <?php foreach ($available_languages as $code => $name): ?>
                                                <option value="<?php echo e($code); ?>" <?php echo ($user_data['language_preference'] == $code) ? 'selected' : ''; ?>><?php echo e($name); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                         <div class="invalid-feedback" id="language_preferenceError"></div>
                                    </div>
                                </div>
                                <div class="mb-3 row align-items-center">
                                    <label for="profile_timezone" class="col-sm-4 col-md-3 col-form-label fw-medium"><?php echo e(__('profile_timezone_label', [], $GLOBALS['current_language'] ?? 'en')); ?></label>
                                    <div class="col-sm-8 col-md-9">
                                        <select class="form-select" id="profile_timezone" name="timezone">
                                            <option value=""><?php echo e(__('profile_select_timezone_option', [], $GLOBALS['current_language'] ?? 'en')); // "-- Select Timezone --" ?></option>
                                            <?php foreach ($available_timezones as $tz): ?>
                                                <option value="<?php echo e($tz); ?>" <?php echo ($user_data['timezone'] == $tz) ? 'selected' : ''; ?>><?php echo e(str_replace('_', ' ', $tz)); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                         <div class="invalid-feedback" id="timezoneError"></div>
                                    </div>
                                </div>
                            </fieldset>
                        </div>
                    </div>

                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-light py-3">
                             <h2 class="h5 mb-0 fw-semibold text-text-dark"><i class="bi bi-key-fill me-2"></i><?php echo e(__('profile_subsection_change_password', [], $GLOBALS['current_language'] ?? 'en')); ?></h2>
                        </div>
                        <div class="card-body p-4">
                            <fieldset>
                                <legend class="visually-hidden"><?php echo e(__('profile_subsection_change_password', [], $GLOBALS['current_language'] ?? 'en')); ?></legend>
                                 <div class="mb-3 row align-items-center">
                                    <label for="profile_current_password" class="col-sm-4 col-md-3 col-form-label fw-medium"><?php echo e(__('profile_current_password_label', [], $GLOBALS['current_language'] ?? 'en')); ?></label>
                                    <div class="col-sm-8 col-md-9">
                                        <input type="password" class="form-control" id="profile_current_password" name="current_password" autocomplete="current-password" placeholder="<?php echo e(__('profile_password_leave_blank_note', [], $GLOBALS['current_language'] ?? 'en')); // "Required to change password" ?>">
                                        <div class="invalid-feedback" id="current_passwordError"></div>
                                    </div>
                                </div>
                                <div class="mb-3 row align-items-center">
                                    <label for="profile_new_password" class="col-sm-4 col-md-3 col-form-label fw-medium"><?php echo e(__('profile_new_password_label', [], $GLOBALS['current_language'] ?? 'en')); ?></label>
                                    <div class="col-sm-8 col-md-9">
                                        <input type="password" class="form-control" id="profile_new_password" name="new_password" autocomplete="new-password" minlength="8">
                                        <small class="form-text text-muted"><?php echo e(__('help_text_password_profile', [], $GLOBALS['current_language'] ?? 'en')); // "Min. 8 characters. Leave blank if not changing." ?></small>
                                        <div class="invalid-feedback" id="new_passwordError"></div>
                                    </div>
                                </div>
                                <div class="mb-3 row align-items-center">
                                    <label for="profile_confirm_new_password" class="col-sm-4 col-md-3 col-form-label fw-medium"><?php echo e(__('profile_confirm_new_password_label', [], $GLOBALS['current_language'] ?? 'en')); ?></label>
                                    <div class="col-sm-8 col-md-9">
                                        <input type="password" class="form-control" id="profile_confirm_new_password" name="confirm_new_password" autocomplete="new-password">
                                        <div class="invalid-feedback" id="confirm_new_passwordError"></div>
                                    </div>
                                </div>
                            </fieldset>
                        </div>
                    </div>

                    <div id="profile-form-messages" class="my-3" role="alert" aria-live="assertive"></div>

                    <div class="mt-4 d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary btn-lg px-4" id="updateProfileButton">
                            <span class="button-text"><?php echo e(__('profile_button_update', [], $GLOBALS['current_language'] ?? 'en')); ?></span>
                            <span class="spinner-border spinner-border-sm ms-2 d-none" role="status" aria-hidden="true"></span>
                        </button>
                    </div>
                     <p class="text-end mt-2"><small class="text-muted"><?php echo e(__('profile_update_api_note', [], $GLOBALS['current_language'] ?? 'en')); // "Profile updates will be handled via an API endpoint." ?></small></p>
                </form>
            </div>

            <!-- Sidebar/Actions Column -->
            <div class="col-lg-4">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-light py-3"><h3 class="h6 mb-0 fw-semibold text-text-dark"><i class="bi bi-person-circle me-2"></i><?php echo e(__('profile_sidebar_avatar_title', [], $GLOBALS['current_language'] ?? 'en')); ?></h3></div>
                    <div class="card-body text-center p-4">
                        <?php
                            $current_avatar_url = base_url('/assets/images/placeholders/avatar_placeholder_200x200.png'); // Default
                            if (!empty($user_data['avatar_path']) && defined('UPLOADS_BASE_URL')) {
                                // avatar_path from DB is expected to be like 'user-avatars/123/filename.jpg'
                                $current_avatar_url = rtrim(UPLOADS_BASE_URL, '/') . '/' . ltrim($user_data['avatar_path'], '/');
                            }
                        ?>
                        <img id="userAvatarPreview" src="<?php echo e($current_avatar_url); ?>"
                             alt="<?php echo e(sprintf(__('profile_avatar_alt_text_user %s', [], $GLOBALS['current_language'] ?? 'en'), e($user_data['username']))); ?>"
                             class="img-thumbnail rounded-circle mb-3 shadow-sm" style="width: 150px; height: 150px; object-fit: cover;">

                        <form id="avatarUploadForm" action="<?php echo e(base_url('/api/v1/user/avatar-upload.php')); ?>" method="POST" enctype="multipart/form-data">
                            <?php echo csrf_input_field(); // For this specific form ?>
                            <input type="hidden" name="user_id" value="<?php echo e($user_data['id']); ?>">
                            <div class="mb-2">
                                <label for="user_avatar_upload_input" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-camera-fill me-1"></i><?php echo e(__('profile_button_select_avatar', [], $GLOBALS['current_language'] ?? 'en')); // "Select New Avatar" ?>
                                </label>
                                <input type="file" class="form-control d-none" id="user_avatar_upload_input" name="user_avatar" accept="<?php echo e(implode(',', unserialize(USER_AVATAR_ALLOWED_MIME_TYPES))); ?>">
                            </div>
                            <button type="submit" class="btn btn-sm btn-success d-none" id="uploadAvatarBtn">
                                <i class="bi bi-upload me-1"></i><?php echo e(__('profile_button_upload_avatar', [], $GLOBALS['current_language'] ?? 'en')); // "Upload" ?>
                                <span class="spinner-border spinner-border-sm ms-1 d-none" role="status" aria-hidden="true"></span>
                            </button>
                            <small id="avatarUploadStatus" class="form-text d-block mt-1"></small>
                        </form>
                    </div>
                </div>
                <div class="card shadow-sm">
                     <div class="card-header bg-light py-3"><h3 class="h6 mb-0 fw-semibold text-text-dark"><i class="bi bi-link-45deg me-2"></i><?php echo e(__('profile_sidebar_quick_links_title', [], $GLOBALS['current_language'] ?? 'en')); ?></h3></div>
                    <div class="list-group list-group-flush">
                        <a href="<?php echo e(base_url('/pet-profile')); ?>" class="list-group-item list-group-item-action"><i class="bi bi-hearts me-2 text-primary-orange"></i><?php echo e(__('nav_my_pets', [], $GLOBALS['current_language'] ?? 'en')); ?></a>
                        <a href="#" class="list-group-item list-group-item-action disabled" title="Feature coming soon"><i class="bi bi-calendar-check me-2"></i><?php echo e(__('profile_link_my_bookings', [], $GLOBALS['current_language'] ?? 'en')); ?></a>
                        <a href="#" class="list-group-item list-group-item-action disabled" title="Feature coming soon"><i class="bi bi-star-half me-2"></i><?php echo e(__('profile_link_my_reviews', [], $GLOBALS['current_language'] ?? 'en')); ?></a>
                        <a href="#" class="list-group-item list-group-item-action text-danger disabled" title="Feature coming soon"><i class="bi bi-trash3 me-2"></i><?php echo e(__('profile_link_delete_account', [], $GLOBALS['current_language'] ?? 'en')); ?></a>
                    </div>
                </div>
            </div>
        </div>

    <?php endif; ?>
</div>
<script>
// JavaScript for User Profile Page
document.addEventListener('DOMContentLoaded', function() {
    const profileForm = document.getElementById('profileUpdateForm');
    if (!profileForm) return;

    const updateButton = document.getElementById('updateProfileButton');
    const buttonText = updateButton.querySelector('.button-text');
    const spinner = updateButton.querySelector('.spinner-border');
    const formMessages = document.getElementById('profile-form-messages');

    // Enable the update button (it might have been disabled in a pure stub)
    if(updateButton) updateButton.disabled = false;

    function clearProfileValidationUI() {
        profileForm.querySelectorAll('.form-control, .form-select').forEach(el => el.classList.remove('is-invalid'));
        profileForm.querySelectorAll('.invalid-feedback').forEach(el => el.textContent = '');
        if (formMessages) {
            formMessages.innerHTML = '';
            formMessages.className = 'my-3';
        }
    }

    function displayProfileFormMessage(message, type = 'danger', isHtml = false) {
        if (!formMessages) return;
        formMessages.innerHTML = `<div class="alert alert-${type} alert-dismissible fade show" role="alert">
                                    ${isHtml ? message : escapeHtml(message)}
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                 </div>`;
        formMessages.hidden = false;
    }

    function displayProfileFieldErrors(errors) {
        // Ensure previous general messages are cleared if field errors are shown
        // if (formMessages) formMessages.innerHTML = '';

        for (const field in errors) {
            const inputElement = profileForm.querySelector(`[name="${field}"]`);
            // Try to find error div by ID convention (fieldName + "Error") or as sibling/cousin .invalid-feedback
            const errorElement = document.getElementById(`${field}Error`) ||
                                 (inputElement ? (inputElement.closest('.form-floating') || inputElement.closest('.mb-3') || inputElement.parentNode).querySelector('.invalid-feedback') : null);

            if (inputElement) {
                inputElement.classList.add('is-invalid');
                // Focus the first field with an error
                if (Object.keys(errors)[0] === field) {
                    inputElement.focus();
                }
            }
            if (errorElement) {
                errorElement.textContent = errors[field];
                errorElement.style.display = 'block'; // Ensure it's visible if hidden by default
            } else if (inputElement) {
                // Fallback if no dedicated error div, append after input (less ideal for Bootstrap structure)
                const smallError = document.createElement('div');
                smallError.className = 'invalid-feedback d-block';
                smallError.textContent = errors[field];
                inputElement.parentNode.appendChild(smallError);
            }
        }
    }

    function escapeHtml(unsafe) {
        if (typeof unsafe !== 'string') return '';
        return unsafe.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
    }

    profileForm.addEventListener('submit', async function(event) {
        event.preventDefault();
        clearProfileValidationUI();

        if (buttonText) buttonText.textContent = '<?php echo e(__('state_text_processing', [], $GLOBALS['current_language'] ?? 'en')); ?>';
        if (spinner) spinner.classList.remove('d-none');
        updateButton.disabled = true;

        const formData = new FormData(profileForm);

        // Only send password fields if new_password is not empty
        // The API will require current_password if new_password is set.
        if (formData.get('new_password') === '') {
            formData.delete('current_password');
            formData.delete('new_password');
            formData.delete('confirm_new_password');
        }

        // The user_id is already in a hidden field, confirmed by session on server.

        try {
            const response = await fetch(profileForm.action, {
                method: 'POST',
                body: formData,
                headers: { 'Accept': 'application/json' }
            });
            const result = await response.json();

            if (response.ok && result.success) {
                displayProfileFormMessage(result.message || '<?php echo e(__('profile_update_success', [], $GLOBALS['current_language'] ?? 'en')); ?>', 'success');

                // If username was updated, reflect it in the navigation (if possible without full page reload)
                if (result.updated_fields && result.updated_fields.includes('username')) {
                   const navUsernameDisplay = document.querySelector('#userDropdown'); // Assuming Bootstrap nav dropdown ID
                   if (navUsernameDisplay && formData.get('username')) {
                       // This is a simplistic update; a more robust solution might involve a global state or events.
                       // Example: navUsernameDisplay.textContent = `Welcome, ${formData.get('username')}`;
                       // For now, we can inform the user a refresh might be needed for all UI updates.
                       console.log("Username updated. Navigation might need a refresh to show new username.");
                   }
                }
                // If password was changed successfully, clear password fields on the form
                if (result.updated_fields && result.updated_fields.includes('password')) {
                    ['current_password', 'new_password', 'confirm_new_password'].forEach(fieldName => {
                        const field = profileForm.querySelector(`[name="${fieldName}"]`);
                        if (field) field.value = '';
                    });
                }
                 // If language preference changed, a page reload might be best to apply new language strings everywhere
                if (result.updated_fields && result.updated_fields.includes('language_preference')) {
                    displayProfileFormMessage(result.message + ' <?php echo e(__('profile_update_language_changed_refresh_note', [], $GLOBALS['current_language'] ?? 'en')); // "Language preference changed. The page will reload to apply changes." ?>', 'success');
                    setTimeout(() => window.location.reload(), 3000);
                }


            } else {
                let errorMessage = result.message || '<?php echo e(__('profile_update_failed_generic_error', [], $GLOBALS['current_language'] ?? 'en')); ?>';
                if (result.errors) {
                    displayProfileFieldErrors(result.errors);
                } else {
                     // If no specific errors object, but call failed, show the general message prominently.
                     displayProfileFormMessage(errorMessage, 'danger');
                }
            }
        } catch (error) {
            console.error('Profile update submission error:', error);
            displayProfileFormMessage('<?php echo e(__('profile_update_failed_network', [], $GLOBALS['current_language'] ?? 'en')); ?>', 'danger');
        } finally {
            if (buttonText) buttonText.textContent = '<?php echo e(__('profile_button_update', [], $GLOBALS['current_language'] ?? 'en')); ?>';
            if (spinner) spinner.classList.add('d-none');
            updateButton.disabled = false;
        }
    });
});
</script>
<?php
// Placeholder for translation strings
        const formData = new FormData(profileForm);
        try {
            const response = await fetch(profileForm.action, {
                method: 'POST',
                body: formData,
                headers: { 'Accept': 'application/json' }
            });
            const result = await response.json();

            if (response.ok && result.success) {
                displayProfileFormMessage(result.message || 'Profile updated successfully!', 'success');
                // Optionally update displayed username if changed:
                // if (result.updated_fields && result.updated_fields.username) {
                //    document.querySelector('.welcome-username-nav').textContent = result.updated_fields.username; // If you have such an element
                // }
            } else {
                let errorMessage = result.message || 'Profile update failed. Please check errors.';
                if (result.errors) {
                    displayProfileFieldErrors(result.errors);
                }
                displayProfileFormMessage(errorMessage, 'danger');
            }
        } catch (error) {
            console.error('Profile update error:', error);
            displayProfileFormMessage('A network error occurred. Please try again.', 'danger');
        } finally {
            if (buttonText) buttonText.textContent = '<?php echo e(__('profile_button_update', [], $GLOBALS['current_language'] ?? 'en')); ?>';
            if (spinner) spinner.classList.add('d-none');
            updateButton.disabled = false;
        }
        */
    });
});
</script>
<?php
// Placeholder for translation strings
// __('error_profile_load_failed_db', [], $GLOBALS['current_language'] ?? 'en');
// __('error_profile_load_failed_server', [], $GLOBALS['current_language'] ?? 'en');
// __('error_profile_not_logged_in', [], $GLOBALS['current_language'] ?? 'en');
// __('page_title_user_profile', [], $GLOBALS['current_language'] ?? 'en');
// __('profile_last_login_placeholder', [], $GLOBALS['current_language'] ?? 'en');
// __('profile_section_account_details', [], $GLOBALS['current_language'] ?? 'en');
// __('profile_subsection_basic_info', [], $GLOBALS['current_language'] ?? 'en');
// __('profile_email_change_note', [], $GLOBALS['current_language'] ?? 'en');
// __('profile_member_since', [], $GLOBALS['current_language'] ?? 'en');
// __('profile_account_status', [], $GLOBALS['current_language'] ?? 'en');
// __('profile_subsection_preferences', [], $GLOBALS['current_language'] ?? 'en');
// __('profile_language_label', [], $GLOBALS['current_language'] ?? 'en');
// __('profile_timezone_label', [], $GLOBALS['current_language'] ?? 'en');
// __('profile_select_timezone_option', [], $GLOBALS['current_language'] ?? 'en');
// __('profile_subsection_change_password', [], $GLOBALS['current_language'] ?? 'en');
// __('profile_current_password_label', [], $GLOBALS['current_language'] ?? 'en');
// __('profile_password_leave_blank_note', [], $GLOBALS['current_language'] ?? 'en');
// __('profile_new_password_label', [], $GLOBALS['current_language'] ?? 'en');
// __('help_text_password_profile', [], $GLOBALS['current_language'] ?? 'en');
// __('profile_confirm_new_password_label', [], $GLOBALS['current_language'] ?? 'en');
// __('profile_button_update', [], $GLOBALS['current_language'] ?? 'en');
// __('profile_update_api_note', [], $GLOBALS['current_language'] ?? 'en');
// __('profile_sidebar_avatar_title', [], $GLOBALS['current_language'] ?? 'en');
// __('profile_avatar_alt_text_user %s', [], $GLOBALS['current_language'] ?? 'en'); // %s for username
// __('profile_button_change_avatar', [], $GLOBALS['current_language'] ?? 'en');
// __('profile_sidebar_quick_links_title', [], $GLOBALS['current_language'] ?? 'en');
// __('profile_link_my_bookings', [], $GLOBALS['current_language'] ?? 'en');
// __('profile_link_my_reviews', [], $GLOBALS['current_language'] ?? 'en');
// __('profile_link_delete_account', [], $GLOBALS['current_language'] ?? 'en');
// __('profile_alert_update_success_stub', [], $GLOBALS['current_language'] ?? 'en');
// __('error_username_taken_stub', [], $GLOBALS['current_language'] ?? 'en');
// __('error_password_min_length_stub', [], $GLOBALS['current_language'] ?? 'en');
// __('profile_alert_update_failed_stub', [], $GLOBALS['current_language'] ?? 'en');
?>
