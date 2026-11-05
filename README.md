# ShippyPro SDK

A typed, object-oriented PHP library for the [ShippyPro API](https://api.shippypro.com/docs).

Everything the SDK returns is a typed object — no raw arrays leak into the public API. Arrays live only inside the mappers, at the JSON (de)serialization boundary.

This library supports the following endpoints at the moment:

- `POST /v1/Ping` — Bearer-authenticated connection/auth check of the v1 API.
- `GET /v2/status` — public (unauthenticated) health check of the v2 API.
- `POST /v1/Ship` — create a shipment and get labels, incl. customs (CN22) and DHL/UPS carrier options.

This library is made for PHP projects featuring PHP version 8.5+.

## Installation

```bash
composer require djthossi/shippy-pro-sdk
```

## Usage

### Ping (`POST /v1/Ping`)

```php
use DjThossi\ShippyProSdk\Domain\ApiKey;
use DjThossi\ShippyProSdk\ShippyProFactory;
use DjThossi\ShippyProSdk\SimpleShippyProConfiguration;

// Only the API key is mandatory. The base URL (https://api.shippypro.com)
// and the request timeout (10 seconds) default.
$configuration = new SimpleShippyProConfiguration(new ApiKey('YOUR_API_KEY'));
$factory = new ShippyProFactory($configuration);
$pingApi = $factory->createPingApi();

$response = $pingApi->ping();

if ($response->statusCode->isOk()) {
    echo $response->responseBody->result ? "alive\n" : "not alive\n";
} elseif ($response->statusCode->isTooManyRequests()) {
    echo "rate limited (429)\n";
}
```

### Status (`GET /v2/status`)

This endpoint is public, so no authentication header is sent. The SDK still
requires an `ApiKey` to be configured, but it is never transmitted for this
call.

```php
use DjThossi\ShippyProSdk\Domain\ApiKey;
use DjThossi\ShippyProSdk\ShippyProFactory;
use DjThossi\ShippyProSdk\SimpleShippyProConfiguration;

$configuration = new SimpleShippyProConfiguration(new ApiKey('not-required-for-status'));
$factory = new ShippyProFactory($configuration);
$statusApi = $factory->createStatusApi();

$response = $statusApi->status();

if ($response->statusCode->isOk()) {
    echo "status:  {$response->responseBody->status}\n";
    echo "version: {$response->responseBody->version}\n";
}
```

See the runnable examples in [`examples/`](examples) (`make examples-ping`, `make examples-status`).

### Error handling

HTTP error codes (`400`, `401`, `403`, `404`, `429`, `500`) are **not** thrown.
They are exposed through the `StatusCode` on the response object, so you inspect
the result rather than catching exceptions:

```php
$statusCode = $response->statusCode;

$statusCode->isSuccessful();      // 200–299
$statusCode->isOk();              // 200
$statusCode->isBadRequest();      // 400
$statusCode->isForbidden();       // 403
$statusCode->isNotFound();        // 404
$statusCode->isTooManyRequests(); // 429
$statusCode->isServerError();     // 500
```

Transport-level failures (connection refused, DNS, timeout) are genuinely
exceptional and are not swallowed — Guzzle's `ConnectException` / `RequestException`
propagate to the caller.

### Partner identification

If you integrate ShippyPro on behalf of your users, configure a `PartnerName`.
It is sent as a lowercase `Referer` header on every request.

```php
use DjThossi\ShippyProSdk\Domain\ApiKey;
use DjThossi\ShippyProSdk\Domain\PartnerName;
use DjThossi\ShippyProSdk\SimpleShippyProConfiguration;

$configuration = new SimpleShippyProConfiguration(
    new ApiKey('YOUR_API_KEY'),
    null,
    null,
    new PartnerName('my-platform'),
);
```

## Symfony Integration

This library is framework-agnostic and has no dedicated Symfony bundle. It
integrates into a Symfony application via autowiring.

Add this to the parameter section of your `services.yaml`:

```yaml
parameters:
  # shippy-pro-sdk parameters
  app.SHIPPYPRO_API_KEY: "%env(SHIPPYPRO_API_KEY)%"
  app.SHIPPYPRO_BASE_URL: "%env(SHIPPYPRO_BASE_URL)%"
```

Add this to the services section of your `services.yaml`:

```yaml
#file: noinspection ComposeUnknownKeys
services:
  # shippy-pro-sdk services
  DjThossi\ShippyProSdk\Http\:
    resource: '../vendor/djthossi/shippy-pro-sdk/src/Http'
    bind:
      $baseUrl: '%app.SHIPPYPRO_BASE_URL%'
      $apiKey: '%app.SHIPPYPRO_API_KEY%'

  DjThossi\ShippyProSdk\Api\:
    resource: '../vendor/djthossi/shippy-pro-sdk/src/Api'
    arguments:
      $client: '@DjThossi\ShippyProSdk\Http\Client'
```

Afterward add the credentials to your `.env.local`:

```apacheconf
# shippy-pro-sdk parameters
SHIPPYPRO_API_KEY=your-api-key
SHIPPYPRO_BASE_URL=https://api.shippypro.com
```

Now you can inject each API endpoint directly into your business logic and
Symfony takes care of the autowiring:

```php
<?php
declare(strict_types=1);
namespace App\Controller;

use DjThossi\ShippyProSdk\Api\StatusApi;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class StatusController extends AbstractController
{
    #[Route('/health', name: 'health', methods: ['GET'])]
    public function index(StatusApi $statusApi): Response
    {
        $status = $statusApi->status();

        return new Response($status->responseBody->status);
    }
}
```

## Development

The project ships with a Docker setup and a `Makefile`:

```bash
make setup                     # install dependencies
make phpLatest-test            # run the test suite with coverage (HTML report in html-coverage/)
make phpLatest-test-fast       # run the test suite without coverage
make php-cs-fix                # apply the code style
make examples-ping             # run the ping example
make examples-status           # run the status example
```

### Platform note (Apple Silicon)

The Docker image `ronisaha/php-8-debian:cli-8.5.12` currently publishes tags
only for `linux/amd64`. On `arm64` (Apple Silicon) the image runs under
emulation, which is functional but slower — expect the test suite and Composer
operations to take longer than on a native `amd64` host.

## Rate limit

The ShippyPro API allows **20 calls per 10 seconds**. The SDK does not implement
rate limiting itself; when the limit is exceeded, the API responds with `429`,
which you can observe via `StatusCode::isTooManyRequests()`.

## Cross-border capabilities (CH / GB)

This section documents how the SDK covers cross-border orders to Switzerland
(CH) and Great Britain (GB): which capabilities are expressible on the `Ship`
request, which are carried through `CreateCrossBorderInvoice`, and which have no
ShippyPro equivalent at all.

### Capability mapping

The cross-border capabilities and their resolution in this SDK:

| Capability | Resolution | SDK field / object |
|---|---|---|
| carrier selection | covered | `CarrierName` / `CarrierService` / `CarrierID` (`ShipRequest` + `ShipMapper`) |
| recipient address | covered | `Domain\Address` (+ `CountryCode`) |
| package | covered | `Parcel` / `ParcelCollection` |
| reference number | covered (caller-supplied) | `ShipRequest.orderId` / note fields |
| additional services | covered | `CarrierOption` / `CarrierOptionCollection` |
| billing | partial | `Customs.billAccountNumber` (EXW only); third-party duties/taxes not supported |
| customs declaration | covered (line items) + tax / reference via invoice | `Customs` / `Cn22Item` (lines); `CreateCrossBorderInvoice` (tax numbers, exporter reference) |
| incoterm | covered | `Incoterm` enum |

For cross-border CH/GB shipments, the cross-border classification and the
mandatory declared total value are enforced by `ShipRequest::isCrossBorder()`
together with the total-value invariant. The declared total value is sent to
ShippyPro as the single `TotalValue` field (a `MoneyAmount`).

### Fields covered through `CreateCrossBorderInvoice`

The following three fields have **no** equivalent on the ShippyPro `Ship`
request. They are carried instead through the `CreateCrossBorderInvoice` method:

| Field | ShippyPro target | SDK location |
|---|---|---|
| sender tax number (e.g. `DE111111111`) | `ShipperExporter.VatNumber` | `InvoiceParty.vatNumber` (shipper/exporter) |
| receiver tax number (e.g. `GB123456789000`, `CHE123456789`) | `Consignee.VatNumber` | `InvoiceParty.vatNumber` (consignee) |
| exporter reference (e.g. `CHE-123.456.789 MWST`) | `ShipperExporter.CustomReference` | `InvoiceParty.customReference` (shipper/exporter) |

On the `Ship` request the sender tax identity is sourced by ShippyPro from the
account profile; `CreateCrossBorderInvoice` carries it per request instead. A
per-shipment IOSS number remains available on `Ship` through the `sp_ioss`
attribute (`ParcelAttributes.spIoss`).

### Genuine limitations (never sent on the `Ship` request)

The following five customs fields have no ShippyPro equivalent and are therefore
never sent on the `Ship` request:

- `duties_and_taxes` third-party billing — ShippyPro's only `Ship` billing
  concept is `BillAccountNumber`, which is permitted only with the `EXW` incoterm
  and is not equivalent to a separate third-party duties/taxes account. A UPS
  `transportation` third-party account is subject to the same limitation.
- `contents_explanation`
- `drop_off_location`
- `posting_date`
- `additional_fees`

### ZPL label dimensions (centimetres)

Carrier-specific ZPL label formats are expressed through `LabelFormatting` /
`LabelType`. Inch-based ZPL sizes are sent to ShippyPro in centimetres, rounded
to two decimal places:

- DHL `zpl2_103x199mm_203dpi` ≈ **10.30 × 19.90 cm**
- UPS `zpl2_4x8in_203dpi` (4 × 8 in) ≈ **10.16 × 20.32 cm**

`LabelFormatting` performs no range validation on width or height; the concrete
ZPL sizes above are expressible without any check.

## Non-goals (current iteration)

- No endpoints other than `POST /v1/Ping` and `GET /v2/status`.
- No dedicated Symfony bundle — autowiring only, as documented above.
- No asynchronous or parallel requests.
- No built-in rate limiting (the 20-calls-per-10-seconds limit is documented, not enforced).

## Contribute to this repo
For more details click [here](CONTRIBUTING.md)
