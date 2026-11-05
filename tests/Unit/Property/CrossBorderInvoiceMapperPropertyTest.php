<?php

declare(strict_types=1);

namespace DjThossi\ShippyProSdk\Tests\Unit\Property;

use DjThossi\ShippyProSdk\Domain\CountryCode;
use DjThossi\ShippyProSdk\Domain\CreateCrossBorderInvoiceRequest;
use DjThossi\ShippyProSdk\Domain\CrossBorderGoodsCollection;
use DjThossi\ShippyProSdk\Domain\CrossBorderGoodsItem;
use DjThossi\ShippyProSdk\Domain\DateOfExportation;
use DjThossi\ShippyProSdk\Domain\Incoterm;
use DjThossi\ShippyProSdk\Domain\InvoiceParty;
use DjThossi\ShippyProSdk\Mapper\CrossBorderInvoiceMapper;
use Eris\Attributes\ErisRepeat;
use Eris\Generators;
use Eris\TestTrait;

use function json_decode;

use const JSON_THROW_ON_ERROR;

use PHPUnit\Framework\TestCase;

use function strtoupper;
use function trim;

final class CrossBorderInvoiceMapperPropertyTest extends TestCase
{
    use TestTrait;

    /**
     * Feature: CreateCrossBorderInvoice, Property 1: For any valid CreateCrossBorderInvoiceRequest, decoding the JSON
     * produced by CrossBorderInvoiceMapper::encodeRequest SHALL yield Method === 'CreateCrossBorderInvoice' and a
     * Params map that contains every required key with values equal to the inputs.
     */
    #[ErisRepeat(100)]
    public function testRequiredParamsAlwaysPresentOnEncodeRoundTrip(): void
    {
        $mapper = new CrossBorderInvoiceMapper();

        $this->forAll(
            Generators::elements(Incoterm::Dap, Incoterm::Ddp, Incoterm::Exw),
            Generators::elements('permanent', 'temporary', 'repair'),
            Generators::string(),
            Generators::string(),
        )->then(function (
            Incoterm $incoterm,
            string $typeOfExport,
            string $orderId,
            string $invoiceNumber,
        ) use ($mapper): void {
            $party = new InvoiceParty('Acme', 'Main St 1', '10115', 'Berlin', new CountryCode('DE'), '+49');
            $request = new CreateCrossBorderInvoiceRequest(
                $incoterm,
                $typeOfExport,
                $orderId,
                $invoiceNumber,
                new DateOfExportation('2024-01-15'),
                $party,
                $party,
                new CrossBorderGoodsCollection(
                    new CrossBorderGoodsItem(1, 'Widget', '123456', 2, 1.5, 9.99),
                ),
            );

            $decoded = json_decode($mapper->encodeRequest($request), true, 512, JSON_THROW_ON_ERROR);

            $this->assertSame('CreateCrossBorderInvoice', $decoded[CrossBorderInvoiceMapper::FIELD_METHOD]);

            $params = $decoded[CrossBorderInvoiceMapper::FIELD_PARAMS];
            $this->assertSame($incoterm->value, $params[CrossBorderInvoiceMapper::FIELD_INCOTERM]);
            $this->assertSame($typeOfExport, $params[CrossBorderInvoiceMapper::FIELD_TYPE_OF_EXPORT]);
            $this->assertSame($orderId, $params[CrossBorderInvoiceMapper::FIELD_ORDER_ID]);
            $this->assertSame($invoiceNumber, $params[CrossBorderInvoiceMapper::FIELD_INVOICE_NUMBER]);
            $this->assertSame('2024-01-15', $params[CrossBorderInvoiceMapper::FIELD_DATE_OF_EXPORTATION]);
            $this->assertArrayHasKey(CrossBorderInvoiceMapper::FIELD_SHIPPER_EXPORTER, $params);
            $this->assertArrayHasKey(CrossBorderInvoiceMapper::FIELD_CONSIGNEE, $params);
            $this->assertArrayHasKey(CrossBorderInvoiceMapper::FIELD_GOODS, $params);
        });
    }

