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
		add_action('wp_ajax_add_room', array($this, 'add_room_ajax'));
		add_action('wp_ajax_edit_room', array($this, 'edit_room_ajax'));
		add_action('wp_ajax_delete_room', array($this, 'delete_room_ajax'));
		add_action('wp_ajax_get_customer_rooms', array($this, 'get_customer_rooms_ajax'));

		// Add customer recommendations endpoint
		add_action('init', array($this, 'add_customer_recommendations_endpoint'));
		
		// Flush rewrite rules if needed
		if (get_option('product_recommendations_flush_rewrite_rules')) {
			flush_rewrite_rules();
			delete_option('product_recommendations_flush_rewrite_rules');
		}

		// Add custom AJAX handler for adding products to cart
		add_action('wp_ajax_add_to_cart_custom', array($this, 'add_to_cart_custom_ajax'));
		add_action('wp_ajax_nopriv_add_to_cart_custom', array($this, 'add_to_cart_custom_ajax'));
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
					'general_recommendations' => __('General Recommendations', 'product-recommendations'),
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

		// Add to product-recommendations.js
		wp_add_inline_script(
			$this->plugin_name . '-product-recommendations',
			"$('#add-room-btn').on('click', function(e) {
				e.preventDefault();
				const roomName = prompt('Enter room name:');
				if (!roomName) return;
				
				$.ajax({
					url: pr_product_object.ajax_url,
					type: 'POST',
					data: {
						action: 'add_room',
						nonce: pr_product_object.nonce,
						room_name: roomName
					},
					success: function(response) {
						if (response.success) {
							$('#recommendation_room').append(
								$('<option>', {
									value: response.data.id,
									text: response.data.name
								})
							);
						}
					}
				});
			});"
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
		add_rewrite_endpoint('product-recommendations/customers/add', EP_ROOT | EP_PAGES);
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
		$room_id = !empty($_POST['room_id']) ? intval($_POST['room_id']) : null;
		$team_member_id = get_current_user_id();
		
		// Debug output
		error_log('Adding recommendation with room_id: ' . var_export($room_id, true));
		
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
		
		// Check if this product is already recommended for this customer in the same room
		$recommendations_table = $wpdb->prefix . 'pr_recommendations';
		
		$existing = $wpdb->get_var($wpdb->prepare(
			"SELECT id FROM $recommendations_table 
			 WHERE customer_id = %d 
			 AND product_id = %d 
			 AND (room_id = %d OR (room_id IS NULL AND %d IS NULL))",
			$customer_id,
			$product_id,
			$room_id,
			$room_id
		));
		
		if ($existing) {
			wp_send_json_error('This product is already recommended for this customer in this room');
		}
		
		// If room_id is provided, verify it belongs to this customer
		if ($room_id) {
			$room = $wpdb->get_row($wpdb->prepare(
				"SELECT id FROM {$wpdb->prefix}pr_rooms 
				 WHERE id = %d AND customer_id = %d",
				$room_id,
				$customer_id
			));
			
			if (!$room) {
				wp_send_json_error('Invalid room');
			}
		}
		
		// Insert new recommendation
		$data = array(
			'customer_id' => $customer_id,
			'team_member_id' => $team_member_id,
			'product_id' => $product_id,
			'date_created' => current_time('mysql'),
			'status' => 'pending',
			'notes' => $notes
		);
		
		$formats = array(
			'%d',  // customer_id
			'%d',  // team_member_id
			'%d',  // product_id
			'%s',  // date_created
			'%s',  // status
			'%s'   // notes
		);
		
		// Only add room_id if it's not empty
		if (!empty($room_id)) {
			$data['room_id'] = $room_id;
			$formats[] = '%d';  // room_id
		}
		
		$result = $wpdb->insert(
			$recommendations_table,
			$data,
			$formats
		);
		
		if ($result === false) {
			wp_send_json_error('Failed to add recommendation: ' . $wpdb->last_error);
		} else {
			// Verify the insertion
			$inserted = $wpdb->get_row($wpdb->prepare(
				"SELECT * FROM $recommendations_table WHERE id = %d",
				$wpdb->insert_id
			));
			error_log('Inserted recommendation: ' . print_r($inserted, true));
			
			// Get the product details for the response
			$product = wc_get_product($product_id);
			
			// Get room name if room_id is provided
			$room_name = '';
			if ($room_id) {
				$room_name = $wpdb->get_var($wpdb->prepare(
					"SELECT name FROM {$wpdb->prefix}pr_rooms WHERE id = %d",
					$room_id
				));
			}

			// Debug log
			error_log('Room ID: ' . $room_id);
			error_log('Room Name: ' . $room_name);

			wp_send_json_success(array(
				'message' => 'Recommendation added successfully!',
				'recommendation' => array(
					'id' => $wpdb->insert_id,
					'product_name' => $product->get_name(),
					'product_image' => wp_get_attachment_image_url($product->get_image_id(), 'thumbnail'),
					'date_created' => date_i18n(get_option('date_format')),
					'status' => 'pending',
					'notes' => $notes,
					'room_id' => $room_id,
					'room_name' => $room_name
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

	/**
	 * Handle AJAX add room
	 */
	public function add_room_ajax() {
		// Verify nonce
		if (!check_ajax_referer('product_search_nonce', 'nonce', false)) {
			wp_send_json_error('Invalid nonce');
		}
		
		// Verify user can add rooms
		if (!current_user_can('read')) {
			wp_send_json_error('Unauthorized');
		}
		
		global $wpdb;
		
		// Check if tables exist, create them if they don't
		$table_name = $wpdb->prefix . 'pr_rooms';
		if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
			require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-product-recommendations-db.php';
			Product_Recommendations_DB::create_tables();
		}
		
		$room_name = sanitize_text_field($_POST['room_name']);
		$customer_id = intval($_POST['customer_id']);
		$team_member_id = get_current_user_id();
		
		if (empty($room_name)) {
			wp_send_json_error('Room name is required');
		}
		
		// Verify customer belongs to team member
		$customer_table = $wpdb->prefix . 'pr_customers';
		$customer = $wpdb->get_row($wpdb->prepare(
			"SELECT * FROM $customer_table WHERE id = %d AND team_member_id = %d",
			$customer_id,
			$team_member_id
		));
		
		if (!$customer) {
			wp_send_json_error('Invalid customer');
		}
		
		// Insert new room
		$result = $wpdb->insert(
			$table_name,
			array(
				'customer_id' => $customer_id,
				'name' => $room_name,
				'date_created' => current_time('mysql')
			),
			array('%d', '%s', '%s')
		);
		
		if ($result === false) {
			wp_send_json_error('Failed to create room: ' . $wpdb->last_error);
		} else {
			wp_send_json_success(array(
				'id' => $wpdb->insert_id,
				'name' => $room_name
			));
		}
	}

	/**
	 * Handle AJAX edit room
	 */
	public function edit_room_ajax() {
		// Verify nonce
		if (!check_ajax_referer('product_search_nonce', 'nonce', false)) {
			wp_send_json_error('Invalid nonce');
		}
		
		// Verify user can edit rooms
		if (!current_user_can('read')) {
			wp_send_json_error('Unauthorized');
		}
		
		$room_id = intval($_POST['room_id']);
		$room_name = sanitize_text_field($_POST['room_name']);
		$team_member_id = get_current_user_id();
		
		if (empty($room_name)) {
			wp_send_json_error('Room name is required');
		}
		
		global $wpdb;
		$table_name = $wpdb->prefix . 'pr_rooms';
		
		// Verify room belongs to a customer that belongs to this team member
		$is_authorized = $wpdb->get_var($wpdb->prepare(
			"SELECT r.id 
			 FROM {$wpdb->prefix}pr_rooms r
			 JOIN {$wpdb->prefix}pr_customers c ON r.customer_id = c.id
			 WHERE r.id = %d AND c.team_member_id = %d",
			$room_id,
			$team_member_id
		));
		
		if (!$is_authorized) {
			wp_send_json_error('Unauthorized to edit this room');
		}
		
		// Update room
		$result = $wpdb->update(
			$table_name,
			array('name' => $room_name),
			array('id' => $room_id),
			array('%s'),
			array('%d')
		);
		
		if ($result === false) {
			wp_send_json_error('Failed to update room');
		} else {
			wp_send_json_success();
		}
	}

	/**
	 * Handle AJAX delete room
	 */
	public function delete_room_ajax() {
		// Verify nonce
		if (!check_ajax_referer('product_search_nonce', 'nonce', false)) {
			wp_send_json_error('Invalid nonce');
		}
		
		// Verify user can delete rooms
		if (!current_user_can('read')) {
			wp_send_json_error('Unauthorized');
		}
		
		$room_id = intval($_POST['room_id']);
		$team_member_id = get_current_user_id();
		
		global $wpdb;
		$table_name = $wpdb->prefix . 'pr_rooms';
		
		// Verify room belongs to a customer that belongs to this team member
		$is_authorized = $wpdb->get_var($wpdb->prepare(
			"SELECT r.id 
			 FROM {$wpdb->prefix}pr_rooms r
			 JOIN {$wpdb->prefix}pr_customers c ON r.customer_id = c.id
			 WHERE r.id = %d AND c.team_member_id = %d",
			$room_id,
			$team_member_id
		));
		
		if (!$is_authorized) {
			wp_send_json_error('Unauthorized to delete this room');
		}
		
		// Delete room
		$result = $wpdb->delete(
			$table_name,
			array('id' => $room_id),
			array('%d')
		);
		
		if ($result === false) {
			wp_send_json_error('Failed to delete room');
		} else {
			// Update recommendations to remove room_id
			$wpdb->update(
				$wpdb->prefix . 'pr_recommendations',
				array('room_id' => null),
				array('room_id' => $room_id),
				array('%d'),
				array('%d')
			);
			
			wp_send_json_success();
		}
	}

	/**
	 * Handle AJAX get customer rooms
	 */
	public function get_customer_rooms_ajax() {
		// Verify nonce
		if (!check_ajax_referer('product_search_nonce', 'nonce', false)) {
			wp_send_json_error('Invalid nonce');
		}
		
		// Verify user can view rooms
		if (!current_user_can('read')) {
			wp_send_json_error('Unauthorized');
		}
		
		$customer_id = intval($_POST['customer_id']);
		$team_member_id = get_current_user_id();
		
		// Verify customer belongs to team member
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
		
		// Get rooms for customer
		$rooms = $wpdb->get_results($wpdb->prepare(
			"SELECT id, name FROM {$wpdb->prefix}pr_rooms WHERE customer_id = %d ORDER BY name ASC",
			$customer_id
		));
		
		wp_send_json_success($rooms);
	}

	/**
	 * Handle recommendations display
	 */
	public function handle_recommendations_endpoint() {
		global $wpdb;
		$customer_id = get_query_var('customer_id');
		
		// Get customer details
		$customer_table = $wpdb->prefix . 'pr_customers';
		$customer = $wpdb->get_row($wpdb->prepare(
			"SELECT c.*, u.display_name, u.user_email 
			 FROM $customer_table c
			 JOIN {$wpdb->users} u ON c.user_id = u.ID
			 WHERE c.id = %d AND c.team_member_id = %d",
			$customer_id,
			get_current_user_id()
		));
		
		if (!$customer) {
			wp_die(__('Invalid customer', 'product-recommendations'));
		}
		
		// Get all recommendations for this customer with room names
		$recommendations_table = $wpdb->prefix . 'pr_recommendations';
		
		// Modified query to ensure we're getting the correct room data
		$query = $wpdb->prepare(
			"SELECT r.*, p.post_title as product_name, r.room_id, rm.name as room_name
			 FROM {$wpdb->prefix}pr_recommendations r
			 LEFT JOIN {$wpdb->posts} p ON r.product_id = p.ID
			 LEFT JOIN {$wpdb->prefix}pr_rooms rm ON r.room_id = rm.id
			 WHERE r.customer_id = %d
			 ORDER BY COALESCE(r.room_id, 0), r.date_created DESC",
			$customer_id
		);
		
		$recommendations = $wpdb->get_results($query);
		
		// Debug output
		error_log('Recommendations Query: ' . $query);
		error_log('Number of recommendations found: ' . count($recommendations));
		error_log('Raw recommendations data: ' . print_r($recommendations, true));
		
		// Include the template
		include plugin_dir_path(__FILE__) . 'partials/manage-recommendations.php';
	}

	/**
	 * Register customer recommendations endpoint
	 */
	public function add_customer_recommendations_endpoint() {
		add_rewrite_endpoint('my-recommendations', EP_ROOT | EP_PAGES);
		
		// Add to My Account menu
		add_filter('woocommerce_account_menu_items', function($items) {
			$new_items = array();
			
			// Insert after Dashboard
			foreach ($items as $key => $value) {
				$new_items[$key] = $value;
				if ($key === 'dashboard') {
					$new_items['my-recommendations'] = __('My Recommendations', 'product-recommendations');
				}
			}
			
			return $new_items;
		});
		
		// Add endpoint content
		add_action('woocommerce_account_my-recommendations_endpoint', array($this, 'my_recommendations_content'));
	}

	/**
	 * Display customer recommendations content
	 */
	public function my_recommendations_content() {
		global $wpdb;
		$user_id = get_current_user_id();
		
		// Get customer ID for this user
		$customer_id = $wpdb->get_var($wpdb->prepare(
			"SELECT id FROM {$wpdb->prefix}pr_customers WHERE user_id = %d",
			$user_id
		));
		
		if (!$customer_id) {
			echo '<p>' . __('No recommendations found.', 'product-recommendations') . '</p>';
			return;
		}
		
		// Get recommendations for this customer
		$recommendations = $wpdb->get_results($wpdb->prepare(
			"SELECT r.*, p.post_title as product_name, rm.name as room_name
			 FROM {$wpdb->prefix}pr_recommendations r
			 LEFT JOIN {$wpdb->posts} p ON r.product_id = p.ID
			 LEFT JOIN {$wpdb->prefix}pr_rooms rm ON r.room_id = rm.id
			 WHERE r.customer_id = %d
			 ORDER BY COALESCE(r.room_id, 0), r.date_created DESC",
			$customer_id
		));
		
		// Include the template
		include plugin_dir_path(__FILE__) . 'partials/customer-recommendations.php';
	}

	/**
	 * Custom AJAX handler for adding products to cart
	 */
	public function add_to_cart_custom_ajax() {
		check_ajax_referer('wc_store_api', 'nonce');
		
		$product_id = isset($_POST['product_id']) ? absint($_POST['product_id']) : 0;
		$quantity = isset($_POST['quantity']) ? absint($_POST['quantity']) : 1;
		
		if ($product_id > 0) {
			$added = WC()->cart->add_to_cart($product_id, $quantity);
			if ($added) {
				wp_send_json_success();
			} else {
				wp_send_json_error();
			}
		} else {
			wp_send_json_error();
		}
		
		wp_die();
	}
}
