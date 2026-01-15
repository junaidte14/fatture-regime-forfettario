<?php
/**
 * Client Model Class
 * Handles client management with IT/EU/NON_EU classification
 */

if (!defined('ABSPATH')) {
    exit;
}

class FRF_Client {
    
    private $table_name;
    
    public function __construct() {
        $this->table_name = FRF_Database::get_table_name('clients');
    }
    
    /**
     * Create new client
     */
    public function create($data) {
        global $wpdb;
        
        // Validate required fields
        if (empty($data['business_name'])) {
            return new WP_Error('invalid_data', __('Business name is required', 'fatture-rf'));
        }
        
        // Determine client type based on country
        $country = strtoupper(sanitize_text_field($data['country'] ?? 'IT'));
        $client_type = $this->determine_client_type($country);
        
        // Prepare client data
        $client_data = array(
            'business_name' => sanitize_text_field($data['business_name']),
            'vat_number' => sanitize_text_field($data['vat_number'] ?? ''),
            'tax_code' => sanitize_text_field($data['tax_code'] ?? ''),
            'email' => sanitize_email($data['email'] ?? ''),
            'pec_email' => sanitize_email($data['pec_email'] ?? ''),
            'phone' => sanitize_text_field($data['phone'] ?? ''),
            'address' => sanitize_textarea_field($data['address'] ?? ''),
            'city' => sanitize_text_field($data['city'] ?? ''),
            'province' => sanitize_text_field($data['province'] ?? ''),
            'postal_code' => sanitize_text_field($data['postal_code'] ?? ''),
            'country' => $country,
            'client_type' => $client_type,
            'sdi_code' => sanitize_text_field($data['sdi_code'] ?? '')
        );
        
        // Validate based on client type
        $validation = $this->validate_client_data($client_data);
        if (is_wp_error($validation)) {
            return $validation;
        }
        
        $result = $wpdb->insert($this->table_name, $client_data);
        
        if ($result === false) {
            return new WP_Error('db_error', __('Error creating client', 'fatture-rf'));
        }
        
        return $wpdb->insert_id;
    }
    
    /**
     * Update client
     */
    public function update($client_id, $data) {
        global $wpdb;
        
        $client_id = intval($client_id);
        
        if (!$this->exists($client_id)) {
            return new WP_Error('not_found', __('Client not found', 'fatture-rf'));
        }
        
        // Prepare update data
        $update_data = array();
        
        $fields = ['business_name', 'vat_number', 'tax_code', 'email', 'pec_email', 
                   'phone', 'address', 'city', 'province', 'postal_code', 'country', 'sdi_code'];
        
        foreach ($fields as $field) {
            if (isset($data[$field])) {
                $update_data[$field] = $data[$field];
            }
        }
        
        // Update client type if country changed
        if (isset($update_data['country'])) {
            $update_data['country'] = strtoupper($update_data['country']);
            $update_data['client_type'] = $this->determine_client_type($update_data['country']);
        }
        
        if (empty($update_data)) {
            return true;
        }
        
        // Validate data
        $current = $this->get($client_id);
        $merged_data = array_merge((array) $current, $update_data);
        $validation = $this->validate_client_data($merged_data);
        if (is_wp_error($validation)) {
            return $validation;
        }
        
        $result = $wpdb->update(
            $this->table_name,
            $update_data,
            array('id' => $client_id)
        );
        
        return $result !== false;
    }
    
