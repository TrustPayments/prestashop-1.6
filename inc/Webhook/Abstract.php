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
 * Abstract webhook processor.
 */
abstract class TrustPaymentsWebhookAbstract
{
    private static $instances = array();

    /**
     *
     * @return static
     */
    public static function instance()
    {
        $class = get_called_class();
        if (! isset(self::$instances[$class])) {
            self::$instances[$class] = new $class();
        }
        return self::$instances[$class];
    }

    /**
     * Processes the received webhook request.
     *
     * @param TrustPaymentsWebhookRequest $request
     */
    abstract public function process(TrustPaymentsWebhookRequest $request);
}
