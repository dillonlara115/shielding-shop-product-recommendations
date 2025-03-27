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

// Include the shared recommendations table function
require_once plugin_dir_path(__FILE__) . 'recommendations-table-shared.php';

// Get current user ID
$user_id = get_current_user_id();

if (!$user_id) {
    wc_add_notice(__('You must be logged in to view recommendations', 'product-recommendations'), 'error');
    return;
}

// Get customer ID for this user
global $wpdb;
$customer_id = $wpdb->get_var($wpdb->prepare(
    "SELECT id FROM {$wpdb->prefix}pr_customers WHERE user_id = %d",
    $user_id
));

if (!$customer_id) {
    wc_add_notice(__('No recommendations found for your account', 'product-recommendations'), 'error');
    return;
}

// Get all team members who have provided recommendations for this customer
$team_members = $wpdb->get_results($wpdb->prepare(
    "SELECT DISTINCT r.team_member_id, u.display_name as team_member_name
     FROM {$wpdb->prefix}pr_recommendations r
     JOIN {$wpdb->users} u ON r.team_member_id = u.ID
     WHERE r.customer_id = %d
     ORDER BY u.display_name ASC",
    $customer_id
));

// If there's only one team member, proceed as before
if (count($team_members) <= 1) {
    // Get recommendations for this customer, ordered by position
    $recommendations = $wpdb->get_results($wpdb->prepare(
        "SELECT r.*, p.post_title as product_name, rm.name as room_name
         FROM {$wpdb->prefix}pr_recommendations r
         LEFT JOIN {$wpdb->posts} p ON r.product_id = p.ID
         LEFT JOIN {$wpdb->prefix}pr_rooms rm ON r.room_id = rm.id
         WHERE r.customer_id = %d
         ORDER BY COALESCE(r.room_id, 0), r.position ASC, r.date_created DESC",
        $customer_id
    ));

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

    // Set up context for customer view
    $customer_context = array(
        'view' => 'customer',
        'show_actions' => true,
        'show_status' => false,
        'show_notes' => true,
        'show_subtotal' => true,
        'room_product_ids' => array(),
        'placeholder_image' => '/wp-content/uploads/2023/09/product-placeholder.png'
    );

    // Display general recommendations first
    if (!empty($general_recommendations)): ?>
        <h3 class="title is-2"><?php esc_html_e('Core Recommendations', 'product-recommendations'); ?></h3>
        <?php 
        $customer_context['room_product_ids'] = array_column($general_recommendations, 'product_id');
        display_recommendations_table($general_recommendations, $customer_context); 
        ?>
    <?php endif;

    // Display room-specific recommendations
    if (!empty($recommendations_by_room)): ?>
        <?php foreach ($recommendations_by_room as $room_id => $room_data): 
            if (!empty($room_data['recommendations'])): ?>
                <h3 class="title is-2 mt-6 is-capitalized"><?php echo esc_html($room_data['name']); ?></h3>
                <?php 
                $customer_context['room_product_ids'] = $room_data['product_ids'];
                $customer_context['show_subtotal'] = true;
                display_recommendations_table($room_data['recommendations'], $customer_context); 
                ?>
            <?php endif;
        endforeach; ?>
    <?php else: ?>
        <p><?php esc_html_e('No room-specific recommendations found.', 'product-recommendations'); ?></p>
    <?php endif;

} else {
    // Multiple team members - show a team member selection interface
    ?>
    <h2 class="title is-2"><?php esc_html_e('Your Recommendations', 'product-recommendations'); ?></h2>
    
    <div class="team-members-tabs">
        <div class="tabs">
            <ul>
                <?php foreach ($team_members as $index => $team_member): ?>
                    <li class="<?php echo $index === 0 ? 'is-active' : ''; ?>">
                        <a href="#team-member-<?php echo esc_attr($team_member->team_member_id); ?>">
                            <?php echo esc_html($team_member->team_member_name); ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        
        <?php foreach ($team_members as $index => $team_member): 
            // Get recommendations for this team member
            $team_recommendations = $wpdb->get_results($wpdb->prepare(
                "SELECT r.*, p.post_title as product_name, rm.name as room_name
                 FROM {$wpdb->prefix}pr_recommendations r
                 LEFT JOIN {$wpdb->posts} p ON r.product_id = p.ID
                 LEFT JOIN {$wpdb->prefix}pr_rooms rm ON r.room_id = rm.id
                 WHERE r.customer_id = %d AND r.team_member_id = %d
                 ORDER BY COALESCE(r.room_id, 0), r.position ASC, r.date_created DESC",
                $customer_id,
                $team_member->team_member_id
            ));
            
            // Group recommendations by room
            $team_recommendations_by_room = array();
            $team_general_recommendations = array();

            foreach ($team_recommendations as $recommendation) {
                if (!empty($recommendation->room_id)) {
                    if (!isset($team_recommendations_by_room[$recommendation->room_id])) {
                        $team_recommendations_by_room[$recommendation->room_id] = array(
                            'name' => $recommendation->room_name,
                            'recommendations' => array(),
                            'product_ids' => array()
                        );
                    }
                    $team_recommendations_by_room[$recommendation->room_id]['recommendations'][] = $recommendation;
                    $team_recommendations_by_room[$recommendation->room_id]['product_ids'][] = $recommendation->product_id;
                } else {
                    $team_general_recommendations[] = $recommendation;
                }
            }
            
            // Set up context for customer view
            $customer_context = array(
                'view' => 'customer',
                'show_actions' => true,
                'show_status' => false,
                'show_notes' => true,
                'show_subtotal' => true,
                'room_product_ids' => array(),
                'placeholder_image' => '/wp-content/uploads/2023/09/product-placeholder.png'
            );
        ?>
            <div id="team-member-<?php echo esc_attr($team_member->team_member_id); ?>" class="team-member-content" style="<?php echo $index === 0 ? '' : 'display: none;'; ?>">
                <h3 class="title is-3"><?php echo sprintf(__('Recommendations from %s', 'product-recommendations'), esc_html($team_member->team_member_name)); ?></h3>
                
                <?php if (empty($team_recommendations)): ?>
                    <p><?php esc_html_e('No recommendations found from this team member.', 'product-recommendations'); ?></p>
                <?php else: ?>
                    <?php 
                    // Display general recommendations first
                    if (!empty($team_general_recommendations)): ?>
                        <h4 class="title is-4"><?php esc_html_e('Core Recommendations', 'product-recommendations'); ?></h4>
                        <?php 
                        $customer_context['room_product_ids'] = array_column($team_general_recommendations, 'product_id');
                        display_recommendations_table($team_general_recommendations, $customer_context); 
                        ?>
                    <?php endif;

                    // Display room-specific recommendations
                    if (!empty($team_recommendations_by_room)): ?>
                        <?php foreach ($team_recommendations_by_room as $room_id => $room_data): 
                            if (!empty($room_data['recommendations'])): ?>
                                <h4 class="title is-4 mt-6 is-capitalized"><?php echo esc_html($room_data['name']); ?></h4>
                                <?php 
                                $customer_context['room_product_ids'] = $room_data['product_ids'];
                                $customer_context['show_subtotal'] = true;
                                display_recommendations_table($room_data['recommendations'], $customer_context); 
                                ?>
                            <?php endif;
                        endforeach; ?>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        // Handle tab switching
        $('.team-members-tabs .tabs a').on('click', function(e) {
            e.preventDefault();
            
            // Get the target content ID
            var target = $(this).attr('href');
            
            // Hide all content panels
            $('.team-member-content').hide();
            
            // Show the target content
            $(target).show();
            
            // Update active tab
            $('.team-members-tabs .tabs li').removeClass('is-active');
            $(this).parent('li').addClass('is-active');
        });
    });
    </script>
