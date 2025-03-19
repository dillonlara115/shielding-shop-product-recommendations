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

// Include the shared recommendations table function
require_once plugin_dir_path(__FILE__) . 'recommendations-table-shared.php';

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
?>

<div class="woocommerce-account-content">
    <h2><?php esc_html_e('My Recommendations', 'product-recommendations'); ?></h2>
    
    <div class="woocommerce-notices-wrapper"></div>
    
    <p class="back-link mb-4">
        <a href="<?php echo esc_url(wc_get_account_endpoint_url('my-recommendations')); ?>" class="button">
            &larr; <?php esc_html_e('Back to All Team Members', 'product-recommendations'); ?>
        </a>
    </p>
    
    <h3 class="title is-2 mb-4">
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
        
        // Get product IDs for core recommendations
        $general_product_ids = array();
        foreach ($general_recommendations as $recommendation) {
            $general_product_ids[] = $recommendation->product_id;
        }
        
        // Set up context for customer view
        $customer_context = array(
            'view' => 'customer',
            'show_actions' => true,
            'show_status' => false,
            'show_notes' => true,
            'show_subtotal' => true
        );
        
        // Display core recommendations first
        if (!empty($general_recommendations)): ?>
            <div class="room-section">
                <div class="room-header mb-3">
                    <h3 class="title is-2 is-capitalized mb-0"><?php esc_html_e('Core Recommendations', 'product-recommendations'); ?></h3>
                </div>
                <?php 
                $customer_context['room_product_ids'] = $general_product_ids;
                display_recommendations_table($general_recommendations, $customer_context); 
                ?>
            </div>
        <?php endif;
        
        // Display room-specific recommendations
        foreach ($recommendations_by_room as $room_id => $room_data): 
            if (!empty($room_data['name'])): ?>
                <div class="room-section mt-6">
                    <div class="room-header mb-3">
                        <h3 class="title is-2 is-capitalized mb-0"><?php echo esc_html($room_data['name']); ?></h3>
                    </div>
                    <?php 
                    $customer_context['room_product_ids'] = $room_data['product_ids'];
                    display_recommendations_table($room_data['recommendations'], $customer_context); 
                    ?>
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
