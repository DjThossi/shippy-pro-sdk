<?php

declare(strict_types=1);

namespace DjThossi\ShippyProSdk\Tests\Unit\Api;

use DjThossi\ShippyProSdk\Api\PaperlessDocumentationApi;
use DjThossi\ShippyProSdk\Domain\CountryCode;
use DjThossi\ShippyProSdk\Domain\PaperlessDocumentType;
use DjThossi\ShippyProSdk\Domain\UploadPaperlessDocumentationRequest;
use DjThossi\ShippyProSdk\Domain\UploadPaperlessDocumentationResponse;
use DjThossi\ShippyProSdk\Exception\InvalidPaperlessDocumentationException;
use DjThossi\ShippyProSdk\Exception\PaperlessDocumentLoadException;
use DjThossi\ShippyProSdk\Http\Client;
use DjThossi\ShippyProSdk\Mapper\ErrorMapper;
use DjThossi\ShippyProSdk\Mapper\HeaderMapper;
use DjThossi\ShippyProSdk\Mapper\PaperlessDocumentationMapper;
use GuzzleHttp\Psr7\Response;

use const JSON_THROW_ON_ERROR;

use function strlen;

final class PaperlessDocumentationApiTest extends ApiTestCase
{
    private const string DOCUMENT = 'JVBERi0xLjQK';

    public function testUploadSendsEnvelopePostToTheEndpoint(): void
    {
        $client = $this->createMock(Client::class);
        $client->expects($this->once())
            ->method('postV1')
            ->with(
                '/v1/UploadPaperlessDocumentation',
                $this->callback(static function (string $body): bool {
                    $decoded = json_decode($body, true, 512, JSON_THROW_ON_ERROR);

                    return $decoded === [
                        'Method' => 'UploadPaperlessDocumentation',
                        'Params' => [
                            'TransactionID' => 'CH2225032 1xTE0207-200300-Y',
                            'Name' => 'Sebastian Schweizer',
                            'Country' => 'CH',
                            'Document' => 'JVBERi0xLjQK',
                            'DocumentType' => 0,
                        ],
                    ];
                }),
            )
            ->willReturn(new Response(200, [], '{"Result":"OK","Message":""}'));

        $this->createApi($client)->uploadPaperlessDocumentation($this->request(PaperlessDocumentType::Invoice));
    }

    public function testDocumentTypeIsOmittedWhenNull(): void
    {
        $body = (new PaperlessDocumentationMapper())->encodeRequest($this->request());

        $this->assertArrayNotHasKey('DocumentType', json_decode($body, true, 512, JSON_THROW_ON_ERROR)['Params']);
    }

    public function testOtherDocumentTypeIsEncodedAs99(): void
    {
        $body = (new PaperlessDocumentationMapper())->encodeRequest($this->request(PaperlessDocumentType::Other));

        $this->assertSame(99, json_decode($body, true, 512, JSON_THROW_ON_ERROR)['Params']['DocumentType']);
    }

    public function testMapsSuccessfulResponse(): void
    {
        $result = $this->uploadFor(new Response(200, ['X-Request-Id' => 'req_pl_1'], '{"Result":"OK","Message":""}'));

        $this->assertTrue($result->statusCode->isOk());
        $this->assertFalse($result->hasError());
        $this->assertSame('req_pl_1', $result->headers->getRequestId());
        $this->assertSame('OK', $result->responseBody?->result);
    }

    public function testNormalizesErrorBodyIntoApiError(): void
    {
        $result = $this->uploadFor(new Response(200, [], '{"Error":"Invalid document"}'));

        $this->assertTrue($result->hasError());
        $this->assertSame('Invalid document', $result->error?->detail);
        $this->assertNull($result->responseBody);
    }

    public function testSurfacesHttpErrorCodeThroughStatusCode(): void
    {
        $result = $this->uploadFor(new Response(401, [], ''));

        $this->assertSame(401, $result->statusCode->value);
        $this->assertNull($result->responseBody);
    }

    public function testMissingResultMapsToNull(): void
    {
        $result = $this->uploadFor(new Response(200, [], '{}'));

        $this->assertNull($result->responseBody?->result);
    }

    public function testEmptyTransactionIdIsRejected(): void
    {
        $this->expectException(InvalidPaperlessDocumentationException::class);

        new UploadPaperlessDocumentationRequest(' ', 'Name', new CountryCode('CH'), self::DOCUMENT);
    }

    public function testTooLongTransactionIdIsRejected(): void
    {
        $this->expectException(InvalidPaperlessDocumentationException::class);

        new UploadPaperlessDocumentationRequest(str_repeat('a', 256), 'Name', new CountryCode('CH'), self::DOCUMENT);
    }

    public function testMaxLengthTransactionIdIsAccepted(): void
    {
        $request = new UploadPaperlessDocumentationRequest(str_repeat('a', 255), 'Name', new CountryCode('CH'), self::DOCUMENT);

        $this->assertSame(255, strlen($request->transactionId));
    }

    public function testEmptyNameIsRejected(): void
    {
        $this->expectException(InvalidPaperlessDocumentationException::class);

        new UploadPaperlessDocumentationRequest('1', '', new CountryCode('CH'), self::DOCUMENT);
    }

