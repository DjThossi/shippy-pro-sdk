<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use DjThossi\ShippyProSdk\Domain\ApiKey;
use DjThossi\ShippyProSdk\Domain\GetOrderRequest;
use DjThossi\ShippyProSdk\ShippyProFactory;
use DjThossi\ShippyProSdk\SimpleShippyProConfiguration;

// Initialize the ShippyPro SDK with YOUR API key. The base URL
// (https://api.shippypro.com) and request timeout (10 seconds) default.
$configuration = new SimpleShippyProConfiguration(new ApiKey('YOUR_API_KEY'));
$factory = new ShippyProFactory($configuration);
$api = $factory->createGetOrderApi();

try {
    // The ShippyPro OrderID, e.g. the NewOrderID returned by the Ship call.
    $response = $api->getOrder(new GetOrderRequest(385590136));

    echo "GetOrder request completed.\n";
    echo "Response Status Code: {$response->statusCode->value}\n";

    if ($response->hasError()) {
        // e.g. {"Error": "No orders found"}
        echo "Error: {$response->error->detail}\n";
    } elseif ($response->responseBody !== null) {
        $body = $response->responseBody;
        echo "OrderID: {$body->orderId}\n";
        echo "Tracking carrier: {$body->trackingCarrier}\n";
        echo "Tracking number: {$body->trackingNumber}\n";
        echo "Tracking link: {$body->trackingExternalLink}\n";
        foreach ($body->labelUrls as $url) {
            echo "Label: {$url}\n";
        }
    }
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . "\n";
}
