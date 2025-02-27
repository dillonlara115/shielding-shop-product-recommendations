<?php
/**
 * Template for the Product Recommendations tab content
 *
 * @package    Product_Recommendations
 * @subpackage Product_Recommendations/public/partials
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

$current_user = wp_get_current_user();

// Before including the tabs navigation
$current_tab = 'dashboard';
include_once plugin_dir_path(__FILE__) . 'tabs-navigation.php';
?>

<div class="container mx-auto p-4">
    <div class="w-full ">
        <div class="">
            <h2 class=" text-2xl mb-4">Your Product Recommendations</h2>
            <p class="text-lg mb-6">
                Welcome <?php echo esc_html($current_user->display_name); ?>, 
                here are your personalized product recommendations.
            </p>
            <div class="columns">
                <div class="column is-half">
                    <div class="card">
                        <header class="card-header">
                            <p class="card-header-title">
                                Manage Customers
                            </p>
                        </header>
                        <div class="card-content">
                            <div class="content">
                                <p>View and manage your customer list</p>
                            </div>
                        </div>
                        <footer class="card-footer">
                            <a href="<?php echo esc_url(wc_get_account_endpoint_url('product-recommendations/customers')); ?>" class="card-footer-item">View</a>
                            <a href="<?php echo esc_url(wc_get_account_endpoint_url('product-recommendations/customers/add')); ?>" class="card-footer-item">Add</a>
                        </footer>
                    </div>
                </div>
                <div class="column is-half">
                    <div class="card">
                        <header class="card-header">
                            <p class="card-header-title">
                                Manage Recommendations
                            </p>
                        </header>
                        <div class="card-content">
                            <div class="content">
                                <p>Create and manage product recommendations</p>
                            </div>
                        </div>
                        <footer class="card-footer">
                            <a href="<?php echo esc_url(wc_get_account_endpoint_url('product-recommendations/recommendations')); ?>" class="card-footer-item">View</a>
                            <a href="#" class="card-footer-item">Add</a>
                        </footer>
                    </div>
                </div>
            </div>
            </div>
    </div>
</div>  