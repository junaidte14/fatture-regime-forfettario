/**
 * Fatture Regime Forfettario - Admin JavaScript
 */

(function($) {
    'use strict';
    
    const FRF_Admin = {
        
        /**
         * Initialize
         */
        init: function() {
            this.invoiceForm();
            this.deleteConfirmation();
            this.clientTypeChange();
            this.regimeForfettarioToggle();
        },
        
        /**
         * Invoice form handling
         */
        invoiceForm: function() {
            // Add invoice item
            $(document).on('click', '.frf-add-item', function(e) {
                e.preventDefault();
                
                const template = $('#frf-item-template').html();
                const index = $('.frf-invoice-items tbody tr').length;
                const row = template.replace(/INDEX/g, index);
                
                $('.frf-invoice-items tbody').append(row);
            });
            
            // Remove invoice item
            $(document).on('click', '.frf-remove-item', function(e) {
                e.preventDefault();
                
                if ($('.frf-invoice-items tbody tr').length > 1) {
                    $(this).closest('tr').remove();
                    FRF_Admin.calculateInvoiceTotals();
                }
            });
            
            // Calculate item total on input
            $(document).on('input', '.item-quantity, .item-unit-price', function() {
                const row = $(this).closest('tr');
                const quantity = parseFloat(row.find('.item-quantity').val()) || 0;
                const unitPrice = parseFloat(row.find('.item-unit-price').val()) || 0;
                const total = quantity * unitPrice;
                
                row.find('.item-total').val(total.toFixed(2));
                FRF_Admin.calculateInvoiceTotals();
            });
            
            // Calculate totals on tax input
            $(document).on('input', '#tax_rate, #withholding_tax', function() {
                FRF_Admin.calculateInvoiceTotals();
            });
        },
        
        /**
         * Calculate invoice totals
         */
        calculateInvoiceTotals: function() {
            let subtotal = 0;
            
            // Sum all items
            $('.item-total').each(function() {
                subtotal += parseFloat($(this).val()) || 0;
            });
            
            // Get tax rate
            const taxRate = parseFloat($('#tax_rate').val()) || 0;
            const taxAmount = (subtotal * taxRate) / 100;
            const total = subtotal + taxAmount;
            
            // Get withholding tax
            const withholdingTaxRate = parseFloat($('#withholding_tax').val()) || 0;
            const withholdingAmount = (subtotal * withholdingTaxRate) / 100;
            const netToPay = total - withholdingAmount;
            
            // Update fields
            $('#subtotal').val(subtotal.toFixed(2));
            $('#tax_amount').val(taxAmount.toFixed(2));
            $('#total').val(total.toFixed(2));
            $('#withholding_amount').val(withholdingAmount.toFixed(2));
            $('#net_to_pay').val(netToPay.toFixed(2));
            
            // Update display
            $('.display-subtotal').text(this.formatCurrency(subtotal));
            $('.display-tax').text(this.formatCurrency(taxAmount));
            $('.display-total').text(this.formatCurrency(total));
            $('.display-withholding').text(this.formatCurrency(withholdingAmount));
            $('.display-net').text(this.formatCurrency(netToPay));
        },
        
        /**
         * Format currency
         */
        formatCurrency: function(amount) {
            return new Intl.NumberFormat('it-IT', {
                style: 'currency',
                currency: 'EUR'
            }).format(amount);
        },
        
        /**
         * Delete confirmation
         */
        deleteConfirmation: function() {
            $('.frf-delete-link').on('click', function(e) {
                if (!confirm(frfAdmin.confirmDelete)) {
                    e.preventDefault();
                }
            });
        },
        
        /**
         * Client type change handling
         */
        clientTypeChange: function() {
            $('#country').on('change', function() {
                const country = $(this).val();
                const isIT = country === 'IT';
                const isEU = ['AT', 'BE', 'BG', 'CY', 'CZ', 'DE', 'DK', 'EE', 'ES', 'FI', 
                             'FR', 'GR', 'HR', 'HU', 'IE', 'LT', 'LU', 'LV', 'MT', 'NL', 
                             'PL', 'PT', 'RO', 'SE', 'SI', 'SK'].includes(country);
                
                // Show/hide fields based on client type
                if (isIT) {
                    $('.frf-field-sdi, .frf-field-pec').show();
                    $('.frf-field-vat, .frf-field-tax-code').show();
                } else if (isEU) {
                    $('.frf-field-sdi, .frf-field-pec').hide();
                    $('.frf-field-vat').show();
                    $('.frf-field-tax-code').hide();
                } else {
                    $('.frf-field-sdi, .frf-field-pec, .frf-field-tax-code').hide();
                    $('.frf-field-vat').show();
                }
            });
        },
        
        /**
         * Regime Forfettario toggle
         */
        regimeForfettarioToggle: function() {
            $('#regime_forfettario').on('change', function() {
                if ($(this).is(':checked')) {
                    $('#exempt_vat').prop('checked', true).closest('.frf-form-group').show();
                    $('.frf-regime-settings').show();
                } else {
                    $('.frf-regime-settings').hide();
                }
            });
            
            // Trigger on load
            $('#regime_forfettario').trigger('change');
        },
        
        /**
         * Client search
         */
        clientSearch: function() {
            let timeout;
            
            $('#client-search').on('input', function() {
                clearTimeout(timeout);
                const search = $(this).val();
                
                timeout = setTimeout(function() {
                    $.ajax({
                        url: frfAdmin.ajaxUrl,
                        method: 'POST',
                        data: {
                            action: 'frf_search_clients',
                            nonce: frfAdmin.nonce,
                            search: search
                        },
                        success: function(response) {
                            if (response.success) {
                                FRF_Admin.updateClientList(response.data);
                            }
                        }
                    });
                }, 300);
            });
        },
        
        /**
         * Update client list
         */
        updateClientList: function(clients) {
            const select = $('#client_id');
            select.empty();
            
            select.append('<option value="">Seleziona cliente</option>');
            
            clients.forEach(function(client) {
                select.append(
                    $('<option></option>')
                        .val(client.id)
                        .text(client.business_name)
                );
            });
        },
        
        /**
         * Invoice status change
         */
        changeInvoiceStatus: function() {
            $('.frf-change-status').on('click', function(e) {
                e.preventDefault();
                
                const invoiceId = $(this).data('invoice-id');
                const newStatus = $(this).data('status');
                
                $.ajax({
                    url: frfAdmin.ajaxUrl,
                    method: 'POST',
                    data: {
                        action: 'frf_change_invoice_status',
                        nonce: frfAdmin.nonce,
                        invoice_id: invoiceId,
                        status: newStatus
                    },
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert(response.data.message || 'Errore durante il cambio di stato');
                        }
                    }
                });
            });
        },
        
        /**
         * Export invoice to PDF
         */
        exportInvoicePDF: function() {
            $('.frf-export-pdf').on('click', function(e) {
                e.preventDefault();
                
                const invoiceId = $(this).data('invoice-id');
                window.location.href = frfAdmin.ajaxUrl + 
                    '?action=frf_export_invoice_pdf&invoice_id=' + invoiceId + 
                    '&nonce=' + frfAdmin.nonce;
            });
        }
    };
    
    // Initialize on document ready
    $(document).ready(function() {
        FRF_Admin.init();
    });
    
})(jQuery);