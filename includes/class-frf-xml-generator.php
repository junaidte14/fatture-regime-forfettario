<?php
/**
 * XML FatturaPA Generator Class
 * Path: includes/class-frf-xml-generator.php
 * Generates XML invoices compatible with Italian SDI (Sistema di Interscambio)
 * Format: FatturaPA v1.2.2
 */

if (!defined('ABSPATH')) {
    exit;
}

class FRF_XML_Generator {
    
    private $invoice;
    private $client;
    private $business;
    private $settings;
    private $xml;
    
    /**
     * Generate XML for invoice
     */
    public function generate($invoice_id) {
        // Load invoice data
        $invoice_model = new FRF_Invoice();
        $this->invoice = $invoice_model->get($invoice_id);
        
        if (!$this->invoice) {
            return new WP_Error('not_found', __('Fattura non trovata', 'fatture-rf'));
        }
        
        // Load client
        $client_model = new FRF_Client();
        $this->client = $client_model->get($this->invoice->client_id);
        
        // Load business settings
        $this->settings = FRF_Settings::get_instance();
        $this->business = $this->settings->get_business_info();
        
        // Validate required data
        $validation = $this->validate_data();
        if (is_wp_error($validation)) {
            return $validation;
        }
        
        // Create XML
        $this->xml = new DOMDocument('1.0', 'UTF-8');
        $this->xml->formatOutput = true;
        
        // Create root element
        $root = $this->xml->createElementNS(
            'http://ivaservizi.agenziaentrate.gov.it/docs/xsd/fatture/v1.2',
            'p:FatturaElettronica'
        );
        $root->setAttribute('versione', 'FPA12');
        $this->xml->appendChild($root);
        
        // Add Header
        $this->add_header($root);
        
        // Add Body
        $this->add_body($root);
        
        return $this->xml->saveXML();
    }
    
    /**
     * Validate required data for XML generation
     */
    private function validate_data() {
        $errors = array();
        
        // Business validation
        if (empty($this->business['vat_number'])) {
            $errors[] = __('P.IVA aziendale mancante', 'fatture-rf');
        }
        if (empty($this->business['country'])) {
            $errors[] = __('Paese aziendale mancante', 'fatture-rf');
        }
        
        // Client validation - Italian clients
        if ($this->client->client_type === 'IT') {
            if (empty($this->client->vat_number) && empty($this->client->tax_code)) {
                $errors[] = __('Cliente italiano: richiesta P.IVA o Codice Fiscale', 'fatture-rf');
            }
            if (empty($this->client->sdi_code) && empty($this->client->pec_email)) {
                $errors[] = __('Cliente italiano: richiesto Codice SDI o PEC', 'fatture-rf');
            }
        }
        
        // Invoice validation
        if (empty($this->invoice->items)) {
            $errors[] = __('Fattura senza voci', 'fatture-rf');
        }
        
        if (!empty($errors)) {
            return new WP_Error('validation_error', implode('; ', $errors));
        }
        
        return true;
    }
    
    /**
     * Add FatturaElettronicaHeader section
     */
    private function add_header($root) {
        $header = $this->xml->createElement('FatturaElettronicaHeader');
        $root->appendChild($header);
        
        // DatiTrasmissione
        $this->add_transmission_data($header);
        
        // CedentePrestatore (Supplier)
        $this->add_supplier_data($header);
        
        // CessionarioCommittente (Customer)
        $this->add_customer_data($header);
    }
    
    /**
     * Add DatiTrasmissione (Transmission Data)
     */
    private function add_transmission_data($header) {
        $dati = $this->xml->createElement('DatiTrasmissione');
        $header->appendChild($dati);
        
        // IdTrasmittente
        $id_tras = $this->xml->createElement('IdTrasmittente');
        $dati->appendChild($id_tras);
        
        $this->add_element($id_tras, 'IdPaese', $this->business['country']);
        $this->add_element($id_tras, 'IdCodice', $this->business['vat_number']);
        
        // ProgressivoInvio - Progressive number (can be based on invoice number)
        $progressive = str_replace('/', '', $this->invoice->invoice_number);
        $this->add_element($dati, 'ProgressivoInvio', $progressive);
        
        // FormatoTrasmissione - Format (FPA12 for B2B, FPR12 for B2C)
        $formato = $this->client->client_type === 'IT' ? 'FPA12' : 'FPR12';
        $this->add_element($dati, 'FormatoTrasmissione', $formato);
        
        // CodiceDestinatario - Destination code (SDI code or 0000000/XXXXXXX for PEC)
        $codice_dest = !empty($this->client->sdi_code) ? $this->client->sdi_code : '0000000';
        $this->add_element($dati, 'CodiceDestinatario', $codice_dest);
        
        // PECDestinatario - If using PEC
        if (!empty($this->client->pec_email) && empty($this->client->sdi_code)) {
            $this->add_element($dati, 'PECDestinatario', $this->client->pec_email);
        }
    }
    
