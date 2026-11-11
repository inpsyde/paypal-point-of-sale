<?php

namespace Syde\Vendor\Zettle\Unleash\Client;

use Override;
use Syde\Vendor\Zettle\Unleash\Client\Configuration\Context;
use Syde\Vendor\Zettle\Unleash\Client\DTO\DefaultFeature;
use Syde\Vendor\Zettle\Unleash\Client\DTO\DefaultVariant;
use Syde\Vendor\Zettle\Unleash\Client\DTO\Variant;
use Syde\Vendor\Zettle\Unleash\Client\Enum\Stickiness;
use Syde\Vendor\Zettle\Unleash\Client\Metrics\MetricsHandler;
use Syde\Vendor\Zettle\Unleash\Client\Repository\ProxyRepository;
final readonly class DefaultProxyUnleash implements Unleash
{
    public function __construct(private ProxyRepository $repository, private MetricsHandler $metricsHandler)
    {
    }
    /**
     * @codeCoverageIgnore
     */
    #[Override]
    public function register(): bool
    {
        //This is a no op, since registration is handled by the proxy/edge, this doesn't need coverage
        return \false;
    }
    #[Override]
    public function isEnabled(string $featureName, ?Context $context = null, bool $default = \false): bool
    {
        $response = $this->repository->findFeatureByContext($featureName, $context);
        $enabled = $response ? $response->isEnabled() : $default;
        $this->metricsHandler->handleMetrics(new DefaultFeature($featureName, $enabled, []), $enabled);
        return $enabled;
    }
    #[Override]
    public function getVariant(string $featureName, ?Context $context = null, ?Variant $fallbackVariant = null): Variant
    {
        $variant = $fallbackVariant ?? new DefaultVariant('disabled', \false, 0, Stickiness::DEFAULT);
        $response = $this->repository->findFeatureByContext($featureName, $context);
        if ($response !== null) {
            $variant = $response->getVariant();
        }
        $metricVariant = new DefaultVariant($variant->getName(), $variant->isEnabled(), 0, Stickiness::DEFAULT, $variant->getPayload());
        $this->metricsHandler->handleMetrics(new DefaultFeature($featureName, $variant->isEnabled(), []), $variant->isEnabled(), $metricVariant);
        return $variant;
    }
}
