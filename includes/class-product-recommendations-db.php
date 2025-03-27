<?php
/**
 * Database setup and management for Product Recommendations
 *
 * @package    Product_Recommendations
 * @subpackage Product_Recommendations/includes
 */

class Product_Recommendations_DB {

    /**
     * Create database tables
     */
    public static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Customers table
        $table_name = $wpdb->prefix . 'pr_customers';
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            team_member_id bigint(20) NOT NULL,
            date_added datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            status varchar(20) DEFAULT 'active' NOT NULL,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY team_member_id (team_member_id)
        ) $charset_collate;";
        
        // Rooms table
        $table_name_rooms = $wpdb->prefix . 'pr_rooms';
        $sql .= "CREATE TABLE $table_name_rooms (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            customer_id bigint(20) NOT NULL,
            name varchar(255) NOT NULL,
            date_created datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            KEY customer_id (customer_id)
        ) $charset_collate;";
        
        // Recommendations table
        $table_name_recommendations = $wpdb->prefix . 'pr_recommendations';
        $sql .= "CREATE TABLE $table_name_recommendations (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            customer_id bigint(20) NOT NULL,
            team_member_id bigint(20) NOT NULL,
            product_id bigint(20) NOT NULL,
            date_created datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            status varchar(20) DEFAULT 'pending' NOT NULL,
            notes text,
            room_id bigint(20) DEFAULT NULL,
            quantity int(11) DEFAULT 1 NOT NULL,
            PRIMARY KEY  (id),
            KEY customer_id (customer_id),
            KEY team_member_id (team_member_id),
            KEY product_id (product_id),
            KEY room_id (room_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Update database tables
     */
    public static function update_tables() {
        global $wpdb;
        
        // Check if quantity column exists in recommendations table
        $table_name = $wpdb->prefix . 'pr_recommendations';
        $column_exists = $wpdb->get_results("SHOW COLUMNS FROM {$table_name} LIKE 'quantity'");
        
        // Add quantity column if it doesn't exist
        if (empty($column_exists)) {
            $wpdb->query("ALTER TABLE {$table_name} ADD COLUMN quantity int(11) DEFAULT 1 NOT NULL AFTER room_id");
        }
        
        // Check if position column exists in recommendations table
        $position_exists = $wpdb->get_results("SHOW COLUMNS FROM {$table_name} LIKE 'position'");
        
        // Add position column if it doesn't exist
        if (empty($position_exists)) {
            $wpdb->query("ALTER TABLE {$table_name} ADD COLUMN position int(11) DEFAULT 0 NOT NULL AFTER quantity");
            error_log('Added position column to recommendations table');
        } else {
            error_log('Position column already exists in recommendations table');
        }
        
        // Create email log table if it doesn't exist
        $table_name_email_log = $wpdb->prefix . 'pr_email_log';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_name_email_log}'");
        
        if (!$table_exists) {
            $charset_collate = $wpdb->get_charset_collate();
            
            $sql = "CREATE TABLE {$table_name_email_log} (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                customer_id bigint(20) NOT NULL,
                team_member_id bigint(20) NOT NULL,
                date_sent datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
                subject varchar(255) NOT NULL,
                PRIMARY KEY  (id),
                KEY customer_id (customer_id),
                KEY team_member_id (team_member_id)
            ) {$charset_collate};";
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        }
    }

    /**
     * Initialize position values for existing recommendations
     */
    public static function initialize_positions() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'pr_recommendations';
        
        // Check if position column exists
        $position_exists = $wpdb->get_results("SHOW COLUMNS FROM {$table_name} LIKE 'position'");
        
        if (empty($position_exists)) {
            // Add position column if it doesn't exist
            self::update_tables();
        }
        
        // Get all unique combinations of customer_id, team_member_id, and room_id
        $groups = $wpdb->get_results("
            SELECT DISTINCT customer_id, team_member_id, room_id 
            FROM {$table_name}
            ORDER BY customer_id, team_member_id, room_id
        ");
        
        foreach ($groups as $group) {
            // Get recommendations for this group
            $recommendations = $wpdb->get_results($wpdb->prepare(
                "SELECT id FROM {$table_name} 
                 WHERE customer_id = %d AND team_member_id = %d AND (room_id = %d OR (room_id IS NULL AND %d IS NULL))
                 ORDER BY date_created ASC",
                $group->customer_id,
                $group->team_member_id,
                $group->room_id,
                $group->room_id
            ));
            
            // Update position values
            foreach ($recommendations as $index => $recommendation) {
                $wpdb->update(
                    $table_name,
                    array('position' => $index),
                    array('id' => $recommendation->id),
                    array('%d'),
                    array('%d')
                );
            }
        }
        
        error_log('Initialized position values for all recommendations');
    }
} 