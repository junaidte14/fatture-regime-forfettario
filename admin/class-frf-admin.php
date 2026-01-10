<?php
/**
 * Admin Controller Class
 * Manages admin interface and menu
 */

if (!defined('ABSPATH')) {
    exit;
}

class FRF_Admin {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_init', array($this, 'handle_actions'));

        // AJAX Actions
        add_action('wp_ajax_frf_export_invoice_pdf', array($this, 'ajax_export_pdf'));
        add_action('wp_ajax_frf_change_invoice_status', array($this, 'ajax_change_status'));
        add_action('wp_ajax_frf_search_clients', array($this, 'ajax_search_clients'));

        add_action('wp_ajax_frf_test_woo_connection', array($this, 'ajax_test_woo_connection'));
        add_action('wp_ajax_frf_sync_woo_store', array($this, 'ajax_sync_woo_store'));
        add_action('wp_ajax_frf_create_invoice_from_woo_order', array($this, 'ajax_create_invoice_from_order'));

        // Register WooCommerce auto-sync cron job
        add_action('frf_woocommerce_auto_sync', array($this, 'run_auto_sync'));
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        // Main menu
        add_menu_page(
            __('Fatture RF', 'fatture-rf'),
            __('Fatture RF', 'fatture-rf'),
            'manage_options',
            'fatture-rf',
            array($this, 'render_dashboard'),
            'dashicons-media-spreadsheet',
            30
        );
        
        // Dashboard
        add_submenu_page(
            'fatture-rf',
            __('Dashboard', 'fatture-rf'),
            __('Dashboard', 'fatture-rf'),
            'manage_options',
            'fatture-rf',
            array($this, 'render_dashboard')
        );
        
        // Invoices
        add_submenu_page(
            'fatture-rf',
            __('Invoices', 'fatture-rf'),
            __('Invoices', 'fatture-rf'),
            'manage_options',
            'fatture-rf-invoices',
            array('FRF_Admin_Invoices', 'render')
        );
        
        // Clients
        add_submenu_page(
            'fatture-rf',
            __('Clients', 'fatture-rf'),
            __('Clients', 'fatture-rf'),
            'manage_options',
            'fatture-rf-clients',
            array('FRF_Admin_Clients', 'render')
        );

        add_submenu_page(
            'fatture-rf',
            __('WooCommerce', 'fatture-rf'),
            __('WooCommerce', 'fatture-rf'),
            'manage_options',
            'fatture-rf-woocommerce',
            array($this, 'render_woocommerce')
        );
        
