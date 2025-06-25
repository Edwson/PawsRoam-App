<?php
/**
 * API Endpoint for Admin to List All Business Reviews (with filters)
 * Method: GET
 * Expected GET parameters: [status (pending,approved,rejected)], [page], [limit], [business_name_search]
 */

// Bootstrap
if (session_status() == PHP_SESSION_NONE) { session_start(/* ...options... */); }
if (!defined('BASE_PATH')) { define('BASE_PATH', dirname(__DIR__, 5)); } // Adjust depth: api/v1/admin/reviews -> project root
if (!defined('DS')) { define('DS', DIRECTORY_SEPARATOR); }

$required_files = [ /* ... core files ... */
    BASE_PATH . DS . 'config' . DS . 'constants.php', BASE_PATH . DS . 'config' . DS . 'database.php',
    BASE_PATH . DS . 'includes' . DS . 'functions.php', BASE_PATH . DS . 'includes' . DS . 'translation.php',
    BASE_PATH . DS . 'includes' . DS . 'auth.php'
];
foreach ($required_files as $file) {
    if (file_exists($file)) { require_once $file; }
    else { http_response_code(500); header('Content-Type: application/json'); error_log("CRITICAL: Admin List Reviews API missing $file"); echo json_encode(['success' => false, 'message' => 'Server config error.']); exit; }
}

$current_api_language = $GLOBALS['current_language'] ?? DEFAULT_LANGUAGE ?? 'en';
header('Content-Type: application/json');

// --- Access Control & Request Method ---
require_role(['super_admin']); // Only super_admin can list all reviews
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405); echo json_encode(['success' => false, 'message' => __('error_method_not_allowed', [], $current_api_language)]); exit;
}

// --- Input Collection & Validation (STUB - basic for now) ---
$status_filter = isset($_GET['status']) ? trim($_GET['status']) : null;
$business_name_search = isset($_GET['business_name_search']) ? trim($_GET['business_name_search']) : null;
$page = filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT, ['options' => ['default' => 1, 'min_range' => 1]]);
$limit = filter_var($_GET['limit'] ?? 15, FILTER_VALIDATE_INT, ['options' => ['default' => 15, 'min_range' => 1, 'max_range' => 100]]);

$allowed_statuses = ['pending', 'approved', 'rejected'];
if ($status_filter !== null && !in_array($status_filter, $allowed_statuses)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => __('error_admin_invalid_review_status_filter', [], $current_api_language)]); // "Invalid status filter provided."
    exit;
}

// --- STUB: Database Fetching & Pagination ---
$reviews = [];
$total_reviews = 0;
$total_pages = 0;

// Dummy data for stub
$all_dummy_reviews = [
    ['id' => 201, 'business_id' => 1, 'business_name' => 'The Pet Cafe', 'user_id' => 2, 'author_username' => 'UserA', 'rating' => 5, 'title' => 'Loved it!', 'comment' => 'Great atmosphere for pets.', 'status' => 'pending', 'created_at' => date("Y-m-d H:i:s", strtotime("-1 day"))],
    ['id' => 202, 'business_id' => 2, 'business_name' => 'Happy Paws Park', 'user_id' => 3, 'author_username' => 'UserB', 'rating' => 4, 'title' => 'Good for dogs', 'comment' => 'Lots of space to run.', 'status' => 'approved', 'created_at' => date("Y-m-d H:i:s", strtotime("-3 days"))],
    ['id' => 203, 'business_id' => 1, 'business_name' => 'The Pet Cafe', 'user_id' => 4, 'author_username' => 'UserC', 'rating' => 2, 'title' => 'Okayish', 'comment' => 'A bit small.', 'status' => 'rejected', 'created_at' => date("Y-m-d H:i:s", strtotime("-5 days"))],
    ['id' => 204, 'business_id' => 3, 'business_name' => 'Another Biz', 'user_id' => 5, 'author_username' => 'UserD', 'rating' => 5, 'title' => 'Fantastic!', 'comment' => 'Will come again', 'status' => 'pending', 'created_at' => date("Y-m-d H:i:s", strtotime("-2 hours"))],
];

$filtered_reviews = $all_dummy_reviews;
if ($status_filter) {
    $filtered_reviews = array_filter($filtered_reviews, function($review) use ($status_filter) {
        return $review['status'] === $status_filter;
    });
}
if ($business_name_search) {
     $filtered_reviews = array_filter($filtered_reviews, function($review) use ($business_name_search) {
        return stripos($review['business_name'], $business_name_search) !== false;
    });
}
$total_reviews = count($filtered_reviews);
$total_pages = ceil($total_reviews / $limit);
$offset = ($page - 1) * $limit;
$reviews = array_slice(array_values($filtered_reviews), $offset, $limit); // Re-index after filter

// Actual query structure would be:
/*
SELECT br.*, u.username as author_username, b.name as business_name
FROM business_reviews br
JOIN users u ON br.user_id = u.id
JOIN businesses b ON br.business_id = b.id
WHERE (:status_filter IS NULL OR br.status = :status_filter)
  AND (:business_name_search IS NULL OR b.name LIKE :business_name_search_like)
ORDER BY br.created_at DESC
LIMIT :limit OFFSET :offset
*/

http_response_code(200);
echo json_encode([
    'success' => true,
    'reviews' => $reviews,
    'pagination' => [
        'total_items' => $total_reviews,
        'current_page' => $page,
        'items_per_page' => $limit,
        'total_pages' => $total_pages
    ]
]);
exit;

<?php
// Translation placeholders
// __('error_admin_invalid_review_status_filter', [], $current_api_language);
?>
