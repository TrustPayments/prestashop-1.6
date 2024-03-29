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

    var trustpayments_checkout = {

        handlerCounter: 0,
        iframe_handler: null,
        configuration_id: null,
        height: null,
        element_processing_spinner_container: '#trustpayments-processing-spinner-container',
        processing: false,

        init : function () {
            $(this.element_processing_spinner_container).hide();
            this.handlerCounter = 0;
            var configuration = $('#trustpayments-method-configuration');
            this.configuration_id = configuration.data("configuration-id");
            this.register_method();
            this.register_button_handler();
        },

        register_method : function () {
            var self = this;
            if (typeof window.trustpaymentsIFrameCheckoutHandler === 'undefined') {
                if (this.handlerCounter > 20) {
                    $('.trustpayments-loader').remove();
                    this.enable_pay_button();
                    return;
                }
                this.handlerCounter++;
                setTimeout(function () {
                    self.register_method();
                }, 100);
                return;
            }

            if (this.iframe_handler != null) {
                return;
            }
            this.iframe_handler = window.trustpaymentsIFrameCheckoutHandler(this.configuration_id);
            this.iframe_handler.setValidationCallback(function (validation_result) {
                self.process_validation(validation_result);
            });
            this.iframe_handler .setHeightChangeCallback(function (height) {
                self.handle_iframe_height(height);
            });
            this.iframe_handler.setInitializeCallback(function () {
                $('#trustpayments-iframe-possible').remove();
                $('.trustpayments-loader').remove();
                $('html, body').animate(
                    {
                        scrollTop : ($('#trustpayments-method-container').offset().top - screen.height/8)
                        },
                    1000
                );
                self.enable_pay_button();
            });
            this.iframe_handler.create("trustpayments-method-container");
        },

        register_button_handler : function () {
            $('.trustpayments-submit').off('click.trustpayments').on('click.trustpayments', {
                self : this
                }, this.handler_submit);
        },


        handle_iframe_height : function (height) {
            var item = $('#trustpayments-submit-top');
            if (height === 0) {
                item.addClass('invisible');
                item.hide();
            } else {
                item.removeClass('invisible');
                item.show();
                $(this.element_processing_spinner_container).hide();
            }
        },


        handler_submit : function (event) {
            var self = event.data.self;
            if(!self.processing) {
                self.disable_pay_button();

                var tosInput = $('#cgv');
                if (tosInput.size() > 0) {
                    if (!tosInput.is(':checked')) {
                        self.remove_existing_errors();
                        self.show_new_errors(trustpayments_msg_tos_error);
                        self.enable_pay_button();
                        return false;
                    }
                }
                if (self.iframe_handler == null) {
                    self.process_validation({success: true});
                    return false;
                }
                self.iframe_handler.validate();
            }
            return false;
        },


        show_processing_spinner : function () {
            var spinnerContainer = $(this.element_processing_spinner_container);
            spinnerContainer.removeClass('invisible');
            spinnerContainer.show();
        },

        hide_processing_spinner: function () {
            var spinnerContainer = $(this.element_processing_spinner_container);
            spinnerContainer.addClass('invisible');
            spinnerContainer.hide();
        },


        process_validation : function (validation_result) {
            var self = this;
            if (validation_result.success) {
                self.show_processing_spinner();
                var form = $('#trustpayments-payment-form');
                $.ajax({
                    type:       'POST',
                    dataType:   'json',
                    url:        form.attr('action'),
                    data:       form.serialize(),
                    success:    function (response, textStatus, jqXHR) {
                        if ( response.result === 'success' ) {
                            self.iframe_handler.submit();
                            return;
                        } else if (response.result === 'redirect') {
                            location.replace(response.redirect);
                            return;
                        } else if ( response.result === 'failure' ) {
                            if (response.reload === 'true' ) {
                                location.reload();
                                self.hide_processing_spinner();
                                self.enable_pay_button();
                                return;
                            } else if (response.redirect) {
                                location.replace(response.redirect);
                                return;
                            }
                        }
                        self.remove_existing_errors();
                        self.show_new_errors(trustpayments_msg_json_error);
                        self.hide_processing_spinner();
                        self.enable_pay_button();
                    },
                    error:      function (jqXHR, textStatus, errorThrown) {
                        self.remove_existing_errors();
                        self.show_new_errors(trustpayments_msg_json_error);
                        self.hide_processing_spinner();
                        self.enable_pay_button();
                    }
                });
            } else {
                if (validation_result.errors) {
                    this.remove_existing_errors();
                    this.show_new_errors(this.format_error_messages(validation_result.errors));
                }

                this.enable_pay_button();
            }
        },

        disable_pay_button : function () {
            $('.trustpayments-submit').prop('disabled', true);
            this.processing = true;
        },

        enable_pay_button : function () {
            $('.trustpayments-submit').prop('disabled', false);
            this.processing = false;
        },

        remove_existing_errors : function () {
            var error_message_selector = $('#trustpayments-error-messages');
            error_message_selector.empty();
            error_message_selector.removeClass("alert alert-danger");
        },

        show_new_errors : function (message) {
            var error_message_selector = $('#trustpayments-error-messages');
            error_message_selector.addClass('alert alert-danger');
            error_message_selector.append('<div>'+message+'</div>');
            $('html, body').animate({
                scrollTop : (error_message_selector.offset().top - 20)
            }, 1000);
        },

        format_error_messages : function (messages) {
            var formatted_message;
            if (typeof messages == 'object') {
                formatted_message = messages.join("\n");
            } else if (messages.constructor === Array) {
                formatted_message = messages.join("\n").stripTags().toString();
            } else {
                formatted_message = messages
            }
            return formatted_message;
        }
    };
    trustpayments_checkout.init();

});