    public function testEmptyDocumentIsRejected(): void
    {
        $this->expectException(InvalidPaperlessDocumentationException::class);

        new UploadPaperlessDocumentationRequest('1', 'Name', new CountryCode('CH'), '');
    }

    public function testNonBase64DocumentIsRejected(): void
    {
        $this->expectException(InvalidPaperlessDocumentationException::class);

        new UploadPaperlessDocumentationRequest('1', 'Name', new CountryCode('CH'), '%%% not base64 %%%');
    }

    public function testUploadFromFileReadsAndBase64EncodesTheFile(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'etd');
        file_put_contents($path, '%PDF-1.4 test');

        try {
            $client = $this->createMock(Client::class);
            $client->expects($this->once())
                ->method('postV1')
                ->with(
                    '/v1/UploadPaperlessDocumentation',
                    $this->callback(static fn (string $body): bool => json_decode($body, true, 512, JSON_THROW_ON_ERROR)['Params']['Document'] === base64_encode('%PDF-1.4 test')),
                )
                ->willReturn(new Response(200, [], '{"Result":"OK"}'));

            $result = $this->createApi($client)->uploadPaperlessDocumentationFromFile($path, '1', 'Name', new CountryCode('CH'));

            $this->assertSame('OK', $result->responseBody?->result);
        } finally {
            unlink($path);
        }
    }

    public function testUploadFromFileThrowsForMissingFile(): void
    {
        $this->expectException(PaperlessDocumentLoadException::class);

        $this->createApi($this->createStub(Client::class))
            ->uploadPaperlessDocumentationFromFile('/does/not/exist.pdf', '1', 'Name', new CountryCode('CH'));
    }

    public function testUploadFromFileThrowsForEmptyFile(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'etd');

        try {
            $this->expectException(PaperlessDocumentLoadException::class);

            $this->createApi($this->createStub(Client::class))
                ->uploadPaperlessDocumentationFromFile($path, '1', 'Name', new CountryCode('CH'));
        } finally {
            unlink($path);
        }
    }

    public function testUploadFromUrlDownloadsAndBase64EncodesTheDocument(): void
    {
        $client = $this->createMock(Client::class);
        $client->expects($this->once())
            ->method('download')
            ->with('https://example.com/invoice.pdf')
            ->willReturn(new Response(200, [], '%PDF-1.4 remote'));
        $client->expects($this->once())
            ->method('postV1')
            ->with(
                '/v1/UploadPaperlessDocumentation',
                $this->callback(static fn (string $body): bool => json_decode($body, true, 512, JSON_THROW_ON_ERROR)['Params']['Document'] === base64_encode('%PDF-1.4 remote')),
            )
            ->willReturn(new Response(200, [], '{"Result":"OK"}'));

        $result = $this->createApi($client)->uploadPaperlessDocumentationFromUrl(
            'https://example.com/invoice.pdf',
            '1',
            'Name',
            new CountryCode('CH'),
            PaperlessDocumentType::CommercialInvoice,
        );

        $this->assertSame('OK', $result->responseBody?->result);
    }

    public function testUploadFromUrlThrowsOnFailedDownloadAndDoesNotUpload(): void
    {
        $client = $this->createMock(Client::class);
        $client->method('download')->willReturn(new Response(404, [], 'nope'));
        $client->expects($this->never())->method('postV1');

        $this->expectException(PaperlessDocumentLoadException::class);

        $this->createApi($client)->uploadPaperlessDocumentationFromUrl('https://example.com/x.pdf', '1', 'Name', new CountryCode('CH'));
    }

    public function testUploadFromUrlThrowsOnEmptyDownload(): void
    {
        $client = $this->createStub(Client::class);
        $client->method('download')->willReturn(new Response(200, [], ''));

        $this->expectException(PaperlessDocumentLoadException::class);

        $this->createApi($client)->uploadPaperlessDocumentationFromUrl('https://example.com/x.pdf', '1', 'Name', new CountryCode('CH'));
    }

    public function testUploadFromUrlRejectsNonHttpUrl(): void
    {
        $client = $this->createMock(Client::class);
        $client->expects($this->never())->method('download');

        $this->expectException(InvalidPaperlessDocumentationException::class);

        $this->createApi($client)->uploadPaperlessDocumentationFromUrl('file:///etc/passwd', '1', 'Name', new CountryCode('CH'));
    }

    private function request(?PaperlessDocumentType $type = null): UploadPaperlessDocumentationRequest
    {
        return new UploadPaperlessDocumentationRequest(
            'CH2225032 1xTE0207-200300-Y',
            'Sebastian Schweizer',
            new CountryCode('CH'),
            self::DOCUMENT,
            $type,
        );
    }

    private function uploadFor(Response $response): UploadPaperlessDocumentationResponse
    {
        $client = $this->createStub(Client::class);
        $client->method('postV1')->willReturn($response);

        return $this->createApi($client)->uploadPaperlessDocumentation($this->request());
    }

    private function createApi(Client $client): PaperlessDocumentationApi
    {
        return new PaperlessDocumentationApi($client, new PaperlessDocumentationMapper(), new HeaderMapper(), new ErrorMapper());
    }
}
