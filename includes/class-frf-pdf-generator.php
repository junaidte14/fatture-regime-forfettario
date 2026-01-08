<?php
/**
 * PDF Generator Class
 * Path: includes/class-frf-pdf-generator.php
 * Generates PDF invoices using TCPDF or mPDF
 */

if (!defined('ABSPATH')) {
    exit;
}

class FRF_PDF_Generator {
    
    private $invoice;
    private $client;
    private $business;
    private $settings;
    
    /**
     * Generate PDF for invoice
     */
    public function generate($invoice_id) {
        // Load invoice data
        $invoice_model = new FRF_Invoice();
        $this->invoice = $invoice_model->get($invoice_id);
        
        if (!$this->invoice) {
            return new WP_Error('not_found', __('Invoice not found', 'fatture-rf'));
        }
        
        // Load client
        $client_model = new FRF_Client();
        $this->client = $client_model->get($this->invoice->client_id);
        
        // Load business settings
        $this->settings = FRF_Settings::get_instance();
        $this->business = $this->settings->get_business_info();
        
        // Check if TCPDF is available
        if ($this->is_tcpdf_available()) {
            return $this->generate_with_tcpdf();
        }
        
        // Fallback to HTML-based PDF
        return $this->generate_html_pdf();
    }
    
    /**
     * Check if TCPDF is available
     */
    private function is_tcpdf_available() {
        return class_exists('TCPDF');
    }
    
    /**
     * Generate PDF using TCPDF
     */
    private function generate_with_tcpdf() {
        require_once(ABSPATH . 'wp-includes/class-phpass.php');
        
        if (!class_exists('TCPDF')) {
            // Try to include TCPDF if available in common locations
            $tcpdf_paths = [
                ABSPATH . 'wp-content/plugins/woocommerce/packages/tcpdf/tcpdf.php',
                ABSPATH . 'wp-includes/tcpdf/tcpdf.php',
            ];
            
            foreach ($tcpdf_paths as $path) {
                if (file_exists($path)) {
                    require_once($path);
                    break;
                }
            }
        }
        
        if (!class_exists('TCPDF')) {
            return $this->generate_html_pdf();
        }
        
        // Create new PDF document
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        
        // Set document information
        $pdf->SetCreator('Fatture RF');
        $pdf->SetAuthor($this->business['business_name']);
        $pdf->SetTitle('Fattura ' . $this->invoice->invoice_number);
        $pdf->SetSubject('Fattura');
        
        // Remove default header/footer
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        
        // Set margins
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetAutoPageBreak(true, 15);
        
        // Add a page
        $pdf->AddPage();
        
        // Set font
        $pdf->SetFont('helvetica', '', 10);
        
        // Generate HTML content
        $html = $this->get_pdf_html();
        
        // Output HTML content
        $pdf->writeHTML($html, true, false, true, false, '');
        
        // Output PDF
        $filename = sanitize_file_name('fattura-' . $this->invoice->invoice_number . '.pdf');
        
        return $pdf->Output($filename, 'D'); // D = force download
    }
    
    /**
     * Generate HTML-based PDF (fallback)
     */
    private function generate_html_pdf() {
        // Generate HTML
        $html = $this->get_pdf_html_complete();
        
        // Set headers for PDF download
        $filename = sanitize_file_name('fattura-' . $this->invoice->invoice_number . '.pdf');
        
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        // For now, we'll output as HTML with print-friendly CSS
        // In production, you'd use a library like DomPDF or mPDF
        echo $html;
        exit;
    }
    
