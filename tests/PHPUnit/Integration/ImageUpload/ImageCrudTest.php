<?php
declare(strict_types=1);

use Syde\PayPal\PointOfSale\PhpSdk\API\Image\Images;
use Syde\PayPal\PointOfSale\PhpSdk\Exception\ZettleRestException;
use Syde\PayPal\PointOfSale\Test\AuthenticatedRestRequestTestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

class ImageCrudTest extends AuthenticatedRestRequestTestCase
{
    protected function setUp(): void
    {
        self::markTestSkipped('Fails randomly https://inpsyde.slack.com/archives/C01DTSYJ743/p1610966076014300');
    }

    public function testReadWriteImageFromUrl()
    {
        $images = $this->images();
        try {
            $result = $images->create($this->randomImage());
            $this->assertNotEmpty($result->imageLookupKey());
            foreach ([
                $result->smallImageUrl(),
                $result->largeImageUrl(),
            ] as $remoteUrl) {
                $this->assertEventually($remoteUrl);
            }
        } catch (ZettleRestException $exception) {
            fwrite(STDOUT, $exception->getMessage());
            fwrite(STDOUT, print_r($exception->json(), true));
            $this->fail($exception->getMessage());
        }
    }

    public function testReadWriteBulkImageUrlUpload(): void
    {
        $imagesApi = $this->images();

        $result = $imagesApi->bulkCreate(
            [
                $this->randomImage(),
                $this->randomImage(),
                $this->randomImage(),
            ]
        );

        $this->assertNotEmpty($result);

        foreach ($result->all() as $image) {
            foreach ([$image->smallImageUrl(), $image->largeImageUrl()] as $remoteUrl) {
                $this->assertEventually($remoteUrl);
            }
        }
    }

    protected function assertEventually(string $url): void
    {
        $currentUrl = $url;
        $startTime = time();
        $waitTimeSecs = 60;
        $response = [];
        while (true) {
            if (time() - $startTime > $waitTimeSecs) {
                $this->fail(
                    "Url {$url} did not return a 200 response within {$waitTimeSecs} seconds. Last Response: ".
                    json_encode($response)
                );
            }

            $response = $this->fetch($currentUrl);

            if ($response['status'] !== 404) {
                break;
            }

            // weird caching issue
            // https://inpsyde.slack.com/archives/GRZ2UAHPD/p1598868802027700
            $currentUrl = $url . '?t=' . time();

            sleep(1);
        }
        $this->assertSame(
            200,
            $response['status'],
            "Url {$url} should return with a 200 response"
        );
    }

    private function images(): Images
    {
        return $this->get('paypal-pos.sdk.api.images');
    }

    private function fetch(string $url, $method = 'GET'): array
    {
        $client = $this->get('inpsyde.http-client');
        assert($client instanceof ClientInterface);
        $requestFactory = $this->get('inpsyde.http-client.request-factory');
        assert($requestFactory instanceof RequestFactoryInterface);
        $streamFactory = $this->get('inpsyde.http-client.stream-factory');
        assert($streamFactory instanceof StreamFactoryInterface);
        $request = $requestFactory->createRequest($method, $url);
        $response = $client->sendRequest($request);

        $body = $response->getBody();
        $body->rewind();
        $contents = $body->getContents();

        return [
            'status' => $response->getStatusCode(),
            'body' => $contents,
            'location' => $response->getHeader('Location'),
        ];
    }

    /**
     * Return a Lorem Ipsum Image from Image Service
     *
     * @return string
     */
    private function randomImage(): string
    {
        $response = $this->fetch('https://picsum.photos/200');

        $body = $response['location'];

        return array_shift($body);
    }
}
