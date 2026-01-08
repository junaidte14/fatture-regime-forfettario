<?php
/**
 * Admin Invoices Management
 */

if (!defined('ABSPATH')) {
    exit;
}

class FRF_Admin_Invoices {
    
    /**
     * Render invoices page
     */
    public static function render() {
        $action = isset($_GET['view']) ? sanitize_text_field($_GET['view']) : 'list';
        
        switch ($action) {
            case 'add':
                self::render_add_invoice();
                break;
            case 'edit':
                self::render_edit_invoice();
                break;
            case 'view':
                self::render_view_invoice();
                break;
            default:
                self::render_list();
        }
    }
    
    /**
     * Render invoices list
     */
    private static function render_list() {
        $invoice = new FRF_Invoice();
        
        // Handle filters
        $status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
        $client_id = isset($_GET['client_id']) ? intval($_GET['client_id']) : 0;
        
        $args = array(
            'status' => $status,
            'client_id' => $client_id,
            'limit' => 20,
            'offset' => 0
        );
        
        $invoices = $invoice->get_all($args);
        
        // Get clients for filter
        $client_model = new FRF_Client();
        $clients = $client_model->get_all(array('limit' => 0));
        
        include FRF_PLUGIN_DIR . 'admin/views/invoices-list.php';
    }
    
    /**
     * Render add invoice form
     */
    private static function render_add_invoice() {
        // Handle form submission
        if (isset($_POST['frf_save_invoice'])) {
            check_admin_referer('frf_save_invoice');
            
            $result = self::save_invoice();
            
            if (is_wp_error($result)) {
                $error = $result->get_error_message();
            } else {
                wp_redirect(admin_url('admin.php?page=fatture-rf-invoices&view=view&id=' . $result));
                exit;
            }
        }
        
        $invoice_model = new FRF_Invoice();
        $invoice_number = $invoice_model->generate_invoice_number();
        
        // Get clients
        $client_model = new FRF_Client();
        $clients = $client_model->get_all(array('limit' => 0));
        
        // Get settings for defaults
        $settings = FRF_Settings::get_instance();
        
        include FRF_PLUGIN_DIR . 'admin/views/invoice-form.php';
    }
    
    /**
     * Render edit invoice form
     */
    private static function render_edit_invoice() {
        if (!isset($_GET['id'])) {
            wp_die(__('Invalid invoice ID', 'fatture-rf'));
        }
        
        $invoice_id = intval($_GET['id']);
        $invoice_model = new FRF_Invoice();
        $invoice = $invoice_model->get($invoice_id);
        
        if (!$invoice) {
            wp_die(__('Invoice not found', 'fatture-rf'));
        }
        
        // Handle form submission
        if (isset($_POST['frf_save_invoice'])) {
            check_admin_referer('frf_save_invoice');
            
            $result = self::save_invoice($invoice_id);
            
            if (is_wp_error($result)) {
                $error = $result->get_error_message();
            } else {
                wp_redirect(admin_url('admin.php?page=fatture-rf-invoices&view=view&id=' . $invoice_id));
                exit;
            }
        }
        
        // Get clients
        $client_model = new FRF_Client();
        $clients = $client_model->get_all(array('limit' => 0));
        
        $settings = FRF_Settings::get_instance();
        
        include FRF_PLUGIN_DIR . 'admin/views/invoice-form.php';
    }
    
    /**
     * Render invoice view
     */
    private static function render_view_invoice() {
        if (!isset($_GET['id'])) {
            wp_die(__('Invalid invoice ID', 'fatture-rf'));
        }
        
        $invoice_id = intval($_GET['id']);
        $invoice_model = new FRF_Invoice();
        $invoice = $invoice_model->get($invoice_id);
        
        if (!$invoice) {
            wp_die(__('Invoice not found', 'fatture-rf'));
        }
        
        // Get client
        $client_model = new FRF_Client();
        $client = $client_model->get($invoice->client_id);
        
        // Get status history
        $history = $invoice_model->get_status_history($invoice_id);
        
        // Get business info
        $settings = FRF_Settings::get_instance();
        $business = $settings->get_business_info();
        
        include FRF_PLUGIN_DIR . 'admin/views/invoice-view.php';
    }
    
    /**
     * Save invoice (create or update)
     */
    private static function save_invoice($invoice_id = 0) {
        $invoice_model = new FRF_Invoice();
        
        // Collect items
        $items = array();
        if (isset($_POST['items']) && is_array($_POST['items'])) {
            foreach ($_POST['items'] as $item) {
                if (!empty($item['description'])) {
                    $items[] = array(
                        'description' => sanitize_textarea_field($item['description']),
                        'quantity' => floatval($item['quantity']),
                        'unit_price' => floatval($item['unit_price']),
                        'total' => floatval($item['total'])
                    );
                }
            }
        }
        
        $data = array(
            'invoice_number' => sanitize_text_field($_POST['invoice_number']),
            'invoice_date' => sanitize_text_field($_POST['invoice_date']),
            'client_id' => intval($_POST['client_id']),
            'subtotal' => floatval($_POST['subtotal']),
            'tax_rate' => floatval($_POST['tax_rate'] ?? 0),
            'tax_amount' => floatval($_POST['tax_amount'] ?? 0),
            'total' => floatval($_POST['total']),
            'withholding_tax' => floatval($_POST['withholding_tax'] ?? 0),
            'withholding_amount' => floatval($_POST['withholding_amount'] ?? 0),
            'net_to_pay' => floatval($_POST['net_to_pay']),
            'payment_terms' => sanitize_text_field($_POST['payment_terms'] ?? ''),
            'payment_method' => sanitize_text_field($_POST['payment_method'] ?? ''),
            'notes' => sanitize_textarea_field($_POST['notes'] ?? ''),
            'status' => sanitize_text_field($_POST['status'] ?? 'draft'),
            'items' => $items
        );
        
        if ($invoice_id > 0) {
            return $invoice_model->update($invoice_id, $data);
        } else {
            return $invoice_model->create($data);
        }
    }
    
    /**
     * Get status badge HTML
     */
    public static function get_status_badge($status) {
        $statuses = array(
            'draft' => array('label' => __('Draft', 'fatture-rf'), 'class' => 'secondary'),
            'sent' => array('label' => __('Sent', 'fatture-rf'), 'class' => 'info'),
            'paid' => array('label' => __('Paid', 'fatture-rf'), 'class' => 'success'),
            'overdue' => array('label' => __('Overdue', 'fatture-rf'), 'class' => 'warning'),
            'cancelled' => array('label' => __('Cancelled', 'fatture-rf'), 'class' => 'error'),
            'submitted' => array('label' => __('SDI Submitted', 'fatture-rf'), 'class' => 'info'),
            'accepted' => array('label' => __('SDI Accepted', 'fatture-rf'), 'class' => 'success'),
            'rejected' => array('label' => __('SDI Rejected', 'fatture-rf'), 'class' => 'error')
        );
        
        $status_info = isset($statuses[$status]) ? $statuses[$status] : array('label' => $status, 'class' => 'default');
        
        return sprintf(
            '<span class="frf-badge frf-badge-%s">%s</span>',
            esc_attr($status_info['class']),
            esc_html($status_info['label'])
        );
    }
}