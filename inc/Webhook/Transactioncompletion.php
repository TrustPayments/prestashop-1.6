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
 * Webhook processor to handle transaction completion state transitions.
 */
class TrustPaymentsWebhookTransactioncompletion extends TrustPaymentsWebhookOrderrelatedabstract
{

    /**
     *
     * @see TrustPaymentsWebhookOrderrelatedabstract::loadEntity()
     * @return \TrustPayments\Sdk\Model\TransactionCompletion
     */
    protected function loadEntity(TrustPaymentsWebhookRequest $request)
    {
        $completionService = new \TrustPayments\Sdk\Service\TransactionCompletionService(
            TrustPaymentsHelper::getApiClient()
        );
        return $completionService->read($request->getSpaceId(), $request->getEntityId());
    }

    protected function getOrderId($completion)
    {
        /* @var \TrustPayments\Sdk\Model\TransactionCompletion $completion */
        return $completion->getLineItemVersion()
            ->getTransaction()
            ->getMerchantReference();
    }

    protected function getTransactionId($completion)
    {
        /* @var \TrustPayments\Sdk\Model\TransactionCompletion $completion */
        return $completion->getLinkedTransaction();
    }

    protected function processOrderRelatedInner(Order $order, $completion)
    {
        /* @var \TrustPayments\Sdk\Model\TransactionCompletion $completion */
        switch ($completion->getState()) {
            case \TrustPayments\Sdk\Model\TransactionCompletionState::FAILED:
                $this->update($completion, $order, false);
                break;
            case \TrustPayments\Sdk\Model\TransactionCompletionState::SUCCESSFUL:
                $this->update($completion, $order, true);
                break;
            default:
                // Nothing to do.
                break;
        }
    }

    protected function update(\TrustPayments\Sdk\Model\TransactionCompletion $completion, Order $order, $success)
    {
        $completionJob = TrustPaymentsModelCompletionjob::loadByCompletionId(
            $completion->getLinkedSpaceId(),
            $completion->getId()
        );
        if (! $completionJob->getId()) {
            // We have no completion job with this id -> the server could not store the id of the completion after
            // sending the request. (e.g. connection issue or crash)
            // We only have on running completion which was not yet processed successfully and use it as it should be
            // the one the webhook is for.

            $completionJob = TrustPaymentsModelCompletionjob::loadRunningCompletionForTransaction(
                $completion->getLinkedSpaceId(),
                $completion->getLinkedTransaction()
            );
            if (! $completionJob->getId()) {
                return;
            }
            $completionJob->setCompletionId($completion->getId());
        }

        if ($success) {
            $completionJob->setState(TrustPaymentsModelCompletionjob::STATE_SUCCESS);
        } else {
            if ($completion->getFailureReason() != null) {
                $completionJob->setFailureReason($completion->getFailureReason()
                    ->getDescription());
            }
            $completionJob->setState(TrustPaymentsModelCompletionjob::STATE_FAILURE);
        }
        $completionJob->save();
    }
}
