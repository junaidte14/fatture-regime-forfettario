<?php
/**
 * SDI Connector Class
 * Path: includes/class-frf-sdi-connector.php
 * Handles submission of invoices to Italian SDI (Sistema di Interscambio)
 */

if (!defined('ABSPATH')) {
    exit;
}

class FRF_SDI_Connector {
    
    private $settings;
    
    public function __construct() {
        $this->settings = FRF_Settings::get_instance();
    }
    
    /**
     * Send invoice to SDI via PEC
     */
    public function send_via_pec($invoice_id) {
        // Generate XML
        $xml_generator = new FRF_XML_Generator();
        $xml_string = $xml_generator->generate($invoice_id);
        
        if (is_wp_error($xml_string)) {
            return $xml_string;
        }
        
        // Validate XML
        $validation = $xml_generator->validate_xml($xml_string);
        if (is_wp_error($validation)) {
            return $validation;
        }
        
        // Save XML to file
        $filepath = $xml_generator->save_to_file($invoice_id);
        
        if (is_wp_error($filepath)) {
            return $filepath;
        }
        
        // Get invoice
        $invoice_model = new FRF_Invoice();
        $invoice = $invoice_model->get($invoice_id);
        
        // PEC details
        $pec_from = $this->settings->get('pec_email');
        $pec_to = 'sdi01@pec.fatturapa.it'; // SDI PEC address
        
        if (empty($pec_from)) {
            return new WP_Error('no_pec', __('PEC aziendale non configurata', 'fatture-rf'));
        }
        
        // Prepare email
        $subject = 'Fattura ' . $invoice->invoice_number;
        $message = 'Invio fattura elettronica al Sistema di Interscambio.';
        
        // Attachments
        $attachments = array($filepath);
        
        // Send email
        $sent = wp_mail($pec_to, $subject, $message, array(
            'From: ' . $pec_from,
            'Content-Type: text/plain; charset=UTF-8'
        ), $attachments);
        
        if ($sent) {
            // Update invoice status
            $invoice_model->update($invoice_id, array(
                'status' => 'submitted',
                'submission_date' => current_time('mysql'),
                'xml_file_path' => $filepath
            ));
            
            return true;
        }
        
        return new WP_Error('send_failed', __('Invio PEC fallito', 'fatture-rf'));
    }
    
    /**
     * Send invoice via API (if implementing direct API connection)
     * This is placeholder for future implementation with direct SDI API
     */
    public function send_via_api($invoice_id) {
        // Generate XML
        $xml_generator = new FRF_XML_Generator();
        $xml_string = $xml_generator->generate($invoice_id);
        
        if (is_wp_error($xml_string)) {
            return $xml_string;
        }
        
        // TODO: Implement direct API connection to SDI
        // This would require:
        // 1. Digital signature (firma digitale)
        // 2. Authentication credentials
        // 3. Web service integration
        
        return new WP_Error('not_implemented', 
            __('Invio diretto via API non ancora implementato', 'fatture-rf'));
    }
    
    /**
     * Check status of submitted invoice
     * Parse SDI response notifications
     */
    public function check_status($invoice_id) {
        $invoice_model = new FRF_Invoice();
        $invoice = $invoice_model->get($invoice_id);
        
        if (!$invoice || empty($invoice->sdi_identifier)) {
            return new WP_Error('no_submission', 
                __('Fattura non ancora inviata a SDI', 'fatture-rf'));
        }
        
        // TODO: Implement status check
        // This involves parsing notification emails from SDI:
        // - NS (Notifica Scarto) - Rejection
        // - MC (Mancata Consegna) - Delivery failure  
        // - RC (Ricevuta Consegna) - Delivery receipt
        // - AT (Attestazione di Trasmissione) - Transmission attestation
        // - DT (Decorrenza Termini) - Terms expiry
        
        return array(
            'status' => $invoice->status,
            'submission_date' => $invoice->submission_date,
            'sdi_identifier' => $invoice->sdi_identifier
        );
    }
    
    /**
     * Parse SDI notification email
     */
    public function parse_notification($email_content) {
        // TODO: Parse XML notification from SDI
        // Extract notification type and details
        
        // Notification types:
        // - NS: Notifica Scarto (Rejection)
        // - MC: Mancata Consegna (Delivery failure)
        // - RC: Ricevuta Consegna (Delivery receipt)
        // - AT: Attestazione di Trasmissione
        // - DT: Decorrenza Termini
        // - EC: Esito Committente (Customer outcome)
        
        return array(
            'type' => '',
            'invoice_number' => '',
            'details' => ''
        );
    }
    
    /**
     * Handle SDI notification and update invoice status
     */
    public function handle_notification($notification) {
        $invoice_model = new FRF_Invoice();
        
        $status_map = array(
            'NS' => 'rejected',  // Notifica Scarto
            'MC' => 'rejected',  // Mancata Consegna
            'RC' => 'sent',      // Ricevuta Consegna
            'AT' => 'sent',      // Attestazione Trasmissione
            'DT' => 'accepted',  // Decorrenza Termini
            'EC' => 'accepted'   // Esito Committente positive
        );
        
        if (isset($status_map[$notification['type']])) {
            $new_status = $status_map[$notification['type']];
            
            // Find invoice by number
            // Update status
            // Log notification
        }
    }
}