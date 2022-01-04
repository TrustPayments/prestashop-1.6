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
 * Webhook processor to handle refund state transitions.
 */
class TrustPaymentsWebhookRefund extends TrustPaymentsWebhookOrderrelatedabstract
{

    /**
     * Processes the received order related webhook request.
     *
     * @param TrustPaymentsWebhookRequest $request
     */
    public function process(TrustPaymentsWebhookRequest $request)
    {
        parent::process($request);
        $refund = $this->loadEntity($request);
        $refundJob = TrustPaymentsModelRefundjob::loadByExternalId(
            $refund->getLinkedSpaceId(),
            $refund->getExternalId()
        );
        if ($refundJob->getState() == TrustPaymentsModelRefundjob::STATE_APPLY) {
            TrustPaymentsServiceRefund::instance()->applyRefundToShop($refundJob->getId());
        }
    }

    /**
     *
     * @see TrustPaymentsWebhookOrderrelatedabstract::loadEntity()
     * @return \TrustPayments\Sdk\Model\Refund
     */
    protected function loadEntity(TrustPaymentsWebhookRequest $request)
    {
        $refundService = new \TrustPayments\Sdk\Service\RefundService(
            TrustPaymentsHelper::getApiClient()
        );
        return $refundService->read($request->getSpaceId(), $request->getEntityId());
    }

    protected function getOrderId($refund)
    {
        /* @var \TrustPayments\Sdk\Model\Refund $refund */
        return $refund->getTransaction()->getMerchantReference();
    }

    protected function getTransactionId($refund)
    {
        /* @var \TrustPayments\Sdk\Model\Refund $refund */
        return $refund->getTransaction()->getId();
    }

    protected function processOrderRelatedInner(Order $order, $refund)
    {
        /* @var \TrustPayments\Sdk\Model\Refund $refund */
        switch ($refund->getState()) {
            case \TrustPayments\Sdk\Model\RefundState::FAILED:
                $this->failed($refund, $order);
                break;
            case \TrustPayments\Sdk\Model\RefundState::SUCCESSFUL:
                $this->refunded($refund, $order);
                break;
            default:
                // Nothing to do.
                break;
        }
    }

    protected function failed(\TrustPayments\Sdk\Model\Refund $refund, Order $order)
    {
        $refundJob = TrustPaymentsModelRefundjob::loadByExternalId(
            $refund->getLinkedSpaceId(),
            $refund->getExternalId()
        );
        if ($refundJob->getId()) {
            $refundJob->setState(TrustPaymentsModelRefundjob::STATE_FAILURE);
            $refundJob->setRefundId($refund->getId());
            if ($refund->getFailureReason() != null) {
                $refundJob->setFailureReason($refund->getFailureReason()
                    ->getDescription());
            }
            $refundJob->save();
        }
    }

    protected function refunded(\TrustPayments\Sdk\Model\Refund $refund, Order $order)
    {
        $refundJob = TrustPaymentsModelRefundjob::loadByExternalId(
            $refund->getLinkedSpaceId(),
            $refund->getExternalId()
        );
        if ($refundJob->getId()) {
            $refundJob->setState(TrustPaymentsModelRefundjob::STATE_APPLY);
            $refundJob->setRefundId($refund->getId());
            $refundJob->save();
        }
    }
}
