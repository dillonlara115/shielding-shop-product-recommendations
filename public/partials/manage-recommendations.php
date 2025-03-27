<?php
/**
 * Template for managing customer recommendations
 *
 * @package    Product_Recommendations
 * @subpackage Product_Recommendations/public/partials
 */

if (!defined('ABSPATH')) {
    exit;
}

// Include the shared recommendations table function
require_once plugin_dir_path(__FILE__) . 'recommendations-table-shared.php';

// Include tab navigation
include_once plugin_dir_path(__FILE__) . 'tabs-navigation.php';

// Get customer ID from URL
$customer_id = isset($customer_id) ? intval($customer_id) : 0;

if (!$customer_id) {
    wc_add_notice(__('Invalid customer', 'product-recommendations'), 'error');
    return;
}

// Get customer details
global $wpdb;
$customer = $wpdb->get_row($wpdb->prepare(
    "SELECT c.*, u.display_name as customer_name, u.user_email as customer_email
     FROM {$wpdb->prefix}pr_customers c
     JOIN {$wpdb->users} u ON c.user_id = u.ID
     WHERE c.id = %d AND c.team_member_id = %d",
    $customer_id,
    get_current_user_id()
));

if (!$customer) {
    wc_add_notice(__('Customer not found or not associated with your account', 'product-recommendations'), 'error');
    return;
}

// Get recommendations for this customer
$recommendations = $wpdb->get_results($wpdb->prepare(
    "SELECT r.*, p.post_title as product_name, rm.name as room_name
     FROM {$wpdb->prefix}pr_recommendations r
     LEFT JOIN {$wpdb->posts} p ON r.product_id = p.ID
     LEFT JOIN {$wpdb->prefix}pr_rooms rm ON r.room_id = rm.id
     WHERE r.customer_id = %d AND r.team_member_id = %d
     ORDER BY COALESCE(r.room_id, 0), r.date_created DESC",
    $customer_id,
    get_current_user_id()
));

// Get rooms for this customer
$rooms = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}pr_rooms WHERE customer_id = %d ORDER BY name ASC",
    $customer_id
));
?>

