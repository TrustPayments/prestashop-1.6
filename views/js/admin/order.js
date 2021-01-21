/**
 * Trust Payments Prestashop
 *
 * This Prestashop module enables to process payments with Trust Payments (https://www.trustpayments.com/).
 *
 * @author customweb GmbH (http://www.customweb.com/)
 * @copyright 2017 - 2021 customweb GmbH
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache Software License (ASL 2.0)
 */
jQuery(function ($) {
    
    function moveTrustPaymentsDocuments()
    {
        var parentElement = $("#trustpayments_documents_tab").parent();
        $("#trustpayments_documents_tab").detach().appendTo(parentElement);
    }
    
    function moveTrustPaymentsActionsAndInfo()
    {
        $("a.trustpayments-management-btn").each(function (key, element) {
            $(element).detach();
            $("div.panel").find("div.well.hidden-print").find("i.icon-print").closest("div.well").append(element);
        });
        $("span.trustpayments-management-info").each(function (key, element) {
            $(element).detach();
            $("div.panel").find("div.well.hidden-print").find("i.icon-print").closest("div.well").append(element);
        });
    //to get the styling of prestashop we have to add this
        $("a.trustpayments-management-btn").after("&nbsp;\n");
        $("span.trustpayments-management-info").after("&nbsp;\n");
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
            'closeBtn': false,
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
                if ( response.success == 'true' ) {
                    location.reload();
                    return;
                } else if ( response.success == 'false' ) {
                    if (response.message) {
                        showTrustPaymentsInformation(response.message, msg_trustpayments_confirm_txt);
                    }
                    return;
                }
                showTrustPaymentsInformation(trustpayments_msg_general_error, msg_trustpayments_confirm_txt);
            },
            error:  function (jqXHR, textStatus, errorThrown) {
                showTrustPaymentsInformation(trustpayments_msg_general_error, msg_trustpayments_confirm_txt);
            },
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
                'theme': 'black',
            },
            {
                'text': trustpayments_void_btn_confirm_txt,
                'closeAlert': true,
                'theme': 'blue',
                'onClick':  executeTrustPaymentsVoid

            }
            ],
            'theme':'blue',
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
                if ( response.success == 'true' ) {
                    showTrustPaymentsInformationSuccess(response.message);
                    return;
                } else if ( response.success == 'false' ) {
                    if (response.message) {
                        showTrustPaymentsInformationFailures(response.message);
                        return;
                    }
                }
                showTrustPaymentsInformationFailures(trustpayments_msg_general_error);
            },
            error:  function (jqXHR, textStatus, errorThrown) {
                showTrustPaymentsInformationFailures(trustpayments_msg_general_error);
            },
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
                'theme': 'black',
            },
            {
                'text': trustpayments_completion_btn_confirm_txt,
                'closeAlert': true,
                'theme': 'blue',
                'onClick': executeTrustPaymentsCompletion
            }
            ],
            'theme':'blue',
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
                if ( response.success == 'true' ) {
                    showTrustPaymentsInformationSuccess(response.message);
                    return;
                } else if ( response.success == 'false' ) {
                    if (response.message) {
                        showTrustPaymentsInformationFailures(response.message);
                        return;
                    }
                }
                showTrustPaymentsInformationFailures(trustpayments_msg_general_error);
            },
            error:  function (jqXHR, textStatus, errorThrown) {
                showTrustPaymentsInformationFailures(trustpayments_msg_general_error);
            },
        });
        return false;
    }
    
    function trustPaymentsTotalRefundChanges()
    {
        var generateDiscount =  $('.standard_refund_fields').find('#generateDiscount').attr("checked") == 'checked';
        var sendOffline = $('#trustpayments_refund_offline_cb_total').attr("checked") == 'checked';
        trustPaymentsRefundChanges('total', generateDiscount, sendOffline);
    }
    
    function trustPaymentsPartialRefundChanges()
    {
    
        var generateDiscount = $('.partial_refund_fields').find('#generateDiscountRefund').attr("checked") == 'checked';
        var sendOffline = $('#trustpayments_refund_offline_cb_partial').attr("checked")  == 'checked';
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
        if ($('#trustpayments_is_transaction').length > 0) {
            $('#add_voucher').remove();
        }
        if ($('#trustpayments_remove_edit').length > 0) {
            $('#add_product').remove();
            $('#add_voucher').remove();
            $('.edit_product_change_link').closest('div').remove();
            $('.panel-vouchers').find('i.icon-minus-sign').closest('a').remove();
        }
        if ($('#trustpayments_remove_cancel').length > 0) {
            $('#desc-order-standard_refund').remove();
        }
        if ($('#trustpayments_changes_refund').length > 0) {
            $('#refund_total_3').closest('div').remove();
            $('.standard_refund_fields').find('div.form-group').after($('#trustpayments_refund_online_text_total'));
            $('.standard_refund_fields').find('div.form-group').after($('#trustpayments_refund_offline_text_total'));
            $('.standard_refund_fields').find('div.form-group').after($('#trustpayments_refund_no_text_total'));
            $('.standard_refund_fields').find('#spanShippingBack').after($('#trustpayments_refund_offline_span_total'));
            $('.standard_refund_fields').find('#generateDiscount').off('click.trustpayments').on('click.trustpayments', trustPaymentsTotalRefundChanges);
            $('#trustpayments_refund_offline_cb_total').on('click.trustpayments', trustPaymentsTotalRefundChanges);
        
            $('#refund_3').closest('div').remove();
            $('.partial_refund_fields').find('button').before($('#trustpayments_refund_online_text_partial'));
            $('.partial_refund_fields').find('button').before($('#trustpayments_refund_offline_text_partial'));
            $('.partial_refund_fields').find('button').before($('#trustpayments_refund_no_text_partial'));
            $('.partial_refund_fields').find('#generateDiscountRefund').closest('p').after($('#trustpayments_refund_offline_span_partial'));
            $('.partial_refund_fields').find('#generateDiscountRefund').off('click.trustpayments').on('click.trustpayments', trustPaymentsPartialRefundChanges);
            $('#trustpayments_refund_offline_cb_partial').on('click.trustpayments', trustPaymentsPartialRefundChanges);
        }
        if ($('#trustpayments_completion_pending').length > 0) {
            $('#add_product').remove();
            $('#add_voucher').remove();
            $(".edit_product_change_link").closest('div').remove();
            $('#desc-order-partial_refund').remove();
            $('#desc-order-standard_refund').remove();
        }
        if ($('#trustpayments_void_pending').length > 0) {
            $('#add_product').remove();
            $('#add_voucher').remove();
            $(".edit_product_change_link").closest('div').remove();
            $('#desc-order-partial_refund').remove();
            $('#desc-order-standard_refund').remove();
        }
        if ($('#trustpayments_refund_pending').length > 0) {
            $('#desc-order-standard_refund').remove();
            $('#desc-order-partial_refund').remove();
        }
        moveTrustPaymentsDocuments();
        moveTrustPaymentsActionsAndInfo();
    }
    
    function init()
    {
        handleTrustPaymentsLayoutChanges();
        registerTrustPaymentsActions();
    }
    
    init();
});
