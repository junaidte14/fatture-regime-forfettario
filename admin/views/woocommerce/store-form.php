<?php
/**
 * WooCommerce Store Form View
 * Path: admin/views/woocommerce/store-form.php
 */

if (!defined('ABSPATH')) {
    exit;
}

$is_edit = isset($store) && !empty($store);
$page_title = $is_edit ? __('Modifica Negozio WooCommerce', 'fatture-rf') : 
    __('Collega Negozio WooCommerce', 'fatture-rf');
?>

<div class="wrap">
    <h1><?php echo esc_html($page_title); ?></h1>
    
    <?php if (isset($error)): ?>
    <div class="notice notice-error">
        <p><?php echo esc_html($error); ?></p>
    </div>
    <?php endif; ?>
    
    <form method="post" action="" class="frf-form" id="woo-store-form">
        <?php wp_nonce_field('frf_save_store'); ?>
        
        <div class="frf-card">
            <div class="frf-card-header">
                <h2 class="frf-card-title"><?php _e('Informazioni Negozio', 'fatture-rf'); ?></h2>
            </div>
            
            <div class="frf-form-row">
                <div class="frf-form-group">
                    <label for="store_name">
                        <?php _e('Nome Negozio', 'fatture-rf'); ?> <span style="color: red;">*</span>
                    </label>
                    <input type="text" id="store_name" name="store_name" 
                           value="<?php echo esc_attr($store->store_name ?? ''); ?>" required>
                    <small class="frf-form-help"><?php _e('Nome identificativo per il negozio', 'fatture-rf'); ?></small>
                </div>
                
                <div class="frf-form-group">
                    <label for="store_url">
                        <?php _e('URL Negozio', 'fatture-rf'); ?> <span style="color: red;">*</span>
                    </label>
                    <input type="url" id="store_url" name="store_url" 
                           value="<?php echo esc_attr($store->store_url ?? ''); ?>" 
                           placeholder="https://mio-negozio.com" required>
                    <small class="frf-form-help"><?php _e('URL completo del negozio WooCommerce', 'fatture-rf'); ?></small>
                </div>
            </div>
        </div>
        
        <div class="frf-card">
            <div class="frf-card-header">
                <h2 class="frf-card-title"><?php _e('Credenziali API WooCommerce', 'fatture-rf'); ?></h2>
            </div>
            
            <div class="frf-form-row">
                <div class="frf-form-group">
                    <label for="consumer_key">
                        <?php _e('Consumer Key', 'fatture-rf'); ?> <span style="color: red;">*</span>
                    </label>
                    <input type="text" id="consumer_key" name="consumer_key" 
                           value="<?php echo esc_attr($store->consumer_key ?? ''); ?>" required>
                    <small class="frf-form-help"><?php _e('Chiave API WooCommerce (inizia con ck_)', 'fatture-rf'); ?></small>
                </div>
                
                <div class="frf-form-group">
                    <label for="consumer_secret">
                        <?php _e('Consumer Secret', 'fatture-rf'); ?> <span style="color: red;">*</span>
                    </label>
                    <input type="text" id="consumer_secret" name="consumer_secret" 
                           value="<?php echo esc_attr($store->consumer_secret ?? ''); ?>" required>
                    <small class="frf-form-help"><?php _e('Chiave segreta API (inizia con cs_)', 'fatture-rf'); ?></small>
                </div>
            </div>
            
            <div class="frf-form-group">
                <button type="button" id="test-connection" class="button">
                    <span class="dashicons dashicons-admin-plugins" style="margin-top: 3px;"></span>
                    <?php _e('Testa Connessione', 'fatture-rf'); ?>
                </button>
                <span id="connection-status" style="margin-left: 10px;"></span>
            </div>
            
            <div class="notice notice-info" style="margin-top: 15px;">
                <p>
                    <strong><?php _e('Come ottenere le chiavi API:', 'fatture-rf'); ?></strong><br>
                    1. Nel tuo negozio WooCommerce, vai su <strong>WooCommerce > Impostazioni > Avanzate > API REST</strong><br>
                    2. Clicca su <strong>Aggiungi chiave</strong><br>
                    3. Imposta una descrizione (es: "Fatture RF")<br>
                    4. Imposta permessi: <strong>Lettura/Scrittura</strong><br>
                    5. Clicca su <strong>Genera chiave API</strong><br>
                    6. Copia Consumer Key e Consumer Secret
                </p>
            </div>
        </div>
        
        <div class="frf-card">
            <div class="frf-card-header">
                <h2 class="frf-card-title"><?php _e('Impostazioni Sincronizzazione', 'fatture-rf'); ?></h2>
            </div>
            
            <div class="frf-form-row">
                <div class="frf-form-group">
                    <label for="sync_from_date"><?php _e('Sincronizza Ordini Dal', 'fatture-rf'); ?></label>
                    <input type="date" id="sync_from_date" name="sync_from_date" 
                           value="<?php echo esc_attr($store->sync_from_date ?? date('Y-m-d', strtotime('-30 days'))); ?>">
                    <small class="frf-form-help">
                        <?php _e('Solo ordini da questa data in poi verranno sincronizzati', 'fatture-rf'); ?>
                    </small>
                </div>
                
                <div class="frf-form-group">
                    <label for="sync_interval"><?php _e('Intervallo Sincronizzazione (minuti)', 'fatture-rf'); ?></label>
                    <select id="sync_interval" name="sync_interval">
                        <option value="15" <?php selected($store->sync_interval ?? 60, 15); ?>>15 minuti</option>
                        <option value="30" <?php selected($store->sync_interval ?? 60, 30); ?>>30 minuti</option>
                        <option value="60" <?php selected($store->sync_interval ?? 60, 60); ?>>1 ora</option>
                        <option value="120" <?php selected($store->sync_interval ?? 60, 120); ?>>2 ore</option>
                        <option value="360" <?php selected($store->sync_interval ?? 60, 360); ?>>6 ore</option>
                        <option value="1440" <?php selected($store->sync_interval ?? 60, 1440); ?>>24 ore</option>
                    </select>
                </div>
            </div>
            
            <div class="frf-form-row">
                <div class="frf-form-group">
                    <label>
                        <input type="checkbox" id="auto_sync" name="auto_sync" 
                               value="1" <?php checked($store->auto_sync ?? false); ?>>
                        <?php _e('Abilita sincronizzazione automatica', 'fatture-rf'); ?>
                    </label>
                    <small class="frf-form-help">
                        <?php _e('Gli ordini verranno sincronizzati automaticamente secondo l\'intervallo impostato', 'fatture-rf'); ?>
                    </small>
                </div>
                
                <?php if ($is_edit): ?>
                <div class="frf-form-group">
                    <label for="status"><?php _e('Stato Negozio', 'fatture-rf'); ?></label>
                    <select id="status" name="status">
                        <option value="active" <?php selected($store->status, 'active'); ?>>
                            <?php _e('Attivo', 'fatture-rf'); ?>
                        </option>
                        <option value="inactive" <?php selected($store->status, 'inactive'); ?>>
                            <?php _e('Inattivo', 'fatture-rf'); ?>
                        </option>
                    </select>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <p class="submit">
            <button type="submit" name="frf_save_store" class="button button-primary button-large">
                <?php echo $is_edit ? __('Aggiorna Negozio', 'fatture-rf') : __('Salva e Collega Negozio', 'fatture-rf'); ?>
            </button>
            <a href="<?php echo admin_url('admin.php?page=fatture-rf-woocommerce&view=stores'); ?>" 
               class="button button-large">
                <?php _e('Annulla', 'fatture-rf'); ?>
            </a>
        </p>
    </form>
