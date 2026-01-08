<?php
/**
 * Invoice Model Class
 * Handles all invoice operations
 */

if (!defined('ABSPATH')) {
    exit;
}

class FRF_Invoice {
    
    private $table_name;
    private $items_table;
    private $history_table;
    
    public function __construct() {
        global $wpdb;
        $this->table_name = FRF_Database::get_table_name('invoices');
        $this->items_table = FRF_Database::get_table_name('invoice_items');
        $this->history_table = FRF_Database::get_table_name('status_history');
    }
    
    /**
     * Create new invoice
     */
    public function create($data) {
        global $wpdb;
        
        // Validate required fields
        if (empty($data['invoice_number']) || empty($data['client_id'])) {
            return new WP_Error('invalid_data', __('Invoice number and client are required', 'fatture-rf'));
        }
        
        // Check if invoice number already exists
        if ($this->invoice_number_exists($data['invoice_number'])) {
            return new WP_Error('duplicate', __('Invoice number already exists', 'fatture-rf'));
        }
        
        // Prepare invoice data
        $invoice_data = array(
            'invoice_number' => sanitize_text_field($data['invoice_number']),
            'invoice_date' => sanitize_text_field($data['invoice_date']),
            'client_id' => intval($data['client_id']),
            'subtotal' => floatval($data['subtotal'] ?? 0),
            'tax_rate' => floatval($data['tax_rate'] ?? 0),
            'tax_amount' => floatval($data['tax_amount'] ?? 0),
            'total' => floatval($data['total'] ?? 0),
            'withholding_tax' => floatval($data['withholding_tax'] ?? 0),
            'withholding_amount' => floatval($data['withholding_amount'] ?? 0),
            'net_to_pay' => floatval($data['net_to_pay'] ?? 0),
            'payment_terms' => sanitize_text_field($data['payment_terms'] ?? ''),
            'payment_method' => sanitize_text_field($data['payment_method'] ?? ''),
            'notes' => sanitize_textarea_field($data['notes'] ?? ''),
            'status' => sanitize_text_field($data['status'] ?? 'draft')
        );
        
        $result = $wpdb->insert($this->table_name, $invoice_data);
        
        if ($result === false) {
            return new WP_Error('db_error', __('Error creating invoice', 'fatture-rf'));
        }
        
        $invoice_id = $wpdb->insert_id;
        
        // Add invoice items if provided
        if (!empty($data['items']) && is_array($data['items'])) {
            $this->add_items($invoice_id, $data['items']);
        }
        
        // Log status in history
        $this->add_status_history($invoice_id, null, $invoice_data['status']);
        
        return $invoice_id;
    }
    
    /**
     * Update invoice
     */
    public function update($invoice_id, $data) {
        global $wpdb;
        
        $invoice_id = intval($invoice_id);
        
        // Get current invoice
        $current = $this->get($invoice_id);
        if (!$current) {
            return new WP_Error('not_found', __('Invoice not found', 'fatture-rf'));
        }
        
        // Prepare update data
        $update_data = array();
        
        $fields = ['invoice_number', 'invoice_date', 'client_id', 'subtotal', 'tax_rate', 
                   'tax_amount', 'total', 'withholding_tax', 'withholding_amount', 
                   'net_to_pay', 'payment_terms', 'payment_method', 'notes', 'status'];
        
        foreach ($fields as $field) {
            if (isset($data[$field])) {
                $update_data[$field] = $data[$field];
            }
        }
        
        if (empty($update_data)) {
            return true;
        }
        
        // Track status change
        if (isset($update_data['status']) && $update_data['status'] !== $current->status) {
            $this->add_status_history($invoice_id, $current->status, $update_data['status']);
        }
        
        $result = $wpdb->update(
            $this->table_name,
            $update_data,
            array('id' => $invoice_id)
        );
        
        // Update items if provided
        if (isset($data['items']) && is_array($data['items'])) {
            $this->delete_items($invoice_id);
            $this->add_items($invoice_id, $data['items']);
        }
        
        return $result !== false;
    }
    
