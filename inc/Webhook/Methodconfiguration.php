<?php
/**
 * Trust Payments Prestashop
 *
 * This Prestashop module enables to process payments with Trust Payments (https://www.trustpayments.com/).
 *
 * @author customweb GmbH (http://www.customweb.com/)
 * @copyright 2017 - 2023 customweb GmbH
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache Software License (ASL 2.0)
 */

/**
 * Webhook processor to handle payment method configuration state transitions.
 */
class TrustPaymentsWebhookMethodconfiguration extends TrustPaymentsWebhookAbstract
{

    /**
     * Synchronizes the payment method configurations on state transition.
     *
     * @param TrustPaymentsWebhookRequest $request
     */
    public function process(TrustPaymentsWebhookRequest $request)
    {
        $paymentMethodConfigurationService = TrustPaymentsServiceMethodconfiguration::instance();
        $paymentMethodConfigurationService->synchronize();
    }
}
