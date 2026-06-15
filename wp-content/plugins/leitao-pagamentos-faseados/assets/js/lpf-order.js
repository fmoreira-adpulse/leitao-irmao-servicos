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
                const paidAt = response.data.paid_at;

                row.find( 'input, select' ).prop( 'disabled', true );
                row.find( '[name$="[status]"]' ).val( 'paid' );
                row.find( '[name$="[paid_at]"]' ).val( paidAt );

                row.find( '.lpf-phase-actions' ).html(
                    '<span class="lpf-status-badge is-paid">'
                    + lpf_order.i18n.paid + ' (' + paidAt + ')'
                    + '</span>'
                );

                row.removeClass( 'is-pending' ).addClass( 'is-paid' );
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
} );
