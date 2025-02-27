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

		// Add existing hooks...

		// Add AJAX handlers
		add_action('wp_ajax_search_users', array($this, 'search_users_ajax'));
		add_action('wp_ajax_add_customer', array($this, 'add_customer_ajax'));
		add_action('wp_ajax_search_products', array($this, 'search_products_ajax'));
		add_action('wp_ajax_add_recommendation', array($this, 'add_recommendation_ajax'));
		add_action('wp_ajax_remove_recommendation', array($this, 'remove_recommendation_ajax'));
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

		// Enqueue user search script
		wp_enqueue_script(
			$this->plugin_name . '-user-search',
			plugin_dir_url(__FILE__) . 'js/user-search.js',
			array('jquery'),
			$this->version,
			true
		);

		// Localize script
		wp_localize_script(
			$this->plugin_name . '-user-search',
			'pr_ajax_object',
			array(
				'ajax_url' => admin_url('admin-ajax.php'),
				'nonce' => wp_create_nonce('user_search_nonce')
			)
		);

		// Enqueue product recommendations script
		wp_enqueue_script(
			$this->plugin_name . '-product-recommendations',
			plugin_dir_url(__FILE__) . 'js/product-recommendations.js',
			array('jquery'),
			$this->version,
			true
		);
		
		// Localize script
		wp_localize_script(
			$this->plugin_name . '-product-recommendations',
			'pr_product_object',
			array(
				'ajax_url' => admin_url('admin-ajax.php'),
				'nonce' => wp_create_nonce('product_search_nonce'),
				'texts' => array(
					'current_recommendations' => __('Current Recommendations', 'product-recommendations'),
					'image' => __('Image', 'product-recommendations'),
					'product' => __('Product', 'product-recommendations'),
					'date_added' => __('Date Added', 'product-recommendations'),
					'status' => __('Status', 'product-recommendations'),
					'notes' => __('Notes', 'product-recommendations'),
					'actions' => __('Actions', 'product-recommendations'),
					'remove' => __('Remove', 'product-recommendations'),
					'confirm_remove' => __('Are you sure you want to remove this recommendation?', 'product-recommendations'),
					'no_recommendations' => __('No recommendations found for this customer.', 'product-recommendations')
				)
			)
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
		$vars[] = 'product-recommendations/customers/add';
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
		add_rewrite_endpoint('product-recommendations/customers/add', EP_ROOT | EP_PAGES);
		add_rewrite_endpoint('product-recommendations/recommendations', EP_ROOT | EP_PAGES);
		add_rewrite_endpoint('product-recommendations/customers/([0-9]+)/recommendations', EP_ROOT | EP_PAGES);
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
	
		// Check for customers/add endpoint
		$endpoint_value = get_query_var('product-recommendations');
	
		if ($endpoint_value === 'customers/add') {
			$template = plugin_dir_path(dirname(__FILE__)) . 'public/partials/add-customer.php';
			$theme_template = locate_template('woocommerce/add-customer.php');
	
			if ($theme_template) {
				$template = $theme_template;
			}
	
			if (file_exists($template)) {
				include $template;
			}
			return;
		}

		// Check if we are on '/product-recommendations/customers'
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

		// Check for customer recommendations endpoint
		if (preg_match('/^customers\/(\d+)\/recommendations$/', $endpoint_value, $matches)) {
			$customer_id = intval($matches[1]);
			
			// Load the manage recommendations template
			$template = plugin_dir_path(dirname(__FILE__)) . 'public/partials/manage-recommendations.php';
			$theme_template = locate_template('woocommerce/manage-recommendations.php');
			
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
	
	/**
	 * Handle AJAX user search
	 */
	public function search_users_ajax() {
		// Verify nonce
		if (!check_ajax_referer('user_search_nonce', 'nonce', false)) {
			wp_send_json_error('Invalid nonce');
		}

		// Verify user can search
		if (!current_user_can('read')) {
			wp_send_json_error('Unauthorized');
		}

		$search = sanitize_text_field($_POST['search']);

		// Search users
		$users = get_users(array(
			'search' => "*{$search}*",
			'search_columns' => array('user_login', 'user_email', 'display_name'),
			'number' => 10,
			'fields' => array('ID', 'display_name', 'user_email')
		));

		$results = array_map(function($user) {
			return array(
				'ID' => $user->ID,
				'display_name' => $user->display_name,
				'user_email' => $user->user_email
			);
		}, $users);

		wp_send_json_success($results);
	}

	/**
	 * Handle AJAX add customer
	 */
	public function add_customer_ajax() {
		// Verify nonce
		if (!check_ajax_referer('user_search_nonce', 'nonce', false)) {
			wp_send_json_error('Invalid nonce');
		}

		// Verify user can add customers
		if (!current_user_can('read')) {
			wp_send_json_error('Unauthorized');
		}

		$user_id = intval($_POST['user_id']);
		$team_member_id = get_current_user_id();
		
		// Validate user exists
		if (!get_userdata($user_id)) {
			wp_send_json_error('Invalid user');
		}
		
		global $wpdb;
		$table_name = $wpdb->prefix . 'pr_customers';
		
		// Check if table exists
		$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
		
		if (!$table_exists) {
			// Create the table if it doesn't exist
			require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-product-recommendations-db.php';
			Product_Recommendations_DB::create_tables();
			
			// Check again if table was created
			$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
			
			if (!$table_exists) {
				wp_send_json_error('Database table could not be created');
				return;
			}
		}
		
		// Check if relationship already exists
		$existing = $wpdb->get_var($wpdb->prepare(
			"SELECT id FROM $table_name WHERE user_id = %d AND team_member_id = %d",
			$user_id,
			$team_member_id
		));
		
		if ($existing) {
			wp_send_json_error('This customer is already in your list');
		}
		
		// Insert new customer relationship
		$result = $wpdb->insert(
			$table_name,
			array(
				'user_id' => $user_id,
				'team_member_id' => $team_member_id,
				'date_added' => current_time('mysql'),
				'status' => 'active'
			),
			array('%d', '%d', '%s', '%s')
		);
		
		if ($result === false) {
			// Get the database error
			$db_error = $wpdb->last_error;
			wp_send_json_error('Database error: ' . $db_error);
		} else {
			wp_send_json_success(array(
				'message' => 'Customer added successfully!',
				'redirect_url' => wc_get_account_endpoint_url('product-recommendations/customers')
			));
		}
	}

	/**
	 * Handle AJAX product search
	 */
	public function search_products_ajax() {
		// Verify nonce
		if (!check_ajax_referer('product_search_nonce', 'nonce', false)) {
			wp_send_json_error('Invalid nonce');
		}
		
		// Verify user can search
		if (!current_user_can('read')) {
			wp_send_json_error('Unauthorized');
		}
		
		$search = sanitize_text_field($_POST['search']);
		
		// Search products
		$args = array(
			'post_type' => 'product',
			'post_status' => 'publish',
			's' => $search,
			'posts_per_page' => 10
		);
		
		$products_query = new WP_Query($args);
		$products = array();
		
		if ($products_query->have_posts()) {
			while ($products_query->have_posts()) {
				$products_query->the_post();
				$product = wc_get_product(get_the_ID());
				
				if (!$product) continue;
				
				$products[] = array(
					'id' => $product->get_id(),
					'name' => $product->get_name(),
					'price' => $product->get_price(),
					'price_html' => $product->get_price_html(),
					'image' => wp_get_attachment_image_url($product->get_image_id(), 'thumbnail') ?: wc_placeholder_img_src('thumbnail')
				);
			}
		}
		
		wp_reset_postdata();
		wp_send_json_success($products);
	}

	/**
	 * Handle AJAX add recommendation
	 */
	public function add_recommendation_ajax() {
		// Verify nonce
		if (!check_ajax_referer('product_search_nonce', 'nonce', false)) {
			wp_send_json_error('Invalid nonce');
		}
		
		// Verify user can add recommendations
		if (!current_user_can('read')) {
			wp_send_json_error('Unauthorized');
		}
		
		$customer_id = intval($_POST['customer_id']);
		$product_id = intval($_POST['product_id']);
		$notes = sanitize_textarea_field($_POST['notes']);
		$team_member_id = get_current_user_id();
		
		// Validate customer exists and belongs to this team member
		global $wpdb;
		$customer_table = $wpdb->prefix . 'pr_customers';
		
		$customer = $wpdb->get_row($wpdb->prepare(
			"SELECT * FROM $customer_table WHERE id = %d AND team_member_id = %d",
			$customer_id,
			$team_member_id
		));
		
		if (!$customer) {
			wp_send_json_error('Invalid customer');
		}
		
		// Validate product exists
		$product = wc_get_product($product_id);
		if (!$product) {
			wp_send_json_error('Invalid product');
		}
		
		// Check if recommendation already exists
		$recommendations_table = $wpdb->prefix . 'pr_recommendations';
		
		$existing = $wpdb->get_var($wpdb->prepare(
			"SELECT id FROM $recommendations_table WHERE customer_id = %d AND product_id = %d",
			$customer_id,
			$product_id
		));
		
		if ($existing) {
			wp_send_json_error('This product is already recommended for this customer');
		}
		
		// Insert new recommendation
		$result = $wpdb->insert(
			$recommendations_table,
			array(
				'customer_id' => $customer_id,
				'team_member_id' => $team_member_id,
				'product_id' => $product_id,
				'date_created' => current_time('mysql'),
				'status' => 'pending',
				'notes' => $notes
			),
			array('%d', '%d', '%d', '%s', '%s', '%s')
		);
		
		if ($result === false) {
			$db_error = $wpdb->last_error;
			wp_send_json_error('Database error: ' . $db_error);
		} else {
			$recommendation_id = $wpdb->insert_id;
			
			wp_send_json_success(array(
				'message' => 'Recommendation added successfully!',
				'recommendation' => array(
					'id' => $recommendation_id,
					'product_url' => get_permalink($product_id),
					'date_formatted' => date_i18n(get_option('date_format')),
					'status' => 'pending',
					'status_label' => 'Pending'
				)
			));
		}
	}

	/**
	 * Handle AJAX remove recommendation
	 */
	public function remove_recommendation_ajax() {
		// Verify nonce
		if (!check_ajax_referer('product_search_nonce', 'nonce', false)) {
			wp_send_json_error('Invalid nonce');
		}
		
		// Verify user can remove recommendations
		if (!current_user_can('read')) {
			wp_send_json_error('Unauthorized');
		}
		
		$recommendation_id = intval($_POST['recommendation_id']);
		$team_member_id = get_current_user_id();
		
		// Validate recommendation exists and belongs to this team member
		global $wpdb;
		$recommendations_table = $wpdb->prefix . 'pr_recommendations';
		
		$recommendation = $wpdb->get_row($wpdb->prepare(
			"SELECT * FROM $recommendations_table WHERE id = %d AND team_member_id = %d",
			$recommendation_id,
			$team_member_id
		));
		
		if (!$recommendation) {
			wp_send_json_error('Invalid recommendation');
		}
		
		// Delete the recommendation
		$result = $wpdb->delete(
			$recommendations_table,
			array('id' => $recommendation_id),
			array('%d')
		);
		
		if ($result === false) {
			$db_error = $wpdb->last_error;
			wp_send_json_error('Database error: ' . $db_error);
		} else {
			wp_send_json_success(array(
				'message' => 'Recommendation removed successfully!'
			));
		}
	}
}