<?php
}
?>

<style>
.team-members-tabs .tabs {
    margin-bottom: 1.5rem;
}

.team-members-tabs .tabs ul {
    border-bottom: 1px solid #dbdbdb;
    display: flex;
    flex-wrap: wrap;
    list-style: none;
    margin: 0;
    padding: 0;
}

.team-members-tabs .tabs li {
    display: block;
    margin-bottom: -1px;
}

.team-members-tabs .tabs li.is-active a {
    border-bottom-color: #3273dc;
    color: #3273dc;
}

.team-members-tabs .tabs a {
    border-bottom: 1px solid transparent;
    color: #4a4a4a;
    display: flex;
    justify-content: center;
    margin-bottom: -1px;
    padding: 0.5em 1em;
    vertical-align: top;
    text-decoration: none;
}

.team-members-tabs .tabs a:hover {
    border-bottom-color: #b5b5b5;
    color: #363636;
}
</style>

<script type="text/javascript">
jQuery(document).ready(function($) {
    console.log('Customer recommendations script loaded');
    
    // Check if woocommerce_params is available, if not, create a fallback
    if (typeof woocommerce_params === 'undefined') {
        console.log('woocommerce_params not found, creating fallback');
        window.woocommerce_params = {
            cart_url: '<?php echo esc_js(wc_get_cart_url()); ?>'
        };
    }
    
    // Add single product to cart
    $('.add-to-cart-button').on('click', function(e) {
        e.preventDefault();
        console.log('Add to cart button clicked');
        var productId = $(this).data('product-id');
        var quantity = $(this).data('quantity') || 1;
        console.log('Product ID:', productId, 'Quantity:', quantity);
        addProductsToCart([{id: productId, quantity: quantity}]);
    });
    
    // Add all products from a room to cart
    $('.add-room-to-cart').on('click', function(e) {
        e.preventDefault();
        console.log('Add room to cart button clicked');
        var products = $(this).data('products');
        console.log('Products:', products);
        addProductsToCart(products);
    });
    
    // Add all products to cart
    $('.add-all-to-cart').on('click', function(e) {
        e.preventDefault();
        console.log('Add all to cart button clicked');
        var products = $(this).data('products');
        console.log('Products:', products);
        addProductsToCart(products);
    });
    
    function addProductsToCart(products) {
        if (!products || products.length === 0) {
            console.log('No products to add');
            return;
        }
        
        // Show loading state
        var statusDiv = $('#add-to-cart-status');
        if (statusDiv.length === 0) {
            statusDiv = $('<div id="add-to-cart-status" class="woocommerce-info"></div>');
            $('.woocommerce-account-content').prepend(statusDiv);
        }
        
        statusDiv.html('Adding products to cart...').show();
        
        // Process products sequentially
        var index = 0;
        var successCount = 0;
        var totalItems = 0;
        
        function addNextProduct() {
            if (index >= products.length) {
                // All products processed - store success message and reload
                sessionStorage.setItem('pr_cart_success', successCount + ' products (' + totalItems + ' items) added to cart!');
                statusDiv.html('Products added to cart! Refreshing page...');
                
                // Reload page after a short delay
                setTimeout(function() {
                    window.location.reload();
                }, 1000);
                return;
            }
            
            var product = products[index];
            var productId, quantity;
            
            // Handle both array of objects and array of IDs for backward compatibility
            if (typeof product === 'object' && product !== null) {
                productId = product.id;
                quantity = product.quantity || 1;
            } else {
                productId = product;
                quantity = 1;
            }
            
            console.log('Adding product to cart:', productId, 'Quantity:', quantity);
            
            $.ajax({
                url: pr_product_object.ajax_url,
                type: 'POST',
                data: {
                    action: 'add_to_cart_custom',
                    product_id: productId,
                    quantity: quantity,
                    nonce: pr_product_object.nonce
                },
                success: function(response) {
                    console.log('AJAX success:', response);
                    if (response.success) {
                        successCount++;
                        totalItems += quantity;
                    } else {
                        console.error('Failed to add product to cart:', response.data);
                    }
                    
                    // Process next product
                    index++;
                    addNextProduct();
                },
                error: function(xhr, status, error) {
                    console.error('AJAX error:', error);
                    console.error('Status:', status);
                    console.error('Response:', xhr.responseText);
                    
                    // Process next product even if there was an error
                    index++;
                    addNextProduct();
                }
            });
        }
        
        // Start processing products
        addNextProduct();
    }
    
    // Check for success message from previous page load
    if (sessionStorage.getItem('pr_cart_success')) {
        var successMessage = sessionStorage.getItem('pr_cart_success');
        var statusDiv = $('#add-to-cart-status');
        if (statusDiv.length === 0) {
            statusDiv = $('<div id="add-to-cart-status" class="woocommerce-info"></div>');
            $('.woocommerce-account-content').prepend(statusDiv);
        }
        
        statusDiv.html(successMessage + ' <a href="' + woocommerce_params.cart_url + '">View Cart</a>').show();
        
        // Hide the message after 5 seconds
        setTimeout(function() {
            statusDiv.fadeOut();
        }, 5000);
        
        // Clear the stored message
        sessionStorage.removeItem('pr_cart_success');
    }
});
</script> 