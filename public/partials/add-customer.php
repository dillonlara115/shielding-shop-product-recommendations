<?php
/**
 * Template for adding new customers
 *
 * @package    Product_Recommendations
 * @subpackage Product_Recommendations/public/partials
 */

if (!defined('ABSPATH')) {
    exit;
}

// Before including the tabs navigation
$current_tab = 'add_customer';
include_once plugin_dir_path(__FILE__) . 'tabs-navigation.php';

// Generate nonce for AJAX
$ajax_nonce = wp_create_nonce('user_search_nonce');
?>

<div class="woocommerce-account-content">
    <h2><?php esc_html_e('Add New Customer', 'product-recommendations'); ?></h2>
    
    <div class="woocommerce-notices-wrapper"></div>
    
    <div class="search-section mb-4">
        <div class="field">
            <label class="label" for="user_search"><?php esc_html_e('Search Existing Users', 'product-recommendations'); ?></label>
            <div class="control">
                <input type="text" 
                       class="input" 
                       name="user_search" 
                       id="user_search" 
                       autocomplete="off"
                       placeholder="<?php esc_attr_e('Start typing to search users...', 'product-recommendations'); ?>" />
                <div id="user-search-results" class="user-search-results"></div>
            </div>
        </div>
    </div>

    <div id="selected-user-card" class="card" style="display: none;">
        <header class="card-header">
            <p class="card-header-title">
                <?php esc_html_e('Selected User', 'product-recommendations'); ?>
            </p>
        </header>
        <div class="card-content">
            <div class="content">
                <div class="field">
                    <label class="label"><?php esc_html_e('Name', 'product-recommendations'); ?></label>
                    <p id="selected-user-name" class="subtitle"></p>
                </div>
                <div class="field">
                    <label class="label"><?php esc_html_e('Email', 'product-recommendations'); ?></label>
                    <p id="selected-user-email" class="subtitle"></p>
                </div>
                <div class="field">
                    <label class="label"><?php esc_html_e('User ID', 'product-recommendations'); ?></label>
                    <p id="selected-user-id" class="subtitle"></p>
                </div>
            </div>
        </div>
        <footer class="card-footer">
            <button id="add-customer-btn" class="button is-primary card-footer-item">
                <?php esc_html_e('Add Customer', 'product-recommendations'); ?>
            </button>
        </footer>
    </div>
</div>

<style>
.user-search-results {
    display: none;
    position: absolute;
    background: white;
    border: 1px solid #ddd;
    border-top: none;
    max-height: 200px;
    overflow-y: auto;
    width: 100%;
    z-index: 1000;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.user-search-results .user-result {
    padding: 10px;
    cursor: pointer;
    border-bottom: 1px solid #f0f0f0;
}

.user-search-results .user-result:hover {
    background-color: #f8f9fa;
}

#user_search {
    position: relative;
    width: 100%;
}

.card {
    margin-top: 1.5rem;
}

.subtitle {
    margin-bottom: 0.5rem !important;
}
</style> 