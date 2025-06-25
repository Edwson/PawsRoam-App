<?php
require_login();

$pageTitle = __('page_title_edit_pet', [], $GLOBALS['current_language'] ?? 'en'); // "Edit Pet Profile"
$user_id = current_user_id();
$pet_id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
$pet_data = null;
$error_message = null;

if (!$pet_id) {
    $error_message = __('error_invalid_pet_id_for_edit', [], $GLOBALS['current_language'] ?? 'en'); // "No pet specified or invalid ID for editing."
    http_response_code(400);
} else {
    try {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM user_pets WHERE id = :pet_id AND user_id = :user_id LIMIT 1");
        $stmt->bindParam(':pet_id', $pet_id, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();
        $pet_data = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$pet_data) {
            $error_message = __('error_pet_not_found_or_not_owned', [], $GLOBALS['current_language'] ?? 'en'); // "Pet profile not found or you do not have permission to edit it."
            http_response_code(404); // Or 403 if found but not owned
        } else {
            $pageTitle = sprintf(__('page_title_edit_pet_name %s', [], $GLOBALS['current_language'] ?? 'en'), e($pet_data['name'])); // "Edit Profile for [Pet Name]"
            // Decode JSON fields for form pre-fill if they were stored as JSON strings of arrays
            $pet_data['personality_traits'] = !empty($pet_data['personality_traits']) ? implode(', ', json_decode($pet_data['personality_traits'], true) ?: []) : '';
            $pet_data['medical_conditions'] = !empty($pet_data['medical_conditions']) ? implode(', ', json_decode($pet_data['medical_conditions'], true) ?: []) : '';
            $pet_data['dietary_restrictions'] = !empty($pet_data['dietary_restrictions']) ? implode(', ', json_decode($pet_data['dietary_restrictions'], true) ?: []) : '';
        }
    } catch (PDOException $e) {
        error_log("Database error fetching pet for edit (ID: {$pet_id}, User: {$user_id}): " . $e->getMessage());
        $error_message = __('error_pet_profiles_load_failed_db', [], $GLOBALS['current_language'] ?? 'en');
        http_response_code(500);
    }
}

// Data for dropdowns
$pet_species_options = [ /* ... copy from add-pet.php or centralize ... */
    'dog' => __('pet_species_dog', [], $GLOBALS['current_language'] ?? 'en'), 'cat' => __('pet_species_cat', [], $GLOBALS['current_language'] ?? 'en'), 'bird' => __('pet_species_bird', [], $GLOBALS['current_language'] ?? 'en'), 'rabbit' => __('pet_species_rabbit', [], $GLOBALS['current_language'] ?? 'en'), 'other' => __('pet_species_other', [], $GLOBALS['current_language'] ?? 'en'),];
$pet_size_options = [ /* ... copy from add-pet.php or centralize ... */
    '' => __('select_option_placeholder', [], $GLOBALS['current_language'] ?? 'en') . __('pet_size_label', [], $GLOBALS['current_language'] ?? 'en'), 'small' => __('pet_size_small', [], $GLOBALS['current_language'] ?? 'en'), 'medium' => __('pet_size_medium', [], $GLOBALS['current_language'] ?? 'en'), 'large' => __('pet_size_large', [], $GLOBALS['current_language'] ?? 'en'), 'extra_large' => __('pet_size_extra_large', [], $GLOBALS['current_language'] ?? 'en'),];

if (empty($_SESSION[CSRF_TOKEN_NAME ?? 'csrf_token'])) { generate_csrf_token(true); }
?>

