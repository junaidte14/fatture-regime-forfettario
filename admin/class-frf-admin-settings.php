<?php
/**
 * Admin Settings Page
 */

if (!defined('ABSPATH')) {
    exit;
}

class FRF_Admin_Settings {
    
    /**
     * Render settings page
     */
    public static function render() {
        $tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'general';
        
        // Handle form submission
        if (isset($_POST['frf_save_settings'])) {
            check_admin_referer('frf_save_settings');
            self::save_settings();
        }
        
        $settings = FRF_Settings::get_instance();
        $current_settings = $settings->get_all();
        
        include FRF_PLUGIN_DIR . 'admin/views/settings.php';
    }
    
    /**
     * Save settings
     */
    private static function save_settings() {
        $settings = FRF_Settings::get_instance();
        
        $new_settings = array();
        
        // Business information
        $new_settings['business_name'] = sanitize_text_field($_POST['business_name'] ?? '');
        $new_settings['vat_number'] = sanitize_text_field($_POST['vat_number'] ?? '');
        $new_settings['tax_code'] = sanitize_text_field($_POST['tax_code'] ?? '');
        $new_settings['address'] = sanitize_textarea_field($_POST['address'] ?? '');
        $new_settings['city'] = sanitize_text_field($_POST['city'] ?? '');
        $new_settings['province'] = sanitize_text_field($_POST['province'] ?? '');
        $new_settings['postal_code'] = sanitize_text_field($_POST['postal_code'] ?? '');
        $new_settings['country'] = sanitize_text_field($_POST['country'] ?? 'IT');
        $new_settings['email'] = sanitize_email($_POST['email'] ?? '');
        $new_settings['pec_email'] = sanitize_email($_POST['pec_email'] ?? '');
        $new_settings['phone'] = sanitize_text_field($_POST['phone'] ?? '');
        $new_settings['sdi_code'] = sanitize_text_field($_POST['sdi_code'] ?? '');
        
        // Invoice settings
        $new_settings['invoice_prefix'] = sanitize_text_field($_POST['invoice_prefix'] ?? 'FATT');
        $new_settings['default_payment_terms'] = sanitize_text_field($_POST['default_payment_terms'] ?? '');
        $new_settings['default_payment_method'] = sanitize_text_field($_POST['default_payment_method'] ?? '');
        $new_settings['default_notes'] = sanitize_textarea_field($_POST['default_notes'] ?? '');
        $new_settings['apply_withholding_tax'] = isset($_POST['apply_withholding_tax']);
        $new_settings['withholding_tax_rate'] = floatval($_POST['withholding_tax_rate'] ?? 20);
        
        // Regime Forfettario
        $new_settings['regime_forfettario'] = isset($_POST['regime_forfettario']);
        $new_settings['flat_tax_rate'] = floatval($_POST['flat_tax_rate'] ?? 15);
        $new_settings['exempt_vat'] = isset($_POST['exempt_vat']);
        
        // Email notifications
        $new_settings['enable_email_notifications'] = isset($_POST['enable_email_notifications']);
        $new_settings['notification_email'] = sanitize_email($_POST['notification_email'] ?? '');
        $new_settings['send_invoice_email'] = isset($_POST['send_invoice_email']);
        $new_settings['invoice_email_subject'] = sanitize_text_field($_POST['invoice_email_subject'] ?? '');
        $new_settings['invoice_email_body'] = sanitize_textarea_field($_POST['invoice_email_body'] ?? '');
        
        // Validate settings
        $validation = $settings->validate($new_settings);
        
        if (is_wp_error($validation)) {
            add_settings_error('frf_messages', 'frf_message', $validation->get_error_message(), 'error');
        } else {
            if ($settings->update($new_settings)) {
                add_settings_error('frf_messages', 'frf_message', __('Impostazioni salvate con successo', 'fatture-rf'), 'success');
            } else {
                add_settings_error('frf_messages', 'frf_message', __('Errore durante il salvataggio', 'fatture-rf'), 'error');
            }
        }
    }
    
    /**
     * Render tab navigation
     */
    public static function render_tabs($current_tab) {
        $tabs = array(
            'general' => __('Generale', 'fatture-rf'),
            'invoices' => __('Fatture', 'fatture-rf'),
            'regime' => __('Regime Forfettario', 'fatture-rf'),
            'email' => __('Email', 'fatture-rf')
        );
        
        echo '<h2 class="nav-tab-wrapper">';
        foreach ($tabs as $tab => $name) {
            $class = ($tab === $current_tab) ? ' nav-tab-active' : '';
            $url = admin_url('admin.php?page=fatture-rf-settings&tab=' . $tab);
            echo '<a class="nav-tab' . $class . '" href="' . esc_url($url) . '">' . esc_html($name) . '</a>';
        }
        echo '</h2>';
    }
}