<?php

namespace Syde\Vendor\Zettle\Unleash\Client\ConstraintValidator;

use Syde\Vendor\Zettle\Unleash\Client\Configuration\Context;
use Syde\Vendor\Zettle\Unleash\Client\DTO\Constraint;
interface ConstraintValidator
{
    public function validateConstraint(Constraint $constraint, Context $context): bool;
}
