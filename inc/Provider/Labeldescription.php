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
 * Provider of label descriptor information from the gateway.
 */
class TrustPaymentsProviderLabeldescription extends TrustPaymentsProviderAbstract
{
    protected function __construct()
    {
        parent::__construct('trustpayments_label_description');
    }

    /**
     * Returns the label descriptor by the given code.
     *
     * @param int $id
     * @return \TrustPayments\Sdk\Model\LabelDescriptor
     */
    public function find($id)
    {
        return parent::find($id);
    }

    /**
     * Returns a list of label descriptors.
     *
     * @return \TrustPayments\Sdk\Model\LabelDescriptor[]
     */
    public function getAll()
    {
        return parent::getAll();
    }

    protected function fetchData()
    {
        $labelDescriptorService = new \TrustPayments\Sdk\Service\LabelDescriptionService(
            TrustPaymentsHelper::getApiClient()
        );
        return $labelDescriptorService->all();
    }

    protected function getId($entry)
    {
        /* @var \TrustPayments\Sdk\Model\LabelDescriptor $entry */
        return $entry->getId();
    }
}
