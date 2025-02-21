<?php
/**
 * Template for viewing recommendations in My Account
 *
 * @package    Product_Recommendations
 * @subpackage Product_Recommendations/public/partials
 */

if (!defined('ABSPATH')) {
    exit;
}

?>
<div class="woocommerce-account-content">
    <h2><?php esc_html_e('Product Recommendations', 'product-recommendations'); ?></h2>
    
    <div class="woocommerce-notices-wrapper"></div>
    
    <table class="woocommerce-table shop_table">
        <thead>
            <tr>
                <th><?php esc_html_e('Customer', 'product-recommendations'); ?></th>
                <th><?php esc_html_e('Product', 'product-recommendations'); ?></th>
                <th><?php esc_html_e('Date Created', 'product-recommendations'); ?></th>
                <th><?php esc_html_e('Status', 'product-recommendations'); ?></th>
                <th><?php esc_html_e('Actions', 'product-recommendations'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php
            $recommendations = array(); // This will be replaced with actual data fetch
            
            if (empty($recommendations)): ?>
                <tr>
                    <td colspan="5" class="woocommerce-no-items"><?php esc_html_e('No recommendations found', 'product-recommendations'); ?></td>
                </tr>
            <?php else:
                foreach ($recommendations as $recommendation): ?>
                    <tr>
                        <td><?php echo esc_html($recommendation->customer_name); ?></td>
                        <td><?php echo esc_html($recommendation->product_name); ?></td>
                        <td><?php echo esc_html($recommendation->date_created); ?></td>
                        <td>
                            <span class="woocommerce-badge status-<?php echo esc_attr($recommendation->status); ?>">
                                <?php echo esc_html($recommendation->status); ?>
                            </span>
                        </td>
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