<?php

/**
 * Fired during plugin activation
 *
 * @link       https://dillonlara.me
 * @since      1.0.0
 *
 * @package    Product_Recommendations
 * @subpackage Product_Recommendations/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Product_Recommendations
 * @subpackage Product_Recommendations/includes
 * @author     Dillon Lara <dev@seosuccor>
 */
class Product_Recommendations_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
		// Make sure WooCommerce is active
		if (!class_exists('WooCommerce')) {
			deactivate_plugins(plugin_basename(__FILE__));
			wp_die('This plugin requires WooCommerce to be installed and active.');
		}
		
		// Add the endpoint
		add_rewrite_endpoint('product-recommendations', EP_ROOT | EP_PAGES);
		
		// Flush rewrite rules
		flush_rewrite_rules();
	}

}
