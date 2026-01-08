<?php
/**
 * Invoice Form View Template (Add/Edit)
 * Path: admin/views/invoice-form.php
 */

if (!defined('ABSPATH')) {
    exit;
}

$is_edit = isset($invoice) && !empty($invoice);
$page_title = $is_edit ? __('Edit Invoice', 'fatture-rf') : __('New Invoice', 'fatture-rf');

// Get settings for defaults
$default_payment_terms = $settings->get('default_payment_terms', '30 giorni data fattura');
$default_payment_method = $settings->get('default_payment_method', 'Bonifico bancario');
$apply_withholding = $settings->get('apply_withholding_tax', false);
$withholding_rate = $settings->get('withholding_tax_rate', 20);
$exempt_vat = $settings->get('exempt_vat', true);
?>

<div class="wrap">
    <h1><?php echo esc_html($page_title); ?></h1>
    
    <?php if (isset($error)): ?>
    <div class="notice notice-error">
        <p><?php echo esc_html($error); ?></p>
    </div>
    <?php endif; ?>
    
    <form method="post" action="" class="frf-form" id="invoice-form">
        <?php wp_nonce_field('frf_save_invoice'); ?>
        
        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px;">
            <!-- Main Content -->
            <div>
                <!-- Invoice Details -->
                <div class="frf-card">
                    <div class="frf-card-header">
                        <h2 class="frf-card-title"><?php _e('Invoice Details', 'fatture-rf'); ?></h2>
                    </div>
                    
                    <div class="frf-form-row">
                        <div class="frf-form-group">
                            <label for="invoice_number">
                                <?php _e('Invoice Number', 'fatture-rf'); ?> <span style="color: red;">*</span>
                            </label>
                            <input type="text" id="invoice_number" name="invoice_number" 
                                   value="<?php echo esc_attr($invoice->invoice_number ?? $invoice_number); ?>" 
                                   <?php echo $is_edit ? 'readonly' : ''; ?> required>
                        </div>
                        
                        <div class="frf-form-group">
                            <label for="invoice_date">
                                <?php _e('Invoice Date', 'fatture-rf'); ?> <span style="color: red;">*</span>
                            </label>
                            <input type="date" id="invoice_date" name="invoice_date" 
                                   value="<?php echo esc_attr($invoice->invoice_date ?? date('Y-m-d')); ?>" required>
                        </div>
                    </div>
                    
                    <div class="frf-form-group">
                        <label for="client_id">
                            <?php _e('Client', 'fatture-rf'); ?> <span style="color: red;">*</span>
                        </label>
                        <select id="client_id" name="client_id" required>
                            <option value=""><?php _e('Select client', 'fatture-rf'); ?></option>
                            <?php foreach ($clients as $client_option): ?>
                            <option value="<?php echo $client_option->id; ?>" 
                                    <?php selected($invoice->client_id ?? ($_GET['client_id'] ?? ''), $client_option->id); ?>>
                                <?php echo esc_html($client_option->business_name); ?> 
                                (<?php echo esc_html($client_option->city); ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <!-- Invoice Items -->
                <div class="frf-card" style="margin-top: 20px;">
                    <div class="frf-card-header">
                        <h2 class="frf-card-title">
                            <?php _e('Invoice Items', 'fatture-rf'); ?>
                            <button type="button" class="button button-small frf-add-item" style="float: right;">
                                <?php _e('+ Add Item', 'fatture-rf'); ?>
                            </button>
                        </h2>
                    </div>
                    
                    <div class="frf-invoice-items">
                        <table>
                            <thead>
                                <tr>
                                    <th style="width: 50%;"><?php _e('Description', 'fatture-rf'); ?></th>
                                    <th style="width: 15%;"><?php _e('Quantity', 'fatture-rf'); ?></th>
                                    <th style="width: 15%;"><?php _e('Unit Price', 'fatture-rf'); ?></th>
                                    <th style="width: 15%;"><?php _e('TOTAL', 'fatture-rf'); ?></th>
                                    <th style="width: 5%;"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $items = $invoice->items ?? [['description' => '', 'quantity' => 1, 'unit_price' => 0, 'total' => 0]];
                                foreach ($items as $index => $item): 
                                ?>
                                <tr>
                                    <td>
                                        <textarea name="items[<?php echo $index; ?>][description]" 
                                                  class="item-description" rows="2" required><?php echo esc_textarea($item['description'] ?? ''); ?></textarea>
                                    </td>
                                    <td>
                                        <input type="number" name="items[<?php echo $index; ?>][quantity]" 
                                               class="item-quantity" step="0.01" min="0" 
                                               value="<?php echo esc_attr($item['quantity'] ?? 1); ?>" required>
                                    </td>
                                    <td>
                                        <input type="number" name="items[<?php echo $index; ?>][unit_price]" 
                                               class="item-unit-price" step="0.01" min="0" 
                                               value="<?php echo esc_attr($item['unit_price'] ?? 0); ?>" required>
                                    </td>
                                    <td>
                                        <input type="number" name="items[<?php echo $index; ?>][total]" 
                                               class="item-total" step="0.01" readonly 
                                               value="<?php echo esc_attr($item['total'] ?? 0); ?>">
                                    </td>
                                    <td>
                                        <span class="dashicons dashicons-trash item-remove frf-remove-item" 
                                              style="cursor: pointer; color: #d63638;" title="<?php _e('Remove', 'fatture-rf'); ?>"></span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Payment Details -->
                <div class="frf-card" style="margin-top: 20px;">
                    <div class="frf-card-header">
                        <h2 class="frf-card-title"><?php _e('Payment Terms', 'fatture-rf'); ?></h2>
                    </div>
                    
                    <div class="frf-form-row">
                        <div class="frf-form-group">
                            <label for="payment_terms"><?php _e('Payment Terms', 'fatture-rf'); ?></label>
                            <input type="text" id="payment_terms" name="payment_terms" 
                                   value="<?php echo esc_attr($invoice->payment_terms ?? $default_payment_terms); ?>">
                        </div>
                        
                        <div class="frf-form-group">
                            <label for="payment_method"><?php _e('Payment Method', 'fatture-rf'); ?></label>
                            <select id="payment_method" name="payment_method">
                                <option value="Bonifico bancario" <?php selected($invoice->payment_method ?? $default_payment_method, 'Bonifico bancario'); ?>>
                                    <?php _e('Bank Transfer', 'fatture-rf'); ?>
                                </option>
                                <option value="Contanti" <?php selected($invoice->payment_method ?? '', 'Contanti'); ?>>
                                    <?php _e('Cash', 'fatture-rf'); ?>
                                </option>
                                <option value="Assegno" <?php selected($invoice->payment_method ?? '', 'Assegno'); ?>>
                                    <?php _e('Check', 'fatture-rf'); ?>
                                </option>
                                <option value="RID" <?php selected($invoice->payment_method ?? '', 'RID'); ?>>
                                    <?php _e('Direct Debit (RID)', 'fatture-rf'); ?>
                                </option>
                                <option value="PayPal" <?php selected($invoice->payment_method ?? '', 'PayPal'); ?>>
                                    <?php _e('PayPal', 'fatture-rf'); ?>
                                </option>
                                <option value="Stripe" <?php selected($invoice->payment_method ?? '', 'Stripe'); ?>>
                                    <?php _e('Stripe', 'fatture-rf'); ?>
                                </option>
                                <option value="Payoneer" <?php selected($invoice->payment_method ?? '', 'Payoneer'); ?>>
                                    <?php _e('Payoneer', 'fatture-rf'); ?>
                                </option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="frf-form-group">
                        <label for="notes"><?php _e('Note', 'fatture-rf'); ?></label>
                        <textarea id="notes" name="notes" rows="3"><?php echo esc_textarea($invoice->notes ?? ''); ?></textarea>
                        <small class="frf-form-help">
                            <?php _e('Note aggiuntive che appariranno sulla fattura', 'fatture-rf'); ?>
                        </small>
                    </div>
                </div>
            </div>
            
            <!-- Sidebar -->
            <div>
                <!-- Status -->
                <div class="frf-card">
                    <div class="frf-card-header">
                        <h2 class="frf-card-title"><?php _e('Status', 'fatture-rf'); ?></h2>
                    </div>
                    
                    <div class="frf-form-group">
                        <label for="status"><?php _e('Invoice Status', 'fatture-rf'); ?></label>
                        <select id="status" name="status">
                            <option value="draft" <?php selected($invoice->status ?? 'draft', 'draft'); ?>>
                                <?php _e('Draft', 'fatture-rf'); ?>
                            </option>
                            <option value="sent" <?php selected($invoice->status ?? '', 'sent'); ?>>
                                <?php _e('Sent', 'fatture-rf'); ?>
                            </option>
                            <option value="paid" <?php selected($invoice->status ?? '', 'paid'); ?>>
                                <?php _e('Paid', 'fatture-rf'); ?>
                            </option>
                            <option value="overdue" <?php selected($invoice->status ?? '', 'overdue'); ?>>
                                <?php _e('Overdue', 'fatture-rf'); ?>
                            </option>
                            <option value="cancelled" <?php selected($invoice->status ?? '', 'cancelled'); ?>>
                                <?php _e('Cancelled', 'fatture-rf'); ?>
                            </option>
                        </select>
                    </div>
                </div>
                
                <!-- Totals -->
                <div class="frf-card" style="margin-top: 20px;">
                    <div class="frf-card-header">
                        <h2 class="frf-card-title"><?php _e('Total', 'fatture-rf'); ?></h2>
                    </div>
                    
                    <div style="padding: 15px 0;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                            <span><?php _e('Subtotal:', 'fatture-rf'); ?></span>
                            <strong class="display-subtotal">€ <?php echo number_format($invoice->subtotal ?? 0, 2, ',', '.'); ?></strong>
                        </div>
                        
                        <?php if (!$exempt_vat): ?>
                        <div class="frf-form-group" style="margin-bottom: 15px;">
                            <label for="tax_rate"><?php _e('IVA (%)', 'fatture-rf'); ?></label>
                            <input type="number" id="tax_rate" name="tax_rate" step="0.01" 
                                   value="<?php echo esc_attr($invoice->tax_rate ?? 22); ?>">
                        </div>
                        
                        <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                            <span><?php _e('IVA:', 'fatture-rf'); ?></span>
                            <strong class="display-tax">€ <?php echo number_format($invoice->tax_amount ?? 0, 2, ',', '.'); ?></strong>
                        </div>
                        <?php else: ?>
                        <div style="padding: 10px; background: #f8f9fa; border-radius: 4px; margin-bottom: 15px; font-size: 12px;">
                            <strong><?php _e('Regime Forfettario', 'fatture-rf'); ?></strong><br>
                            <?php _e('Operazione effettuata ai sensi dell\'art. 1, commi 54-89, Legge n. 190/2014 e s.m.i - Regime forfetario', 'fatture-rf'); ?>
                        </div>
                        <?php endif; ?>
                        
                        <div style="display: flex; justify-content: space-between; margin-bottom: 15px; padding-top: 10px; border-top: 2px solid #ddd;">
                            <span><strong><?php _e('Total:', 'fatture-rf'); ?></strong></span>
                            <strong class="display-total" style="font-size: 18px;">€ <?php echo number_format($invoice->total ?? 0, 2, ',', '.'); ?></strong>
                        </div>
                        
                        <?php if ($apply_withholding): ?>
                        <div class="frf-form-group" style="margin-bottom: 15px;">
                            <label for="withholding_tax"><?php _e('Withholding Tax (%)', 'fatture-rf'); ?></label>
                            <input type="number" id="withholding_tax" name="withholding_tax" step="0.01" 
                                   value="<?php echo esc_attr($invoice->withholding_tax ?? $withholding_rate); ?>">
                        </div>
                        
                        <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                            <span><?php _e('Withholding:', 'fatture-rf'); ?></span>
                            <strong class="display-withholding" style="color: #d63638;">
                                - € <?php echo number_format($invoice->withholding_amount ?? 0, 2, ',', '.'); ?>
                            </strong>
                        </div>
                        
                        <div style="display: flex; justify-content: space-between; padding-top: 10px; border-top: 2px solid #ddd;">
                            <span><strong><?php _e('Net to Pay:', 'fatture-rf'); ?></strong></span>
                            <strong class="display-net" style="font-size: 18px; color: #0a7d3e;">
                                € <?php echo number_format($invoice->net_to_pay ?? 0, 2, ',', '.'); ?>
                            </strong>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Hidden fields for calculated values -->
                    <input type="hidden" id="subtotal" name="subtotal" value="<?php echo esc_attr($invoice->subtotal ?? 0); ?>">
                    <input type="hidden" id="tax_amount" name="tax_amount" value="<?php echo esc_attr($invoice->tax_amount ?? 0); ?>">
                    <input type="hidden" id="total" name="total" value="<?php echo esc_attr($invoice->total ?? 0); ?>">
                    <input type="hidden" id="withholding_amount" name="withholding_amount" value="<?php echo esc_attr($invoice->withholding_amount ?? 0); ?>">
                    <input type="hidden" id="net_to_pay" name="net_to_pay" value="<?php echo esc_attr($invoice->net_to_pay ?? 0); ?>">
                </div>
                
                <!-- Actions -->
                <div class="frf-card" style="margin-top: 20px;">
                    <button type="submit" name="frf_save_invoice" class="button button-primary button-large" style="width: 100%;">
                        <?php echo $is_edit ? __('Update Invoice', 'fatture-rf') : __('Save Invoice', 'fatture-rf'); ?>
                    </button>
                    <a href="<?php echo admin_url('admin.php?page=fatture-rf-invoices'); ?>" 
                       class="button button-large" style="width: 100%; margin-top: 10px; text-align: center;">
                        <?php _e('Cancel', 'fatture-rf'); ?>
                    </a>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Item Template for JavaScript -->
<script type="text/html" id="frf-item-template">
    <tr>
        <td>
            <textarea name="items[INDEX][description]" class="item-description" rows="2" required></textarea>
        </td>
        <td>
            <input type="number" name="items[INDEX][quantity]" class="item-quantity" step="0.01" min="0" value="1" required>
        </td>
        <td>
            <input type="number" name="items[INDEX][unit_price]" class="item-unit-price" step="0.01" min="0" value="0" required>
        </td>
        <td>
            <input type="number" name="items[INDEX][total]" class="item-total" step="0.01" readonly value="0">
        </td>
        <td>
            <span class="dashicons dashicons-trash item-remove frf-remove-item" 
                  style="cursor: pointer; color: #d63638;" title="<?php _e('Rimuovi', 'fatture-rf'); ?>"></span>
        </td>
    </tr>
</script>

<script>
jQuery(document).ready(function($) {
    // Trigger initial calculation
    $('.item-quantity, .item-unit-price').trigger('input');
});
</script>