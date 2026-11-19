<?php

namespace Syde\Vendor\Zettle\Unleash\Client\Strategy;

use Override;
use Syde\Vendor\Zettle\Unleash\Client\Configuration\Context;
use Syde\Vendor\Zettle\Unleash\Client\DTO\Strategy;
use Syde\Vendor\Zettle\Unleash\Client\Exception\InvalidIpAddressException;
use Syde\Vendor\Zettle\Unleash\Client\Exception\MissingArgumentException;
use Syde\Vendor\Zettle\Unleash\Client\Helper\NetworkCalculator;
final class IpAddressStrategyHandler extends AbstractStrategyHandler
{
    /**
     * @throws MissingArgumentException
     */
    #[Override]
    public function isEnabled(Strategy $strategy, Context $context): bool
    {
        if (!$ipAddresses = $this->findParameter('IPs', $strategy)) {
            throw new MissingArgumentException("The remote server did not return 'IPs' config");
        }
        $ipAddresses = array_map('trim', explode(',', $ipAddresses));
        $enabled = \false;
        $currentIpAddress = $context->getIpAddress();
        if ($currentIpAddress !== null) {
            foreach ($ipAddresses as $ipAddress) {
                try {
                    $calculator = NetworkCalculator::fromString($ipAddress);
                } catch (InvalidIpAddressException) {
                    continue;
                }
                if ($calculator->isInRange($currentIpAddress)) {
                    $enabled = \true;
                    break;
                }
            }
        }
        if (!$enabled) {
            return \false;
        }
        if (!$this->validateConstraints($strategy, $context)) {
            return \false;
        }
        return \true;
    }
    #[Override]
    public function getStrategyName(): string
    {
        return 'remoteAddress';
    }
}
