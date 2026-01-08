<?php
/**
 * Settings Management Class
 */

if (!defined('ABSPATH')) {
    exit;
}

class FRF_Settings {
    
    private static $instance = null;
    private $option_name = 'frf_settings';
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Get all settings
     */
    public function get_all() {
        return get_option($this->option_name, $this->get_defaults());
    }
    
    /**
     * Get single setting
     */
    public function get($key, $default = '') {
        $settings = $this->get_all();
        return isset($settings[$key]) ? $settings[$key] : $default;
    }
    
    /**
     * Update settings
     */
    public function update($settings) {
        $current = $this->get_all();
        $updated = array_merge($current, $settings);
        return update_option($this->option_name, $updated);
    }
    
    /**
     * Get default settings
     */
    private function get_defaults() {
        return array(
            // Business information
            'business_name' => '',
            'vat_number' => '',
            'tax_code' => '',
            'address' => '',
            'city' => '',
            'province' => '',
            'postal_code' => '',
            'country' => 'IT',
            'email' => '',
            'pec_email' => '',
            'phone' => '',
            'sdi_code' => '',
            
            // Invoice settings
            'invoice_prefix' => 'FATT',
            'next_invoice_number' => 1,
            'default_payment_terms' => '30 giorni data fattura',
            'default_payment_method' => 'Bonifico bancario',
            'default_notes' => '',
            'apply_withholding_tax' => false,
            'withholding_tax_rate' => 20.00,
            
            // Regime Forfettario settings
            'regime_forfettario' => true,
            'regime_type' => 'forfettario',
            'start_date' => '',
            'flat_tax_rate' => 15.00, // Can be 5% or 15%
            'exempt_vat' => true,
            
            // Email notifications
            'enable_email_notifications' => true,
            'notification_email' => get_option('admin_email'),
            'send_invoice_email' => true,
            'invoice_email_subject' => 'Nuova fattura #{invoice_number}',
            'invoice_email_body' => 'In allegato la fattura n. {invoice_number} del {invoice_date}.',
            
            // Advanced
            'currency' => 'EUR',
            'currency_symbol' => '€',
            'date_format' => 'd/m/Y',
            'decimal_separator' => ',',
            'thousands_separator' => '.',
            'enable_debug' => false
        );
    }
    
    /**
     * Get business information
     */
    public function get_business_info() {
        $settings = $this->get_all();
        return array(
            'business_name' => $settings['business_name'],
            'vat_number' => $settings['vat_number'],
            'tax_code' => $settings['tax_code'],
            'address' => $settings['address'],
            'city' => $settings['city'],
            'province' => $settings['province'],
            'postal_code' => $settings['postal_code'],
            'country' => $settings['country'],
            'email' => $settings['email'],
            'pec_email' => $settings['pec_email'],
            'phone' => $settings['phone'],
            'sdi_code' => $settings['sdi_code']
        );
    }
    
    /**
     * Validate settings
     */
    public function validate($settings) {
        $errors = array();
        
        // Validate VAT number format for IT
        if (!empty($settings['vat_number']) && $settings['country'] === 'IT') {
            if (!preg_match('/^\d{11}$/', $settings['vat_number'])) {
                $errors[] = __('P.IVA italiana non valida', 'fatture-rf');
            }
        }
        
        // Validate Tax Code format for IT
        if (!empty($settings['tax_code']) && $settings['country'] === 'IT') {
            if (!preg_match('/^[A-Z0-9]{16}$/i', $settings['tax_code'])) {
                $errors[] = __('Codice Fiscale non valido', 'fatture-rf');
            }
        }
        
        // Validate email
        if (!empty($settings['email']) && !is_email($settings['email'])) {
            $errors[] = __('Email non valida', 'fatture-rf');
        }
        
        // Validate PEC email
        if (!empty($settings['pec_email']) && !is_email($settings['pec_email'])) {
            $errors[] = __('Email PEC non valida', 'fatture-rf');
        }
        
        // Validate tax rates
        if (isset($settings['flat_tax_rate'])) {
            $rate = floatval($settings['flat_tax_rate']);
            if ($rate !== 5.0 && $rate !== 15.0) {
                $errors[] = __('Aliquota forfettaria deve essere 5% o 15%', 'fatture-rf');
            }
        }
        
        if (!empty($errors)) {
            return new WP_Error('validation_error', implode('<br>', $errors));
        }
        
        return true;
    }
}