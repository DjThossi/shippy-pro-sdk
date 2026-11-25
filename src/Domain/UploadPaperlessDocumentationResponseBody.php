<?php

declare(strict_types=1);

namespace DjThossi\ShippyProSdk\Domain;

final readonly class UploadPaperlessDocumentationResponseBody
{
    /**
     * The `Result` field is optional per v1.json, so it is null when the API
     * omits it.
     *
     * @param ?string $result the `Result` field (for example "OK")
     */
    public function __construct(
        public ?string $result,
    ) {
    }
}
