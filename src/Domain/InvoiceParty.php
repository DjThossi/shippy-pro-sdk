<?php

declare(strict_types=1);

namespace DjThossi\ShippyProSdk\Domain;

final readonly class InvoiceParty
{
    public function __construct(
        public string $name,
        public string $address,
        public string $zipcode,
        public string $city,
        public CountryCode $country,
        public string $telephone,
        public ?string $company = null,
        public ?string $vatNumber = null,
        public ?string $eori = null,
        public ?string $customReference = null,
    ) {
    }
}
