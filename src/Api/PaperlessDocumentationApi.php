<?php

declare(strict_types=1);

namespace DjThossi\ShippyProSdk\Api;

use function base64_encode;

use DjThossi\ShippyProSdk\Domain\CountryCode;
use DjThossi\ShippyProSdk\Domain\PaperlessDocumentType;
use DjThossi\ShippyProSdk\Domain\StatusCode;
use DjThossi\ShippyProSdk\Domain\UploadPaperlessDocumentationRequest;
use DjThossi\ShippyProSdk\Domain\UploadPaperlessDocumentationResponse;
use DjThossi\ShippyProSdk\Exception\InvalidPaperlessDocumentationException;
use DjThossi\ShippyProSdk\Exception\PaperlessDocumentLoadException;
use DjThossi\ShippyProSdk\Http\Client;
use DjThossi\ShippyProSdk\Mapper\ErrorMapper;
use DjThossi\ShippyProSdk\Mapper\HeaderMapper;
use DjThossi\ShippyProSdk\Mapper\PaperlessDocumentationMapper;

use function file_get_contents;

use GuzzleHttp\Exception\GuzzleException;

use function in_array;
use function is_array;
use function is_file;
use function is_readable;
use function json_decode;

use const JSON_THROW_ON_ERROR;

use function parse_url;

use const PHP_URL_SCHEME;

use function sprintf;
use function strtolower;
use function trim;

readonly class PaperlessDocumentationApi
{
    private const string ENDPOINT = '/v1/UploadPaperlessDocumentation';

    public function __construct(
        private Client $client,
        private PaperlessDocumentationMapper $mapper,
        private HeaderMapper $headerMapper,
        private ErrorMapper $errorMapper,
    ) {
    }

    public function uploadPaperlessDocumentation(UploadPaperlessDocumentationRequest $request): UploadPaperlessDocumentationResponse
    {
        $response = $this->client->postV1(self::ENDPOINT, $this->mapper->encodeRequest($request));
        $statusCode = new StatusCode($response->getStatusCode());
        $headers = $this->headerMapper->mapResponseHeaders($response);

        $data = $this->decodeBody((string) $response->getBody());

        $error = $this->errorMapper->mapFromArray($data);
        if ($error !== null || !$statusCode->isOk()) {
            return new UploadPaperlessDocumentationResponse($statusCode, $headers, null, $error);
        }

        return new UploadPaperlessDocumentationResponse($statusCode, $headers, $this->mapper->mapResponseBody($data));
    }

    /**
     * Reads the file, base64 encodes it and uploads it.
     *
     * @throws InvalidPaperlessDocumentationException for invalid transactionId/name
     * @throws PaperlessDocumentLoadException         if the file does not exist or cannot be read
     */
    public function uploadPaperlessDocumentationFromFile(
        string $filePath,
        string $transactionId,
        string $name,
        CountryCode $country,
        ?PaperlessDocumentType $documentType = null,
    ): UploadPaperlessDocumentationResponse {
        if (!is_file($filePath) || !is_readable($filePath)) {
            throw new PaperlessDocumentLoadException(sprintf('File "%s" does not exist or is not readable', $filePath));
        }

        $content = file_get_contents($filePath);
        if ($content === false) {
            throw new PaperlessDocumentLoadException(sprintf('File "%s" could not be read', $filePath));
        }

        return $this->uploadContent($content, $transactionId, $name, $country, $documentType);
    }

    /**
     * Downloads the document from the given http(s) URL, base64 encodes it and
     * uploads it. No ShippyPro credentials are sent to the download host.
     *
     * @throws InvalidPaperlessDocumentationException for an invalid URL, transactionId or name
     * @throws PaperlessDocumentLoadException         if the download fails or returns a non-2xx status/empty body
     */
    public function uploadPaperlessDocumentationFromUrl(
        string $downloadUrl,
        string $transactionId,
        string $name,
        CountryCode $country,
        ?PaperlessDocumentType $documentType = null,
    ): UploadPaperlessDocumentationResponse {
        $scheme = parse_url(trim($downloadUrl), PHP_URL_SCHEME);
        if ($scheme === null || $scheme === false || !in_array(strtolower($scheme), ['http', 'https'], true)) {
            throw new InvalidPaperlessDocumentationException('Download URL must be an http or https URL');
        }

        try {
            $response = $this->client->download(trim($downloadUrl));
        } catch (GuzzleException $exception) {
            throw new PaperlessDocumentLoadException(sprintf('Document could not be downloaded from "%s": %s', $downloadUrl, $exception->getMessage()), 0, $exception);
        }

        if (!new StatusCode($response->getStatusCode())->isSuccessful()) {
            throw new PaperlessDocumentLoadException(sprintf('Document download from "%s" failed with HTTP status %d', $downloadUrl, $response->getStatusCode()));
        }

        return $this->uploadContent((string) $response->getBody(), $transactionId, $name, $country, $documentType);
    }

    private function uploadContent(
        string $content,
        string $transactionId,
        string $name,
        CountryCode $country,
        ?PaperlessDocumentType $documentType,
    ): UploadPaperlessDocumentationResponse {
        if ($content === '') {
            throw new PaperlessDocumentLoadException('Document content is empty');
        }

        return $this->uploadPaperlessDocumentation(
            new UploadPaperlessDocumentationRequest($transactionId, $name, $country, base64_encode($content), $documentType),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeBody(string $body): array
    {
        if ($body === '') {
            return [];
        }

        $data = json_decode($body, true, 512, JSON_THROW_ON_ERROR);

        return is_array($data) ? $data : [];
    }
}
