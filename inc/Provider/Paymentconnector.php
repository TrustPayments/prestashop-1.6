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
 * Provider of payment connector information from the gateway.
 */
class TrustPaymentsProviderPaymentconnector extends TrustPaymentsProviderAbstract
{
    protected function __construct()
    {
        parent::__construct('trustpayments_connectors');
    }

    /**
     * Returns the payment connector by the given id.
     *
     * @param int $id
     * @return \TrustPayments\Sdk\Model\PaymentConnector
     */
    public function find($id)
    {
        return parent::find($id);
    }

    /**
     * Returns a list of payment connectors.
     *
     * @return \TrustPayments\Sdk\Model\PaymentConnector[]
     */
    public function getAll()
    {
        return parent::getAll();
    }

    protected function fetchData()
    {
        $connectorService = new \TrustPayments\Sdk\Service\PaymentConnectorService(
            TrustPaymentsHelper::getApiClient()
        );
        return $connectorService->all();
    }

    protected function getId($entry)
    {
        /* @var \TrustPayments\Sdk\Model\PaymentConnector $entry */
        return $entry->getId();
    }
}
