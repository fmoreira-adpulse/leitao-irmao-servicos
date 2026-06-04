jQuery(document).ready(function($) {

    // #region Order List Page
    let tabOpeners = $('.filter_tab_opener');
    if (tabOpeners.length > 0) {
        tabOpeners.on("click", function (e) {
            let tabType = $(this).data('tab'),
                tabs = $('.adpulse-search-filters');
            
            tabs.filter(`:not([data-tab="${tabType}"])`).removeClass('open');
            tabs.filter(`[data-tab="${tabType}"]`).toggleClass('open');

            e.preventDefault();
        });
    }
    
    $('select#order_status').on('change', function() {
        let status = $(this).val(),
            inputs = jQuery(`input[data-status]`);
        
        for(let singleInput of inputs) {
            let requiredAndNotDisabled = $(singleInput).data('status') == status.substring(3);
            $(singleInput).prop('disabled', !requiredAndNotDisabled);
            $(singleInput).prop('required', requiredAndNotDisabled);
            
            if (requiredAndNotDisabled) {
                $(singleInput).removeClass('adpulse-custom-input-hidden');
                $(singleInput).parents('p').siblings('h3').removeClass('ad-pulse-hidden');
            }
            else {
                $(singleInput).addClass('adpulse-custom-input-hidden');
                $(singleInput).parents('p').siblings('h3').addClass('ad-pulse-hidden');
            }   
        }
    });

    checkForConditionalStatus();
    
    $(".__A__Order_Change_Statuses").on( "click", function (e) {
        let status = $(this).data('status'),
            modal = $(this).siblings(`.adpulse-modal[data-status="${status}"]`);

        if (modal.length > 0) {
            e.stopPropagation();
            modal.css('display', 'block');
        }
    });

    $(".order-alert").on("click", function () {
        if ($(this).hasClass("order-alert")) {
            let orderId = parseInt($(this).attr("id").substring(5));
            sendWpAjax("remove_alert", {order_id: orderId}).then(response => {
                $(this).removeClass("order-alert");
            });
        }
    });

    let urlParams = new URLSearchParams(window.location.search);
    checkSearchAndOpenTab(urlParams, 'product');
    checkSearchAndOpenTab(urlParams, 'status');
    checkSearchAndOpenTab(urlParams, 'priority');

    // #endregion

    // #region Order Page

    let saveOrderBtn = $('button.save_order');
    if (saveOrderBtn.length > 0) {

        // set default order status if the order is being created now
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('post') == null) {
            jQuery("span#select2-order_status-container").text("-");
            jQuery("select#order_status").val("wc-pedido-aberto");
        }

        saveOrderBtn.on('click', function () {
            $('.page-title-action').after('<span class="loader"></span>');
        });

        $('button[data-action=generate-order-report]').click(function(e) {
            debugger;
            let loader = $(this).find('.loader');
            e.preventDefault();
            generateReportAjax(loader);
        });

        $('button[data-action=confirm-payment]').click(function(e) {
            let orderId = $(this).data('id'),
                orderHref = $(this).data('href'),
                loader = $(this).find('.loader');

            e.preventDefault();

            if (orderHref !== '')
                window.open(orderHref);
            else
                confirmSubOrderPayment(orderId, loader);
        });

        let generatePaymentsButton = $('button[data-action=generate-payments]');

        generatePaymentsButton.click(function(e) {
            e.preventDefault();
            if (!$(this).hasClass('disabled')) {
                let params = { order_id: $(this).data('id'), payment_number: $("#payment-number").val() };
                sendWpAjax("generate_payments", params).then(response => {
                    window.location.reload();
                });
            }
        });

        $("#payment-number").change(function() {
            if ($(this).val() > 0)
                generatePaymentsButton.removeClass("disabled");
            else
                generatePaymentsButton.addClass("disabled");
        });

        $("form#order").on("submit", function(e) {
            let hasError = false,
                errorMsg = "",
                generatePaymentsContainer = $(".generate-payments-container"),
                table = $(".payments-table"),
                checkObjects = {
                    "check-payments-are-created": {
                        find: "[data-is-installment=\"1\"]", 
                        message: "no-installments-error"
                    },
                    "check-first-payment": {
                        find: "[data-is-installment=\"1\"][data-is-paid=\"1\"]", 
                        message: "no-paid-installments-error"
                    }
                };

            for (let [toCheck, terms] of Object.entries(checkObjects)) {
                if (generatePaymentsContainer.data(toCheck) === 1) {
                    hasError = !table.find(terms.find).length > 0;
                    errorMsg = generatePaymentsContainer.data(terms.message);

                    if (hasError)
                        break;
                }

                console.log(toCheck);
            }

            if (hasError) {
                e.preventDefault();
                alert(errorMsg);
            }
        });
    }

    let fileInput = $('input[name=\"attachments_order_files\[\]\"]');
    if(fileInput.length > 0) {
        fileInput.fileuploader({
            files: attachedFiles,
            addMore: true
        });
    }

    duplicateSubmitButton();

    disableBoxHandling();

    // Loader in the order detail page
    jQuery('#post').on('submit', function() {
        jQuery('body').after('<span class="wc-admin-loader"></span>');
    });

    // #endregion

    // #region Settings Page

    let productSelect = $('#order_status_order_product__0'),
        precedingStatusRows = $('[id^="fieldrow-order_status_next_status"]');

    // object that defines connections between radios has conditional inputs
    // example : "is_conditinal" == true => show input with slug "next_status", hide otherwise
    let conditionalRadios = {
        "is_conditional": ["next_status", "order_product"],
        "status_has_payment": ["1:minimum_payment_percentage", "status_payment_is_percentage", "minimum_absolute_payment"],
        "status_payment_is_percentage": ["2:minimum_payment_percentage", "!:minimum_absolute_payment"]
    };

    // apply logic of showing/hiding conditional inputs in the settings page
    let  composedConditionals = {};
    for (const [radioSlug, conditionalInputSlugs] of Object.entries(conditionalRadios)) {
        let positiveConditionals = [], negativeConditionals = [];

        for (let slug of conditionalInputSlugs) {
            if (slug[0] == "!")
                negativeConditionals.push(`[id^="fieldrow-order_status_${slug.substring(2)}"]`);
            else if (!isNaN(slug[0])) {
                if (composedConditionals.hasOwnProperty(slug.substring(2)))
                    composedConditionals[slug.substring(2)].push(radioSlug);
                else
                    composedConditionals[slug.substring(2)] = [radioSlug];
            }
            else
                positiveConditionals.push(`[id^="fieldrow-order_status_${slug}"]`);
        }

        if (positiveConditionals.length > 0)
            setConditionalRadio(radioSlug, positiveConditionals, true);
        if (negativeConditionals.length > 0)
            setConditionalRadio(radioSlug, negativeConditionals, false);
    }

    for (let [inputSlug, radioArray] of Object.entries(composedConditionals)) {
        let input = jQuery(`[id^="fieldrow-order_status_${inputSlug}"]`);
        for (let i = 0; i < 2; i++) {
            var iRadios = [];

            for (let radioInput of radioArray) {
                let thisRadio = jQuery(`[data-field_id="order_status_${radioInput}"] input[value="${i}"]`);
                iRadios.push(thisRadio);
            }

            checkRadios(iRadios, input, i === 1);
        }
    }

    if(productSelect.length > 0) {
        checkSelect(productSelect, precedingStatusRows);
        productSelect.change(function() {
            checkSelect(productSelect, precedingStatusRows);
        });
    }

    // #endregion

    // #region New Client Page
    
    // the "new user" button goes to the custom form now
    let energyPlusSuffix = "?energyplus_hide",
        originalCreateUserUrl = `wp-admin/user-new.php${energyPlusSuffix}`,
        customCreateUserUrl = `formulario-de-user${energyPlusSuffix}`,
        createClientButton = $(`a.btn[href*=\"${originalCreateUserUrl}\"]`);

    if (createClientButton.length > 0)
        createClientButton.attr('href', createClientButton.attr('href').replace(originalCreateUserUrl, customCreateUserUrl));

    // auto fill feature
    if(isCreateNewUserPage()) {
        // check if it is update or create
        let userId = getUserIDFromUrl();

        // if user id is present and greater than 0 then the user info is auto-fill
        if(userId > 0) {
            autoFillFromUserId(userId);

            // remove 'required' tag from the password input
            let passwordField = jQuery('[name="form_fields[user_pass]"]'),
                passwordGroup = passwordField.parent('.elementor-field-group'),
                passwordLabel = passwordGroup.find('label'),
                submitButtons = jQuery('.elementor-form-fields-wrapper .elementor-field-type-submit'),
                lastSubmitButtonText = jQuery(submitButtons[0]).find('.elementor-button-text');

            passwordLabel.append(' (preencher para atualizar, deixar vazio para permanecer inalterada)');
            lastSubmitButtonText.text('Atualizar utilizador');
        }

        checkAdminForUserRoleDropdown();
        addAutoFillButton();

        $('button.auto-fill-button').click(function(event) {
            event.preventDefault();
            switch($(this).data('action')) {
                case 'auto-fill-shipping':
                    copyFormData({'billing': 'shipping'});
                    break;
                case 'auto-fill-billing':
                    copyFormData({'': 'billing'});
            }
        });
    }

    // When form is being submitted
    $(document).on('elementor-pro/forms/ajax:send', function (event, formData) {
        debugger;
        console.log('Form is submitting...');
        clearTimeout(errorTimeout); // Clear any existing timeout

        // Start a timeout to show error manually if needed
        errorTimeout = setTimeout(function () {
        console.warn('Server still not responding... delaying error message.');
        // Optional: show custom message to user
        }, 5000);
    });
    // #endregion
});

