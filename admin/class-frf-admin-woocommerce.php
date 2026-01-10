<?php
/**
 * WooCommerce Admin Controller
 * Path: admin/class-frf-admin-woocommerce.php
 * Manages WooCommerce integration admin pages
 */

if (!defined('ABSPATH')) {
    exit;
}

class FRF_Admin_WooCommerce {
    
    /**
     * Render WooCommerce stores page
     */
    public static function render_stores() {
        $action = isset($_GET['view']) ? sanitize_text_field($_GET['view']) : 'list';
        
        switch ($action) {
            case 'add':
                self::render_add_store();
                break;
            case 'edit':
                self::render_edit_store();
                break;
            case 'sync':
                self::handle_sync_store();
                break;
            default:
                self::render_stores_list();
        }
    }
    
    /**
     * Render stores list
     */
    private static function render_stores_list() {
        $store_model = new FRF_WooCommerce_Store();
        $stores = $store_model->get_all_stores();
        
        include FRF_PLUGIN_DIR . 'admin/views/woocommerce/stores-list.php';
    }
    
    /**
     * Render add store form
     */
    private static function render_add_store() {
        if (isset($_POST['frf_save_store'])) {
            check_admin_referer('frf_save_store');
            
            $result = self::save_store();
            
            if (is_wp_error($result)) {
                $error = $result->get_error_message();
            } else {
                wp_redirect(admin_url('admin.php?page=fatture-rf-woocommerce&view=stores'));
                exit;
            }
        }
        
        include FRF_PLUGIN_DIR . 'admin/views/woocommerce/store-form.php';
    }
    
    /**
     * Render edit store form
     */
    private static function render_edit_store() {
        if (!isset($_GET['id'])) {
            wp_die(__('ID negozio non valido', 'fatture-rf'));
        }
        
        $store_id = intval($_GET['id']);
        $store_model = new FRF_WooCommerce_Store();
        $store = $store_model->get_store($store_id);
        
        if (!$store) {
            wp_die(__('Negozio non trovato', 'fatture-rf'));
        }
        
        if (isset($_POST['frf_save_store'])) {
            check_admin_referer('frf_save_store');
            
            $result = self::save_store($store_id);
            
            if (is_wp_error($result)) {
                $error = $result->get_error_message();
            } else {
                wp_redirect(admin_url('admin.php?page=fatture-rf-woocommerce&view=stores'));
                exit;
            }
        }
        
        include FRF_PLUGIN_DIR . 'admin/views/woocommerce/store-form.php';
    }
    
    /**
     * Save store
     */
    private static function save_store($store_id = 0) {
        $store_model = new FRF_WooCommerce_Store();
        
        $data = array(
            'store_name' => sanitize_text_field($_POST['store_name']),
            'store_url' => esc_url_raw($_POST['store_url']),
            'consumer_key' => sanitize_text_field($_POST['consumer_key']),
            'consumer_secret' => sanitize_text_field($_POST['consumer_secret']),
            'sync_from_date' => sanitize_text_field($_POST['sync_from_date']),
            'auto_sync' => isset($_POST['auto_sync']),
            'sync_interval' => intval($_POST['sync_interval']),
            'status' => sanitize_text_field($_POST['status'] ?? 'active')
        );
        
        if ($store_id > 0) {
            return $store_model->update_store($store_id, $data);
        } else {
            return $store_model->add_store($data);
        }
    }
    
    /**
     * Handle store sync
     */
    private static function handle_sync_store() {
        if (!isset($_GET['id'])) {
            wp_die(__('ID negozio non valido', 'fatture-rf'));
        }
        
        check_admin_referer('frf_sync_store');
        
        $store_id = intval($_GET['id']);
        $sync = new FRF_WooCommerce_Sync();
        
        $result = $sync->sync_store_orders($store_id);
        
        if (is_wp_error($result)) {
            add_settings_error('frf_messages', 'frf_message', 
                $result->get_error_message(), 'error');
        } else {
            $message = sprintf(
                __('Sincronizzati %d ordini su %d. Errori: %d', 'fatture-rf'),
                $result['synced'],
                $result['total'],
                count($result['errors'])
            );
            add_settings_error('frf_messages', 'frf_message', $message, 'success');
        }
        
        wp_redirect(admin_url('admin.php?page=fatture-rf-woocommerce&view=stores'));
        exit;
    }
    
