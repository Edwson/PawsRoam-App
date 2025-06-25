<?php
// This page is intended to be included by index.php.
// Assumes $current_language, $pageTitle, and core functions/auth are available.

require_login(); // Redirects to login if user is not authenticated.

$user_id = current_user_id();
$pageTitle = __('page_title_pet_profiles', [], $GLOBALS['current_language'] ?? 'en'); // "My Pet Profiles"

// In a full implementation, you would fetch the user's pets here:
// try {
//     $db = Database::getInstance()->getConnection();
//     $stmt = $db->prepare("SELECT id, name, species, breed, avatar_path FROM user_pets WHERE user_id = :user_id ORDER BY name ASC");
//     $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
//     $stmt->execute();
//     $pets = $stmt->fetchAll(PDO::FETCH_ASSOC);
// } catch (PDOException $e) {
//     error_log("Database error fetching pet profiles for user ID {$user_id}: " . $e->getMessage());
//     $pets = [];
//     $page_error_message = __('error_pet_profiles_load_failed_db', [], $GLOBALS['current_language'] ?? 'en');
// }
// $pets = []; // Stub: No pets fetched for now, so the "none found" message will show.
$page_error_message = null;

try {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT id, name, species, breed, avatar_path FROM user_pets WHERE user_id = :user_id ORDER BY name ASC");
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $pets = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database error fetching pet profiles for user ID {$user_id}: " . $e->getMessage());
    $pets = [];
    $page_error_message = __('error_pet_profiles_load_failed_db', [], $GLOBALS['current_language'] ?? 'en');
}

?>

