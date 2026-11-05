<?php

declare(strict_types=1);

namespace DjThossi\ShippyProSdk\Domain;

final readonly class CreateCrossBorderInvoiceResponseBody
{
    /**
     * Both fields are optional per v1.json. They are null when the API omits
     * them (for example when authentication fails, which ShippyPro reports
     * with HTTP 200 and an `{"Error": ...}` body instead of the response
     * payload).
     *
     * @param ?string $result             the `Result` field (for example "OK")
     * @param ?string $crossborderInvoice the `CrossborderInvoice` field, a base64-encoded PDF
     */
    public function __construct(
        public ?string $result,
        public ?string $crossborderInvoice,
    ) {
    }
}
