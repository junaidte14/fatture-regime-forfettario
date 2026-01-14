<?php
/**
 * WooCommerce Sync Class - COMPLETE FIXED VERSION
 * Path: includes/class-frf-woo-sync.php
 * Handles syncing orders and customers from WooCommerce stores
 */

if (!defined('ABSPATH')) {
    exit;
}

class FRF_WooCommerce_Sync {
    
    private $store_model;
    private $orders_table;
    
    public function __construct() {
        global $wpdb;
        $this->store_model = new FRF_WooCommerce_Store();
        $this->orders_table = $wpdb->prefix . 'frf_woo_orders';
    }
    
    /**
     * Sync orders from a specific store - FIXED VERSION
     */
    public function sync_store_orders($store_id, $limit = 50) {
        $store = $this->store_model->get_store($store_id);
        
        if (!$store) {
            return new WP_Error('store_not_found', __('Negozio non trovato', 'fatture-rf'));
        }
        
        // Prepare API parameters
        $params = array(
            'per_page' => $limit,
            'orderby'  => 'date',
            'order'    => 'desc',
        );
        
        // Determine the start date for sync
        $last_synced = $this->get_last_synced_date($store_id);
        
        if (!empty($last_synced) && $last_synced !== '0000-00-00 00:00:00') {
            // Use last synced date if available
            $timestamp = strtotime($last_synced);
        } elseif (!empty($store->sync_from_date) && $store->sync_from_date !== '0000-00-00') {
            // Use configured sync_from_date
            $timestamp = strtotime($store->sync_from_date . ' 00:00:00');
        } else {
            // Default: 30 days ago
            $timestamp = strtotime('-30 days');
        }
        
        // Format as ISO 8601 (WooCommerce REST API format)
        // Subtract 1 second to avoid duplicate on subsequent syncs
        if (!empty($last_synced)) {
            $timestamp = $timestamp + 1;
        }
        
        $params['after'] = gmdate('Y-m-d\TH:i:s\Z', $timestamp); // ISO 8601 format

        // Fetch orders from WooCommerce
        $orders = $this->store_model->api_request($store_id, 'orders', 'GET', $params);
        
        if (is_wp_error($orders)) {
            return $orders;
        }
        
        $synced_count = 0;
        $errors = array();
        
        foreach ($orders as $order) {
            $result = $this->save_order($store_id, $order);
            
            if (is_wp_error($result)) {
                $errors[] = sprintf(
                    __('Ordine #%s: %s', 'fatture-rf'),
                    $order['number'],
                    $result->get_error_message()
                );
            } else {
                $synced_count++;
            }
        }
        
        // Update last sync time
        $this->store_model->update_last_sync($store_id);
        
        return array(
            'synced' => $synced_count,
            'total' => count($orders),
            'errors' => $errors
        );
    }
    
