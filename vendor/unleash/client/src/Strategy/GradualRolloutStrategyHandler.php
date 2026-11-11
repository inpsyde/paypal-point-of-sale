<?php

namespace Syde\Vendor\Zettle\Unleash\Client\Strategy;

use Override;
use Syde\Vendor\Zettle\Unleash\Client\Configuration\Context;
use Syde\Vendor\Zettle\Unleash\Client\DTO\Strategy;
use Syde\Vendor\Zettle\Unleash\Client\Enum\Stickiness;
use Syde\Vendor\Zettle\Unleash\Client\Stickiness\StickinessCalculator;
final class GradualRolloutStrategyHandler extends AbstractStrategyHandler
{
    public function __construct(private readonly StickinessCalculator $stickinessCalculator)
    {
    }
    #[Override]
    public function isEnabled(Strategy $strategy, Context $context): bool
    {
        if (!$stickiness = $this->findParameter('stickiness', $strategy)) {
            return \false;
        }
        $groupId = $this->findParameter('groupId', $strategy) ?? '';
        if (!$rollout = $this->findParameter('rollout', $strategy)) {
            return \false;
        }
        $id = match (strtolower($stickiness)) {
            Stickiness::DEFAULT => $context->getCurrentUserId() ?? $context->getSessionId() ?? random_int(1, 100000),
            Stickiness::RANDOM => random_int(1, 100000),
            Stickiness::USER_ID => $context->getCurrentUserId(),
            Stickiness::SESSION_ID => $context->getSessionId(),
            default => $context->findContextValue($stickiness),
        };
        if ($id === null) {
            return \false;
        }
        $normalized = $this->stickinessCalculator->calculate((string) $id, $groupId);
        $enabled = $normalized <= (int) $rollout;
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
        return 'flexibleRollout';
    }
}
