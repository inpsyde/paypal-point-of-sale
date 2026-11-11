<?php

namespace Syde\Vendor\Zettle\Unleash\Client\Strategy;

use Override;
use Syde\Vendor\Zettle\Unleash\Client\Configuration\Context;
use Syde\Vendor\Zettle\Unleash\Client\DTO\Strategy;
final class UserIdStrategyHandler extends AbstractStrategyHandler
{
    #[Override]
    public function isEnabled(Strategy $strategy, Context $context): bool
    {
        if (!$userIds = $this->findParameter('userIds', $strategy)) {
            return \false;
        }
        if ($context->getCurrentUserId() === null) {
            return \false;
        }
        $userIds = array_map('trim', explode(',', $userIds));
        $enabled = in_array($context->getCurrentUserId(), $userIds, \true);
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
        return 'userWithId';
    }
}
