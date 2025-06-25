<?php
/**
 * API Endpoint for Business Search
 * Method: GET
 * Expected GET parameters:
 * - lat (float, required): Latitude of the search center.
 * - lng (float, required): Longitude of the search center.
 * - radius (float, optional): Search radius in kilometers. Defaults to 5km.
 * - limit (int, optional): Maximum number of results. Defaults to 25.
 */

// Bootstrap
if (session_status() == PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_secure' => isset($_SERVER['HTTPS']),
        'cookie_samesite' => 'Lax'
    ]);
}

if (!defined('BASE_PATH')) { define('BASE_PATH', dirname(__DIR__, 4)); }
if (!defined('DS')) { define('DS', DIRECTORY_SEPARATOR); }

$required_files = [
    BASE_PATH . DS . 'config' . DS . 'constants.php',
    BASE_PATH . DS . 'config' . DS . 'database.php',
    BASE_PATH . DS . 'includes' . DS . 'functions.php',
    BASE_PATH . DS . 'includes' . DS . 'translation.php',
];
foreach ($required_files as $file) {
    if (file_exists($file)) { require_once $file; }
    else {
        http_response_code(500); header('Content-Type: application/json');
        // Log this critical error
        error_log("CRITICAL: Business Search API failed to load core file: " . $file);
        echo json_encode(['success' => false, 'message' => 'Server configuration error. Please try again later.']); exit;
    }
}

$current_api_language = $GLOBALS['current_language'] ?? DEFAULT_LANGUAGE ?? 'en';
header('Content-Type: application/json');

// --- Input Collection and Validation ---
$lat_param = $_GET['lat'] ?? null;
$lng_param = $_GET['lng'] ?? null;
$radius_km = (float)($_GET['radius'] ?? 5.0);
$limit = (int)($_GET['limit'] ?? 25);

$errors = [];

if ($lat_param === null || !is_numeric($lat_param) || (float)$lat_param < -90 || (float)$lat_param > 90) {
    $errors['lat'] = __('error_invalid_latitude', [], $current_api_language);
}
if ($lng_param === null || !is_numeric($lng_param) || (float)$lng_param < -180 || (float)$lng_param > 180) {
    $errors['lng'] = __('error_invalid_longitude', [], $current_api_language);
}
if ($radius_km <= 0 || $radius_km > (defined('MAX_SEARCH_RADIUS_KM') ? MAX_SEARCH_RADIUS_KM : 100)) {
    $errors['radius'] = __('error_invalid_radius', [], $current_api_language);
}
if ($limit <= 0 || $limit > (defined('MAX_SEARCH_LIMIT') ? MAX_SEARCH_LIMIT : 100)) {
    $errors['limit'] = __('error_invalid_limit', [], $current_api_language);
}

if (!empty($errors)) {
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'message' => __('error_validation_failed_search_params', [], $current_api_language), 'errors' => $errors]);
    exit;
}

// Validated inputs
$lat = (float)$lat_param;
$lng = (float)$lng_param;

// --- Database Query ---
// Using Haversine formula for distance calculation, filtered by a bounding box first for performance.
if (!defined('EARTH_RADIUS_KM')) { define('EARTH_RADIUS_KM', 6371); }

// Calculate bounding box (degrees)
$lat_rad = deg2rad($lat);
$lng_rad = deg2rad($lng);

// Angular radius (in radians)
$angular_radius = $radius_km / EARTH_RADIUS_KM;

$min_lat_rad = $lat_rad - $angular_radius;
$max_lat_rad = $lat_rad + $angular_radius;

if ($min_lat_rad > -$M_PI_2 && $max_lat_rad < $M_PI_2) { // Check if not passing poles
    $delta_lng = asin(sin($angular_radius) / cos($lat_rad));
    $min_lng_rad = $lng_rad - $delta_lng;
    $max_lng_rad = $lng_rad + $delta_lng;
} else { // At poles, longitude is irrelevant, search all longitudes in a circle
    $min_lat_rad = max($min_lat_rad, -$M_PI_2);
    $max_lat_rad = min($max_lat_rad, $M_PI_2);
    $min_lng_rad = -$M_PI; // Search full circle
    $max_lng_rad = $M_PI;
}