jQuery(window).load(function() {
    // #region Support Ticket Page and List
    
    let searchParams = new URL(location.href).searchParams,
        ticketId = searchParams.get("post"),
        postType = searchParams.get("post_type");

    
    if (ticketId != undefined) {
        // is ticket detail page
        userIsTicketCreator(ticketId).then(response => {
            if (!response) {
                disableTicketEdit();
            }
        });
    } else if (postType != undefined && postType === 'wcsts_ticket') {
        // is ticket listing page
        jQuery(".row-actions").remove();
    }

    // #endregion
});

// #region Order Page Functions

// #endregion

// #region Settings Page Functions

function setConditionalRadio(radioSlug, inputs, isPositive) {
    let inputsToShow = jQuery(inputs.join(", "));

    // 2 here is the number of inputs in the "Yes" (= 1), "No" (= 0) radio
    for (let i = 0; i < 2; i++) {
        let conditionalRadio = jQuery(`[data-field_id="order_status_${radioSlug}"] input[value="${i}"]`);
        let showInput = isPositive? i === 1 : i === 0;

        if (conditionalRadio.length > 0) {
            // update the input's visibility now
            checkSingleRadioIsOn(conditionalRadio, inputsToShow, i === 1);

            // update the input's visibility whenever it is clicked
            conditionalRadio.change(function() {
                checkSingleRadioIsOn(conditionalRadio, inputsToShow, showInput);
            });
        }
    }
}

