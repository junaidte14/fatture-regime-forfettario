<?php
/**
 * Dashboard View Template
 * Path: admin/views/dashboard.php
 */

if (!defined('ABSPATH')) {
    exit;
}

$settings = FRF_Settings::get_instance();
$business_info = $settings->get_business_info();
?>

<div class="wrap">
    <h1><?php _e('Dashboard Fatture RF', 'fatture-rf'); ?></h1>
    
    <?php settings_errors('frf_messages'); ?>
    
    <!-- Quick Actions -->
    <div style="margin: 20px 0;">
        <a href="<?php echo admin_url('admin.php?page=fatture-rf-invoices&view=add'); ?>" class="button button-primary button-large">
            <span class="dashicons dashicons-plus-alt" style="margin-top: 3px;"></span>
            <?php _e('New Invoice', 'fatture-rf'); ?>
        </a>
        <a href="<?php echo admin_url('admin.php?page=fatture-rf-clients&view=add'); ?>" class="button button-secondary button-large">
            <span class="dashicons dashicons-businessman" style="margin-top: 3px;"></span>
            <?php _e('New Client', 'fatture-rf'); ?>
        </a>
    </div>
    
    <!-- Statistics Cards -->
    <div class="frf-dashboard-stats">
        <!-- Total Invoices -->
        <div class="frf-stat-card">
            <h3><?php _e('Total Invoices', 'fatture-rf'); ?></h3>
            <p class="frf-stat-value"><?php echo number_format($stats->total_invoices, 0, ',', '.'); ?></p>
            <p class="frf-stat-label">
                <?php echo number_format($stats->paid_invoices, 0, ',', '.'); ?> <?php _e('paid', 'fatture-rf'); ?> | 
                <?php echo number_format($stats->sent_invoices, 0, ',', '.'); ?> <?php _e('sent', 'fatture-rf'); ?>
            </p>
        </div>
        
        <!-- Total Amount -->
        <div class="frf-stat-card">
            <h3><?php _e('Total Revenue', 'fatture-rf'); ?></h3>
            <p class="frf-stat-value">€ <?php echo number_format($stats->total_amount, 2, ',', '.'); ?></p>
            <p class="frf-stat-label"><?php _e('Total amount issued', 'fatture-rf'); ?></p>
        </div>
        
        <!-- Paid Amount -->
        <div class="frf-stat-card">
            <h3><?php _e('Received', 'fatture-rf'); ?></h3>
            <p class="frf-stat-value" style="color: #0a7d3e;">
                € <?php echo number_format($stats->paid_amount, 2, ',', '.'); ?>
            </p>
            <p class="frf-stat-label">
                <?php 
                $percentage = $stats->total_amount > 0 ? ($stats->paid_amount / $stats->total_amount) * 100 : 0;
                echo number_format($percentage, 0) . '%'; 
                ?> <?php _e('of total', 'fatture-rf'); ?>
            </p>
        </div>
        
        <!-- Outstanding Amount -->
        <div class="frf-stat-card">
            <h3><?php _e('Outstanding', 'fatture-rf'); ?></h3>
            <p class="frf-stat-value" style="color: #d63638;">
                € <?php echo number_format($stats->outstanding_amount, 2, ',', '.'); ?>
            </p>
            <p class="frf-stat-label"><?php _e('Unpaid invoices', 'fatture-rf'); ?></p>
        </div>
        
        <!-- Total Clients -->
        <div class="frf-stat-card">
            <h3><?php _e('Clients', 'fatture-rf'); ?></h3>
            <p class="frf-stat-value"><?php echo number_format($total_clients, 0, ',', '.'); ?></p>
            <p class="frf-stat-label">
                <a href="<?php echo admin_url('admin.php?page=fatture-rf-clients'); ?>">
                    <?php _e('View all', 'fatture-rf'); ?>
                </a>
            </p>
        </div>
        
        <!-- Draft Invoices -->
        <div class="frf-stat-card">
            <h3><?php _e('Draft', 'fatture-rf'); ?></h3>
            <p class="frf-stat-value"><?php echo number_format($stats->draft_invoices, 0, ',', '.'); ?></p>
            <p class="frf-stat-label">
                <a href="<?php echo admin_url('admin.php?page=fatture-rf-invoices&status=draft'); ?>">
                    <?php _e('View drafts', 'fatture-rf'); ?>
                </a>
            </p>
        </div>
    </div>
    
    <!-- Business Info Check -->
    <?php if (empty($business_info['business_name']) || empty($business_info['vat_number'])): ?>
    <div class="notice notice-warning" style="margin: 20px 0;">
        <p>
            <strong><?php _e('Attention:', 'fatture-rf'); ?></strong>
            <?php _e('Configure your business data in settings for complete invoices.', 'fatture-rf'); ?>
            <a href="<?php echo admin_url('admin.php?page=fatture-rf-settings'); ?>">
                <?php _e('Go to settings', 'fatture-rf'); ?>
            </a>
        </p>
    </div>
    <?php endif; ?>
    
    <!-- Recent Invoices -->
    <div class="frf-card" style="margin-top: 30px;">
        <div class="frf-card-header">
            <h2 class="frf-card-title">
                <?php _e('Recent Invoices', 'fatture-rf'); ?>
                <a href="<?php echo admin_url('admin.php?page=fatture-rf-invoices'); ?>" 
                   class="button button-small" style="float: right;">
                    <?php _e('View All', 'fatture-rf'); ?>
                </a>
            </h2>
        </div>
        
        <?php if (!empty($recent_invoices)): ?>
        <table class="frf-table">
            <thead>
                <tr>
                    <th><?php _e('Number', 'fatture-rf'); ?></th>
                    <th><?php _e('Date', 'fatture-rf'); ?></th>
                    <th><?php _e('Client', 'fatture-rf'); ?></th>
                    <th><?php _e('Amount', 'fatture-rf'); ?></th>
                    <th><?php _e('Status', 'fatture-rf'); ?></th>
                    <th><?php _e('Actions', 'fatture-rf'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $client_model = new FRF_Client();
                foreach ($recent_invoices as $invoice): 
                    $client = $client_model->get($invoice->client_id);
                ?>
                <tr>
                    <td><strong><?php echo esc_html($invoice->invoice_number); ?></strong></td>
                    <td><?php echo date_i18n(get_option('date_format'), strtotime($invoice->invoice_date)); ?></td>
                    <td>
                        <?php if ($client): ?>
                            <a href="<?php echo admin_url('admin.php?page=fatture-rf-clients&view=view&id=' . $client->id); ?>">
                                <?php echo esc_html($client->business_name); ?>
                            </a>
                        <?php endif; ?>
                    </td>
                    <td><strong>€ <?php echo number_format($invoice->total, 2, ',', '.'); ?></strong></td>
                    <td><?php echo FRF_Admin_Invoices::get_status_badge($invoice->status); ?></td>
                    <td class="frf-actions">
                        <a href="<?php echo admin_url('admin.php?page=fatture-rf-invoices&view=view&id=' . $invoice->id); ?>" 
                           class="frf-action-link">
                            <?php _e('View', 'fatture-rf'); ?>
                        </a>
                        <a href="<?php echo admin_url('admin.php?page=fatture-rf-invoices&view=edit&id=' . $invoice->id); ?>" 
                           class="frf-action-link">
                            <?php _e('Edit', 'fatture-rf'); ?>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <p style="padding: 20px; text-align: center; color: #666;">
            <?php _e('No invoices found.', 'fatture-rf'); ?>
            <a href="<?php echo admin_url('admin.php?page=fatture-rf-invoices&view=add'); ?>">
                <?php _e('Create your first invoice', 'fatture-rf'); ?>
            </a>
        </p>
        <?php endif; ?>
    </div>
    
    <!-- Quick Stats by Month -->
    <div class="frf-card" style="margin-top: 20px;">
        <div class="frf-card-header">
            <h2 class="frf-card-title"><?php _e('Monthly Summary', 'fatture-rf'); ?></h2>
        </div>
        
        <?php
        global $wpdb;
        $table = FRF_Database::get_table_name('invoices');
        $monthly_stats = $wpdb->get_results("
            SELECT 
                DATE_FORMAT(invoice_date, '%Y-%m') as month,
                COUNT(*) as count,
                SUM(total) as total,
                SUM(CASE WHEN status = 'paid' THEN total ELSE 0 END) as paid
            FROM {$table}
            WHERE invoice_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
            GROUP BY month
            ORDER BY month DESC
        ");
        ?>
        
        <?php if (!empty($monthly_stats)): ?>
        <table class="frf-table">
            <thead>
                <tr>
                    <th><?php _e('Month', 'fatture-rf'); ?></th>
                    <th><?php _e('#Invoices', 'fatture-rf'); ?></th>
                    <th><?php _e('Total Issued', 'fatture-rf'); ?></th>
                    <th><?php _e('Received', 'fatture-rf'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($monthly_stats as $stat): ?>
                <tr>
                    <td>
                        <strong>
                            <?php 
                            $date = DateTime::createFromFormat('Y-m', $stat->month);
                            echo $date->format('F Y');
                            ?>
                        </strong>
                    </td>
                    <td><?php echo number_format($stat->count, 0, ',', '.'); ?></td>
                    <td>€ <?php echo number_format($stat->total, 2, ',', '.'); ?></td>
                    <td style="color: #0a7d3e;">
                        <strong>€ <?php echo number_format($stat->paid, 2, ',', '.'); ?></strong>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
    
    <!-- Info Box -->
    <div class="frf-card" style="margin-top: 20px; background: #f0f6fc; border-color: #2271b1;">
        <h3 style="margin-top: 0;">💡 <?php _e('Tips', 'fatture-rf'); ?></h3>
        <ul style="margin: 10px 0;">
            <li><?php _e('Configure your business data in settings for complete invoices', 'fatture-rf'); ?></li>
            <li><?php _e('Verify that all clients have correct data for electronic invoicing', 'fatture-rf'); ?></li>
            <li><?php _e('Le fatture in regime forfettario sono esenti IVA (art. 1, c. 58, Legge n. 190/2014)', 'fatture-rf'); ?></li>
            <li><?php _e('XML submission to SDI will be available in the next version', 'fatture-rf'); ?></li>
        </ul>
    </div>
</div>

<style>
/* Additional inline styles for dashboard */
.frf-stat-card {
    transition: transform 0.2s, box-shadow 0.2s;
}

.frf-stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.frf-stat-value {
    animation: fadeIn 0.5s ease-in;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>