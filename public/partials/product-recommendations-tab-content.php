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
?>

<div class="woocommerce-recommendations">
    <h2>Your Product Recommendations</h2>
    <p>Welcome <?php echo esc_html($current_user->display_name); ?>, here are your personalized product recommendations.</p>
    
    <?php
    // Add your recommendation logic here
    ?>
</div> 