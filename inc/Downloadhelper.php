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
 * This class provides function to download documents from Trust Payments
 */
class TrustPaymentsDownloadhelper
{

    /**
     * Downloads the transaction's invoice PDF document.
     */
    public static function downloadInvoice($order)
    {
        $transactionInfo = TrustPaymentsHelper::getTransactionInfoForOrder($order);
        if ($transactionInfo != null && in_array(
            $transactionInfo->getState(),
            array(
                \TrustPayments\Sdk\Model\TransactionState::COMPLETED,
                \TrustPayments\Sdk\Model\TransactionState::FULFILL,
                \TrustPayments\Sdk\Model\TransactionState::DECLINE
            )
        )) {
            $service = new \TrustPayments\Sdk\Service\TransactionService(
                TrustPaymentsHelper::getApiClient()
            );
            $document = $service->getInvoiceDocument(
                $transactionInfo->getSpaceId(),
                $transactionInfo->getTransactionId()
            );
            self::download($document);
        }
    }

    /**
     * Downloads the transaction's packing slip PDF document.
     */
    public static function downloadPackingSlip($order)
    {
        $transactionInfo = TrustPaymentsHelper::getTransactionInfoForOrder($order);
        if ($transactionInfo != null &&
            $transactionInfo->getState() == \TrustPayments\Sdk\Model\TransactionState::FULFILL) {
            $service = new \TrustPayments\Sdk\Service\TransactionService(
                TrustPaymentsHelper::getApiClient()
            );
            $document = $service->getPackingSlip($transactionInfo->getSpaceId(), $transactionInfo->getTransactionId());
            self::download($document);
        }
    }

    /**
     * Sends the data received by calling the given path to the browser and ends the execution of the script
     *
     * @param string $path
     */
    protected static function download(\TrustPayments\Sdk\Model\RenderedDocument $document)
    {
        header('Pragma: public');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Content-type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $document->getTitle() . '.pdf"');
        header('Content-Description: ' . $document->getTitle());
        echo TrustPaymentsTools::base64Decode($document->getData());
        exit();
    }
}
