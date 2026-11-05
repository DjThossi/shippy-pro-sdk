<?php

declare(strict_types=1);

namespace DjThossi\ShippyProSdk\Tests\Unit\Api;

use DjThossi\ShippyProSdk\Api\CrossBorderInvoiceApi;
use DjThossi\ShippyProSdk\Domain\CountryCode;
use DjThossi\ShippyProSdk\Domain\CreateCrossBorderInvoiceRequest;
use DjThossi\ShippyProSdk\Domain\CreateCrossBorderInvoiceResponse;
use DjThossi\ShippyProSdk\Domain\CrossBorderGoodsCollection;
use DjThossi\ShippyProSdk\Domain\CrossBorderGoodsItem;
use DjThossi\ShippyProSdk\Domain\DateOfExportation;
use DjThossi\ShippyProSdk\Domain\Incoterm;
use DjThossi\ShippyProSdk\Domain\InvoiceParty;
use DjThossi\ShippyProSdk\Http\Client;
use DjThossi\ShippyProSdk\Mapper\CrossBorderInvoiceMapper;
use DjThossi\ShippyProSdk\Mapper\ErrorMapper;
use DjThossi\ShippyProSdk\Mapper\HeaderMapper;
use GuzzleHttp\Psr7\Response;

use const JSON_THROW_ON_ERROR;

use PHPUnit\Framework\Attributes\DataProvider;

final class CrossBorderInvoiceApiTest extends ApiTestCase
{
    public function testCreateInvoicePostsEnvelopeToTheCrossBorderInvoiceEndpoint(): void
    {
        $client = $this->createMock(Client::class);
        $client->expects($this->once())
            ->method('postV1')
            ->with(
                '/v1/CreateCrossBorderInvoice',
                $this->callback(static function (string $body): bool {
                    $decoded = json_decode($body, true, 512, JSON_THROW_ON_ERROR);

                    return $decoded['Method'] === 'CreateCrossBorderInvoice'
                        && isset($decoded['Params']['OrderId'])
                        && $decoded['Params']['OrderId'] === 'ORDER1';
                }),
            )
            ->willReturn(new Response(200, [], '{"Result":"OK","CrossborderInvoice":"base64=="}'));

        $this->createApi($client)->createInvoice($this->request());
    }

    public function testCreateInvoiceNeverGoesThroughThePublicGetPath(): void
    {
        $client = $this->createMock(Client::class);
        $client->expects($this->never())->method('getV2Public');
        $client->method('postV1')->willReturn(new Response(200, [], '{"Result":"OK","CrossborderInvoice":"base64=="}'));

        $this->createApi($client)->createInvoice($this->request());
    }

    public function testCreateInvoiceMapsSuccessfulResponse(): void
    {
        $result = $this->createInvoiceFor(new Response(
            200,
            ['X-Request-Id' => 'req_cbi_1'],
            '{"Result":"OK","CrossborderInvoice":"base64=="}',
        ));

        $this->assertInstanceOf(CreateCrossBorderInvoiceResponse::class, $result);
        $this->assertSame(200, $result->statusCode->value);
        $this->assertTrue($result->statusCode->isOk());
        $this->assertFalse($result->hasError());
        $this->assertNotNull($result->responseBody);
        $this->assertSame('OK', $result->responseBody->result);
        $this->assertSame('base64==', $result->responseBody->crossborderInvoice);
        $this->assertSame('req_cbi_1', $result->headers->getRequestId());
    }

    public function testCreateInvoiceNormalizesV1ErrorBodyIntoApiError(): void
    {
        $result = $this->createInvoiceFor(new Response(200, [], '{"Error":"Authentication Failed"}'));

        $this->assertTrue($result->hasError());
        $this->assertSame('Authentication Failed', $result->error?->detail);
        $this->assertNull($result->responseBody);
    }

    #[DataProvider('errorCodeProvider')]
    public function testCreateInvoiceSurfacesHttpErrorCodeThroughStatusCode(int $code, string $helper): void
    {
        $result = $this->createInvoiceFor(new Response($code, [], ''));

        $this->assertSame($code, $result->statusCode->value);
        $this->assertFalse($result->statusCode->isSuccessful());
        $this->assertNull($result->responseBody);
        $this->assertTrue($result->statusCode->{$helper}());
    }

    /**
     * @return array<string, array{0: int, 1: string}>
     */
    public static function errorCodeProvider(): array
    {
        return [
            '400 bad request' => [400, 'isBadRequest'],
            '403 forbidden' => [403, 'isForbidden'],
            '429 too many requests' => [429, 'isTooManyRequests'],
            '500 server error' => [500, 'isServerError'],
        ];
    }

    private function createInvoiceFor(Response $response): CreateCrossBorderInvoiceResponse
    {
        $client = $this->createStub(Client::class);
        $client->method('postV1')->willReturn($response);

        return $this->createApi($client)->createInvoice($this->request());
    }

    private function createApi(Client $client): CrossBorderInvoiceApi
    {
        return new CrossBorderInvoiceApi(
            $client,
            new CrossBorderInvoiceMapper(),
            new HeaderMapper(),
            new ErrorMapper(),
        );
    }

    private function request(): CreateCrossBorderInvoiceRequest
    {
        $party = new InvoiceParty(
            'John Doe',
            'Main Street 1',
            '12345',
            'Berlin',
            new CountryCode('DE'),
            '+49 30 1234567',
        );

        $goods = new CrossBorderGoodsCollection(
            new CrossBorderGoodsItem(1, 'T-Shirt', '610910', 2, 0.5, 9.99),
        );

        return new CreateCrossBorderInvoiceRequest(
            Incoterm::Dap,
            'permanent',
            'ORDER1',
            'INV-1',
            new DateOfExportation('2021-05-10'),
            $party,
            $party,
            $goods,
        );
    }
}
