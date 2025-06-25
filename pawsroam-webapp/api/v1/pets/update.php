<?php
/**
 * API Endpoint for Updating an Existing Pet Profile
 * Method: POST
 * Expected FormData: pet_id, name, species, [breed], [size], [weight_kg], [birthdate],
 *                    [personality_traits], [medical_conditions], [dietary_restrictions],
 *                    [avatar_new (file)], [remove_current_avatar (checkbox value '1')], csrf_token
 */

// Bootstrap
if (session_status() == PHP_SESSION_NONE) {
    session_start(['cookie_httponly' => true, 'cookie_secure' => isset($_SERVER['HTTPS']), 'cookie_samesite' => 'Lax']);
}

if (!defined('BASE_PATH')) { define('BASE_PATH', dirname(__DIR__, 4)); }
if (!defined('DS')) { define('DS', DIRECTORY_SEPARATOR); }

$required_files = [
    BASE_PATH . DS . 'config' . DS . 'constants.php',
    BASE_PATH . DS . 'config' . DS . 'database.php',
    BASE_PATH . DS . 'includes' . DS . 'functions.php',
    BASE_PATH . DS . 'includes' . DS . 'translation.php',
    BASE_PATH . DS . 'includes' . DS . 'auth.php'
];
foreach ($required_files as $file) {
    if (file_exists($file)) { require_once $file; }
    else {
        http_response_code(500); header('Content-Type: application/json');
        error_log("CRITICAL: Update Pet API failed to load core file: " . $file);
        echo json_encode(['success' => false, 'message' => 'Server configuration error.']); exit;
    }
}

$current_api_language = $GLOBALS['current_language'] ?? DEFAULT_LANGUAGE ?? 'en';
header('Content-Type: application/json');

// --- Access Control & Request Method Validation ---
require_login();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => __('error_method_not_allowed', [], $current_api_language)]);
    exit;
}

// --- CSRF Token Validation ---
if (!validate_csrf_token($_POST[CSRF_TOKEN_NAME ?? 'csrf_token'] ?? null)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => __('error_csrf_token_invalid', [], $current_api_language)]);
    exit;
}

// --- Input Collection & Pet Ownership Verification ---
$user_id = current_user_id();
$pet_id = filter_var($_POST['pet_id'] ?? null, FILTER_VALIDATE_INT);
$errors = [];
$current_pet_data = null;

if (!$pet_id || $pet_id <= 0) {
    $errors['pet_id'] = __('error_invalid_pet_id_for_edit_api', [], $current_api_language); // "Invalid pet ID for update."
} else {
    try {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM user_pets WHERE id = :pet_id AND user_id = :user_id LIMIT 1");
        $stmt->bindParam(':pet_id', $pet_id, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();
        $current_pet_data = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$current_pet_data) {
            $errors['pet_id'] = __('error_pet_not_found_or_not_owned_api', [], $current_api_language); // "Pet not found or you're not authorized to edit it."
        }
    } catch (PDOException $e) {
        error_log("Update Pet API: DB error fetching pet for ownership check (PetID: {$pet_id}, UserID: {$user_id}): " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => __('error_server_generic', [], $current_api_language)]);
        exit;
    }
}

$name = trim($_POST['name'] ?? '');
// ... (collect other fields similarly to create.php)
$species = trim($_POST['species'] ?? '');
$breed = trim($_POST['breed'] ?? null);
$size = trim($_POST['size'] ?? null);
$weight_kg = trim($_POST['weight_kg'] ?? null);
$birthdate = trim($_POST['birthdate'] ?? null);
$personality_traits = trim($_POST['personality_traits'] ?? null);
$medical_conditions = trim($_POST['medical_conditions'] ?? null);
$dietary_restrictions = trim($_POST['dietary_restrictions'] ?? null);
$remove_current_avatar = isset($_POST['remove_current_avatar']) && $_POST['remove_current_avatar'] == '1';


