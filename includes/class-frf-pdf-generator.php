<?php
/**
 * PDF Generator Class - HTML ONLY VERSION
 * Path: includes/class-frf-pdf-generator.php
 * Generates printable HTML invoices
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
        
        // Generate HTML and output
        return $this->generate_html_pdf();
    }
    
    /**
     * Generate HTML PDF
     */
    private function generate_html_pdf() {
        $html = $this->get_complete_html();
        
        // Set headers for HTML display with print dialog
        header('Content-Type: text/html; charset=utf-8');
        
        echo $html;
        echo '<script>
            window.onload = function() { 
                setTimeout(function() {
                    window.print(); 
                }, 500);
            };
        </script>';
        
        return true;
    }
    
    /**
     * Get complete HTML document
     */
    private function get_complete_html() {
        ob_start();
        ?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fattura <?php echo esc_html($this->invoice->invoice_number); ?></title>
    <style>
        @page { margin: 2cm; size: A4; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: Arial, Helvetica, sans-serif; 
            font-size: 11pt; 
            line-height: 1.5; 
            color: #333;
        }
        .container { width: 100%; max-width: 21cm; margin: 0 auto; padding: 1cm; }
        .header { margin-bottom: 40px; }
        .header table { width: 100%; border: none; }
        .header td { vertical-align: top; border: none; padding: 0; }
        .company-info { font-size: 10pt; }
        .company-info h2 { font-size: 16pt; margin-bottom: 10px; color: #2271b1; }
        .invoice-title { text-align: right; }
        .invoice-title h1 { font-size: 24pt; color: #2271b1; margin-bottom: 5px; }
        .invoice-title h2 { font-size: 18pt; margin-bottom: 10px; }
        .client-info { 
            background: #f8f9fa; 
            padding: 20px; 
            margin: 30px 0; 
            border-left: 4px solid #2271b1;
        }
        .client-info h3 { font-size: 12pt; margin-bottom: 10px; color: #2271b1; }
        table.items { 
            width: 100%; 
            border-collapse: collapse; 
            margin: 30px 0; 
        }
        table.items thead th { 
            background: #2271b1; 
            color: white; 
            padding: 12px 8px; 
            text-align: left; 
            font-weight: bold;
            border: 1px solid #1a5a8a;
        }
        table.items tbody td { 
            padding: 10px 8px; 
            border-bottom: 1px solid #ddd; 
        }
        table.items tbody tr:last-child td {
            border-bottom: 2px solid #2271b1;
        }
        .totals { 
            margin-top: 30px; 
            float: right; 
            width: 50%; 
        }
        .totals table { width: 100%; border-collapse: collapse; }
        .totals td { 
            padding: 8px; 
            border-bottom: 1px solid #eee; 
        }
        .totals td:first-child { text-align: left; }
        .totals td:last-child { text-align: right; font-weight: bold; }
        .totals .total-row td { 
            font-size: 14pt; 
            padding: 12px 8px;
            border-top: 2px solid #2271b1; 
            border-bottom: 2px solid #2271b1;
            background: #f8f9fa;
        }
        .notes { 
            clear: both;
            background: #f8f9fa; 
            padding: 20px; 
            margin-top: 40px; 
            font-size: 9pt;
            border-left: 4px solid #2271b1;
        }
        .notes strong { display: block; margin-bottom: 5px; font-size: 10pt; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        @media print {
            body { print-color-adjust: exact; -webkit-print-color-adjust: exact; }
            .no-print { display: none; }
            @page { margin: 1.5cm; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <table>
                <tr>
                    <td style="width: 50%;">
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
                            <div>
                                <?php echo esc_html($this->business['postal_code'] . ' ' . $this->business['city']); ?>
                                <?php if (!empty($this->business['province'])): ?>
                                    (<?php echo esc_html($this->business['province']); ?>)
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                            <?php if (!empty($this->business['email'])): ?>
                            <div>Email: <?php echo esc_html($this->business['email']); ?></div>
                            <?php endif; ?>
                            <?php if (!empty($this->business['phone'])): ?>
                            <div>Tel: <?php echo esc_html($this->business['phone']); ?></div>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td style="width: 50%;">
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
            <h3>Intestato a:</h3>
            <div><strong><?php echo esc_html($this->client->business_name); ?></strong></div>
            <?php if (!empty($this->client->vat_number)): ?>
            <div>P.IVA: <?php echo esc_html($this->client->vat_number); ?></div>
            <?php endif; ?>
            <?php if (!empty($this->client->tax_code)): ?>
            <div>C.F.: <?php echo esc_html($this->client->tax_code); ?></div>
            <?php endif; ?>
            <?php if (!empty($this->client->address)): ?>
            <div><?php echo esc_html($this->client->address); ?></div>
            <div>
                <?php echo esc_html($this->client->postal_code . ' ' . $this->client->city); ?>
                <?php if (!empty($this->client->province)): ?>
                    (<?php echo esc_html($this->client->province); ?>)
                <?php endif; ?>
                - <?php echo esc_html($this->client->country); ?>
            </div>
            <?php endif; ?>
            <?php if (!empty($this->client->email)): ?>
            <div>Email: <?php echo esc_html($this->client->email); ?></div>
            <?php endif; ?>
        </div>
        
        <table class="items">
            <thead>
                <tr>
                    <th style="width: 50%;">Descrizione</th>
                    <th style="width: 15%;" class="text-center">Q.tà</th>
                    <th style="width: 17.5%;" class="text-right">Prezzo Unit.</th>
                    <th style="width: 17.5%;" class="text-right">Totale</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($this->invoice->items as $item): ?>
                <tr>
                    <td><?php echo nl2br(esc_html($item->description)); ?></td>
                    <td class="text-center"><?php echo number_format($item->quantity, 2, ',', '.'); ?></td>
                    <td class="text-right">€ <?php echo number_format($item->unit_price, 2, ',', '.'); ?></td>
                    <td class="text-right"><strong>€ <?php echo number_format($item->total, 2, ',', '.'); ?></strong></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <div class="totals">
            <table>
                <tr>
                    <td>Imponibile</td>
                    <td>€ <?php echo number_format($this->invoice->subtotal, 2, ',', '.'); ?></td>
                </tr>
                <?php if ($this->invoice->tax_rate > 0): ?>
                <tr>
                    <td>IVA (<?php echo number_format($this->invoice->tax_rate, 0); ?>%)</td>
                    <td>€ <?php echo number_format($this->invoice->tax_amount, 2, ',', '.'); ?></td>
                </tr>
                <?php endif; ?>
                <tr class="total-row">
                    <td>TOTALE</td>
                    <td>€ <?php echo number_format($this->invoice->total, 2, ',', '.'); ?></td>
                </tr>
                <?php if ($this->invoice->withholding_tax > 0): ?>
                <tr>
                    <td>Ritenuta d'acconto (<?php echo number_format($this->invoice->withholding_tax, 0); ?>%)</td>
                    <td>- € <?php echo number_format($this->invoice->withholding_amount, 2, ',', '.'); ?></td>
                </tr>
                <tr class="total-row">
                    <td>NETTO A PAGARE</td>
                    <td>€ <?php echo number_format($this->invoice->net_to_pay, 2, ',', '.'); ?></td>
                </tr>
                <?php endif; ?>
            </table>
        </div>
        
        <?php if ($this->invoice->tax_rate == 0): ?>
        <div class="notes">
            <strong>Regime Forfettario:</strong>
            Operazione effettuata ai sensi dell'art. 1, commi 54-89, Legge n. 190/2014 e s.m.i - Regime forfetario. Operazione senza applicazione dell'IVA ai sensi dell'art. 1, comma 58, Legge 190/2014.
        </div>
        <?php endif; ?>
        
        <?php if (!empty($this->invoice->payment_terms) || !empty($this->invoice->payment_method)): ?>
        <div class="notes">
            <strong>Condizioni di Pagamento:</strong>
            <?php if (!empty($this->invoice->payment_terms)): ?>
            <div>Termini: <?php echo esc_html($this->invoice->payment_terms); ?></div>
            <?php endif; ?>
            <?php if (!empty($this->invoice->payment_method)): ?>
            <div>Metodo: <?php echo esc_html($this->invoice->payment_method); ?></div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($this->invoice->notes)): ?>
        <div class="notes">
            <strong>Note:</strong>
            <?php echo nl2br(esc_html($this->invoice->notes)); ?>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>
        <?php
        return ob_get_clean();
    }
}