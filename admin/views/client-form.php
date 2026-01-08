<?php
/**
 * Client Form View Template (Add/Edit)
 * Path: admin/views/client-form.php
 */

if (!defined('ABSPATH')) {
    exit;
}

$is_edit = isset($client) && !empty($client);
$page_title = $is_edit ? __('Edit Client', 'fatture-rf') : __('New Client', 'fatture-rf');
?>

<div class="wrap">
    <h1><?php echo esc_html($page_title); ?></h1>
    
    <?php if (isset($error)): ?>
    <div class="notice notice-error">
        <p><?php echo esc_html($error); ?></p>
    </div>
    <?php endif; ?>
    
    <form method="post" action="" class="frf-form">
        <?php wp_nonce_field('frf_save_client'); ?>
        
        <div class="frf-card">
            <div class="frf-card-header">
                <h2 class="frf-card-title"><?php _e('Personal Information', 'fatture-rf'); ?></h2>
            </div>
            
            <div class="frf-form-row">
                <div class="frf-form-group">
                    <label for="business_name">
                        <?php _e('Business Name', 'fatture-rf'); ?> <span style="color: red;">*</span>
                    </label>
                    <input type="text" id="business_name" name="business_name" 
                           value="<?php echo esc_attr($client->business_name ?? ''); ?>" required>
                </div>
                
                <div class="frf-form-group">
                    <label for="country"><?php _e('Country', 'fatture-rf'); ?></label>
                    <select id="country" name="country">
                        <option value="IT" <?php selected($client->country ?? 'IT', 'IT'); ?>>Italia</option>
                        <option value="AT" <?php selected($client->country ?? '', 'AT'); ?>>Austria</option>
                        <option value="BE" <?php selected($client->country ?? '', 'BE'); ?>>Belgio</option>
                        <option value="BG" <?php selected($client->country ?? '', 'BG'); ?>>Bulgaria</option>
                        <option value="CY" <?php selected($client->country ?? '', 'CY'); ?>>Cipro</option>
                        <option value="CZ" <?php selected($client->country ?? '', 'CZ'); ?>>Repubblica Ceca</option>
                        <option value="DE" <?php selected($client->country ?? '', 'DE'); ?>>Germania</option>
                        <option value="DK" <?php selected($client->country ?? '', 'DK'); ?>>Danimarca</option>
                        <option value="EE" <?php selected($client->country ?? '', 'EE'); ?>>Estonia</option>
                        <option value="ES" <?php selected($client->country ?? '', 'ES'); ?>>Spagna</option>
                        <option value="FI" <?php selected($client->country ?? '', 'FI'); ?>>Finlandia</option>
                        <option value="FR" <?php selected($client->country ?? '', 'FR'); ?>>Francia</option>
                        <option value="GR" <?php selected($client->country ?? '', 'GR'); ?>>Grecia</option>
                        <option value="HR" <?php selected($client->country ?? '', 'HR'); ?>>Croazia</option>
                        <option value="HU" <?php selected($client->country ?? '', 'HU'); ?>>Ungheria</option>
                        <option value="IE" <?php selected($client->country ?? '', 'IE'); ?>>Irlanda</option>
                        <option value="LT" <?php selected($client->country ?? '', 'LT'); ?>>Lituania</option>
                        <option value="LU" <?php selected($client->country ?? '', 'LU'); ?>>Lussemburgo</option>
                        <option value="LV" <?php selected($client->country ?? '', 'LV'); ?>>Lettonia</option>
                        <option value="MT" <?php selected($client->country ?? '', 'MT'); ?>>Malta</option>
                        <option value="NL" <?php selected($client->country ?? '', 'NL'); ?>>Paesi Bassi</option>
                        <option value="PL" <?php selected($client->country ?? '', 'PL'); ?>>Polonia</option>
                        <option value="PT" <?php selected($client->country ?? '', 'PT'); ?>>Portogallo</option>
                        <option value="RO" <?php selected($client->country ?? '', 'RO'); ?>>Romania</option>
                        <option value="SE" <?php selected($client->country ?? '', 'SE'); ?>>Svezia</option>
                        <option value="SI" <?php selected($client->country ?? '', 'SI'); ?>>Slovenia</option>
                        <option value="SK" <?php selected($client->country ?? '', 'SK'); ?>>Slovacchia</option>
                        <option value="US" <?php selected($client->country ?? '', 'US'); ?>>Stati Uniti</option>
                        <option value="GB" <?php selected($client->country ?? '', 'GB'); ?>>Regno Unito</option>
                        <option value="CH" <?php selected($client->country ?? '', 'CH'); ?>>Svizzera</option>
                    </select>
                </div>
            </div>
            
            <div class="frf-form-row">
                <div class="frf-form-group frf-field-vat">
                    <label for="vat_number"><?php _e('Partita IVA', 'fatture-rf'); ?></label>
                    <input type="text" id="vat_number" name="vat_number" 
                           value="<?php echo esc_attr($client->vat_number ?? ''); ?>">
                    <small class="frf-form-help"><?php _e('11 cifre per clienti italiani', 'fatture-rf'); ?></small>
                </div>
                
                <div class="frf-form-group frf-field-tax-code">
                    <label for="tax_code"><?php _e('Codice Fiscale', 'fatture-rf'); ?></label>
                    <input type="text" id="tax_code" name="tax_code" 
                           value="<?php echo esc_attr($client->tax_code ?? ''); ?>" maxlength="16">
                    <small class="frf-form-help"><?php _e('16 caratteri alfanumerici', 'fatture-rf'); ?></small>
                </div>
            </div>
        </div>
        
        <div class="frf-card">
            <div class="frf-card-header">
                <h2 class="frf-card-title"><?php _e('Address', 'fatture-rf'); ?></h2>
            </div>
            
            <div class="frf-form-group">
                <label for="address"><?php _e('Street/Square', 'fatture-rf'); ?></label>
                <textarea id="address" name="address" rows="2"><?php echo esc_textarea($client->address ?? ''); ?></textarea>
            </div>
            
            <div class="frf-form-row">
                <div class="frf-form-group">
                    <label for="city"><?php _e('City', 'fatture-rf'); ?></label>
                    <input type="text" id="city" name="city" 
                           value="<?php echo esc_attr($client->city ?? ''); ?>">
                </div>
                
                <div class="frf-form-group">
                    <label for="province"><?php _e('Province', 'fatture-rf'); ?></label>
                    <input type="text" id="province" name="province" 
                           value="<?php echo esc_attr($client->province ?? ''); ?>" maxlength="2">
                </div>
                
                <div class="frf-form-group">
                    <label for="postal_code"><?php _e('Postal Code (CAP)', 'fatture-rf'); ?></label>
                    <input type="text" id="postal_code" name="postal_code" 
                           value="<?php echo esc_attr($client->postal_code ?? ''); ?>">
                </div>
            </div>
        </div>
        
        <div class="frf-card">
            <div class="frf-card-header">
                <h2 class="frf-card-title"><?php _e('Contacts', 'fatture-rf'); ?></h2>
            </div>
            
            <div class="frf-form-row">
                <div class="frf-form-group">
                    <label for="email"><?php _e('Email', 'fatture-rf'); ?></label>
                    <input type="email" id="email" name="email" 
                           value="<?php echo esc_attr($client->email ?? ''); ?>">
                </div>
                
                <div class="frf-form-group">
                    <label for="phone"><?php _e('Telefono', 'fatture-rf'); ?></label>
                    <input type="text" id="phone" name="phone" 
                           value="<?php echo esc_attr($client->phone ?? ''); ?>">
                </div>
            </div>
        </div>
        
        <div class="frf-card frf-field-pec">
            <div class="frf-card-header">
                <h2 class="frf-card-title"><?php _e('Electronic Invoicing', 'fatture-rf'); ?></h2>
            </div>
            
            <div class="frf-form-row">
                <div class="frf-form-group frf-field-sdi">
                    <label for="sdi_code"><?php _e('SDI Recipient Code', 'fatture-rf'); ?></label>
                    <input type="text" id="sdi_code" name="sdi_code" 
                           value="<?php echo esc_attr($client->sdi_code ?? ''); ?>" maxlength="7">
                    <small class="frf-form-help"><?php _e('7 characters (e.g., 0000000 for individuals)', 'fatture-rf'); ?></small>
                </div>
                
                <div class="frf-form-group frf-field-pec">
                    <label for="pec_email"><?php _e('PEC', 'fatture-rf'); ?></label>
                    <input type="email" id="pec_email" name="pec_email" 
                           value="<?php echo esc_attr($client->pec_email ?? ''); ?>">
                    <small class="frf-form-help"><?php _e('PEC email for electronic invoicing', 'fatture-rf'); ?></small>
                </div>
            </div>
            
            <div class="notice notice-info" style="margin-top: 15px;">
                <p>
                    <strong><?php _e('Info:', 'fatture-rf'); ?></strong>
                    <?php _e('Italian clients require at least SDI Code or PEC for electronic invoicing', 'fatture-rf'); ?>
                </p>
            </div>
        </div>
        
        <p class="submit">
            <button type="submit" name="frf_save_client" class="button button-primary button-large">
                <?php echo $is_edit ? __('Update Client', 'fatture-rf') : __('Save Client', 'fatture-rf'); ?>
            </button>
            <a href="<?php echo admin_url('admin.php?page=fatture-rf-clients'); ?>" class="button button-large">
                <?php _e('Cancel', 'fatture-rf'); ?>
            </a>
        </p>
    </form>
</div>