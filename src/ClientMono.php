<?php
/**
 * @author Alona Tsarova
 */

declare(strict_types=1);

namespace MonoPay;

use GuzzleHttp\RequestOptions;
use GuzzleHttp\Client as HttpClient;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Exception\GuzzleException;

class ClientMono
{
    public const TIMEOUT = 10;
    public const API_ENDPOINT = 'https://api.monobank.ua/';
    public const MERCHANT_DETAILS_URI = 'api/merchant/details';
    public const MERCHANT_PUBKEY_URI = 'api/merchant/pubkey';
    public const MERCHANT_STATEMENT_URI = 'api/merchant/statement';
    public const CREATE_INVOICE_URI = 'api/merchant/invoice/create';
    public const INVOICE_STATUS_URI = 'api/merchant/invoice/status';
    public const INVOICE_CANCEL_URI = 'api/merchant/invoice/cancel';
    public const INVOICE_REMOVE_URI = 'api/merchant/invoice/remove';
    public const INVOICE_RECEIPT_URI = 'api/merchant/invoice/receipt';
    public const INVOICE_FINALIZE_URI = 'api/merchant/invoice/finalize';
    public const INVOICE_FISCAL_CHECKS_URI = 'api/merchant/invoice/fiscal-checks';
    public const INVOICE_PAYMENT_DIRECT_URI = 'api/merchant/invoice/payment-direct';

    private HttpClient $httpClient;

    /**
     * @param string $token
     * @param string|null $platform
     * @param string|null $platformVersion
     * @throws GuzzleException
     */
    public function __construct(string $token, ?string $platform = null, ?string $platformVersion = null)
    {
        $headers['X-Token'] = $token;

        if ($platform) {
            $headers['X-Cms'] = $platform;
        }

        if ($platformVersion) {
            $headers['X-Cms-Version'] = $platformVersion;
        }

        $this->httpClient = new HttpClient([
            'base_uri' => self::API_ENDPOINT,
            RequestOptions::TIMEOUT => self::TIMEOUT,
            RequestOptions::HEADERS => $headers,
            RequestOptions::HTTP_ERRORS => false,
        ]);
    }

    /**
     * @return HttpClient
     */
    private function getClient(): \GuzzleHttp\Client
    {
        return $this->httpClient;
    }

    /**
     * @return array
     * @throws Exception
     * @throws GuzzleException
     */
    public function getPublicKey(): array
    {
        $response = $this->getClient()->request('GET', self::MERCHANT_PUBKEY_URI);

        return $this->getResponse($response);
    }

    /**
     * @return array
     * @throws Exception
     * @throws GuzzleException
     */
    public function getMerchant(): array
    {
        $response = $this->getClient()->request('GET', self::MERCHANT_DETAILS_URI);

        return $this->getResponse($response);
    }

    /**
     * @param array $options
     * @return array
     * @throws Exception
     * @throws GuzzleException
     */
    public function getMerchantStatement(array $options): array
    {
        $response = $this->getClient()->request('GET', self::MERCHANT_STATEMENT_URI, [
            RequestOptions::QUERY => $options
        ]);

        return $this->getResponse($response);
    }

    /**
     * @param array $options
     * @return array
     * @throws Exception
     * @throws GuzzleException
     */
    public function createInvoice(array $options = []): array
    {
        $response = $this->getClient()->request('POST', self::CREATE_INVOICE_URI, [
            RequestOptions::JSON => $options
        ]);

        return $this->getResponse($response);
    }

    /**
     * @param string $invoiceId
     * @return array
     * @throws Exception
     * @throws GuzzleException
     */
    public function invoiceStatus(string $invoiceId): array
    {
        $response = $this->getClient()->request('GET', self::INVOICE_STATUS_URI, [
            RequestOptions::QUERY => [
                'invoiceId' => $invoiceId
            ]
        ]);

        return $this->getResponse($response);
    }

    /**
     * @param string $invoiceId
     * @return array
     * @throws Exception
     * @throws GuzzleException
     */
    public function invoiceCancel(string $invoiceId): array
    {
        $response = $this->getClient()->request('POST', self::INVOICE_CANCEL_URI, [
            RequestOptions::JSON => [
                'invoiceId' => $invoiceId
            ]
        ]);

        return $this->getResponse($response);
    }

    /**
     * @param string $invoiceId
     * @return array
     * @throws Exception
     * @throws GuzzleException
     */
    public function invoiceRemove(string $invoiceId): array
    {
        $response = $this->getClient()->request('POST', self::INVOICE_REMOVE_URI, [
            RequestOptions::JSON => [
                'invoiceId' => $invoiceId
            ]
        ]);

        return $this->getResponse($response);
    }

    /**
     * @param array $options
     * @return array
     * @throws Exception
     * @throws GuzzleException
     */
    public function captureInvoice(array $options): array
    {
        $response = $this->getClient()->request('POST', self::INVOICE_FINALIZE_URI, [
            RequestOptions::JSON => $options
        ]);

        return $this->getResponse($response);
    }

    /**
     * @param string $invoiceId
     * @return array
     * @throws Exception
     * @throws GuzzleException
     */
    public function invoiceReceipt(string $invoiceId): array
    {
        $response = $this->getClient()->request('GET', self::INVOICE_RECEIPT_URI, [
            RequestOptions::QUERY => [
                'invoiceId' => $invoiceId,
                'email' => 'tsareva.as@gmail.com'
            ]
        ]);

        return $this->getResponse($response);
    }

    /**
     * @param string $invoiceId
     * @return array
     * @throws Exception
     * @throws GuzzleException
     */
    public function invoiceFiscalChecks(string $invoiceId): array
    {
        $response = $this->getClient()->request('GET', self::INVOICE_FISCAL_CHECKS_URI, [
            RequestOptions::QUERY => [
                'invoiceId' => $invoiceId
            ]
        ]);

        return $this->getResponse($response);
    }

    /**
     * @param array $options
     * @return array
     * @throws Exception
     * @throws GuzzleException
     */
    public function invoicePaymentDirect(array $options = []): array
    {
        $response = $this->getClient()->request('POST', self::INVOICE_PAYMENT_DIRECT_URI, [
            RequestOptions::JSON => $options
        ]);

        return $this->getResponse($response);
    }

    protected function getResponse(ResponseInterface $response): array
    {
        $json = $response->getBody()->getContents();
        if (!$json && $response->getStatusCode() === 200) {
            return [];
        }

        $data = json_decode($json, true);
        if (!$data) {
            throw new \Exception('Cannot decode json response from Mono: ' . $json, 500);
        }

        if ($response->getStatusCode() == '200') {
            return $data;
        } elseif (isset($data['errorDescription'])) {
            throw new \Exception($data['errorDescription'], $response->getStatusCode());
        } elseif (isset($data['errCode']) && isset($data['errText'])) {
            throw new \Exception('Error: ' . $data['errText'] . '. Error code: ' . $data['errCode'], $response->getStatusCode());
        } else {
            throw new \Exception('Unknown error response: ' . $json, $response->getStatusCode());
        }
    }
}