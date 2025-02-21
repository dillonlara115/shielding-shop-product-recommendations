<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://dillonlara.me
 * @since      1.0.0
 *
 * @package    Product_Recommendations
 * @subpackage Product_Recommendations/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Product_Recommendations
 * @subpackage Product_Recommendations/public
 * @author     Dillon Lara <dev@seosuccor>
 */
class Product_Recommendations_Public {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version = $version;
	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 */
	public function enqueue_styles() {
		// Debug
		error_log('Enqueuing styles for Product Recommendations');
		
	
		// Load plugin-specific styles after frameworks
		wp_enqueue_style(
			$this->plugin_name,
			plugin_dir_url(__FILE__) . 'css/product-recommendations-public.css',
			array(),
			$this->version
		);
	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 */
	public function enqueue_scripts() {
		// Debug
		error_log('Enqueuing scripts for Product Recommendations');
		



		// Load plugin-specific scripts
		wp_enqueue_script(
			$this->plugin_name,
			plugin_dir_url(__FILE__) . 'js/product-recommendations-public.js',
			array(),
			$this->version,
			true // Load in footer
		);
	}

	/**
	 * Add the endpoint to WooCommerce query vars
	 */
	public function add_recommendations_query_vars($vars) {
		if (!is_array($vars)) {
			$vars = array();
		}
		$vars[] = 'product-recommendations';
		$vars[] = 'product-recommendations/customers';
		return $vars;
	}

	public function add_recommendations_endpoint() {
		if (!class_exists('WooCommerce')) {
			return;
		}
		
		// Register the base endpoint
		add_rewrite_endpoint('product-recommendations', EP_ROOT | EP_PAGES);
		add_rewrite_endpoint('product-recommendations/customers', EP_ROOT | EP_PAGES);
		add_rewrite_endpoint('product-recommendations/recommendations', EP_ROOT | EP_PAGES);
		
		// Ensure WordPress recognizes new endpoints
		flush_rewrite_rules();
	}
	
	public function init() {
		add_rewrite_endpoint('product-recommendations', EP_ROOT | EP_PAGES);
		add_rewrite_endpoint('product-recommendations/customers', EP_ROOT | EP_PAGES); 
		add_rewrite_endpoint('product-recommendations/recommendations', EP_ROOT | EP_PAGES);
	}

	public function add_recommendations_menu_item($items) {
		// Insert our item before the logout menu item
		$logout = $items['customer-logout'];
		unset($items['customer-logout']);
		
		$items['product-recommendations'] = 'Product Recommendations';
		$items['customer-logout'] = $logout;
		
		return $items;
	}

	public function recommendations_content() {
		global $wp_query;
	
		// Debugging Output
		echo "<!-- Checking WooCommerce Endpoint -->";
		// echo "<pre>";
		// print_r($wp_query->query_vars);
		// echo "</pre>";
	
		// Check if we are on '/product-recommendations/customers'
		$endpoint_value = get_query_var('product-recommendations');
	
		if ($endpoint_value === 'customers') {
			echo "<!-- Loading Customers Content -->";
	
			// Load the correct template for customers
			$template = plugin_dir_path(dirname(__FILE__)) . 'public/partials/view-customers.php';
			$theme_template = locate_template('woocommerce/view-customers.php');
	
			if ($theme_template) {
				$template = $theme_template;
			}
	
			if (file_exists($template)) {
				include $template;
			}
			return;
		}

		// Check if we are on '/product-recommendations/recommendations'
		if ($endpoint_value === 'recommendations') {
			echo "<!-- Loading Recommendations Content -->";

			// Load the correct template for recommendations
			$template = plugin_dir_path(dirname(__FILE__)) . 'public/partials/view-recommendations.php';
			$theme_template = locate_template('woocommerce/view-recommendations.php');

			if ($theme_template) {
				$template = $theme_template;
			}

			if (file_exists($template)) {
				include $template;
			}
			return;
		}
	
		// Default to product-recommendations-tab-content.php
		echo "<!-- Loading Product Recommendations Content -->";
		$template = plugin_dir_path(dirname(__FILE__)) . 'public/partials/product-recommendations-tab-content.php';
		$theme_template = locate_template('woocommerce/product-recommendations-tab-content.php');
	
		if ($theme_template) {
			$template = $theme_template;
		}
	
		if (file_exists($template)) {
			include $template;
		}
	}
	
}
