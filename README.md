# Monobank | MonoPay | Plata by Mono Library

A simple PHP library to work with Monobank Acquiring. For detailed API documentation, refer to [Monobank Acquiring API](https://api.monobank.ua/docs/acquiring.html).

## Dependencies
- guzzlehttp/guzzle >= 7.9.0
- PHP >= 7.4

## How to Use
First, obtain an API token, which is required to access Monobank's API. You can register one in your Monobank [personal account](https://web.monobank.ua/) to activate a token or enable a [test token](https://api.monobank.ua/index.html).

Run the next command to install the library with all dependencies:

```
composer require atsareva/monopay
```

## Usage Examples

### Initialize Mono Client
```
require_once 'vendor/autoload.php';

/** Initialize Mono client */
$client = new MonoPay\ClientMono('TOKEN');
```

### Invoice Examples

#### Create an Invoice

```
$options = [
    'amount' => 5000,
    'merchantPaymInfo' => [
        'reference' => '1000000021',
        'destination' => 'Order #1000000021',
        'basketOrder' => [
            [
                'name' => 'Product ABC',
                'qty' => 1,
                'sum' => 5000, // Amount in the smallest currency unit per product unit
                'icon' => 'https://example.com/media/catalog/product/product_abc.jpg',
                'unit' => 'kg',
            ],
        ],
    ],
    'redirectUrl' => 'https://example.com/monopay/success.php',
    'webHookUrl' => 'https://example.com/monopay/webhook.php',
    'paymentType' => 'hold', // Options: debit | hold
];

try {
    $invoice = new MonoPay\Invoice();
    $invoice
        ->setClient($client)
        ->create($options);

    // Save invoice ID for future reference
    echo "Invoice ID: " . $invoice->getInvoiceId() . '<br/>';
    echo "Invoice Payment URL: " . $invoice->getPageUrl() . '<br/>';
    echo "Invoice Status: " . $invoice->getStatus() . '<br/>';

    // Redirect customer to payment page
    header("Location: " . $invoice->getPageUrl());
} catch (Exception $e) {
    // Handle error
    echo $e->getMessage();
}
```

#### Get Updated Invoice Data

```
try {
    $invoice
        ->setClient($client)
        ->setInvoiceId('INVOICE_ID')
        ->updateInfo();

    echo "Invoice ID: " . $invoice->getInvoiceId() . '<br/>';
    echo "Invoice Status: " . $invoice->getStatus() . '<br/>';

} catch (Exception $e) {
    // Handle error
    echo $e->getMessage();
}
```

#### Finalize Invoice (Hold Status)

```
try {
    $invoice
        ->setClient($client)
        ->setInvoiceId('INVOICE_ID')
        ->capture();

} catch (Exception $e) {
    // Handle error
    echo $e->getMessage();
}
```

#### Refund Invoice (Successful Payment)

```
try {
    $invoice
        ->setClient($client)
        ->setInvoiceId('INVOICE_ID')
        ->refund()
        ->updateInfo();

    echo "Invoice Status: " . $invoice->getStatus() . '<br/>';

} catch (Exception $e) {
    // Handle error
    echo $e->getMessage();
}
```

### Merchant Examples

```
try {
    $merchant = new MonoPay\Merchant();
    $merchant
        ->setClient($client)
        ->load();

    echo "Merchant ID: " . $merchant->getMerchantId() . '<br/>';
    echo "Merchant Name: " . $merchant->getMerchantName() . '<br/>';
    echo "Public Key: " . $merchant->getPublicKey() . '<br/>';

    // Load all statements from the last 4 days
    $statements = $merchant->getStatement(time() - 60 * 60 * 96);
    foreach ($statements as $statement) {
        var_dump($statement);
    }
} catch (Exception $e) {
    // Handle error
    echo $e->getMessage();
}

```

---

**Note:** This library is currently in development. Please create a ticket if you encounter any issues.

Thanks!
