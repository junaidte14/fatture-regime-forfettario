<?php
/**
 * Invoice View Template
 * Path: admin/views/invoice-view.php
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1>
        <?php _e('Invoice', 'fatture-rf'); ?> <?php echo esc_html($invoice->invoice_number); ?>
        <?php echo FRF_Admin_Invoices::get_status_badge($invoice->status); ?>
    </h1>
    
    <p style="margin-top: 10px;">
        <?php if ($invoice->status === 'draft'): ?>
        <a href="<?php echo admin_url('admin.php?page=fatture-rf-invoices&view=edit&id=' . $invoice->id); ?>" 
           class="button button-primary">
            <?php _e('Edit Invoice', 'fatture-rf'); ?>
        </a>
        <?php endif; ?>
        
        <button class="button frf-export-pdf" data-invoice-id="<?php echo $invoice->id; ?>">
            <span class="dashicons dashicons-pdf" style="margin-top: 3px;"></span>
            <?php _e('Download PDF', 'fatture-rf'); ?>
        </button>
        
        <?php if ($invoice->status !== 'cancelled'): ?>
        <button class="button">
            <span class="dashicons dashicons-email" style="margin-top: 3px;"></span>
            <?php _e('Send Email', 'fatture-rf'); ?>
        </button>
        <?php endif; ?>
        
        <a href="<?php echo admin_url('admin.php?page=fatture-rf-invoices'); ?>" class="button">
            <?php _e('← Back to Invoices', 'fatture-rf'); ?>
        </a>
    </p>
    
    <!-- Invoice Document -->
    <div class="frf-card" style="margin-top: 20px; max-width: 800px; margin-left: auto; margin-right: auto;">
        <!-- Header -->
        <div class="frf-invoice-header">
            <div>
                <h2 style="margin: 0;"><?php echo esc_html($business['business_name']); ?></h2>
                <?php if (!empty($business['vat_number'])): ?>
                <p style="margin: 5px 0;">P.IVA: <?php echo esc_html($business['vat_number']); ?></p>
                <?php endif; ?>
                <?php if (!empty($business['tax_code'])): ?>
                <p style="margin: 5px 0;">C.F.: <?php echo esc_html($business['tax_code']); ?></p>
                <?php endif; ?>
                <?php if (!empty($business['address'])): ?>
                <p style="margin: 5px 0;">
                    <?php echo esc_html($business['address']); ?><br>
                    <?php echo esc_html($business['postal_code'] . ' ' . $business['city']); ?>
                    <?php if (!empty($business['province'])): ?>
                        (<?php echo esc_html($business['province']); ?>)
                    <?php endif; ?>
                </p>
                <?php endif; ?>
                <?php if (!empty($business['email'])): ?>
                <p style="margin: 5px 0;">Email: <?php echo esc_html($business['email']); ?></p>
                <?php endif; ?>
                <?php if (!empty($business['pec_email'])): ?>
                <p style="margin: 5px 0;">PEC: <?php echo esc_html($business['pec_email']); ?></p>
                <?php endif; ?>
            </div>
            
            <div style="text-align: right;">
                <h1 style="margin: 0; font-size: 32px; color: #2271b1;">FATTURA</h1>
                <p style="margin: 10px 0; font-size: 18px;">
                    <strong><?php echo esc_html($invoice->invoice_number); ?></strong>
                </p>
                <p style="margin: 5px 0;">
                    <?php _e('Data:', 'fatture-rf'); ?> 
                    <strong><?php echo date_i18n(get_option('date_format'), strtotime($invoice->invoice_date)); ?></strong>
                </p>
            </div>
        </div>
        
        <hr style="margin: 30px 0; border: none; border-top: 2px solid #e0e0e0;">
        
        <!-- Client Info -->
        <div style="margin-bottom: 30px;">
            <h3 style="margin: 0 0 10px 0;"><?php _e('Cliente:', 'fatture-rf'); ?></h3>
            <div class="frf-client-info">
                <p style="margin: 5px 0;"><strong><?php echo esc_html($client->business_name); ?></strong></p>
                <?php if (!empty($client->vat_number)): ?>
                <p style="margin: 5px 0;">P.IVA: <?php echo esc_html($client->vat_number); ?></p>
                <?php endif; ?>
                <?php if (!empty($client->tax_code)): ?>
                <p style="margin: 5px 0;">C.F.: <?php echo esc_html($client->tax_code); ?></p>
                <?php endif; ?>
                <?php if (!empty($client->address)): ?>
                <p style="margin: 5px 0;">
                    <?php echo esc_html($client->address); ?><br>
                    <?php echo esc_html($client->postal_code . ' ' . $client->city); ?>
                    <?php if (!empty($client->province)): ?>
                        (<?php echo esc_html($client->province); ?>)
                    <?php endif; ?>
                    - <?php echo esc_html($client->country); ?>
                </p>
                <?php endif; ?>
                <?php if (!empty($client->sdi_code)): ?>
                <p style="margin: 5px 0;">Codice SDI: <?php echo esc_html($client->sdi_code); ?></p>
                <?php endif; ?>
                <?php if (!empty($client->pec_email)): ?>
                <p style="margin: 5px 0;">PEC: <?php echo esc_html($client->pec_email); ?></p>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Items Table -->
        <table class="frf-table" style="margin-bottom: 20px;">
            <thead>
                <tr>
                    <th style="text-align: left;"><?php _e('Descrizione', 'fatture-rf'); ?></th>
                    <th style="text-align: center; width: 10%;"><?php _e('Q.tà', 'fatture-rf'); ?></th>
                    <th style="text-align: right; width: 15%;"><?php _e('Prezzo Unit.', 'fatture-rf'); ?></th>
                    <th style="text-align: right; width: 15%;"><?php _e('Totale', 'fatture-rf'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($invoice->items as $item): ?>
                <tr>
                    <td><?php echo nl2br(esc_html($item->description)); ?></td>
                    <td class="frf-text-center"><?php echo number_format($item->quantity, 2, ',', '.'); ?></td>
                    <td class="frf-text-right">€ <?php echo number_format($item->unit_price, 2, ',', '.'); ?></td>
                    <td class="frf-text-right"><strong>€ <?php echo number_format($item->total, 2, ',', '.'); ?></strong></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <!-- Totals -->
        <div class="frf-invoice-totals" style="max-width: 400px; margin-left: auto;">
            <div class="frf-total-row">
                <span><?php _e('Imponibile', 'fatture-rf'); ?></span>
                <span><strong>€ <?php echo number_format($invoice->subtotal, 2, ',', '.'); ?></strong></span>
            </div>
            
            <?php if ($invoice->tax_rate > 0): ?>
            <div class="frf-total-row">
                <span><?php _e('IVA', 'fatture-rf'); ?> (<?php echo number_format($invoice->tax_rate, 0); ?>%)</span>
                <span><strong>€ <?php echo number_format($invoice->tax_amount, 2, ',', '.'); ?></strong></span>
            </div>
            <?php else: ?>
            <div class="frf-total-row">
                <span colspan="2" style="font-size: 11px; font-style: italic;">
                    <?php _e('Operazione effettuata ai sensi dell\'art. 1, commi 54-89, Legge n. 190/2014 - Regime forfetario', 'fatture-rf'); ?>
                </span>
            </div>
            <?php endif; ?>
            
            <div class="frf-total-row">
                <span><strong><?php _e('TOTALE', 'fatture-rf'); ?></strong></span>
                <span style="font-size: 20px;"><strong>€ <?php echo number_format($invoice->total, 2, ',', '.'); ?></strong></span>
            </div>
            
            <?php if ($invoice->withholding_tax > 0): ?>
            <div class="frf-total-row">
                <span><?php _e('Ritenuta d\'acconto', 'fatture-rf'); ?> (<?php echo number_format($invoice->withholding_tax, 0); ?>%)</span>
                <span style="color: #d63638;"><strong>- € <?php echo number_format($invoice->withholding_amount, 2, ',', '.'); ?></strong></span>
            </div>
            
            <div class="frf-total-row">
                <span><strong><?php _e('NETTO A PAGARE', 'fatture-rf'); ?></strong></span>
                <span style="font-size: 20px; color: #0a7d3e;"><strong>€ <?php echo number_format($invoice->net_to_pay, 2, ',', '.'); ?></strong></span>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Payment Info -->
        <?php if (!empty($invoice->payment_terms) || !empty($invoice->payment_method)): ?>
        <div style="margin-top: 30px; padding: 15px; background: #f8f9fa; border-radius: 4px;">
            <h4 style="margin: 0 0 10px 0;"><?php _e('Condizioni di Pagamento', 'fatture-rf'); ?></h4>
            <?php if (!empty($invoice->payment_terms)): ?>
            <p style="margin: 5px 0;"><strong><?php _e('Termini:', 'fatture-rf'); ?></strong> <?php echo esc_html($invoice->payment_terms); ?></p>
            <?php endif; ?>
            <?php if (!empty($invoice->payment_method)): ?>
            <p style="margin: 5px 0;"><strong><?php _e('Metodo:', 'fatture-rf'); ?></strong> <?php echo esc_html($invoice->payment_method); ?></p>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <!-- Notes -->
        <?php if (!empty($invoice->notes)): ?>
        <div style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 4px;">
            <h4 style="margin: 0 0 10px 0;"><?php _e('Note', 'fatture-rf'); ?></h4>
            <p style="margin: 0;"><?php echo nl2br(esc_html($invoice->notes)); ?></p>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Status History -->
    <?php if (!empty($history)): ?>
    <div class="frf-card frf-status-history" style="margin-top: 30px; max-width: 800px; margin-left: auto; margin-right: auto;">
        <div class="frf-card-header">
            <h2 class="frf-card-title"><?php _e('Cronologia Stati', 'fatture-rf'); ?></h2>
        </div>
        
        <?php foreach ($history as $item): ?>
        <div class="frf-history-item">
            <div class="frf-history-date">
                <?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($item->changed_at)); ?>
            </div>
            <div class="frf-history-change">
                <?php if ($item->old_status): ?>
                    <?php echo FRF_Admin_Invoices::get_status_badge($item->old_status); ?> 
                    → 
                <?php endif; ?>
                <?php echo FRF_Admin_Invoices::get_status_badge($item->new_status); ?>
            </div>
            <?php if (!empty($item->notes)): ?>
            <div style="margin-top: 5px; font-size: 13px; color: #666;">
                <?php echo esc_html($item->notes); ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<style>
@media print {
    .wrap > h1, .wrap > p, .frf-status-history { display: none; }
    .frf-card { border: none; box-shadow: none; }
}
</style>