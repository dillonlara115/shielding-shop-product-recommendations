<?php
/**
 * Template for tab navigation in Product Recommendations
 *
 * @package    Product_Recommendations
 * @subpackage Product_Recommendations/public/partials
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get the current URL
$current_url = home_url(add_query_arg(array()));

// Determine current tab based on URL patterns
$is_dashboard = strpos($current_url, '/product-recommendations') !== false && 
                strpos($current_url, '/product-recommendations/') === false;
                
$is_customers = strpos($current_url, '/product-recommendations/customers') !== false && 
                strpos($current_url, '/product-recommendations/customers/add') === false;
                
$is_add_customer = strpos($current_url, '/product-recommendations/customers/add') !== false;

// Get the current tab from the including file
$current_tab = isset($current_tab) ? $current_tab : 'dashboard';

// Define tabs
$tabs = array(
    'dashboard' => array(
        'url' => wc_get_account_endpoint_url('product-recommendations'),
        'label' => __('Dashboard', 'product-recommendations'),
        'is_active' => $current_tab === 'dashboard'
    ),
    'customers' => array(
        'url' => wc_get_account_endpoint_url('product-recommendations/customers'),
        'label' => __('Customers', 'product-recommendations'),
        'is_active' => $current_tab === 'customers'
    ),
    'add_customer' => array(
        'url' => wc_get_account_endpoint_url('product-recommendations/customers/add'),
        'label' => __('Add Customer', 'product-recommendations'),
        'is_active' => $current_tab === 'add_customer'
    )
);
?>

<div class="tabs-wrapper mb-5">
    <div class="tabs">
        <ul>
            <?php foreach ($tabs as $key => $tab) : ?>
                <li class="<?php echo $tab['is_active'] ? 'is-active' : ''; ?>">
                    <a href="<?php echo esc_url($tab['url']); ?>">
                        <?php echo esc_html($tab['label']); ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div> 