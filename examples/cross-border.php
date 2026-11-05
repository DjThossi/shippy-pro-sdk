<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use DjThossi\ShippyProSdk\Api\CrossBorderInvoiceApi;
use DjThossi\ShippyProSdk\Api\ShipApi;
use DjThossi\ShippyProSdk\Domain\Address;
use DjThossi\ShippyProSdk\Domain\ApiKey;
use DjThossi\ShippyProSdk\Domain\Cn22Item;
use DjThossi\ShippyProSdk\Domain\Cn22ItemCollection;
use DjThossi\ShippyProSdk\Domain\CountryCode;
use DjThossi\ShippyProSdk\Domain\CreateCrossBorderInvoiceRequest;
use DjThossi\ShippyProSdk\Domain\CrossBorderGoodsCollection;
use DjThossi\ShippyProSdk\Domain\CrossBorderGoodsItem;
use DjThossi\ShippyProSdk\Domain\CustomInfoContentsType;
use DjThossi\ShippyProSdk\Domain\Customs;
use DjThossi\ShippyProSdk\Domain\DateOfExportation;
use DjThossi\ShippyProSdk\Domain\Incoterm;
use DjThossi\ShippyProSdk\Domain\InvoiceParty;
use DjThossi\ShippyProSdk\Domain\LabelFormatting;
use DjThossi\ShippyProSdk\Domain\LabelType;
use DjThossi\ShippyProSdk\Domain\MoneyAmount;
use DjThossi\ShippyProSdk\Domain\Parcel;
use DjThossi\ShippyProSdk\Domain\ParcelCollection;
use DjThossi\ShippyProSdk\Domain\ShipRequest;
use DjThossi\ShippyProSdk\ShippyProFactory;
use DjThossi\ShippyProSdk\SimpleShippyProConfiguration;

/*
 * Cross-border examples (DE -> CH and DE -> GB).
 *
 * Each destination is shipped in two steps, mirroring how ShippyPro splits the
 * customs concern across two API methods:
 *
 *   1. ShipApi::ship()                  -> creates the label + the CN22 customs
 *                                          line items + incoterm (what the
 *                                          `Ship` request can express).
 *   2. CrossBorderInvoiceApi::createInvoice()
 *                                       -> carries the structured tax identifiers
 *                                          and exporter reference that the `Ship`
 *                                          request has NO field for:
 *                                          sender tax number   -> ShipperExporter.VatNumber
 *                                          receiver tax number -> Consignee.VatNumber
 *                                          exporter reference  -> ShipperExporter.CustomReference
 *
 * DOCUMENTED LIMITATION (no ShippyPro equivalent, so NOT reproduced here):
 *   - a third-party `duties_and_taxes` account (e.g. GB + UPS)
 *   - a third-party `transportation` account (e.g. UPS)
 *   ShippyPro's only `Ship` billing concept is BillAccountNumber (EXW only),
 *   which is not equivalent to a separate third-party duties/taxes account.
 *   Other unmapped customs fields: contents_explanation, drop_off_location,
 *   posting_date, additional_fees (see the README cross-border section).
 */

$configuration = new SimpleShippyProConfiguration(new ApiKey('YOUR_API_KEY'));
$factory = new ShippyProFactory($configuration);
$shipApi = $factory->createShipApi();
$crossBorderInvoiceApi = $factory->createCrossBorderInvoiceApi();

// The sender (exporter) is identical for both destinations. The sender tax
// number (e.g. DE111111111) has no `Ship` field; ShippyPro sources it from the
// account profile on `Ship`, so it travels on the CreateCrossBorderInvoice
// ShipperExporter instead.
$senderAddress = new Address('Erika Mustermann', 'ShippyPro GmbH', 'Musterstrasse 1', '', 'Berlin', 'BE', '10115', new CountryCode('DE'), '+493012345678', 'sender@example.com');
$senderSenderTaxNumber = 'DE111111111'; // sender tax number

/**
 * Builds and sends one cross-border shipment (label) plus its cross-border
 * invoice (tax identifiers / exporter reference) for a single destination.
 *
 * @param array{
 *     carrierName: string,
 *     carrierId: int,
 *     incoterm: Incoterm,
 *     recipient: Address,
 *     receiverTaxNumber: string,
 *     exporterReference: ?string,
 *     item: array{description: string, weightKg: float, quantity: int, unitValue: float, hsCode: string, originCountry: string},
 *     currency: string,
 *     labelWidthCm: float,
 *     labelHeightCm: float
 * } $order
 */
