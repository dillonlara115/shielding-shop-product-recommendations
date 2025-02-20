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
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Product_Recommendations_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Product_Recommendations_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/product-recommendations-public.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Product_Recommendations_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Product_Recommendations_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/product-recommendations-public.js', array( 'jquery' ), $this->version, false );

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
