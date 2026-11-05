<?php

declare(strict_types=1);

namespace DjThossi\ShippyProSdk\Domain;

use DjThossi\ShippyProSdk\Exception\InvalidCrossBorderInvoiceException;

/**
 * Typed model of the v1 `CreateCrossBorderInvoiceParams` request object.
 *
 * Only the clearly-documented goods invariant is enforced client-side at
 * construction time (a cross-border invoice must contain at least one goods
 * line item). Every other carrier- and country-specific rule is left to the
 * API.
 */
final readonly class CreateCrossBorderInvoiceRequest
{
    public function __construct(
        public Incoterm $incoterm,
        public string $typeOfExport,
        public string $orderId,
        public string $invoiceNumber,
        public DateOfExportation $dateOfExportation,
        public InvoiceParty $shipperExporter,
        public InvoiceParty $consignee,
        public CrossBorderGoodsCollection $goods,
        public ?string $currency = null,
        public ?string $numberOfParcels = null,
        public ?string $orderReference = null,
        public ?string $totalLineItems = null,
        public ?string $totalWeight = null,
        public ?float $totalAmount = null,
        public ?float $totalGst = null,
        public ?string $weightUom = null,
        public ?string $invoiceType = null,
        public ?string $uploadEtd = null,
        public ?string $declarationNotes = null,
        public ?string $airwaybillNumber = null,
        public ?string $customReference = null,
        public ?CountryCode $countryOfExportDestination = null,
        public ?DateOfExportation $signatureDate = null,
        public ?InvoiceParty $importer = null,
    ) {
        $this->assertGoodsNotEmpty();
    }

    private function assertGoodsNotEmpty(): void
    {
        if ($this->goods->isEmpty()) {
            throw new InvalidCrossBorderInvoiceException('A cross-border invoice requires at least one goods line item');
        }
    }
}
