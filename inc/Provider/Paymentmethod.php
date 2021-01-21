<?php
/**
 * Trust Payments Prestashop
 *
 * This Prestashop module enables to process payments with Trust Payments (https://www.trustpayments.com/).
 *
 * @author customweb GmbH (http://www.customweb.com/)
 * @copyright 2017 - 2021 customweb GmbH
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache Software License (ASL 2.0)
 */

/**
 * Provider of payment method information from the gateway.
 */
class TrustPaymentsProviderPaymentmethod extends TrustPaymentsProviderAbstract
{
    protected function __construct()
    {
        parent::__construct('trustpayments_methods');
    }

    /**
     * Returns the payment method by the given id.
     *
     * @param int $id
     * @return \TrustPayments\Sdk\Model\PaymentMethod
     */
    public function find($id)
    {
        return parent::find($id);
    }

    /**
     * Returns a list of payment methods.
     *
     * @return \TrustPayments\Sdk\Model\PaymentMethod[]
     */
    public function getAll()
    {
        return parent::getAll();
    }

    protected function fetchData()
    {
        $methodService = new \TrustPayments\Sdk\Service\PaymentMethodService(
            TrustPaymentsHelper::getApiClient()
        );
        return $methodService->all();
    }

    protected function getId($entry)
    {
        /* @var \TrustPayments\Sdk\Model\PaymentMethod $entry */
        return $entry->getId();
    }
}