$min_lat_deg = rad2deg($min_lat_rad);
$max_lat_deg = rad2deg($max_lat_rad);
$min_lng_deg = rad2deg($min_lng_rad);
$max_lng_deg = rad2deg($max_lng_rad);


$businesses = [];
try {
    $db = Database::getInstance()->getConnection();

    // Assumes 'name' column will be added to 'businesses' table.
    // This query selects businesses within a bounding box, then filters by exact distance using Haversine.
    $sql = "SELECT id, slug, name, latitude, longitude, pawstar_rating, owner_user_id,
                   ( " . EARTH_RADIUS_KM . " * acos(
                       cos(radians(:center_lat)) * cos(radians(latitude)) *
                       cos(radians(longitude) - radians(:center_lng)) +
                       sin(radians(:center_lat)) * sin(radians(latitude))
                     )
                   ) AS distance
            FROM businesses
            WHERE
                status = 'active' AND
                latitude BETWEEN :min_lat_deg AND :max_lat_deg AND ";

    // Handle longitude wrapping for bounding box (if min_lng > max_lng, it crosses the 180th meridian)
    if ($min_lng_deg > $max_lng_deg) { // Crosses antimeridian
        $sql .= "(longitude >= :min_lng_deg OR longitude <= :max_lng_deg) ";
    } else {
        $sql .= "longitude BETWEEN :min_lng_deg AND :max_lng_deg ";
    }

    $sql .= "HAVING distance <= :radius_km
             ORDER BY distance ASC
             LIMIT :limit_val";

    $stmt = $db->prepare($sql);
    $stmt->bindParam(':center_lat', $lat);
    $stmt->bindParam(':center_lng',lng);
    $stmt->bindParam(':min_lat_deg', $min_lat_deg);
    $stmt->bindParam(':max_lat_deg', $max_lat_deg);
    $stmt->bindParam(':min_lng_deg', $min_lng_deg);
    $stmt->bindParam(':max_lng_deg', $max_lng_deg);
    $stmt->bindParam(':radius_km', $radius_km);
    $stmt->bindParam(':limit_val', $limit, PDO::PARAM_INT);

    $stmt->execute();
    $businesses = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($businesses)) {
        echo json_encode(['success' => true, 'businesses' => [], 'message' => __('info_no_businesses_found_nearby', [], $current_api_language)]);
    } else {
        // Ensure numeric fields are actual numbers for JSON, not strings
        foreach ($businesses as &$biz) {
            $biz['latitude'] = (float)$biz['latitude'];
            $biz['longitude'] = (float)$biz['longitude'];
            $biz['pawstar_rating'] = (int)$biz['pawstar_rating'];
            $biz['distance'] = round((float)$biz['distance'], 2); // Round distance to 2 decimal places
        }
        unset($biz); // Unset reference
        echo json_encode(['success' => true, 'businesses' => $businesses]);
    }

} catch (PDOException $e) {
    error_log("Database error during business search (PDO): " . $e->getMessage() . " SQL: " . ($sql ?? 'N/A'));
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => __('error_server_generic_search_failed', [], $current_api_language)]);
} catch (Exception $e) {
    error_log("General error during business search: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => __('error_server_generic_search_failed', [], $current_api_language)]);
}

exit;

<?php
// Placeholder for translation strings (ensure these are in lang/en/common.php or similar)
// __('error_invalid_latitude', [], $current_api_language);
// __('error_invalid_longitude', [], $current_api_language);
// __('error_invalid_radius', [], $current_api_language);
// __('error_invalid_limit', [], $current_api_language);
// __('error_validation_failed_search_params', [], $current_api_language);
// __('info_no_businesses_found_nearby', [], $current_api_language);
// __('error_server_generic_search_failed', [], $current_api_language);
?>
