<?php

namespace Syde\Vendor\Zettle\Unleash\Client\Helper;

use Syde\Vendor\Zettle\Unleash\Client\ConstraintValidator\ConstraintValidator;
use Syde\Vendor\Zettle\Unleash\Client\ConstraintValidator\DefaultConstraintValidator;
trait ConstraintValidatorTrait
{
    private ?ConstraintValidator $validator = null;
    private function getValidator(): ConstraintValidator
    {
        $this->validator ??= new DefaultConstraintValidator();
        return $this->validator;
    }
}