    /**
     * Add CedentePrestatore (Supplier Data)
     */
    private function add_supplier_data($header) {
        $cedente = $this->xml->createElement('CedentePrestatore');
        $header->appendChild($cedente);
        
        // DatiAnagrafici
        $dati_anag = $this->xml->createElement('DatiAnagrafici');
        $cedente->appendChild($dati_anag);
        
        // IdFiscaleIVA
        $id_fisc = $this->xml->createElement('IdFiscaleIVA');
        $dati_anag->appendChild($id_fisc);
        $this->add_element($id_fisc, 'IdPaese', $this->business['country']);
        $this->add_element($id_fisc, 'IdCodice', $this->business['vat_number']);
        
        // CodiceFiscale (if different from VAT)
        if (!empty($this->business['tax_code']) && $this->business['tax_code'] !== $this->business['vat_number']) {
            $this->add_element($dati_anag, 'CodiceFiscale', $this->business['tax_code']);
        }
        
        // Anagrafica
        $anagrafica = $this->xml->createElement('Anagrafica');
        $dati_anag->appendChild($anagrafica);
        $this->add_element($anagrafica, 'Denominazione', $this->business['business_name']);
        
        // RegimeFiscale - RF01 for Regime Forfettario
        $regime = $this->settings->get('regime_forfettario') ? 'RF01' : 'RF02';
        $this->add_element($dati_anag, 'RegimeFiscale', $regime);
        
        // Sede (Registered Office)
        $sede = $this->xml->createElement('Sede');
        $cedente->appendChild($sede);
        
        $this->add_element($sede, 'Indirizzo', $this->business['address']);
        $this->add_element($sede, 'CAP', $this->business['postal_code']);
        $this->add_element($sede, 'Comune', $this->business['city']);
        if (!empty($this->business['province'])) {
            $this->add_element($sede, 'Provincia', $this->business['province']);
        }
        $this->add_element($sede, 'Nazione', $this->business['country']);
    }
    
    /**
     * Add CessionarioCommittente (Customer Data)
     */
    private function add_customer_data($header) {
        $cessionario = $this->xml->createElement('CessionarioCommittente');
        $header->appendChild($cessionario);
        
        // DatiAnagrafici
        $dati_anag = $this->xml->createElement('DatiAnagrafici');
        $cessionario->appendChild($dati_anag);
        
        // IdFiscaleIVA (only if customer has VAT)
        if (!empty($this->client->vat_number)) {
            $id_fisc = $this->xml->createElement('IdFiscaleIVA');
            $dati_anag->appendChild($id_fisc);
            $this->add_element($id_fisc, 'IdPaese', $this->client->country);
            $this->add_element($id_fisc, 'IdCodice', $this->client->vat_number);
        }
        
        // CodiceFiscale (for Italian customers without VAT or as alternative)
        if (!empty($this->client->tax_code)) {
            $this->add_element($dati_anag, 'CodiceFiscale', $this->client->tax_code);
        }
        
        // Anagrafica
        $anagrafica = $this->xml->createElement('Anagrafica');
        $dati_anag->appendChild($anagrafica);
        $this->add_element($anagrafica, 'Denominazione', $this->client->business_name);
        
        // Sede
        $sede = $this->xml->createElement('Sede');
        $cessionario->appendChild($sede);
        
        $this->add_element($sede, 'Indirizzo', $this->client->address ?: 'N/A');
        $this->add_element($sede, 'CAP', $this->client->postal_code ?: '00000');
        $this->add_element($sede, 'Comune', $this->client->city ?: 'N/A');
        if (!empty($this->client->province)) {
            $this->add_element($sede, 'Provincia', $this->client->province);
        }
        $this->add_element($sede, 'Nazione', $this->client->country);
    }
    
