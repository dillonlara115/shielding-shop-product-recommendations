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
$customer_table = $wpdb->prefix . 'pr_customers';
$customer = $wpdb->get_row($wpdb->prepare(
    "SELECT c.*, u.display_name, u.user_email 
     FROM $customer_table c
     JOIN {$wpdb->users} u ON c.user_id = u.ID
     WHERE c.id = %d AND c.team_member_id = %d",
    $customer_id,
    get_current_user_id()
));

if (!$customer) {
    wc_add_notice(__('Customer not found', 'product-recommendations'), 'error');
    return;
}

// Get existing recommendations
$recommendations_table = $wpdb->prefix . 'pr_recommendations';
$recommendations = $wpdb->get_results($wpdb->prepare(
    "SELECT r.*, p.post_title as product_name, p.ID as product_id, rm.name as room_name
     FROM $recommendations_table r
     JOIN {$wpdb->posts} p ON r.product_id = p.ID
     LEFT JOIN {$wpdb->prefix}pr_rooms rm ON r.room_id = rm.id
     WHERE r.customer_id = %d
     ORDER BY COALESCE(r.room_id, 0), r.date_created DESC",
    $customer_id
));

// Generate nonce for AJAX
$ajax_nonce = wp_create_nonce('product_search_nonce');
?>

