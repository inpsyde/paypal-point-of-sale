<?php

namespace Syde\Vendor\Zettle\Unleash\Client\DTO;

use Syde\Vendor\Zettle\JetBrains\PhpStorm\ExpectedValues;
use Syde\Vendor\Zettle\Unleash\Client\Enum\ConstraintOperator;
interface Constraint
{
    public function getContextName(): string;
    #[ExpectedValues(valuesFromClass: ConstraintOperator::class)]
    public function getOperator(): string;
    /**
     * @return array<string>|null
     */
    public function getValues(): ?array;
    public function getSingleValue(): ?string;
    public function isInverted(): bool;
    public function isCaseInsensitive(): bool;
}