<div class="container my-4 my-md-5">
    <div class="row mb-4 align-items-center">
        <div class="col">
            <h1 class="display-6 fw-bold"><?php echo e($pageTitle); ?></h1>
        </div>
        <div class="col text-end">
            <a href="<?php echo e(base_url('/pet-profile')); ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left-circle me-2"></i><?php echo e(__('button_back_to_my_pets', [], $GLOBALS['current_language'] ?? 'en')); ?>
            </a>
        </div>
    </div>

    <?php if ($error_message): ?>
        <div class="alert alert-danger shadow-sm" role="alert">
            <h4 class="alert-heading"><i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo e(__('error_oops_title', [], $GLOBALS['current_language'] ?? 'en')); ?></h4>
            <p><?php echo e($error_message); ?></p>
        </div>
    <?php elseif ($pet_data): ?>
    <div class="card shadow-lg border-0">
        <div class="card-header bg-primary-orange text-white py-3">
            <h2 class="h4 mb-0"><i class="bi bi-pencil-square me-2"></i><?php echo e(__('edit_pet_form_title', [], $GLOBALS['current_language'] ?? 'en')); // "Update Your Pet's Details" ?></h2>
        </div>
        <div class="card-body p-4 p-md-5">
            <form id="editPetForm" action="<?php echo e(base_url('/api/v1/pets/update.php')); ?>" method="POST" enctype="multipart/form-data" novalidate>
                <?php echo csrf_input_field(); ?>
                <input type="hidden" name="pet_id" value="<?php echo e($pet_data['id']); ?>">

                <div id="editPetFormMessages" class="mb-3" role="alert" aria-live="assertive"></div>

                <fieldset>
                    <legend class="h5 fw-semibold mb-3 border-bottom pb-2 text-text-dark"><?php echo e(__('add_pet_section_basic_info', [], $GLOBALS['current_language'] ?? 'en')); ?></legend>
                    <div class="row g-3">
                        <div class="col-md-6 form-floating mb-3">
                            <input type="text" class="form-control" id="pet_name" name="name" value="<?php echo e($pet_data['name']); ?>" placeholder="<?php echo e(__('placeholder_pet_name', [], $GLOBALS['current_language'] ?? 'en')); ?>" required maxlength="100">
                            <label for="pet_name"><?php echo e(__('label_pet_name', [], $GLOBALS['current_language'] ?? 'en')); ?></label>
                            <div class="invalid-feedback" id="nameError"></div>
                        </div>
                        <div class="col-md-6 form-floating mb-3">
                            <select class="form-select" id="pet_species" name="species" required>
                                <?php foreach($pet_species_options as $value => $label): ?>
                                <option value="<?php echo e($value); ?>" <?php echo ($pet_data['species'] == $value) ? 'selected' : ''; ?>><?php echo e($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <label for="pet_species"><?php echo e(__('label_pet_species', [], $GLOBALS['current_language'] ?? 'en')); ?></label>
                            <div class="invalid-feedback" id="speciesError"></div>
                        </div>
                        <div class="col-md-6 form-floating mb-3">
                            <input type="text" class="form-control" id="pet_breed" name="breed" value="<?php echo e($pet_data['breed']); ?>" placeholder="<?php echo e(__('placeholder_pet_breed', [], $GLOBALS['current_language'] ?? 'en')); ?>" maxlength="100">
                            <label for="pet_breed"><?php echo e(__('label_pet_breed', [], $GLOBALS['current_language'] ?? 'en')); ?></label>
                            <div class="invalid-feedback" id="breedError"></div>
                        </div>
                         <div class="col-md-6 form-floating mb-3">
                            <input type="date" class="form-control" id="pet_birthdate" name="birthdate" value="<?php echo e($pet_data['birthdate']); ?>" max="<?php echo date('Y-m-d'); ?>">
                            <label for="pet_birthdate"><?php echo e(__('label_pet_birthdate', [], $GLOBALS['current_language'] ?? 'en')); ?></label>
                            <div class="invalid-feedback" id="birthdateError"></div>
                        </div>
                    </div>
                </fieldset>

                <hr class="my-4">

                <fieldset>
                    <legend class="h5 fw-semibold mb-3 border-bottom pb-2 text-text-dark"><?php echo e(__('add_pet_section_physical_details', [], $GLOBALS['current_language'] ?? 'en')); ?></legend>
                     <div class="row g-3">
                        <div class="col-md-6 form-floating mb-3">
                            <select class="form-select" id="pet_size" name="size">
                                <?php foreach($pet_size_options as $value => $label): ?>
                                <option value="<?php echo e($value); ?>" <?php echo ($pet_data['size'] == $value) ? 'selected' : ''; ?>><?php echo e($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <label for="pet_size"><?php echo e(__('label_pet_size', [], $GLOBALS['current_language'] ?? 'en')); ?></label>
                             <div class="invalid-feedback" id="sizeError"></div>
                        </div>
                        <div class="col-md-6 form-floating mb-3">
                            <input type="number" class="form-control" id="pet_weight_kg" name="weight_kg" value="<?php echo e($pet_data['weight_kg']); ?>" placeholder="e.g., 5.5" step="0.1" min="0.1" max="150">
                            <label for="pet_weight_kg"><?php echo e(__('label_pet_weight_kg', [], $GLOBALS['current_language'] ?? 'en')); ?></label>
                            <div class="invalid-feedback" id="weight_kgError"></div>
                        </div>
                    </div>
                </fieldset>

                <hr class="my-4">

                 <fieldset>
                    <legend class="h5 fw-semibold mb-3 border-bottom pb-2 text-text-dark"><?php echo e(__('add_pet_section_characteristics', [], $GLOBALS['current_language'] ?? 'en')); ?></legend>
                    <div class="mb-3">
                        <label for="pet_personality_traits" class="form-label"><?php echo e(__('label_pet_personality', [], $GLOBALS['current_language'] ?? 'en')); ?></label>
                        <textarea class="form-control" id="pet_personality_traits" name="personality_traits" rows="3" placeholder="<?php echo e(__('placeholder_pet_personality', [], $GLOBALS['current_language'] ?? 'en')); ?>"><?php echo e($pet_data['personality_traits']); ?></textarea>
                        <small class="form-text text-muted"><?php echo e(__('help_text_pet_personality', [], $GLOBALS['current_language'] ?? 'en')); ?></small>
                        <div class="invalid-feedback" id="personality_traitsError"></div>
                    </div>
                    <div class="mb-3">
                        <label for="pet_medical_conditions" class="form-label"><?php echo e(__('label_pet_medical', [], $GLOBALS['current_language'] ?? 'en')); ?></label>
                        <textarea class="form-control" id="pet_medical_conditions" name="medical_conditions" rows="3" placeholder="<?php echo e(__('placeholder_pet_medical', [], $GLOBALS['current_language'] ?? 'en')); ?>"><?php echo e($pet_data['medical_conditions']); ?></textarea>
                         <div class="invalid-feedback" id="medical_conditionsError"></div>
                    </div>
                    <div class="mb-3">
                        <label for="pet_dietary_restrictions" class="form-label"><?php echo e(__('label_pet_dietary', [], $GLOBALS['current_language'] ?? 'en')); ?></label>
                        <textarea class="form-control" id="pet_dietary_restrictions" name="dietary_restrictions" rows="3" placeholder="<?php echo e(__('placeholder_pet_dietary', [], $GLOBALS['current_language'] ?? 'en')); ?>"><?php echo e($pet_data['dietary_restrictions']); ?></textarea>
                        <div class="invalid-feedback" id="dietary_restrictionsError"></div>
                    </div>
                </fieldset>

                <hr class="my-4">

                <fieldset>
                     <legend class="h5 fw-semibold mb-3 border-bottom pb-2 text-text-dark"><?php echo e(__('edit_pet_section_avatar', [], $GLOBALS['current_language'] ?? 'en')); // "Update Profile Picture" ?></legend>
                    <div class="mb-3">
                        <label for="pet_avatar_new" class="form-label"><?php echo e(__('label_pet_avatar_new', [], $GLOBALS['current_language'] ?? 'en')); // "Upload New Avatar (Optional)" ?></label>
                        <input class="form-control" type="file" id="pet_avatar_new" name="avatar_new" accept="image/jpeg, image/png, image/gif">
                        <small class="form-text text-muted"><?php echo e(__('help_text_pet_avatar', [], $GLOBALS['current_language'] ?? 'en')); ?></small>
                        <div class="invalid-feedback" id="avatar_newError"></div>
                    </div>
                    <?php if (!empty($pet_data['avatar_path'])): ?>
                    <div class="mb-3">
                        <p><?php echo e(__('edit_pet_current_avatar_label', [], $GLOBALS['current_language'] ?? 'en')); // "Current Avatar:" ?></p>
                        <img id="currentAvatarPreview" src="<?php echo e(rtrim(UPLOADS_BASE_URL ?? base_url('/uploads'), '/') . '/' . ltrim($pet_data['avatar_path'], '/')); ?>" alt="<?php echo e(sprintf(__('pet_avatar_alt %s', [], $GLOBALS['current_language'] ?? 'en'), e($pet_data['name']))); ?>" class="img-thumbnail rounded-circle" style="width: 100px; height: 100px; object-fit: cover;">
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="checkbox" value="1" id="remove_current_avatar" name="remove_current_avatar">
                            <label class="form-check-label" for="remove_current_avatar">
                                <?php echo e(__('edit_pet_remove_avatar_checkbox_label', [], $GLOBALS['current_language'] ?? 'en')); // "Remove current avatar" ?>
                            </label>
                        </div>
                    </div>
                    <?php endif; ?>
                    <img id="newAvatarPreview" src="#" alt="<?php echo e(__('alt_new_pet_avatar_preview', [], $GLOBALS['current_language'] ?? 'en')); // "New Avatar Preview" ?>" class="mt-2 img-thumbnail rounded-circle d-none" style="width: 100px; height: 100px; object-fit: cover;"/>
                </fieldset>

                <div class="mt-4 pt-3 border-top">
                    <button type="submit" class="btn btn-primary btn-lg px-5" id="editPetButton">
                        <span class="button-text"><?php echo e(__('button_update_pet_submit', [], $GLOBALS['current_language'] ?? 'en')); // "Update Pet Profile" ?></span>
                        <span class="spinner-border spinner-border-sm ms-2 d-none" role="status" aria-hidden="true"></span>
                    </button>
                    <a href="<?php echo e(base_url('/pet-profile')); ?>" class="btn btn-link text-muted ms-2"><?php echo e(__('button_cancel', [], $GLOBALS['current_language'] ?? 'en')); ?></a>
                </div>
            </form>
        </div>
    </div>
    <?php else: ?>
        <?php // This part is hit if $pet_data is null AND there was no $error_message initially set, which means no ID was passed.
              // The initial $error_message for no pet_id handles this.
              // If $error_message was set, it's displayed above.
        ?>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const editPetForm = document.getElementById('editPetForm');
    if (!editPetForm) return;

    const editPetButton = document.getElementById('editPetButton');
    const buttonText = editPetButton.querySelector('.button-text');
    const spinner = editPetButton.querySelector('.spinner-border');
    const formMessages = document.getElementById('editPetFormMessages');
    const avatarInput = document.getElementById('pet_avatar_new');
    const newAvatarPreview = document.getElementById('newAvatarPreview');
    const currentAvatarPreview = document.getElementById('currentAvatarPreview');
    const removeAvatarCheckbox = document.getElementById('remove_current_avatar');

    function clearEditPetValidationUI() { /* ... similar to addPetForm ... */
        editPetForm.querySelectorAll('.form-control, .form-select').forEach(el => el.classList.remove('is-invalid'));
        editPetForm.querySelectorAll('.invalid-feedback').forEach(el => el.textContent = '');
        if (formMessages) { formMessages.innerHTML = ''; formMessages.className = 'mb-3'; }
    }
    function displayEditPetFormMessage(message, type = 'danger') { /* ... similar ... */
        if (!formMessages) return;
        formMessages.innerHTML = `<div class="alert alert-${type} alert-dismissible fade show" role="alert">${escapeHtml(message)}<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>`;
    }
    function displayEditPetFieldErrors(errors) { /* ... similar ... */
        for (const field in errors) {
            const inputElement = editPetForm.querySelector(`[name="${field}"]`);
            const errorElementId = `${field.replace('_new', '')}Error`; // Handle avatar_new mapping to avatarError
            const errorDiv = document.getElementById(errorElementId) || document.getElementById(field + 'Error');
            if (inputElement) inputElement.classList.add('is-invalid');
            if (errorDiv) errorDiv.textContent = errors[field];
        }
    }
    function escapeHtml(unsafe) { /* ... similar ... */
        if (typeof unsafe !== 'string') return '';
        return unsafe.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
    }

    if (avatarInput && newAvatarPreview) {
        avatarInput.addEventListener('change', function(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    newAvatarPreview.src = e.target.result;
                    newAvatarPreview.classList.remove('d-none');
                }
                reader.readAsDataURL(file);
                if(removeAvatarCheckbox) removeAvatarCheckbox.checked = false; // Uncheck remove if new file selected
            } else {
                newAvatarPreview.classList.add('d-none');
                newAvatarPreview.src = "#";
            }
        });
    }
    if (removeAvatarCheckbox && currentAvatarPreview) {
        removeAvatarCheckbox.addEventListener('change', function() {
            if (this.checked) {
                currentAvatarPreview.style.opacity = '0.5';
                if(avatarInput) avatarInput.value = ''; // Clear new avatar input if removing current
                if(newAvatarPreview) { newAvatarPreview.classList.add('d-none'); newAvatarPreview.src = "#"; }
            } else {
                currentAvatarPreview.style.opacity = '1';
            }
        });
    }


    editPetForm.addEventListener('submit', async function (event) {
        event.preventDefault();
        clearEditPetValidationUI();

        if (buttonText) buttonText.textContent = '<?php echo e(__('state_text_processing', [], $GLOBALS['current_language'] ?? 'en')); ?>';
        if (spinner) spinner.classList.remove('d-none');
        editPetButton.disabled = true;

        const formData = new FormData(editPetForm);

        try {
            const response = await fetch(editPetForm.action, {
                method: 'POST',
                body: formData,
                headers: { 'Accept': 'application/json' }
            });
            const result = await response.json();

            if (response.ok && result.success) {
                displayEditPetFormMessage(result.message || '<?php echo e(__('edit_pet_alert_success', [], $GLOBALS['current_language'] ?? 'en')); // "Pet profile updated successfully!" ?>', 'success');
                // Optionally update current avatar preview if a new one was set by API
                if (result.new_avatar_path && currentAvatarPreview) {
                    currentAvatarPreview.src = '<?php echo rtrim(UPLOADS_BASE_URL ?? base_url("/uploads"), "/"); ?>/' + result.new_avatar_path.ltrim('/');
                    if(newAvatarPreview) {newAvatarPreview.classList.add('d-none'); newAvatarPreview.src="#";}
                    if(avatarInput) avatarInput.value = '';
                } else if (formData.get('remove_current_avatar') && currentAvatarPreview) {
                    currentAvatarPreview.src = '<?php echo e(base_url("/assets/images/placeholders/pet_avatar_default_64.png")); ?>'; // Or hide it
                }
                // No redirect by default, user stays on page to see changes or make more.
                // Could redirect after delay: setTimeout(() => { window.location.href = '<?php echo e(base_url("/pet-profile")); ?>'; }, 2000);
            } else {
                let errorMessage = result.message || '<?php echo e(__('edit_pet_alert_failed_unknown', [], $GLOBALS['current_language'] ?? 'en')); // "Failed to update pet. Please check errors." ?>';
                if (result.errors) {
                    displayEditPetFieldErrors(result.errors);
                }
                displayEditPetFormMessage(errorMessage, 'danger');
            }
        } catch (error) {
            console.error('Edit Pet submission error:', error);
            displayEditPetFormMessage('<?php echo e(__('edit_pet_alert_failed_network', [], $GLOBALS['current_language'] ?? 'en')); // "A network error occurred. Please try again." ?>', 'danger');
        } finally {
            if (buttonText) buttonText.textContent = '<?php echo e(__('button_update_pet_submit', [], $GLOBALS['current_language'] ?? 'en')); ?>';
            if (spinner) spinner.classList.add('d-none');
            editPetButton.disabled = false;
        }
    });
});
</script>
<?php
// Translation placeholders
// __('page_title_edit_pet', [], $GLOBALS['current_language'] ?? 'en');
// __('error_invalid_pet_id_for_edit', [], $GLOBALS['current_language'] ?? 'en');
// __('error_pet_not_found_or_not_owned', [], $GLOBALS['current_language'] ?? 'en');
// __('page_title_edit_pet_name %s', [], $GLOBALS['current_language'] ?? 'en');
// __('edit_pet_form_title', [], $GLOBALS['current_language'] ?? 'en');
// __('edit_pet_section_avatar', [], $GLOBALS['current_language'] ?? 'en');
// __('label_pet_avatar_new', [], $GLOBALS['current_language'] ?? 'en');
// __('edit_pet_current_avatar_label', [], $GLOBALS['current_language'] ?? 'en');
// __('edit_pet_remove_avatar_checkbox_label', [], $GLOBALS['current_language'] ?? 'en');
// __('alt_new_pet_avatar_preview', [], $GLOBALS['current_language'] ?? 'en');
// __('button_update_pet_submit', [], $GLOBALS['current_language'] ?? 'en');
// __('edit_pet_alert_success', [], $GLOBALS['current_language'] ?? 'en');
// __('edit_pet_alert_failed_unknown', [], $GLOBALS['current_language'] ?? 'en');
// __('edit_pet_alert_failed_network', [], $GLOBALS['current_language'] ?? 'en');
// Reused from add-pet: species_options, size_options, labels for name, species, breed, etc.
?>
