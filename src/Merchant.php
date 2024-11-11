<?php
/**
 * @author Alona Tsarova
 */

declare(strict_types=1);

namespace MonoPay;

/**
 * List of methods which are accessible via __call() in case of invoice data is loaded
 * @method string getMerchantId()
 * @method string getMerchantName()
 * @method string getEdrpou()
 */
class Merchant extends DataObject
{
    /**
     * Load merchant
     * More info - https://api.monobank.ua/docs/acquiring.html#/paths/~1api~1merchant~1details/get
     *
     * @return $this
     * @throws Exception
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function load(): self
    {
        $response = $this->getClient()->getMerchant();
        if (!isset($response['merchantId']) || !isset($response['merchantName'])) {
            throw new \Exception('Merchant isn\'t available in Mono.', 500);
        }

        $this->updateData($response);

        return $this;
    }

    /**
     * Retrieve merchant statement list by date range
     * More info - https://api.monobank.ua/docs/acquiring.html#/paths/~1api~1merchant~1statement/get
     *
     * @param int $from
     * @param int|null $to
     * @param string|null $code
     * @return array
     * @throws Exception
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getStatement(int $from, ?int $to = null, ?string $code = null): array
    {
        $options = ['from' => $from];

        if ($to) {
            $options['to'] = $to;
        }

        if ($code) {
            $options['code'] = $code;
        }

        $response = $this->getClient()->getMerchantStatement($options);
        if (!isset($response['list'])) {
            throw new \Exception('Merchant statement isn\'t available on Mono.', 500);
        }

        return $response['list'];
    }

    /**
     * Retrieves merchant public key
     * More info - https://api.monobank.ua/docs/acquiring.html#/paths/~1api~1merchant~1pubkey/get
     *
     * @return string
     * @throws Exception
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getPublicKey(): string
    {
        $response = $this->getClient()->getPublicKey();
        if (!isset($response['key'])) {
            throw new \Exception('Public key isn\'t available in Mono.', 500);
        }

        return $response['key'];
    }
}
