<?php
/**
 * Settings View Template
 * Path: admin/views/settings.php
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php _e('Impostazioni Fatture RF', 'fatture-rf'); ?></h1>
    
    <?php settings_errors('frf_messages'); ?>
    
    <?php FRF_Admin_Settings::render_tabs($tab); ?>
    
    <form method="post" action="">
        <?php wp_nonce_field('frf_save_settings'); ?>
        
        <?php if ($tab === 'general'): ?>
        <!-- General Tab -->
        <div class="frf-card">
            <div class="frf-card-header">
                <h2 class="frf-card-title"><?php _e('Dati Aziendali', 'fatture-rf'); ?></h2>
            </div>
            
            <div class="frf-form-row">
                <div class="frf-form-group">
                    <label for="business_name">
                        <?php _e('Ragione Sociale', 'fatture-rf'); ?> <span style="color: red;">*</span>
                    </label>
                    <input type="text" id="business_name" name="business_name" 
                           value="<?php echo esc_attr($current_settings['business_name']); ?>" required>
                </div>
                
                <div class="frf-form-group">
                    <label for="country"><?php _e('Paese', 'fatture-rf'); ?></label>
                    <select id="country" name="country">
                        <option value="IT" <?php selected($current_settings['country'], 'IT'); ?>>Italia</option>
                    </select>
                </div>
            </div>
            
            <div class="frf-form-row">
                <div class="frf-form-group">
                    <label for="vat_number">
                        <?php _e('Partita IVA', 'fatture-rf'); ?> <span style="color: red;">*</span>
                    </label>
                    <input type="text" id="vat_number" name="vat_number" 
                           value="<?php echo esc_attr($current_settings['vat_number']); ?>" required>
                    <small class="frf-form-help"><?php _e('11 cifre', 'fatture-rf'); ?></small>
                </div>
                
                <div class="frf-form-group">
                    <label for="tax_code">
                        <?php _e('Codice Fiscale', 'fatture-rf'); ?> <span style="color: red;">*</span>
                    </label>
                    <input type="text" id="tax_code" name="tax_code" 
                           value="<?php echo esc_attr($current_settings['tax_code']); ?>" maxlength="16" required>
                    <small class="frf-form-help"><?php _e('16 caratteri alfanumerici', 'fatture-rf'); ?></small>
                </div>
            </div>
            
            <div class="frf-form-group">
                <label for="address"><?php _e('Indirizzo', 'fatture-rf'); ?></label>
                <textarea id="address" name="address" rows="2"><?php echo esc_textarea($current_settings['address']); ?></textarea>
            </div>
            
            <div class="frf-form-row">
                <div class="frf-form-group">
                    <label for="city"><?php _e('Città', 'fatture-rf'); ?></label>
                    <input type="text" id="city" name="city" 
                           value="<?php echo esc_attr($current_settings['city']); ?>">
                </div>
                
                <div class="frf-form-group">
                    <label for="province"><?php _e('Provincia', 'fatture-rf'); ?></label>
                    <input type="text" id="province" name="province" 
                           value="<?php echo esc_attr($current_settings['province']); ?>" maxlength="2">
                </div>
                
                <div class="frf-form-group">
                    <label for="postal_code"><?php _e('CAP', 'fatture-rf'); ?></label>
                    <input type="text" id="postal_code" name="postal_code" 
                           value="<?php echo esc_attr($current_settings['postal_code']); ?>">
                </div>
            </div>
            
            <div class="frf-form-row">
                <div class="frf-form-group">
                    <label for="email"><?php _e('Email', 'fatture-rf'); ?></label>
                    <input type="email" id="email" name="email" 
                           value="<?php echo esc_attr($current_settings['email']); ?>">
                </div>
                
                <div class="frf-form-group">
                    <label for="phone"><?php _e('Telefono', 'fatture-rf'); ?></label>
                    <input type="text" id="phone" name="phone" 
                           value="<?php echo esc_attr($current_settings['phone']); ?>">
                </div>
            </div>
            
            <div class="frf-form-row">
                <div class="frf-form-group">
                    <label for="pec_email"><?php _e('PEC', 'fatture-rf'); ?></label>
                    <input type="email" id="pec_email" name="pec_email" 
                           value="<?php echo esc_attr($current_settings['pec_email']); ?>">
                    <small class="frf-form-help"><?php _e('Email PEC per fatturazione elettronica', 'fatture-rf'); ?></small>
                </div>
                
                <div class="frf-form-group">
                    <label for="sdi_code"><?php _e('Codice SDI', 'fatture-rf'); ?></label>
                    <input type="text" id="sdi_code" name="sdi_code" 
                           value="<?php echo esc_attr($current_settings['sdi_code']); ?>" maxlength="7">
                    <small class="frf-form-help"><?php _e('7 caratteri', 'fatture-rf'); ?></small>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if ($tab === 'invoices'): ?>
        <!-- Invoices Tab -->
        <div class="frf-card">
            <div class="frf-card-header">
                <h2 class="frf-card-title"><?php _e('Impostazioni Fatture', 'fatture-rf'); ?></h2>
            </div>
            
            <div class="frf-form-row">
                <div class="frf-form-group">
                    <label for="invoice_prefix"><?php _e('Prefisso Fatture', 'fatture-rf'); ?></label>
                    <input type="text" id="invoice_prefix" name="invoice_prefix" 
                           value="<?php echo esc_attr($current_settings['invoice_prefix']); ?>">
                    <small class="frf-form-help"><?php _e('Es: FATT (risultato: FATT/2026/0001)', 'fatture-rf'); ?></small>
                </div>
            </div>
            
            <div class="frf-form-row">
                <div class="frf-form-group">
                    <label for="default_payment_terms"><?php _e('Termini di Pagamento Predefiniti', 'fatture-rf'); ?></label>
                    <input type="text" id="default_payment_terms" name="default_payment_terms" 
                           value="<?php echo esc_attr($current_settings['default_payment_terms']); ?>">
                    <small class="frf-form-help"><?php _e('Es: 30 giorni data fattura', 'fatture-rf'); ?></small>
                </div>
                
                <div class="frf-form-group">
                    <label for="default_payment_method"><?php _e('Metodo di Pagamento Predefinito', 'fatture-rf'); ?></label>
                    <select id="default_payment_method" name="default_payment_method">
                        <option value="Bonifico bancario" <?php selected($current_settings['default_payment_method'], 'Bonifico bancario'); ?>>
                            <?php _e('Bonifico bancario', 'fatture-rf'); ?>
                        </option>
                        <option value="Contanti" <?php selected($current_settings['default_payment_method'], 'Contanti'); ?>>
                            <?php _e('Contanti', 'fatture-rf'); ?>
                        </option>
                        <option value="Assegno" <?php selected($current_settings['default_payment_method'], 'Assegno'); ?>>
                            <?php _e('Assegno', 'fatture-rf'); ?>
                        </option>
                        <option value="RID" <?php selected($current_settings['default_payment_method'], 'RID'); ?>>
                            <?php _e('RID', 'fatture-rf'); ?>
                        </option>
                        <option value="PayPal" <?php selected($current_settings['default_payment_method'], 'PayPal'); ?>>
                            <?php _e('PayPal', 'fatture-rf'); ?>
                        </option>
                    </select>
                </div>
            </div>
            
            <div class="frf-form-group">
                <label for="default_notes"><?php _e('Note Predefinite', 'fatture-rf'); ?></label>
                <textarea id="default_notes" name="default_notes" rows="3"><?php echo esc_textarea($current_settings['default_notes']); ?></textarea>
                <small class="frf-form-help"><?php _e('Queste note appariranno su tutte le nuove fatture', 'fatture-rf'); ?></small>
            </div>
            
            <hr style="margin: 30px 0;">
            
            <h3><?php _e('Ritenuta d\'Acconto', 'fatture-rf'); ?></h3>
            
            <div class="frf-form-group">
                <label>
                    <input type="checkbox" id="apply_withholding_tax" name="apply_withholding_tax" 
                           value="1" <?php checked($current_settings['apply_withholding_tax']); ?>>
                    <?php _e('Applica ritenuta d\'acconto alle fatture', 'fatture-rf'); ?>
                </label>
            </div>
            
            <div class="frf-form-group">
                <label for="withholding_tax_rate"><?php _e('Percentuale Ritenuta (%)', 'fatture-rf'); ?></label>
                <input type="number" id="withholding_tax_rate" name="withholding_tax_rate" step="0.01" 
                       value="<?php echo esc_attr($current_settings['withholding_tax_rate']); ?>">
                <small class="frf-form-help"><?php _e('Di solito 20%', 'fatture-rf'); ?></small>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if ($tab === 'regime'): ?>
        <!-- Regime Forfettario Tab -->
        <div class="frf-card">
            <div class="frf-card-header">
                <h2 class="frf-card-title"><?php _e('Configurazione Regime Forfettario', 'fatture-rf'); ?></h2>
            </div>
            
            <div class="frf-form-group">
                <label>
                    <input type="checkbox" id="regime_forfettario" name="regime_forfettario" 
                           value="1" <?php checked($current_settings['regime_forfettario']); ?>>
                    <?php _e('Attiva regime forfettario', 'fatture-rf'); ?>
                </label>
                <small class="frf-form-help">
                    <?php _e('Legge n. 190/2014 - Regime forfetario per partite IVA', 'fatture-rf'); ?>
                </small>
            </div>
            
            <div class="frf-regime-settings">
                <div class="frf-form-group">
                    <label for="flat_tax_rate"><?php _e('Aliquota Imposta Sostitutiva', 'fatture-rf'); ?></label>
                    <select id="flat_tax_rate" name="flat_tax_rate">
                        <option value="5" <?php selected($current_settings['flat_tax_rate'], 5); ?>>
                            5% - <?php _e('Start-up (primi 5 anni)', 'fatture-rf'); ?>
                        </option>
                        <option value="15" <?php selected($current_settings['flat_tax_rate'], 15); ?>>
                            15% - <?php _e('Ordinaria', 'fatture-rf'); ?>
                        </option>
                    </select>
                    <small class="frf-form-help">
                        <?php _e('5% per i primi 5 anni di attività, 15% successivamente', 'fatture-rf'); ?>
                    </small>
                </div>
                
                <div class="frf-form-group">
                    <label>
                        <input type="checkbox" id="exempt_vat" name="exempt_vat" 
                               value="1" <?php checked($current_settings['exempt_vat']); ?>>
                        <?php _e('Esenzione IVA', 'fatture-rf'); ?>
                    </label>
                    <small class="frf-form-help">
                        <?php _e('Il regime forfettario prevede l\'esenzione IVA (art. 1, c. 58)', 'fatture-rf'); ?>
                    </small>
                </div>
                
                <div class="notice notice-info" style="margin-top: 20px;">
                    <p>
                        <strong><?php _e('Info sul Regime Forfettario:', 'fatture-rf'); ?></strong><br>
                        - <?php _e('Esenzione IVA (non addebiti IVA, non la detrai)', 'fatture-rf'); ?><br>
                        - <?php _e('Tassazione sostitutiva al 5% o 15% sul reddito', 'fatture-rf'); ?><br>
                        - <?php _e('Limite di fatturato: € 85.000/anno', 'fatture-rf'); ?><br>
                        - <?php _e('Non obbligatorio certificazione UniEmens', 'fatture-rf'); ?>
                    </p>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if ($tab === 'email'): ?>
        <!-- Email Tab -->
        <div class="frf-card">
            <div class="frf-card-header">
                <h2 class="frf-card-title"><?php _e('Notifiche Email', 'fatture-rf'); ?></h2>
            </div>
            
            <div class="frf-form-group">
                <label>
                    <input type="checkbox" id="enable_email_notifications" name="enable_email_notifications" 
                           value="1" <?php checked($current_settings['enable_email_notifications']); ?>>
                    <?php _e('Abilita notifiche email', 'fatture-rf'); ?>
                </label>
            </div>
            
            <div class="frf-form-group">
                <label for="notification_email"><?php _e('Email per Notifiche', 'fatture-rf'); ?></label>
                <input type="email" id="notification_email" name="notification_email" 
                       value="<?php echo esc_attr($current_settings['notification_email']); ?>">
                <small class="frf-form-help"><?php _e('Email dove ricevere le notifiche del sistema', 'fatture-rf'); ?></small>
            </div>
            
            <hr style="margin: 30px 0;">
            
            <h3><?php _e('Email Invio Fatture', 'fatture-rf'); ?></h3>
            
            <div class="frf-form-group">
                <label>
                    <input type="checkbox" id="send_invoice_email" name="send_invoice_email" 
                           value="1" <?php checked($current_settings['send_invoice_email']); ?>>
                    <?php _e('Abilita invio fatture via email ai clienti', 'fatture-rf'); ?>
                </label>
            </div>
            
            <div class="frf-form-group">
                <label for="invoice_email_subject"><?php _e('Oggetto Email', 'fatture-rf'); ?></label>
                <input type="text" id="invoice_email_subject" name="invoice_email_subject" 
                       value="<?php echo esc_attr($current_settings['invoice_email_subject']); ?>">
                <small class="frf-form-help"><?php _e('Usa {invoice_number} per inserire il numero fattura', 'fatture-rf'); ?></small>
            </div>
            
            <div class="frf-form-group">
                <label for="invoice_email_body"><?php _e('Corpo Email', 'fatture-rf'); ?></label>
                <textarea id="invoice_email_body" name="invoice_email_body" rows="5"><?php echo esc_textarea($current_settings['invoice_email_body']); ?></textarea>
                <small class="frf-form-help">
                    <?php _e('Variabili disponibili: {invoice_number}, {invoice_date}, {client_name}, {total}', 'fatture-rf'); ?>
                </small>
            </div>
        </div>
        <?php endif; ?>
        
        <p class="submit">
            <button type="submit" name="frf_save_settings" class="button button-primary button-large">
                <?php _e('Salva Impostazioni', 'fatture-rf'); ?>
            </button>
        </p>
    </form>
</div>