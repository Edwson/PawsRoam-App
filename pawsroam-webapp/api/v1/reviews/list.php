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
$total_pages = 0;

try {
    $db = Database::getInstance()->getConnection();

    // Get total count of approved reviews for pagination
    $stmt_count = $db->prepare("SELECT COUNT(*) FROM business_reviews WHERE business_id = :business_id AND status = 'approved'");
    $stmt_count->bindParam(':business_id', $business_id, PDO::PARAM_INT);
    $stmt_count->execute();
    $total_reviews = (int)$stmt_count->fetchColumn();

    if ($total_reviews > 0) {
        $total_pages = ceil($total_reviews / $limit);
        // Ensure current page is not out of bounds
        if ($page > $total_pages) {
            $page = $total_pages; // Or return empty if preferred for out-of-bounds pages
            $offset = ($page - 1) * $limit; // Recalculate offset
        }

        $stmt_reviews = $db->prepare(
            "SELECT br.id, br.user_id, u.username AS author_username, u.avatar_path AS author_avatar_path,
                    br.rating, br.title, br.comment, br.review_photos, br.created_at
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
        $reviews_data = $stmt_reviews->fetchAll(PDO::FETCH_ASSOC);

        // Process reviews: format dates, construct photo URLs, author avatar URLs
        $uploads_base_url = defined('UPLOADS_BASE_URL') ? rtrim(UPLOADS_BASE_URL, '/') : '';
        $default_user_avatar = base_url('/assets/images/placeholders/avatar_placeholder_50.png'); // Example default

        foreach ($reviews_data as $review_item) {
            $author_avatar_url = $default_user_avatar;
            if (!empty($review_item['author_avatar_path'])) {
                // Assuming author_avatar_path is stored like 'user-avatars/USER_ID/filename.jpg'
                $author_avatar_url = $uploads_base_url . '/' . ltrim($review_item['author_avatar_path'], '/');
            }

            $review_photo_urls = [];
            if (!empty($review_item['review_photos'])) {
                $photo_paths = json_decode($review_item['review_photos'], true);
                if (is_array($photo_paths)) {
                    foreach ($photo_paths as $path) {
                        // Assuming review photos are stored like 'business-review-photos/BUSINESS_ID/USER_ID_OF_REVIEWER/filename.jpg'
                        // For now, let's assume path is relative to 'uploads/' directly for simplicity if path includes full subdirs
                        $review_photo_urls[] = $uploads_base_url . '/' . ltrim($path, '/');
                    }
                }
            }

            $reviews[] = [
                'id' => (int)$review_item['id'],
                'user_id' => (int)$review_item['user_id'],
                'author_username' => e($review_item['author_username']),
                'author_avatar_url' => e($author_avatar_url),
                'rating' => (int)$review_item['rating'],
                'title' => $review_item['title'] ? e($review_item['title']) : null,
                'comment' => $review_item['comment'] ? nl2br(e($review_item['comment'])) : null, // nl2br for display
                'review_photo_urls' => $review_photo_urls,
                'created_at_formatted' => date("F j, Y, g:i A", strtotime($review_item['created_at'])), // Human-readable
                'created_at_iso' => date(DateTime::ATOM, strtotime($review_item['created_at'])) // ISO 8601 for machines/JS
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
