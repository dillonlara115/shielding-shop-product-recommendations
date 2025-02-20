<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://dillonlara.me
 * @since             1.0.0
 * @package           Product_Recommendations
 *
 * @wordpress-plugin
 * Plugin Name:       The Shielding Shop Product Recommendations
 * Plugin URI:        https://seosuccor.com
 * Description:       This is a custom built plugin for The Shielding Shop which has been designed to setup custom product recommendations for customers from Team Members of The Shielding Shop.
 * Version:           1.0.0
 * Author:            Dillon Lara
 * Author URI:        https://dillonlara.me/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       product-recommendations
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'PRODUCT_RECOMMENDATIONS_VERSION', '1.0.0' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-product-recommendations-activator.php
 */
function activate_product_recommendations() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-product-recommendations-activator.php';
	Product_Recommendations_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-product-recommendations-deactivator.php
 */
function deactivate_product_recommendations() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-product-recommendations-deactivator.php';
	Product_Recommendations_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_product_recommendations' );
register_deactivation_hook( __FILE__, 'deactivate_product_recommendations' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-product-recommendations.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_product_recommendations() {

	$plugin = new Product_Recommendations();
	$plugin->run();

}
run_product_recommendations();
