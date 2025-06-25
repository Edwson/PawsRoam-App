<?php
require_login();

$pageTitle = __('page_title_view_pet_default', [], $GLOBALS['current_language'] ?? 'en'); // "View Pet Profile"
$user_id = current_user_id();
$pet_id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
$pet_data = null;
$error_message = null;

if (!$pet_id) {
    $error_message = __('error_invalid_pet_id_for_view', [], $GLOBALS['current_language'] ?? 'en'); // "No pet specified or invalid ID for viewing."
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
            $error_message = __('error_pet_not_found_or_not_owned_view', [], $GLOBALS['current_language'] ?? 'en'); // "Pet profile not found or you do not have permission to view it."
            http_response_code(404);
        } else {
            $pageTitle = sprintf(__('page_title_view_pet_name %s', [], $GLOBALS['current_language'] ?? 'en'), e($pet_data['name'])); // "Viewing Profile for [Pet Name]"
        }
    } catch (PDOException $e) {
        error_log("Database error fetching pet for view (ID: {$pet_id}, User: {$user_id}): " . $e->getMessage());
        $error_message = __('error_pet_profiles_load_failed_db', [], $GLOBALS['current_language'] ?? 'en'); // Reusing
        http_response_code(500);
    }
}

if (empty($pageTitle) && $error_message) { // Set error title if needed
    $pageTitle = __('page_title_error', [], $GLOBALS['current_language'] ?? 'en') . " - " . (defined('APP_NAME') ? APP_NAME : 'PawsRoam');
}

/**
 * Helper to display a JSON field as a list or simple text.
 */
function display_json_field_as_list($json_string, $default_text = 'N/A') {
    if (empty($json_string)) return e($default_text);
    $data = json_decode($json_string, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($data) && !empty($data)) {
        if (count($data) > 1) {
            $html = '<ul class="list-unstyled mb-0">';
            foreach ($data as $item) {
                $html .= '<li><i class="bi bi-dot me-1"></i>' . e($item) . '</li>';
            }
            $html .= '</ul>';
            return $html;
        } elseif (count($data) === 1) {
            return e($data[0]);
        }
    } elseif (!empty($json_string) && (json_last_error() !== JSON_ERROR_NONE || !is_array($data))) {
        // If it's not valid JSON or not an array, but has content, display as is (escaped)
        // This handles cases where plain text was entered instead of comma-separated for traits.
        return nl2br(e($json_string));
    }
    return e($default_text);
}
?>

