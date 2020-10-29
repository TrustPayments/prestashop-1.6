<?php
/**
 * Trust Payments Prestashop
 *
 * This Prestashop module enables to process payments with Trust Payments (https://www.trustpayments.com/).
 *
 * @author customweb GmbH (http://www.customweb.com/)
 * @copyright 2017 - 2020 customweb GmbH
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache Software License (ASL 2.0)
 */

/**
 * This provider allows to create a TrustPayments_ShopRefund_IStrategy.
 * The implementation of
 * the strategy depends on the actual prestashop version.
 */
class TrustPaymentsBackendStrategyprovider
{

    /**
     * Returns the refund strategy to use
     *
     * @return TrustPaymentsBackendIstrategy
     */
    public static function getStrategy()
    {
        return new TrustPaymentsBackendDefaultstrategy();
    }
}