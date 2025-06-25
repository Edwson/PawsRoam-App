<?php
// This page is intended to be included by index.php.
// Assumes $current_language, $pageTitle, and core functions/auth are available.

require_login(); // User must be logged in to add a pet.

$pageTitle = __('page_title_add_pet', [], $GLOBALS['current_language'] ?? 'en'); // "Add New Pet Profile"

// Data for dropdowns (could be moved to constants or a helper)
$pet_species_options = [
    'dog' => __('pet_species_dog', [], $GLOBALS['current_language'] ?? 'en'),
    'cat' => __('pet_species_cat', [], $GLOBALS['current_language'] ?? 'en'),
    'bird' => __('pet_species_bird', [], $GLOBALS['current_language'] ?? 'en'),
    'rabbit' => __('pet_species_rabbit', [], $GLOBALS['current_language'] ?? 'en'),
    'other' => __('pet_species_other', [], $GLOBALS['current_language'] ?? 'en'),
];
$pet_size_options = [
    '' => __('select_option_placeholder', [], $GLOBALS['current_language'] ?? 'en') . __('pet_size_label', [], $GLOBALS['current_language'] ?? 'en'), // "-- Select Size --"
    'small' => __('pet_size_small', [], $GLOBALS['current_language'] ?? 'en'), // "Small (e.g., Chihuahua, Cat)"
    'medium' => __('pet_size_medium', [], $GLOBALS['current_language'] ?? 'en'), // "Medium (e.g., Beagle, Cocker Spaniel)"
    'large' => __('pet_size_large', [], $GLOBALS['current_language'] ?? 'en'), // "Large (e.g., Labrador, German Shepherd)"
    'extra_large' => __('pet_size_extra_large', [], $GLOBALS['current_language'] ?? 'en'), // "Extra Large (e.g., Great Dane, Mastiff)"
];

if (empty($_SESSION[CSRF_TOKEN_NAME ?? 'csrf_token'])) {
    generate_csrf_token(true);
}
?>

