<?php

namespace Syde\Vendor\Zettle\Unleash\Client\Strategy;

use Syde\Vendor\Zettle\JetBrains\PhpStorm\Deprecated;
use Override;
use Syde\Vendor\Zettle\Unleash\Client\Configuration\Context;
use Syde\Vendor\Zettle\Unleash\Client\DTO\DefaultStrategy;
use Syde\Vendor\Zettle\Unleash\Client\DTO\Strategy;
use Syde\Vendor\Zettle\Unleash\Client\Enum\Stickiness;
#[Deprecated(reason: 'The strategy has been deprecated, please use Gradual Rollout (flexibleRollout)')]
final class GradualRolloutUserIdStrategyHandler extends AbstractStrategyHandler
{
    public function __construct(private readonly GradualRolloutStrategyHandler $rolloutStrategyHandler)
    {
    }
    #[Override]
    public function isEnabled(Strategy $strategy, Context $context): bool
    {
        $transformedStrategy = new DefaultStrategy($this->getStrategyName(), ['stickiness' => Stickiness::USER_ID, 'groupId' => $strategy->getParameters()['groupId'], 'rollout' => $strategy->getParameters()['percentage']]);
        return $this->rolloutStrategyHandler->isEnabled($transformedStrategy, $context);
    }
    #[Override]
    public function getStrategyName(): string
    {
        return 'gradualRolloutUserId';
    }
}
