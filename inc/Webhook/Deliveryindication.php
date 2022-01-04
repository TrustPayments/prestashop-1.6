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
 * Webhook processor to handle delivery indication state transitions.
 */
class TrustPaymentsWebhookDeliveryindication extends TrustPaymentsWebhookOrderrelatedabstract
{

    /**
     *
     * @see TrustPaymentsWebhookOrderrelatedabstract::loadEntity()
     * @return \TrustPayments\Sdk\Model\DeliveryIndication
     */
    protected function loadEntity(TrustPaymentsWebhookRequest $request)
    {
        $deliveryIndicationService = new \TrustPayments\Sdk\Service\DeliveryIndicationService(
            TrustPaymentsHelper::getApiClient()
        );
        return $deliveryIndicationService->read($request->getSpaceId(), $request->getEntityId());
    }

    protected function getOrderId($deliveryIndication)
    {
        /* @var \TrustPayments\Sdk\Model\DeliveryIndication $deliveryIndication */
        return $deliveryIndication->getTransaction()->getMerchantReference();
    }

    protected function getTransactionId($deliveryIndication)
    {
        /* @var \TrustPayments\Sdk\Model\DeliveryIndication $delivery_indication */
        return $deliveryIndication->getLinkedTransaction();
    }

    protected function processOrderRelatedInner(Order $order, $deliveryIndication)
    {
        /* @var \TrustPayments\Sdk\Model\DeliveryIndication $deliveryIndication */
        switch ($deliveryIndication->getState()) {
            case \TrustPayments\Sdk\Model\DeliveryIndicationState::MANUAL_CHECK_REQUIRED:
                $this->review($order);
                break;
            default:
                break;
        }
    }

    protected function review(Order $sourceOrder)
    {
        TrustPaymentsBasemodule::startRecordingMailMessages();
        $manualStatusId = Configuration::get(TrustPaymentsBasemodule::CK_STATUS_MANUAL);
        TrustPaymentsHelper::updateOrderMeta($sourceOrder, 'manual_check', true);
        $orders = $sourceOrder->getBrother();
        $orders[] = $sourceOrder;
        foreach ($orders as $order) {
            $order->setCurrentState($manualStatusId);
            $order->save();
        }
        TrustPaymentsBasemodule::stopRecordingMailMessages();
    }
}
