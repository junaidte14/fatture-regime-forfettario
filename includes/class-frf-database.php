<?php
/**
 * Database Management Class
 * Handles database schema creation and updates
 */

if (!defined('ABSPATH')) {
    exit;
}

class FRF_Database {
    
    /**
     * Create database tables
     */
    public static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Clients table
        $table_clients = $wpdb->prefix . 'frf_clients';
        $sql_clients = "CREATE TABLE IF NOT EXISTS $table_clients (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            business_name varchar(255) NOT NULL,
            vat_number varchar(50) DEFAULT NULL,
            tax_code varchar(50) DEFAULT NULL,
            email varchar(100) DEFAULT NULL,
            pec_email varchar(100) DEFAULT NULL,
            phone varchar(50) DEFAULT NULL,
            address text DEFAULT NULL,
            city varchar(100) DEFAULT NULL,
            province varchar(10) DEFAULT NULL,
            postal_code varchar(20) DEFAULT NULL,
            country varchar(2) DEFAULT 'IT',
            client_type enum('IT','EU','NON_EU') DEFAULT 'IT',
            sdi_code varchar(7) DEFAULT NULL,
            woo_store_id bigint(20) DEFAULT NULL,
            woo_customer_id bigint(20) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY client_type (client_type),
            KEY vat_number (vat_number),
            KEY woo_store_id (woo_store_id)
        ) $charset_collate;";
        
        // Invoices table
        $table_invoices = $wpdb->prefix . 'frf_invoices';
        $sql_invoices = "CREATE TABLE IF NOT EXISTS $table_invoices (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            invoice_number varchar(50) NOT NULL,
            invoice_date date NOT NULL,
            client_id bigint(20) NOT NULL,
            subtotal decimal(10,2) NOT NULL DEFAULT 0.00,
            tax_rate decimal(5,2) DEFAULT NULL,
            tax_amount decimal(10,2) DEFAULT 0.00,
            total decimal(10,2) NOT NULL DEFAULT 0.00,
            withholding_tax decimal(5,2) DEFAULT NULL,
            withholding_amount decimal(10,2) DEFAULT 0.00,
            net_to_pay decimal(10,2) NOT NULL DEFAULT 0.00,
            payment_terms varchar(100) DEFAULT NULL,
            payment_method varchar(50) DEFAULT NULL,
            notes text DEFAULT NULL,
            status enum('draft','sent','paid','overdue','cancelled','submitted','accepted','rejected') DEFAULT 'draft',
            paid_date date DEFAULT NULL,
            submission_date datetime DEFAULT NULL,
            sdi_identifier varchar(50) DEFAULT NULL,
            xml_file_path varchar(255) DEFAULT NULL,
            woo_order_id bigint(20) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY invoice_number (invoice_number),
            KEY client_id (client_id),
            KEY invoice_date (invoice_date),
            KEY status (status),
            KEY woo_order_id (woo_order_id),
            CONSTRAINT fk_client FOREIGN KEY (client_id) REFERENCES $table_clients(id) ON DELETE RESTRICT
        ) $charset_collate;";
        
        // Invoice items table
        $table_items = $wpdb->prefix . 'frf_invoice_items';
        $sql_items = "CREATE TABLE IF NOT EXISTS $table_items (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            invoice_id bigint(20) NOT NULL,
            description text NOT NULL,
            quantity decimal(10,2) NOT NULL DEFAULT 1.00,
            unit_price decimal(10,2) NOT NULL DEFAULT 0.00,
            total decimal(10,2) NOT NULL DEFAULT 0.00,
            sort_order int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY invoice_id (invoice_id),
            CONSTRAINT fk_invoice FOREIGN KEY (invoice_id) REFERENCES $table_invoices(id) ON DELETE CASCADE
        ) $charset_collate;";
        
        // Status history table
        $table_history = $wpdb->prefix . 'frf_status_history';
        $sql_history = "CREATE TABLE IF NOT EXISTS $table_history (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            invoice_id bigint(20) NOT NULL,
            old_status varchar(50) DEFAULT NULL,
            new_status varchar(50) NOT NULL,
            notes text DEFAULT NULL,
            changed_by bigint(20) DEFAULT NULL,
            changed_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY invoice_id (invoice_id),
            KEY changed_at (changed_at),
            CONSTRAINT fk_invoice_history FOREIGN KEY (invoice_id) REFERENCES $table_invoices(id) ON DELETE CASCADE
        ) $charset_collate;";

        /**
         * WooCommerce Stores table
         */
        $table_stores = $wpdb->prefix . 'frf_woo_stores';
        $sql_stores = "CREATE TABLE IF NOT EXISTS $table_stores (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            store_name varchar(200) NOT NULL,
            store_url varchar(255) NOT NULL,
            consumer_key varchar(255) NOT NULL,
            consumer_secret varchar(255) NOT NULL,
            sync_from_date date DEFAULT NULL,
            auto_sync tinyint(1) DEFAULT 0,
            sync_interval int(11) DEFAULT 60,
            status enum('active','inactive','error') DEFAULT 'active',
            last_sync_at datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY status (status),
            KEY auto_sync (auto_sync)
        ) $charset_collate;";

        /**
         * WooCommerce Orders table
         */
        $table_woo_orders = $wpdb->prefix . 'frf_woo_orders';
        $sql_woo_orders = "CREATE TABLE IF NOT EXISTS $table_woo_orders (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            store_id bigint(20) NOT NULL,
            woo_order_id bigint(20) NOT NULL,
            order_number varchar(50) NOT NULL,
            order_date datetime NOT NULL,
            status varchar(50) NOT NULL,
            customer_data longtext NOT NULL,
            items_data longtext NOT NULL,
            subtotal decimal(10,2) NOT NULL DEFAULT 0.00,
            tax decimal(10,2) NOT NULL DEFAULT 0.00,
            total decimal(10,2) NOT NULL DEFAULT 0.00,
            currency varchar(10) DEFAULT 'EUR',
            payment_method varchar(100) DEFAULT NULL,
            invoice_id bigint(20) DEFAULT NULL,
            raw_data longtext DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY store_order (store_id, woo_order_id),
            KEY store_id (store_id),
            KEY invoice_id (invoice_id),
            KEY order_date (order_date),
            KEY status (status),
            CONSTRAINT fk_woo_store FOREIGN KEY (store_id) REFERENCES $table_stores(id) ON DELETE CASCADE
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        dbDelta($sql_clients);
        dbDelta($sql_invoices);
        dbDelta($sql_items);
        dbDelta($sql_history);
        dbDelta($sql_stores);
        dbDelta($sql_woo_orders);

        // Add WooCommerce fields to existing tables (if they don't exist)
        // Check and add columns to clients table
        $clients_columns = $wpdb->get_col("DESCRIBE {$table_clients}", 0);
        
        if (!in_array('woo_store_id', $clients_columns)) {
            $wpdb->query("ALTER TABLE {$table_clients} ADD COLUMN woo_store_id bigint(20) DEFAULT NULL AFTER sdi_code");
        }
        
        if (!in_array('woo_customer_id', $clients_columns)) {
            $wpdb->query("ALTER TABLE {$table_clients} ADD COLUMN woo_customer_id bigint(20) DEFAULT NULL AFTER woo_store_id");
        }
        
        // Check if index exists before creating it
        $clients_indexes = $wpdb->get_results("SHOW INDEX FROM {$table_clients} WHERE Key_name = 'woo_store_id'");
        if (empty($clients_indexes)) {
            $wpdb->query("ALTER TABLE {$table_clients} ADD INDEX woo_store_id (woo_store_id)");
        }

        // Check and add column to invoices table  
        $invoices_columns = $wpdb->get_col("DESCRIBE {$table_invoices}", 0);
        
        if (!in_array('woo_order_id', $invoices_columns)) {
            $wpdb->query("ALTER TABLE {$table_invoices} ADD COLUMN woo_order_id bigint(20) DEFAULT NULL AFTER xml_file_path");
        }
        
        // Check if index exists before creating it
        $invoices_indexes = $wpdb->get_results("SHOW INDEX FROM {$table_invoices} WHERE Key_name = 'woo_order_id'");
        if (empty($invoices_indexes)) {
            $wpdb->query("ALTER TABLE {$table_invoices} ADD INDEX woo_order_id (woo_order_id)");
        }
        
        // Save database version
        update_option('frf_db_version', FRF_VERSION);
    }

    /**
     * Get table name with prefix
     */
    public static function get_table_name($table) {
        global $wpdb;
        return $wpdb->prefix . 'frf_' . $table;
    }
}