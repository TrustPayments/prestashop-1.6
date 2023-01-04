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
    
    function moveTrustPaymentsManualTasks()
    {
        $("#trustpayments_notifications").find("li").each(function (key, element) {
            $("#header_notifs_icon_wrapper").append(element);
        });
    }
    moveTrustPaymentsManualTasks();
    
});