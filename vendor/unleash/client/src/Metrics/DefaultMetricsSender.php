<?php

namespace Syde\Vendor\Zettle\Unleash\Client\Metrics;

use Override;
use Syde\Vendor\Zettle\Psr\Http\Client\ClientExceptionInterface;
use Syde\Vendor\Zettle\Psr\Http\Client\ClientInterface;
use Syde\Vendor\Zettle\Psr\Http\Message\RequestFactoryInterface;
use Syde\Vendor\Zettle\Unleash\Client\Configuration\UnleashConfiguration;
use Syde\Vendor\Zettle\Unleash\Client\Helper\StringStream;
use Syde\Vendor\Zettle\Unleash\Client\Unleash;
final readonly class DefaultMetricsSender implements MetricsSender
{
    public function __construct(private ClientInterface $httpClient, private RequestFactoryInterface $requestFactory, private UnleashConfiguration $configuration)
    {
    }
    #[Override]
    public function sendMetrics(MetricsBucket $bucket): void
    {
        if (!$this->configuration->isMetricsEnabled() || !$this->configuration->isFetchingEnabled()) {
            return;
        }
        $payload = array_merge(['appName' => $this->configuration->getAppName(), 'instanceId' => $this->configuration->getInstanceId(), 'bucket' => $bucket->jsonSerialize(), 'platformName' => \PHP_SAPI, 'platformVersion' => \PHP_VERSION, 'yggdrasilVersion' => null, 'specVersion' => Unleash::SPECIFICATION_VERSION], $this->configuration->getSdkFlavourMetadata());
        $request = $this->requestFactory->createRequest('POST', $this->configuration->getMetricsUrl())->withHeader('Content-Type', 'application/json')->withHeader('Unleash-Interval', (string) $this->configuration->getMetricsInterval())->withBody(new StringStream(json_encode($payload, \JSON_THROW_ON_ERROR)));
        foreach ($this->configuration->getHeaders() as $name => $value) {
            $request = $request->withHeader($name, $value);
        }
        try {
            $this->httpClient->sendRequest($request);
        } catch (ClientExceptionInterface) {
            // ignore the error
        }
    }
}