<div class="container my-4 my-md-5">
    <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
        <h1 class="display-6 fw-bold"><?php echo e($pageTitle); ?></h1>
        <a href="<?php echo e(base_url('/pets/add')); ?>" class="btn btn-primary btn-lg shadow-sm" title="<?php echo e(__('tooltip_add_new_pet_now', [], $GLOBALS['current_language'] ?? 'en')); // "Add a new pet to your profile" ?>">
            <i class="bi bi-plus-circle-fill me-2"></i><?php echo e(__('button_add_new_pet', [], $GLOBALS['current_language'] ?? 'en')); ?>
        </a>
    </div>

    <?php if (isset($page_error_message) && $page_error_message): ?>
        <div class="alert alert-danger" role="alert">
            <?php echo e($page_error_message); ?>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-light py-3">
            <h2 class="h5 mb-0 fw-semibold text-text-dark"><i class="bi bi-hearts me-2 text-primary-orange"></i><?php echo e(__('pet_profiles_list_title', [], $GLOBALS['current_language'] ?? 'en')); ?></h2>
        </div>
        <div class="card-body p-lg-4">
            <?php if (empty($pets)): // This will always be true for the stub ?>
                <div class="text-center py-5 my-3">
                    <img src="<?php echo e(base_url('/assets/images/placeholders/empty_state_pets.svg')); ?>" alt="<?php echo e(__('alt_text_no_pets_illustration', [], $GLOBALS['current_language'] ?? 'en')); // "Illustration of empty pet carrier" ?>" style="max-width: 200px; opacity: 0.7;" class="mb-4">
                    <h3 class="h4 text-muted"><?php echo e(__('pet_profiles_none_found_message', [], $GLOBALS['current_language'] ?? 'en')); ?></h3>
                    <p class="text-muted col-md-8 col-lg-6 mx-auto">
                        <?php
                        // Example of more complex string formatting for translation
                        $add_pet_link_text = e(__('button_add_your_first_pet_link_text', [], $GLOBALS['current_language'] ?? 'en')); // "add your first pet profile"
                        $add_pet_link = '<a href="'.base_url('/pets/add').'" class="text-primary-orange fw-semibold disabled" title="'.e(__('tooltip_add_first_pet', [], $GLOBALS['current_language'] ?? 'en')).'" aria-disabled="true">'.$add_pet_link_text.'</a>';
                        echo sprintf(
                            e(__('pet_profiles_add_one_prompt_html %s', [], $GLOBALS['current_language'] ?? 'en')), // "It looks a bit empty here! Why not %s and share details about your furry, feathery, or scaly friend?"
                            $add_pet_link
                        );
                        ?>
                    </p>
                    <p class="mt-4"><small class="text-muted"><?php echo e(__('pet_profiles_stub_note', [], $GLOBALS['current_language'] ?? 'en')); ?></small></p>
                </div>
            <?php else: ?>
                <?php /* This part is for future implementation when $pets array is populated */ ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($pets as $pet): ?>
                        <div class="list-group-item list-group-item-action d-md-flex justify-content-between align-items-center py-3 px-0">
                            <div class="d-flex align-items-center mb-2 mb-md-0">
                                <img src="<?php echo e(base_url(!empty($pet['avatar_path']) ? $pet['avatar_path'] : '/assets/images/placeholders/pet_avatar_default_64.png')); ?>"
                                     alt="<?php echo e(sprintf(__('pet_avatar_alt %s', [], $GLOBALS['current_language'] ?? 'en'), e($pet['name']))); ?>"
                                     class="rounded-circle me-3 shadow-sm" style="width: 64px; height: 64px; object-fit: cover;">
                                <div>
                                    <h4 class="h5 mb-0 fw-semibold text-primary-orange"><?php echo e($pet['name']); ?></h4>
                                    <small class="text-muted d-block">
                                        <?php echo e(ucfirst($pet['species'])); ?>
                                        <?php if (!empty($pet['breed'])): ?>
                                            &bull; <?php echo e($pet['breed']); ?>
                                        <?php endif; ?>
                                    </small>
                                </div>
                            </div>
                            <div class="mt-2 mt-md-0 text-md-end">
                                <a href="<?php echo e(base_url('/pets/view/' . $pet['id'])); ?>" class="btn btn-sm btn-outline-info me-1" title="<?php echo e(sprintf(__('tooltip_view_pet_profile %s', [], $GLOBALS['current_language'] ?? 'en'), e($pet['name']))); ?>">
                                    <i class="bi bi-eye-fill"></i> <?php echo e(__('button_view', [], $GLOBALS['current_language'] ?? 'en')); ?>
                                </a>
                                <a href="<?php echo e(base_url('/pets/edit/' . $pet['id'])); ?>" class="btn btn-sm btn-outline-primary me-1" title="<?php echo e(sprintf(__('tooltip_edit_pet_profile %s', [], $GLOBALS['current_language'] ?? 'en'), e($pet['name']))); ?>">
                                    <i class="bi bi-pencil-fill"></i> <?php echo e(__('button_edit', [], $GLOBALS['current_language'] ?? 'en')); ?>
                                </a>
                                <button type="button" class="btn btn-sm btn-outline-danger"
                                        data-bs-toggle="modal" data-bs-target="#deletePetModal"
                                        data-pet-id="<?php echo e($pet['id']); ?>" data-pet-name="<?php echo e(e($pet['name'])); ?>" title="<?php echo e(sprintf(__('tooltip_delete_pet_profile %s', [], $GLOBALS['current_language'] ?? 'en'), e($pet['name']))); ?>">
                                    <i class="bi bi-trash-fill"></i> <?php echo e(__('button_delete', [], $GLOBALS['current_language'] ?? 'en')); ?>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php /* Placeholder for "Add/Edit Pet" Modal or separate page form elements - for future */ ?>
    <!-- Delete Pet Confirmation Modal (Bootstrap 5) -->
    <div class="modal fade" id="deletePetModal" tabindex="-1" aria-labelledby="deletePetModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="deletePetModalLabel"><i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo e(__('modal_title_delete_pet_confirm_generic', [], $GLOBALS['current_language'] ?? 'en')); // "Confirm Pet Deletion" ?></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p id="deletePetModalText"><?php echo e(__('modal_body_delete_pet_warning_generic', [], $GLOBALS['current_language'] ?? 'en')); // "Are you sure you want to delete this pet profile? This action cannot be undone." ?></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo e(__('button_cancel', [], $GLOBALS['current_language'] ?? 'en')); ?></button>
                    <form id="deletePetForm" action="<?php echo e(base_url('/api/v1/pets/delete')); ?>" method="POST" style="display:inline;"> <?php // API endpoint to be created ?>
                        <?php echo csrf_input_field(); ?>
                        <input type="hidden" name="pet_id" id="deletePetIdInput" value="">
                        <button type="submit" class="btn btn-danger disabled" id="confirmDeletePetButton" aria-disabled="true"><?php echo e(__('button_delete_confirm', [], $GLOBALS['current_language'] ?? 'en')); ?></button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Basic JS for Pet Profile Page (e.g., handling modal for delete confirmation)
