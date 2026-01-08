<?php
/**
 * Clients List View Template
 * Path: admin/views/clients-list.php
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php _e('Clients', 'fatture-rf'); ?></h1>
    <a href="<?php echo admin_url('admin.php?page=fatture-rf-clients&view=add'); ?>" class="page-title-action">
        <?php _e('Add New', 'fatture-rf'); ?>
    </a>
    <hr class="wp-header-end">
    
    <?php settings_errors('frf_messages'); ?>
    
    <!-- Filters -->
    <div class="frf-filters">
        <form method="get" action="">
            <input type="hidden" name="page" value="fatture-rf-clients">
            
            <select name="client_type" id="client_type">
                <option value=""><?php _e('All Types', 'fatture-rf'); ?></option>
                <option value="IT" <?php selected($client_type, 'IT'); ?>><?php _e('Italia', 'fatture-rf'); ?></option>
                <option value="EU" <?php selected($client_type, 'EU'); ?>><?php _e('EU', 'fatture-rf'); ?></option>
                <option value="NON_EU" <?php selected($client_type, 'NON_EU'); ?>><?php _e('Extra-EU', 'fatture-rf'); ?></option>
            </select>
            
            <input type="search" name="s" value="<?php echo esc_attr($search); ?>" 
                   placeholder="<?php _e('Search client...', 'fatture-rf'); ?>">
            
            <button type="submit" class="button"><?php _e('Filter', 'fatture-rf'); ?></button>
            
            <?php if (!empty($client_type) || !empty($search)): ?>
            <a href="<?php echo admin_url('admin.php?page=fatture-rf-clients'); ?>" class="button">
                <?php _e('Reset filter', 'fatture-rf'); ?>
            </a>
            <?php endif; ?>
        </form>
    </div>
    
    <!-- Clients Table -->
    <?php if (!empty($clients)): ?>
    <table class="frf-table">
        <thead>
            <tr>
                <th><?php _e('Business Name', 'fatture-rf'); ?></th>
                <th><?php _e('P.IVA / CF', 'fatture-rf'); ?></th>
                <th><?php _e('Type', 'fatture-rf'); ?></th>
                <th><?php _e('City', 'fatture-rf'); ?></th>
                <th><?php _e('Email', 'fatture-rf'); ?></th>
                <th><?php _e('Fatture', 'fatture-rf'); ?></th>
                <th><?php _e('Actions', 'fatture-rf'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($clients as $client): 
                $stats = (new FRF_Client())->get_client_stats($client->id);
            ?>
            <tr>
                <td>
                    <strong>
                        <a href="<?php echo admin_url('admin.php?page=fatture-rf-clients&view=view&id=' . $client->id); ?>">
                            <?php echo esc_html($client->business_name); ?>
                        </a>
                    </strong>
                </td>
                <td>
                    <?php if (!empty($client->vat_number)): ?>
                        <span title="P.IVA"><?php echo esc_html($client->vat_number); ?></span>
                    <?php elseif (!empty($client->tax_code)): ?>
                        <span title="CF"><?php echo esc_html($client->tax_code); ?></span>
                    <?php endif; ?>
                </td>
                <td><?php echo FRF_Admin_Clients::get_client_type_badge($client->client_type); ?></td>
                <td><?php echo esc_html($client->city); ?></td>
                <td>
                    <?php if (!empty($client->email)): ?>
                        <a href="mailto:<?php echo esc_attr($client->email); ?>">
                            <?php echo esc_html($client->email); ?>
                        </a>
                    <?php endif; ?>
                </td>
                <td class="frf-text-center">
                    <a href="<?php echo admin_url('admin.php?page=fatture-rf-invoices&client_id=' . $client->id); ?>">
                        <?php echo number_format($stats->total_invoices, 0, ',', '.'); ?>
                    </a>
                </td>
                <td class="frf-actions">
                    <a href="<?php echo admin_url('admin.php?page=fatture-rf-clients&view=view&id=' . $client->id); ?>" 
                       class="frf-action-link">
                        <?php _e('View', 'fatture-rf'); ?>
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=fatture-rf-clients&view=edit&id=' . $client->id); ?>" 
                       class="frf-action-link">
                        <?php _e('Edit', 'fatture-rf'); ?>
                    </a>
                    <?php if ($stats->total_invoices == 0): ?>
                    <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=fatture-rf-clients&action=delete_client&id=' . $client->id), 'frf_action'); ?>" 
                       class="frf-action-link frf-delete-link" style="color: #d63638;">
                        <?php _e('Delete', 'fatture-rf'); ?>
                    </a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
    <div class="frf-card">
        <p style="text-align: center; padding: 40px; color: #666;">
            <?php _e('No clients found.', 'fatture-rf'); ?>
            <br><br>
            <a href="<?php echo admin_url('admin.php?page=fatture-rf-clients&view=add'); ?>" class="button button-primary">
                <?php _e('Add first client', 'fatture-rf'); ?>
            </a>
        </p>
    </div>
    <?php endif; ?>
</div>