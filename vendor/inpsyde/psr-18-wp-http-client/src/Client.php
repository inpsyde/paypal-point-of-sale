<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Inpsyde\Wp\HttpClient;

use Syde\Vendor\Zettle\Inpsyde\Wp\HttpClient\Exception\NetworkException;
use Syde\Vendor\Zettle\Inpsyde\Wp\HttpClient\Exception\RequestException;
use Syde\Vendor\Zettle\Psr\Http\Client\ClientInterface;
use Syde\Vendor\Zettle\Psr\Http\Message\RequestInterface;
use Syde\Vendor\Zettle\Psr\Http\Message\ResponseFactoryInterface;
use Syde\Vendor\Zettle\Psr\Http\Message\ResponseInterface;
use Syde\Vendor\Zettle\Psr\Http\Message\RequestFactoryInterface;
use Syde\Vendor\Zettle\Psr\Http\Message\StreamFactoryInterface;
use Syde\Vendor\Zettle\Psr\Http\Message\StreamInterface;
use WP_Http;
use WP_Http_Cookie;
use function wp_remote_retrieve_response_code;
use function wp_remote_retrieve_response_message;
/**
 * This class purpose is to send PSR-7 requests and return PSR-7 responses
 *
 * This class implements PSR-18 standard. Mostly this class relies on {@link WP_Http::request()}.
 * See README.md for more installation and usage details.
 */
class Client implements ClientInterface
{
    protected WP_Http $wpHttp;
    protected RequestFactoryInterface $requestFactory;
    protected ResponseFactoryInterface $responseFactory;
    protected StreamFactoryInterface $streamFactory;
    /** @var array<string, mixed> */
    protected array $clientOptions;
    /**
     * @param WP_Http $wpHttp WordPress class instance to make actual requests
     * @param RequestFactoryInterface $requestFactory The factory that creates requests
     * @param ResponseFactoryInterface $responseFactory The factory that creates responses
     * @param StreamFactoryInterface $streamFactory The factory that creates streams
     * @param array $clientOptions Client options will be passed to {@link WP_Http::request()}
     */
    public function __construct(WP_Http $wpHttp, RequestFactoryInterface $requestFactory, ResponseFactoryInterface $responseFactory, StreamFactoryInterface $streamFactory, array $clientOptions = [])
    {
        $this->wpHttp = $wpHttp;
        $this->requestFactory = $requestFactory;
        $this->responseFactory = $responseFactory;
        $this->streamFactory = $streamFactory;
        $this->clientOptions = $clientOptions;
    }
    /**
     * @inheritDoc
     */
    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $target = (string) $request->getUri();
        $target = trim($target);
        if (strlen($target) === 0) {
            // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
            throw new RequestException('URI is empty', 0, null, $request);
        }
        $result = $this->wpHttp->request($target, $this->getRequestArgs($request));
        if (is_wp_error($result)) {
            // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
            throw new NetworkException($result->get_error_message(), 0, null, $request);
        }
        return $this->prepareResponse($result);
    }
    /**
     * Returns arguments array extracted from PSR-7 request
     *
     * Extracts data from PSR-7 request to be used to send request with {@link WP_Http::request()},
     * so the keys of the returned array are the same as described in the provided method documentation
     *
     * @param RequestInterface $request Request do get data from
     *
     * @return array The arguments for {@link WP_Http::request()}
     */
    protected function getRequestArgs(RequestInterface $request): array
    {
        $args = [
            'method' => strtoupper($request->getMethod()),
            //forced http 1.0 because we don't support responses with 1.x.x code, which is required by PSR-18
            'httpversion' => '1.0',
            'blocking' => \true,
            // forced true because we don't support asynchronous requests for now
            'headers' => $this->getFormattedHeadersFromRequest($request),
            'cookies' => $request->getHeader('Cookie'),
            'body' => (string) $request->getBody(),
        ];
        return array_merge($this->clientOptions, $args);
    }
    protected function getFormattedHeadersFromRequest(RequestInterface $request): array
    {
        $headers = [];
        foreach ($request->getHeaders() as $headerName => $headerValue) {
            $headers[$headerName] = $request->getHeaderLine($headerName);
        }
        return $headers;
    }
    /**
     * Create PSR-7 response from response data array
     *
     * Takes result array returned by {@link WP_Http::request()}
     * and converts it to PSR-7-compatible response
     *
     * @param array $result Response data returned by {@link WP_Http::request()}
     *
     * @return ResponseInterface PSR-7 response created from response data array
     */
    protected function prepareResponse(array $result): ResponseInterface
    {
        $code = wp_remote_retrieve_response_code($result);
        $reasonPhrase = wp_remote_retrieve_response_message($result);
        $response = $this->responseFactory->createResponse($code, $reasonPhrase);
        $response = $this->setResponseBodyFromWpResponseData($response, $result);
        $response = $this->setResponseHeadersFromWpResponseData($response, $result);
        return $response;
    }
    /**
     * Set body to PSR-7 response from data array
     *
     * Take the response body content from array returned by {@link WP_Http::request()}
     * and set it to PSR-7 response
     *
     * @param ResponseInterface $response PSR-7 response object to set body to
     * @param array $result Array to take body content from
     *
     * @return ResponseInterface PSR-7 response with added body
     */
    protected function setResponseBodyFromWpResponseData(ResponseInterface $response, array $result): ResponseInterface
    {
        $bodyContent = (string) $result['body'];
        $stream = $this->createStream($bodyContent);
        return $response->withBody($stream);
    }
    /**
     * Create StreamInterface from string
     *
     * @param string $content String to create stream from
     *
     * @return StreamInterface Created stream
     */
    protected function createStream(string $content): StreamInterface
    {
        return $this->streamFactory->createStream($content);
    }
    /**
     * Set headers to PSR-7 response
     *
     * Take the response headers (including cookies)
     * from response data array returned by {@link WP_Http::request()}
     * and set it to the provided PSR-7 response
     *
     * @param ResponseInterface $response PSR-7 response to set headers to
     * @param array $result Response data array to take headers from
     *
     * @return ResponseInterface Response object with headers added
     */
    protected function setResponseHeadersFromWpResponseData(ResponseInterface $response, array $result): ResponseInterface
    {
        foreach ($result['headers'] as $headerName => $headerValue) {
            $response = $response->withHeader($headerName, $headerValue);
        }
        /**
         * @var WP_Http_Cookie[] $wpCookies
         */
        $wpCookies = $result['cookies'];
        $cookiesValues = array_map(static function (WP_Http_Cookie $wpCookie): string {
            return $wpCookie->getHeaderValue();
        }, $wpCookies);
        if ($cookiesValues) {
            $response = $response->withAddedHeader('Set-Cookie', $cookiesValues);
        }
        return $response;
    }
}