function checkRadios(radioList, input, show) {
    checkComposedRadioIsOn(radioList, input, show);

    for (let radio of radioList) {
        radio.change(function() {
            checkComposedRadioIsOn(radioList, input, show);
        });
    }
}

function checkComposedRadioIsOn(radioList, input, show) {
    // checks if all radios are checked
    if (radioList.every((radio) => radio.is(':checked')) && show || !show)
        checkRadioClasses(input, show);
}

function checkSingleRadioIsOn(radio, input, show) {
    if(radio.is(':checked'))
        checkRadioClasses(input, show)
}

function checkRadioClasses(input, show) {
    if(show)
        input.removeClass('hidden-by-radio');
    else
        input.addClass('hidden-by-radio');
}

function checkSelect(select, rows) {
    let selectVal =  select.val(),
        rowToShow = rows.filter('#fieldrow-order_status_next_status_' + selectVal);

    rows.addClass('ad-pulse-hidden');
    rowToShow.removeClass('ad-pulse-hidden');
}

function generateAllStatusDivs(orderID, statuses, statusesWithInputs) {
    let result = "";
    for (const [statusSlug, statusName] of Object.entries(statuses)) {
        let hasInput = statusesWithInputs.includes(statusSlug.substring(3))? "1" : "0";
        result += generateSingleStatusDiv(orderID, statusSlug, statusName, hasInput);
    }
    return result;
}

