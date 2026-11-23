<?php

declare(strict_types=1);

namespace DjThossi\ShippyProSdk\Tests\Unit\Api;

use DjThossi\ShippyProSdk\Api\GetOrderApi;
use DjThossi\ShippyProSdk\Domain\GetOrderRequest;
use DjThossi\ShippyProSdk\Domain\GetOrderResponse;
use DjThossi\ShippyProSdk\Http\Client;
use DjThossi\ShippyProSdk\Mapper\ErrorMapper;
use DjThossi\ShippyProSdk\Mapper\GetOrderMapper;
use DjThossi\ShippyProSdk\Mapper\HeaderMapper;
use GuzzleHttp\Psr7\Response;

use const JSON_THROW_ON_ERROR;

final class GetOrderApiTest extends ApiTestCase
{
    private const string SUCCESS_BODY = '{"Result":"OK","OrderID":"385590136","TransactionID":"CH2225446 1xJI0196-90160-Y",'
        . '"CarrierID":"300","TrackingCarrier":"DHLParcelDEShipping","TrackingNumber":"CB125240438DE",'
        . '"TrackingExternalLink":"https:\/\/www.dhl.com\/track?id=CB125240438DE","AdditionalTrackingNumbers":[],'
        . '"MarketplacePlatform":"Manual shipment","Status":"1",'
        . '"LabelURL":["https:\/\/cdn.shippypro.com\/a.zpl","https:\/\/cdn.shippypro.com\/b.pdf"],'
        . '"ErrorMessage":null,"ErrorType":{},"ErrorSuggestion":{}}';

    public function testGetOrderSendsEnvelopePostToTheGetOrderEndpoint(): void
    {
        $client = $this->createMock(Client::class);
        $client->expects($this->once())
            ->method('postV1')
            ->with(
                '/v1/GetOrder',
                $this->callback(static function (string $body): bool {
                    $decoded = json_decode($body, true, 512, JSON_THROW_ON_ERROR);

                    return $decoded === ['Method' => 'GetOrder', 'Params' => ['OrderID' => 385590136]];
                }),
            )
            ->willReturn(new Response(200, [], self::SUCCESS_BODY));

        $this->createApi($client)->getOrder(new GetOrderRequest(385590136));
    }

    public function testGetOrderMapsSuccessfulResponse(): void
    {
        $result = $this->getOrderFor(new Response(200, ['X-Request-Id' => 'req_go_1'], self::SUCCESS_BODY));

        $this->assertTrue($result->statusCode->isOk());
        $this->assertFalse($result->hasError());
        $this->assertSame('req_go_1', $result->headers->getRequestId());

        $body = $result->responseBody;
        $this->assertNotNull($body);
        $this->assertSame('OK', $body->result);
        $this->assertSame('385590136', $body->orderId);
        $this->assertSame('CH2225446 1xJI0196-90160-Y', $body->transactionId);
        $this->assertSame('300', $body->carrierId);
        $this->assertSame('DHLParcelDEShipping', $body->trackingCarrier);
        $this->assertSame('CB125240438DE', $body->trackingNumber);
        $this->assertSame('https://www.dhl.com/track?id=CB125240438DE', $body->trackingExternalLink);
        $this->assertSame([], $body->additionalTrackingNumbers);
        $this->assertSame('Manual shipment', $body->marketplacePlatform);
        $this->assertSame('1', $body->status);
        $this->assertSame(
            ['https://cdn.shippypro.com/a.zpl', 'https://cdn.shippypro.com/b.pdf'],
            $body->labelUrls->all(),
        );
        $this->assertNull($body->errorMessage);
        $this->assertNull($body->errorType);
        $this->assertNull($body->errorSuggestion);
        $this->assertFalse($body->hasBusinessError());
    }

    public function testGetOrderNormalizesNoOrdersFoundIntoApiError(): void
    {
        $result = $this->getOrderFor(new Response(200, [], '{"Error":"No orders found"}'));

        $this->assertTrue($result->hasError());
        $this->assertSame('No orders found', $result->error?->detail);
        $this->assertNull($result->responseBody);
    }

    public function testGetOrderSurfacesHttpErrorCodeThroughStatusCode(): void
    {
        $result = $this->getOrderFor(new Response(401, [], ''));

        $this->assertSame(401, $result->statusCode->value);
        $this->assertNull($result->responseBody);
    }

    public function testGetOrderMapsErrorDetails(): void
    {
        $result = $this->getOrderFor(new Response(
            200,
            [],
            '{"Result":"OK","ErrorMessage":"Boom","ErrorType":{"code":"C","name":"N","typeDescription":"D"},'
            . '"ErrorSuggestion":{"errorDescription":"E","solution":"S"}}',
        ));

        $body = $result->responseBody;
        $this->assertNotNull($body);
        $this->assertTrue($body->hasBusinessError());
        $this->assertSame('C', $body->errorType?->code);
        $this->assertSame('S', $body->errorSuggestion?->solution);
        $this->assertTrue($body->labelUrls->isEmpty());
    }

    private function getOrderFor(Response $response): GetOrderResponse
    {
        $client = $this->createStub(Client::class);
        $client->method('postV1')->willReturn($response);

        return $this->createApi($client)->getOrder(new GetOrderRequest(385590136));
    }

    private function createApi(Client $client): GetOrderApi
    {
        return new GetOrderApi($client, new GetOrderMapper(), new HeaderMapper(), new ErrorMapper());
    }
}
