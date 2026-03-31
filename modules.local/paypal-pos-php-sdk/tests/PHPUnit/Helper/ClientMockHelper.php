<?php

namespace Syde\PayPal\PointOfSale\PhpSdk\Tests;

use Mockery;
use Mockery\LegacyMockInterface;
use Mockery\MockInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriFactoryInterface;

/**
 * phpcs:disable Syde.Functions.ReturnTypeDeclaration
 *
 * @package Syde\PayPal\PointOfSale\PhpSdk\Tests
 */
class ClientMockHelper
{

    /**
     * @var LegacyMockInterface|MockInterface|ClientInterface
     */
    private $client;

    /**
     * @var LegacyMockInterface|MockInterface|UriFactoryInterface
     */
    private $uriFactory;

    /**
     * @var LegacyMockInterface|MockInterface|RequestFactoryInterface
     */
    private $requestFactory;

    /**
     * @var LegacyMockInterface|MockInterface|StreamFactoryInterface
     */
    private $streamFactory;

    /**
     * @var string
     */
    private $method;

    /**
     * @var string
     */
    private $url;

    /**
     * @var LegacyMockInterface|MockInterface|RequestInterface
     */
    private $request;

    public function __construct(string $method, string $url)
    {
        $this->method = $method;
        $this->url = $url;

        $this->client = Mockery::mock(ClientInterface::class);
        $this->uriFactory = Mockery::mock(UriFactoryInterface::class);
        $this->requestFactory = Mockery::mock(RequestFactoryInterface::class);
        $this->streamFactory = Mockery::mock(StreamFactoryInterface::class);
        $this->streamFactory->shouldReceive('createStream')
            ->once()
            ->andReturn(Mockery::mock(StreamInterface::class));
        $this->request = Mockery::mock(RequestInterface::class);
        $this->request->shouldReceive('withBody')
            ->once()
            ->andReturn($this->request);
        $this->request->shouldReceive('withHeader')
            ->withArgs(['Content-Type', 'application/json'])
            ->once()
            ->andReturn($this->request);
        $this->requestFactory->shouldReceive('createRequest')
            ->once()
            ->with($this->method, $this->url)
            ->andReturn(
                $this->request
            );
    }

    /**
     * @return LegacyMockInterface|MockInterface|ClientInterface
     */
    public function getClient(int $responseStatus, array $responseJson)
    {
        //$client = clone $this->client;
        $client = $this->client;
        $bodyStream = Mockery::mock(StreamInterface::class);
        $bodyStream->shouldReceive('rewind')->once();
        $bodyStream->shouldReceive('getContents')->once()->andReturn(json_encode($responseJson));

        $response = Mockery::mock(ResponseInterface::class);
        $response->shouldReceive('getBody')->once()->andReturn($bodyStream);
        $response->shouldReceive('getStatusCode')->once()->andReturn($responseStatus);
        $client->shouldReceive('sendRequest')->once()->andReturn($response);

        return $client;
    }

    /**
     * @param LegacyMockInterface|MockInterface|ClientInterface $client
     */
    public function setClient($client): void
    {
        $this->client = $client;
    }

    /**
     * @return LegacyMockInterface|MockInterface|UriFactoryInterface
     */
    public function getUriFactory()
    {
        return $this->uriFactory;
    }

    /**
     * @param LegacyMockInterface|MockInterface|UriFactoryInterface $uriFactory
     */
    public function setUriFactory($uriFactory): void
    {
        $this->uriFactory = $uriFactory;
    }

    /**
     * @return LegacyMockInterface|MockInterface|RequestFactoryInterface
     */
    public function getRequestFactory()
    {
        $requestFactory = clone $this->requestFactory;

        return $requestFactory;
    }

    /**
     * @param LegacyMockInterface|MockInterface|RequestFactoryInterface $requestFactory
     */
    public function setRequestFactory($requestFactory): void
    {
        $this->requestFactory = $requestFactory;
    }

    /**
     * @return LegacyMockInterface|MockInterface|StreamFactoryInterface
     */
    public function getStreamFactory()
    {
        return $this->streamFactory;
    }

    /**
     * @param LegacyMockInterface|MockInterface|StreamFactoryInterface $streamFactory
     */
    public function setStreamFactory($streamFactory): void
    {
        $this->streamFactory = $streamFactory;
    }

    /**
     * @return LegacyMockInterface|MockInterface|RequestInterface
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @param LegacyMockInterface|MockInterface|RequestInterface $request
     */
    public function setRequest($request): void
    {
        $this->request = $request;
    }
}