// --- Input Validation (similar to create.php, but for updates) ---
// Name
if (empty($name)) { $errors['name'] = __('error_pet_name_required', [], $current_api_language); }
elseif (strlen($name) > 100) { $errors['name'] = __('error_pet_name_too_long', [], $current_api_language); }
// Species
$allowed_species = ['dog', 'cat', 'bird', 'rabbit', 'other'];
if (empty($species)) { $errors['species'] = __('error_pet_species_required', [], $current_api_language); }
elseif (!in_array($species, $allowed_species)) { $errors['species'] = __('error_pet_species_invalid', [], $current_api_language); }
// Breed
if ($breed !== null && strlen($breed) > 100) { $errors['breed'] = __('error_pet_breed_too_long', [], $current_api_language); }
// Size
$allowed_sizes = ['small', 'medium', 'large', 'extra_large'];
if ($size !== null && !empty($size) && !in_array($size, $allowed_sizes)) { $errors['size'] = __('error_pet_size_invalid', [], $current_api_language); }
elseif (empty($size)) { $size = null; }
// Weight
if ($weight_kg !== null && !empty($weight_kg)) {
    if (!is_numeric($weight_kg) || (float)$weight_kg <= 0 || (float)$weight_kg > 200) { $errors['weight_kg'] = __('error_pet_weight_invalid', [], $current_api_language); }
    else { $weight_kg = (float)$weight_kg; }
} elseif (empty($weight_kg)) { $weight_kg = null; }
// Birthdate
if ($birthdate !== null && !empty($birthdate)) {
    $d = DateTime::createFromFormat('Y-m-d', $birthdate);
    if (!$d || $d->format('Y-m-d') !== $birthdate) { $errors['birthdate'] = __('error_pet_birthdate_invalid_format', [], $current_api_language); }
    elseif (new DateTime($birthdate) > new DateTime()) { $errors['birthdate'] = __('error_pet_birthdate_future', [], $current_api_language); }
} elseif (empty($birthdate)) { $birthdate = null; }

// JSON fields (using function from create.php, ensure it's available or redefine)
if (!function_exists('prepare_json_field')) { // Basic re-definition if not included via a shared functions file yet by create.php
    function prepare_json_field($input_string) {
        if ($input_string === null || $input_string === '') return null;
        json_decode($input_string); if (json_last_error() == JSON_ERROR_NONE) return $input_string;
        $array = array_map('trim', explode(',', $input_string)); $array = array_filter($array);
        return !empty($array) ? json_encode($array) : null;
    }
}
$personality_traits_json = prepare_json_field($personality_traits);
$medical_conditions_json = prepare_json_field($medical_conditions);
$dietary_restrictions_json = prepare_json_field($dietary_restrictions);

// --- Avatar Update Logic ---
$new_avatar_db_path = $current_pet_data['avatar_path'] ?? null; // Start with current path
$old_avatar_to_delete_on_success = null;

if ($remove_current_avatar) {
    if (!empty($current_pet_data['avatar_path'])) {
        $old_avatar_to_delete_on_success = $current_pet_data['avatar_path'];
    }
    $new_avatar_db_path = null; // Set to null for DB update
}

// Check for new avatar upload only if not removing current one (or if remove is false and new is provided)
if (!$remove_current_avatar && isset($_FILES['avatar_new']) && $_FILES['avatar_new']['error'] !== UPLOAD_ERR_NO_FILE) {
    if ($_FILES['avatar_new']['error'] === UPLOAD_ERR_OK) {
        $target_pet_avatar_dir = 'pet-photos' . DS . $user_id;
        $allowed_mimes = defined('PET_AVATAR_ALLOWED_MIME_TYPES') ? unserialize(PET_AVATAR_ALLOWED_MIME_TYPES) : ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = (defined('DEFAULT_MAX_UPLOAD_SIZE_MB') ? DEFAULT_MAX_UPLOAD_SIZE_MB * 1024 * 1024 : 2 * 1024 * 1024);

        $upload_result = handle_file_upload('avatar_new', $target_pet_avatar_dir, $allowed_mimes, $max_size, 'pet_avatar_' . $user_id . '_');

        if ($upload_result['success']) {
            if (!empty($current_pet_data['avatar_path'])) { // If there was an old avatar
                $old_avatar_to_delete_on_success = $current_pet_data['avatar_path'];
            }
            $new_avatar_db_path = $upload_result['filepath']; // Path for DB
        } else {
            $errors['avatar_new'] = $upload_result['message'];
        }
    } else {
        // New file submitted but had a PHP upload error
        $upload_error_fetch = handle_file_upload('avatar_new', 'pet-photos'); // Call to get translated error
        $errors['avatar_new'] = $upload_error_fetch['message'];
    }
}


// --- Process or Return Errors ---
if (!empty($errors)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => __('error_validation_failed', [], $current_api_language), 'errors' => $errors]);
    exit;
}

// --- Database Update ---
// Construct SET clause dynamically based on what actually changed or needs to be set
$fields_to_update_sql = [];
$params_to_bind = [':pet_id' => $pet_id, ':user_id' => $user_id]; // user_id in WHERE for safety

