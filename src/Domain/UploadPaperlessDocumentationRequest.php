<?php

declare(strict_types=1);

namespace DjThossi\ShippyProSdk\Domain;

use function base64_decode;

use DjThossi\ShippyProSdk\Exception\InvalidPaperlessDocumentationException;

use function mb_strlen;
use function trim;

final readonly class UploadPaperlessDocumentationRequest
{
    public const int MAX_TRANSACTION_ID_LENGTH = 255;

    /**
     * TransactionID, Name and Country must match the later Ship call exactly
     * (case sensitive), so none of them is normalized.
     *
     * @param string                 $transactionId your internal reference order number (max 255 chars)
     * @param string                 $name          recipient name
     * @param CountryCode            $country       recipient country
     * @param string                 $document      PDF document, already base64 encoded
     * @param ?PaperlessDocumentType $documentType  null omits the field, the API default (Invoice) applies
     */
    public function __construct(
        public string $transactionId,
        public string $name,
        public CountryCode $country,
        public string $document,
        public ?PaperlessDocumentType $documentType = null,
    ) {
        if (trim($transactionId) === '') {
            throw new InvalidPaperlessDocumentationException('TransactionID must not be empty');
        }

        if (mb_strlen($transactionId) > self::MAX_TRANSACTION_ID_LENGTH) {
            throw new InvalidPaperlessDocumentationException('TransactionID must not exceed 255 characters');
        }

        if (trim($name) === '') {
            throw new InvalidPaperlessDocumentationException('Name must not be empty');
        }

        if ($document === '' || base64_decode($document, true) === false) {
            throw new InvalidPaperlessDocumentationException('Document must be a non-empty base64 encoded string');
        }
    }
}
