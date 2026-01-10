<?php
/**
 * Plugin Name: Fatture Regime Forfettario
 * Plugin URI: https://codoplex.com
 * Description: Gestione fatture per regime forfettario compatibile con Agenzia delle Entrate
 * Version: 1.0.0
 * Author: Junaid Hassan
 * Author URI: https://codoplex.com
 * Text Domain: fatture-rf
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('FRF_VERSION', '1.0.0');
define('FRF_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('FRF_PLUGIN_URL', plugin_dir_url(__FILE__));
define('FRF_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main Plugin Class
 */
class Fatture_Regime_Forfettario {
    
    private static $instance = null;
    
    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->load_dependencies();
        $this->init_hooks();
    }
    
    /**
     * Load required files
     */
    private function load_dependencies() {
        // Core classes
        require_once FRF_PLUGIN_DIR . 'includes/class-frf-database.php';
        require_once FRF_PLUGIN_DIR . 'includes/class-frf-invoice.php';
        require_once FRF_PLUGIN_DIR . 'includes/class-frf-client.php';
        require_once FRF_PLUGIN_DIR . 'includes/class-frf-settings.php';
        require_once FRF_PLUGIN_DIR . 'includes/class-frf-pdf-generator.php';
        // NEW: WooCommerce Integration
        require_once FRF_PLUGIN_DIR . 'includes/class-frf-woo-store.php';
        require_once FRF_PLUGIN_DIR . 'includes/class-frf-woo-sync.php';    
        
        // Admin classes
        if (is_admin()) {
            require_once FRF_PLUGIN_DIR . 'admin/class-frf-admin.php';
            require_once FRF_PLUGIN_DIR . 'admin/class-frf-admin-invoices.php';
            require_once FRF_PLUGIN_DIR . 'admin/class-frf-admin-clients.php';
            require_once FRF_PLUGIN_DIR . 'admin/class-frf-admin-settings.php';
            require_once FRF_PLUGIN_DIR . 'admin/class-frf-admin-woocommerce.php';
        }
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        add_action('init', array($this, 'init'));
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        FRF_Database::create_tables();
        flush_rewrite_rules();

        // Schedule WooCommerce auto-sync
        if (!wp_next_scheduled('frf_woocommerce_auto_sync')) {
            wp_schedule_event(time(), 'hourly', 'frf_woocommerce_auto_sync');
        }
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        flush_rewrite_rules();
        // Remove scheduled events
        wp_clear_scheduled_hook('frf_woocommerce_auto_sync');
    }
    
    /**
     * Load text domain for translations
     */
    public function load_textdomain() {
        load_plugin_textdomain('fatture-rf', false, dirname(FRF_PLUGIN_BASENAME) . '/languages');
    }

    // NEW: Auto-sync method
    public function run_auto_sync() {
        $sync = new FRF_WooCommerce_Sync();
        $sync->sync_all_stores();
    }
    
    /**
     * Initialize plugin
     */
    public function init() {
        if (is_admin()) {
            FRF_Admin::get_instance();
        }

        // NEW: Register auto-sync action
        add_action('frf_woocommerce_auto_sync', array($this, 'run_auto_sync'));
    }
}

// Initialize plugin
function frf_init() {
    return Fatture_Regime_Forfettario::get_instance();
}

// Start the plugin
frf_init();