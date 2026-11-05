<?php

declare(strict_types=1);

namespace DjThossi\ShippyProSdk\Domain;

/**
 * A single line item for a cross-border customs invoice, mapping to one entry of
 * the v1 `CreateCrossBorderInvoice` goods array. Like {@see Cn22Item}, this is a
 * plain readonly data container with no validation of its own.
 */
final readonly class CrossBorderGoodsItem
{
    public function __construct(
        public int $parcelCount,
        public string $description,
        public string $hsCode,
        public int $quantity,
        public float $weight,
        public float $unitValue,
        public ?int $typeOfPackaging = null,
        public ?string $marks = null,
        public ?string $countryOfOrigin = null,
        public ?string $quantityUom = null,
        public ?float $gstPaid = null,
    ) {
    }
}