    /**
     * Feature: CreateCrossBorderInvoice, Property 4: For any goods line item, the encoded Goods entry SHALL contain
     * ParcelCount, Description, HsCode, Quantity, Weight and UnitValue equal to the item inputs.
     */
    #[ErisRepeat(100)]
    public function testGoodsItemsEncodeAllRequiredKeys(): void
    {
        $mapper = new CrossBorderInvoiceMapper();

        $this->forAll(
            Generators::choose(1, 50),
            Generators::string(),
            Generators::string(),
            Generators::choose(1, 999),
            Generators::choose(1, 100000),
            Generators::choose(1, 100000),
        )->then(function (
            int $parcelCount,
            string $description,
            string $hsCode,
            int $quantity,
            int $weightCents,
            int $unitValueCents,
        ) use ($mapper): void {
            $weight = $weightCents / 100;
            $unitValue = $unitValueCents / 100;
            $party = new InvoiceParty('Acme', 'Main St 1', '10115', 'Berlin', new CountryCode('DE'), '+49');
            $request = new CreateCrossBorderInvoiceRequest(
                Incoterm::Dap,
                'permanent',
                'ORDER1',
                'INV-1',
                new DateOfExportation('2024-01-15'),
                $party,
                $party,
                new CrossBorderGoodsCollection(
                    new CrossBorderGoodsItem($parcelCount, $description, $hsCode, $quantity, $weight, $unitValue),
                ),
            );

            $params = $mapper->encodeParams($request);
            $item = $params[CrossBorderInvoiceMapper::FIELD_GOODS][0];

            $this->assertSame($parcelCount, $item[CrossBorderInvoiceMapper::FIELD_GOODS_PARCEL_COUNT]);
            $this->assertSame($description, $item[CrossBorderInvoiceMapper::FIELD_GOODS_DESCRIPTION]);
            $this->assertSame($hsCode, $item[CrossBorderInvoiceMapper::FIELD_GOODS_HS_CODE]);
            $this->assertSame($quantity, $item[CrossBorderInvoiceMapper::FIELD_GOODS_QUANTITY]);
            $this->assertEqualsWithDelta($weight, $item[CrossBorderInvoiceMapper::FIELD_GOODS_WEIGHT], 0.0001);
            $this->assertEqualsWithDelta($unitValue, $item[CrossBorderInvoiceMapper::FIELD_GOODS_UNIT_VALUE], 0.0001);
        });
    }

    /**
     * Feature: CreateCrossBorderInvoice, Property 2: For any InvoiceParty, the encoded party SHALL always contain
     * Name, Address, Zipcode, City, Country (the uppercased CountryCode value) and Telephone, and SHALL contain
     * Company, VatNumber, Eori and CustomReference if and only if the corresponding property is non-null.
     */
    #[ErisRepeat(100)]
    public function testPartyEncodingPreservesRequiredAndPresentOptionalFields(): void
    {
        $mapper = new CrossBorderInvoiceMapper();

        $this->forAll(
            Generators::elements('US', 'de', ' it ', 'Fr', 'GB'),
            Generators::bool(),
            Generators::bool(),
            Generators::bool(),
            Generators::bool(),
        )->then(function (
            string $country,
            bool $withCompany,
            bool $withVat,
            bool $withEori,
            bool $withCustomRef,
        ) use ($mapper): void {
            $party = new InvoiceParty(
                'Acme',
                'Main St 1',
                '10115',
                'Berlin',
                new CountryCode($country),
                '+49',
                $withCompany ? 'Acme GmbH' : null,
                $withVat ? 'DE123456789' : null,
                $withEori ? 'DE999' : null,
                $withCustomRef ? 'REF-1' : null,
            );
            $request = $this->requestWithParties($party, $party);

            $encoded = $mapper->encodeParams($request)[CrossBorderInvoiceMapper::FIELD_SHIPPER_EXPORTER];

            $this->assertSame('Acme', $encoded[CrossBorderInvoiceMapper::FIELD_PARTY_NAME]);
            $this->assertSame('Main St 1', $encoded[CrossBorderInvoiceMapper::FIELD_PARTY_ADDRESS]);
            $this->assertSame('10115', $encoded[CrossBorderInvoiceMapper::FIELD_PARTY_ZIPCODE]);
            $this->assertSame('Berlin', $encoded[CrossBorderInvoiceMapper::FIELD_PARTY_CITY]);
            $this->assertSame(strtoupper(trim($country)), $encoded[CrossBorderInvoiceMapper::FIELD_PARTY_COUNTRY]);
            $this->assertSame('+49', $encoded[CrossBorderInvoiceMapper::FIELD_PARTY_TELEPHONE]);

            $this->assertSame($withCompany, isset($encoded[CrossBorderInvoiceMapper::FIELD_PARTY_COMPANY]));
            $this->assertSame($withVat, isset($encoded[CrossBorderInvoiceMapper::FIELD_PARTY_VAT_NUMBER]));
            $this->assertSame($withEori, isset($encoded[CrossBorderInvoiceMapper::FIELD_PARTY_EORI]));
            $this->assertSame($withCustomRef, isset($encoded[CrossBorderInvoiceMapper::FIELD_PARTY_CUSTOM_REFERENCE]));
        });
    }

