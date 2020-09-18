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
 * Webhook processor to handle transaction state transitions.
 */
class TrustPaymentsWebhookTransaction extends TrustPaymentsWebhookOrderrelatedabstract
{

    /**
     *
     * @see TrustPaymentsWebhookOrderrelatedabstract::loadEntity()
     * @return \TrustPayments\Sdk\Model\Transaction
     */
    protected function loadEntity(TrustPaymentsWebhookRequest $request)
    {
        $transactionService = new \TrustPayments\Sdk\Service\TransactionService(
            TrustPaymentsHelper::getApiClient()
        );
        return $transactionService->read($request->getSpaceId(), $request->getEntityId());
    }

    protected function getOrderId($transaction)
    {
        /* @var \TrustPayments\Sdk\Model\Transaction $transaction */
        return $transaction->getMerchantReference();
    }

    protected function getTransactionId($transaction)
    {
        /* @var \TrustPayments\Sdk\Model\Transaction $transaction */
        return $transaction->getId();
    }

    protected function processOrderRelatedInner(Order $order, $transaction)
    {
        /* @var \TrustPayments\Sdk\Model\Transaction $transaction */
        $transactionInfo = TrustPaymentsModelTransactioninfo::loadByOrderId($order->id);
        if ($transaction->getState() != $transactionInfo->getState()) {
            switch ($transaction->getState()) {
                case \TrustPayments\Sdk\Model\TransactionState::AUTHORIZED:
                    $this->authorize($transaction, $order);
                    break;
                case \TrustPayments\Sdk\Model\TransactionState::DECLINE:
                    $this->decline($transaction, $order);
                    break;
                case \TrustPayments\Sdk\Model\TransactionState::FAILED:
                    $this->failed($transaction, $order);
                    break;
                case \TrustPayments\Sdk\Model\TransactionState::FULFILL:
                    $this->authorize($transaction, $order);
                    $this->fulfill($transaction, $order);
                    break;
                case \TrustPayments\Sdk\Model\TransactionState::VOIDED:
                    $this->voided($transaction, $order);
                    break;
                case \TrustPayments\Sdk\Model\TransactionState::COMPLETED:
                    $this->waiting($transaction, $order);
                    break;
                default:
                    // Nothing to do.
                    break;
            }
        }
    }

    protected function authorize(\TrustPayments\Sdk\Model\Transaction $transaction, Order $sourceOrder)
    {
        if (TrustPaymentsHelper::getOrderMeta($sourceOrder, 'authorized')) {
            return;
        }
        // Do not send emails for this status update
        TrustPaymentsBasemodule::startRecordingMailMessages();
        TrustPaymentsHelper::updateOrderMeta($sourceOrder, 'authorized', true);
        $authorizedStatusId = Configuration::get(TrustPaymentsBasemodule::CK_STATUS_AUTHORIZED);
        $orders = $sourceOrder->getBrother();
        $orders[] = $sourceOrder;
        foreach ($orders as $order) {
            $order->setCurrentState($authorizedStatusId);
            $order->save();
        }
        TrustPaymentsBasemodule::stopRecordingMailMessages();
        if (Configuration::get(TrustPaymentsBasemodule::CK_MAIL, null, null, $sourceOrder->id_shop)) {
            // Send stored messages
            $messages = TrustPaymentsHelper::getOrderEmails($sourceOrder);
            if (count($messages) > 0) {
                if (method_exists('Mail', 'sendMailMessageWithoutHook')) {
                    foreach ($messages as $message) {
                        Mail::sendMailMessageWithoutHook($message, false);
                    }
                }
            }
        }
        TrustPaymentsHelper::deleteOrderEmails($order);
        // Cleanup carts
        $originalCartId = TrustPaymentsHelper::getOrderMeta($order, 'originalCart');
        if (! empty($originalCartId)) {
            $cart = new Cart($originalCartId);
            $cart->delete();
        }
        TrustPaymentsServiceTransaction::instance()->updateTransactionInfo($transaction, $sourceOrder);
    }

