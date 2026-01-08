<?php
/**
 * Invoices List View Template
 * Path: admin/views/invoices-list.php
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php _e('Fatture', 'fatture-rf'); ?></h1>
    <a href="<?php echo admin_url('admin.php?page=fatture-rf-invoices&view=add'); ?>" class="page-title-action">
        <?php _e('Aggiungi Nuova', 'fatture-rf'); ?>
    </a>
    <hr class="wp-header-end">
    
    <?php settings_errors('frf_messages'); ?>
    
    <!-- Filters -->
    <div class="frf-filters">
        <form method="get" action="">
            <input type="hidden" name="page" value="fatture-rf-invoices">
            
            <select name="status" id="status">
                <option value=""><?php _e('Tutti gli stati', 'fatture-rf'); ?></option>
                <option value="draft" <?php selected($status, 'draft'); ?>><?php _e('Bozza', 'fatture-rf'); ?></option>
                <option value="sent" <?php selected($status, 'sent'); ?>><?php _e('Inviata', 'fatture-rf'); ?></option>
                <option value="paid" <?php selected($status, 'paid'); ?>><?php _e('Pagata', 'fatture-rf'); ?></option>
                <option value="overdue" <?php selected($status, 'overdue'); ?>><?php _e('Scaduta', 'fatture-rf'); ?></option>
                <option value="cancelled" <?php selected($status, 'cancelled'); ?>><?php _e('Annullata', 'fatture-rf'); ?></option>
                <option value="submitted" <?php selected($status, 'submitted'); ?>><?php _e('Inviata SDI', 'fatture-rf'); ?></option>
                <option value="accepted" <?php selected($status, 'accepted'); ?>><?php _e('Accettata SDI', 'fatture-rf'); ?></option>
                <option value="rejected" <?php selected($status, 'rejected'); ?>><?php _e('Rifiutata SDI', 'fatture-rf'); ?></option>
            </select>
            
            <select name="client_id" id="client_id">
                <option value=""><?php _e('Tutti i clienti', 'fatture-rf'); ?></option>
                <?php foreach ($clients as $client_option): ?>
                <option value="<?php echo $client_option->id; ?>" <?php selected($client_id, $client_option->id); ?>>
                    <?php echo esc_html($client_option->business_name); ?>
                </option>
                <?php endforeach; ?>
            </select>
            
            <button type="submit" class="button"><?php _e('Filtra', 'fatture-rf'); ?></button>
            
            <?php if (!empty($status) || !empty($client_id)): ?>
            <a href="<?php echo admin_url('admin.php?page=fatture-rf-invoices'); ?>" class="button">
                <?php _e('Resetta filtri', 'fatture-rf'); ?>
            </a>
            <?php endif; ?>
        </form>
    </div>
    
    <!-- Invoices Table -->
    <?php if (!empty($invoices)): ?>
    <table class="frf-table">
        <thead>
            <tr>
                <th><?php _e('Numero', 'fatture-rf'); ?></th>
                <th><?php _e('Data', 'fatture-rf'); ?></th>
                <th><?php _e('Cliente', 'fatture-rf'); ?></th>
                <th><?php _e('Importo', 'fatture-rf'); ?></th>
                <th><?php _e('Da Pagare', 'fatture-rf'); ?></th>
                <th><?php _e('Stato', 'fatture-rf'); ?></th>
                <th><?php _e('Azioni', 'fatture-rf'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $client_model = new FRF_Client();
            foreach ($invoices as $invoice): 
                $invoice_client = $client_model->get($invoice->client_id);
            ?>
            <tr>
                <td>
                    <strong>
                        <a href="<?php echo admin_url('admin.php?page=fatture-rf-invoices&view=view&id=' . $invoice->id); ?>">
                            <?php echo esc_html($invoice->invoice_number); ?>
                        </a>
                    </strong>
                </td>
                <td><?php echo date_i18n(get_option('date_format'), strtotime($invoice->invoice_date)); ?></td>
                <td>
                    <?php if ($invoice_client): ?>
                        <a href="<?php echo admin_url('admin.php?page=fatture-rf-clients&view=view&id=' . $invoice_client->id); ?>">
                            <?php echo esc_html($invoice_client->business_name); ?>
                        </a>
                    <?php endif; ?>
                </td>
                <td><strong>€ <?php echo number_format($invoice->total, 2, ',', '.'); ?></strong></td>
                <td>
                    <strong style="color: <?php echo $invoice->status === 'paid' ? '#0a7d3e' : '#d63638'; ?>;">
                        € <?php echo number_format($invoice->net_to_pay, 2, ',', '.'); ?>
                    </strong>
                </td>
                <td><?php echo FRF_Admin_Invoices::get_status_badge($invoice->status); ?></td>
                <td class="frf-actions">
                    <a href="<?php echo admin_url('admin.php?page=fatture-rf-invoices&view=view&id=' . $invoice->id); ?>" 
                       class="frf-action-link">
                        <?php _e('Visualizza', 'fatture-rf'); ?>
                    </a>
                    <?php if ($invoice->status === 'draft'): ?>
                    <a href="<?php echo admin_url('admin.php?page=fatture-rf-invoices&view=edit&id=' . $invoice->id); ?>" 
                       class="frf-action-link">
                        <?php _e('Modifica', 'fatture-rf'); ?>
                    </a>
                    <?php endif; ?>
                    <?php if (in_array($invoice->status, ['draft', 'cancelled'])): ?>
                    <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=fatture-rf-invoices&action=delete_invoice&id=' . $invoice->id), 'frf_action'); ?>" 
                       class="frf-action-link frf-delete-link" style="color: #d63638;">
                        <?php _e('Elimina', 'fatture-rf'); ?>
                    </a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <!-- Summary -->
    <div class="frf-card" style="margin-top: 20px;">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
            <?php
            $total = array_sum(array_column($invoices, 'total'));
            $total_paid = array_sum(array_map(function($inv) {
                return $inv->status === 'paid' ? $inv->total : 0;
            }, $invoices));
            $total_outstanding = $total - $total_paid;
            ?>
            <div style="text-align: center; padding: 20px;">
                <div style="font-size: 12px; color: #666; margin-bottom: 5px;">
                    <?php _e('Totale Fatture', 'fatture-rf'); ?>
                </div>
                <div style="font-size: 24px; font-weight: bold; color: #2271b1;">
                    <?php echo count($invoices); ?>
                </div>
            </div>
            <div style="text-align: center; padding: 20px;">
                <div style="font-size: 12px; color: #666; margin-bottom: 5px;">
                    <?php _e('Importo Totale', 'fatture-rf'); ?>
                </div>
                <div style="font-size: 24px; font-weight: bold;">
                    € <?php echo number_format($total, 2, ',', '.'); ?>
                </div>
            </div>
            <div style="text-align: center; padding: 20px;">
                <div style="font-size: 12px; color: #666; margin-bottom: 5px;">
                    <?php _e('Incassato', 'fatture-rf'); ?>
                </div>
                <div style="font-size: 24px; font-weight: bold; color: #0a7d3e;">
                    € <?php echo number_format($total_paid, 2, ',', '.'); ?>
                </div>
            </div>
            <div style="text-align: center; padding: 20px;">
                <div style="font-size: 12px; color: #666; margin-bottom: 5px;">
                    <?php _e('Da Incassare', 'fatture-rf'); ?>
                </div>
                <div style="font-size: 24px; font-weight: bold; color: #d63638;">
                    € <?php echo number_format($total_outstanding, 2, ',', '.'); ?>
                </div>
            </div>
        </div>
    </div>
    
    <?php else: ?>
    <div class="frf-card">
        <p style="text-align: center; padding: 40px; color: #666;">
            <?php _e('Nessuna fattura trovata.', 'fatture-rf'); ?>
            <br><br>
            <a href="<?php echo admin_url('admin.php?page=fatture-rf-invoices&view=add'); ?>" class="button button-primary">
                <?php _e('Crea la prima fattura', 'fatture-rf'); ?>
            </a>
        </p>
    </div>
    <?php endif; ?>
</div>