    /**
     * Get client by ID
     */
    public function get($client_id) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE id = %d",
            intval($client_id)
        ));
    }
    
    /**
     * Get all clients with filters
     */
    public function get_all($args = array()) {
        global $wpdb;
        
        $defaults = array(
            'client_type' => '',
            'search' => '',
            'orderby' => 'business_name',
            'order' => 'ASC',
            'limit' => 50,
            'offset' => 0
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $where = array('1=1');
        
        if (!empty($args['client_type'])) {
            $where[] = $wpdb->prepare('client_type = %s', sanitize_text_field($args['client_type']));
        }
        
        if (!empty($args['search'])) {
            $search = '%' . $wpdb->esc_like(sanitize_text_field($args['search'])) . '%';
            $where[] = $wpdb->prepare(
                '(business_name LIKE %s OR vat_number LIKE %s OR tax_code LIKE %s OR email LIKE %s)',
                $search, $search, $search, $search
            );
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
     * Delete client
     */
    public function delete($client_id) {
        global $wpdb;
        
        // Check if client has invoices
        $invoices_table = FRF_Database::get_table_name('invoices');
        $has_invoices = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$invoices_table} WHERE client_id = %d",
            intval($client_id)
        ));
        
        if ($has_invoices > 0) {
            return new WP_Error('has_invoices', __('Cannot delete: client has associated invoices', 'fatture-rf'));
        }
        
        return $wpdb->delete($this->table_name, array('id' => intval($client_id)));
    }
    
    /**
     * Check if client exists
     */
    public function exists($client_id) {
        global $wpdb;
        
        return (bool) $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$this->table_name} WHERE id = %d",
            intval($client_id)
        ));
    }
    
    /**
     * Determine client type based on country
     */
    private function determine_client_type($country) {
        $country = strtoupper($country);
        
        if ($country === 'IT') {
            return 'IT';
        }
        
        // EU countries
        $eu_countries = array(
            'AT', 'BE', 'BG', 'CY', 'CZ', 'DE', 'DK', 'EE', 'ES', 'FI', 'FR', 'GR', 
            'HR', 'HU', 'IE', 'LT', 'LU', 'LV', 'MT', 'NL', 'PL', 'PT', 'RO', 'SE', 'SI', 'SK'
        );
        
        if (in_array($country, $eu_countries)) {
            return 'EU';
        }
        
        return 'NON_EU';
    }
    
    /**
     * Validate client data by combining direct fields with raw meta-data intelligence
     */
    private function validate_client_data($data) {
        // 1. Standard Data Extraction
        $country    = isset($data['country']) ? strtoupper($data['country']) : ''; 
        $vat_number = isset($data['vat_number']) ? trim($data['vat_number']) : '';
        $tax_code   = isset($data['tax_code']) ? trim($data['tax_code']) : '';
        $sdi_code   = isset($data['sdi_code']) ? trim($data['sdi_code']) : '';
        $pec_email  = isset($data['pec_email']) ? trim($data['pec_email']) : '';

        // 2. Intelligent Client Type Extraction (from raw_data meta)
        $client_type = '';
        if (isset($data['raw_data']['order']['meta_data'])) {
            foreach ($data['raw_data']['order']['meta_data'] as $meta) {
                if ($meta['key'] === '_forfettario_client_type') {
                    $client_type = $meta['value'];
                    break;
                }
            }
        }

        // --- 3. VALIDATION LOGIC ---

        // SCENARIO: ITALIAN CLIENTS
        if ($country === 'IT') {
            
            // B2B: Italian Business
            if ($client_type === 'b2b_it') {
                if (empty($vat_number)) {
                    return new WP_Error('validation_error', __('Italian business clients require a VAT Number.', 'fatture-rf'));
                }
                if (!preg_match('/^\d{11}$/', $vat_number)) {
                    return new WP_Error('validation_error', __('Invalid Italian VAT number (11 digits required).', 'fatture-rf'));
                }
                // Electronic Invoicing Check (Mandatory for B2B)
                if (empty($sdi_code) && empty($pec_email)) {
                    return new WP_Error('validation_error', __('Electronic invoicing requires SDI Code or PEC.', 'fatture-rf'));
                }
            } 
            
            // B2C: Italian Private (Fixes your bug: SDI/PEC no longer required here)
            else {
                if (empty($tax_code)) {
                    return new WP_Error('validation_error', __('Italian private clients require a Tax Code.', 'fatture-rf'));
                }
                if (!preg_match('/^[A-Z0-9]{16}$/i', $tax_code)) {
                    return new WP_Error('validation_error', __('Invalid Tax Code (16 characters required).', 'fatture-rf'));
                }
            }
        }

        // SCENARIO: EU CLIENTS (NON-IT)
        elseif ($this->is_eu_country($country)) {
            
            // EU Business needs VAT for Intra-Community rules
            if ($client_type === 'b2b_eu' && empty($vat_number)) {
                return new WP_Error('validation_error', __('EU Business clients require a VAT Number.', 'fatture-rf'));
            }
            
            // EU Private (B2C) usually needs no specific fiscal validation
        }

        // SCENARIO: NON-EU (REST OF WORLD)
        else {
            if (empty($country)) {
                return new WP_Error('validation_error', __('Country field is required.', 'fatture-rf'));
            }
        }

        return true;
    }

    /**
     * Check if a country code belongs to the European Union
     */
    private function is_eu_country($cc) {
        $eu_list = ['AT','BE','BG','HR','CY','CZ','DK','EE','FI','FR','DE','GR','HU','IE','LV','LT','LU','MT','NL','PL','PT','RO','SK','SI','ES','SE'];
        return in_array($cc, $eu_list);
    }
    
    /**
     * Get client statistics
     */
    public function get_client_stats($client_id) {
        global $wpdb;
        
        $invoices_table = FRF_Database::get_table_name('invoices');
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT 
                COUNT(*) as total_invoices,
                SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END) as paid_invoices,
                SUM(CASE WHEN status = 'paid' THEN total ELSE 0 END) as total_paid,
                SUM(CASE WHEN status != 'paid' AND status != 'cancelled' THEN total ELSE 0 END) as total_outstanding
             FROM {$invoices_table} 
             WHERE client_id = %d",
            intval($client_id)
        ));
    }
}