</div>

<script>
jQuery(document).ready(function($) {
    // Test connection
    $('#test-connection').on('click', function() {
        var btn = $(this);
        var statusEl = $('#connection-status');
        var originalText = btn.html();
        
        var storeUrl = $('#store_url').val();
        var consumerKey = $('#consumer_key').val();
        var consumerSecret = $('#consumer_secret').val();
        
        if (!storeUrl || !consumerKey || !consumerSecret) {
            statusEl.html('<span style="color: #d63638;">⚠️ Compila tutti i campi richiesti</span>');
            return;
        }
        
        btn.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> Test...');
        statusEl.html('<span style="color: #666;">Connessione in corso...</span>');
        
        $.ajax({
            url: frfAdmin.ajaxUrl,
            method: 'POST',
            data: {
                action: 'frf_test_woo_connection',
                nonce: frfAdmin.nonce,
                store_url: storeUrl,
                consumer_key: consumerKey,
                consumer_secret: consumerSecret
            },
            success: function(response) {
                if (response.success) {
                    statusEl.html('<span style="color: #0a7d3e;">✓ ' + response.data.message + '</span>');
                } else {
                    statusEl.html('<span style="color: #d63638;">✗ ' + response.data.message + '</span>');
                }
            },
            error: function() {
                statusEl.html('<span style="color: #d63638;">✗ Errore di connessione</span>');
            },
            complete: function() {
                btn.prop('disabled', false).html(originalText);
            }
        });
    });
});
</script>