function sendCrossBorderOrder(
    ShipApi $shipApi,
    CrossBorderInvoiceApi $crossBorderInvoiceApi,
    Address $senderAddress,
    string $senderTaxNumber,
    array $order,
): void {
    $destinationCountry = $order['recipient']->country->value;
    echo "=== Cross-border order to {$destinationCountry} via {$order['carrierName']} ===\n";

    // ---- Step 1: the Ship request (label + CN22 customs line items + incoterm) ----
    //
    // The item description is truncated to 30 characters. That truncation is a
    // calling-application concern (it is not a ShippyPro/SDK rule); it is applied
    // here before handing the value to the SDK.
    $description = mb_substr($order['item']['description'], 0, 30, 'UTF-8');

    $customs = new Customs(
        $order['incoterm'],
        null,
        // A `commercial_goods` contents type is not a valid ShippyPro value; the
        // closest permitted one is Merchandise.
        CustomInfoContentsType::Merchandise,
        new Cn22ItemCollection(
            new Cn22Item(
                $description,
                $order['item']['weightKg'],
                $order['item']['quantity'],
                $order['item']['unitValue'],
                new CountryCode($order['item']['originCountry']),
                $order['currency'],
                $order['item']['hsCode'],
            ),
        ),
    );

    // Carrier-specific ZPL label, expressed in centimetres (two decimals).
    $labelFormatting = new LabelFormatting(
        LabelType::Zpl,
        $order['labelWidthCm'],
        $order['labelHeightCm'],
    );

    $totalValue = new MoneyAmount(
        number_format($order['item']['unitValue'] * $order['item']['quantity'], 2, '.', ''),
        $order['currency'],
    );

    $shipRequest = new ShipRequest(
        toAddress: $order['recipient'],
        fromAddress: $senderAddress,
        parcels: new ParcelCollection(new Parcel(30.0, 20.0, 10.0, $order['item']['weightKg'])),
        transactionId: 'ORDER-' . $destinationCountry . '-0001',
        contentDescription: 'Apparel',
        carrierName: $order['carrierName'],
        carrierService: 'standard', // standard shipping service
        carrierId: $order['carrierId'],
        totalValue: $totalValue,
        customs: $customs,
        labelFormatting: $labelFormatting,
    );

    try {
        $shipResponse = $shipApi->ship($shipRequest);
        echo "  Ship status: {$shipResponse->statusCode->value}\n";
        if ($shipResponse->hasError()) {
            echo "  Ship error: {$shipResponse->error->detail}\n";
        } elseif ($shipResponse->responseBody !== null && !$shipResponse->responseBody->hasBusinessError()) {
            echo "  Label created, order ID: {$shipResponse->responseBody->newOrderId}\n";
        } elseif ($shipResponse->responseBody !== null) {
            echo "  Ship business error: {$shipResponse->responseBody->errorMessage}\n";
        }
    } catch (Exception $e) {
        echo '  Ship error: ' . $e->getMessage() . "\n";

        exit(1);
    }

    // ---- Step 2: the cross-border invoice (tax identifiers + exporter reference) ----
    //
    // These values have NO field on the `Ship` request. They are carried here:
    //   - sender tax number   -> ShipperExporter.VatNumber
    //   - receiver tax number -> Consignee.VatNumber
    //   - exporter reference  -> ShipperExporter.CustomReference (set for CH in
    //     this example; null for GB)
    $shipperExporter = new InvoiceParty(
        $senderAddress->name,
        $senderAddress->street1,
        $senderAddress->zip,
        $senderAddress->city,
        $senderAddress->country,
        $senderAddress->phone,
        company: $senderAddress->company,
        vatNumber: $senderTaxNumber,                 // sender tax number
        customReference: $order['exporterReference'], // exporter reference (CH)
    );

    $consignee = new InvoiceParty(
        $order['recipient']->name,
        $order['recipient']->street1,
        $order['recipient']->zip,
        $order['recipient']->city,
        $order['recipient']->country,
        $order['recipient']->phone,
        company: $order['recipient']->company !== '' ? $order['recipient']->company : null,
        vatNumber: $order['receiverTaxNumber'],       // receiver tax number
    );

    $invoiceRequest = new CreateCrossBorderInvoiceRequest(
        incoterm: $order['incoterm'],
        typeOfExport: 'permanent',
        orderId: 'ORDER-' . $destinationCountry . '-0001',
        invoiceNumber: 'INV-' . $destinationCountry . '-0001',
        dateOfExportation: new DateOfExportation(date('Y-m-d')),
        shipperExporter: $shipperExporter,
        consignee: $consignee,
        goods: new CrossBorderGoodsCollection(
            new CrossBorderGoodsItem(
                1,
                $description,
                $order['item']['hsCode'],
                $order['item']['quantity'],
                $order['item']['weightKg'],
                $order['item']['unitValue'],
                countryOfOrigin: $order['item']['originCountry'],
            ),
        ),
        currency: $order['currency'],
    );

    try {
        $invoiceResponse = $crossBorderInvoiceApi->createInvoice($invoiceRequest);
        echo "  Invoice status: {$invoiceResponse->statusCode->value}\n";
        if ($invoiceResponse->hasError()) {
            echo "  Invoice error: {$invoiceResponse->error->detail}\n";
        } elseif ($invoiceResponse->responseBody !== null) {
            echo "  Invoice result: {$invoiceResponse->responseBody->result}\n";
        }
    } catch (Exception $e) {
        echo '  Invoice error: ' . $e->getMessage() . "\n";

        exit(1);
    }

    echo "\n";
}

