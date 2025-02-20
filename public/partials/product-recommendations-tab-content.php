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

<!-- Tailwind/DaisyUI Test -->
<div class="container mx-auto p-4">
    <div class="w-full ">
        <div class="">
            <h2 class=" text-2xl mb-4">Your Product Recommendations</h2>
            <p class="text-lg mb-6">
                Welcome <?php echo esc_html($current_user->display_name); ?>, 
                here are your personalized product recommendations.
            </p>
            
            </div>
    </div>
</div>  