<?php
/**
 * WooCommerce Stores List View
 * Path: admin/views/woocommerce/stores-list.php
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php _e('Negozi WooCommerce', 'fatture-rf'); ?></h1>
    <a href="<?php echo admin_url('admin.php?page=fatture-rf-woocommerce&view=add'); ?>" class="page-title-action">
        <?php _e('Collega Negozio', 'fatture-rf'); ?>
    </a>
    <hr class="wp-header-end">
    
    <?php settings_errors('frf_messages'); ?>
    
    <?php if (empty($stores)): ?>
    <div class="frf-card">
        <div style="text-align: center; padding: 60px 20px;">
            <span class="dashicons dashicons-store" style="font-size: 80px; color: #ddd; margin-bottom: 20px;"></span>
            <h2><?php _e('Nessun negozio collegato', 'fatture-rf'); ?></h2>
            <p style="color: #666; max-width: 600px; margin: 20px auto;">
                <?php _e('Collega il tuo negozio WooCommerce per sincronizzare automaticamente ordini e clienti. Potrai generare fatture direttamente dagli ordini WooCommerce.', 'fatture-rf'); ?>
            </p>
            <a href="<?php echo admin_url('admin.php?page=fatture-rf-woocommerce&view=add'); ?>" 
               class="button button-primary button-large">
                <span class="dashicons dashicons-plus-alt" style="margin-top: 3px;"></span>
                <?php _e('Collega il Primo Negozio', 'fatture-rf'); ?>
            </a>
        </div>
    </div>
    <?php else: ?>
    
    <table class="frf-table">
        <thead>
            <tr>
                <th><?php _e('Nome Negozio', 'fatture-rf'); ?></th>
                <th><?php _e('URL', 'fatture-rf'); ?></th>
                <th><?php _e('Ordini Sincronizzati', 'fatture-rf'); ?></th>
                <th><?php _e('Ultima Sync', 'fatture-rf'); ?></th>
                <th><?php _e('Auto Sync', 'fatture-rf'); ?></th>
                <th><?php _e('Stato', 'fatture-rf'); ?></th>
                <th><?php _e('Azioni', 'fatture-rf'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $store_model = new FRF_WooCommerce_Store();
            foreach ($stores as $store): 
                $stats = $store_model->get_store_stats($store->id);
            ?>
            <tr>
                <td><strong><?php echo esc_html($store->store_name); ?></strong></td>
                <td>
                    <a href="<?php echo esc_url($store->store_url); ?>" target="_blank">
                        <?php echo esc_html(parse_url($store->store_url, PHP_URL_HOST)); ?>
                        <span class="dashicons dashicons-external" style="font-size: 14px;"></span>
                    </a>
                </td>
                <td class="frf-text-center">
                    <a href="<?php echo admin_url('admin.php?page=fatture-rf-woocommerce&view=orders&store_id=' . $store->id); ?>">
                        <strong><?php echo number_format($stats->total_orders ?? 0, 0, ',', '.'); ?></strong>
                    </a>
                    <br>
                    <small style="color: #666;">
                        <?php echo number_format($stats->invoiced_orders ?? 0, 0, ',', '.'); ?> <?php _e('fatturati', 'fatture-rf'); ?>
                    </small>
                </td>
                <td>
                    <?php if ($store->last_sync_at): ?>
                        <?php echo date_i18n('d/m/Y H:i', strtotime($store->last_sync_at)); ?>
                    <?php else: ?>
                        <span style="color: #999;">—</span>
                    <?php endif; ?>
                </td>
                <td class="frf-text-center">
                    <?php if ($store->auto_sync): ?>
                        <span class="dashicons dashicons-yes" style="color: #0a7d3e;"></span>
                        <br><small><?php echo $store->sync_interval; ?> min</small>
                    <?php else: ?>
                        <span class="dashicons dashicons-no" style="color: #999;"></span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php
                    $status_colors = array(
                        'active' => 'success',
                        'inactive' => 'secondary',
                        'error' => 'error'
                    );
                    $status_labels = array(
                        'active' => __('Attivo', 'fatture-rf'),
                        'inactive' => __('Inattivo', 'fatture-rf'),
                        'error' => __('Errore', 'fatture-rf')
                    );
                    ?>
                    <span class="frf-badge frf-badge-<?php echo $status_colors[$store->status]; ?>">
                        <?php echo $status_labels[$store->status]; ?>
                    </span>
                </td>
                <td class="frf-actions">
                    <button class="button button-small frf-sync-store" data-store-id="<?php echo $store->id; ?>">
                        <span class="dashicons dashicons-update" style="margin-top: 3px;"></span>
                        <?php _e('Sincronizza', 'fatture-rf'); ?>
                    </button>
                    <a href="<?php echo admin_url('admin.php?page=fatture-rf-woocommerce&view=edit&id=' . $store->id); ?>" 
                       class="frf-action-link">
                        <?php _e('Modifica', 'fatture-rf'); ?>
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=fatture-rf-woocommerce&view=orders&store_id=' . $store->id); ?>" 
                       class="frf-action-link">
                        <?php _e('Ordini', 'fatture-rf'); ?>
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <!-- Info Box -->
    <div class="frf-card" style="margin-top: 20px; background: #f0f6fc; border-color: #2271b1;">
        <h3 style="margin-top: 0;">💡 <?php _e('Come funziona', 'fatture-rf'); ?></h3>
        <ul style="margin: 10px 0;">
            <li><?php _e('Collega il tuo negozio WooCommerce usando le chiavi API', 'fatture-rf'); ?></li>
            <li><?php _e('Gli ordini vengono sincronizzati automaticamente (se abilitato) o manualmente', 'fatture-rf'); ?></li>
            <li><?php _e('Crea fatture direttamente dagli ordini con un click', 'fatture-rf'); ?></li>
            <li><?php _e('I clienti vengono creati automaticamente dalla prima fattura', 'fatture-rf'); ?></li>
        </ul>
    </div>
    
    <?php endif; ?>
</div>

<script>
jQuery(document).ready(function($) {
    // Sync store button
    $('.frf-sync-store').on('click', function(e) {
        e.preventDefault();
        
        var btn = $(this);
        var storeId = btn.data('store-id');
        var originalText = btn.html();
        
        btn.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> Sincronizzazione...');
        
        $.ajax({
            url: frfAdmin.ajaxUrl,
            method: 'POST',
            data: {
                action: 'frf_sync_woo_store',
                nonce: frfAdmin.nonce,
                store_id: storeId
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    location.reload();
                } else {
                    alert('Errore: ' + response.data.message);
                    btn.prop('disabled', false).html(originalText);
                }
            },
            error: function() {
                alert('Errore di connessione');
                btn.prop('disabled', false).html(originalText);
            }
        });
    });
});
</script>

<style>
.dashicons.spin {
    animation: spin 1s linear infinite;
}
@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}
</style>