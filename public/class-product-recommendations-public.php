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

		// Register new AJAX handlers
		$this->register_ajax_handlers();
	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 */
	public function enqueue_styles() {
		// Debug
		error_log('Enqueuing styles for Product Recommendations');
		
		// Load Font Awesome
		wp_enqueue_style(
			$this->plugin_name . '-fontawesome',
			'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css',
			array(),
			'5.15.4'
		);

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
					'general_recommendations' => __('Core Recommendations', 'product-recommendations'),
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
	 * AJAX handler for product search
	 */
	public function search_products_ajax() {
		check_ajax_referer('product_search_nonce', 'nonce');
		
		$search_term = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
		$placeholder_image = 'http://shieldingshop.local/wp-content/uploads/2023/09/product-placeholder.png';
		
		if (empty($search_term)) {
			wp_send_json_error('Search term is required');
			return;
		}
		
		// Check if current user is a team member
		$current_user_id = get_current_user_id();
		$is_team_member = current_user_can('manage_options') || current_user_can('edit_shop_orders');
		
		// Set up the query arguments
		$args = array(
			'post_type'      => 'product',
			'posts_per_page' => 10,
			's'              => $search_term,
			'orderby'        => 'title',
			'order'          => 'ASC',
		);
		
		// Include private products if user is a team member
		if ($is_team_member) {
			$args['post_status'] = array('publish', 'private');
		} else {
			$args['post_status'] = 'publish';
		}
		
		$products_query = new WP_Query($args);
		$products = array();
		
		if ($products_query->have_posts()) {
			while ($products_query->have_posts()) {
				$products_query->the_post();
				$product_id = get_the_ID();
				$product = wc_get_product($product_id);
				
				if ($product) {
					$image_id = $product->get_image_id();
					$image_url = $image_id ? wp_get_attachment_image_url($image_id, 'thumbnail') : $placeholder_image;
					
					$products[] = array(
						'id'        => $product_id,
						'name'      => $product->get_name(),
						'price'     => $product->get_price(),
						'price_html' => $product->get_price_html(),
						'image'     => $image_url,
						'status'    => $product->get_status(),
						'is_private' => $product->get_status() === 'private',
						'is_variable' => $product->is_type('variable')
					);
				}
			}
			wp_reset_postdata();
		}
		
		wp_send_json_success($products);
	}

	/**
	 * AJAX handler for adding a recommendation
	 */
	public function add_recommendation_ajax() {
		check_ajax_referer('product_search_nonce', 'nonce');
		
		$customer_id = isset($_POST['customer_id']) ? intval($_POST['customer_id']) : 0;
		$product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
		$notes = isset($_POST['notes']) ? sanitize_textarea_field($_POST['notes']) : '';
		$room_id = isset($_POST['room_id']) && !empty($_POST['room_id']) ? intval($_POST['room_id']) : null;
		$quantity = isset($_POST['quantity']) ? max(1, intval($_POST['quantity'])) : 1;
		
		if (!$customer_id || !$product_id) {
			wp_send_json_error('Missing required fields');
			return;
		}
		
		// Check if user has permission to add recommendation for this customer
		global $wpdb;
		$team_member_id = get_current_user_id();
		
		$customer_exists = $wpdb->get_var($wpdb->prepare(
			"SELECT id FROM {$wpdb->prefix}pr_customers 
			 WHERE id = %d AND team_member_id = %d",
			$customer_id,
			$team_member_id
		));
		
		if (!$customer_exists) {
			wp_send_json_error('You do not have permission to add recommendations for this customer');
			return;
		}
		
		// Prepare data for insertion
		$data = array(
			'customer_id' => $customer_id,
			'team_member_id' => $team_member_id,
			'product_id' => $product_id,
			'date_created' => current_time('mysql'),
			'status' => 'pending',
			'notes' => $notes,
			'quantity' => $quantity
		);
		
		// Format specifiers
		$formats = array('%d', '%d', '%d', '%s', '%s', '%s', '%d');
		
		// Add room_id if it's set
		if ($room_id !== null) {
			$data['room_id'] = $room_id;
			$formats[] = '%d';
		}
		
		// Insert recommendation
		$result = $wpdb->insert(
			$wpdb->prefix . 'pr_recommendations',
			$data,
			$formats
		);
		
		if ($result) {
			$recommendation_id = $wpdb->insert_id;
			
			// Get product details
			$product = wc_get_product($product_id);
			$product_name = $product ? $product->get_name() : 'Product #' . $product_id;
			
			wp_send_json_success(array(
				'message' => sprintf(__('Recommendation for "%s" added successfully', 'product-recommendations'), $product_name),
				'recommendation_id' => $recommendation_id
			));
		} else {
			// Log the error for debugging
			error_log('Failed to add recommendation: ' . $wpdb->last_error);
			wp_send_json_error('Failed to add recommendation: ' . $wpdb->last_error);
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
		global $wp;
		
		// Check if we're viewing a specific team member's recommendations
		if (isset($wp->query_vars['my-recommendations']) && !empty($wp->query_vars['my-recommendations'])) {
			// Set the team member ID for use in the template
			set_query_var('team_member_id', intval($wp->query_vars['my-recommendations']));
		}
		
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

	/**
	 * AJAX handler for getting product variants
	 */
	public function get_product_variants_ajax() {
		check_ajax_referer('product_search_nonce', 'nonce');
		
		$product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
		$placeholder_image = 'http://shieldingshop.local/wp-content/uploads/2023/09/product-placeholder.png';
		
		if (!$product_id) {
			wp_send_json_error('Product ID is required');
			return;
		}
		
		$product = wc_get_product($product_id);
		
		if (!$product || !$product->is_type('variable')) {
			wp_send_json_error('Not a variable product');
			return;
		}
		
		$variations = $product->get_available_variations();
		$variants = array();
		
		foreach ($variations as $variation) {
			$variation_obj = wc_get_product($variation['variation_id']);
			
			if ($variation_obj && $variation_obj->is_purchasable()) {
				$image_id = !empty($variation['image_id']) ? $variation['image_id'] : $product->get_image_id();
				$image_url = $image_id ? wp_get_attachment_image_url($image_id, 'thumbnail') : $placeholder_image;
				
				// Build attribute summary
				$attribute_summary = array();
				foreach ($variation['attributes'] as $key => $value) {
					$taxonomy = str_replace('attribute_', '', $key);
					$term_name = $value;
					
					// If it's a taxonomy attribute, get the term name
					if (taxonomy_exists($taxonomy)) {
						$term = get_term_by('slug', $value, $taxonomy);
						if ($term) {
							$term_name = $term->name;
						}
					}
					
					$attribute_name = wc_attribute_label($taxonomy);
					$attribute_summary[] = $attribute_name . ': ' . $term_name;
				}
				
				$variants[] = array(
					'id'               => $variation['variation_id'],
					'parent_id'        => $product_id,
					'name'             => $variation_obj->get_name(),
					'price'            => $variation_obj->get_price(),
					'price_html'       => $variation_obj->get_price_html(),
					'image'            => $image_url,
					'status'           => $variation_obj->get_status(),
					'is_private'       => $variation_obj->get_status() === 'private',
					'is_variable'      => false,
					'is_variation'     => true,
					'attributes'       => $variation['attributes'],
					'attribute_summary' => implode(', ', $attribute_summary)
				);
			}
		}
		
		wp_send_json_success($variants);
	}

	/**
	 * Register new AJAX handlers
	 */
	public function register_ajax_handlers() {
		// Existing handlers
		add_action('wp_ajax_search_products', array($this, 'search_products_ajax'));
		add_action('wp_ajax_add_recommendation', array($this, 'add_recommendation_ajax'));
		add_action('wp_ajax_remove_recommendation', array($this, 'remove_recommendation_ajax'));
		add_action('wp_ajax_search_users', array($this, 'search_users_ajax'));
		add_action('wp_ajax_add_customer', array($this, 'add_customer_ajax'));
		add_action('wp_ajax_add_room', array($this, 'add_room_ajax'));
		add_action('wp_ajax_edit_room', array($this, 'edit_room_ajax'));
		add_action('wp_ajax_remove_room', array($this, 'delete_room_ajax'));
		add_action('wp_ajax_get_customer_rooms', array($this, 'get_customer_rooms_ajax'));
		add_action('wp_ajax_add_to_cart_custom', array($this, 'add_to_cart_custom_ajax'));
		add_action('wp_ajax_nopriv_add_to_cart_custom', array($this, 'add_to_cart_custom_ajax'));
		
		// New handler for product variants
		add_action('wp_ajax_get_product_variants', array($this, 'get_product_variants_ajax'));
	}
}
