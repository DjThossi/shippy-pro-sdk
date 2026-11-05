<?php

declare(strict_types=1);

namespace DjThossi\ShippyProSdk\Mapper;

use function array_key_exists;
use function array_map;

use DjThossi\ShippyProSdk\Domain\CreateCrossBorderInvoiceRequest;
use DjThossi\ShippyProSdk\Domain\CreateCrossBorderInvoiceResponseBody;
use DjThossi\ShippyProSdk\Domain\CrossBorderGoodsCollection;
use DjThossi\ShippyProSdk\Domain\CrossBorderGoodsItem;
use DjThossi\ShippyProSdk\Domain\InvoiceParty;
use DjThossi\ShippyProSdk\Exception\MissingRequiredFieldsException;

use function json_encode;

use const JSON_THROW_ON_ERROR;

final readonly class CrossBorderInvoiceMapper
{
    public const string METHOD = 'CreateCrossBorderInvoice';
    public const string FIELD_METHOD = 'Method';
    public const string FIELD_PARAMS = 'Params';

    // Params fields (exact v1.json names).
    public const string FIELD_INCOTERM = 'Incoterm';
    public const string FIELD_TYPE_OF_EXPORT = 'TypeOfExport';
    public const string FIELD_ORDER_ID = 'OrderId';
    public const string FIELD_INVOICE_NUMBER = 'InvoiceNumber';
    public const string FIELD_DATE_OF_EXPORTATION = 'DateOfExportation';
    public const string FIELD_SHIPPER_EXPORTER = 'ShipperExporter';
    public const string FIELD_CONSIGNEE = 'Consignee';
    public const string FIELD_GOODS = 'Goods';
    public const string FIELD_CURRENCY = 'Currency';
    public const string FIELD_NUMBER_OF_PARCELS = 'NumberOfParcels';
    public const string FIELD_ORDER_REFERENCE = 'OrderReference';
    public const string FIELD_TOTAL_LINE_ITEMS = 'TotalLineItems';
    public const string FIELD_TOTAL_WEIGHT = 'TotalWeight';
    public const string FIELD_TOTAL_AMOUNT = 'TotalAmount';
    public const string FIELD_TOTAL_GST = 'TotalGst';
    public const string FIELD_WEIGHT_UOM = 'WeightUOM';
    public const string FIELD_INVOICE_TYPE = 'InvoiceType';
    public const string FIELD_UPLOAD_ETD = 'UploadETD';
    public const string FIELD_DECLARATION_NOTES = 'DeclarationNotes';
    public const string FIELD_AIRWAYBILL_NUMBER = 'AirwaybillNumber';
    public const string FIELD_CUSTOM_REFERENCE = 'CustomReference';
    public const string FIELD_COUNTRY_OF_EXPORT_DESTINATION = 'CountryOfExportDestination';
    public const string FIELD_SIGNATURE_DATE = 'SignatureDate';
    public const string FIELD_IMPORTER = 'Importer';

    // Party sub-object fields (exact v1.json names).
    public const string FIELD_PARTY_NAME = 'Name';
    public const string FIELD_PARTY_ADDRESS = 'Address';
    public const string FIELD_PARTY_COMPANY = 'Company';
    public const string FIELD_PARTY_ZIPCODE = 'Zipcode';
    public const string FIELD_PARTY_CITY = 'City';
    public const string FIELD_PARTY_COUNTRY = 'Country';
    public const string FIELD_PARTY_TELEPHONE = 'Telephone';
    public const string FIELD_PARTY_VAT_NUMBER = 'VatNumber';
    public const string FIELD_PARTY_EORI = 'Eori';
    public const string FIELD_PARTY_CUSTOM_REFERENCE = 'CustomReference';

    // Goods item fields (exact v1.json names).
    public const string FIELD_GOODS_PARCEL_COUNT = 'ParcelCount';
    public const string FIELD_GOODS_DESCRIPTION = 'Description';
    public const string FIELD_GOODS_HS_CODE = 'HsCode';
    public const string FIELD_GOODS_QUANTITY = 'Quantity';
    public const string FIELD_GOODS_WEIGHT = 'Weight';
    public const string FIELD_GOODS_UNIT_VALUE = 'UnitValue';
    public const string FIELD_GOODS_TYPE_OF_PACKAGING = 'TypeOfPackaging';
    public const string FIELD_GOODS_MARKS = 'Marks';
    public const string FIELD_GOODS_COUNTRY_OF_ORIGIN = 'CountryOfOrigin';
    public const string FIELD_GOODS_QUANTITY_UOM = 'QuantityUOM';
    public const string FIELD_GOODS_GST_PAID = 'GstPaid';

    // Response fields (exact v1.json names).
    public const string FIELD_RESULT = 'Result';
    public const string FIELD_CROSSBORDER_INVOICE = 'CrossborderInvoice';

    /**
     * The v1.json `CreateCrossBorderInvoiceResponse` schema declares no required
     * fields, so no response field is enforced (mirroring the defensive
     * `CreateCrossBorderInvoiceResponseBody`).
     *
     * @var string[]
     */
    public const array REQUIRED_RESPONSE_FIELDS = [];

    public function encodeRequest(CreateCrossBorderInvoiceRequest $request): string
    {
        return json_encode([
            self::FIELD_METHOD => self::METHOD,
            self::FIELD_PARAMS => (object) $this->encodeParams($request),
        ], JSON_THROW_ON_ERROR);
    }

    /**
     * @return array<string, mixed>
     */
    public function encodeParams(CreateCrossBorderInvoiceRequest $request): array
    {
        $params = [
            self::FIELD_INCOTERM => $request->incoterm->value,
            self::FIELD_TYPE_OF_EXPORT => $request->typeOfExport,
            self::FIELD_ORDER_ID => $request->orderId,
            self::FIELD_INVOICE_NUMBER => $request->invoiceNumber,
            self::FIELD_DATE_OF_EXPORTATION => $request->dateOfExportation->value,
            self::FIELD_SHIPPER_EXPORTER => $this->encodeParty($request->shipperExporter),
            self::FIELD_CONSIGNEE => $this->encodeParty($request->consignee),
            self::FIELD_GOODS => $this->encodeGoods($request->goods),
        ];

        if ($request->currency !== null) {
            $params[self::FIELD_CURRENCY] = $request->currency;
        }
        if ($request->numberOfParcels !== null) {
            $params[self::FIELD_NUMBER_OF_PARCELS] = $request->numberOfParcels;
        }
        if ($request->orderReference !== null) {
            $params[self::FIELD_ORDER_REFERENCE] = $request->orderReference;
        }
        if ($request->totalLineItems !== null) {
            $params[self::FIELD_TOTAL_LINE_ITEMS] = $request->totalLineItems;
        }
        if ($request->totalWeight !== null) {
            $params[self::FIELD_TOTAL_WEIGHT] = $request->totalWeight;
        }
        if ($request->totalAmount !== null) {
            $params[self::FIELD_TOTAL_AMOUNT] = $request->totalAmount;
        }
        if ($request->totalGst !== null) {
            $params[self::FIELD_TOTAL_GST] = $request->totalGst;
        }
        if ($request->weightUom !== null) {
            $params[self::FIELD_WEIGHT_UOM] = $request->weightUom;
        }
        if ($request->invoiceType !== null) {
            $params[self::FIELD_INVOICE_TYPE] = $request->invoiceType;
        }
        if ($request->uploadEtd !== null) {
            $params[self::FIELD_UPLOAD_ETD] = $request->uploadEtd;
        }
        if ($request->declarationNotes !== null) {
            $params[self::FIELD_DECLARATION_NOTES] = $request->declarationNotes;
        }
        if ($request->airwaybillNumber !== null) {
            $params[self::FIELD_AIRWAYBILL_NUMBER] = $request->airwaybillNumber;
        }
        if ($request->customReference !== null) {
            $params[self::FIELD_CUSTOM_REFERENCE] = $request->customReference;
        }
        if ($request->countryOfExportDestination !== null) {
            $params[self::FIELD_COUNTRY_OF_EXPORT_DESTINATION] = $request->countryOfExportDestination->value;
        }
        if ($request->signatureDate !== null) {
            $params[self::FIELD_SIGNATURE_DATE] = $request->signatureDate->value;
        }
        if ($request->importer !== null) {
            $params[self::FIELD_IMPORTER] = $this->encodeParty($request->importer);
        }

        return $params;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function mapResponseBody(array $data): CreateCrossBorderInvoiceResponseBody
    {
        $this->ensureRequiredFields($data);

        return new CreateCrossBorderInvoiceResponseBody(
            $this->nullableString($data, self::FIELD_RESULT),
            $this->nullableString($data, self::FIELD_CROSSBORDER_INVOICE),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function encodeParty(InvoiceParty $party): array
    {
        $encoded = [
            self::FIELD_PARTY_NAME => $party->name,
            self::FIELD_PARTY_ADDRESS => $party->address,
            self::FIELD_PARTY_ZIPCODE => $party->zipcode,
            self::FIELD_PARTY_CITY => $party->city,
            self::FIELD_PARTY_COUNTRY => $party->country->value,
            self::FIELD_PARTY_TELEPHONE => $party->telephone,
        ];

        if ($party->company !== null) {
            $encoded[self::FIELD_PARTY_COMPANY] = $party->company;
        }
        if ($party->vatNumber !== null) {
            $encoded[self::FIELD_PARTY_VAT_NUMBER] = $party->vatNumber;
        }
        if ($party->eori !== null) {
            $encoded[self::FIELD_PARTY_EORI] = $party->eori;
        }
        if ($party->customReference !== null) {
            $encoded[self::FIELD_PARTY_CUSTOM_REFERENCE] = $party->customReference;
        }

        return $encoded;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function encodeGoods(CrossBorderGoodsCollection $goods): array
    {
        return array_map(
            static fn (CrossBorderGoodsItem $item): array => [
                self::FIELD_GOODS_PARCEL_COUNT => $item->parcelCount,
                self::FIELD_GOODS_DESCRIPTION => $item->description,
                self::FIELD_GOODS_HS_CODE => $item->hsCode,
                self::FIELD_GOODS_QUANTITY => $item->quantity,
                self::FIELD_GOODS_WEIGHT => $item->weight,
                self::FIELD_GOODS_UNIT_VALUE => $item->unitValue,
                ...($item->typeOfPackaging !== null ? [self::FIELD_GOODS_TYPE_OF_PACKAGING => $item->typeOfPackaging] : []),
                ...($item->marks !== null ? [self::FIELD_GOODS_MARKS => $item->marks] : []),
                ...($item->countryOfOrigin !== null ? [self::FIELD_GOODS_COUNTRY_OF_ORIGIN => $item->countryOfOrigin] : []),
                ...($item->quantityUom !== null ? [self::FIELD_GOODS_QUANTITY_UOM => $item->quantityUom] : []),
                ...($item->gstPaid !== null ? [self::FIELD_GOODS_GST_PAID => $item->gstPaid] : []),
            ],
            $goods->all(),
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    private function ensureRequiredFields(array $data): void
    {
        $missing = [];
        foreach (self::REQUIRED_RESPONSE_FIELDS as $field) {
            if (!array_key_exists($field, $data)) {
                $missing[] = $field;
            }
        }

        if ($missing !== []) {
            throw new MissingRequiredFieldsException($missing);
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    private function nullableString(array $data, string $field): ?string
    {
        return isset($data[$field]) ? (string) $data[$field] : null;
    }
}