    /**
     * Save WooCommerce order to local database - ENHANCED VERSION
     */
    private function save_order($store_id, $order_data) {
        global $wpdb;
        
        // Check if order already exists
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$this->orders_table} 
             WHERE store_id = %d AND woo_order_id = %d",
            $store_id,
            $order_data['id']
        ));
        
        // Extract customer and items data
        $customer_data = $this->extract_customer_data($order_data);
        $items_data = $this->extract_items_data($order_data);
        
        // Extract marca da bollo information from fees
        $bollo_info = $this->extract_bollo_info($order_data);
        
        // Map WooCommerce order to our format
        $order = array(
            'store_id' => $store_id,
            'woo_order_id' => $order_data['id'],
            'order_number' => sanitize_text_field($order_data['number']),
            'order_date' => date('Y-m-d H:i:s', strtotime($order_data['date_created'])),
            'status' => sanitize_text_field($order_data['status']),
            'customer_data' => json_encode($customer_data),
            'items_data' => json_encode($items_data),
            'subtotal' => floatval($order_data['total']) - floatval($order_data['total_tax']) - floatval($bollo_info['amount']),
            'tax' => floatval($order_data['total_tax']),
            'total' => floatval($order_data['total']),
            'currency' => sanitize_text_field($order_data['currency']),
            'payment_method' => sanitize_text_field($order_data['payment_method_title'] ?? ''),
            'raw_data' => json_encode(array(
                'order' => $order_data,
                'bollo' => $bollo_info,
                'fiscal_data' => array(
                    'codice_fiscale' => $customer_data['codice_fiscale'],
                    'partita_iva' => $customer_data['partita_iva'],
                    'sdi' => $customer_data['sdi'],
                    'pec' => $customer_data['pec']
                )
            ))
        );
        
        if ($existing) {
            // Update existing order
            $order['updated_at'] = current_time('mysql');
            $result = $wpdb->update(
                $this->orders_table,
                $order,
                array('id' => $existing)
            );
            return $result !== false ? $existing : new WP_Error('update_failed', 
                __('Errore aggiornamento ordine', 'fatture-rf'));
        } else {
            // Insert new order
            $order['created_at'] = current_time('mysql');
            $result = $wpdb->insert($this->orders_table, $order);
            return $result !== false ? $wpdb->insert_id : new WP_Error('insert_failed', 
                __('Errore salvataggio ordine', 'fatture-rf'));
        }
    }
    
    /**
     * Extract customer data from WooCommerce order - ENHANCED VERSION
     */
    private function extract_customer_data($order) {
        $billing = $order['billing'];
        $shipping = $order['shipping'];
        
        // Extract Italian fiscal fields from meta_data
        $meta_data = isset($order['meta_data']) ? $order['meta_data'] : array();
        $codice_fiscale = $this->get_meta_value($meta_data, 'billing_codice_fiscale');
        $partita_iva = $this->get_meta_value($meta_data, 'billing_partita_iva');
        $sdi = $this->get_meta_value($meta_data, 'billing_sdi');
        $pec = $this->get_meta_value($meta_data, 'billing_pec');
        
        // Alternative meta key names (some plugins use different keys)
        if (empty($codice_fiscale)) {
            $codice_fiscale = $this->get_meta_value($meta_data, '_billing_cf');
        }
        if (empty($partita_iva)) {
            $partita_iva = $this->get_meta_value($meta_data, '_billing_vat_number');
        }
        if (empty($sdi)) {
            $sdi = $this->get_meta_value($meta_data, '_billing_codice_sdi');
        }
        if (empty($pec)) {
            $pec = $this->get_meta_value($meta_data, '_billing_pec_email');
        }
        
        return array(
            'email' => $billing['email'] ?? '',
            'first_name' => $billing['first_name'] ?? '',
            'last_name' => $billing['last_name'] ?? '',
            'company' => $billing['company'] ?? '',
            'phone' => $billing['phone'] ?? '',
            'billing_address' => array(
                'address_1' => $billing['address_1'] ?? '',
                'address_2' => $billing['address_2'] ?? '',
                'city' => $billing['city'] ?? '',
                'state' => $billing['state'] ?? '',
                'postcode' => $billing['postcode'] ?? '',
                'country' => $billing['country'] ?? ''
            ),
            'shipping_address' => array(
                'address_1' => $shipping['address_1'] ?? '',
                'address_2' => $shipping['address_2'] ?? '',
                'city' => $shipping['city'] ?? '',
                'state' => $shipping['state'] ?? '',
                'postcode' => $shipping['postcode'] ?? '',
                'country' => $shipping['country'] ?? ''
            ),
            // Italian fiscal fields
            'codice_fiscale' => $codice_fiscale,
            'partita_iva' => $partita_iva,
            'sdi' => $sdi,
            'pec' => $pec,
            // Legacy fields for backwards compatibility
            'vat_number' => $partita_iva,
            'tax_code' => $codice_fiscale
        );
    }
    
    /**
     * Extract items data from WooCommerce order
     */
    private function extract_items_data($order) {
        $items = array();
        
        foreach ($order['line_items'] as $item) {
            $items[] = array(
                'name' => $item['name'],
                'product_id' => $item['product_id'],
                'variation_id' => $item['variation_id'] ?? 0,
                'quantity' => $item['quantity'],
                'subtotal' => floatval($item['subtotal']),
                'total' => floatval($item['total']),
                'tax' => floatval($item['total_tax']),
                'sku' => $item['sku'] ?? ''
            );
        }
        
        return $items;
    }
    
    /**
     * Get meta value from WooCommerce meta data array
     */
    private function get_meta_value($meta_data, $key) {
        foreach ($meta_data as $meta) {
            if ($meta['key'] === $key) {
                return $meta['value'];
            }
        }
        return '';
    }
    
    /**
     * Extract marca da bollo information from order - NEW METHOD
     */
    private function extract_bollo_info($order_data) {
        $bollo_info = array(
            'applied' => false,
            'amount' => 0
        );
        
        // Check fees for marca da bollo
        if (isset($order_data['fee_lines']) && is_array($order_data['fee_lines'])) {
            foreach ($order_data['fee_lines'] as $fee) {
                $fee_name = isset($fee['name']) ? strtolower($fee['name']) : '';
                
                // Check if this is a marca da bollo fee
                if (strpos($fee_name, 'bollo') !== false || 
                    strpos($fee_name, 'imposta di bollo') !== false ||
                    strpos($fee_name, 'stamp') !== false) {
                    
                    $bollo_info['applied'] = true;
                    $bollo_info['amount'] = abs(floatval($fee['total']));
                    break;
                }
            }
        }
        
        // Also check meta data
        if (!$bollo_info['applied'] && isset($order_data['meta_data'])) {
            $meta_bollo_applied = $this->get_meta_value($order_data['meta_data'], '_forfettario_bollo_applied');
            $meta_bollo_amount = $this->get_meta_value($order_data['meta_data'], '_forfettario_bollo_amount');
            
            if ($meta_bollo_applied === 'yes' || $meta_bollo_applied === true) {
                $bollo_info['applied'] = true;
                $bollo_info['amount'] = floatval($meta_bollo_amount);
            }
        }
        
        return $bollo_info;
    }
    
    /**
     * Get synced order by ID
     */
    public function get_order($order_id) {
        global $wpdb;
        
        $order = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->orders_table} WHERE id = %d",
            intval($order_id)
        ));
        
        if ($order) {
            $order->customer_data = json_decode($order->customer_data, true);
            $order->items_data = json_decode($order->items_data, true);
        }
        
        return $order;
    }
    
    /**
     * Get all synced orders
     */
    public function get_all_orders($args = array()) {
        global $wpdb;
        
        $defaults = array(
            'store_id' => 0,
            'status' => '',
            'has_invoice' => '', // 'yes', 'no', ''
            'orderby' => 'order_date',
            'order' => 'DESC',
            'limit' => 50,
            'offset' => 0
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $where = array('1=1');
        
        if (!empty($args['store_id'])) {
            $where[] = $wpdb->prepare('store_id = %d', intval($args['store_id']));
        }
        
        if (!empty($args['status'])) {
            $where[] = $wpdb->prepare('status = %s', sanitize_text_field($args['status']));
        }
        
        if ($args['has_invoice'] === 'yes') {
            $where[] = 'invoice_id IS NOT NULL';
        } elseif ($args['has_invoice'] === 'no') {
            $where[] = 'invoice_id IS NULL';
        }
        
        $where_clause = implode(' AND ', $where);
        $orderby = sanitize_sql_orderby($args['orderby'] . ' ' . $args['order']);
        
        $query = "SELECT * FROM {$this->orders_table} WHERE {$where_clause} ORDER BY {$orderby}";
        
        if ($args['limit'] > 0) {
            $query .= $wpdb->prepare(' LIMIT %d OFFSET %d', 
                intval($args['limit']), intval($args['offset']));
        }
        
        return $wpdb->get_results($query);
    }
    
    /**
     * Delete synced order - NEW METHOD
     */
    public function delete_order($order_id) {
        global $wpdb;
        
        $order = $this->get_order($order_id);
        
        if (!$order) {
            return new WP_Error('not_found', __('Ordine non trovato', 'fatture-rf'));
        }
        
        // Check if order has an associated invoice
        if ($order->invoice_id) {
            return new WP_Error('has_invoice', 
                __('Impossibile eliminare: l\'ordine ha una fattura associata. Elimina prima la fattura.', 'fatture-rf'));
        }
        
        // Delete the order
        $result = $wpdb->delete(
            $this->orders_table,
            array('id' => intval($order_id)),
            array('%d')
        );
        
        if ($result === false) {
            return new WP_Error('delete_failed', __('Errore durante l\'eliminazione dell\'ordine', 'fatture-rf'));
        }
        
        return true;
    }
    
    /**
     * Get or create client from order
     */
    public function get_or_create_client($order_id) {
        $order = $this->get_order($order_id);
        
        if (!$order) {
            return new WP_Error('order_not_found', __('Ordine non trovato', 'fatture-rf'));
        }
        
        $client_model = new FRF_Client();
        $customer = $order->customer_data;
        
        // Check if client already exists by email or VAT
        global $wpdb;
        $clients_table = FRF_Database::get_table_name('clients');
        
        $existing_client = null;
        
        // Try to find by Partita IVA first
        if (!empty($customer['partita_iva'])) {
            $existing_client = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$clients_table} WHERE vat_number = %s",
                $customer['partita_iva']
            ));
        }
        
        // Try by Codice Fiscale if not found
        if (!$existing_client && !empty($customer['codice_fiscale'])) {
            $existing_client = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$clients_table} WHERE tax_code = %s",
                $customer['codice_fiscale']
            ));
        }
        
        // Try by email if still not found
        if (!$existing_client && !empty($customer['email'])) {
            $existing_client = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$clients_table} WHERE email = %s",
                $customer['email']
            ));
        }
        
        if ($existing_client) {
            return $existing_client->id;
        }
        
        // Create new client with enhanced fiscal data
        $billing = $customer['billing_address'];
        
        $business_name = !empty($customer['company']) ? 
            $customer['company'] : 
            trim($customer['first_name'] . ' ' . $customer['last_name']);
        
        $client_data = array(
            'business_name' => $business_name,
            'vat_number' => $customer['partita_iva'] ?? '',
            'tax_code' => $customer['codice_fiscale'] ?? '',
            'email' => $customer['email'] ?? '',
            'phone' => $customer['phone'] ?? '',
            'address' => trim(($billing['address_1'] ?? '') . ' ' . ($billing['address_2'] ?? '')),
            'city' => $billing['city'] ?? '',
            'province' => $billing['state'] ?? '',
            'postal_code' => $billing['postcode'] ?? '',
            'country' => $billing['country'] ?? 'IT',
            'sdi_code' => $customer['sdi'] ?? '',
            'pec_email' => $customer['pec'] ?? '',
            'woo_store_id' => $order->store_id,
            'woo_customer_id' => $order->woo_order_id
        );
        
        return $client_model->create($client_data);
    }
    
    /**
     * Create invoice from WooCommerce order - FIXED VERSION
     */
    public function create_invoice_from_order($order_id) {
        global $wpdb;
        $order = $this->get_order($order_id);
        
        if (!$order) {
            return new WP_Error('order_not_found', __('Ordine non trovato', 'fatture-rf'));
        }
        
        // Check if invoice already exists and is valid
        if ($order->invoice_id) {
            $invoice_model = new FRF_Invoice();
            $existing_invoice = $invoice_model->get($order->invoice_id);
            
            // If invoice exists and is valid, return error
            if ($existing_invoice) {
                return new WP_Error('invoice_exists', 
                    __('Fattura già creata per questo ordine', 'fatture-rf'));
            } else {
                // Invoice was deleted, clear the reference
                $wpdb->update(
                    $this->orders_table,
                    array('invoice_id' => null),
                    array('id' => $order_id),
                    array('%d'),
                    array('%d')
                );
                $order->invoice_id = null;
            }
        }
        
        // Get or create client
        $client_id = $this->get_or_create_client($order_id);
        
        if (is_wp_error($client_id)) {
            return $client_id;
        }
        
        // Prepare invoice items
        $items = array();
        foreach ($order->items_data as $item) {
            $items[] = array(
                'description' => $item['name'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['subtotal'] / $item['quantity'],
                'total' => $item['total']
            );
        }
        
        // Get settings for defaults
        $settings = FRF_Settings::get_instance();
        
        // Generate invoice number
        $invoice_model = new FRF_Invoice();
        $invoice_number = $invoice_model->generate_invoice_number();
        
        // Prepare invoice data
        $invoice_data = array(
            'invoice_number' => $invoice_number,
            'invoice_date' => date('Y-m-d'),
            'client_id' => $client_id,
            'subtotal' => $order->subtotal,
            'tax_rate' => $order->subtotal > 0 ? ($order->tax / $order->subtotal) * 100 : 0,
            'tax_amount' => $order->tax,
            'total' => $order->total,
            'withholding_tax' => 0,
            'withholding_amount' => 0,
            'net_to_pay' => $order->total,
            'payment_method' => $order->payment_method,
            'payment_terms' => $settings->get('default_payment_terms'),
            'notes' => $this->build_invoice_notes($order, $settings),
            'status' => 'draft',
            'items' => $items,
            'woo_order_id' => $order->id
        );
        
        // Create invoice
        $invoice_id = $invoice_model->create($invoice_data);
        
        if (is_wp_error($invoice_id)) {
            return $invoice_id;
        }
        
        // Link invoice to order
        $wpdb->update(
            $this->orders_table,
            array('invoice_id' => $invoice_id),
            array('id' => $order_id)
        );
        
        return $invoice_id;
    }
    
    /**
     * Build invoice notes from order and settings - NEW METHOD
     */
    private function build_invoice_notes($order, $settings) {
        $notes_parts = array();
        
        // Add default notes from settings if configured
        $default_notes = $settings->get('default_notes');
        if (!empty($default_notes)) {
            $notes_parts[] = $default_notes;
        }
        
        // Add WooCommerce order reference
        $notes_parts[] = sprintf(
            __('Riferimento ordine WooCommerce #%s', 'fatture-rf'),
            $order->order_number
        );
        
        return implode("\n\n", $notes_parts);
    }
    
    /**
     * Get last synced date for a store
     */
    private function get_last_synced_date($store_id) {
        global $wpdb;
        
        return $wpdb->get_var($wpdb->prepare(
            "SELECT MAX(order_date) FROM {$this->orders_table} WHERE store_id = %d",
            intval($store_id)
        ));
    }
    
    /**
     * Sync all active stores
     */
    public function sync_all_stores() {
        $stores = $this->store_model->get_all_stores(array('status' => 'active'));
        
        $results = array();
        
        foreach ($stores as $store) {
            if ($store->auto_sync) {
                $results[$store->id] = $this->sync_store_orders($store->id);
            }
        }
        
        return $results;
    }
}