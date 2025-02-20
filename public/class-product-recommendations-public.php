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
		
		// Load DaisyUI
		wp_enqueue_style(
			$this->plugin_name . '-daisyui',
			'https://cdn.jsdelivr.net/npm/daisyui@4.12.23/dist/full.min.css',
			array(),
			'4.12.23'
		);

		// Load plugin-specific styles after frameworks
		wp_enqueue_style(
			$this->plugin_name,
			plugin_dir_url(__FILE__) . 'css/product-recommendations-public.css',
			array($this->plugin_name . '-daisyui'),
			$this->version
		);
	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 */
	public function enqueue_scripts() {
		// Debug
		error_log('Enqueuing scripts for Product Recommendations');
		
		// Load Tailwind
		wp_enqueue_script(
			$this->plugin_name . '-tailwind', 
			'https://cdn.tailwindcss.com',
			array(),
			null,
			false // Load in header
		);

		// Add Tailwind configuration
		wp_add_inline_script($this->plugin_name . '-tailwind', "
			tailwind.config = {
				corePlugins: {
					preflight: false,
				},
				important: true,
				theme: {
					extend: {}
				},
				daisyui: {
					themes: ['light']
				}
			}
		");

		// Load plugin-specific scripts
		wp_enqueue_script(
			$this->plugin_name,
			plugin_dir_url(__FILE__) . 'js/product-recommendations-public.js',
			array('jquery', $this->plugin_name . '-tailwind'),
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
		return $vars;
	}

	public function add_recommendations_endpoint() {
		// Only add if WooCommerce is active
		if (!class_exists('WooCommerce')) {
			return;
		}
		
		// Add the endpoint
		add_rewrite_endpoint('product-recommendations', EP_ROOT | EP_PAGES);
	}

	public function init() {
		add_rewrite_endpoint('product-recommendations', EP_ROOT | EP_PAGES);
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
		// Debug output
		echo "<!-- Loading Product Recommendations Content -->";
		
		// Load the template
		$template = plugin_dir_path( dirname( __FILE__ ) ) . 'public/partials/product-recommendations-tab-content.php';
		
		// Allow template override in theme
		$theme_template = locate_template('woocommerce/product-recommendations-tab-content.php');
		if ($theme_template) {
			$template = $theme_template;
		}
		
		if (file_exists($template)) {
			include $template;
		}
	}

}
