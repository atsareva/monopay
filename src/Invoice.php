<?php
/**
 * @author Alona Tsarova
 */

declare(strict_types=1);

namespace MonoPay;

/**
 * List of methods which are accessible via __call() in case of invoice data is loaded
 * @method string getInvoiceId()
 * @method string getStatus()
 * @method string getPageUrl()
 * @method string setPageUrl(string $url)
 * @method string getFailureReason()
 * @method string getErrCode()
 * @method int getAmount()
 * @method int getCcy()
 * @method int getFinalAmount()
 * @method string getCreatedDate()
 * @method string getModifiedDate()
 * @method string getReference()
 * @method string getDestination()
 * @method array getCancelList()
 * @method object getPaymentInfo()
 * @method object getWalletData()
 * @method object getTipsInfo()
 */
class Invoice extends DataObject
{

    /**
     * @param string $id
     * @return $this
     */
    public function setInvoiceId(string $id): self
    {
        $this->data['invoiceId'] = $id;

        return $this;
    }

    /**
     * Create invoice via mono api
     * More info - https://api.monobank.ua/docs/acquiring.html#/paths/~1api~1merchant~1invoice~1create/post
     *
     * @param array $options
     * @return $this
     * @throws Exception
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function create(array $options): self
    {
        $this->validateAmount($options);

        $response = $this->getClient()->createInvoice($options);
        /**
         * pageUrl is the payment url that have to be provided when invoice is successfully created.
         * pageUrl is accessible via getPageUrl() method
         */
        if (!isset($response['invoiceId']) || !isset($response['pageUrl'])) {
            throw new \Exception('Invoice can\'t be created on Mono.', 500);
        }

        $this->updateData($response);

        return $this;
    }

    /**
     * Get invoice details and status updates
     * More info - https://api.monobank.ua/docs/acquiring.html#/paths/~1api~1merchant~1invoice~1status?invoiceId={invoiceId}/get
     *
     * @return $this
     * @throws Exception
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function loadInfo(): self
    {
        $response = $this->getClient()->invoiceStatus($this->getInvoiceId());
        if (!isset($response['invoiceId']) || !isset($response['status'])) {
            throw new \Exception('Invoice can\'t be found on Mono.', 500);
        }

        $this->updateData($response);

        return $this;
    }

    /**
     * Refund invoice in case of successful payment
     * More info - https://api.monobank.ua/docs/acquiring.html#/paths/~1api~1merchant~1invoice~1cancel/post
     *
     * @return $this
     * @throws Exception
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function refund(): self
    {
        $response = $this->getClient()->invoiceCancel($this->getInvoiceId());
        if (!isset($response['status']) && $response['status'] !== 'success') {
            throw new \Exception('Invoice can\'t be canceled on Mono.', 500);
        }

        $this->updateData($response);

        return $this;
    }

    /**
     * Invoice would be canceled but ony in case there was no payment
     * More info - https://api.monobank.ua/docs/acquiring.html#/paths/~1api~1merchant~1invoice~1remove/post
     *
     * @return $this
     * @throws Exception
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function cancel(): self
    {
        $this->getClient()->invoiceRemove($this->getInvoiceId());

        return $this;
    }

    /**
     * Capture invoice but only in case money was on hold
     * More info - https://api.monobank.ua/docs/acquiring.html#/paths/~1api~1merchant~1invoice~1finalize/post
     *
     * @param int|null $amount
     * @param array $items
     * @return $this
     * @throws Exception
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function capture(?int $amount = null, array $items = []): self
    {
        /**
         * Mono api says that amount isn't required however it returns error if it isn't passed.
         */
        $this->loadInfo();
        if ($amount === null) {
            $amount = $this->getAmount();
        }

        if ($items) {
            $options['items'] = $items;
        }

        $options['amount'] = $amount;
        $options['invoiceId'] = $this->getInvoiceId();

        $response = $this->getClient()->captureInvoice($options);

        if (isset($response['status']) && $response['status'] !== 'success') {
            throw new \Exception('Invoice can\'t be captured. Current invoice status is ' . $this->getStatus() . '.', 500);
        }

        return $this;
    }

    /**
     * Retrieve invoice receipt file in base64
     * More info - https://api.monobank.ua/docs/acquiring.html#/paths/~1api~1merchant~1invoice~1receipt?invoiceId={invoiceId}/get
     *
     * @return string
     * @throws Exception
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getReceipt(): string
    {
        $response = $this->getClient()->invoiceReceipt($this->getInvoiceId());
        if (!isset($response['file'])) {
            throw new \Exception('Invoice receipt can\'t be loaded.', 500);
        }

        return $response['file'];
    }

    /**
     * Retrieve invoice fiscal checks
     * More info - https://api.monobank.ua/docs/acquiring.html#/paths/~1api~1merchant~1invoice~1fiscal-checks?invoiceId={invoiceId}/get
     *
     * @return array
     * @throws Exception
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getFiscalChecks(): array
    {
        $response = $this->getClient()->invoiceFiscalChecks($this->getInvoiceId());
        if (!isset($response['checks'])) {
            throw new \Exception('Invoice fiscal checks can\'t be loaded.', 500);
        }

        return $response['checks'];
    }

    /**
     * Create direct payment. Works only in case of available required certificates.
     * More info - https://api.monobank.ua/docs/acquiring.html#/paths/~1api~1merchant~1invoice~1payment-direct/post
     *
     * @param array $options
     * @return $this
     * @throws Exception
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function directPayment(array $options): self
    {
        $this->validateAmount($options);
        $this->validateCardData($options);

        $response = $this->getClient()->invoicePaymentDirect($options);
        if (!isset($response['invoiceId']) || !isset($response['status'])) {
            throw new \Exception('Invoice can\'t be payed on Mono.', 500);
        }

        $this->updateData($response);

        if (isset($response['tdsUrl'])) {
            $this->setPageUrl($response['tdsUrl']);
        }

        return $this;
    }

    /**
     * @param array $data
     * @return self
     * @throws Exception
     */
    private function validateAmount(array $data): self
    {
        if (!isset($data['amount'])) {
            throw new \Exception('Amount is a required value', 500);
        }

        if ($data['amount'] < 1) {
            throw new \Exception('Amount must be a natural number', 500);
        }

        return $this;
    }

    /**
     * @param array $data
     * @return self
     * @throws Exception
     */
    private function validateCardData(array $data): self
    {
        if (!isset($data['cardData'])) {
            throw new \Exception('Card data is required. It should contain card number, expiration date and cvv.', 500);
        }

        if (!isset($data['cardData']['pan'])) {
            throw new \Exception('Card number is a required value.', 500);
        }

        if (!isset($data['cardData']['exp'])) {
            throw new \Exception('Expiration date is a required value.', 500);
        }

        if (!isset($data['cardData']['cvv'])) {
            throw new \Exception('CVV is a required value.', 500);
        }

        return $this;
    }
}
