<?php
/**
 * Admin Clients Management
 */

if (!defined('ABSPATH')) {
    exit;
}

class FRF_Admin_Clients {
    
    /**
     * Render clients page
     */
    public static function render() {
        $action = isset($_GET['view']) ? sanitize_text_field($_GET['view']) : 'list';
        
        switch ($action) {
            case 'add':
                self::render_add_client();
                break;
            case 'edit':
                self::render_edit_client();
                break;
            case 'view':
                self::render_view_client();
                break;
            default:
                self::render_list();
        }
    }
    
    /**
     * Render clients list
     */
    private static function render_list() {
        $client = new FRF_Client();
        
        // Handle filters
        $client_type = isset($_GET['client_type']) ? sanitize_text_field($_GET['client_type']) : '';
        $search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
        
        $args = array(
            'client_type' => $client_type,
            'search' => $search,
            'limit' => 50,
            'offset' => 0
        );
        
        $clients = $client->get_all($args);
        
        include FRF_PLUGIN_DIR . 'admin/views/clients-list.php';
    }
    
    /**
     * Render add client form
     */
    private static function render_add_client() {
        // Handle form submission
        if (isset($_POST['frf_save_client'])) {
            check_admin_referer('frf_save_client');
            
            $result = self::save_client();
            
            if (is_wp_error($result)) {
                $error = $result->get_error_message();
            } else {
                wp_redirect(admin_url('admin.php?page=fatture-rf-clients&view=view&id=' . $result));
                exit;
            }
        }
        
        include FRF_PLUGIN_DIR . 'admin/views/client-form.php';
    }
    
    /**
     * Render edit client form
     */
    private static function render_edit_client() {
        if (!isset($_GET['id'])) {
            wp_die(__('ID cliente non valido', 'fatture-rf'));
        }
        
        $client_id = intval($_GET['id']);
        $client_model = new FRF_Client();
        $client = $client_model->get($client_id);
        
        if (!$client) {
            wp_die(__('Cliente non trovato', 'fatture-rf'));
        }
        
        // Handle form submission
        if (isset($_POST['frf_save_client'])) {
            check_admin_referer('frf_save_client');
            
            $result = self::save_client($client_id);
            
            if (is_wp_error($result)) {
                $error = $result->get_error_message();
            } else {
                wp_redirect(admin_url('admin.php?page=fatture-rf-clients&view=view&id=' . $client_id));
                exit;
            }
        }
        
        include FRF_PLUGIN_DIR . 'admin/views/client-form.php';
    }
    
    /**
     * Render client view
     */
    private static function render_view_client() {
        if (!isset($_GET['id'])) {
            wp_die(__('ID cliente non valido', 'fatture-rf'));
        }
        
        $client_id = intval($_GET['id']);
        $client_model = new FRF_Client();
        $client = $client_model->get($client_id);
        
        if (!$client) {
            wp_die(__('Cliente non trovato', 'fatture-rf'));
        }
        
        // Get client invoices
        $invoice_model = new FRF_Invoice();
        $invoices = $invoice_model->get_all(array('client_id' => $client_id, 'limit' => 0));
        
        // Get client stats
        $stats = $client_model->get_client_stats($client_id);
        
        include FRF_PLUGIN_DIR . 'admin/views/client-view.php';
    }
    
    /**
     * Save client (create or update)
     */
    private static function save_client($client_id = 0) {
        $client_model = new FRF_Client();
        
        $data = array(
            'business_name' => sanitize_text_field($_POST['business_name']),
            'vat_number' => sanitize_text_field($_POST['vat_number'] ?? ''),
            'tax_code' => sanitize_text_field($_POST['tax_code'] ?? ''),
            'email' => sanitize_email($_POST['email'] ?? ''),
            'pec_email' => sanitize_email($_POST['pec_email'] ?? ''),
            'phone' => sanitize_text_field($_POST['phone'] ?? ''),
            'address' => sanitize_textarea_field($_POST['address'] ?? ''),
            'city' => sanitize_text_field($_POST['city'] ?? ''),
            'province' => sanitize_text_field($_POST['province'] ?? ''),
            'postal_code' => sanitize_text_field($_POST['postal_code'] ?? ''),
            'country' => sanitize_text_field($_POST['country'] ?? 'IT'),
            'sdi_code' => sanitize_text_field($_POST['sdi_code'] ?? '')
        );
        
        if ($client_id > 0) {
            return $client_model->update($client_id, $data);
        } else {
            return $client_model->create($data);
        }
    }
    
    /**
     * Get client type badge HTML
     */
    public static function get_client_type_badge($type) {
        $types = array(
            'IT' => array('label' => __('Italia', 'fatture-rf'), 'class' => 'success'),
            'EU' => array('label' => __('UE', 'fatture-rf'), 'class' => 'info'),
            'NON_EU' => array('label' => __('Extra-UE', 'fatture-rf'), 'class' => 'warning')
        );
        
        $type_info = isset($types[$type]) ? $types[$type] : array('label' => $type, 'class' => 'default');
        
        return sprintf(
            '<span class="frf-badge frf-badge-%s">%s</span>',
            esc_attr($type_info['class']),
            esc_html($type_info['label'])
        );
    }
}