        // Settings
        add_submenu_page(
            'fatture-rf',
            __('Settings', 'fatture-rf'),
            __('Settings', 'fatture-rf'),
            'manage_options',
            'fatture-rf-settings',
            array('FRF_Admin_Settings', 'render')
        );
    }

    public function render_woocommerce() {
        $view = isset($_GET['view']) ? sanitize_text_field($_GET['view']) : 'stores';
        
        if ($view === 'orders') {
            FRF_Admin_WooCommerce::render_orders();
        } else {
            FRF_Admin_WooCommerce::render_stores();
        }
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_scripts($hook) {
        // Only load on plugin pages
        if (strpos($hook, 'fatture-rf') === false) {
            return;
        }
        
        // Styles
        wp_enqueue_style(
            'frf-admin-style',
            FRF_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            FRF_VERSION
        );
        
        // Scripts
        wp_enqueue_script(
            'frf-admin-script',
            FRF_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            FRF_VERSION,
            true
        );
        
        // Localize script
        wp_localize_script('frf-admin-script', 'frfAdmin', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('frf_admin_nonce'),
            'confirmDelete' => __('Are you sure you want to delete this item?', 'fatture-rf')
        ));
    }
    
    /**
     * Handle admin actions
     */
    public function handle_actions() {
        if (!isset($_GET['page']) || strpos($_GET['page'], 'fatture-rf') !== 0) {
            return;
        }
        
        if (!isset($_GET['action']) || !isset($_GET['_wpnonce'])) {
            return;
        }
        
        if (!wp_verify_nonce($_GET['_wpnonce'], 'frf_action')) {
            wp_die(__('Unauthorized action', 'fatture-rf'));
        }
        
        $action = sanitize_text_field($_GET['action']);
        
        // Handle different actions
        switch ($action) {
            case 'delete_invoice':
                $this->handle_delete_invoice();
                break;
            case 'delete_client':
                $this->handle_delete_client();
                break;
        }
    }
    
    /**
     * Handle invoice deletion
     */
    private function handle_delete_invoice() {
        if (!isset($_GET['id'])) {
            return;
        }
        
        $invoice_id = intval($_GET['id']);
        $invoice = new FRF_Invoice();
        
        if ($invoice->delete($invoice_id)) {
            $this->add_admin_notice('success', __('Invoice deleted successfully', 'fatture-rf'));
        } else {
            $this->add_admin_notice('error', __('Errore durante l\'eliminazione della fattura', 'fatture-rf'));
        }
        
        wp_redirect(admin_url('admin.php?page=fatture-rf-invoices'));
        exit;
    }
    
    /**
     * Handle client deletion
     */
    private function handle_delete_client() {
        if (!isset($_GET['id'])) {
            return;
        }
        
        $client_id = intval($_GET['id']);
        $client = new FRF_Client();
        
        $result = $client->delete($client_id);
        
        if (is_wp_error($result)) {
            $this->add_admin_notice('error', $result->get_error_message());
        } else {
            $this->add_admin_notice('success', __('Client deleted successfully', 'fatture-rf'));
        }
        
        wp_redirect(admin_url('admin.php?page=fatture-rf-clients'));
        exit;
    }
    
    /**
     * Add admin notice
     */
    private function add_admin_notice($type, $message) {
        add_settings_error('frf_messages', 'frf_message', $message, $type);
    }
    
    /**
     * Render dashboard
     */
    public function render_dashboard() {
        global $wpdb;
        
        $invoice = new FRF_Invoice();
        $client = new FRF_Client();
        
        // Get statistics
        $invoices_table = FRF_Database::get_table_name('invoices');
        
        $stats = $wpdb->get_row("
            SELECT 
                COUNT(*) as total_invoices,
                SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END) as paid_invoices,
                SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft_invoices,
                SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent_invoices,
                SUM(total) as total_amount,
                SUM(CASE WHEN status = 'paid' THEN total ELSE 0 END) as paid_amount,
                SUM(CASE WHEN status IN ('sent', 'overdue') THEN total ELSE 0 END) as outstanding_amount
            FROM {$invoices_table}
        ");
        
        $total_clients = $wpdb->get_var("SELECT COUNT(*) FROM " . FRF_Database::get_table_name('clients'));
        
        // Recent invoices
        $recent_invoices = $invoice->get_all(array('limit' => 5, 'orderby' => 'created_at', 'order' => 'DESC'));
        
        include FRF_PLUGIN_DIR . 'admin/views/dashboard.php';
    }

    /**
     * Handle PDF export via AJAX
     */
    public function ajax_export_pdf() {
        check_ajax_referer('frf_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized access', 'fatture-rf'));
        }
        
        if (!isset($_GET['invoice_id'])) {
            wp_die(__('Invalid invoice ID', 'fatture-rf'));
        }
        
        $invoice_id = intval($_GET['invoice_id']);
        
        // Load PDF generator
        require_once FRF_PLUGIN_DIR . 'includes/class-frf-pdf-generator.php';
        
        $pdf_generator = new FRF_PDF_Generator();
        $result = $pdf_generator->generate($invoice_id);
        
        if (is_wp_error($result)) {
            wp_die($result->get_error_message());
        }
        
        exit;
    }

    /**
     * Handle invoice status change via AJAX
     */
    public function ajax_change_status() {
        check_ajax_referer('frf_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Unauthorized access', 'fatture-rf')));
        }
        
        $invoice_id = intval($_POST['invoice_id']);
        $new_status = sanitize_text_field($_POST['status']);
        
        $invoice_model = new FRF_Invoice();
        $result = $invoice_model->update($invoice_id, array('status' => $new_status));
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }
        
        wp_send_json_success(array('message' => __('Stato aggiornato con successo', 'fatture-rf')));
    }

    /**
     * Handle client search via AJAX
     */
    public function ajax_search_clients() {
        check_ajax_referer('frf_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Unauthorized access', 'fatture-rf')));
        }
        
        $search = sanitize_text_field($_POST['search']);
        
        $client_model = new FRF_Client();
        $clients = $client_model->get_all(array(
            'search' => $search,
            'limit' => 20
        ));
        
        $results = array();
        foreach ($clients as $client) {
            $results[] = array(
                'id' => $client->id,
                'business_name' => $client->business_name,
                'vat_number' => $client->vat_number,
                'city' => $client->city
            );
        }
        
        wp_send_json_success($results);
    }

    /**
     * AJAX: Test WooCommerce connection
     */
    public function ajax_test_woo_connection() {
        FRF_Admin_WooCommerce::ajax_test_connection();
    }

    /**
     * AJAX: Sync WooCommerce store
     */
    public function ajax_sync_woo_store() {
        FRF_Admin_WooCommerce::ajax_sync_store();
    }

    /**
     * AJAX: Create invoice from WooCommerce order
     */
    public function ajax_create_invoice_from_order() {
        check_ajax_referer('frf_admin_nonce', 'nonce');   
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Accesso non autorizzato', 'fatture-rf')));
        }
        $order_id = intval($_POST['order_id']);
        $sync = new FRF_WooCommerce_Sync();
        $invoice_id = $sync->create_invoice_from_order($order_id);
        if (is_wp_error($invoice_id)) {
            wp_send_json_error(array('message' => $invoice_id->get_error_message()));
        }
        wp_send_json_success(array(
            'message' => __('Fattura creata con successo', 'fatture-rf'),
            'invoice_id' => $invoice_id
        ));
    }

    /**
     * Run auto-sync for all stores
     */
    public function run_auto_sync() {
        $sync = new FRF_WooCommerce_Sync();
        $sync->sync_all_stores();
    }

}