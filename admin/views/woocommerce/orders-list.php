<?php
/**
 * WooCommerce Orders List View
 * Path: admin/views/woocommerce/orders-list.php
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php _e('Ordini WooCommerce Sincronizzati', 'fatture-rf'); ?></h1>
    <a href="<?php echo admin_url('admin.php?page=fatture-rf-woocommerce&view=stores'); ?>" class="page-title-action">
        <?php _e('← Gestisci Negozi', 'fatture-rf'); ?>
    </a>
    <hr class="wp-header-end">
    
    <?php settings_errors('frf_messages'); ?>
    
    <!-- Filters -->
    <div class="frf-filters">
        <form method="get" action="">
            <input type="hidden" name="page" value="fatture-rf-woocommerce">
            <input type="hidden" name="view" value="orders">
            
            <select name="store_id" id="store_id">
                <option value=""><?php _e('Tutti i negozi', 'fatture-rf'); ?></option>
                <?php foreach ($stores as $store_option): ?>
                <option value="<?php echo $store_option->id; ?>" <?php selected($store_id, $store_option->id); ?>>
                    <?php echo esc_html($store_option->store_name); ?>
                </option>
                <?php endforeach; ?>
            </select>
            
            <select name="status" id="status">
                <option value=""><?php _e('Tutti gli stati', 'fatture-rf'); ?></option>
                <option value="pending" <?php selected($status, 'pending'); ?>><?php _e('In attesa', 'fatture-rf'); ?></option>
                <option value="processing" <?php selected($status, 'processing'); ?>><?php _e('In elaborazione', 'fatture-rf'); ?></option>
                <option value="completed" <?php selected($status, 'completed'); ?>><?php _e('Completato', 'fatture-rf'); ?></option>
                <option value="on-hold" <?php selected($status, 'on-hold'); ?>><?php _e('In sospeso', 'fatture-rf'); ?></option>
                <option value="cancelled" <?php selected($status, 'cancelled'); ?>><?php _e('Annullato', 'fatture-rf'); ?></option>
            </select>
            
            <select name="has_invoice" id="has_invoice">
                <option value=""><?php _e('Fattura', 'fatture-rf'); ?></option>
                <option value="yes" <?php selected($has_invoice, 'yes'); ?>><?php _e('Con fattura', 'fatture-rf'); ?></option>
                <option value="no" <?php selected($has_invoice, 'no'); ?>><?php _e('Senza fattura', 'fatture-rf'); ?></option>
            </select>
            
            <button type="submit" class="button"><?php _e('Filtra', 'fatture-rf'); ?></button>
            
            <?php if (!empty($store_id) || !empty($status) || !empty($has_invoice)): ?>
            <a href="<?php echo admin_url('admin.php?page=fatture-rf-woocommerce&view=orders'); ?>" class="button">
                <?php _e('Resetta filtri', 'fatture-rf'); ?>
            </a>
            <?php endif; ?>
        </form>
    </div>
    
    <!-- Orders Table -->
    <?php if (!empty($orders)): ?>
    <table class="frf-table">
        <thead>
            <tr>
                <th><?php _e('Negozio', 'fatture-rf'); ?></th>
                <th><?php _e('Ordine #', 'fatture-rf'); ?></th>
                <th><?php _e('Data', 'fatture-rf'); ?></th>
                <th><?php _e('Cliente', 'fatture-rf'); ?></th>
                <th><?php _e('Totale', 'fatture-rf'); ?></th>
                <th><?php _e('Stato', 'fatture-rf'); ?></th>
                <th><?php _e('Fattura', 'fatture-rf'); ?></th>
                <th><?php _e('Azioni', 'fatture-rf'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $store_model = new FRF_WooCommerce_Store();
            foreach ($orders as $order): 
                $store = $store_model->get_store($order->store_id);
                $customer = json_decode($order->customer_data, true);
                var_dump($store);
                var_dump($customer);
                var_dump($order);
            ?>
            <tr>
                <td>
                    <strong><?php echo esc_html($store->store_name); ?></strong>
                </td>
                <td>
                    <a href="<?php echo esc_url(trailingslashit($store->store_url) . 'wp-admin/post.php?post=' . $order->woo_order_id . '&action=edit'); ?>" 
                       target="_blank">
                        #<?php echo esc_html($order->order_number); ?>
                        <span class="dashicons dashicons-external" style="font-size: 14px;"></span>
                    </a>
                </td>
                <td><?php echo date_i18n(get_option('date_format'), strtotime($order->order_date)); ?></td>
                <td>
                    <?php 
                    if (!empty($customer['company'])) {
                        echo esc_html($customer['company']);
                    } else {
                        echo esc_html(trim($customer['first_name'] . ' ' . $customer['last_name']));
                    }
                    ?>
                    <?php if (!empty($customer['email'])): ?>
                    <br><small style="color: #666;"><?php echo esc_html($customer['email']); ?></small>
                    <?php endif; ?>
                </td>
                <td><strong>€ <?php echo number_format($order->total, 2, ',', '.'); ?></strong></td>
                <td><?php echo FRF_Admin_WooCommerce::get_woo_status_badge($order->status); ?></td>
                <td class="frf-text-center">
                    <?php if ($order->invoice_id): ?>
                        <a href="<?php echo admin_url('admin.php?page=fatture-rf-invoices&view=view&id=' . $order->invoice_id); ?>">
                            <span class="dashicons dashicons-yes-alt" style="color: #0a7d3e; font-size: 20px;"></span>
                            <br><small><?php _e('Visualizza', 'fatture-rf'); ?></small>
                        </a>
                    <?php else: ?>
                        <span class="dashicons dashicons-minus" style="color: #999; font-size: 20px;"></span>
                    <?php endif; ?>
                </td>
                <td class="frf-actions">
                    <?php if (!$order->invoice_id && in_array($order->status, ['processing', 'completed'])): ?>
                    <button class="button button-small button-primary frf-create-invoice" 
                            data-order-id="<?php echo $order->id; ?>"
                            data-order-number="<?php echo esc_attr($order->order_number); ?>">
                        <span class="dashicons dashicons-media-spreadsheet" style="margin-top: 3px;"></span>
                        <?php _e('Crea Fattura', 'fatture-rf'); ?>
                    </button>
                    <?php elseif ($order->invoice_id): ?>
                    <a href="<?php echo admin_url('admin.php?page=fatture-rf-invoices&view=view&id=' . $order->invoice_id); ?>" 
                       class="button button-small">
                        <?php _e('Vedi Fattura', 'fatture-rf'); ?>
                    </a>
                    <?php else: ?>
                    <span style="color: #999;"><?php _e('Non fatturabile', 'fatture-rf'); ?></span>
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
            $total_orders = count($orders);
            $total_with_invoice = count(array_filter($orders, function($o) { return $o->invoice_id; }));
            $total_without_invoice = $total_orders - $total_with_invoice;
            $total_amount = array_sum(array_column($orders, 'total'));
            ?>
            <div style="text-align: center; padding: 20px;">
                <div style="font-size: 12px; color: #666; margin-bottom: 5px;">
                    <?php _e('Totale Ordini', 'fatture-rf'); ?>
                </div>
                <div style="font-size: 24px; font-weight: bold; color: #2271b1;">
                    <?php echo $total_orders; ?>
                </div>
            </div>
            <div style="text-align: center; padding: 20px;">
                <div style="font-size: 12px; color: #666; margin-bottom: 5px;">
                    <?php _e('Con Fattura', 'fatture-rf'); ?>
                </div>
                <div style="font-size: 24px; font-weight: bold; color: #0a7d3e;">
                    <?php echo $total_with_invoice; ?>
                </div>
            </div>
            <div style="text-align: center; padding: 20px;">
                <div style="font-size: 12px; color: #666; margin-bottom: 5px;">
                    <?php _e('Senza Fattura', 'fatture-rf'); ?>
                </div>
                <div style="font-size: 24px; font-weight: bold; color: #d63638;">
                    <?php echo $total_without_invoice; ?>
                </div>
            </div>
            <div style="text-align: center; padding: 20px;">
                <div style="font-size: 12px; color: #666; margin-bottom: 5px;">
                    <?php _e('Importo Totale', 'fatture-rf'); ?>
                </div>
                <div style="font-size: 24px; font-weight: bold;">
                    € <?php echo number_format($total_amount, 2, ',', '.'); ?>
                </div>
            </div>
        </div>
    </div>
    
    <?php else: ?>
    <div class="frf-card">
        <p style="text-align: center; padding: 40px; color: #666;">
            <?php _e('Nessun ordine sincronizzato.', 'fatture-rf'); ?>
            <br><br>
            <a href="<?php echo admin_url('admin.php?page=fatture-rf-woocommerce&view=stores'); ?>" 
               class="button button-primary">
                <?php _e('Sincronizza Ordini', 'fatture-rf'); ?>
            </a>
        </p>
    </div>
    <?php endif; ?>
</div>

<script>
jQuery(document).ready(function($) {
    // Create invoice from order
    $('.frf-create-invoice').on('click', function() {
        var btn = $(this);
        var orderId = btn.data('order-id');
        var orderNumber = btn.data('order-number');
        
        if (!confirm('Vuoi creare una fattura dall\'ordine #' + orderNumber + '?')) {
            return;
        }
        
        var originalText = btn.html();
        btn.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> Creazione...');
        
        $.ajax({
            url: frfAdmin.ajaxUrl,
            method: 'POST',
            data: {
                action: 'frf_create_invoice_from_woo_order',
                nonce: frfAdmin.nonce,
                order_id: orderId
            },
            success: function(response) {
                if (response.success) {
                    alert('Fattura creata con successo!');
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