<div class="woocommerce-account-content">
    <h2><?php esc_html_e('Manage Customer Recommendations', 'product-recommendations'); ?></h2>
    
    <div class="woocommerce-notices-wrapper"></div>
    
    <div class="customer-details card mb-4">
        <header class="card-header">
            <p class="card-header-title">
                <?php esc_html_e('Customer Details', 'product-recommendations'); ?>
            </p>
        </header>
        <div class="card-content">
            <div class="content">
                <p><strong><?php esc_html_e('Name:', 'product-recommendations'); ?></strong> <?php echo esc_html($customer->customer_name); ?></p>
                <p><strong><?php esc_html_e('Email:', 'product-recommendations'); ?></strong> <?php echo esc_html($customer->customer_email); ?></p>
                <p><strong><?php esc_html_e('Status:', 'product-recommendations'); ?></strong> 
                    <span class="status-badge status-<?php echo esc_attr($customer->status); ?>">
                        <?php echo esc_html(ucfirst($customer->status)); ?>
                    </span>
                </p>
                <p><strong><?php esc_html_e('Added:', 'product-recommendations'); ?></strong> <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($customer->date_added))); ?></p>
            </div>
        </div>
    </div>
    
    <div class="rooms card mb-4">
        <header class="card-header">
            <p class="card-header-title">
                <?php esc_html_e('Rooms', 'product-recommendations'); ?>
            </p>
        </header>
        <div class="card-content">
            <div class="content">
                <table class="woocommerce-table shop_table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Room Name', 'product-recommendations'); ?></th>
                            <th><?php esc_html_e('Actions', 'product-recommendations'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($rooms)): ?>
                            <tr>
                                <td colspan="2" class="woocommerce-no-items"><?php esc_html_e('No rooms found', 'product-recommendations'); ?></td>
                            </tr>
                        <?php else:
                            foreach ($rooms as $room): ?>
                                <tr>
                                    <td class="room-name"><?php echo esc_html($room->name); ?></td>
                                    <td>
                                        <div class="buttons are-small">
                                            <button class="button button-edit-room" data-id="<?php echo esc_attr($room->id); ?>">
                                                <?php esc_html_e('Edit', 'product-recommendations'); ?>
                                            </button>
                                            <button class="button button-remove-room" data-id="<?php echo esc_attr($room->id); ?>" title="<?php esc_attr_e('Delete', 'product-recommendations'); ?>">
                                                <span><?php esc_html_e('Delete', 'product-recommendations'); ?></span>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach;
                        endif; ?>
                    </tbody>
                </table>
                <button id="add-room-btn" class="button mt-3"><?php esc_html_e('Add Room', 'product-recommendations'); ?></button>
            </div>
        </div>
    </div>
    
    <div class="add-recommendation card mb-4">
        <header class="card-header">
            <p class="card-header-title">
                <?php esc_html_e('Add New Recommendation', 'product-recommendations'); ?>
            </p>
        </header>
        <div class="card-content">
            <div class="content">
                <div class="field">
                    <label class="label" for="product_search"><?php esc_html_e('Search Products', 'product-recommendations'); ?></label>
                    <div class="control">
                        <input type="text" 
                               class="input" 
                               name="product_search" 
                               id="product_search" 
                               autocomplete="off"
                               placeholder="<?php esc_attr_e('Start typing to search products...', 'product-recommendations'); ?>" />
                        <div id="product-search-results" class="product-search-results"></div>
                    </div>
                </div>
                
                <div id="selected-product-card" class="selected-product-card" style="display: none;">
                    <div class="selected-product-header">
                        <h4><?php esc_html_e('Selected Product', 'product-recommendations'); ?></h4>
                    </div>
                    <div class="selected-product-content">
                        <div class="selected-product-image" id="selected-product-image"></div>
                        <div class="selected-product-details">
                            <div class="selected-product-name" id="selected-product-name"></div>
                            <div class="selected-product-price" id="selected-product-price"></div>
                        </div>
                    </div>
                    
                    <div class="field">
                        <label class="label" for="recommendation_room"><?php esc_html_e('Room (Optional)', 'product-recommendations'); ?></label>
                        <div class="control">
                            <select name="recommendation_room" id="recommendation_room" class="input">
                                <option value=""><?php esc_html_e('General Recommendations', 'product-recommendations'); ?></option>
                                <?php foreach ($rooms as $room): ?>
                                    <option value="<?php echo esc_attr($room->id); ?>"><?php echo esc_html($room->name); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="field">
                        <label class="label" for="recommendation_notes"><?php esc_html_e('Notes (Optional)', 'product-recommendations'); ?></label>
                        <div class="control">
                            <textarea name="recommendation_notes" id="recommendation_notes" class="textarea" placeholder="<?php esc_attr_e('Add notes about why you recommend this product...', 'product-recommendations'); ?>"></textarea>
                        </div>
                    </div>
                    
                    <div class="field">
                        <label class="label" for="recommendation_quantity"><?php esc_html_e('Quantity', 'product-recommendations'); ?></label>
                        <div class="control">
                            <input type="number" name="recommendation_quantity" id="recommendation_quantity" class="input" min="1" value="1">
                        </div>
                    </div>
                    
                    <div class="field">
                        <button id="add-recommendation-btn" class="button is-primary" data-customer-id="<?php echo esc_attr($customer_id); ?>">
                            <?php esc_html_e('Add Recommendation', 'product-recommendations'); ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="existing-recommendations">
        <h3 class="title is-2"><?php esc_html_e('Current Recommendations', 'product-recommendations'); ?></h3>
        
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
        
        // Set up context for team member view
        $team_member_context = array(
            'view' => 'team_member',
            'show_actions' => true,
            'show_status' => true,
            'show_notes' => true,
            'show_subtotal' => false
        );
        
        // Display core recommendations first
        if (!empty($general_recommendations)): ?>
            <h3 class="title is-2 is-capitalized"><?php esc_html_e('Core Recommendations', 'product-recommendations'); ?></h3>
            <?php display_recommendations_table($general_recommendations, $team_member_context); ?>
        <?php endif;
        
        // Display room-specific recommendations
        foreach ($recommendations_by_room as $room_id => $room_data): 
            if (!empty($room_data['name'])): ?>
                <h3 class="title is-2 mt-6 is-capitalized"><?php echo esc_html($room_data['name']); ?></h3>
                <?php display_recommendations_table($room_data['recommendations'], $team_member_context); ?>
            <?php endif;
        endforeach; ?>
    </div>
</div> 