    /**
     * Add FatturaElettronicaBody section
     */
    private function add_body($root) {
        $body = $this->xml->createElement('FatturaElettronicaBody');
        $root->appendChild($body);
        
        // DatiGenerali
        $this->add_general_data($body);
        
        // DatiBeniServizi (Goods and Services)
        $this->add_goods_services($body);
        
        // DatiPagamento (Payment data)
        $this->add_payment_data($body);
    }
    
    /**
     * Add DatiGenerali (General Data)
     */
    private function add_general_data($body) {
        $dati_gen = $this->xml->createElement('DatiGenerali');
        $body->appendChild($dati_gen);
        
        // DatiGeneraliDocumento
        $dati_doc = $this->xml->createElement('DatiGeneraliDocumento');
        $dati_gen->appendChild($dati_doc);
        
        // TipoDocumento - TD01 for invoice
        $this->add_element($dati_doc, 'TipoDocumento', 'TD01');
        
        // Divisa - Currency
        $this->add_element($dati_doc, 'Divisa', 'EUR');
        
        // Data and Numero
        $this->add_element($dati_doc, 'Data', date('Y-m-d', strtotime($this->invoice->invoice_date)));
        $this->add_element($dati_doc, 'Numero', $this->invoice->invoice_number);
        
        // Importo totale
        $this->add_element($dati_doc, 'ImportoTotaleDocumento', 
            number_format($this->invoice->total, 2, '.', ''));
        
        // Causale - Description/Notes
        if (!empty($this->invoice->notes)) {
            $this->add_element($dati_doc, 'Causale', substr($this->invoice->notes, 0, 200));
        }
    }
    
    /**
     * Add DatiBeniServizi (Goods and Services Data)
     */
    private function add_goods_services($body) {
        $dati_beni = $this->xml->createElement('DatiBeniServizi');
        $body->appendChild($dati_beni);
        
        // DettaglioLinee - Invoice items
        foreach ($this->invoice->items as $index => $item) {
            $dettaglio = $this->xml->createElement('DettaglioLinee');
            $dati_beni->appendChild($dettaglio);
            
            $this->add_element($dettaglio, 'NumeroLinea', $index + 1);
            $this->add_element($dettaglio, 'Descrizione', $item->description);
            $this->add_element($dettaglio, 'Quantita', number_format($item->quantity, 2, '.', ''));
            $this->add_element($dettaglio, 'PrezzoUnitario', number_format($item->unit_price, 2, '.', ''));
            $this->add_element($dettaglio, 'PrezzoTotale', number_format($item->total, 2, '.', ''));
            
            // AliquotaIVA - VAT rate (0 for regime forfettario)
            $aliquota_iva = $this->invoice->tax_rate > 0 ? number_format($this->invoice->tax_rate, 2, '.', '') : '0.00';
            $this->add_element($dettaglio, 'AliquotaIVA', $aliquota_iva);
            
            // Natura - Nature of operation (N2.2 for regime forfettario)
            if ($this->settings->get('regime_forfettario') && $this->invoice->tax_rate == 0) {
                $this->add_element($dettaglio, 'Natura', 'N2.2');
            }
        }
        
        // DatiRiepilogo - Summary
        $riepilogo = $this->xml->createElement('DatiRiepilogo');
        $dati_beni->appendChild($riepilogo);
        
        $aliquota_iva = $this->invoice->tax_rate > 0 ? number_format($this->invoice->tax_rate, 2, '.', '') : '0.00';
        $this->add_element($riepilogo, 'AliquotaIVA', $aliquota_iva);
        
        // Natura for regime forfettario
        if ($this->settings->get('regime_forfettario') && $this->invoice->tax_rate == 0) {
            $this->add_element($riepilogo, 'Natura', 'N2.2');
            $this->add_element($riepilogo, 'RiferimentoNormativo', 
                'Operazione effettuata ai sensi dell\'art. 1, commi 54-89, Legge n. 190/2014');
        }
        
        $this->add_element($riepilogo, 'ImponibileImporto', number_format($this->invoice->subtotal, 2, '.', ''));
        $this->add_element($riepilogo, 'Imposta', number_format($this->invoice->tax_amount, 2, '.', ''));
        
        // Ritenuta - Withholding tax
        if ($this->invoice->withholding_tax > 0) {
            $this->add_element($riepilogo, 'TipoRitenuta', 'RT02');
            $this->add_element($riepilogo, 'ImportoRitenuta', number_format($this->invoice->withholding_amount, 2, '.', ''));
            $this->add_element($riepilogo, 'AliquotaRitenuta', number_format($this->invoice->withholding_tax, 2, '.', ''));
            $this->add_element($riepilogo, 'CausalePagamento', 'A');
        }
    }
    
