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
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-product-recommendations-db.php';
	
	Product_Recommendations_Activator::activate();
	Product_Recommendations_DB::create_tables();
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

function pr_flush_rewrite_rules() {
	// Just set a flag to flush rules later
	update_option('product_recommendations_flush_rewrite_rules', true);
}

register_activation_hook(__FILE__, 'pr_flush_rewrite_rules');

// Also add a function to flush rules on plugin update
function pr_maybe_flush_rules() {
	if (get_option('product_recommendations_version') !== PRODUCT_RECOMMENDATIONS_VERSION) {
		update_option('product_recommendations_flush_rewrite_rules', true);
		update_option('product_recommendations_version', PRODUCT_RECOMMENDATIONS_VERSION);
	}
}
add_action('plugins_loaded', 'pr_maybe_flush_rules');

// Add this to the existing plugin file
function pr_update_database() {
	// Check if we need to update the database
	if (get_option('product_recommendations_db_version') !== PRODUCT_RECOMMENDATIONS_VERSION) {
		require_once plugin_dir_path(__FILE__) . 'includes/class-product-recommendations-db.php';
		Product_Recommendations_DB::update_tables();
		update_option('product_recommendations_db_version', PRODUCT_RECOMMENDATIONS_VERSION);
	}
}
add_action('plugins_loaded', 'pr_update_database', 5); // Run before other plugin code