    /**
     * Get invoice by ID
     */
    public function get($invoice_id) {
        global $wpdb;
        
        $invoice = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE id = %d",
            intval($invoice_id)
        ));
        
        if ($invoice) {
            $invoice->items = $this->get_items($invoice_id);
        }
        
        return $invoice;
    }
    
    /**
     * Get all invoices with filters
     */
    public function get_all($args = array()) {
        global $wpdb;
        
        $defaults = array(
            'status' => '',
            'client_id' => 0,
            'date_from' => '',
            'date_to' => '',
            'orderby' => 'invoice_date',
            'order' => 'DESC',
            'limit' => 20,
            'offset' => 0
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $where = array('1=1');
        
        if (!empty($args['status'])) {
            $where[] = $wpdb->prepare('status = %s', sanitize_text_field($args['status']));
        }
        
        if (!empty($args['client_id'])) {
            $where[] = $wpdb->prepare('client_id = %d', intval($args['client_id']));
        }
        
        if (!empty($args['date_from'])) {
            $where[] = $wpdb->prepare('invoice_date >= %s', sanitize_text_field($args['date_from']));
        }
        
        if (!empty($args['date_to'])) {
            $where[] = $wpdb->prepare('invoice_date <= %s', sanitize_text_field($args['date_to']));
        }
        
        $where_clause = implode(' AND ', $where);
        $orderby = sanitize_sql_orderby($args['orderby'] . ' ' . $args['order']);
        
        $query = "SELECT * FROM {$this->table_name} WHERE {$where_clause} ORDER BY {$orderby}";
        
        if ($args['limit'] > 0) {
            $query .= $wpdb->prepare(' LIMIT %d OFFSET %d', intval($args['limit']), intval($args['offset']));
        }
        
        return $wpdb->get_results($query);
    }
    
    /**
     * Delete invoice
     */
    public function delete($invoice_id) {
        global $wpdb;
        
        $invoice_id = intval($invoice_id);
        
        return $wpdb->delete($this->table_name, array('id' => $invoice_id));
    }
    
    /**
     * Add invoice items
     */
    private function add_items($invoice_id, $items) {
        global $wpdb;
        
        foreach ($items as $index => $item) {
            $wpdb->insert($this->items_table, array(
                'invoice_id' => $invoice_id,
                'description' => sanitize_textarea_field($item['description']),
                'quantity' => floatval($item['quantity']),
                'unit_price' => floatval($item['unit_price']),
                'total' => floatval($item['total']),
                'sort_order' => $index
            ));
        }
    }
    
    /**
     * Get invoice items
     */
    public function get_items($invoice_id) {
        global $wpdb;
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->items_table} WHERE invoice_id = %d ORDER BY sort_order",
            intval($invoice_id)
        ));
    }
    
    /**
     * Delete invoice items
     */
    private function delete_items($invoice_id) {
        global $wpdb;
        return $wpdb->delete($this->items_table, array('invoice_id' => $invoice_id));
    }
    
    /**
     * Add status history
     */
    private function add_status_history($invoice_id, $old_status, $new_status, $notes = '') {
        global $wpdb;
        
        $wpdb->insert($this->history_table, array(
            'invoice_id' => $invoice_id,
            'old_status' => $old_status,
            'new_status' => $new_status,
            'notes' => sanitize_textarea_field($notes),
            'changed_by' => get_current_user_id()
        ));
    }
    
    /**
     * Get status history
     */
    public function get_status_history($invoice_id) {
        global $wpdb;
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->history_table} WHERE invoice_id = %d ORDER BY changed_at DESC",
            intval($invoice_id)
        ));
    }
    
    /**
     * Check if invoice number exists
     */
    private function invoice_number_exists($invoice_number, $exclude_id = 0) {
        global $wpdb;
        
        $query = $wpdb->prepare(
            "SELECT id FROM {$this->table_name} WHERE invoice_number = %s",
            sanitize_text_field($invoice_number)
        );
        
        if ($exclude_id > 0) {
            $query .= $wpdb->prepare(' AND id != %d', intval($exclude_id));
        }
        
        return (bool) $wpdb->get_var($query);
    }
    
    /**
     * Generate next invoice number
     */
    public function generate_invoice_number() {
        global $wpdb;
        
        $year = date('Y');
        $prefix = get_option('frf_invoice_prefix', 'FATT');
        
        $last_number = $wpdb->get_var($wpdb->prepare(
            "SELECT invoice_number FROM {$this->table_name} 
             WHERE invoice_number LIKE %s 
             ORDER BY id DESC LIMIT 1",
            $prefix . '/' . $year . '/%'
        ));
        
        if ($last_number) {
            $parts = explode('/', $last_number);
            $number = intval(end($parts)) + 1;
        } else {
            $number = 1;
        }
        
        return sprintf('%s/%s/%04d', $prefix, $year, $number);
    }
}