    /**
     * Add DatiPagamento (Payment Data)
     */
    private function add_payment_data($body) {
        $dati_pag = $this->xml->createElement('DatiPagamento');
        $body->appendChild($dati_pag);
        
        // CondizioniPagamento - Payment terms (TP02 = full payment)
        $this->add_element($dati_pag, 'CondizioniPagamento', 'TP02');
        
        // DettaglioPagamento
        $dettaglio_pag = $this->xml->createElement('DettaglioPagamento');
        $dati_pag->appendChild($dettaglio_pag);
        
        // ModalitaPagamento - Payment method
        $modalita = $this->get_payment_method_code($this->invoice->payment_method);
        $this->add_element($dettaglio_pag, 'ModalitaPagamento', $modalita);
        
        // ImportoPagamento
        $this->add_element($dettaglio_pag, 'ImportoPagamento', 
            number_format($this->invoice->net_to_pay, 2, '.', ''));
    }
    
    /**
     * Map payment method to FatturaPA code
     */
    private function get_payment_method_code($payment_method) {
        $methods = array(
            'Bonifico bancario' => 'MP05',
            'Contanti' => 'MP01',
            'Assegno' => 'MP02',
            'RID' => 'MP19',
            'PayPal' => 'MP08'
        );
        
        return isset($methods[$payment_method]) ? $methods[$payment_method] : 'MP05';
    }
    
    /**
     * Add element with text
     */
    private function add_element($parent, $name, $value) {
        if ($value !== null && $value !== '') {
            $element = $this->xml->createElement($name, htmlspecialchars($value, ENT_XML1));
            $parent->appendChild($element);
            return $element;
        }
        return null;
    }
    
    /**
     * Validate XML against XSD schema
     */
    public function validate_xml($xml_string) {
        $dom = new DOMDocument();
        $dom->loadXML($xml_string);
        
        // Path to FatturaPA XSD schema (should be downloaded and stored)
        $xsd_path = FRF_PLUGIN_DIR . 'includes/schema/Schema_FatturaPA_v1.2.2.xsd';
        
        if (!file_exists($xsd_path)) {
            return new WP_Error('schema_missing', 
                __('Schema XSD non trovato. Scaricalo da fatturapa.gov.it', 'fatture-rf'));
        }
        
        libxml_use_internal_errors(true);
        
        if (!$dom->schemaValidate($xsd_path)) {
            $errors = libxml_get_errors();
            $error_messages = array();
            
            foreach ($errors as $error) {
                $error_messages[] = sprintf('[Line %d] %s', $error->line, $error->message);
            }
            
            libxml_clear_errors();
            
            return new WP_Error('validation_error', implode("\n", $error_messages));
        }
        
        return true;
    }
    
    /**
     * Save XML to file
     */
    public function save_to_file($invoice_id, $directory = null) {
        $xml_string = $this->generate($invoice_id);
        
        if (is_wp_error($xml_string)) {
            return $xml_string;
        }
        
        if (!$directory) {
            $upload_dir = wp_upload_dir();
            $directory = $upload_dir['basedir'] . '/fatture-rf/xml/';
        }
        
        // Create directory if it doesn't exist
        if (!file_exists($directory)) {
            wp_mkdir_p($directory);
        }
        
        // Country code + VAT + progressive
        $filename = sprintf(
            '%s%s_%s.xml',
            $this->business['country'],
            $this->business['vat_number'],
            str_replace('/', '_', $this->invoice->invoice_number)
        );
        
        $filepath = $directory . $filename;
        
        file_put_contents($filepath, $xml_string);
        
        return $filepath;
    }
}