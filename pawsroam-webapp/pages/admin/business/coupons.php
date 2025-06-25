<?php
// Included by index.php
require_once __DIR__ . '/../../../includes/auth.php';
require_role(['business_admin', 'super_admin']);

$pageTitle = __('page_title_manage_coupons', [], $current_language);
// Actual data fetching will be done via JS calling the list API endpoint.
?>

<div class="container my-4 my-md-5">
    <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
        <h1 class="display-6 fw-bold"><?= $pageTitle ?></h1>
        <a href="<?= get_route_url('admin_coupon_create') ?>" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i><?= __('button_add_new_coupon', [], $current_language) ?>
        </a>
    </div>

    <p class="text-muted"><?= __('manage_coupons_description_stub', [], $current_language) ?></p>

    <!-- Filters (Stub) -->
    <div class="card mb-4">
        <div class="card-body">
            <h5 class="card-title"><?= __('filter_coupons_title_stub', [], $current_language) ?></h5>
            <form id="filterCouponsForm" class="row g-3">
                <?php if (has_role('super_admin')): ?>
                <div class="col-md-4">
                    <label for="filter_business_id" class="form-label"><?= __('filter_by_business_stub', [], $current_language) ?></label>
                    <select id="filter_business_id" name="business_id" class="form-select">
                        <option value=""><?= __('all_businesses_stub', [], $current_language) ?></option>
                        <!-- Options to be populated dynamically if super_admin -->
                    </select>
                </div>
                <?php endif; ?>
                <div class="col-md-3">
                    <label for="filter_status" class="form-label"><?= __('filter_by_status_stub', [], $current_language) ?></label>
                    <select id="filter_status" name="status" class="form-select">
                        <option value=""><?= __('all_statuses_stub', [], $current_language) ?></option>
                        <option value="active"><?= __('coupon_status_active', [], $current_language) ?></option>
                        <option value="inactive"><?= __('coupon_status_inactive', [], $current_language) ?></option>
                        <option value="expired"><?= __('coupon_status_expired', [], $current_language) ?></option>
                        <option value="fully_redeemed"><?= __('coupon_status_fully_redeemed', [], $current_language) ?></option>
                    </select>
                </div>
                <div class="col-md-2 align-self-end">
                    <button type="submit" class="btn btn-info w-100" disabled title="Filtering coming soon"><?= __('button_filter_stub', [], $current_language) ?></button>
                </div>
            </form>
        </div>
    </div>

    <!-- Coupon List Table (Stub) -->
    <div id="couponListContainer">
        <p class="text-center py-5"><?= __('loading_coupons_stub', [], $current_language) ?></p>
        <?php /* Example table structure - will be built by JS
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th><?= __('coupon_th_title', [], $current_language) ?></th>
                    <?php if (has_role('super_admin')): ?>
                        <th><?= __('coupon_th_business', [], $current_language) ?></th>
                    <?php endif; ?>
                    <th><?= __('coupon_th_code', [], $current_language) ?></th>
                    <th><?= __('coupon_th_status', [], $current_language) ?></th>
                    <th><?= __('coupon_th_dates', [], $current_language) ?></th>
                    <th><?= __('coupon_th_usage', [], $current_language) ?></th>
                    <th><?= __('coupon_th_actions', [], $current_language) ?></th>
                </tr>
            </thead>
            <tbody id="couponTableBody">
                <!-- Rows will be populated by JavaScript -->
            </tbody>
        </table>
        <nav aria-label="Coupon pagination">
            <ul class="pagination justify-content-center" id="couponPagination">
                <!-- Pagination links will be populated by JavaScript -->
            </ul>
        </nav>
        */ ?>
    </div>
    <div id="noCouponsMessage" class="alert alert-info text-center d-none">
        <?= __('no_coupons_found_for_business_stub', [], $current_language) ?>
    </div>

</div>

<?php
// Add JavaScript to fetch and display coupons (stub for now)
// This would call the /api/v1/business/coupons/list.php endpoint
// And handle edit/delete actions by redirecting or calling other APIs.
?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const couponListContainer = document.getElementById('couponListContainer');
    const noCouponsMessage = document.getElementById('noCouponsMessage');

    // Placeholder: Simulate fetching coupons
    setTimeout(() => {
        // In a real implementation, fetch from API:
        // fetch('<?= get_api_route_url('v1/business/coupons/list') ?>')
        // .then(response => response.json())
        // .then(data => { ... populate table ... });

        // Stub data display
        const isSuperAdmin = <?= json_encode(has_role('super_admin')) ?>;
        let tableHTML = `
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th><?= __('coupon_th_title', [], $current_language) ?></th>
                        ${isSuperAdmin ? '<th><?= __('coupon_th_business', [], $current_language) ?></th>' : ''}
                        <th><?= __('coupon_th_code', [], $current_language) ?></th>
                        <th><?= __('coupon_th_status', [], $current_language) ?></th>
                        <th><?= __('coupon_th_dates', [], $current_language) ?></th>
                        <th><?= __('coupon_th_usage', [], $current_language) ?></th>
                        <th><?= __('coupon_th_actions', [], $current_language) ?></th>
                    </tr>
                </thead>
                <tbody>`;

        const stubCoupons = [
            { id: 101, title: '10% Off Your Next Visit', business_name: 'Demo Pet Cafe', code: 'PAWSOME10', status: 'active', start_date: '2023-01-01', end_date: '2024-12-31', usage: '5/100' },
            { id: 102, title: 'Free Toy with Grooming', business_name: 'Demo Pet Cafe', code: 'FREETOY', status: 'inactive', start_date: '2024-02-01', end_date: '2024-03-01', usage: '0/50' }
        ];

        if (stubCoupons.length > 0) {
            stubCoupons.forEach(coupon => {
                tableHTML += `
                    <tr>
                        <td>${coupon.title}</td>
                        ${isSuperAdmin ? `<td>${coupon.business_name}</td>` : ''}
                        <td><code>${coupon.code || '-'}</code></td>
                        <td><span class="badge bg-${coupon.status === 'active' ? 'success' : 'secondary'}">${coupon.status}</span></td>
                        <td>${new Date(coupon.start_date).toLocaleDateString()} - ${new Date(coupon.end_date).toLocaleDateString()}</td>
                        <td>${coupon.usage}</td>
                        <td>
                            <a href="<?= get_route_url('admin_coupon_edit', ['id' => 'PLACEHOLDER_ID']) ?>".replace('PLACEHOLDER_ID', coupon.id) class="btn btn-sm btn-outline-primary" title="<?= __('tooltip_edit_coupon_stub', [], $current_language) ?>"><i class="fas fa-edit"></i></a>
                            <button class="btn btn-sm btn-outline-danger" disabled title="Delete coming soon"><i class="fas fa-trash-alt"></i></button>
                        </td>
                    </tr>`;
            });
            tableHTML += `</tbody></table>`;
            couponListContainer.innerHTML = tableHTML;
        } else {
            couponListContainer.innerHTML = ''; // Clear loading message
            noCouponsMessage.classList.remove('d-none');
        }
    }, 1000);
});
</script>