    /**
     * Feature: CreateCrossBorderInvoice, Property 3: For any shipper/consignee parties, the encoded
     * ShipperExporter.VatNumber SHALL equal shipperExporter.vatNumber, Consignee.VatNumber SHALL equal
     * consignee.vatNumber, and ShipperExporter.CustomReference SHALL equal shipperExporter.customReference.
     */
    #[ErisRepeat(100)]
    public function testTaxNumbersAndExporterReferenceMapToCorrectFields(): void
    {
        $mapper = new CrossBorderInvoiceMapper();

        $this->forAll(
            Generators::string(),
            Generators::string(),
            Generators::string(),
        )->then(function (
            string $shipperVat,
            string $consigneeVat,
            string $exporterReference,
        ) use ($mapper): void {
            $shipper = new InvoiceParty(
                'Shipper',
                'Addr 1',
                '10115',
                'Berlin',
                new CountryCode('DE'),
                '+49',
                vatNumber: $shipperVat,
                customReference: $exporterReference,
            );
            $consignee = new InvoiceParty(
                'Consignee',
                'Addr 2',
                '1000',
                'Bern',
                new CountryCode('CH'),
                '+41',
                vatNumber: $consigneeVat,
            );
            $request = $this->requestWithParties($shipper, $consignee);

            $params = $mapper->encodeParams($request);
            $encodedShipper = $params[CrossBorderInvoiceMapper::FIELD_SHIPPER_EXPORTER];
            $encodedConsignee = $params[CrossBorderInvoiceMapper::FIELD_CONSIGNEE];

            $this->assertSame($shipperVat, $encodedShipper[CrossBorderInvoiceMapper::FIELD_PARTY_VAT_NUMBER]);
            $this->assertSame($consigneeVat, $encodedConsignee[CrossBorderInvoiceMapper::FIELD_PARTY_VAT_NUMBER]);
            $this->assertSame(
                $exporterReference,
                $encodedShipper[CrossBorderInvoiceMapper::FIELD_PARTY_CUSTOM_REFERENCE],
            );
        });
    }

    /**
     * Feature: CreateCrossBorderInvoice, Property 5: For any valid alpha-2 input, constructing an InvoiceParty SHALL
     * succeed and the encoded party Country SHALL equal the uppercased code.
     */
    #[ErisRepeat(100)]
    public function testValidCountryCodesAreAcceptedAndNormalized(): void
    {
        $mapper = new CrossBorderInvoiceMapper();

        $this->forAll(Generators::elements('us', 'DE', ' it ', 'Fr', 'gb', 'CH', 'Nl'))
            ->then(function (string $country) use ($mapper): void {
                $party = new InvoiceParty('Acme', 'Main St 1', '10115', 'Berlin', new CountryCode($country), '+49');
                $request = $this->requestWithParties($party, $party);

                $encoded = $mapper->encodeParams($request)[CrossBorderInvoiceMapper::FIELD_SHIPPER_EXPORTER];

                $this->assertSame(
                    strtoupper(trim($country)),
                    $encoded[CrossBorderInvoiceMapper::FIELD_PARTY_COUNTRY],
                );
            });
    }

    private function requestWithParties(
        InvoiceParty $shipperExporter,
        InvoiceParty $consignee,
    ): CreateCrossBorderInvoiceRequest {
        return new CreateCrossBorderInvoiceRequest(
            Incoterm::Dap,
            'permanent',
            'ORDER1',
            'INV-1',
            new DateOfExportation('2024-01-15'),
            $shipperExporter,
            $consignee,
            new CrossBorderGoodsCollection(
                new CrossBorderGoodsItem(1, 'Widget', '123456', 2, 1.5, 9.99),
            ),
        );
    }
}