    /**
     * Render synced orders page
     */
    public static function render_orders() {
        $sync = new FRF_WooCommerce_Sync();
        
        // Handle filters
        $store_id = isset($_GET['store_id']) ? intval($_GET['store_id']) : 0;
        $status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
        $has_invoice = isset($_GET['has_invoice']) ? sanitize_text_field($_GET['has_invoice']) : '';
        
        $args = array(
            'store_id' => $store_id,
            'status' => $status,
            'has_invoice' => $has_invoice,
            'limit' => 50
        );
        
        $orders = $sync->get_all_orders($args);
        
        // Get stores for filter
        $store_model = new FRF_WooCommerce_Store();
        $stores = $store_model->get_all_stores();
        
        include FRF_PLUGIN_DIR . 'admin/views/woocommerce/orders-list.php';
    }
    
    /**
     * Handle create invoice from order
     */
    public static function handle_create_invoice() {
        if (!isset($_GET['order_id'])) {
            wp_die(__('ID ordine non valido', 'fatture-rf'));
        }
        
        check_admin_referer('frf_create_invoice_from_order');
        
        $order_id = intval($_GET['order_id']);
        $sync = new FRF_WooCommerce_Sync();
        
        $invoice_id = $sync->create_invoice_from_order($order_id);
        
        if (is_wp_error($invoice_id)) {
            add_settings_error('frf_messages', 'frf_message', 
                $invoice_id->get_error_message(), 'error');
            wp_redirect(admin_url('admin.php?page=fatture-rf-woocommerce&view=orders'));
        } else {
            add_settings_error('frf_messages', 'frf_message', 
                __('Fattura creata con successo', 'fatture-rf'), 'success');
            wp_redirect(admin_url('admin.php?page=fatture-rf-invoices&view=view&id=' . $invoice_id));
        }
        exit;
    }
    
    /**
     * Get status badge HTML
     */
    public static function get_woo_status_badge($status) {
        $statuses = array(
            'pending' => array('label' => __('In attesa', 'fatture-rf'), 'class' => 'secondary'),
            'processing' => array('label' => __('In elaborazione', 'fatture-rf'), 'class' => 'info'),
            'on-hold' => array('label' => __('In sospeso', 'fatture-rf'), 'class' => 'warning'),
            'completed' => array('label' => __('Completato', 'fatture-rf'), 'class' => 'success'),
            'cancelled' => array('label' => __('Annullato', 'fatture-rf'), 'class' => 'error'),
            'refunded' => array('label' => __('Rimborsato', 'fatture-rf'), 'class' => 'error'),
            'failed' => array('label' => __('Fallito', 'fatture-rf'), 'class' => 'error')
        );
        
        $status_info = isset($statuses[$status]) ? $statuses[$status] : 
            array('label' => $status, 'class' => 'secondary');
        
        return sprintf(
            '<span class="frf-badge frf-badge-%s">%s</span>',
            esc_attr($status_info['class']),
            esc_html($status_info['label'])
        );
    }
    
    /**
     * AJAX: Test store connection
     */
    public static function ajax_test_connection() {
        check_ajax_referer('frf_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Accesso non autorizzato', 'fatture-rf')));
        }
        
        $store_url = esc_url_raw($_POST['store_url']);
        $consumer_key = sanitize_text_field($_POST['consumer_key']);
        $consumer_secret = sanitize_text_field($_POST['consumer_secret']);
        
        $store_model = new FRF_WooCommerce_Store();
        $result = $store_model->test_connection($store_url, $consumer_key, $consumer_secret);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }
        
        wp_send_json_success(array('message' => __('Connessione riuscita!', 'fatture-rf')));
    }
    
    /**
     * AJAX: Sync store
     */
    public static function ajax_sync_store() {
        check_ajax_referer('frf_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Accesso non autorizzato', 'fatture-rf')));
        }
        
        $store_id = intval($_POST['store_id']);
        $sync = new FRF_WooCommerce_Sync();
        
        $result = $sync->sync_store_orders($store_id);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }
        
        wp_send_json_success(array(
            'message' => sprintf(
                __('Sincronizzati %d ordini', 'fatture-rf'),
                $result['synced']
            ),
            'data' => $result
        ));
    }
}