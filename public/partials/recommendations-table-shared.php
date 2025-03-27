<?php
/**
 * Shared function for displaying recommendation tables
 *
 * @package    Product_Recommendations
 * @subpackage Product_Recommendations/public/partials
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Display a recommendations table with context-aware features
 * 
 * @param array $recommendations Array of recommendation objects
 * @param array $context Context settings to customize the table display
 *   - view: string - The current view ('team_member', 'customer', 'admin')
 *   - show_actions: bool - Whether to show action buttons
 *   - show_status: bool - Whether to show status column
 *   - show_notes: bool - Whether to show notes column
 *   - show_subtotal: bool - Whether to show subtotal row
 *   - room_product_ids: array - Product IDs for "Add All to Cart" functionality
 *   - placeholder_image: string - URL for placeholder image
 */
function display_recommendations_table($recommendations, $context = array()) {
    // Default context settings
    $defaults = array(
        'view' => 'team_member',  // team_member, customer, admin
        'show_actions' => true,
        'show_status' => true,
        'show_notes' => true,
        'show_subtotal' => false,
        'room_product_ids' => array(),
        'placeholder_image' => '/wp-content/uploads/2023/09/product-placeholder.png'
    );
    
    // Merge defaults with provided context
    $context = wp_parse_args($context, $defaults);
    
    // Calculate subtotal if needed
    $subtotal = 0;
    $has_variable_pricing = false;
    $product_prices = array();
    
    if ($context['show_subtotal']) {
        foreach ($recommendations as $recommendation) {
            $product = wc_get_product($recommendation->product_id);
            if ($product) {
                if ($product->is_type('variable')) {
                    $has_variable_pricing = true;
                    // Get min and max prices for variable products
                    $min_price = $product->get_variation_price('min');
                    $max_price = $product->get_variation_price('max');
                    if ($min_price == $max_price) {
                        $product_prices[] = $min_price;
                    } else {
                        // Use minimum price for subtotal calculation
                        $product_prices[] = $min_price;
                    }
                } else {
                    $product_prices[] = $product->get_price();
                }
            }
        }
        
        // Calculate minimum subtotal
        $subtotal = array_sum($product_prices);
    }
    
    // Determine column count for empty state and colspan values
    $column_count = 2; // Image and Product columns are always shown
    if ($context['show_notes']) $column_count++;
    if ($context['show_status']) $column_count++;
    $column_count++; // Date Added column is always shown
    if ($context['show_actions']) $column_count++;
    
    // Is the current user a member?
    $is_member = current_user_can('read') && !current_user_can('manage_options') && !current_user_can('edit_shop_orders');
    
    // Is the current user a team admin?
    $is_team_admin = current_user_can('manage_options') || current_user_can('edit_shop_orders');
    ?>
    
    <table class="woocommerce-table shop_table recommendations-table">
        <thead>
            <tr>
                <th class="product-thumbnail"><?php esc_html_e('Image', 'product-recommendations'); ?></th>
                <th><?php esc_html_e('Product', 'product-recommendations'); ?></th>
                <th><?php esc_html_e('Date Added', 'product-recommendations'); ?></th>
                <?php if ($context['show_notes']): ?>
                    <th><?php esc_html_e('Notes', 'product-recommendations'); ?></th>
                <?php endif; ?>
                <?php if ($context['show_status']): ?>
                    <th><?php esc_html_e('Status', 'product-recommendations'); ?></th>
                <?php endif; ?>
                <?php if ($context['show_actions']): ?>
                    <th><?php esc_html_e('Actions', 'product-recommendations'); ?></th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php 
            if (empty($recommendations)): ?>
                <tr>
                    <td colspan="<?php echo esc_attr($column_count); ?>" class="woocommerce-no-items">
                        <?php esc_html_e('No recommendations found', 'product-recommendations'); ?>
                    </td>
                </tr>
            <?php else:
                foreach ($recommendations as $recommendation): 
                    $product = wc_get_product($recommendation->product_id);
                    $image_id = $product ? $product->get_image_id() : 0;
                    $image_url = $image_id ? wp_get_attachment_image_url($image_id, 'thumbnail') : $context['placeholder_image'];
                    $price_html = $product ? $product->get_price_html() : '';
                    $product_permalink = get_permalink($recommendation->product_id);
                    $is_private = $product && $product->get_status() === 'private';
                ?>
                    <tr data-id="<?php echo esc_attr($recommendation->id); ?>">
                        <td class="product-thumbnail">
                            <?php if ($context['view'] === 'customer'): ?>
                                <a href="<?php echo esc_url($product_permalink); ?>">
                                    <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($recommendation->product_name); ?>" />
                                </a>
                            <?php else: ?>
                                <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($recommendation->product_name); ?>" />
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="product-info">
                                <div class="product-name">
                                    <?php if ($context['view'] === 'customer'): ?>
                                        <a href="<?php echo esc_url($product_permalink); ?>">
                                            <?php echo esc_html($recommendation->product_name); ?>
                                        </a>
                                    <?php else: ?>
                                        <?php echo esc_html($recommendation->product_name); ?>
                                    <?php endif; ?>
                                    
                                    <?php if ($is_private): ?>
                                        <span class="private-product-label">Member Exclusive</span>
                                    <?php endif; ?>
                                </div>
                                <div class="product-price"><?php echo $price_html; ?></div>
                                <?php if (!empty($recommendation->quantity) && $recommendation->quantity > 1): ?>
                                    <div class="product-quantity">
                                        <?php echo sprintf(_n('Quantity: %d item', 'Quantity: %d items', $recommendation->quantity, 'product-recommendations'), $recommendation->quantity); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($recommendation->date_created))); ?></td>
                        
                        <?php if ($context['show_notes']): ?>
                            <td><?php echo esc_html($recommendation->notes); ?></td>
                        <?php endif; ?>
                        
                        <?php if ($context['show_status']): ?>
                            <td>
                                <span class="status-badge status-<?php echo esc_attr($recommendation->status); ?>">
                                    <?php echo esc_html(ucfirst($recommendation->status)); ?>
                                </span>
                            </td>
                        <?php endif; ?>
                        
                        <?php if ($context['show_actions']): ?>
                            <td class="is-flex is-2">
                                <?php if ($context['view'] === 'team_member'): ?>
                                    <button class="button button-remove-recommendation" data-id="<?php echo esc_attr($recommendation->id); ?>" title="<?php esc_attr_e('Remove', 'product-recommendations'); ?>">
                                        <span><?php esc_html_e('Remove', 'product-recommendations'); ?></span>
                                    </button>
                                <?php elseif ($context['view'] === 'customer'): ?>
                                    <button class="button add-to-cart-button" data-product-id="<?php echo esc_attr($recommendation->product_id); ?>">
                                        <?php esc_html_e('Add to Cart', 'product-recommendations'); ?>
                                    </button>
                                <?php endif; ?>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; 
                
                // Add subtotal row if needed
                if ($context['show_subtotal'] && !empty($recommendations)): 
                    // Calculate colspan for subtotal label
                    $subtotal_label_colspan = $column_count - 2; // -2 for subtotal value and actions columns
                ?>
                    <tr class="subtotal-row">
                        <td colspan="<?php echo esc_attr($subtotal_label_colspan); ?>" class="subtotal-label">
                            <strong><?php esc_html_e('Subtotal', 'product-recommendations'); ?></strong>
                            <?php if ($has_variable_pricing): ?>
                                <span class="variable-pricing-note">
                                    <?php esc_html_e('(Minimum - some products have variable pricing)', 'product-recommendations'); ?>
                                </span>
                            <?php endif; ?>
                        </td>
                        <td class="subtotal-value">
                            <strong><?php echo wc_price($subtotal); ?></strong>
                        </td>
                        <td class="add-all-cell">
                            <button class="button add-room-to-cart" data-products="<?php echo esc_attr(json_encode($context['room_product_ids'])); ?>">
                                <?php esc_html_e('Add All to Cart', 'product-recommendations'); ?>
                            </button>
                        </td>
                    </tr>
                <?php endif; ?>
            <?php endif; ?>
        </tbody>
    </table>
    <?php
} 