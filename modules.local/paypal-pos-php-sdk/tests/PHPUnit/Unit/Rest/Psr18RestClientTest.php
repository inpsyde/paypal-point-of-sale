<?php

use Syde\PayPal\PointOfSale\PhpSdk\Exception\ZettleRestException;
use Syde\PayPal\PointOfSale\PhpSdk\Psr18RestClient;
use Syde\PayPal\PointOfSale\PhpSdk\Tests\ClientMockHelper;
use MonkeryTestCase\BrainMonkeyWpTestCase;
use Psr\Log\LoggerInterface;

class Psr18RestClientTest extends BrainMonkeyWpTestCase
{
    private $logger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->logger = Mockery::mock(LoggerInterface::class);
    }

    public function testGet()
    {
        $url = 'http://foo.bar';
        $responseStatus = 200;
        $responseJson = [];

        $baseMock = new ClientMockHelper('GET', $url);
        $testee = new Psr18RestClient(
            $this->logger,
            $baseMock->getClient($responseStatus, $responseJson),
            $baseMock->getUriFactory(),
            $baseMock->getRequestFactory(),
            $baseMock->getStreamFactory()
        );
        $result = $testee->get($url, []);
        $this->assertSame($responseJson, $result);
    }

    public function testPost()
    {
        $url = 'http://foo.bar';
        $responseStatus = 200;
        $responseJson = [];

        $baseMock = new ClientMockHelper('POST', $url);

        $testee = new Psr18RestClient(
            $this->logger,
            $baseMock->getClient($responseStatus, $responseJson),
            $baseMock->getUriFactory(),
            $baseMock->getRequestFactory(),
            $baseMock->getStreamFactory()
        );
        $result = $testee->post($url, []);
        $this->assertSame($responseJson, $result);
    }

    public function testGetError()
    {
        $url = 'http://foo.bar';
        $responseStatus = 500;
        $responseJson = [];
        $baseMock = new ClientMockHelper('GET', $url);

        $testee = new Psr18RestClient(
            $this->logger,
            $baseMock->getClient($responseStatus, $responseJson),
            $baseMock->getUriFactory(),
            $baseMock->getRequestFactory(),
            $baseMock->getStreamFactory()
        );

        $this->expectException(ZettleRestException::class);
        $testee->get($url, []);
    }

    public function testListeners()
    {
        $url = 'http://foo.bar';
        $responseStatus = 200;
        $responseJson = [];
        $baseMock = new ClientMockHelper('GET', $url);
        $i = 0;
        $incr = function () use (&$i) {
            $i++;
        };

        $testee = new Psr18RestClient(
            $this->logger,
            $baseMock->getClient($responseStatus, $responseJson),
            $baseMock->getUriFactory(),
            $baseMock->getRequestFactory(),
            $baseMock->getStreamFactory(),
            $incr,
            $incr,
            $incr,
            $incr
        );

        $testee->get($url, []);
        $this->assertSame(4, $i);
    }
}