function generateSingleStatusDiv(orderID, statusSlug, statusName, hasInputStr) {
    var simpleSlug = statusSlug.substring(3);
    return  `<a href="javascript:;" data-status="${statusSlug}" data-do="changestatus" data-has-input="${hasInputStr}" data-id="${orderID}" data-text="${statusName}" class="__A__Ajax_Button __A__StopPropagation __A__Order_Change_Statuses">`+
                `<span class="text-${simpleSlug}">⬤</span>` +
                    `${statusName}` +
            `</a>`;
}

function checkForConditionalStatus() {
    jQuery('.__A__Order_Change_Statuses[data-has-input="1"]').on("click", function(e) {
        e.stopPropagation();
        let orderID = jQuery(this).data('id'),
            statusText = jQuery(this).data('text'),
            statusSlug =jQuery(this).data('status');

        localStorage.setItem('set-order-status-slug', statusSlug);
        localStorage.setItem('set-order-status-name', statusText);

        jQuery(`.__A__Ajax_Btn_SP.trig[data-hash="${orderID}"]`).trigger('click');
    });

    let statusSlug = localStorage.getItem('set-order-status-slug'),
        statusName = localStorage.getItem('set-order-status-name');

    if (statusSlug && statusName) {
        let selectSpan = jQuery('span#select2-order_status-container'),
            selectInput = jQuery('select#order_status');

        selectSpan.text(statusName);
        selectSpan.attr('title', statusName);

        selectInput.val(statusSlug);
        selectInput.trigger('change');

        localStorage.removeItem('set-order-status-slug');
        localStorage.removeItem('set-order-status-name');
    }
}

function checkSearchAndOpenTab(urlParams, searchKey) {
    if (urlParams.has(searchKey))
        jQuery(`.filter_tab_opener[data-tab="${searchKey}"]`).trigger('click');
}

function sendWpAjax(action, dataToSend = {}) {
    let endIndex = window.location.href.indexOf('wp-admin');
    if (endIndex === -1)
        endIndex = window.location.href.indexOf('formulario-de-user');

    let baseUrl = window.location.href.substring(0, endIndex);
    
    console.log();
    
    return jQuery.ajax({
        method: 'POST',
        url: `${baseUrl}wp-admin/admin-ajax.php`,
        data: Object.assign({action: action}, dataToSend)
    });
}

function generateReportAjax(loader) {
    let queryString = window.location.search,
        urlParams = new URLSearchParams(queryString),
        orderId = urlParams.get('post');

    loader.addClass('active');

    return sendWpAjax('generate_report', {order_id: orderId}).then(response => {
        let responseJson = JSON.parse(response);
        if (responseJson['status-code'] == 200)
            window.open(responseJson['file'], '_blank');
        else
            alert(responseJson['message']);

        loader.removeClass('active');
    });
}

function confirmSubOrderPayment(orderId, loader) {
    loader.addClass('active');
    sendWpAjax('confirm_sub_order_payment', {order_id: orderId}).then(response => {
        loader.removeClass('active');
        location.reload();
    });
}

// #endregion

// #region Create User functions
function isCreateNewUserPage() {
    return location.href.includes("formulario-de-user");
}

