<?php

namespace Syde\Vendor\Zettle\Unleash\Client\Strategy;

use Override;
use Syde\Vendor\Zettle\Unleash\Client\Configuration\Context;
use Syde\Vendor\Zettle\Unleash\Client\DTO\Constraint;
use Syde\Vendor\Zettle\Unleash\Client\DTO\Strategy;
use Syde\Vendor\Zettle\Unleash\Client\Helper\ConstraintValidatorTrait;
abstract class AbstractStrategyHandler implements StrategyHandler
{
    use ConstraintValidatorTrait;
    #[Override]
    public function supports(Strategy $strategy): bool
    {
        return $strategy->getName() === $this->getStrategyName();
    }
    protected function findParameter(string $parameter, Strategy $strategy): ?string
    {
        $parameters = $strategy->getParameters();
        return $parameters[$parameter] ?? null;
    }
    protected function validateConstraints(Strategy $strategy, Context $context): bool
    {
        if ($strategy->hasNonexistentSegments()) {
            return \false;
        }
        $validator = $this->getValidator();
        $constraints = $this->getConstraintsForStrategy($strategy);
        foreach ($constraints as $constraint) {
            if (!$validator->validateConstraint($constraint, $context)) {
                return \false;
            }
        }
        return \true;
    }
    /**
     * @return iterable<Constraint>
     */
    private function getConstraintsForStrategy(Strategy $strategy): iterable
    {
        yield from $strategy->getConstraints();
        foreach ($strategy->getSegments() as $segment) {
            yield from $segment->getConstraints();
        }
    }
}
