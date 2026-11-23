<?php

declare(strict_types=1);

namespace DjThossi\ShippyProSdk\Domain;

final readonly class GetOrderResponseBody
{
    /**
     * @param array<int, array<mixed>> $additionalTrackingNumbers
     */
    public function __construct(
        public LabelUrlCollection $labelUrls,
        public ?string $result = null,
        public ?string $orderId = null,
        public ?string $transactionId = null,
        public ?string $carrierId = null,
        public ?string $trackingCarrier = null,
        public ?string $trackingNumber = null,
        public ?string $trackingExternalLink = null,
        public array $additionalTrackingNumbers = [],
        public ?string $marketplacePlatform = null,
        public ?string $status = null,
        public ?string $errorMessage = null,
        public ?ErrorType $errorType = null,
        public ?ErrorSuggestion $errorSuggestion = null,
    ) {
    }

    public function hasBusinessError(): bool
    {
        return $this->errorMessage !== null;
    }
}
