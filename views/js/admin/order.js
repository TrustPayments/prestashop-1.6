/**
 * Trust Payments Prestashop
 *
 * This Prestashop module enables to process payments with Trust Payments (https://www.trustpayments.com/).
 *
 * @author customweb GmbH (http://www.customweb.com/)
 * @copyright 2017 - 2023 customweb GmbH
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache Software License (ASL 2.0)
 */
jQuery(function ($) {

    function getOrderIdFromUrl(string) {
        let urlSegment = string.split('trustpayments')[1];
        return urlSegment.split('/')[1]
    }
    
    function initialiseDocumentButtons() {
        if ($('[data-original-title="Download TrustPayments Invoice"]').length) {
            $('[data-original-title="Download Packing Slip"]').click(function(e) {
                e.preventDefault();
                let id_order = getOrderIdFromUrl($(this).attr('href'));
                window.open(trustpayments_admin_token + "&action=trustPaymentsPackingSlip&id_order=" + id_order, "_blank");
            });
        
            $('[data-original-title="Download TrustPayments Invoice"]').click(function(e) {
                e.preventDefault();
                let id_order = getOrderIdFromUrl($(this).attr('href'));
                window.open(trustpayments_admin_token + "&action=trustPaymentsInvoice&id_order=" + id_order, "_blank");
            });
        
            $('#order_grid_table tr').each(function() {
                let $this = $(this);
                let $row = $this.closest('tr');
                let isWPayment = "0";
                let $paymentStatusCol = $row.find('.column-osname');
                let isWPaymentCol = $row.find('.column-is_w_payment').html();
                if (isWPaymentCol) {
                    isWPayment = isWPaymentCol.trim();
                }
                let paymentStatusText = $paymentStatusCol.find('.btn').text();
                if (!paymentStatusText.includes("Payment accepted") || isWPayment.includes("0")) {
                    $row.find('[data-original-title="Download TrustPayments Invoice"]').hide();
                    $row.find('[data-original-title="Download Packing Slip"]').hide();
                }
            });
        }
    }

    function hideIsWPaymentColumn() {
        $('th').each(function() {
            let $this = $(this);
            if ($this.html().includes("is_w_payment")) {
                $('table tr').find('td:eq(' + $this.index() + '),th:eq(' + $this.index() + ')').remove();
                return false;
            }
        });
    }

    function isVersionGTE177()
    {
        if (_PS_VERSION_ === undefined) {
            return false;
        } else {
            return compareVersions(_PS_VERSION_, "1.7.7");
        }
    }

    function compareVersions (currentVersion, minVersion)
    {
        currentVersion = currentVersion.split('.');
        minVersion = minVersion.split('.');
        // we only care about the 3rd digit of the version as 1.8 will be a whole different kettle of fish
        if (typeof currentVersion[2] === 'undefined') {
            return false;
        }
        return (currentVersion[2] >= minVersion[2]) ? true : false;
    }
    
    function moveTrustPaymentsDocuments()
    {
        var documentsTab = $('#trustpayments_documents_tab');
        if (isVersionGTE177()) {
            documentsTab.children('a').addClass('nav-link');
        } else {
            var parentElement = documentsTab.parent();
            documentsTab.detach().appendTo(parentElement);
        }
    }
    
    function moveTrustPaymentsActionsAndInfo()
    {
        var managementBtn = $('a.trustpayments-management-btn');
        var managementInfo = $('span.trustpayments-management-info');
        var orderActions = $('div.order-actions');
        var panel = $('div.panel');
        
        managementBtn.each(function (key, element) {
            $(element).detach();
            if (isVersionGTE177()) {
                orderActions.find('.order-navigation').before(element);
            } else {
                panel.find('div.well.hidden-print').find('i.icon-print').closest('div.well').append(element);
            }
        });
        managementInfo.each(function (key, element) {
            $(element).detach();
            if (isVersionGTE177()) {
                orderActions.find('.order-navigation').before(element);
            } else {
                panel.find('div.well.hidden-print').find('i.icon-print').closest('div.well').append(element);
            }
        });
        //to get the styling of prestashop we have to add this
        managementBtn.after("&nbsp;\n");
        managementInfo.after("&nbsp;\n");
    }
    
    function registerTrustPaymentsActions()
    {
        $('#trustpayments_update').off('click.trustpayments').on(
            'click.trustpayments',
            updateTrustPayments
        );
        $('#trustpayments_void').off('click.trustpayments').on(
            'click.trustpayments',
            showTrustPaymentsVoid
        );
        $("#trustpayments_completion").off('click.trustpayments').on(
            'click.trustpayments',
            showTrustPaymentsCompletion
        );
        $('#trustpayments_completion_submit').off('click.trustpayments').on(
            'click.trustpayments',
            executeTrustPaymentsCompletion
        );
    }
    
    function showTrustPaymentsInformationSuccess(msg)
    {
        showTrustPaymentsInformation(msg, trustpayments_msg_general_title_succes, trustpayments_btn_info_confirm_txt, 'dark_green', function () {
            window.location.replace(window.location.href);});
    }
    
    function showTrustPaymentsInformationFailures(msg)
    {
        showTrustPaymentsInformation(msg, trustpayments_msg_general_title_error, trustpayments_btn_info_confirm_txt, 'dark_red', function () {
            window.location.replace(window.location.href);});
    }
    
    function showTrustPaymentsInformation(msg, title, btnText, theme, callback)
    {
        $.jAlert({
            'type': 'modal',
            'title': title,
            'content': msg,
            'theme': theme,
            'replaceOtherAlerts': true,
            'closeOnClick': false,
            'closeOnEsc': false,
            'closeBtn': false,
            'btns': [
            {
                'text': btnText,
                'closeAlert': true,
                'theme': 'blue',
                'onClick': callback
            }
            ],
            'onClose': callback
        });
    }
    
    function updateTrustPayments()
    {
        $.ajax({
            type:   'POST',
            dataType:   'json',
            url:    trustPaymentsUpdateUrl,
            success:    function (response, textStatus, jqXHR) {
                if ( response.success === 'true' ) {
                    location.reload();
                    return;
                } else if ( response.success === 'false' ) {
                    if (response.message) {
                        showTrustPaymentsInformation(response.message, msg_trustpayments_confirm_txt);
                    }
                    return;
                }
                showTrustPaymentsInformation(trustpayments_msg_general_error, msg_trustpayments_confirm_txt);
            },
            error:  function (jqXHR, textStatus, errorThrown) {
                showTrustPaymentsInformation(trustpayments_msg_general_error, msg_trustpayments_confirm_txt);
            }
        });
    }
    
        
    function showTrustPaymentsVoid(e)
    {
        e.preventDefault();
        $.jAlert({
            'type': 'modal',
            'title': trustpayments_void_title,
            'content': $('#trustpayments_void_msg').text(),
            'class': 'multiple_buttons',
            'closeOnClick': false,
            'closeOnEsc': false,
            'closeBtn': false,
            'btns': [
            {
                'text': trustpayments_void_btn_deny_txt,
                'closeAlert': true,
                'theme': 'black'
            },
            {
                'text': trustpayments_void_btn_confirm_txt,
                'closeAlert': true,
                'theme': 'blue',
                'onClick':  executeTrustPaymentsVoid

            }
            ],
            'theme':'blue'
        });
        return false;
    }

    function executeTrustPaymentsVoid()
    {
        showTrustPaymentsSpinner();
        $.ajax({
            type:   'POST',
            dataType:   'json',
            url:    trustPaymentsVoidUrl,
            success:    function (response, textStatus, jqXHR) {
                if ( response.success === 'true' ) {
                    showTrustPaymentsInformationSuccess(response.message);
                    return;
                } else if ( response.success === 'false' ) {
                    if (response.message) {
                        showTrustPaymentsInformationFailures(response.message);
                        return;
                    }
                }
                showTrustPaymentsInformationFailures(trustpayments_msg_general_error);
            },
            error:  function (jqXHR, textStatus, errorThrown) {
                showTrustPaymentsInformationFailures(trustpayments_msg_general_error);
            }
        });
        return false;
    }
    
    
    function showTrustPaymentsSpinner()
    {
        $.jAlert({
            'type': 'modal',
            'title': false,
            'content': '<div class="trustpayments-loader"></div>',
            'closeOnClick': false,
            'closeOnEsc': false,
            'closeBtn': false,
            'class': 'unnoticeable',
            'replaceOtherAlerts': true
        });
    
    }
    
    function showTrustPaymentsCompletion(e)
    {
        e.preventDefault();
        $.jAlert({
            'type': 'modal',
            'title': trustpayments_completion_title,
            'content': $('#trustpayments_completion_msg').text(),
            'class': 'multiple_buttons',
            'closeOnClick': false,
            'closeOnEsc': false,
            'closeBtn': false,
            'btns': [
            {
                'text': trustpayments_completion_btn_deny_txt,
                'closeAlert': true,
                'theme': 'black'
            },
            {
                'text': trustpayments_completion_btn_confirm_txt,
                'closeAlert': true,
                'theme': 'blue',
                'onClick': executeTrustPaymentsCompletion
            }
            ],
            'theme':'blue'
        });

        return false;
    }
    
    
    function executeTrustPaymentsCompletion()
    {
        showTrustPaymentsSpinner();
        $.ajax({
            type:   'POST',
            dataType:   'json',
            url:    trustPaymentsCompletionUrl,
            success:    function (response, textStatus, jqXHR) {
                if ( response.success === 'true' ) {
                    showTrustPaymentsInformationSuccess(response.message);
                    return;
                } else if ( response.success === 'false' ) {
                    if (response.message) {
                        showTrustPaymentsInformationFailures(response.message);
                        return;
                    }
                }
                showTrustPaymentsInformationFailures(trustpayments_msg_general_error);
            },
            error:  function (jqXHR, textStatus, errorThrown) {
                showTrustPaymentsInformationFailures(trustpayments_msg_general_error);
            }
        });
        return false;
    }
    
    function trustPaymentsTotalRefundChanges()
    {
        var generateDiscount =  $('.standard_refund_fields').find('#generateDiscount').attr("checked") === 'checked';
        var sendOffline = $('#trustpayments_refund_offline_cb_total').attr("checked") === 'checked';
        trustPaymentsRefundChanges('total', generateDiscount, sendOffline);
    }
    
    function trustPaymentsPartialRefundChanges()
    {
    
        var generateDiscount = $('.partial_refund_fields').find('#generateDiscountRefund').attr("checked") === 'checked';
        var sendOffline = $('#trustpayments_refund_offline_cb_partial').attr("checked")  === 'checked';
        trustPaymentsRefundChanges('partial', generateDiscount, sendOffline);
    }
    
    function trustPaymentsRefundChanges(type, generateDiscount, sendOffline)
    {
        if (generateDiscount) {
            $('#trustpayments_refund_online_text_'+type).css('display','none');
            $('#trustpayments_refund_offline_span_'+type).css('display','block');
            if (sendOffline) {
                $('#trustpayments_refund_offline_text_'+type).css('display','block');
                $('#trustpayments_refund_no_text_'+type).css('display','none');
            } else {
                $('#trustpayments_refund_no_text_'+type).css('display','block');
                $('#trustpayments_refund_offline_text_'+type).css('display','none');
            }
        } else {
            $('#trustpayments_refund_online_text_'+type).css('display','block');
            $('#trustpayments_refund_no_text_'+type).css('display','none');
            $('#trustpayments_refund_offline_text_'+type).css('display','none');
            $('#trustpayments_refund_offline_span_'+type).css('display','none');
            $('#trustpayments_refund_offline_cb_'+type).attr('checked', false);
        }
    }
    
    function handleTrustPaymentsLayoutChanges()
    {
        var addVoucher = $('#add_voucher');
        var addProduct = $('#add_product');
        var editProductChangeLink = $('.edit_product_change_link');
        var descOrderStandardRefund = $('#desc-order-standard_refund');
        var standardRefundFields = $('.standard_refund_fields');
        var partialRefundFields = $('.partial_refund_fields');
        var descOrderPartialRefund = $('#desc-order-partial_refund');

        if ($('#trustpayments_is_transaction').length > 0) {
            addVoucher.remove();
        }
        if ($('#trustpayments_remove_edit').length > 0) {
            addProduct.remove();
            addVoucher.remove();
            editProductChangeLink.closest('div').remove();
            $('.panel-vouchers').find('i.icon-minus-sign').closest('a').remove();
        }
        if ($('#trustpayments_remove_cancel').length > 0) {
            descOrderStandardRefund.remove();
        }
        if ($('#trustpayments_changes_refund').length > 0) {
            $('#refund_total_3').closest('div').remove();
            standardRefundFields.find('div.form-group').after($('#trustpayments_refund_online_text_total'));
            standardRefundFields.find('div.form-group').after($('#trustpayments_refund_offline_text_total'));
            standardRefundFields.find('div.form-group').after($('#trustpayments_refund_no_text_total'));
            standardRefundFields.find('#spanShippingBack').after($('#trustpayments_refund_offline_span_total'));
            standardRefundFields.find('#generateDiscount').off('click.trustpayments').on('click.trustpayments', trustPaymentsTotalRefundChanges);
            $('#trustpayments_refund_offline_cb_total').on('click.trustpayments', trustPaymentsTotalRefundChanges);
        
            $('#refund_3').closest('div').remove();
            partialRefundFields.find('button').before($('#trustpayments_refund_online_text_partial'));
            partialRefundFields.find('button').before($('#trustpayments_refund_offline_text_partial'));
            partialRefundFields.find('button').before($('#trustpayments_refund_no_text_partial'));
            partialRefundFields.find('#generateDiscountRefund').closest('p').after($('#trustpayments_refund_offline_span_partial'));
            partialRefundFields.find('#generateDiscountRefund').off('click.trustpayments').on('click.trustpayments', trustPaymentsPartialRefundChanges);
            $('#trustpayments_refund_offline_cb_partial').on('click.trustpayments', trustPaymentsPartialRefundChanges);
        }
        if ($('#trustpayments_completion_pending').length > 0) {
            addProduct.remove();
            addVoucher.remove();
            editProductChangeLink.closest('div').remove();
            descOrderPartialRefund.remove();
            descOrderStandardRefund.remove();
        }
        if ($('#trustpayments_void_pending').length > 0) {
            addProduct.remove();
            addVoucher.remove();
            editProductChangeLink.closest('div').remove();
            descOrderPartialRefund.remove();
            descOrderStandardRefund.remove();
        }
        if ($('#trustpayments_refund_pending').length > 0) {
            descOrderStandardRefund.remove();
            descOrderPartialRefund.remove();
        }
        moveTrustPaymentsDocuments();
        moveTrustPaymentsActionsAndInfo();
    }
    
    function init()
    {
        handleTrustPaymentsLayoutChanges();
        registerTrustPaymentsActions();
        initialiseDocumentButtons();
        hideIsWPaymentColumn();
    }
    
    init();
});
