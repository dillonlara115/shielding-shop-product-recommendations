<?php
/**
 * Template for displaying team member recommendations
 *
 * @package    Product_Recommendations
 * @subpackage Product_Recommendations/public/partials
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get current user ID
$user_id = get_current_user_id();

if (!$user_id) {
    wc_add_notice(__('You must be logged in to view this page', 'product-recommendations'), 'error');
    return;
}

// Get customers for this team member
global $wpdb;
$customers = $wpdb->get_results($wpdb->prepare(
    "SELECT c.*, u.display_name as customer_name, u.user_email as customer_email
     FROM {$wpdb->prefix}pr_customers c
     JOIN {$wpdb->users} u ON c.user_id = u.ID
     WHERE c.team_member_id = %d
     ORDER BY c.date_added DESC",
    $user_id
));
?>

<div class="woocommerce-account-content">
    <h2><?php esc_html_e('My Customers', 'product-recommendations'); ?></h2>
    
    <div class="woocommerce-notices-wrapper"></div>
    
    <div class="add-customer card mb-4">
        <header class="card-header">
            <p class="card-header-title">
                <?php esc_html_e('Add New Customer', 'product-recommendations'); ?>
            </p>
        </header>
        <div class="card-content">
            <div class="content">
                <div class="field">
                    <label class="label" for="user_search"><?php esc_html_e('Search Users', 'product-recommendations'); ?></label>
                    <div class="control">
                        <input type="text" id="user_search" class="input" placeholder="<?php esc_attr_e('Start typing to search users...', 'product-recommendations'); ?>">
                    </div>
                </div>
                
                <div id="user-search-results" class="user-search-results"></div>
            </div>
        </div>
    </div>
    
    <div class="customers-list">
        <h3 class="title is-2"><?php esc_html_e('My Customers', 'product-recommendations'); ?></h3>
        
        <?php if (empty($customers)): ?>
            <p><?php esc_html_e('No customers found. Add a customer to get started.', 'product-recommendations'); ?></p>
        <?php else: ?>
            <table class="woocommerce-table shop_table">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Customer', 'product-recommendations'); ?></th>
                        <th><?php esc_html_e('Email', 'product-recommendations'); ?></th>
                        <th><?php esc_html_e('Status', 'product-recommendations'); ?></th>
                        <th><?php esc_html_e('Added', 'product-recommendations'); ?></th>
                        <th><?php esc_html_e('Actions', 'product-recommendations'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($customers as $customer): ?>
                        <tr>
                            <td><?php echo esc_html($customer->customer_name); ?></td>
                            <td><?php echo esc_html($customer->customer_email); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo esc_attr($customer->status); ?>">
                                    <?php echo esc_html(ucfirst($customer->status)); ?>
                                </span>
                            </td>
                            <td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($customer->date_added))); ?></td>
                            <td>
                                <a href="<?php echo esc_url(wc_get_endpoint_url('product-recommendations', $customer->id, wc_get_page_permalink('myaccount'))); ?>" class="button">
                                    <?php esc_html_e('Manage Recommendations', 'product-recommendations'); ?>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>
