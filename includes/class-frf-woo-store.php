<?php
/**
 * WooCommerce Store Model Class
 * Path: includes/class-frf-woo-store.php
 * Manages external WooCommerce store connections
 */

if (!defined('ABSPATH')) {
    exit;
}

class FRF_WooCommerce_Store {
    
    private $table_name;
    
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'frf_woo_stores';
    }
    
    /**
     * Add new WooCommerce store connection
     */
    public function add_store($data) {
        global $wpdb;
        
        // Validate required fields
        if (empty($data['store_name']) || empty($data['store_url']) || 
            empty($data['consumer_key']) || empty($data['consumer_secret'])) {
            return new WP_Error('invalid_data', 
                __('Nome negozio, URL, Consumer Key e Consumer Secret sono obbligatori', 'fatture-rf'));
        }
        
        // Validate and sanitize store URL
        $store_url = untrailingslashit(esc_url_raw($data['store_url']));
        
        // Test connection before saving
        $test = $this->test_connection(
            $store_url,
            sanitize_text_field($data['consumer_key']),
            sanitize_text_field($data['consumer_secret'])
        );
        
        if (is_wp_error($test)) {
            return $test;
        }
        
        $store_data = array(
            'store_name' => sanitize_text_field($data['store_name']),
            'store_url' => $store_url,
            'consumer_key' => sanitize_text_field($data['consumer_key']),
            'consumer_secret' => sanitize_text_field($data['consumer_secret']),
            'sync_from_date' => (!empty($data['sync_from_date']) && strtotime($data['sync_from_date'])) ? date('Y-m-d', strtotime($data['sync_from_date'])) : date('Y-m-d', strtotime('-30 days')),
            'auto_sync' => isset($data['auto_sync']) ? 1 : 0,
            'sync_interval' => intval($data['sync_interval'] ?? 60),
            'status' => 'active',
            'created_at' => current_time('mysql')
        );
        
        $result = $wpdb->insert($this->table_name, $store_data);
        
        if ($result === false) {
            return new WP_Error('db_error', 
                __('Errore durante il salvataggio del negozio', 'fatture-rf'));
        }
        
        return $wpdb->insert_id;
    }
    
    /**
     * Update store connection
     */
    public function update_store($store_id, $data) {
        global $wpdb;
        
        $store_id = intval($store_id);
        
        if (!$this->exists($store_id)) {
            return new WP_Error('not_found', __('Negozio non trovato', 'fatture-rf'));
        }
        
        $update_data = array();
        
        $fields = ['store_name', 'store_url', 'consumer_key', 'consumer_secret', 
                   'sync_from_date', 'sync_interval', 'status'];
        
        foreach ($fields as $field) {
            if (isset($data[$field])) {
                $update_data[$field] = sanitize_text_field($data[$field]);
            }
        }
        
        if (isset($data['auto_sync'])) {
            $update_data['auto_sync'] = $data['auto_sync'] ? 1 : 0;
        }
        
        if (empty($update_data)) {
            return true;
        }
        
        $update_data['updated_at'] = current_time('mysql');
        
        $result = $wpdb->update(
            $this->table_name,
            $update_data,
            array('id' => $store_id)
        );
        
        return $result !== false;
    }
    
    /**
     * Get store by ID
     */
    public function get_store($store_id) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE id = %d",
            intval($store_id)
        ));
    }
    
    /**
     * Get all stores
     */
    public function get_all_stores($args = array()) {
        global $wpdb;
        
        $defaults = array(
            'status' => '',
            'orderby' => 'store_name',
            'order' => 'ASC'
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $where = array('1=1');
        
        if (!empty($args['status'])) {
            $where[] = $wpdb->prepare('status = %s', sanitize_text_field($args['status']));
        }
        
        $where_clause = implode(' AND ', $where);
        $orderby = sanitize_sql_orderby($args['orderby'] . ' ' . $args['order']);
        
        $query = "SELECT * FROM {$this->table_name} WHERE {$where_clause} ORDER BY {$orderby}";
        
        return $wpdb->get_results($query);
    }
    
    /**
     * Delete store
     */
    public function delete_store($store_id) {
        global $wpdb;
        
        // Check if store has synced orders
        $orders_table = $wpdb->prefix . 'frf_woo_orders';
        $has_orders = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$orders_table} WHERE store_id = %d",
            intval($store_id)
        ));
        
        if ($has_orders > 0) {
            return new WP_Error('has_orders', 
                __('Impossibile eliminare: il negozio ha ordini sincronizzati', 'fatture-rf'));
        }
        
        return $wpdb->delete($this->table_name, array('id' => intval($store_id)));
    }
    
    /**
     * Check if store exists
     */
    public function exists($store_id) {
        global $wpdb;
        
        return (bool) $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$this->table_name} WHERE id = %d",
            intval($store_id)
        ));
    }
    
    /**
     * Test WooCommerce API connection
     */
    public function test_connection($store_url, $consumer_key, $consumer_secret) {
        $api_url = trailingslashit($store_url) . 'wp-json/wc/v3/system_status';
        
        $response = wp_remote_get($api_url, array(
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode($consumer_key . ':' . $consumer_secret)
            ),
            'timeout' => 15
        ));
        
        if (is_wp_error($response)) {
            return new WP_Error('connection_failed', 
                sprintf(__('Connessione fallita: %s', 'fatture-rf'), $response->get_error_message()));
        }
        
        $code = wp_remote_retrieve_response_code($response);
        
        if ($code === 200) {
            return true;
        } elseif ($code === 401) {
            return new WP_Error('authentication_failed', 
                __('Autenticazione fallita: controlla Consumer Key e Secret', 'fatture-rf'));
        } else {
            return new WP_Error('invalid_response', 
                sprintf(__('Risposta non valida dal server (Codice: %d)', 'fatture-rf'), $code));
        }
    }
    
    /**
     * Make API request to WooCommerce store
     */
    public function api_request($store_id, $endpoint, $method = 'GET', $data = array()) {
        $store = $this->get_store($store_id);
        
        if (!$store) {
            return new WP_Error('store_not_found', __('Negozio non trovato', 'fatture-rf'));
        }
        
        $api_url = trailingslashit($store->store_url) . 'wp-json/wc/v3/' . ltrim($endpoint, '/');
        
        $args = array(
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode($store->consumer_key . ':' . $store->consumer_secret),
                'Content-Type' => 'application/json'
            ),
            'timeout' => 30
        );
        
        if ($method === 'GET') {
            if (!empty($data)) {
                $api_url = add_query_arg($data, $api_url);
            }
            $response = wp_remote_get($api_url, $args);
        } else {
            $args['body'] = json_encode($data);
            $args['method'] = $method;
            $response = wp_remote_request($api_url, $args);
        }
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        if ($code >= 200 && $code < 300) {
            return json_decode($body, true);
        }
        
        return new WP_Error('api_error', 
            sprintf(__('Errore API (Codice: %d): %s', 'fatture-rf'), $code, $body));
    }
    
    /**
     * Update last sync time
     */
    public function update_last_sync($store_id) {
        global $wpdb;
        
        return $wpdb->update(
            $this->table_name,
            array('last_sync_at' => current_time('mysql')),
            array('id' => intval($store_id))
        );
    }
    
    /**
     * Get store statistics
     */
    public function get_store_stats($store_id) {
        global $wpdb;
        
        $orders_table = $wpdb->prefix . 'frf_woo_orders';
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT 
                COUNT(*) as total_orders,
                SUM(CASE WHEN invoice_id IS NOT NULL THEN 1 ELSE 0 END) as invoiced_orders,
                SUM(CASE WHEN invoice_id IS NULL THEN 1 ELSE 0 END) as pending_orders,
                SUM(total) as total_amount
             FROM {$orders_table} 
             WHERE store_id = %d",
            intval($store_id)
        ));
    }
}