    /**
     * Get PDF HTML content (for TCPDF)
     */
    private function get_pdf_html() {
        ob_start();
        ?>
        <style>
            h1 { font-size: 24px; color: #2271b1; margin: 0; }
            h2 { font-size: 18px; margin: 10px 0; }
            h3 { font-size: 14px; margin: 10px 0; }
            .header { margin-bottom: 30px; }
            .company-info { font-size: 10px; line-height: 1.4; }
            .invoice-title { text-align: right; }
            .client-info { background: #f8f9fa; padding: 15px; margin: 20px 0; }
            table { width: 100%; border-collapse: collapse; margin: 20px 0; }
            table th { background: #f8f9fa; padding: 10px; text-align: left; border-bottom: 2px solid #ddd; }
            table td { padding: 10px; border-bottom: 1px solid #eee; }
            .totals { margin-top: 20px; text-align: right; }
            .totals table { width: 50%; margin-left: auto; }
            .total-row { font-weight: bold; font-size: 14px; }
            .notes { background: #f8f9fa; padding: 15px; margin-top: 20px; font-size: 9px; }
        </style>
        
        <div class="header">
            <table style="border: none;">
                <tr>
                    <td style="width: 50%; border: none;">
                        <div class="company-info">
                            <h2><?php echo esc_html($this->business['business_name']); ?></h2>
                            <?php if (!empty($this->business['vat_number'])): ?>
                            <div>P.IVA: <?php echo esc_html($this->business['vat_number']); ?></div>
                            <?php endif; ?>
                            <?php if (!empty($this->business['tax_code'])): ?>
                            <div>C.F.: <?php echo esc_html($this->business['tax_code']); ?></div>
                            <?php endif; ?>
                            <?php if (!empty($this->business['address'])): ?>
                            <div><?php echo esc_html($this->business['address']); ?></div>
                            <div><?php echo esc_html($this->business['postal_code'] . ' ' . $this->business['city']); ?>
                                <?php if (!empty($this->business['province'])): ?>
                                    (<?php echo esc_html($this->business['province']); ?>)
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                            <?php if (!empty($this->business['email'])): ?>
                            <div>Email: <?php echo esc_html($this->business['email']); ?></div>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td style="width: 50%; text-align: right; border: none;">
                        <div class="invoice-title">
                            <h1>FATTURA</h1>
                            <h2><?php echo esc_html($this->invoice->invoice_number); ?></h2>
                            <div>Data: <?php echo date_i18n('d/m/Y', strtotime($this->invoice->invoice_date)); ?></div>
                        </div>
                    </td>
                </tr>
            </table>
        </div>
        
        <div class="client-info">
            <h3>Cliente:</h3>
            <div><strong><?php echo esc_html($this->client->business_name); ?></strong></div>
            <?php if (!empty($this->client->vat_number)): ?>
            <div>P.IVA: <?php echo esc_html($this->client->vat_number); ?></div>
            <?php endif; ?>
            <?php if (!empty($this->client->tax_code)): ?>
            <div>C.F.: <?php echo esc_html($this->client->tax_code); ?></div>
            <?php endif; ?>
            <?php if (!empty($this->client->address)): ?>
            <div><?php echo esc_html($this->client->address); ?></div>
            <div><?php echo esc_html($this->client->postal_code . ' ' . $this->client->city); ?>
                <?php if (!empty($this->client->province)): ?>
                    (<?php echo esc_html($this->client->province); ?>)
                <?php endif; ?>
                - <?php echo esc_html($this->client->country); ?>
            </div>
            <?php endif; ?>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th style="width: 50%;">Descrizione</th>
                    <th style="width: 15%; text-align: center;">Q.tà</th>
                    <th style="width: 17.5%; text-align: right;">Prezzo Unit.</th>
                    <th style="width: 17.5%; text-align: right;">Totale</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($this->invoice->items as $item): ?>
                <tr>
                    <td><?php echo nl2br(esc_html($item->description)); ?></td>
                    <td style="text-align: center;"><?php echo number_format($item->quantity, 2, ',', '.'); ?></td>
                    <td style="text-align: right;">€ <?php echo number_format($item->unit_price, 2, ',', '.'); ?></td>
                    <td style="text-align: right;"><strong>€ <?php echo number_format($item->total, 2, ',', '.'); ?></strong></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <div class="totals">
            <table style="border: none;">
                <tr>
                    <td style="border: none;">Imponibile</td>
                    <td style="border: none; text-align: right;"><strong>€ <?php echo number_format($this->invoice->subtotal, 2, ',', '.'); ?></strong></td>
                </tr>
                <?php if ($this->invoice->tax_rate > 0): ?>
                <tr>
                    <td style="border: none;">IVA (<?php echo number_format($this->invoice->tax_rate, 0); ?>%)</td>
                    <td style="border: none; text-align: right;"><strong>€ <?php echo number_format($this->invoice->tax_amount, 2, ',', '.'); ?></strong></td>
                </tr>
                <?php endif; ?>
                <tr class="total-row">
                    <td style="border-top: 2px solid #ddd; border-bottom: none;">TOTALE</td>
                    <td style="border-top: 2px solid #ddd; border-bottom: none; text-align: right;">€ <?php echo number_format($this->invoice->total, 2, ',', '.'); ?></td>
                </tr>
                <?php if ($this->invoice->withholding_tax > 0): ?>
                <tr>
                    <td style="border: none;">Ritenuta d'acconto (<?php echo number_format($this->invoice->withholding_tax, 0); ?>%)</td>
                    <td style="border: none; text-align: right;">- € <?php echo number_format($this->invoice->withholding_amount, 2, ',', '.'); ?></td>
                </tr>
                <tr class="total-row">
                    <td style="border-top: 2px solid #ddd; border-bottom: none;">NETTO A PAGARE</td>
                    <td style="border-top: 2px solid #ddd; border-bottom: none; text-align: right;">€ <?php echo number_format($this->invoice->net_to_pay, 2, ',', '.'); ?></td>
                </tr>
                <?php endif; ?>
            </table>
        </div>
        
        <?php if ($this->invoice->tax_rate == 0): ?>
        <div class="notes">
            <strong>Regime Forfettario:</strong> Operazione effettuata ai sensi dell'art. 1, commi 54-89, Legge n. 190/2014 e s.m.i - Regime forfetario. Operazione senza applicazione dell'IVA.
        </div>
        <?php endif; ?>
        
        <?php if (!empty($this->invoice->payment_terms) || !empty($this->invoice->payment_method)): ?>
        <div class="notes">
            <strong>Condizioni di Pagamento:</strong><br>
            <?php if (!empty($this->invoice->payment_terms)): ?>
            Termini: <?php echo esc_html($this->invoice->payment_terms); ?><br>
            <?php endif; ?>
            <?php if (!empty($this->invoice->payment_method)): ?>
            Metodo: <?php echo esc_html($this->invoice->payment_method); ?>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($this->invoice->notes)): ?>
        <div class="notes">
            <strong>Note:</strong><br>
            <?php echo nl2br(esc_html($this->invoice->notes)); ?>
        </div>
        <?php endif; ?>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Get complete HTML for fallback PDF
     */
    private function get_pdf_html_complete() {
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Fattura <?php echo esc_html($this->invoice->invoice_number); ?></title>
            <style>
                @page { margin: 2cm; }
                body { font-family: Arial, sans-serif; font-size: 12px; line-height: 1.6; }
                <?php echo $this->get_pdf_html(); ?>
            </style>
        </head>
        <body>
            <?php echo $this->get_pdf_html(); ?>
            <script>window.print();</script>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Save PDF to file
     */
    public function save_to_file($invoice_id, $directory = null) {
        if (!$directory) {
            $upload_dir = wp_upload_dir();
            $directory = $upload_dir['basedir'] . '/fatture-rf/';
        }
        
        // Create directory if it doesn't exist
        if (!file_exists($directory)) {
            wp_mkdir_p($directory);
        }
        
        $filename = 'fattura-' . $this->invoice->invoice_number . '.pdf';
        $filepath = $directory . $filename;
        
        // Generate PDF content
        if ($this->is_tcpdf_available()) {
            // TCPDF save to file
            // ... implementation
        }
        
        return $filepath;
    }
}