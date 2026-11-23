# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

### Fixed

### Changed

### Removed

### Breaking Changes

## [1.1.0] - 2026-11-23

### Added
- Support for the `POST /v1/GetOrder` endpoint via `GetOrderApi::getOrder(GetOrderRequest): GetOrderResponse` and `ShippyProFactory::createGetOrderApi()`, returning order/shipment details (tracking carrier/number/link, additional tracking numbers, marketplace platform, tracking status and a typed `LabelUrlCollection`). Error bodies such as `{"Error": "No orders found"}` are surfaced through `GetOrderResponse::$error`; empty `ErrorType`/`ErrorSuggestion` objects map to `null`.
- `examples/get-order.php` plus the `make examples-get-order` target.

### Fixed

### Changed

### Removed

### Breaking Changes

## [1.0.1] - 2026-11-21

### Added
- `ShipRequest::$async` (`?bool`, default `null`) mapped to the `Async` field of the `Ship` request. ShippyPro handles `Ship` asynchronously by default, so labels may not be part of the response; pass `async: false` to force synchronous label generation. When `null`, the field is omitted and the API default applies.

### Fixed

### Changed

### Removed

### Breaking Changes

## [1.0.0] - 2026-11-05

### Added
- Support for the `POST /v1/CreateCrossBorderInvoice` endpoint via `CrossBorderInvoiceApi::createInvoice(CreateCrossBorderInvoiceRequest): CreateCrossBorderInvoiceResponse` and `ShippyProFactory::createCrossBorderInvoiceApi()`, carrying the structured tax identifiers and exporter reference that the `Ship` request cannot express: a reusable `InvoiceParty` (ShipperExporter/Consignee/Importer) with `VatNumber`/`Eori`/`CustomReference`, `CrossBorderGoodsItem`/`CrossBorderGoodsCollection`, and a `DateOfExportation` value object. Maps shipcloud `sender_tax_number` -> `ShipperExporter.VatNumber`, `receiver_tax_number` -> `Consignee.VatNumber` and `exporter_reference` -> `ShipperExporter.CustomReference`.
- `examples/cross-border.php` plus the `make examples-cross-border` target demonstrating DE->CH and DE->GB cross-border shipments, each combining a `Ship` call (label + CN22 customs line items + incoterm) with a `CreateCrossBorderInvoice` call (tax identifiers + exporter reference).
- A gap-analysis section in `README.md` documenting the shipcloud-to-ShippyPro capability mapping, the fields now covered through `CreateCrossBorderInvoice`, and the fields with no ShippyPro equivalent (`duties_and_taxes`/`transportation` third-party accounts, `contents_explanation`, `drop_off_location`, `posting_date`, `additional_fees`).
- Support for the `POST /v1/Ship` endpoint via `ShipApi::ship(ShipRequest): ShipResponse` and `ShippyProFactory::createShipApi()`, with typed request/response modeling: `Address`/`CountryCode`, `Parcel`/`ParcelCollection`, `MoneyAmount` (TotalValue), `Insurance`, `CashOnDelivery`, a `Customs` block with `Cn22Item`/`Cn22ItemCollection` and the `Incoterm`/`CustomInfoContentsType`/`NonDeliveryOption` enums, `CarrierOption`/`CarrierOptionCollection` with documented DHL/UPS helper constants, `LabelFormatting` (PDF/ZPL, cm dimensions, rotate/split/page), and a `ShipResponse` that normalizes the polymorphic `LabelURL` into a typed `LabelUrlCollection`.
- `examples/ship.php` plus the `make examples-ship` target demonstrating a cross-border shipment with customs data.
- Support for the `POST /v1/Ping` endpoint (Bearer-authenticated connection/auth check).
- Support for the `GET /v2/status` endpoint (public, unauthenticated health check).
- Typed access to HTTP response headers via `ResponseHeaders` (`getRequestId()`, `getRateLimit()`, generic `get()`/`has()`/`all()`) and a `RateLimit` value object that reads both the legacy `X-RateLimit-*` headers and the IETF-draft `RateLimit` / `RateLimit-Policy` headers.
- A unified `ApiError` error container (with `FieldError` for field-level details) that normalizes v1 body errors (`{"Error": ...}` and `{"Result":"NOK","ErrorMessage":...}`) and v2 RFC 9457 `ProblemDetail` responses into one shape.
- `PingResponse` and `StatusResponse` now expose `headers` (always) and `error` (nullable, with `hasError()`), so callers can reach the request id, rate-limit state and any API error regardless of endpoint version.

### Fixed
- `PingApi::ping()` no longer throws `MissingRequiredFieldsException` when the API answers with HTTP 200 but no `Result` field (for example `{"Error":"Authentication Failed"}` on a failed authentication). Per `v1.json` the `Result` field is optional, so an absent value now maps to `PingResponseBody::$result === null` instead of raising an exception.

### Changed
- `PingResponseBody::$result` is now nullable (`?bool`). It is `null` when the API omits the `Result` field; callers check `$result === true` for a successful Ping.
- `PingResponse` and `StatusResponse` constructors now take `ResponseHeaders` as the second argument and an optional `ApiError` as the last argument. `StatusResponse::$responseBody` is now nullable (`null` on error / non-200 responses).
- `PingApi` and `StatusApi` constructors now additionally require a `HeaderMapper` and an `ErrorMapper` (wired automatically by `ShippyProFactory`).
- Dev dependencies updated to their latest versions: `phpunit/phpunit` to `^13.4` (from `^11.5`), `friendsofphp/php-cs-fixer` to `^3.95`, and `giorgiosironi/eris` to `^1.3`. The `phpunit.xml.dist` schema was migrated to 13.4, and the Api tests now use test stubs (instead of mocks without expectations) to satisfy PHPUnit 13's stricter mock checks.

### Removed

### Breaking Changes

[unreleased]: https://github.com/DjThossi/shippy-pro-sdk/compare/v1.1.0...HEAD
[1.1.0]: https://github.com/DjThossi/shippy-pro-sdk/compare/v1.0.1...v1.1.0
[1.0.1]: https://github.com/DjThossi/shippy-pro-sdk/compare/v1.0.0...v1.0.1
[1.0.0]: https://github.com/DjThossi/shippy-pro-sdk/releases/tag/v1.0.0
