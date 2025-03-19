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

global $wpdb;
$user_id = get_current_user_id();

// Get all team members who have recommended products to this user
$team_members = $wpdb->get_results($wpdb->prepare(
    "SELECT DISTINCT r.team_member_id, u.display_name as team_member_name
     FROM {$wpdb->prefix}pr_recommendations r
     JOIN {$wpdb->prefix}pr_customers c ON r.customer_id = c.id
     JOIN {$wpdb->users} u ON r.team_member_id = u.ID
     WHERE c.user_id = %d
     ORDER BY u.display_name",
    $user_id
));

// Check if we're viewing a specific team member's recommendations
$team_member_id = get_query_var('team_member_id');

if (!empty($team_member_id)) {
    // We're viewing a specific team member's recommendations
    include(plugin_dir_path(__FILE__) . 'team-member-recommendations.php');
    return;
}

// Main recommendations page - only show team members list
?>

<div class="woocommerce-account-content">
    <h2><?php esc_html_e('My Recommendations', 'product-recommendations'); ?></h2>
    
    <div class="woocommerce-notices-wrapper"></div>
    
    <?php if (!empty($team_members)): ?>
        <div class="team-members-section">
            <h3 class="title is-5 mb-3"><?php esc_html_e('View Recommendations from:', 'product-recommendations'); ?></h3>
            <table class="woocommerce-table shop_table team-members-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Team Member', 'product-recommendations'); ?></th>
                        <th><?php esc_html_e('Actions', 'product-recommendations'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($team_members as $team_member): ?>
                        <tr>
                            <td><?php echo esc_html($team_member->team_member_name); ?></td>
                            <td>
                                <a href="<?php echo esc_url(wc_get_account_endpoint_url('my-recommendations/' . $team_member->team_member_id)); ?>" class="button">
                                    <?php esc_html_e('View Recommendations', 'product-recommendations'); ?>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p><?php esc_html_e('You don\'t have any recommendations yet.', 'product-recommendations'); ?></p>
    <?php endif; ?>
</div>

<style>

</style>

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