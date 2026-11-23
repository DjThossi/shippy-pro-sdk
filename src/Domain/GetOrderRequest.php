<?php

declare(strict_types=1);

namespace DjThossi\ShippyProSdk\Domain;

final readonly class GetOrderRequest
{
    public function __construct(
        public int $orderId,
    ) {
    }
}
