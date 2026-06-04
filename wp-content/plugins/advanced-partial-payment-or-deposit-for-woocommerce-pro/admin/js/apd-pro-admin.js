/**
 * APD Deposit Pro - Admin JavaScript
 * Handles: Plan builder, dynamic installment rows, AJAX CRUD
 */
(function ($) {
    'use strict';

    var APD_Plans = {
        rowIndex: 0,

        init: function () {
            this.bindListActions();
            this.bindBuilderActions();
            this.bindInstallmentActions();
            this.bindPriceSuffix();
            this.initExistingRows();
        },

        // ================================================
        //  LIST: New / Edit / Delete
        // ================================================
        bindListActions: function () {
            var self = this;

            // Add New Plan
            $(document).on('click', '#apd-add-new-plan', function () {
                self.resetBuilder();
                self.showBuilder();
                // Add 1 default row
                self.addInstallmentRow({ amount: '', due_type: 'immediately' });
            });

            // Edit Plan
            $(document).on('click', '.apd-edit-plan', function () {
                var planId = $(this).data('plan-id');
                self.loadPlanForEdit(planId);
            });

            // Delete Plan
            $(document).on('click', '.apd-delete-plan', function () {
                if (!confirm('Are you sure you want to delete this plan?')) return;

                var $btn   = $(this);
                var planId = $btn.data('plan-id');

                $.post(apd_admin.ajax_url, {
                    action:  'apd_delete_plan',
                    nonce:   apd_admin.nonce,
                    plan_id: planId,
                }).done(function (r) {
                    if (r.success) {
                        $btn.closest('tr').fadeOut(300, function () { $(this).remove(); });
                        self.showToast(r.data, 'success');
                    } else {
                        self.showToast(r.data || 'Error', 'error');
                    }
                });
            });
        },

        // ================================================
        //  BUILDER: Save / Back
        // ================================================
        bindBuilderActions: function () {
            var self = this;

            // Back to List
            $(document).on('click', '#apd-back-to-list', function () {
                self.showList();
            });

            // Save Plan
            $(document).on('submit', '#apd-plan-form', function (e) {
                e.preventDefault();
                self.savePlan();
            });
        },

        // ================================================
        //  INSTALLMENT ROWS
        // ================================================
        bindInstallmentActions: function () {
            var self = this;

            // Add Rule
            $(document).on('click', '#apd-add-installment', function () {
                self.addInstallmentRow({ amount: '', due_type: 'immediately' });
            });

            // Remove Rule
            $(document).on('click', '.apd-remove-installment', function () {
                $(this).closest('.apd-installment-row').slideUp(200, function () {
                    $(this).remove();
                    self.updateTotal();
                    self.reindexRows();
                });
            });

            // Due Type change → show/hide extra fields
            $(document).on('change', '.apd-due-type-select', function () {
                var val = $(this).val();
                var $row = $(this).closest('.apd-installment-row');
                $row.find('.apd-due-extra').hide();
                if (val === 'after_purchase') {
                    $row.find('.apd-due-after').show();
                } else if (val === 'fixed_date') {
                    $row.find('.apd-due-fixed').show();
                }
            });

            // Amount change → update total
            $(document).on('input', '.apd-inst-amount-input', function () {
                self.updateTotal();
            });
        },

        // ================================================
        //  Price Type Suffix
        // ================================================
        bindPriceSuffix: function () {
            var self = this;
            $(document).on('change', '#apd-plan-price-type', function () {
                var suffix = $(this).val() === 'percentage' ? '%' : (typeof apd_admin !== 'undefined' ? '$' : '$');
                // If WC currency is available from the page
                var $wc_suffix = $('#apd-total-suffix');
                if ($wc_suffix.length) {
                    $wc_suffix.text(suffix);
                }
                $('.apd-inst-suffix').text(suffix);
            });
        },

        // ================================================
        //  Init Existing Rows (when editing)
        // ================================================
        initExistingRows: function () {
            if (typeof apdExistingInstallments === 'undefined' || !apdExistingInstallments.length) return;
            var self = this;

            // Rows are already rendered via PHP, we just need to index them
            self.rowIndex = apdExistingInstallments.length;
            self.updateTotal();

            // Show builder if editing
            if ($('#apd-plan-id').val()) {
                self.showBuilder();
            }
        },

        // ================================================
        //  Add Installment Row
        // ================================================
        addInstallmentRow: function (data) {
            var idx    = this.rowIndex++;
            var suffix = ($('#apd-plan-price-type').val() || 'percentage') === 'percentage' ? '%' : '$';

            var html = $('#tmpl-apd-installment-row').html()
                .replace(/\{\{data\.index\}\}/g, idx)
                .replace(/\{\{data\.amount\}\}/g, data.amount || '')
                .replace(/\{\{data\.suffix\}\}/g, suffix);

            var $row = $(html);

            // Set due type
            if (data.due_type) {
                $row.find('.apd-due-type-select').val(data.due_type);
                if (data.due_type === 'after_purchase') {
                    $row.find('.apd-due-after').show();
                    if (data.due_after_value) $row.find('[name*="due_after_value"]').val(data.due_after_value);
                    if (data.due_after_unit) $row.find('[name*="due_after_unit"]').val(data.due_after_unit);
                } else if (data.due_type === 'fixed_date') {
                    $row.find('.apd-due-fixed').show();
                    if (data.due_fixed_date) $row.find('[name*="due_fixed_date"]').val(data.due_fixed_date);
                }
            }

            $row.hide();
            $('#apd-installments-container').append($row);
            $row.slideDown(200);
            this.updateTotal();
        },

        // ================================================
        //  Update Total
        // ================================================
        updateTotal: function () {
            var total = 0;
            $('.apd-inst-amount-input').each(function () {
                total += parseFloat($(this).val()) || 0;
            });
            $('#apd-total-amount').text(total % 1 === 0 ? total : total.toFixed(2));
        },

        // ================================================
        //  Re-index Rows
        // ================================================
        reindexRows: function () {
            var idx = 0;
            $('#apd-installments-container .apd-installment-row').each(function () {
                var $row = $(this);
                $row.attr('data-index', idx);
                $row.find('[name]').each(function () {
                    $(this).attr('name', $(this).attr('name').replace(/installments\[\d+\]/, 'installments[' + idx + ']'));
                });
                idx++;
            });
            this.rowIndex = idx;
        },

        // ================================================
        //  Show / Hide Builder & List
        // ================================================
        showBuilder: function () {
            $('#apd-plans-list').slideUp(200);
            $('#apd-plan-builder').slideDown(200);
        },
        showList: function () {
            $('#apd-plan-builder').slideUp(200);
            $('#apd-plans-list').slideDown(200);
        },
        resetBuilder: function () {
            $('#apd-plan-id').val('');
            $('#apd-plan-name').val('');
            $('#apd-plan-price-type').val('percentage');
            $('#apd-plan-status').val('active');
            $('#apd-plan-description').val('');
            $('#apd-installments-container').empty();
            $('#apd-total-amount').text('0');
            $('#apd-total-suffix').text('%');
            $('#apd-builder-title').text('Add New Plan');
            this.rowIndex = 0;
        },

        // ================================================
        //  Load Plan for Editing via AJAX
        // ================================================
        loadPlanForEdit: function (planId) {
            var self = this;
            self.resetBuilder();

            $.post(apd_admin.ajax_url, {
                action: 'apd_get_plans',
                nonce:  apd_admin.nonce,
            }).done(function (r) {
                if (!r.success || !r.data[planId]) return;

                var plan = r.data[planId];
                $('#apd-plan-id').val(planId);
                $('#apd-plan-name').val(plan.name);
                $('#apd-plan-price-type').val(plan.price_type);
                $('#apd-plan-status').val(plan.status || 'active');
                $('#apd-plan-description').val(plan.description || '');
                $('#apd-builder-title').text('Edit Plan');

                // Update suffix
                var suffix = plan.price_type === 'percentage' ? '%' : '$';
                $('#apd-total-suffix').text(suffix);

                // Add rows
                if (plan.installments && plan.installments.length) {
                    $.each(plan.installments, function (i, inst) {
                        self.addInstallmentRow(inst);
                    });
                }

                self.showBuilder();
            });
        },

        // ================================================
        //  Save Plan
        // ================================================
        savePlan: function () {
            var self = this;
            var $btn = $('#apd-save-plan');

            // Collect installments
            var installments = [];
            $('#apd-installments-container .apd-installment-row').each(function () {
                var $row = $(this);
                installments.push({
                    amount:          parseFloat($row.find('.apd-inst-amount-input').val()) || 0,
                    due_type:        $row.find('.apd-due-type-select').val(),
                    due_after_value: parseInt($row.find('[name*="due_after_value"]').val()) || 0,
                    due_after_unit:  $row.find('[name*="due_after_unit"]').val() || 'day',
                    due_fixed_date:  $row.find('[name*="due_fixed_date"]').val() || '',
                });
            });

            if (!$('#apd-plan-name').val().trim()) {
                self.showToast('Please enter a plan name.', 'error');
                return;
            }
            if (!installments.length) {
                self.showToast('Please add at least one installment rule.', 'error');
                return;
            }

            $btn.prop('disabled', true).html('<span class="dashicons dashicons-update apd-spin"></span> Saving...');

            $.post(apd_admin.ajax_url, {
                action:           'apd_save_plan',
                nonce:            apd_admin.nonce,
                plan_id:          $('#apd-plan-id').val(),
                plan_name:        $('#apd-plan-name').val(),
                price_type:       $('#apd-plan-price-type').val(),
                plan_status:      $('#apd-plan-status').val(),
                plan_description: $('#apd-plan-description').val(),
                installments:     installments,
            }).done(function (r) {
                if (r.success) {
                    self.showToast(r.data.message, 'success');
                    setTimeout(function () { window.location.reload(); }, 800);
                } else {
                    self.showToast(r.data || 'Error saving plan.', 'error');
                }
            }).fail(function () {
                self.showToast('Network error.', 'error');
            }).always(function () {
                $btn.prop('disabled', false).html('<span class="dashicons dashicons-saved"></span> Save Plan');
            });
        },

        // ================================================
        //  Toast
        // ================================================
        showToast: function (message, type) {
            var $toast = $('#apd-toast');
            if (!$toast.length) {
                $toast = $('<div class="apd-toast" id="apd-toast"></div>').appendTo('body');
            }
            $toast.removeClass('apd-toast-success apd-toast-error show')
                .addClass('apd-toast-' + type)
                .text(message)
                .addClass('show');
            setTimeout(function () { $toast.removeClass('show'); }, 3500);
        },
    };

    // Report period
    $(document).on('change', '#apd-report-period', function () {
        var period = $(this).val();
        $.post(apd_admin.ajax_url, {
            action: 'apd_get_report_data',
            nonce:  apd_admin.nonce,
            period: period,
        }).done(function (r) {
            if (r.success) console.log('Report data:', r.data);
        });
    });

    $(document).ready(function () {
        APD_Plans.init();
    });

})(jQuery);
