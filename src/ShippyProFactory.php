<?php

declare(strict_types=1);

namespace DjThossi\ShippyProSdk;

use DjThossi\ShippyProSdk\Api\CrossBorderInvoiceApi;
use DjThossi\ShippyProSdk\Api\GetOrderApi;
use DjThossi\ShippyProSdk\Api\PaperlessDocumentationApi;
use DjThossi\ShippyProSdk\Api\PingApi;
use DjThossi\ShippyProSdk\Api\ShipApi;
use DjThossi\ShippyProSdk\Api\StatusApi;
use DjThossi\ShippyProSdk\Http\Client;
use DjThossi\ShippyProSdk\Mapper\CrossBorderInvoiceMapper;
use DjThossi\ShippyProSdk\Mapper\ErrorMapper;
use DjThossi\ShippyProSdk\Mapper\GetOrderMapper;
use DjThossi\ShippyProSdk\Mapper\HeaderMapper;
use DjThossi\ShippyProSdk\Mapper\PaperlessDocumentationMapper;
use DjThossi\ShippyProSdk\Mapper\PingMapper;
use DjThossi\ShippyProSdk\Mapper\ShipMapper;
use DjThossi\ShippyProSdk\Mapper\StatusMapper;

final readonly class ShippyProFactory
{
    public function __construct(
        private ShippyProConfiguration $configuration,
    ) {
    }

    public function createPingApi(): PingApi
    {
        return new PingApi(
            $this->createClient(),
            $this->createPingMapper(),
            $this->createHeaderMapper(),
            $this->createErrorMapper(),
        );
    }

    public function createStatusApi(): StatusApi
    {
        return new StatusApi(
            $this->createClient(),
            $this->createStatusMapper(),
            $this->createHeaderMapper(),
            $this->createErrorMapper(),
        );
    }

    public function createShipApi(): ShipApi
    {
        return new ShipApi(
            $this->createClient(),
            $this->createShipMapper(),
            $this->createHeaderMapper(),
            $this->createErrorMapper(),
        );
    }

    public function createCrossBorderInvoiceApi(): CrossBorderInvoiceApi
    {
        return new CrossBorderInvoiceApi(
            $this->createClient(),
            $this->createCrossBorderInvoiceMapper(),
            $this->createHeaderMapper(),
            $this->createErrorMapper(),
        );
    }

    public function createGetOrderApi(): GetOrderApi
    {
        return new GetOrderApi(
            $this->createClient(),
            $this->createGetOrderMapper(),
            $this->createHeaderMapper(),
            $this->createErrorMapper(),
        );
    }

    public function createPaperlessDocumentationApi(): PaperlessDocumentationApi
    {
        return new PaperlessDocumentationApi(
            $this->createClient(),
            $this->createPaperlessDocumentationMapper(),
            $this->createHeaderMapper(),
            $this->createErrorMapper(),
        );
    }

    private function createClient(): Client
    {
        return new Client(
            $this->configuration->getBaseUrl(),
            $this->configuration->getApiKey(),
            $this->configuration->getRequestTimeout(),
            $this->configuration->getPartnerName(),
        );
    }

    private function createPingMapper(): PingMapper
    {
        return new PingMapper();
    }

    private function createStatusMapper(): StatusMapper
    {
        return new StatusMapper();
    }

    private function createShipMapper(): ShipMapper
    {
        return new ShipMapper();
    }

    private function createGetOrderMapper(): GetOrderMapper
    {
        return new GetOrderMapper();
    }

    private function createCrossBorderInvoiceMapper(): CrossBorderInvoiceMapper
    {
        return new CrossBorderInvoiceMapper();
    }

    private function createPaperlessDocumentationMapper(): PaperlessDocumentationMapper
    {
        return new PaperlessDocumentationMapper();
    }

    private function createHeaderMapper(): HeaderMapper
    {
        return new HeaderMapper();
    }

    private function createErrorMapper(): ErrorMapper
    {
        return new ErrorMapper();
    }
}
