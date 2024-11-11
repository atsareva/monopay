<?php
/**
 * @author Alona Tsarova
 */

declare(strict_types=1);

namespace MonoPay;

class DataObject
{
    protected array $data;

    protected ?ClientMono $client = null;

    /**
     * Update invoice data to have access for invoice params from object
     *
     * @param array $data
     * @return void
     */
    protected function updateData(array $data): void
    {
        $this->data = $data;
    }

    /**
     * @return ClientMono
     * @throws Exception
     */
    protected function getClient(): ClientMono
    {
        if ($this->client === null) {
            throw new \Exception('Mono API Client isn\'t specified.', 500);
        }

        return $this->client;
    }

    /**
     * Set mono client to have ability to work with mono api
     *
     * @param ClientMono $client
     * @return $this
     */
    public function setClient(ClientMono $client): self
    {
        $this->client = $client;

        return $this;
    }

    /**
     * This method exists only to make simple access to the invoice data
     *
     * @param $method
     * @param $args
     * @return mixed|null
     * @throws Exception
     */
    public function __call($method, $args)
    {
        $methodName = substr($method, 0, 3);
        $attributeName = lcfirst(substr($method, 3));
        switch ($methodName) {
            case 'get':
                return $this->data[$attributeName] ?? null;
            case 'set':
                return $this->data[$attributeName] = $args[0];
        }

        throw new \Exception('Invalid method %1::%2', [get_class($this), $method]);
    }
}