<div class="container my-4 my-md-5">
    <div class="row mb-4 align-items-center">
        <div class="col">
            <h1 class="display-6 fw-bold"><?php echo e($pageTitle); ?></h1>
        </div>
        <div class="col text-end">
            <a href="<?php echo e(base_url('/pet-profile')); ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left-circle me-2"></i><?php echo e(__('button_back_to_my_pets', [], $GLOBALS['current_language'] ?? 'en')); // "Back to My Pets" ?>
            </a>
        </div>
    </div>

    <div class="card shadow-lg border-0">
        <div class="card-header bg-primary-orange text-white py-3">
            <h2 class="h4 mb-0"><i class="bi bi-paw-fill me-2"></i><?php echo e(__('add_pet_form_title', [], $GLOBALS['current_language'] ?? 'en')); // "Tell Us About Your Pet" ?></h2>
        </div>
        <div class="card-body p-4 p-md-5">
            <form id="addPetForm" action="<?php echo e(base_url('/api/v1/pets/create.php')); ?>" method="POST" enctype="multipart/form-data" novalidate>
                <?php echo csrf_input_field(); ?>

                <div id="addPetFormMessages" class="mb-3" role="alert" aria-live="assertive"></div>

                <fieldset>
                    <legend class="h5 fw-semibold mb-3 border-bottom pb-2 text-text-dark"><?php echo e(__('add_pet_section_basic_info', [], $GLOBALS['current_language'] ?? 'en')); // "Basic Information" ?></legend>
                    <div class="row g-3">
                        <div class="col-md-6 form-floating mb-3">
                            <input type="text" class="form-control" id="pet_name" name="name" placeholder="<?php echo e(__('placeholder_pet_name', [], $GLOBALS['current_language'] ?? 'en')); ?>" required maxlength="100">
                            <label for="pet_name"><?php echo e(__('label_pet_name', [], $GLOBALS['current_language'] ?? 'en')); // "Pet's Name" ?></label>
                            <div class="invalid-feedback" id="nameError"></div>
                        </div>
                        <div class="col-md-6 form-floating mb-3">
                            <select class="form-select" id="pet_species" name="species" required>
                                <option value="" selected disabled><?php echo e(__('select_option_placeholder', [], $GLOBALS['current_language'] ?? 'en')); ?><?php echo e(__('label_pet_species', [], $GLOBALS['current_language'] ?? 'en')); ?></option>
                                <?php foreach($pet_species_options as $value => $label): ?>
                                <option value="<?php echo e($value); ?>"><?php echo e($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <label for="pet_species"><?php echo e(__('label_pet_species', [], $GLOBALS['current_language'] ?? 'en')); // "Species" ?></label>
                            <div class="invalid-feedback" id="speciesError"></div>
                        </div>
                        <div class="col-md-6 form-floating mb-3">
                            <input type="text" class="form-control" id="pet_breed" name="breed" placeholder="<?php echo e(__('placeholder_pet_breed', [], $GLOBALS['current_language'] ?? 'en')); ?>" maxlength="100">
                            <label for="pet_breed"><?php echo e(__('label_pet_breed', [], $GLOBALS['current_language'] ?? 'en')); // "Breed (Optional)" ?></label>
                            <div class="invalid-feedback" id="breedError"></div>
                        </div>
                         <div class="col-md-6 form-floating mb-3">
                            <input type="date" class="form-control" id="pet_birthdate" name="birthdate" max="<?php echo date('Y-m-d'); // Prevent future dates ?>">
                            <label for="pet_birthdate"><?php echo e(__('label_pet_birthdate', [], $GLOBALS['current_language'] ?? 'en')); // "Birthdate (Optional)" ?></label>
                            <div class="invalid-feedback" id="birthdateError"></div>
                        </div>
                    </div>
                </fieldset>

                <hr class="my-4">

                <fieldset>
                    <legend class="h5 fw-semibold mb-3 border-bottom pb-2 text-text-dark"><?php echo e(__('add_pet_section_physical_details', [], $GLOBALS['current_language'] ?? 'en')); // "Physical Details" ?></legend>
                    <div class="row g-3">
                        <div class="col-md-6 form-floating mb-3">
                            <select class="form-select" id="pet_size" name="size">
                                <?php foreach($pet_size_options as $value => $label): ?>
                                <option value="<?php echo e($value); ?>"><?php echo e($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <label for="pet_size"><?php echo e(__('label_pet_size', [], $GLOBALS['current_language'] ?? 'en')); // "Size (Optional)" ?></label>
                             <div class="invalid-feedback" id="sizeError"></div>
                        </div>
                        <div class="col-md-6 form-floating mb-3">
                            <input type="number" class="form-control" id="pet_weight_kg" name="weight_kg" placeholder="e.g., 5.5" step="0.1" min="0.1" max="150">
                            <label for="pet_weight_kg"><?php echo e(__('label_pet_weight_kg', [], $GLOBALS['current_language'] ?? 'en')); // "Weight (kg, Optional)" ?></label>
                            <div class="invalid-feedback" id="weight_kgError"></div>
                        </div>
                    </div>
                </fieldset>

                <hr class="my-4">

                 <fieldset>
                    <legend class="h5 fw-semibold mb-3 border-bottom pb-2 text-text-dark"><?php echo e(__('add_pet_section_characteristics', [], $GLOBALS['current_language'] ?? 'en')); // "Characteristics & Care" ?></legend>
                    <div class="mb-3">
                        <label for="pet_personality_traits" class="form-label"><?php echo e(__('label_pet_personality', [], $GLOBALS['current_language'] ?? 'en')); // "Personality Traits (Optional)" ?></label>
                        <textarea class="form-control" id="pet_personality_traits" name="personality_traits" rows="3" placeholder="<?php echo e(__('placeholder_pet_personality', [], $GLOBALS['current_language'] ?? 'en')); // "e.g., friendly, playful, shy, loves cuddles" ?>"></textarea>
                        <small class="form-text text-muted"><?php echo e(__('help_text_pet_personality', [], $GLOBALS['current_language'] ?? 'en')); // "Comma-separated traits." ?></small>
                        <div class="invalid-feedback" id="personality_traitsError"></div>
                    </div>
                    <div class="mb-3">
                        <label for="pet_medical_conditions" class="form-label"><?php echo e(__('label_pet_medical', [], $GLOBALS['current_language'] ?? 'en')); // "Medical Conditions (Optional)" ?></label>
                        <textarea class="form-control" id="pet_medical_conditions" name="medical_conditions" rows="3" placeholder="<?php echo e(__('placeholder_pet_medical', [], $GLOBALS['current_language'] ?? 'en')); // "e.g., allergies, past surgeries, medications" ?>"></textarea>
                         <div class="invalid-feedback" id="medical_conditionsError"></div>
                    </div>
                    <div class="mb-3">
                        <label for="pet_dietary_restrictions" class="form-label"><?php echo e(__('label_pet_dietary', [], $GLOBALS['current_language'] ?? 'en')); // "Dietary Restrictions (Optional)" ?></label>
                        <textarea class="form-control" id="pet_dietary_restrictions" name="dietary_restrictions" rows="3" placeholder="<?php echo e(__('placeholder_pet_dietary', [], $GLOBALS['current_language'] ?? 'en')); // "e.g., grain-free, no chicken, specific brand" ?>"></textarea>
                        <div class="invalid-feedback" id="dietary_restrictionsError"></div>
                    </div>
                </fieldset>

                <hr class="my-4">

                <fieldset>
                     <legend class="h5 fw-semibold mb-3 border-bottom pb-2 text-text-dark"><?php echo e(__('add_pet_section_avatar', [], $GLOBALS['current_language'] ?? 'en')); // "Profile Picture" ?></legend>
                    <div class="mb-3">
                        <label for="pet_avatar" class="form-label"><?php echo e(__('label_pet_avatar', [], $GLOBALS['current_language'] ?? 'en')); // "Upload Avatar (Optional)" ?></label>
                        <input class="form-control" type="file" id="pet_avatar" name="avatar" accept="image/jpeg, image/png, image/gif">
                        <small class="form-text text-muted"><?php echo e(__('help_text_pet_avatar', [], $GLOBALS['current_language'] ?? 'en')); // "Max 2MB. JPG, PNG, GIF allowed." ?></small>
                        <div class="invalid-feedback" id="avatarError"></div>
                        <img id="avatarPreview" src="#" alt="<?php echo e(__('alt_pet_avatar_preview', [], $GLOBALS['current_language'] ?? 'en')); // "Avatar Preview" ?>" class="mt-2 img-thumbnail rounded-circle d-none" style="width: 100px; height: 100px; object-fit: cover;"/>
                    </div>
                </fieldset>

                <div class="mt-4 pt-3 border-top">
                    <button type="submit" class="btn btn-primary btn-lg px-5" id="addPetButton">
                        <span class="button-text"><?php echo e(__('button_add_pet_submit', [], $GLOBALS['current_language'] ?? 'en')); // "Add Pet" ?></span>
                        <span class="spinner-border spinner-border-sm ms-2 d-none" role="status" aria-hidden="true"></span>
                    </button>
                    <a href="<?php echo e(base_url('/pet-profile')); ?>" class="btn btn-link text-muted ms-2"><?php echo e(__('button_cancel', [], $GLOBALS['current_language'] ?? 'en')); ?></a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const addPetForm = document.getElementById('addPetForm');
    if (!addPetForm) return;

    const addPetButton = document.getElementById('addPetButton');
    const buttonText = addPetButton.querySelector('.button-text');
    const spinner = addPetButton.querySelector('.spinner-border');
    const formMessages = document.getElementById('addPetFormMessages');
    const avatarInput = document.getElementById('pet_avatar');
    const avatarPreview = document.getElementById('avatarPreview');

    function clearAddPetValidationUI() {
        addPetForm.querySelectorAll('.form-control, .form-select').forEach(el => el.classList.remove('is-invalid'));
        addPetForm.querySelectorAll('.invalid-feedback').forEach(el => el.textContent = '');
        if (formMessages) {
            formMessages.innerHTML = '';
            formMessages.className = 'mb-3';
        }
    }

    function displayAddPetFormMessage(message, type = 'danger') {
        if (!formMessages) return;
        formMessages.innerHTML = `<div class="alert alert-${type} alert-dismissible fade show" role="alert">
                                    ${escapeHtml(message)}
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                 </div>`;
    }

    function displayAddPetFieldErrors(errors) {
        for (const field in errors) {
            const inputElement = addPetForm.querySelector(`[name="${field}"]`);
            const errorElementId = `${field}Error`; // Assumes error div IDs match fieldName + "Error"
            const errorDiv = document.getElementById(errorElementId);

            if (inputElement) inputElement.classList.add('is-invalid');
            if (errorDiv) errorDiv.textContent = errors[field];
        }
    }

    function escapeHtml(unsafe) {
        if (typeof unsafe !== 'string') return '';
        return unsafe.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
    }

    if (avatarInput && avatarPreview) {
        avatarInput.addEventListener('change', function(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    avatarPreview.src = e.target.result;
                    avatarPreview.classList.remove('d-none');
                }
                reader.readAsDataURL(file);
            } else {
                avatarPreview.classList.add('d-none');
                avatarPreview.src = "#";
            }
        });
    }

    addPetForm.addEventListener('submit', async function (event) {
        event.preventDefault();
        clearAddPetValidationUI();

        if (buttonText) buttonText.textContent = '<?php echo e(__('state_text_processing', [], $GLOBALS['current_language'] ?? 'en')); ?>';
        if (spinner) spinner.classList.remove('d-none');
        addPetButton.disabled = true;

        const formData = new FormData(addPetForm);

        try {
            const response = await fetch(addPetForm.action, {
                method: 'POST',
                body: formData, // FormData handles multipart/form-data for file uploads
                headers: { 'Accept': 'application/json' }
            });

            const result = await response.json();

            if (response.ok && result.success) {
                displayAddPetFormMessage(result.message || '<?php echo e(__('add_pet_alert_success', [], $GLOBALS['current_language'] ?? 'en')); // "Pet profile added successfully!" ?>', 'success');
                addPetForm.reset();
                if(avatarPreview) avatarPreview.classList.add('d-none');
                // Redirect to pet profile list or the newly created pet's detail page
                setTimeout(() => {
                    window.location.href = result.redirect_url || '<?php echo e(base_url('/pet-profile')); ?>';
                }, 2000);
            } else {
                let errorMessage = result.message || '<?php echo e(__('add_pet_alert_failed_unknown', [], $GLOBALS['current_language'] ?? 'en')); // "Failed to add pet. Please check errors." ?>';
                if (result.errors) {
                    displayAddPetFieldErrors(result.errors);
                }
                displayAddPetFormMessage(errorMessage, 'danger');
            }
        } catch (error) {
            console.error('Add Pet submission error:', error);
            displayAddPetFormMessage('<?php echo e(__('add_pet_alert_failed_network', [], $GLOBALS['current_language'] ?? 'en')); // "A network error occurred. Please try again." ?>', 'danger');
        } finally {
            if (buttonText) buttonText.textContent = '<?php echo e(__('button_add_pet_submit', [], $GLOBALS['current_language'] ?? 'en')); ?>';
            if (spinner) spinner.classList.add('d-none');
            addPetButton.disabled = false;
        }
    });
});
</script>
<?php
// Placeholder for translation strings for this "Add Pet" page
// __('page_title_add_pet', [], $GLOBALS['current_language'] ?? 'en');
// __('button_back_to_my_pets', [], $GLOBALS['current_language'] ?? 'en');
// __('add_pet_form_title', [], $GLOBALS['current_language'] ?? 'en');
// __('add_pet_section_basic_info', [], $GLOBALS['current_language'] ?? 'en');
// __('placeholder_pet_name', [], $GLOBALS['current_language'] ?? 'en');
// __('label_pet_name', [], $GLOBALS['current_language'] ?? 'en');
// __('select_option_placeholder', [], $GLOBALS['current_language'] ?? 'en'); // Generic "-- Select -- "
// __('label_pet_species', [], $GLOBALS['current_language'] ?? 'en');
// __('pet_species_dog', [], $GLOBALS['current_language'] ?? 'en');
// __('pet_species_cat', [], $GLOBALS['current_language'] ?? 'en');
// __('pet_species_bird', [], $GLOBALS['current_language'] ?? 'en');
// __('pet_species_rabbit', [], $GLOBALS['current_language'] ?? 'en');
// __('pet_species_other', [], $GLOBALS['current_language'] ?? 'en');
// __('placeholder_pet_breed', [], $GLOBALS['current_language'] ?? 'en');
// __('label_pet_breed', [], $GLOBALS['current_language'] ?? 'en');
// __('label_pet_birthdate', [], $GLOBALS['current_language'] ?? 'en');
// __('add_pet_section_physical_details', [], $GLOBALS['current_language'] ?? 'en');
// __('label_pet_size', [], $GLOBALS['current_language'] ?? 'en');
// __('pet_size_label', [], $GLOBALS['current_language'] ?? 'en'); // Used after placeholder like "-- Select Size --"
// __('pet_size_small', [], $GLOBALS['current_language'] ?? 'en');
// __('pet_size_medium', [], $GLOBALS['current_language'] ?? 'en');
// __('pet_size_large', [], $GLOBALS['current_language'] ?? 'en');
// __('pet_size_extra_large', [], $GLOBALS['current_language'] ?? 'en');
// __('label_pet_weight_kg', [], $GLOBALS['current_language'] ?? 'en');
// __('add_pet_section_characteristics', [], $GLOBALS['current_language'] ?? 'en');
// __('label_pet_personality', [], $GLOBALS['current_language'] ?? 'en');
// __('placeholder_pet_personality', [], $GLOBALS['current_language'] ?? 'en');
// __('help_text_pet_personality', [], $GLOBALS['current_language'] ?? 'en');
// __('label_pet_medical', [], $GLOBALS['current_language'] ?? 'en');
// __('placeholder_pet_medical', [], $GLOBALS['current_language'] ?? 'en');
// __('label_pet_dietary', [], $GLOBALS['current_language'] ?? 'en');
// __('placeholder_pet_dietary', [], $GLOBALS['current_language'] ?? 'en');
// __('add_pet_section_avatar', [], $GLOBALS['current_language'] ?? 'en');
// __('label_pet_avatar', [], $GLOBALS['current_language'] ?? 'en');
// __('help_text_pet_avatar', [], $GLOBALS['current_language'] ?? 'en');
// __('alt_pet_avatar_preview', [], $GLOBALS['current_language'] ?? 'en');
// __('button_add_pet_submit', [], $GLOBALS['current_language'] ?? 'en');
// __('add_pet_alert_success', [], $GLOBALS['current_language'] ?? 'en');
// __('add_pet_alert_failed_unknown', [], $GLOBALS['current_language'] ?? 'en');
// __('add_pet_alert_failed_network', [], $GLOBALS['current_language'] ?? 'en');
// Reused: __('button_cancel', [], $GLOBALS['current_language'] ?? 'en');
// Reused: __('state_text_processing', [], $GLOBALS['current_language'] ?? 'en');
?>
