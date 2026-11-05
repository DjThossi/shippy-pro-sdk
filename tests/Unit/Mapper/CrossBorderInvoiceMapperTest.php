<?php

declare(strict_types=1);

namespace DjThossi\ShippyProSdk\Tests\Unit\Mapper;

use DjThossi\ShippyProSdk\Domain\CountryCode;
use DjThossi\ShippyProSdk\Domain\CreateCrossBorderInvoiceRequest;
use DjThossi\ShippyProSdk\Domain\CrossBorderGoodsCollection;
use DjThossi\ShippyProSdk\Domain\CrossBorderGoodsItem;
use DjThossi\ShippyProSdk\Domain\DateOfExportation;
use DjThossi\ShippyProSdk\Domain\Incoterm;
use DjThossi\ShippyProSdk\Domain\InvoiceParty;
use DjThossi\ShippyProSdk\Mapper\CrossBorderInvoiceMapper;

use function json_decode;

use const JSON_THROW_ON_ERROR;

use PHPUnit\Framework\TestCase;

final class CrossBorderInvoiceMapperTest extends TestCase
{
    private CrossBorderInvoiceMapper $mapper;

    protected function setUp(): void
    {
        $this->mapper = new CrossBorderInvoiceMapper();
    }

    public function testEncodeRequestWrapsEnvelopeWithAllRequiredParams(): void
    {
        $json = $this->mapper->encodeRequest($this->minimalRequest());
        $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame('CreateCrossBorderInvoice', $decoded[CrossBorderInvoiceMapper::FIELD_METHOD]);

        $params = $decoded[CrossBorderInvoiceMapper::FIELD_PARAMS];
        $this->assertArrayHasKey(CrossBorderInvoiceMapper::FIELD_INCOTERM, $params);
        $this->assertArrayHasKey(CrossBorderInvoiceMapper::FIELD_TYPE_OF_EXPORT, $params);
        $this->assertArrayHasKey(CrossBorderInvoiceMapper::FIELD_ORDER_ID, $params);
        $this->assertArrayHasKey(CrossBorderInvoiceMapper::FIELD_INVOICE_NUMBER, $params);
        $this->assertArrayHasKey(CrossBorderInvoiceMapper::FIELD_DATE_OF_EXPORTATION, $params);
        $this->assertArrayHasKey(CrossBorderInvoiceMapper::FIELD_SHIPPER_EXPORTER, $params);
        $this->assertArrayHasKey(CrossBorderInvoiceMapper::FIELD_CONSIGNEE, $params);
        $this->assertArrayHasKey(CrossBorderInvoiceMapper::FIELD_GOODS, $params);

        $this->assertSame('DAP', $params[CrossBorderInvoiceMapper::FIELD_INCOTERM]);
        $this->assertSame('permanent', $params[CrossBorderInvoiceMapper::FIELD_TYPE_OF_EXPORT]);
        $this->assertSame('ORDER1', $params[CrossBorderInvoiceMapper::FIELD_ORDER_ID]);
        $this->assertSame('INV-1', $params[CrossBorderInvoiceMapper::FIELD_INVOICE_NUMBER]);
        $this->assertSame('2024-01-15', $params[CrossBorderInvoiceMapper::FIELD_DATE_OF_EXPORTATION]);
    }

    public function testMapResponseBodyMapsResultAndInvoice(): void
    {
        $body = $this->mapper->mapResponseBody([
            'Result' => 'OK',
            'CrossborderInvoice' => 'base64pdf==',
        ]);

        $this->assertSame('OK', $body->result);
        $this->assertSame('base64pdf==', $body->crossborderInvoice);
    }

    public function testMapResponseBodyEmptyMapsBothToNull(): void
    {
        $body = $this->mapper->mapResponseBody([]);

        $this->assertNull($body->result);
        $this->assertNull($body->crossborderInvoice);
    }

    private function minimalRequest(): CreateCrossBorderInvoiceRequest
    {
        $party = new InvoiceParty('Acme', 'Main St 1', '10115', 'Berlin', new CountryCode('DE'), '+49');

        return new CreateCrossBorderInvoiceRequest(
            Incoterm::Dap,
            'permanent',
            'ORDER1',
            'INV-1',
            new DateOfExportation('2024-01-15'),
            $party,
            $party,
            new CrossBorderGoodsCollection(
                new CrossBorderGoodsItem(1, 'Widget', '123456', 2, 1.5, 9.99),
            ),
        );
    }
}
