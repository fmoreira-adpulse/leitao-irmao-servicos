jQuery( function ( $ ) {
    const container = $( '#lpf-phases-container' );

    // Calcular total de fases pagas
    function calcPaidTotal( orderTotal ) {
        let total = 0;
        container.find( '.lpf-phase-row' ).each( function () {
            if ( $( this ).find( '[name$="[status]"]' ).val() !== 'paid' ) return;
            const val  = parseFloat( $( this ).find( '.lpf-value-input' ).val() ) || 0;
            const type = $( this ).find( '.lpf-type-select' ).val();
            total += ( type === 'percentage' ) ? ( val / 100 ) * orderTotal : val;
        } );
        return total;
    }

    // Obrigatória — checkbox
    container.on( 'change', '.lpf-is-required-checkbox', function () {
        const row = $( this ).closest( '.lpf-phase-row' );
        const val = $( this ).is( ':checked' ) ? '1' : '0';
        row.find( '.lpf-is-required-hidden' ).val( val );
        row.toggleClass( 'is-required', $( this ).is( ':checked' ) );
    } );

    // Último pagamento — toggle
    container.on( 'change', '.lpf-is-last-checkbox', function () {
        const $cb  = $( this );
        const row  = $cb.closest( '.lpf-phase-row' );

        // Garantir exclusividade
        container.find( '.lpf-is-last-checkbox' ).not( $cb ).prop( 'checked', false );
        container.find( '.lpf-is-last-hidden' ).val( '0' );
        container.find( '.lpf-last-info' ).addClass( 'lpf-hidden' ).empty();
        container.find( '.lpf-vap-deduction-hidden' ).val( '0' );

        if ( ! $cb.is( ':checked' ) ) return;

        row.find( '.lpf-is-last-hidden' ).val( '1' );

        const orderTotal   = parseFloat( $( '#lpf-order-total' ).val() ) || 0;
        const vapDeduction = parseFloat( $( '#lpf-vap-deduction' ).val() ) || 0;
        const paidTotal    = calcPaidTotal( orderTotal );
        const suggested    = Math.max( 0, orderTotal - paidTotal - vapDeduction );

        // Preencher valor sugerido
        row.find( '.lpf-value-input' ).val( suggested.toFixed( 2 ) );

        // Guardar dedução na fase
        row.find( '.lpf-vap-deduction-hidden' ).val( vapDeduction.toFixed( 2 ) );

        // Mostrar nota de dedução
        const info = row.find( '.lpf-last-info' ).removeClass( 'lpf-hidden' );
        if ( vapDeduction > 0 ) {
            info.html(
                lpf_order.i18n.vap_deduction + ': -' + vapDeduction.toFixed( 2 ) + ' €'
                + ' &nbsp;|&nbsp; '
                + lpf_order.i18n.suggested_value + ': ' + suggested.toFixed( 2 ) + ' €'
            );
        }
    } );

    // Adicionar nova fase
    $( '#lpf-add-phase' ).on( 'click', function () {
        const template = $( '#lpf-phase-template' ).html();
        const phaseId  = 'phase_' + Date.now();
        const html     = template.replace( /__PHASE_ID__/g, phaseId );
        container.append( html );
    } );

    // Remover fase
    container.on( 'click', '.lpf-remove-phase', function () {
        $( this ).closest( '.lpf-phase-row' ).remove();
    } );

    // Alternar botão de acção conforme método (manual/online)
    container.on( 'change', '.lpf-method-select', function () {
        const row    = $( this ).closest( '.lpf-phase-row' );
        const online = $( this ).val() === 'online';
        row.find( '.lpf-mark-paid' ).toggleClass( 'lpf-hidden', online );
        row.find( '.lpf-send-link' ).toggleClass( 'lpf-hidden', ! online );
    } );

    // Enviar link de pagamento (AJAX)
    container.on( 'click', '.lpf-send-link', function () {
        const $btn    = $( this );
        const row     = $btn.closest( '.lpf-phase-row' );
        const phaseId = row.data( 'phase-id' );
        const orderId = $( '#post_ID' ).val()
                     || new URLSearchParams( window.location.search ).get( 'id' );

        const label   = row.find( 'input[name$="[description]"]' ).val();
        const confirm_msg = label
            ? lpf_order.i18n.confirm_send_link.replace( '%s', label )
            : lpf_order.i18n.confirm_send_link_generic;

        if ( ! confirm( confirm_msg ) ) return;

        $btn.prop( 'disabled', true ).text( '...' );

        $.post( lpf_order.ajax_url, {
            action:   'lpf_send_payment_link',
            nonce:    lpf_order.send_link_nonce,
            order_id: orderId,
            phase_id: phaseId,
        } )
        .done( function ( response ) {
            if ( response.success ) {
                $btn.text( lpf_order.i18n.link_sent + ' ' + response.data.sent_at );
            } else {
                alert( response.data.message || lpf_order.i18n.error );
                $btn.prop( 'disabled', false ).text( lpf_order.i18n.send_link );
            }
        } )
        .fail( function () {
            alert( lpf_order.i18n.error );
            $btn.prop( 'disabled', false ).text( lpf_order.i18n.send_link );
        } );
    } );

    const orderTotal = parseFloat( $( '#lpf-order-total' ).val() ) || 0;

    function calcPendingTotal() {
        let total = 0;
        container.find( '.lpf-phase-row' ).each( function () {
            if ( $( this ).find( '[name$="[status]"]' ).val() === 'paid' ) return;
            const val  = parseFloat( $( this ).find( '.lpf-value-input' ).val() ) || 0;
            const type = $( this ).find( '.lpf-type-select' ).val() || 'nominal';
            total += ( type === 'percentage' ) ? ( val / 100 ) * orderTotal : val;
        } );
        return total;
    }

    function validateTotal() {
        const $feedback  = $( '#lpf-save-feedback' );
        const $btn       = $( '#lpf-save-phases' );
        if ( calcPendingTotal() > orderTotal + 0.001 ) {
            $feedback.text( lpf_order.i18n.total_exceeds ).addClass( 'is-error' );
            $btn.prop( 'disabled', true );
            return false;
        }
        $feedback.text( '' ).removeClass( 'is-error' );
        $btn.prop( 'disabled', false );
        return true;
    }

    container.on( 'input',  '.lpf-value-input',  validateTotal );
    container.on( 'change', '.lpf-type-select',  validateTotal );

    // Guardar (AJAX)
    $( '#lpf-save-phases' ).on( 'click', function ( e ) {
        e.preventDefault();
        e.stopPropagation();
        const $btn      = $( this );
        const $feedback = $( '#lpf-save-feedback' );
        const orderId   = $( '#post_ID' ).val()
                       || new URLSearchParams( window.location.search ).get( 'id' );

        if ( ! validateTotal() ) return;

        const phases = [];
        container.find( '.lpf-phase-row' ).each( function () {
            const row = $( this );
            phases.push( {
                phase_id:      row.data( 'phase-id' ),
                description:   row.find( '[name$="[description]"]' ).val(),
                type:          row.find( '.lpf-type-select' ).val(),
                value:         row.find( '.lpf-value-input' ).val(),
                method:        row.find( '.lpf-method-select' ).val(),
                status:        row.find( '[name$="[status]"]' ).val(),
                paid_at:       row.find( '[name$="[paid_at]"]' ).val(),
                is_last:       row.find( '.lpf-is-last-hidden' ).val(),
                is_required:   row.find( '.lpf-is-required-hidden' ).val(),
                vap_deduction: row.find( '.lpf-vap-deduction-hidden' ).val(),
            } );
        } );

        $btn.prop( 'disabled', true ).text( '...' );
        $feedback.text( '' ).removeClass( 'is-error' );

        $.post( lpf_order.ajax_url, {
            action:   'lpf_save_phases',
            nonce:    lpf_order.save_nonce,
            order_id: orderId,
            phases:   JSON.stringify( phases ),
        } )
        .done( function ( response ) {
            if ( response.success ) {
                $( '#lpf-phases-summary' ).html( response.data.summary_html );
                $feedback.text( lpf_order.i18n.saved );
                setTimeout( function () {
                    $btn.prop( 'disabled', false ).text( lpf_order.i18n.save_recalculate );
                    $feedback.text( '' );
                }, 2000 );
            } else {
                $feedback.text( response.data.message || lpf_order.i18n.error ).addClass( 'is-error' );
                $btn.prop( 'disabled', false ).text( lpf_order.i18n.save_recalculate );
            }
        } )
        .fail( function () {
            $feedback.text( lpf_order.i18n.error ).addClass( 'is-error' );
            $btn.prop( 'disabled', false ).text( lpf_order.i18n.save_recalculate );
        } );
    } );

    // Marcar fase como paga (AJAX)
    container.on( 'click', '.lpf-mark-paid', function () {
        if ( ! confirm( lpf_order.i18n.confirm_paid ) ) return;

        const $btn    = $( this );
        const row     = $btn.closest( '.lpf-phase-row' );
        const phaseId = row.data( 'phase-id' );
        const orderId = $( '#post_ID' ).val()
                     || new URLSearchParams( window.location.search ).get( 'id' );

        $btn.prop( 'disabled', true ).text( '...' );

        $.post( lpf_order.ajax_url, {
            action:   'lpf_mark_phase_paid',
            nonce:    lpf_order.nonce,
            order_id: orderId,
            phase_id: phaseId,
        } )
        .done( function ( response ) {
            if ( response.success ) {
                const paidAt    = response.data.paid_at;
                const newStatus = response.data.new_status;

                row.find( 'input:not([type="hidden"]), select' ).prop( 'disabled', true );
                row.find( '[name$="[status]"]' ).val( 'paid' );
                row.find( '[name$="[paid_at]"]' ).val( paidAt );

                row.find( '.lpf-phase-actions' ).html(
                    '<span class="lpf-status-badge is-paid">'
                    + lpf_order.i18n.paid + ' (' + paidAt + ')'
                    + '</span>'
                );

                row.find( '.lpf-phase-invoice' ).removeClass( 'lpf-hidden' );
                row.removeClass( 'is-pending' ).addClass( 'is-paid' );

                // Sincronizar o dropdown de estado do WooCommerce para evitar reversão ao guardar o form
                if ( newStatus ) {
                    const newValue    = 'wc-' + newStatus;
                    const $wc_select  = $( '#order_status' );
                    if ( $wc_select.length ) {
                        if ( ! $wc_select.find( 'option[value="' + newValue + '"]' ).length ) {
                            $wc_select.append( $( '<option>' ).val( newValue ).text( newValue ) );
                        }
                        $wc_select.val( newValue ).trigger( 'change' );
                    }
                }
            } else {
                alert( response.data.message || lpf_order.i18n.error );
                $btn.prop( 'disabled', false ).text( 'Marcar como pago' );
            }
        } )
        .fail( function () {
            alert( lpf_order.i18n.error );
            $btn.prop( 'disabled', false ).text( 'Marcar como pago' );
        } );
    } );

    // Auto-upload ao seleccionar ficheiro de fatura
    container.on( 'change', '.lpf-invoice-file-input', function () {
        const $input   = $( this );
        const file     = $input[0].files[0];
        if ( ! file ) return;

        const row      = $input.closest( '.lpf-phase-row' );
        const phaseId  = row.data( 'phase-id' );
        const orderId  = $( '#post_ID' ).val()
                      || new URLSearchParams( window.location.search ).get( 'id' );
        const $invoice = row.find( '.lpf-phase-invoice' );
        const $label   = $invoice.find( '.lpf-upload-label' );
        const $preview = $invoice.find( '.lpf-upload-filename-preview' );
        const $feedback = $invoice.find( '.lpf-upload-feedback' );

        $preview.text( file.name );
        $feedback.text( lpf_order.i18n.uploading ).removeClass( 'is-error' );
        $label.addClass( 'lpf-uploading' );

        const formData = new FormData();
        formData.append( 'action',       'lpf_upload_phase_invoice' );
        formData.append( 'nonce',        lpf_order.upload_invoice_nonce );
        formData.append( 'order_id',     orderId );
        formData.append( 'phase_id',     phaseId );
        formData.append( 'invoice_file', file );

        $.ajax( {
            url:         lpf_order.ajax_url,
            type:        'POST',
            data:        formData,
            processData: false,
            contentType: false,
        } )
        .done( function ( response ) {
            $label.removeClass( 'lpf-uploading' );
            if ( response.success ) {
                const { attachment_id, file_url, filename } = response.data;
                $invoice.find( '.lpf-invoice-file-id-hidden' ).val( attachment_id );
                $invoice.find( '.lpf-invoice-sent-at-hidden' ).val( '' );
                $invoice.find( '.lpf-invoice-filename' ).text( '📎 ' + filename );
                $invoice.find( '.lpf-invoice-view-link' ).attr( 'href', file_url );
                $invoice.find( '.lpf-invoice-sent-label' ).text( '' ).addClass( 'lpf-hidden' );
                $invoice.find( '.lpf-invoice-upload-area' ).addClass( 'lpf-hidden' );
                $invoice.find( '.lpf-invoice-file-area' ).removeClass( 'lpf-hidden' );
                $feedback.text( '' );
            } else {
                $preview.text( '' );
                $feedback.text( response.data.message || lpf_order.i18n.error ).addClass( 'is-error' );
            }
        } )
        .fail( function () {
            $label.removeClass( 'lpf-uploading' );
            $preview.text( '' );
            $feedback.text( lpf_order.i18n.error ).addClass( 'is-error' );
        } );
    } );

    // Enviar fatura por email
    container.on( 'click', '.lpf-send-invoice', function () {
        const $btn    = $( this );
        const row     = $btn.closest( '.lpf-phase-row' );
        const phaseId = row.data( 'phase-id' );
        const orderId = $( '#post_ID' ).val()
                     || new URLSearchParams( window.location.search ).get( 'id' );
        const label   = row.find( 'input[name$="[description]"]' ).val() || phaseId;

        if ( ! confirm( lpf_order.i18n.confirm_send_invoice.replace( '%s', label ) ) ) return;

        $btn.prop( 'disabled', true ).text( '...' );

        $.post( lpf_order.ajax_url, {
            action:   'lpf_send_phase_invoice',
            nonce:    lpf_order.send_invoice_nonce,
            order_id: orderId,
            phase_id: phaseId,
        } )
        .done( function ( response ) {
            if ( response.success ) {
                const sentAt   = response.data.sent_at;
                const $invoice = row.find( '.lpf-phase-invoice' );
                $invoice.find( '.lpf-invoice-sent-at-hidden' ).val( sentAt );
                $invoice.find( '.lpf-invoice-sent-label' )
                    .text( lpf_order.i18n.invoice_sent + ' ' + sentAt )
                    .removeClass( 'lpf-hidden' );
                $btn.prop( 'disabled', false ).text( lpf_order.i18n.send_invoice );
            } else {
                alert( response.data.message || lpf_order.i18n.error );
                $btn.prop( 'disabled', false ).text( lpf_order.i18n.send_invoice );
            }
        } )
        .fail( function () {
            alert( lpf_order.i18n.error );
            $btn.prop( 'disabled', false ).text( lpf_order.i18n.send_invoice );
        } );
    } );

    // Remover fatura (limpa a referência; o ficheiro fica na media library)
    container.on( 'click', '.lpf-remove-invoice', function () {
        const $invoice = $( this ).closest( '.lpf-phase-invoice' );
        $invoice.find( '.lpf-invoice-file-id-hidden' ).val( '0' );
        $invoice.find( '.lpf-invoice-sent-at-hidden' ).val( '' );
        $invoice.find( '.lpf-invoice-file-area' ).addClass( 'lpf-hidden' );
        $invoice.find( '.lpf-invoice-file-input' ).val( '' );
        $invoice.find( '.lpf-upload-feedback' ).text( '' );
        $invoice.find( '.lpf-invoice-upload-area' ).removeClass( 'lpf-hidden' );
    } );

} );
