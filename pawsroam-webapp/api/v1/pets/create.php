<?php
/**
 * API Endpoint for Creating a New Pet Profile
 * Method: POST
 * Expected FormData: name, species, [breed], [size], [weight_kg], [birthdate],
 *                    [personality_traits], [medical_conditions], [dietary_restrictions],
 *                    [avatar (file)], csrf_token
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
    BASE_PATH . DS . 'includes' . DS . 'auth.php' // For require_login, current_user_id
];
foreach ($required_files as $file) {
    if (file_exists($file)) { require_once $file; }
    else {
        http_response_code(500); header('Content-Type: application/json');
        error_log("CRITICAL: Add Pet API failed to load core file: " . $file);
        echo json_encode(['success' => false, 'message' => 'Server configuration error.']); exit;
    }
}

$current_api_language = $GLOBALS['current_language'] ?? DEFAULT_LANGUAGE ?? 'en';
header('Content-Type: application/json');

// --- Access Control & Request Method Validation ---
require_login(); // Ensures user is logged in
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

// --- Input Collection & Validation ---
$user_id = current_user_id();
$errors = [];

$name = trim($_POST['name'] ?? '');
$species = trim($_POST['species'] ?? '');
$breed = trim($_POST['breed'] ?? null); // Optional
$size = trim($_POST['size'] ?? null); // Optional
$weight_kg = trim($_POST['weight_kg'] ?? null); // Optional
$birthdate = trim($_POST['birthdate'] ?? null); // Optional
$personality_traits = trim($_POST['personality_traits'] ?? null); // Optional, store as JSON
$medical_conditions = trim($_POST['medical_conditions'] ?? null); // Optional, store as JSON
$dietary_restrictions = trim($_POST['dietary_restrictions'] ?? null); // Optional, store as JSON

// Allowed enum values from database schema
$allowed_species = ['dog', 'cat', 'bird', 'rabbit', 'other'];
$allowed_sizes = ['small', 'medium', 'large', 'extra_large']; // '' is also valid if optional

// Name validation
if (empty($name)) {
    $errors['name'] = __('error_pet_name_required', [], $current_api_language);
} elseif (strlen($name) > 100) {
    $errors['name'] = __('error_pet_name_too_long', [], $current_api_language); // "Pet name cannot exceed 100 characters."
}

// Species validation
if (empty($species)) {
    $errors['species'] = __('error_pet_species_required', [], $current_api_language);
} elseif (!in_array($species, $allowed_species)) {
    $errors['species'] = __('error_pet_species_invalid', [], $current_api_language); // "Invalid species selected."
}

// Breed validation (optional)
if ($breed !== null && strlen($breed) > 100) {
    $errors['breed'] = __('error_pet_breed_too_long', [], $current_api_language); // "Breed cannot exceed 100 characters."
}

// Size validation (optional)
if ($size !== null && !empty($size) && !in_array($size, $allowed_sizes)) {
    $errors['size'] = __('error_pet_size_invalid', [], $current_api_language); // "Invalid size selected."
} elseif (empty($size)) {
    $size = null; // Ensure it's NULL if submitted as empty string
}


// Weight validation (optional)
if ($weight_kg !== null && !empty($weight_kg)) {
    if (!is_numeric($weight_kg) || (float)$weight_kg <= 0 || (float)$weight_kg > 200) { // Max 200kg, adjust as needed
        $errors['weight_kg'] = __('error_pet_weight_invalid', [], $current_api_language); // "Invalid weight. Must be a positive number (e.g., 5.5)."
    } else {
        $weight_kg = (float)$weight_kg; // Cast to float
    }
} elseif (empty($weight_kg)) {
    $weight_kg = null;
}


// Birthdate validation (optional)
if ($birthdate !== null && !empty($birthdate)) {
    $d = DateTime::createFromFormat('Y-m-d', $birthdate);
    if (!$d || $d->format('Y-m-d') !== $birthdate) {
        $errors['birthdate'] = __('error_pet_birthdate_invalid_format', [], $current_api_language); // "Invalid birthdate format. Use YYYY-MM-DD."
    } elseif (new DateTime($birthdate) > new DateTime()) {
        $errors['birthdate'] = __('error_pet_birthdate_future', [], $current_api_language); // "Birthdate cannot be in the future."
    }
} elseif (empty($birthdate)) {
    $birthdate = null;
}

// For JSON fields: convert comma-separated strings to JSON array strings or handle as direct JSON if client sends that
// For now, assuming textareas provide plain text. We can store as is or attempt to parse.
// For simplicity, let's assume we store text as is, or if it's meant to be JSON, the client should format it.
// Let's plan to store them as JSON arrays if they contain comma-separated values.
function prepare_json_field($input_string) {
    if ($input_string === null || $input_string === '') return null;
    // Check if it's already a valid JSON string (e.g., from a more advanced UI component)
    json_decode($input_string);
    if (json_last_error() == JSON_ERROR_NONE) {
        return $input_string; // It's already JSON
    }
    // Otherwise, treat as comma-separated and convert to JSON array
    $array = array_map('trim', explode(',', $input_string));
    $array = array_filter($array); // Remove empty elements
    return !empty($array) ? json_encode($array) : null;
}

$personality_traits_json = prepare_json_field($personality_traits);
$medical_conditions_json = prepare_json_field($medical_conditions);
$dietary_restrictions_json = prepare_json_field($dietary_restrictions);


// --- Avatar File Handling (Stub - No actual saving in this step) ---
$avatar_db_path = null; // Default to NULL

// --- Avatar File Handling (Using the full handle_file_upload function) ---
if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] !== UPLOAD_ERR_NO_FILE) { // If a file was actually submitted
    if ($_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $target_pet_avatar_dir = 'pet-photos' . DS . $user_id; // e.g., uploads/pet-photos/123/
        $allowed_mimes = defined('PET_AVATAR_ALLOWED_MIME_TYPES') ? unserialize(PET_AVATAR_ALLOWED_MIME_TYPES) : ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = (defined('DEFAULT_MAX_UPLOAD_SIZE_MB') ? DEFAULT_MAX_UPLOAD_SIZE_MB * 1024 * 1024 : 2 * 1024 * 1024);

        $upload_result = handle_file_upload(
            'avatar',
            $target_pet_avatar_dir,
            $allowed_mimes,
            $max_size,
            'pet_avatar_' . $user_id . '_' // Filename prefix
        );

        if ($upload_result['success']) {
            $avatar_db_path = $upload_result['filepath']; // This is the relative path for DB
        } else {
            $errors['avatar'] = $upload_result['message']; // Error message from handle_file_upload
        }
    } else {
        // File was submitted but had a PHP upload error (other than UPLOAD_ERR_NO_FILE)
        // handle_file_upload itself would catch this if called, but good to be explicit
        // Or, let handle_file_upload manage all error messages from $_FILES.
        // The current handle_file_upload will return specific messages for these errors.
        $upload_result_for_error = handle_file_upload('avatar', 'pet-photos'); // Call to get error message
        $errors['avatar'] = $upload_result_for_error['message'];
    }
}
// If UPLOAD_ERR_NO_FILE, $avatar_db_path remains null, which is fine for an optional field.


// --- Process or Return Errors ---
if (!empty($errors)) {
    http_response_code(422); // Unprocessable Entity
    echo json_encode(['success' => false, 'message' => __('error_validation_failed', [], $current_api_language), 'errors' => $errors]);
    exit;
}

// --- Database Insertion ---
try {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare(
        "INSERT INTO user_pets (user_id, name, species, breed, size, weight_kg, birthdate,
                                personality_traits, medical_conditions, dietary_restrictions, avatar_path, created_at)
         VALUES (:user_id, :name, :species, :breed, :size, :weight_kg, :birthdate,
                 :personality_traits, :medical_conditions, :dietary_restrictions, :avatar_path, NOW())"
    );

    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->bindParam(':name', $name);
    $stmt->bindParam(':species', $species);
    $stmt->bindParam(':breed', $breed); // PDO handles NULL if $breed is null
    $stmt->bindParam(':size', $size);   // PDO handles NULL if $size is null
    $stmt->bindParam(':weight_kg', $weight_kg); // PDO handles NULL if $weight_kg is null
    $stmt->bindParam(':birthdate', $birthdate); // PDO handles NULL if $birthdate is null
    $stmt->bindParam(':personality_traits', $personality_traits_json); // Store as JSON string
    $stmt->bindParam(':medical_conditions', $medical_conditions_json); // Store as JSON string
    $stmt->bindParam(':dietary_restrictions', $dietary_restrictions_json); // Store as JSON string
    $stmt->bindParam(':avatar_path', $avatar_db_path); // Will be NULL for now

    if ($stmt->execute()) {
        $new_pet_id = $db->lastInsertId();
        http_response_code(201); // Created
        echo json_encode([
            'success' => true,
            'message' => __('add_pet_api_success', [], $current_api_language), // "Pet profile created successfully!"
            'pet_id' => $new_pet_id,
            'redirect_url' => base_url('/pet-profile') // Or view page: base_url('/pets/view/' . $new_pet_id)
        ]);
    } else {
        error_log("Add Pet API: DB execution error for user ID {$user_id}, pet name: {$name}.");
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => __('add_pet_api_failed_db', [], $current_api_language)]); // "Failed to create pet profile due to a database error."
    }

} catch (PDOException $e) {
    error_log("Add Pet API (PDOException): " . $e->getMessage() . " for user ID {$user_id}, pet name: {$name}.");
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => __('error_server_generic', [], $current_api_language)]);
} catch (Exception $e) {
    error_log("Add Pet API (Exception): " . $e->getMessage() . " for user ID {$user_id}, pet name: {$name}.");
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => __('error_server_generic', [], $current_api_language)]);
}

exit;

<?php
// Translation string placeholders for this API
// __('error_pet_name_required', [], $current_api_language);
// __('error_pet_name_too_long', [], $current_api_language);
// __('error_pet_species_required', [], $current_api_language);
// __('error_pet_species_invalid', [], $current_api_language);
// __('error_pet_breed_too_long', [], $current_api_language);
// __('error_pet_size_invalid', [], $current_api_language);
// __('error_pet_weight_invalid', [], $current_api_language);
// __('error_pet_birthdate_invalid_format', [], $current_api_language);
// __('error_pet_birthdate_future', [], $current_api_language);
// __('error_pet_avatar_upload_failed_code_1', [], $current_api_language); // ini size
// __('error_pet_avatar_upload_failed_code_2', [], $current_api_language); // form size
// __('error_pet_avatar_upload_failed_code_3', [], $current_api_language); // partial upload
// __('error_pet_avatar_upload_failed_code_4', [], $current_api_language); // no file
// __('error_pet_avatar_upload_failed_code_6', [], $current_api_language); // no temp dir
// __('error_pet_avatar_upload_failed_code_7', [], $current_api_language); // cant write
// __('add_pet_api_success', [], $current_api_language);
// __('add_pet_api_failed_db', [], $current_api_language);
?>
