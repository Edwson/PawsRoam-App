<?php
/**
 * API Endpoint for Listing Business Reviews
 * Method: GET
 * Expected GET parameters: business_id (int, required), [page (int)], [limit (int)]
 */

// Bootstrap
if (session_status() == PHP_SESSION_NONE) {
    session_start(['cookie_httponly' => true, 'cookie_secure' => isset($_SERVER['HTTPS']), 'cookie_samesite' => 'Lax']);
}
if (!defined('BASE_PATH')) { define('BASE_PATH', dirname(__DIR__, 4)); }
if (!defined('DS')) { define('DS', DIRECTORY_SEPARATOR); }

$required_files = [ /* ... core files ... */
    BASE_PATH . DS . 'config' . DS . 'constants.php', BASE_PATH . DS . 'config' . DS . 'database.php',
    BASE_PATH . DS . 'includes' . DS . 'functions.php', BASE_PATH . DS . 'includes' . DS . 'translation.php',
    // auth.php might not be strictly needed if reviews are public, but good for consistency or future user-specific views
    BASE_PATH . DS . 'includes' . DS . 'auth.php'
];
foreach ($required_files as $file) {
    if (file_exists($file)) { require_once $file; }
    else { http_response_code(500); header('Content-Type: application/json'); error_log("CRITICAL: List Reviews API missing $file"); echo json_encode(['success' => false, 'message' => 'Server config error.']); exit; }
}

$current_api_language = $GLOBALS['current_language'] ?? DEFAULT_LANGUAGE ?? 'en';
header('Content-Type: application/json');

// --- Input Collection & Validation (STUB - basic validation for now) ---
$business_id = filter_var($_GET['business_id'] ?? null, FILTER_VALIDATE_INT);
$page = filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT, ['options' => ['default' => 1, 'min_range' => 1]]);
$limit = filter_var($_GET['limit'] ?? 10, FILTER_VALIDATE_INT, ['options' => ['default' => 10, 'min_range' => 1, 'max_range' => 50]]); // Max 50 reviews per page

$errors = [];

if (!$business_id || $business_id <= 0) { $errors['business_id'] = __('error_review_invalid_business_id_list', [], $current_api_language); } // "Invalid business ID for listing reviews."
else { /* TODO: Check if business exists */ }


if (!empty($errors)) {
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'message' => __('error_validation_failed', [], $current_api_language), 'errors' => $errors]);
    exit;
}

// --- STUB: Database Fetching & Pagination ---
$offset = ($page - 1) * $limit;
$reviews = [];
$total_reviews = 0;

try {
    $db = Database::getInstance()->getConnection();
    // For this stub, we'll just return dummy data.
    // Actual query:
    /*
    // Get total count for pagination
    $stmt_count = $db->prepare("SELECT COUNT(*) FROM business_reviews WHERE business_id = :business_id AND status = 'approved'");
    $stmt_count->bindParam(':business_id', $business_id, PDO::PARAM_INT);
    $stmt_count->execute();
    $total_reviews = (int)$stmt_count->fetchColumn();

    if ($total_reviews > 0) {
        $stmt_reviews = $db->prepare(
            "SELECT br.id, br.user_id, u.username AS author_username, br.rating, br.title, br.comment, br.review_photos, br.created_at
             FROM business_reviews br
             JOIN users u ON br.user_id = u.id
             WHERE br.business_id = :business_id AND br.status = 'approved'
             ORDER BY br.created_at DESC
             LIMIT :limit OFFSET :offset"
        );
        $stmt_reviews->bindParam(':business_id', $business_id, PDO::PARAM_INT);
        $stmt_reviews->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt_reviews->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt_reviews->execute();
        $reviews = $stmt_reviews->fetchAll(PDO::FETCH_ASSOC);

        // Process review_photos if stored as JSON string
        foreach ($reviews as &$review) {
            if (!empty($review['review_photos'])) {
                $photos = json_decode($review['review_photos'], true);
                // Construct full URLs if paths are relative
                if (is_array($photos) && defined('UPLOADS_BASE_URL')) {
                    $review['review_photos_urls'] = array_map(function($path) {
                        return rtrim(UPLOADS_BASE_URL, '/') . '/business-review-photos/' . ltrim($path, '/'); // Example path structure
                    }, $photos);
                } else {
                     $review['review_photos_urls'] = [];
                }
            } else {
                $review['review_photos_urls'] = [];
            }
            unset($review['review_photos']); // Don't send raw JSON string if URLs are generated
        }
        unset($review);
    }
    */

    // Dummy data for stub:
    if ($business_id == 1) { // Example: only business ID 1 has reviews for stub
        $total_reviews = 2;
        if ($page == 1) {
             $reviews = [
                [ 'id' => 101, 'user_id' => 2, 'author_username' => 'PetLover22', 'rating' => 5, 'title' => 'Amazing place!', 'comment' => 'My Fluffy loved it here, so many toys and friendly staff!', 'review_photos_urls' => [], 'created_at' => date("Y-m-d H:i:s", strtotime("-2 days")) ],
                [ 'id' => 102, 'user_id' => 3, 'author_username' => 'DogDad88', 'rating' => 4, 'title' => 'Pretty good', 'comment' => 'Good spot, a bit crowded on weekends though.', 'review_photos_urls' => [], 'created_at' => date("Y-m-d H:i:s", strtotime("-5 days")) ],
            ];
        }
    }


    http_response_code(200); // OK
    echo json_encode([
        'success' => true,
        'reviews' => $reviews,
        'pagination' => [
            'total_items' => $total_reviews,
            'current_page' => $page,
            'items_per_page' => $limit,
            'total_pages' => ceil($total_reviews / $limit)
        ]
    ]);

} catch (PDOException $e) {
    error_log("List Reviews API (PDOException): " . $e->getMessage());
    http_response_code(500); echo json_encode(['success' => false, 'message' => __('error_server_generic', [], $current_api_language)]);
} catch (Exception $e) {
    error_log("List Reviews API (Exception): " . $e->getMessage());
    http_response_code(500); echo json_encode(['success' => false, 'message' => __('error_server_generic', [], $current_api_language)]);
}
exit;

<?php
// Translation placeholders
// __('error_review_invalid_business_id_list', [], $current_api_language);
?>