// --- Switzerland (CH): DE -> CH. Sets a CH receiver tax number and the
// CH-specific exporter reference. No GB-style third-party duties/taxes account
// applies to CH. ---
sendCrossBorderOrder($shipApi, $crossBorderInvoiceApi, $senderAddress, $senderSenderTaxNumber, [
    'carrierName' => 'DHL',
    'carrierId' => 1,
    // No per-country incoterm override for CH, so the Customs default (DAP) is
    // used.
    'incoterm' => Incoterm::Dap,
    'recipient' => new Address('Hans Muster', '', 'Bahnhofstrasse 1', '', 'Zurich', 'ZH', '8001', new CountryCode('CH'), '+41441234567', 'ch-recipient@example.com'),
    'receiverTaxNumber' => 'CHE123456789',          // receiver tax number (CH)
    'exporterReference' => 'CHE-123.456.789 MWST',   // exporter reference (CH only)
    'item' => ['description' => 'Wool scarf', 'weightKg' => 0.25, 'quantity' => 1, 'unitValue' => 24.5, 'hsCode' => '611710', 'originCountry' => 'DE'],
    'currency' => 'EUR',
    // DHL ZPL zpl2_103x199mm_203dpi ~= 10.30 x 19.90 cm.
    'labelWidthCm' => 10.30,
    'labelHeightCm' => 19.90,
]);

// --- Great Britain (GB): DE -> GB with UPS. Uses the `dap` incoterm for GB+UPS
// and a GB receiver tax number. A GB+UPS third-party duties_and_taxes account
// and a UPS transportation account have NO ShippyPro equivalent and are
// intentionally omitted. ---
sendCrossBorderOrder($shipApi, $crossBorderInvoiceApi, $senderAddress, $senderSenderTaxNumber, [
    'carrierName' => 'UPS',
    'carrierId' => 23,
    'incoterm' => Incoterm::Dap, // GB + UPS -> incoterm `dap`
    'recipient' => new Address('John Doe', '', '10 Downing Street', '', 'London', '', 'SW1A 2AA', new CountryCode('GB'), '+442079250918', 'gb-recipient@example.com'),
    'receiverTaxNumber' => 'GB123456789000',         // receiver tax number (GB)
    'exporterReference' => null,                     // no exporter reference for GB in this example
    'item' => ['description' => 'Cotton t-shirt', 'weightKg' => 0.3, 'quantity' => 2, 'unitValue' => 19.99, 'hsCode' => '610910', 'originCountry' => 'DE'],
    'currency' => 'EUR',
    // UPS ZPL zpl2_4x8in_203dpi (4x8 in) ~= 10.16 x 20.32 cm.
    'labelWidthCm' => 10.16,
    'labelHeightCm' => 20.32,
]);

echo "All cross-border examples completed.\n";
