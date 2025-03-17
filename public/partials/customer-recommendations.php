<?php
/**
 * Template for displaying customer recommendations
 *
 * @package    Product_Recommendations
 * @subpackage Product_Recommendations/public/partials
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="woocommerce-account-content">
    <h2><?php esc_html_e('My Recommendations', 'product-recommendations'); ?></h2>
    
    <div class="woocommerce-notices-wrapper"></div>
    
    <?php
    // Group recommendations by room
    $recommendations_by_room = array();
    $general_recommendations = array();
    
    foreach ($recommendations as $recommendation) {
        if (!empty($recommendation->room_id)) {
            if (!isset($recommendations_by_room[$recommendation->room_id])) {
                $recommendations_by_room[$recommendation->room_id] = array(
                    'name' => $recommendation->room_name,
                    'recommendations' => array()
                );
            }
            $recommendations_by_room[$recommendation->room_id]['recommendations'][] = $recommendation;
        } else {
            $general_recommendations[] = $recommendation;
        }
    }
    
    // Display general recommendations first
    if (!empty($general_recommendations)): ?>
        <h3 class="title is-4 is-capitalized"><?php esc_html_e('General Recommendations', 'product-recommendations'); ?></h3>
        <?php display_customer_recommendations_table($general_recommendations); ?>
    <?php endif;
    
    // Display room-specific recommendations
    foreach ($recommendations_by_room as $room_id => $room_data): 
        if (!empty($room_data['name'])): ?>
            <h3 class="title is-4 mt-6 is-capitalized"><?php echo esc_html($room_data['name']); ?></h3>
            <?php display_customer_recommendations_table($room_data['recommendations']); ?>
        <?php endif;
    endforeach; ?>
</div>

<?php
// Helper function to display recommendations table
function display_customer_recommendations_table($recommendations) {
    ?>
    <table class="woocommerce-table shop_table recommendations-table">
        <thead>
            <tr>
                <th class="product-thumbnail"><?php esc_html_e('Image', 'product-recommendations'); ?></th>
                <th><?php esc_html_e('Product', 'product-recommendations'); ?></th>
                <th><?php esc_html_e('Date Added', 'product-recommendations'); ?></th>
                <th><?php esc_html_e('Notes', 'product-recommendations'); ?></th>
                <th><?php esc_html_e('Actions', 'product-recommendations'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php 
            if (empty($recommendations)): ?>
                <tr>
                    <td colspan="5" class="woocommerce-no-items"><?php esc_html_e('No recommendations found', 'product-recommendations'); ?></td>
                </tr>
            <?php else:
                foreach ($recommendations as $recommendation): 
                    $product = wc_get_product($recommendation->product_id);
                    $image_url = $product ? wp_get_attachment_image_url($product->get_image_id(), 'thumbnail') : wc_placeholder_img_src('thumbnail');
                    $price_html = $product ? $product->get_price_html() : '';
                ?>
                    <tr>
                        <td class="product-thumbnail">
                            <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($recommendation->product_name); ?>" />
                        </td>
                        <td>
                            <div class="product-info">
                                <div class="product-name"><?php echo esc_html($recommendation->product_name); ?></div>
                                <div class="product-price"><?php echo $price_html; ?></div>
                            </div>
                        </td>
                        <td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($recommendation->date_created))); ?></td>
                        <td><?php echo esc_html($recommendation->notes); ?></td>
                        <td class="is-flex is-2">
                            <a href="<?php echo esc_url(get_permalink($recommendation->product_id)); ?>" class="button is-text ">
                                <?php esc_html_e('View Product', 'product-recommendations'); ?>
                            </a>
                            <a href="<?php echo esc_url(add_query_arg('add-to-cart', $recommendation->product_id, wc_get_cart_url())); ?>" class="button ">
                                <?php esc_html_e('Add to Cart', 'product-recommendations'); ?>
                            </a>
                        </td>
                    </tr>
                <?php endforeach;
            endif; ?>
        </tbody>
    </table>
    <?php
}
?>

<style>

    .is-flex.is-2 {
        gap: 1rem;
    }
.product-info {
    display: flex;
    flex-direction: column;
}
.product-name {
    font-weight: bold;
    margin-bottom: 5px;
}
.product-price {
    color: #666;
    font-size: 0.8em;
}
</style> 