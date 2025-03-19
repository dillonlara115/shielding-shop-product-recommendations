<?php
/**
 * Template for displaying recommendations from a specific team member
 *
 * @package    Product_Recommendations
 * @subpackage Product_Recommendations/public/partials
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get team member details
$team_member_details = get_userdata($team_member_id);
if (!$team_member_details) {
    wc_add_notice(__('Invalid team member', 'product-recommendations'), 'error');
    return;
}

// Get customer ID for current user
$customer_id = $wpdb->get_var($wpdb->prepare(
    "SELECT id FROM {$wpdb->prefix}pr_customers WHERE user_id = %d",
    $user_id
));

if (!$customer_id) {
    wc_add_notice(__('No recommendations found', 'product-recommendations'), 'error');
    return;
}

// Get recommendations from this team member
$recommendations = $wpdb->get_results($wpdb->prepare(
    "SELECT r.*, p.post_title as product_name, rm.name as room_name
     FROM {$wpdb->prefix}pr_recommendations r
     LEFT JOIN {$wpdb->posts} p ON r.product_id = p.ID
     LEFT JOIN {$wpdb->prefix}pr_rooms rm ON r.room_id = rm.id
     WHERE r.customer_id = %d AND r.team_member_id = %d
     ORDER BY COALESCE(r.room_id, 0), r.date_created DESC",
    $customer_id,
    $team_member_id
));

// Get all product IDs for "Add All to Cart" functionality
$all_product_ids = array();
foreach ($recommendations as $recommendation) {
    if (!empty($recommendation->product_id)) {
        $all_product_ids[] = $recommendation->product_id;
    }
}

// Define the display_customer_recommendations_table function
if (!function_exists('display_customer_recommendations_table')) {
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
                                <a href="<?php echo esc_url(add_query_arg('add-to-cart', $recommendation->product_id, wc_get_cart_url())); ?>" class="button add-single-to-cart" data-product-id="<?php echo esc_attr($recommendation->product_id); ?>">
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
}
?>

<div class="woocommerce-account-content">
    <h2><?php esc_html_e('My Recommendations', 'product-recommendations'); ?></h2>
    
    <div class="woocommerce-notices-wrapper"></div>
    
    <p class="back-link mb-4">
        <a href="<?php echo esc_url(wc_get_account_endpoint_url('my-recommendations')); ?>" class="button">
            &larr; <?php esc_html_e('Back to All Team Members', 'product-recommendations'); ?>
        </a>
    </p>
    
    <h3 class="title is-4 mb-4">
        <?php printf(esc_html__('Recommendations from %s', 'product-recommendations'), esc_html($team_member_details->display_name)); ?>
    </h3>
    
    <?php if (!empty($recommendations)): ?>
        <div class="add-all-actions mb-4">
            <button class="button add-all-to-cart" data-products="<?php echo esc_attr(json_encode($all_product_ids)); ?>">
                <?php esc_html_e('Add All Products to Cart', 'product-recommendations'); ?>
            </button>
        </div>
        
        <?php
        // Group recommendations by room
        $recommendations_by_room = array();
        $general_recommendations = array();
        
        foreach ($recommendations as $recommendation) {
            if (!empty($recommendation->room_id)) {
                if (!isset($recommendations_by_room[$recommendation->room_id])) {
                    $recommendations_by_room[$recommendation->room_id] = array(
                        'name' => $recommendation->room_name,
                        'recommendations' => array(),
                        'product_ids' => array()
                    );
                }
                $recommendations_by_room[$recommendation->room_id]['recommendations'][] = $recommendation;
                $recommendations_by_room[$recommendation->room_id]['product_ids'][] = $recommendation->product_id;
            } else {
                $general_recommendations[] = $recommendation;
            }
        }
        
        // Get product IDs for general recommendations
        $general_product_ids = array();
        foreach ($general_recommendations as $recommendation) {
            $general_product_ids[] = $recommendation->product_id;
        }
        
        // Display general recommendations first
        if (!empty($general_recommendations)): ?>
            <div class="room-section">
                <div class="room-header is-flex is-justify-content-space-between is-align-items-center mb-3">
                    <h3 class="title is-4 is-capitalized mb-0"><?php esc_html_e('General Recommendations', 'product-recommendations'); ?></h3>
                    <?php if (!empty($general_product_ids)): ?>
                        <button class="button add-room-to-cart" data-products="<?php echo esc_attr(json_encode($general_product_ids)); ?>">
                            <?php esc_html_e('Add All to Cart', 'product-recommendations'); ?>
                        </button>
                    <?php endif; ?>
                </div>
                <?php display_customer_recommendations_table($general_recommendations); ?>
            </div>
        <?php endif;
        
        // Display room-specific recommendations
        foreach ($recommendations_by_room as $room_id => $room_data): 
            if (!empty($room_data['name'])): ?>
                <div class="room-section mt-6">
                    <div class="room-header is-flex is-justify-content-space-between is-align-items-center mb-3">
                        <h3 class="title is-4 is-capitalized mb-0"><?php echo esc_html($room_data['name']); ?></h3>
                        <?php if (!empty($room_data['product_ids'])): ?>
                            <button class="button add-room-to-cart" data-products="<?php echo esc_attr(json_encode($room_data['product_ids'])); ?>">
                                <?php esc_html_e('Add All to Cart', 'product-recommendations'); ?>
                            </button>
                        <?php endif; ?>
                    </div>
                    <?php display_customer_recommendations_table($room_data['recommendations']); ?>
                </div>
            <?php endif;
        endforeach;
    else: ?>
        <p><?php esc_html_e('No recommendations found from this team member.', 'product-recommendations'); ?></p>
    <?php endif; ?>
    
    <div id="add-to-cart-status" class="woocommerce-message" style="display: none;"></div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Add single product to cart
    $('.add-single-to-cart').on('click', function(e) {
        e.preventDefault();
        var productId = $(this).data('product-id');
        addProductsToCart([productId]);
    });
    
    // Add all products from a room to cart
    $('.add-room-to-cart').on('click', function() {
        var products = $(this).data('products');
        addProductsToCart(products);
    });
    
    // Add all products to cart
    $('.add-all-to-cart').on('click', function() {
        var products = $(this).data('products');
        addProductsToCart(products);
    });
    
    function addProductsToCart(productIds) {
        if (!productIds || productIds.length === 0) return;
        
        // Show loading state
        $('#add-to-cart-status').html('Adding products to cart...').show();
        
        // Process products sequentially instead of in parallel
        var index = 0;
        var successCount = 0;
        
        function addNextProduct() {
            if (index >= productIds.length) {
                // All products processed
                $('#add-to-cart-status').html(successCount + ' products added to cart! <a href="' + wc_add_to_cart_params.cart_url + '">View Cart</a>');
                
                // Hide the message after 5 seconds
                setTimeout(function() {
                    $('#add-to-cart-status').fadeOut();
                }, 5000);
                
                // Update cart fragments
                $(document.body).trigger('wc_fragment_refresh');
                return;
            }
            
            var productId = productIds[index];
            
            $.ajax({
                url: wc_add_to_cart_params.ajax_url,
                type: 'POST',
                data: {
                    action: 'add_to_cart_custom',
                    product_id: productId,
                    quantity: 1,
                    nonce: wc_add_to_cart_params.nonce
                },
                success: function(response) {
                    if (response.success) {
                        successCount++;
                    }
                    
                    // Update status message
                    $('#add-to-cart-status').html('Adding products to cart... (' + (index + 1) + '/' + productIds.length + ')');
                    
                    // Process next product
                    index++;
                    setTimeout(addNextProduct, 300); // Add a small delay between requests
                },
                error: function() {
                    // Continue with next product even if there's an error
                    index++;
                    setTimeout(addNextProduct, 300);
                }
            });
        }
        
        // Start adding products
        addNextProduct();
    }
});
</script> 