function autoFillButonHtml(section) {
    let buttonLabel = section === 'shipping'? 'Preencher com dados de faturação' : (section === 'billing'? 'Preencher com informação do utilizador' : '');

    return '<div class=\"e-form__buttons elementor-column elementor-col-100\">' +
        '<div class=\"elementor-field-group elementor-field-type-submit e-form__buttons__wrapper\">' +
					`<button class=\"elementor-button elementor-size-sm e-form__buttons__wrapper__button auto-fill-button\" data-action="auto-fill-${section}">` +
						'<span class=\"elementor-button-content-wrapper\">' +
                            `<span class=\"elementor-button-text\">${buttonLabel}</span>` +
                        '</span>' +
					'</button>' +
        '</div>' +
    '</div>';
}

function addAutoFillButton() {
    let parentDiv = jQuery("div.elementor-field-type-step");
    jQuery(parentDiv[1]).prepend(autoFillButonHtml('billing'));
    jQuery(parentDiv[parentDiv.length - 1]).prepend(autoFillButonHtml('shipping'));
}

function htmlSubformSelector(formSection) {
    let formSectionPrefix = formSection.length > 0? `${formSection}_` : "";
    return jQuery(`[name^="form_fields[${formSectionPrefix}"]`);
}

// TODO: adicionar botão no passo 2
function copyFormData(prefixesFromTo) {
    for(const [from, to] of Object.entries(prefixesFromTo)) {
        let fromInputs = htmlSubformSelector(from),
            toInputs = htmlSubformSelector(to);

        for(let toInput of toInputs) {
            let fieldLabel = jQuery(toInput).attr('name').replace(`form_fields[${to}_`, '').replace(']', ''),
                matchingInput = jQuery(fromInputs.filter(`[name*="${fieldLabel}"]`)[0]);

            jQuery(toInput).val(matchingInput.val());
        }
    }
}

function checkAdminForUserRoleDropdown() {
    sendWpAjax('is_user_admin').then(response => {
        if (response !== "true") {
            let dropdown = jQuery('#form-field-role'),
                defaultOption = jQuery(dropdown.find('option')[0]);
            
            dropdown.attr('disabled', true);
            dropdown.val(defaultOption.val());
            defaultOption.attr('selected', true);
        }
    })
}

function elementorMessageSuccess(message) {
    jQuery('form.elementor-form').append(`<div class="elementor-message elementor-message-success" role="alert">${message}</div>`);
}

function getUserIDFromUrl() {
    let userId = parseInt((new URL(location.href)).searchParams.get("user_id"));
    return !isNaN(userId)?  userId : 0;
}

function autoFillFromUserId(userId) {
    sendWpAjax('get_user_info', {user_id: userId}).then(response => {
        if (response.success && response.data.ID == userId) {
            debugger;
            jQuery(`[name="form_fields[user_id]"]`).val(userId);
            jQuery(`[name="form_fields[user_login]"]`).val(response.data.username);
            jQuery(`[name="form_fields[user_email]"]`).val(response.data.email);
            jQuery(`[name="form_fields[role]"]`).val(response.data.role);

            for (let metaLabel in response.data.meta) {
                let matchingInput = jQuery(`[name="form_fields[${metaLabel}]"]`);
                if (matchingInput !== undefined) {
                    matchingInput.val(response.data.meta[metaLabel][0]);
                }
            }
        }
    });
}

// #endregion

// #region Support Ticket functions

function userIsTicketCreator(ticketId) {
    return sendWpAjax('user_is_ticket_creator', {'ticket_id': ticketId});
}

function disableTicketEdit() {
    jQuery('select, input, button.insert-media').prop('disabled', true);
    jQuery(".wcsts_delete_message_button, .wcsts_delete_attachment_button, #submitdiv").remove();
    jQuery("body", jQuery("iframe").contents()).attr('contenteditable', false);
}

// #endregion

// #region Auxiliary functions

function duplicateSubmitButton() {
    let saveOrderButton = jQuery('.save_order.button-primary');
    if (saveOrderButton != undefined) {
        let postboxContainer = jQuery('#postbox-container-2.postbox-container');
        saveOrderButton.clone().appendTo(postboxContainer);
    }
}

function disableBoxHandling() {
    jQuery(".postbox-header").find('.hndle').removeClass("hndle");
    jQuery("button.handle-order-lower").remove();
    jQuery("button.handle-order-higher").remove();
}

// #endregion