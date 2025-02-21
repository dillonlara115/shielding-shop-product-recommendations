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

?>
<div class="woocommerce-account-content">
    <h2><?php esc_html_e('Customer List', 'product-recommendations'); ?></h2>
    
    <div class="woocommerce-notices-wrapper"></div>
    
    <table class="woocommerce-table shop_table">
        <thead>
            <tr>
                <th><?php esc_html_e('Name', 'product-recommendations'); ?></th>
                <th><?php esc_html_e('Email', 'product-recommendations'); ?></th>
                <th><?php esc_html_e('Date Added', 'product-recommendations'); ?></th>
                <th><?php esc_html_e('Actions', 'product-recommendations'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php
            $customers = array(); // This will be replaced with actual data fetch
            
            if (empty($customers)): ?>
                <tr>
                    <td colspan="4" class="woocommerce-no-items"><?php esc_html_e('No customers found', 'product-recommendations'); ?></td>
                </tr>
            <?php else: 
                foreach ($customers as $customer): ?>
                    <tr>
                        <td><?php echo esc_html($customer->name); ?></td>
                        <td><?php echo esc_html($customer->email); ?></td>
                        <td><?php echo esc_html($customer->date_added); ?></td>
                        <td>
                            <a href="#" class="button"><?php esc_html_e('Edit', 'product-recommendations'); ?></a>
                            <a href="#" class="button"><?php esc_html_e('Delete', 'product-recommendations'); ?></a>
                        </td>
                    </tr>
                <?php endforeach;
            endif; ?>
        </tbody>
    </table>
</div> 