// Compare with $current_pet_data to see what changed
if ($name !== $current_pet_data['name']) { $fields_to_update_sql[] = "name = :name"; $params_to_bind[':name'] = $name; }
if ($species !== $current_pet_data['species']) { $fields_to_update_sql[] = "species = :species"; $params_to_bind[':species'] = $species; }
if ($breed !== $current_pet_data['breed']) { $fields_to_update_sql[] = "breed = :breed"; $params_to_bind[':breed'] = $breed; } // Handles NULL
if ($size !== $current_pet_data['size']) { $fields_to_update_sql[] = "size = :size"; $params_to_bind[':size'] = $size; }
if ($weight_kg !== ($current_pet_data['weight_kg'] === null ? null : (float)$current_pet_data['weight_kg'])) { $fields_to_update_sql[] = "weight_kg = :weight_kg"; $params_to_bind[':weight_kg'] = $weight_kg; }
if ($birthdate !== $current_pet_data['birthdate']) { $fields_to_update_sql[] = "birthdate = :birthdate"; $params_to_bind[':birthdate'] = $birthdate; }

// For JSON fields, compare the JSON string representation or the source text
if ($personality_traits_json !== $current_pet_data['personality_traits']) { $fields_to_update_sql[] = "personality_traits = :personality_traits"; $params_to_bind[':personality_traits'] = $personality_traits_json; }
if ($medical_conditions_json !== $current_pet_data['medical_conditions']) { $fields_to_update_sql[] = "medical_conditions = :medical_conditions"; $params_to_bind[':medical_conditions'] = $medical_conditions_json; }
if ($dietary_restrictions_json !== $current_pet_data['dietary_restrictions']) { $fields_to_update_sql[] = "dietary_restrictions = :dietary_restrictions"; $params_to_bind[':dietary_restrictions'] = $dietary_restrictions_json; }

// Avatar path change
if ($new_avatar_db_path !== $current_pet_data['avatar_path']) {
    $fields_to_update_sql[] = "avatar_path = :avatar_path";
    $params_to_bind[':avatar_path'] = $new_avatar_db_path;
}


if (empty($fields_to_update_sql)) {
    http_response_code(200);
    echo json_encode(['success' => true, 'message' => __('edit_pet_no_changes_detected', [], $current_api_language)]); // "No changes detected."
    exit;
}

try {
    $sql = "UPDATE user_pets SET " . implode(', ', $fields_to_update_sql) . " WHERE id = :pet_id AND user_id = :user_id";
    $stmt_update = $db->prepare($sql);

    // Bind all parameters that were added
    foreach($params_to_bind as $key => &$val) { // Pass $val by reference
        $stmt_update->bindParam($key, $val); // Type will be inferred by PDO or can be set explicitly if needed
    }
    unset($val); // Break the reference

    if ($stmt_update->execute()) {
        // If update was successful and an old avatar needs to be deleted
        if ($old_avatar_to_delete_on_success && defined('UPLOADS_BASE_PATH')) {
            $full_old_avatar_path = rtrim(UPLOADS_BASE_PATH, DS) . DS . ltrim($old_avatar_to_delete_on_success, DS);
            if (file_exists($full_old_avatar_path) && is_file($full_old_avatar_path)) {
                if (!unlink($full_old_avatar_path)) {
                    error_log("Update Pet API: Failed to delete old avatar file: {$full_old_avatar_path}");
                    // Non-fatal, profile was updated. Could add a note to success message.
                } else {
                     error_log("Update Pet API: Successfully deleted old avatar file: {$full_old_avatar_path}");
                }
            }
        }

        http_response_code(200); // OK
        echo json_encode([
            'success' => true,
            'message' => __('edit_pet_api_success', [], $current_api_language), // "Pet profile updated successfully!"
            'pet_id' => $pet_id,
            'new_avatar_path' => $new_avatar_db_path // Send back new path for UI update
        ]);
    } else {
        error_log("Update Pet API: DB execution error for PetID {$pet_id}, UserID {$user_id}.");
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => __('edit_pet_api_failed_db', [], $current_api_language)]); // "Failed to update pet profile due to a database error."
    }

} catch (PDOException $e) {
    error_log("Update Pet API (PDOException): " . $e->getMessage() . " for PetID {$pet_id}, UserID {$user_id}.");
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => __('error_server_generic', [], $current_api_language)]);
} catch (Exception $e) {
    error_log("Update Pet API (Exception): " . $e->getMessage() . " for PetID {$pet_id}, UserID {$user_id}.");
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => __('error_server_generic', [], $current_api_language)]);
}

exit;

<?php
// Translation string placeholders
// __('error_invalid_pet_id_for_edit_api', [], $current_api_language);
// __('error_pet_not_found_or_not_owned_api', [], $current_api_language);
// __('edit_pet_no_changes_detected', [], $current_api_language);
// __('edit_pet_api_success', [], $current_api_language);
// __('edit_pet_api_failed_db', [], $current_api_language);
// Plus all validation errors from create.php, if specific keys are needed.
?>