    protected function waiting(\TrustPayments\Sdk\Model\Transaction $transaction, Order $sourceOrder)
    {
        TrustPaymentsBasemodule::startRecordingMailMessages();
        $waitingStatusId = Configuration::get(TrustPaymentsBasemodule::CK_STATUS_COMPLETED);
        if (! TrustPaymentsHelper::getOrderMeta($sourceOrder, 'manual_check')) {
            $orders = $sourceOrder->getBrother();
            $orders[] = $sourceOrder;
            foreach ($orders as $order) {
                $order->setCurrentState($waitingStatusId);
                $order->save();
            }
        }
        TrustPaymentsBasemodule::stopRecordingMailMessages();
        TrustPaymentsServiceTransaction::instance()->updateTransactionInfo($transaction, $sourceOrder);
    }

    protected function decline(\TrustPayments\Sdk\Model\Transaction $transaction, Order $sourceOrder)
    {
        if (! Configuration::get(TrustPaymentsBasemodule::CK_MAIL, null, null, $sourceOrder->id_shop)) {
            // Do not send email
            TrustPaymentsBasemodule::startRecordingMailMessages();
        }

        $canceledStatusId = Configuration::get(TrustPaymentsBasemodule::CK_STATUS_DECLINED);
        $orders = $sourceOrder->getBrother();
        $orders[] = $sourceOrder;
        foreach ($orders as $order) {
            $order->setCurrentState($canceledStatusId);
            $order->save();
        }
        TrustPaymentsBasemodule::stopRecordingMailMessages();
        TrustPaymentsServiceTransaction::instance()->updateTransactionInfo($transaction, $sourceOrder);
    }

    protected function failed(\TrustPayments\Sdk\Model\Transaction $transaction, Order $sourceOrder)
    {
        // Do not send email
        TrustPaymentsBasemodule::startRecordingMailMessages();
        $errorStatusId = Configuration::get(TrustPaymentsBasemodule::CK_STATUS_FAILED);
        $orders = $sourceOrder->getBrother();
        $orders[] = $sourceOrder;
        foreach ($orders as $order) {
            $order->setCurrentState($errorStatusId);
            $order->save();
        }
        TrustPaymentsBasemodule::stopRecordingMailMessages();
        TrustPaymentsHelper::deleteOrderEmails($sourceOrder);
        TrustPaymentsServiceTransaction::instance()->updateTransactionInfo($transaction, $sourceOrder);
    }

    protected function fulfill(\TrustPayments\Sdk\Model\Transaction $transaction, Order $sourceOrder)
    {
        if (! Configuration::get(TrustPaymentsBasemodule::CK_MAIL, null, null, $sourceOrder->id_shop)) {
            // Do not send email
            TrustPaymentsBasemodule::startRecordingMailMessages();
        }
        $payedStatusId = Configuration::get(TrustPaymentsBasemodule::CK_STATUS_FULFILL);
        $orders = $sourceOrder->getBrother();
        $orders[] = $sourceOrder;
        foreach ($orders as $order) {
            $order->setCurrentState($payedStatusId);
            if (empty($order->invoice_date) || $order->invoice_date == '0000-00-00 00:00:00') {
                // Make sure invoice date is set, otherwise prestashop ignores the order in the statistics
                $order->invoice_date = date('Y-m-d H:i:s');
            }
            $order->save();
        }
        TrustPaymentsBasemodule::stopRecordingMailMessages();
        TrustPaymentsServiceTransaction::instance()->updateTransactionInfo($transaction, $sourceOrder);
    }

    protected function voided(\TrustPayments\Sdk\Model\Transaction $transaction, Order $sourceOrder)
    {
        if (! Configuration::get(TrustPaymentsBasemodule::CK_MAIL, null, null, $sourceOrder->id_shop)) {
            // Do not send email
            TrustPaymentsBasemodule::startRecordingMailMessages();
        }
        $canceledStatusId = Configuration::get(TrustPaymentsBasemodule::CK_STATUS_VOIDED);
        $orders = $sourceOrder->getBrother();
        $orders[] = $sourceOrder;
        foreach ($orders as $order) {
            $order->setCurrentState($canceledStatusId);
            $order->save();
        }
        TrustPaymentsBasemodule::stopRecordingMailMessages();
        TrustPaymentsServiceTransaction::instance()->updateTransactionInfo($transaction, $sourceOrder);
    }
}
