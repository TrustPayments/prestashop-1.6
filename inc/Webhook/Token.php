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
 * Webhook processor to handle token state transitions.
 */
class TrustPaymentsWebhookToken extends TrustPaymentsWebhookAbstract
{
    public function process(TrustPaymentsWebhookRequest $request)
    {
        $tokenService = TrustPaymentsServiceToken::instance();
        $tokenService->updateToken($request->getSpaceId(), $request->getEntityId());
    }
}