<div class="woocommerce-account-content">
    <h2>
        <?php printf(
            esc_html__('Manage Recommendations for %s', 'product-recommendations'),
            esc_html($customer->display_name)
        ); ?>
    </h2>
    
    <div class="woocommerce-notices-wrapper"></div>
    
    <div class="customer-info card mb-4">
        <header class="card-header">
            <p class="card-header-title">
                <?php esc_html_e('Customer Information', 'product-recommendations'); ?>
            </p>
        </header>
        <div class="card-content">
            <div class="content">
                <p><strong><?php esc_html_e('Name:', 'product-recommendations'); ?></strong> <?php echo esc_html($customer->display_name); ?></p>
                <p><strong><?php esc_html_e('Email:', 'product-recommendations'); ?></strong> <?php echo esc_html($customer->user_email); ?></p>
                <p><strong><?php esc_html_e('Added on:', 'product-recommendations'); ?></strong> <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($customer->date_added))); ?></p>
            </div>
        </div>
    </div>
    
    <div class="rooms-management mt-6 mb-6">
        <h3 class="title is-4"><?php esc_html_e('Room Management', 'product-recommendations'); ?></h3>
        
        <div class="mb-4">
            <button id="add-room-btn" class="button is-primary" data-customer-id="<?php echo esc_attr($customer_id); ?>">
                <?php esc_html_e('Add New Room', 'product-recommendations'); ?>
            </button>
        </div>

        <?php
        // Get rooms for current customer
        $rooms = $wpdb->get_results($wpdb->prepare(
            "SELECT id, name, date_created FROM {$wpdb->prefix}pr_rooms 
             WHERE customer_id = %d 
             ORDER BY name ASC",
            $customer_id
        ));
        ?>

        <table class="woocommerce-table shop_table">
            <thead>
                <tr>
                    <th><?php esc_html_e('Room Name', 'product-recommendations'); ?></th>
                    <th><?php esc_html_e('Date Added', 'product-recommendations'); ?></th>
                    <th><?php esc_html_e('Actions', 'product-recommendations'); ?></th>
                </tr>
            </thead>
            <tbody id="rooms-list">
                <?php if (empty($rooms)): ?>
                    <tr>
                        <td colspan="3" class="woocommerce-no-items"><?php esc_html_e('No rooms found', 'product-recommendations'); ?></td>
                    </tr>
                <?php else: 
                    foreach ($rooms as $room): ?>
                        <tr data-id="<?php echo esc_attr($room->id); ?>">
                            <td class="room-name is-capitalized"><?php echo esc_html($room->name); ?></td>
                            <td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($room->date_created))); ?></td>
                            <td>
                                <button class="button button-edit-room" data-id="<?php echo esc_attr($room->id); ?>">
                                    <?php esc_html_e('Edit', 'product-recommendations'); ?>
                                </button>
                                <button class="button button-remove-room" data-id="<?php echo esc_attr($room->id); ?>">
                                    <?php esc_html_e('Delete', 'product-recommendations'); ?>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach;
                endif; ?>
            </tbody>
        </table>
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
                
                <div id="selected-product-card" class="selected-product mt-4" style="display: none;">
                    <div class="columns">
                        <div class="column is-2">
                            <div id="selected-product-image"></div>
                        </div>
                        <div class="column">
                            <h4 id="selected-product-name" class="title is-5 mb-2"></h4>
                            <p id="selected-product-price" class="is-6 mb-2"></p>
                            <div class="field">
                                <label class="label" for="recommendation_notes"><?php esc_html_e('Notes', 'product-recommendations'); ?></label>
                                <div class="control">
                                    <textarea class="textarea" id="recommendation_notes" placeholder="<?php esc_attr_e('Add notes about why you recommend this product...', 'product-recommendations'); ?>"></textarea>
                                </div>
                            </div>
                            <div class="field">
                                <label class="label" for="recommendation_room"><?php esc_html_e('Room (Optional)', 'product-recommendations'); ?></label>
                                <div class="control">
                                    <div class="select">
                                        <select id="recommendation_room" name="recommendation_room">
                                            <option value=""><?php esc_html_e('Core Recommendations', 'product-recommendations'); ?></option>
                                            <?php
                                            // Get rooms for current customer
                                            $rooms = $wpdb->get_results($wpdb->prepare(
                                                "SELECT id, name FROM {$wpdb->prefix}pr_rooms WHERE customer_id = %d ORDER BY name ASC",
                                                $customer_id
                                            ));
                                            
                                            foreach ($rooms as $room) {
                                                echo '<option value="' . esc_attr($room->id) . '">' . esc_html($room->name) . '</option>';
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <button id="add-recommendation-btn" class="button is-primary" data-customer-id="<?php echo esc_attr($customer_id); ?>">
                                <?php esc_html_e('Add Recommendation', 'product-recommendations'); ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="existing-recommendations">
        <?php
        // Debug output
        error_log('Recommendations data: ' . print_r($recommendations, true));
        
        // Get recommendations grouped by room
        $recommendations_by_room = array();
        $general_recommendations = array();
        
        foreach ($recommendations as $recommendation) {
            error_log('Processing recommendation: Room ID=' . var_export($recommendation->room_id, true) . ', Room Name=' . var_export($recommendation->room_name, true));
            
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
        
        // After grouping
        error_log('Core recommendations count: ' . count($general_recommendations));
        error_log('Room recommendations count: ' . count($recommendations_by_room));
        error_log('Room recommendations data: ' . print_r($recommendations_by_room, true));
        
        // Display core recommendations first
        if (!empty($general_recommendations)): ?>
            <h3 class="title is-4 is-capitalized"><?php esc_html_e('Core Recommendations', 'product-recommendations'); ?></h3>
            <?php display_recommendations_table($general_recommendations); ?>
        <?php endif;
        
        // Display room-specific recommendations
        foreach ($recommendations_by_room as $room_id => $room_data): 
            if (!empty($room_data['name'])): ?>
                <h3 class="title is-4 mt-6 is-capitalized"><?php echo esc_html($room_data['name']); ?></h3>
                <?php display_recommendations_table($room_data['recommendations']); ?>
            <?php endif;
        endforeach; ?>
    </div>
</div>

<style>
.product-search-results {
    display: none;
    position: absolute;
    background: white;
    border: 1px solid #ddd;
    border-top: none;
    max-height: 300px;
    overflow-y: auto;
    width: 100%;
    z-index: 1000;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.product-search-results .product-result {
    padding: 10px;
    cursor: pointer;
    border-bottom: 1px solid #f0f0f0;
    display: flex;
    align-items: center;
}

.product-search-results .product-result:hover {
    background-color: #f8f9fa;
}

.product-result-image {
    width: 50px;
    height: 50px;
    margin-right: 10px;
    background-size: cover;
    background-position: center;
}

.product-result-info {
    flex: 1;
}

.product-result-name {
    font-weight: bold;
}

.product-result-price {
    color: #666;
    font-size: 0.9em;
}

#selected-product-image img {
    max-width: 100%;
    height: auto;
}
</style>

<?php
// Helper function to display recommendations table
function display_recommendations_table($recommendations) {
    include 'recommendations-table.php';
}
?> 