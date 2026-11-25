<?php

declare(strict_types=1);

namespace DjThossi\ShippyProSdk\Mapper;

use DjThossi\ShippyProSdk\Domain\UploadPaperlessDocumentationRequest;
use DjThossi\ShippyProSdk\Domain\UploadPaperlessDocumentationResponseBody;

use function json_encode;

use const JSON_THROW_ON_ERROR;

final readonly class PaperlessDocumentationMapper
{
    public const string METHOD = 'UploadPaperlessDocumentation';
    public const string FIELD_METHOD = 'Method';
    public const string FIELD_PARAMS = 'Params';

    // Request field names (exact v1.json names).
    public const string FIELD_TRANSACTION_ID = 'TransactionID';
    public const string FIELD_NAME = 'Name';
    public const string FIELD_COUNTRY = 'Country';
    public const string FIELD_DOCUMENT = 'Document';
    public const string FIELD_DOCUMENT_TYPE = 'DocumentType';

    // Response field names (exact v1.json names).
    public const string FIELD_RESULT = 'Result';

    public function encodeRequest(UploadPaperlessDocumentationRequest $request): string
    {
        $params = [
            self::FIELD_TRANSACTION_ID => $request->transactionId,
            self::FIELD_NAME => $request->name,
            self::FIELD_COUNTRY => $request->country->value,
            self::FIELD_DOCUMENT => $request->document,
        ];

        if ($request->documentType !== null) {
            $params[self::FIELD_DOCUMENT_TYPE] = $request->documentType->value;
        }

        return json_encode([
            self::FIELD_METHOD => self::METHOD,
            self::FIELD_PARAMS => $params,
        ], JSON_THROW_ON_ERROR);
    }

    /**
     * The v1.json response schema declares no required fields, so an absent
     * `Result` maps to null instead of raising an exception.
     *
     * @param array<string, mixed> $data
     */
    public function mapResponseBody(array $data): UploadPaperlessDocumentationResponseBody
    {
        return new UploadPaperlessDocumentationResponseBody(
            isset($data[self::FIELD_RESULT]) ? (string) $data[self::FIELD_RESULT] : null,
        );
    }
}
