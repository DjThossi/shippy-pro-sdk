<?php

declare(strict_types=1);

namespace DjThossi\ShippyProSdk\Mapper;

use function array_filter;
use function array_map;
use function array_values;

use DjThossi\ShippyProSdk\Domain\ErrorSuggestion;
use DjThossi\ShippyProSdk\Domain\ErrorType;
use DjThossi\ShippyProSdk\Domain\GetOrderRequest;
use DjThossi\ShippyProSdk\Domain\GetOrderResponseBody;
use DjThossi\ShippyProSdk\Domain\LabelUrlCollection;

use function is_array;
use function is_string;
use function json_encode;

use const JSON_THROW_ON_ERROR;

final readonly class GetOrderMapper
{
    public const string METHOD = 'GetOrder';
    public const string FIELD_METHOD = 'Method';
    public const string FIELD_PARAMS = 'Params';

    // Request field names (exact v1.json names).
    public const string FIELD_ORDER_ID = 'OrderID';

    // Response field names (exact v1.json names).
    public const string FIELD_RESULT = 'Result';
    public const string FIELD_TRANSACTION_ID = 'TransactionID';
    public const string FIELD_CARRIER_ID = 'CarrierID';
    public const string FIELD_TRACKING_CARRIER = 'TrackingCarrier';
    public const string FIELD_TRACKING_NUMBER = 'TrackingNumber';
    public const string FIELD_TRACKING_EXTERNAL_LINK = 'TrackingExternalLink';
    public const string FIELD_ADDITIONAL_TRACKING_NUMBERS = 'AdditionalTrackingNumbers';
    public const string FIELD_MARKETPLACE_PLATFORM = 'MarketplacePlatform';
    public const string FIELD_STATUS = 'Status';
    public const string FIELD_LABEL_URL = 'LabelURL';
    public const string FIELD_ERROR_MESSAGE = 'ErrorMessage';
    public const string FIELD_ERROR_TYPE = 'ErrorType';
    public const string FIELD_ERROR_SUGGESTION = 'ErrorSuggestion';

    public function encodeRequest(GetOrderRequest $request): string
    {
        return json_encode([
            self::FIELD_METHOD => self::METHOD,
            self::FIELD_PARAMS => [
                self::FIELD_ORDER_ID => $request->orderId,
            ],
        ], JSON_THROW_ON_ERROR);
    }

    /**
     * Per v1.json every response field is optional, so absent fields map to
     * null / empty values instead of raising an exception.
     *
     * @param array<string, mixed> $data
     */
    public function mapResponseBody(array $data): GetOrderResponseBody
    {
        return new GetOrderResponseBody(
            $this->mapLabelUrl($data),
            $this->nullableString($data, self::FIELD_RESULT),
            $this->nullableString($data, self::FIELD_ORDER_ID),
            $this->nullableString($data, self::FIELD_TRANSACTION_ID),
            $this->nullableString($data, self::FIELD_CARRIER_ID),
            $this->nullableString($data, self::FIELD_TRACKING_CARRIER),
            $this->nullableString($data, self::FIELD_TRACKING_NUMBER),
            $this->nullableString($data, self::FIELD_TRACKING_EXTERNAL_LINK),
            $this->arrayList($data, self::FIELD_ADDITIONAL_TRACKING_NUMBERS),
            $this->nullableString($data, self::FIELD_MARKETPLACE_PLATFORM),
            $this->nullableString($data, self::FIELD_STATUS),
            $this->nullableString($data, self::FIELD_ERROR_MESSAGE),
            $this->mapErrorType($data),
            $this->mapErrorSuggestion($data),
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    private function mapLabelUrl(array $data): LabelUrlCollection
    {
        $raw = $data[self::FIELD_LABEL_URL] ?? null;

        if (is_string($raw)) {
            return new LabelUrlCollection($raw);
        }

        if (is_array($raw)) {
            return new LabelUrlCollection(...array_values(array_map(static fn (mixed $v): string => (string) $v, $raw)));
        }

        return new LabelUrlCollection();
    }

    /**
     * @param array<string, mixed> $data
     */
    private function nullableString(array $data, string $field): ?string
    {
        return isset($data[$field]) ? (string) $data[$field] : null;
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<int, array<mixed>>
     */
    private function arrayList(array $data, string $field): array
    {
        if (!isset($data[$field]) || !is_array($data[$field])) {
            return [];
        }

        return array_values(array_filter($data[$field], static fn (mixed $v): bool => is_array($v)));
    }

    /**
     * The API returns an empty object (`{}`) when there is no error type.
     *
     * @param array<string, mixed> $data
     */
    private function mapErrorType(array $data): ?ErrorType
    {
        $e = $data[self::FIELD_ERROR_TYPE] ?? null;
        if (!is_array($e) || $e === []) {
            return null;
        }

        return new ErrorType(
            isset($e['code']) ? (string) $e['code'] : null,
            isset($e['name']) ? (string) $e['name'] : null,
            isset($e['typeDescription']) ? (string) $e['typeDescription'] : null,
        );
    }

    /**
     * The API returns an empty object (`{}`) when there is no error suggestion.
     *
     * @param array<string, mixed> $data
     */
    private function mapErrorSuggestion(array $data): ?ErrorSuggestion
    {
        $e = $data[self::FIELD_ERROR_SUGGESTION] ?? null;
        if (!is_array($e) || $e === []) {
            return null;
        }

        return new ErrorSuggestion(
            isset($e['errorDescription']) ? (string) $e['errorDescription'] : null,
            isset($e['solution']) ? (string) $e['solution'] : null,
        );
    }
}