<div class="container my-4 my-md-5">
    <div class="row mb-4 align-items-center">
        <div class="col">
            <h1 class="display-6 fw-bold"><?php echo e($pageTitle); ?></h1>
        </div>
        <div class="col text-end">
            <a href="<?php echo e(base_url('/pet-profile')); ?>" class="btn btn-outline-secondary me-2">
                <i class="bi bi-arrow-left-circle me-2"></i><?php echo e(__('button_back_to_my_pets', [], $GLOBALS['current_language'] ?? 'en')); ?>
            </a>
            <?php if ($pet_data): ?>
            <a href="<?php echo e(base_url('/pets/edit/' . $pet_data['id'])); ?>" class="btn btn-primary">
                <i class="bi bi-pencil-square me-2"></i><?php echo e(__('button_edit_this_pet', [], $GLOBALS['current_language'] ?? 'en')); // "Edit This Pet" ?>
            </a>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($error_message): ?>
        <div class="alert alert-danger shadow-sm" role="alert">
            <h4 class="alert-heading"><i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo e(__('error_oops_title', [], $GLOBALS['current_language'] ?? 'en')); ?></h4>
            <p><?php echo e($error_message); ?></p>
        </div>
    <?php elseif ($pet_data): ?>
    <div class="card shadow-lg border-0">
        <div class="row g-0">
            <div class="col-md-4 bg-light text-center p-4 d-flex flex-column align-items-center justify-content-center border-end">
                <?php
                    $avatar_url = base_url('/assets/images/placeholders/pet_avatar_default_200.png'); // Larger default
                    if (!empty($pet_data['avatar_path']) && defined('UPLOADS_BASE_URL')) {
                        $avatar_url = rtrim(UPLOADS_BASE_URL, '/') . '/' . ltrim($pet_data['avatar_path'], '/');
                    }
                ?>
                <img src="<?php echo e($avatar_url); ?>"
                     alt="<?php echo e(sprintf(__('pet_avatar_alt %s', [], $GLOBALS['current_language'] ?? 'en'), e($pet_data['name']))); ?>"
                     class="img-fluid rounded-circle shadow mb-3" style="width: 200px; height: 200px; object-fit: cover;">
                <h2 class="h3 text-primary-orange mb-1"><?php echo e($pet_data['name']); ?></h2>
                <p class="text-muted mb-0">
                    <?php echo e(ucfirst($pet_data['species'])); ?>
                    <?php if (!empty($pet_data['breed'])): ?>
                        &bull; <?php echo e(e($pet_data['breed'])); ?>
                    <?php endif; ?>
                </p>
            </div>
            <div class="col-md-8">
                <div class="card-body p-4 p-lg-5">
                    <h3 class="h5 fw-semibold mb-3 border-bottom pb-2 text-text-dark"><?php echo e(__('view_pet_section_details', [], $GLOBALS['current_language'] ?? 'en')); // "Pet Details" ?></h3>
                    <dl class="row">
                        <dt class="col-sm-4 col-md-3"><?php echo e(__('label_pet_name', [], $GLOBALS['current_language'] ?? 'en')); ?></dt>
                        <dd class="col-sm-8 col-md-9"><?php echo e($pet_data['name']); ?></dd>

                        <dt class="col-sm-4 col-md-3"><?php echo e(__('label_pet_species', [], $GLOBALS['current_language'] ?? 'en')); ?></dt>
                        <dd class="col-sm-8 col-md-9"><?php echo e(ucfirst($pet_data['species'])); ?></dd>

                        <?php if (!empty($pet_data['breed'])): ?>
                        <dt class="col-sm-4 col-md-3"><?php echo e(__('label_pet_breed', [], $GLOBALS['current_language'] ?? 'en')); ?></dt>
                        <dd class="col-sm-8 col-md-9"><?php echo e($pet_data['breed']); ?></dd>
                        <?php endif; ?>

                        <?php if (!empty($pet_data['birthdate'])): ?>
                        <dt class="col-sm-4 col-md-3"><?php echo e(__('label_pet_birthdate', [], $GLOBALS['current_language'] ?? 'en')); ?></dt>
                        <dd class="col-sm-8 col-md-9"><?php echo e(date("F j, Y", strtotime($pet_data['birthdate']))); ?> (<?php
                            $birthDate = new DateTime($pet_data['birthdate']);
                            $today = new DateTime();
                            $age = $today->diff($birthDate);
                            echo e(sprintf(__("pet_age_years_months %d %d", [], $GLOBALS['current_language'] ?? 'en'), $age->y, $age->m)); // "Approx. %d years, %d months old"
                        ?>)</dd>
                        <?php endif; ?>

                        <?php if (!empty($pet_data['size'])): ?>
                        <dt class="col-sm-4 col-md-3"><?php echo e(__('label_pet_size', [], $GLOBALS['current_language'] ?? 'en')); ?></dt>
                        <dd class="col-sm-8 col-md-9"><?php echo e(ucfirst($pet_data['size'])); ?></dd>
                        <?php endif; ?>

                        <?php if (!empty($pet_data['weight_kg'])): ?>
                        <dt class="col-sm-4 col-md-3"><?php echo e(__('label_pet_weight_kg', [], $GLOBALS['current_language'] ?? 'en')); ?></dt>
                        <dd class="col-sm-8 col-md-9"><?php echo e($pet_data['weight_kg']); ?> kg</dd>
                        <?php endif; ?>
                    </dl>

                    <?php if (!empty($pet_data['personality_traits'])): ?>
                    <h4 class="h6 fw-semibold mt-4 mb-2 text-text-dark"><?php echo e(__('label_pet_personality', [], $GLOBALS['current_language'] ?? 'en')); ?></h4>
                    <div class="ps-2"><?php echo display_json_field_as_list($pet_data['personality_traits']); ?></div>
                    <?php endif; ?>

                    <?php if (!empty($pet_data['medical_conditions'])): ?>
                    <h4 class="h6 fw-semibold mt-4 mb-2 text-text-dark"><?php echo e(__('label_pet_medical', [], $GLOBALS['current_language'] ?? 'en')); ?></h4>
                     <div class="ps-2"><?php echo display_json_field_as_list($pet_data['medical_conditions']); ?></div>
                    <?php endif; ?>

                    <?php if (!empty($pet_data['dietary_restrictions'])): ?>
                    <h4 class="h6 fw-semibold mt-4 mb-2 text-text-dark"><?php echo e(__('label_pet_dietary', [], $GLOBALS['current_language'] ?? 'en')); ?></h4>
                     <div class="ps-2"><?php echo display_json_field_as_list($pet_data['dietary_restrictions']); ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>
<?php
// Translation placeholders
// __('page_title_view_pet_default', [], $GLOBALS['current_language'] ?? 'en');
// __('error_invalid_pet_id_for_view', [], $GLOBALS['current_language'] ?? 'en');
// __('error_pet_not_found_or_not_owned_view', [], $GLOBALS['current_language'] ?? 'en');
// __('page_title_view_pet_name %s', [], $GLOBALS['current_language'] ?? 'en');
// __('button_edit_this_pet', [], $GLOBALS['current_language'] ?? 'en');
// __('view_pet_section_details', [], $GLOBALS['current_language'] ?? 'en');
// __("pet_age_years_months %d %d", [], $GLOBALS['current_language'] ?? 'en');
// Reused: error_oops_title, error_pet_profiles_load_failed_db, button_back_to_my_pets, pet_avatar_alt %s, labels for name, species, etc.
?>
