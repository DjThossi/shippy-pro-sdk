<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use DjThossi\ShippyProSdk\Domain\ApiKey;
use DjThossi\ShippyProSdk\Domain\CountryCode;
use DjThossi\ShippyProSdk\Domain\PaperlessDocumentType;
use DjThossi\ShippyProSdk\Domain\UploadPaperlessDocumentationRequest;
use DjThossi\ShippyProSdk\Domain\UploadPaperlessDocumentationResponse;
use DjThossi\ShippyProSdk\ShippyProFactory;
use DjThossi\ShippyProSdk\SimpleShippyProConfiguration;

// Initialize the ShippyPro SDK with YOUR API key. The base URL
// (https://api.shippypro.com) and request timeout (10 seconds) default.
$configuration = new SimpleShippyProConfiguration(new ApiKey('YOUR_API_KEY'));
$factory = new ShippyProFactory($configuration);
$api = $factory->createPaperlessDocumentationApi();

// Upload BEFORE the Ship call. TransactionID, Name and Country must match
// the later Ship call exactly (case sensitive), otherwise the ETD is not linked.
$transactionId = 'SAME TRANSACTION ID AS IN SHIP CALL';
$name = 'SAME RECEIVER NAME AS IN SHIP CALL';
$country = new CountryCode('CH');

function printResponse(string $label, UploadPaperlessDocumentationResponse $response): void
{
    echo "[{$label}] Status Code: {$response->statusCode->value}\n";

    if ($response->hasError()) {
        echo "[{$label}] Error: {$response->error->detail}\n";
    } elseif ($response->responseBody !== null) {
        echo "[{$label}] Result: {$response->responseBody->result}\n";
    }
}

// Version 1: you already have the PDF as base64 string.
try {
    $pdf = file_get_contents(__DIR__ . '/invoice.pdf');
    if ($pdf === false) {
        throw new RuntimeException('Could not read examples/invoice.pdf');
    }

    $response = $api->uploadPaperlessDocumentation(new UploadPaperlessDocumentationRequest(
        transactionId: $transactionId,
        name: $name,
        country: $country,
        document: base64_encode($pdf),
        documentType: PaperlessDocumentType::Invoice,
    ));
    printResponse('base64', $response);
} catch (Exception $e) {
    echo 'Error (base64): ' . $e->getMessage() . "\n";
}

// Version 2: pass a file path, the SDK reads and encodes the file.
try {
    $response = $api->uploadPaperlessDocumentationFromFile(
        __DIR__ . '/invoice.pdf',
        $transactionId,
        $name,
        $country,
        PaperlessDocumentType::Invoice,
    );
    printResponse('file', $response);
} catch (Exception $e) {
    echo 'Error (file): ' . $e->getMessage() . "\n";
}

// Version 3: pass a download URL, the SDK downloads and encodes the file.
// No ShippyPro credentials are sent to the download host.
try {
    $response = $api->uploadPaperlessDocumentationFromUrl(
        'https://example.com/invoice.pdf',
        $transactionId,
        $name,
        $country,
        PaperlessDocumentType::Invoice,
    );
    printResponse('url', $response);
} catch (Exception $e) {
    echo 'Error (url): ' . $e->getMessage() . "\n";
}
