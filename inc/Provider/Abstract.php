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
 * Abstract implementation of a provider.
 */
abstract class TrustPaymentsProviderAbstract
{
    private static $instances = array();

    private $cacheKey;

    private $data;

    /**
     * Constructor.
     *
     * @param string $cache_key
     */
    protected function __construct($cacheKey)
    {
        $this->cacheKey = $cacheKey;
    }

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
     * Fetch the data from the remote server.
     *
     * @return array
     */
    abstract protected function fetchData();

    /**
     * Returns the id of the given entry.
     *
     * @param mixed $entry
     * @return string
     */
    abstract protected function getId($entry);

    /**
     * Returns a single entry by id.
     *
     * @param string $id
     * @return mixed
     */
    public function find($id)
    {
        if ($this->data == null) {
            $this->loadData();
        }

        if (isset($this->data[$id])) {
            return $this->data[$id];
        } else {
            return false;
        }
    }

    /**
     * Returns all entries.
     *
     * @return array
     */
    public function getAll()
    {
        if ($this->data == null) {
            $this->loadData();
        }
        if (! is_array($this->data)) {
            return array();
        }
        return $this->data;
    }

    private function loadData()
    {
        $cachedData = Cache::retrieve($this->cacheKey);
        if ($cachedData !== null) {
            $decoded = TrustPaymentsTools::base64Decode($cachedData, true);
            if ($decoded === false) {
                $decoded = $cachedData;
            }
            $deserialized = unserialize($decoded);
            if (is_array($deserialized)) {
                $this->data = $deserialized;
                return;
            }
        }

        $this->data = array();
        try {
            foreach ($this->fetchData() as $entry) {
                $this->data[$this->getId($entry)] = $entry;
            }
            Cache::store($this->cacheKey, TrustPaymentsTools::base64Encode(serialize($this->data)));
        } catch (\TrustPayments\Sdk\ApiException $e) {
        } catch (\TrustPayments\Sdk\Http\ConnectionException $e) {
        }
    }
}
