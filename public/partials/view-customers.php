<?php
/**
 * Template for viewing customers in My Account
 *
 * @package    Product_Recommendations
 * @subpackage Product_Recommendations/public/partials
 */

if (!defined('ABSPATH')) {
    exit;
}

// Before including the tabs navigation
$current_tab = 'customers';
include_once plugin_dir_path(__FILE__) . 'tabs-navigation.php';

// Get customers for current team member
global $wpdb;
$team_member_id = get_current_user_id();
$table_name = $wpdb->prefix . 'pr_customers';

$customers = $wpdb->get_results($wpdb->prepare(
    "SELECT c.*, u.display_name, u.user_email 
     FROM $table_name c
     JOIN {$wpdb->users} u ON c.user_id = u.ID
     WHERE c.team_member_id = %d
     ORDER BY c.date_added DESC",
    $team_member_id
));
?>

<div class="woocommerce-account-content">
    <h2><?php esc_html_e('Customer List', 'product-recommendations'); ?></h2>
    
    <div class="woocommerce-notices-wrapper"></div>
    
    <p class="woocommerce-Button">
        <a href="<?php echo esc_url(wc_get_account_endpoint_url('product-recommendations/customers/add')); ?>" class="button">
            <?php esc_html_e('Add New Customer', 'product-recommendations'); ?>
        </a>
    </p>
    
    <table class="woocommerce-table shop_table">
        <thead>
            <tr>
                <th><?php esc_html_e('Name', 'product-recommendations'); ?></th>
                <th><?php esc_html_e('Email', 'product-recommendations'); ?></th>
                <th><?php esc_html_e('Date Added', 'product-recommendations'); ?></th>
                <th><?php esc_html_e('Status', 'product-recommendations'); ?></th>
                <th><?php esc_html_e('Actions', 'product-recommendations'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($customers)): ?>
                <tr>
                    <td colspan="5" class="woocommerce-no-items"><?php esc_html_e('No customers found', 'product-recommendations'); ?></td>
                </tr>
            <?php else: 
                foreach ($customers as $customer): ?>
                    <tr>
                        <td><?php echo esc_html($customer->display_name); ?></td>
                        <td><?php echo esc_html($customer->user_email); ?></td>
                        <td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($customer->date_added))); ?></td>
                        <td>
                            <span class="status-badge status-<?php echo esc_attr($customer->status); ?>">
                                <?php echo esc_html(ucfirst($customer->status)); ?>
                            </span>
                        </td>
                        <td>
                            <a href="<?php echo esc_url(wc_get_account_endpoint_url('product-recommendations/customers/' . $customer->id . '/recommendations')); ?>" class="button">
                                <?php esc_html_e('Manage Recommendations', 'product-recommendations'); ?>
                            </a>
                            <a href="#" class="button button-remove" data-customer-id="<?php echo esc_attr($customer->id); ?>">
                                <?php esc_html_e('Remove', 'product-recommendations'); ?>
                            </a>
                        </td>
                    </tr>
                <?php endforeach;
            endif; ?>
        </tbody>
    </table>
</div>

<style>
.status-badge {
    display: inline-block;
    padding: 0.25em 0.5em;
    border-radius: 3px;
    font-size: 0.85em;
    font-weight: 600;
}
.status-active {
    background-color: #e5f9e5;
    color: #1e7e1e;
}
.status-inactive {
    background-color: #f9e5e5;
    color: #7e1e1e;
}
</style> 