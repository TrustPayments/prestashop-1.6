<?php
/**
 * Trust Payments Prestashop
 *
 * This Prestashop module enables to process payments with Trust Payments (https://www.trustpayments.com/).
 *
 * @author customweb GmbH (http://www.customweb.com/)
 * @copyright 2017 - 2022 customweb GmbH
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache Software License (ASL 2.0)
 */

/**
 * Webhook processor to handle manual task state transitions.
 */
class TrustPaymentsWebhookManualtask extends TrustPaymentsWebhookAbstract
{

    /**
     * Updates the number of open manual tasks.
     *
     * @param TrustPaymentsWebhookRequest $request
     */
    public function process(TrustPaymentsWebhookRequest $request)
    {
        $manualTaskService = TrustPaymentsServiceManualtask::instance();
        $manualTaskService->update();
    }
}
