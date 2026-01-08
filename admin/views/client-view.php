<?php
/**
 * Client View Template
 * Path: admin/views/client-view.php
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1>
        <?php echo esc_html($client->business_name); ?>
        <?php echo FRF_Admin_Clients::get_client_type_badge($client->client_type); ?>
    </h1>
    
    <p style="margin-top: 10px;">
        <a href="<?php echo admin_url('admin.php?page=fatture-rf-clients&view=edit&id=' . $client->id); ?>" 
           class="button button-primary">
            <?php _e('Edit Client', 'fatture-rf'); ?>
        </a>
        <a href="<?php echo admin_url('admin.php?page=fatture-rf-invoices&view=add&client_id=' . $client->id); ?>" 
           class="button">
            <?php _e('New Invoice', 'fatture-rf'); ?>
        </a>
        <?php if ($stats->total_invoices == 0): ?>
        <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=fatture-rf-clients&action=delete_client&id=' . $client->id), 'frf_action'); ?>" 
           class="button frf-delete-link" style="color: #d63638;">
            <?php _e('Delete Client', 'fatture-rf'); ?>
        </a>
        <?php endif; ?>
        <a href="<?php echo admin_url('admin.php?page=fatture-rf-clients'); ?>" class="button">
            <?php _e('← Back to All Clients', 'fatture-rf'); ?>
        </a>
    </p>
    
    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-top: 20px;">
        <!-- Client Information -->
        <div>
            <div class="frf-card">
                <div class="frf-card-header">
                    <h2 class="frf-card-title"><?php _e('Client Information', 'fatture-rf'); ?></h2>
                </div>
                
                <table class="widefat" style="border: none;">
                    <tr>
                        <th style="width: 200px; background: #f8f9fa;"><?php _e('Business Name', 'fatture-rf'); ?></th>
                        <td><strong><?php echo esc_html($client->business_name); ?></strong></td>
                    </tr>
                    <?php if (!empty($client->vat_number)): ?>
                    <tr>
                        <th style="background: #f8f9fa;"><?php _e('Partita IVA', 'fatture-rf'); ?></th>
                        <td><?php echo esc_html($client->vat_number); ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php if (!empty($client->tax_code)): ?>
                    <tr>
                        <th style="background: #f8f9fa;"><?php _e('Codice Fiscale', 'fatture-rf'); ?></th>
                        <td><?php echo esc_html($client->tax_code); ?></td>
                    </tr>
                    <?php endif; ?>
                    <tr>
                        <th style="background: #f8f9fa;"><?php _e('Client Type', 'fatture-rf'); ?></th>
                        <td><?php echo FRF_Admin_Clients::get_client_type_badge($client->client_type); ?></td>
                    </tr>
                    <?php if (!empty($client->address)): ?>
                    <tr>
                        <th style="background: #f8f9fa;"><?php _e('Indirizzo', 'fatture-rf'); ?></th>
                        <td>
                            <?php echo esc_html($client->address); ?><br>
                            <?php echo esc_html($client->postal_code . ' ' . $client->city); ?>
                            <?php if (!empty($client->province)): ?>
                                (<?php echo esc_html($client->province); ?>)
                            <?php endif; ?>
                            <br>
                            <?php echo esc_html($client->country); ?>
                        </td>
                    </tr>
                    <?php endif; ?>
                    <?php if (!empty($client->email)): ?>
                    <tr>
                        <th style="background: #f8f9fa;"><?php _e('Email', 'fatture-rf'); ?></th>
                        <td><a href="mailto:<?php echo esc_attr($client->email); ?>"><?php echo esc_html($client->email); ?></a></td>
                    </tr>
                    <?php endif; ?>
                    <?php if (!empty($client->pec_email)): ?>
                    <tr>
                        <th style="background: #f8f9fa;"><?php _e('PEC', 'fatture-rf'); ?></th>
                        <td><a href="mailto:<?php echo esc_attr($client->pec_email); ?>"><?php echo esc_html($client->pec_email); ?></a></td>
                    </tr>
                    <?php endif; ?>
                    <?php if (!empty($client->phone)): ?>
                    <tr>
                        <th style="background: #f8f9fa;"><?php _e('Telefono', 'fatture-rf'); ?></th>
                        <td><?php echo esc_html($client->phone); ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php if (!empty($client->sdi_code)): ?>
                    <tr>
                        <th style="background: #f8f9fa;"><?php _e('Codice SDI', 'fatture-rf'); ?></th>
                        <td><code><?php echo esc_html($client->sdi_code); ?></code></td>
                    </tr>
                    <?php endif; ?>
                </table>
            </div>
            
            <!-- Invoices List -->
            <div class="frf-card" style="margin-top: 20px;">
                <div class="frf-card-header">
                    <h2 class="frf-card-title">
                        <?php _e('Invoices', 'fatture-rf'); ?>
                        <a href="<?php echo admin_url('admin.php?page=fatture-rf-invoices&view=add&client_id=' . $client->id); ?>" 
                           class="button button-small" style="float: right;">
                            <?php _e('+ New Invoice', 'fatture-rf'); ?>
                        </a>
                    </h2>
                </div>
                
                <?php if (!empty($invoices)): ?>
                <table class="frf-table">
                    <thead>
                        <tr>
                            <th><?php _e('Number', 'fatture-rf'); ?></th>
                            <th><?php _e('Date', 'fatture-rf'); ?></th>
                            <th><?php _e('Amount', 'fatture-rf'); ?></th>
                            <th><?php _e('Status', 'fatture-rf'); ?></th>
                            <th><?php _e('Actions', 'fatture-rf'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($invoices as $invoice): ?>
                        <tr>
                            <td><strong><?php echo esc_html($invoice->invoice_number); ?></strong></td>
                            <td><?php echo date_i18n(get_option('date_format'), strtotime($invoice->invoice_date)); ?></td>
                            <td><strong>€ <?php echo number_format($invoice->total, 2, ',', '.'); ?></strong></td>
                            <td><?php echo FRF_Admin_Invoices::get_status_badge($invoice->status); ?></td>
                            <td class="frf-actions">
                                <a href="<?php echo admin_url('admin.php?page=fatture-rf-invoices&view=view&id=' . $invoice->id); ?>" 
                                   class="frf-action-link">
                                    <?php _e('View', 'fatture-rf'); ?>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p style="padding: 20px; text-align: center; color: #666;">
                    <?php _e('No invoices found for this client.', 'fatture-rf'); ?>
                </p>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Statistics Sidebar -->
        <div>
            <div class="frf-card">
                <div class="frf-card-header">
                    <h2 class="frf-card-title"><?php _e('Statistics', 'fatture-rf'); ?></h2>
                </div>
                
                <div style="padding: 10px 0;">
                    <div style="padding: 15px; background: #f8f9fa; border-radius: 4px; margin-bottom: 15px;">
                        <div style="font-size: 12px; color: #666; margin-bottom: 5px;">
                            <?php _e('Total Invoices', 'fatture-rf'); ?>
                        </div>
                        <div style="font-size: 28px; font-weight: bold; color: #2271b1;">
                            <?php echo number_format($stats->total_invoices, 0, ',', '.'); ?>
                        </div>
                    </div>
                    
                    <div style="padding: 15px; background: #f8f9fa; border-radius: 4px; margin-bottom: 15px;">
                        <div style="font-size: 12px; color: #666; margin-bottom: 5px;">
                            <?php _e('Paid Invoices', 'fatture-rf'); ?>
                        </div>
                        <div style="font-size: 28px; font-weight: bold; color: #0a7d3e;">
                            <?php echo number_format($stats->paid_invoices, 0, ',', '.'); ?>
                        </div>
                    </div>
                    
                    <div style="padding: 15px; background: #f8f9fa; border-radius: 4px; margin-bottom: 15px;">
                        <div style="font-size: 12px; color: #666; margin-bottom: 5px;">
                            <?php _e('Total Received', 'fatture-rf'); ?>
                        </div>
                        <div style="font-size: 24px; font-weight: bold; color: #0a7d3e;">
                            € <?php echo number_format($stats->total_paid, 2, ',', '.'); ?>
                        </div>
                    </div>
                    
                    <div style="padding: 15px; background: #f8f9fa; border-radius: 4px;">
                        <div style="font-size: 12px; color: #666; margin-bottom: 5px;">
                            <?php _e('Outstanding', 'fatture-rf'); ?>
                        </div>
                        <div style="font-size: 24px; font-weight: bold; color: #d63638;">
                            € <?php echo number_format($stats->total_outstanding, 2, ',', '.'); ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="frf-card" style="margin-top: 20px;">
                <div class="frf-card-header">
                    <h2 class="frf-card-title"><?php _e('Quick Info', 'fatture-rf'); ?></h2>
                </div>
                
                <div style="padding: 10px 0; font-size: 13px;">
                    <p style="margin: 10px 0;">
                        <strong><?php _e('Client since:', 'fatture-rf'); ?></strong><br>
                        <?php echo date_i18n(get_option('date_format'), strtotime($client->created_at)); ?>
                    </p>
                    <?php if ($client->updated_at != $client->created_at): ?>
                    <p style="margin: 10px 0;">
                        <strong><?php _e('Last updated:', 'fatture-rf'); ?></strong><br>
                        <?php echo date_i18n(get_option('date_format'), strtotime($client->updated_at)); ?>
                    </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>