document.addEventListener('DOMContentLoaded', function() {
    const deletePetModalElement = document.getElementById('deletePetModal');
    if (deletePetModalElement) {
        deletePetModalElement.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget; // Button that triggered the modal
            const petId = button.getAttribute('data-pet-id');
            const petName = button.getAttribute('data-pet-name') || 'this pet'; // Fallback

            const modalTitle = deletePetModalElement.querySelector('.modal-title');
            const modalText = deletePetModalElement.querySelector('#deletePetModalText');
            const petIdInput = deletePetModalElement.querySelector('#deletePetIdInput');
            const confirmDeleteButton = deletePetModalElement.querySelector('#confirmDeletePetButton');

            // Dynamically update modal content using translation strings if available client-side, or generic text
            const titleString = '<?php echo e(addslashes(__('modal_title_delete_pet_confirm %s', [], $GLOBALS['current_language'] ?? 'en'))); ?>';
            const bodyString = '<?php echo e(addslashes(__('modal_body_delete_pet_warning %s', [], $GLOBALS['current_language'] ?? 'en'))); ?>';

            modalTitle.innerHTML = '<i class="bi bi-exclamation-triangle-fill me-2"></i>' + titleString.replace('%s', escapeHtml(petName));
            modalText.textContent = bodyString.replace('%s', escapeHtml(petName));

            if(petIdInput) petIdInput.value = petId;
            if(confirmDeleteButton) confirmDeleteButton.disabled = false; // Ensure button is enabled
        });

        const deletePetForm = document.getElementById('deletePetForm');
        if (deletePetForm) {
            deletePetForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                const confirmDeleteButton = deletePetModalElement.querySelector('#confirmDeletePetButton');
                const originalButtonText = confirmDeleteButton.innerHTML; // Preserve original text
                const petIdToDelete = deletePetModalElement.querySelector('#deletePetIdInput').value;

                confirmDeleteButton.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> <?php echo e(addslashes(__('state_text_processing', [], $GLOBALS['current_language'] ?? 'en'))); ?>`;
                confirmDeleteButton.disabled = true;

                const formData = new FormData(deletePetForm);
                // formData.append('pet_id', petIdToDelete); // Already in hidden input

                try {
                    const response = await fetch(deletePetForm.action, {
                        method: 'POST',
                        body: formData,
                        headers: {'Accept': 'application/json'}
                    });
                    const result = await response.json();

                    // Hide the modal regardless of outcome, messages will be shown on page
                    const modalInstance = bootstrap.Modal.getInstance(deletePetModalElement);
                    if (modalInstance) modalInstance.hide();

                    if (response.ok && result.success) {
                        // Simple alert for now, then reload the page to reflect the change.
                        // A more sophisticated UI would remove the item from the list dynamically.
                        alert(result.message || '<?php echo e(addslashes(__('success_pet_profile_deleted_js_alert', [], $GLOBALS['current_language'] ?? 'en' ))); // "Pet profile deleted successfully." ?>');
                        window.location.reload();
                    } else {
                        // Display error message (e.g., in a global message area or as an alert)
                        alert(result.message || '<?php echo e(addslashes(__('error_pet_delete_failed_js_alert', [], $GLOBALS['current_language'] ?? 'en' ))); // "Failed to delete pet profile. Please try again." ?>');
                    }
                } catch (error) {
                    console.error("Delete pet submission error:", error);
                    const modalInstance = bootstrap.Modal.getInstance(deletePetModalElement);
                    if (modalInstance) modalInstance.hide();
                    alert('<?php echo e(addslashes(__('error_pet_delete_network_js_alert', [], $GLOBALS['current_language'] ?? 'en' ))); // "An error occurred. Please check your connection and try again." ?>');
                } finally {
                    // Restore button if not reloading, but we are reloading on success.
                    // If there was an error and no reload, restore button:
                    // confirmDeleteButton.innerHTML = originalButtonText;
                    // confirmDeleteButton.disabled = false;
                }
            });
        }
    }
    function escapeHtml(unsafe) {
        if (typeof unsafe !== 'string') return '';
        return unsafe.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
    }
});
</script>

<?php
// Placeholder for translation strings
// __('page_title_pet_profiles', [], $GLOBALS['current_language'] ?? 'en');
// __('tooltip_add_new_pet', [], $GLOBALS['current_language'] ?? 'en');
// __('button_add_new_pet', [], $GLOBALS['current_language'] ?? 'en');
// __('error_pet_profiles_load_failed_db', [], $GLOBALS['current_language'] ?? 'en');
// __('pet_profiles_list_title', [], $GLOBALS['current_language'] ?? 'en');
// __('alt_text_no_pets_illustration', [], $GLOBALS['current_language'] ?? 'en');
// __('pet_profiles_none_found_message', [], $GLOBALS['current_language'] ?? 'en');
// __('button_add_your_first_pet_link_text', [], $GLOBALS['current_language'] ?? 'en');
// __('pet_profiles_add_one_prompt_html %s', [], $GLOBALS['current_language'] ?? 'en');
// __('tooltip_add_first_pet', [], $GLOBALS['current_language'] ?? 'en');
// __('button_add_your_first_pet', [], $GLOBALS['current_language'] ?? 'en'); // This one might be redundant if using the link text version
// __('pet_profiles_stub_note', [], $GLOBALS['current_language'] ?? 'en');
// __('pet_avatar_alt %s', [], $GLOBALS['current_language'] ?? 'en');
// __('tooltip_view_pet %s', [], $GLOBALS['current_language'] ?? 'en');
// __('button_view', [], $GLOBALS['current_language'] ?? 'en');
// __('tooltip_edit_pet %s', [], $GLOBALS['current_language'] ?? 'en');
// __('button_edit', [], $GLOBALS['current_language'] ?? 'en');
// __('tooltip_delete_pet %s', [], $GLOBALS['current_language'] ?? 'en');
// __('button_delete', [], $GLOBALS['current_language'] ?? 'en');
// __('modal_title_delete_pet_confirm %s', [], $GLOBALS['current_language'] ?? 'en');
// __('modal_title_delete_pet_confirm_generic', [], $GLOBALS['current_language'] ?? 'en');
// __('modal_body_delete_pet_warning %s', [], $GLOBALS['current_language'] ?? 'en');
// __('modal_body_delete_pet_warning_generic', [], $GLOBALS['current_language'] ?? 'en');
// __('button_delete_confirm', [], $GLOBALS['current_language'] ?? 'en');
?>
