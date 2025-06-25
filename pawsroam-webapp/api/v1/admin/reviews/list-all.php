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
$reviews = [];

try {
    $db = Database::getInstance()->getConnection();
    $params = [];

    // Base query
    $count_sql = "SELECT COUNT(br.id)
                  FROM business_reviews br
                  JOIN users u ON br.user_id = u.id
                  JOIN businesses b ON br.business_id = b.id";
    $select_sql = "SELECT br.id, br.business_id, b.name as business_name, br.user_id, u.username as author_username,
                          br.rating, br.title, br.comment, br.status, br.created_at, br.updated_at
                   FROM business_reviews br
                   JOIN users u ON br.user_id = u.id
                   JOIN businesses b ON br.business_id = b.id";

    $where_clauses = [];
    if ($status_filter) {
        $where_clauses[] = "br.status = :status_filter";
        $params[':status_filter'] = $status_filter;
    }
    if ($business_name_search) {
        $where_clauses[] = "b.name LIKE :business_name_search_like";
        $params[':business_name_search_like'] = '%' . $business_name_search . '%';
    }

    if (!empty($where_clauses)) {
        $count_sql .= " WHERE " . implode(" AND ", $where_clauses);
        $select_sql .= " WHERE " . implode(" AND ", $where_clauses);
    }

    // Get total count for pagination
    $stmt_count = $db->prepare($count_sql);
    $stmt_count->execute($params);
    $total_reviews = (int)$stmt_count->fetchColumn();

    if ($total_reviews > 0) {
        $total_pages = ceil($total_reviews / $limit);
        if ($page > $total_pages) { $page = $total_pages; } // Adjust page if out of bounds
        $offset = ($page - 1) * $limit;

        $select_sql .= " ORDER BY br.created_at DESC LIMIT :limit OFFSET :offset";
        $stmt_reviews = $db->prepare($select_sql);

        // Bind all params for the main query
        foreach ($params as $key => &$val) { // Use reference for $val
            $stmt_reviews->bindParam($key, $val); // Type inferred by PDO
        }
        unset($val); // Break reference
        $stmt_reviews->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt_reviews->bindParam(':offset', $offset, PDO::PARAM_INT);

        $stmt_reviews->execute();
        $reviews_data = $stmt_reviews->fetchAll(PDO::FETCH_ASSOC);

        foreach ($reviews_data as $item) {
            $reviews[] = [
                'id' => (int)$item['id'],
                'business_id' => (int)$item['business_id'],
                'business_name' => e($item['business_name']),
                'user_id' => (int)$item['user_id'],
                'author_username' => e($item['author_username']),
                'rating' => (int)$item['rating'],
                'title' => $item['title'] ? e($item['title']) : null,
                'comment_snippet' => $item['comment'] ? e(substr($item['comment'], 0, 100)) . (strlen($item['comment']) > 100 ? '...' : '') : null,
                'full_comment' => $item['comment'] ? e($item['comment']) : null, // For admin view detail later
                'status' => $item['status'],
                'created_at' => date("Y-m-d H:i", strtotime($item['created_at'])),
                'updated_at' => date("Y-m-d H:i", strtotime($item['updated_at'])),
            ];
        }
    }

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
