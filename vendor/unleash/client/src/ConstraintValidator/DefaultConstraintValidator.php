<?php

namespace Syde\Vendor\Zettle\Unleash\Client\ConstraintValidator;

use Override;
use Syde\Vendor\Zettle\Unleash\Client\Configuration\Context;
use Syde\Vendor\Zettle\Unleash\Client\ConstraintValidator\Operator\Date\DateAfterOperatorValidator;
use Syde\Vendor\Zettle\Unleash\Client\ConstraintValidator\Operator\Date\DateBeforeOperatorValidator;
use Syde\Vendor\Zettle\Unleash\Client\ConstraintValidator\Operator\Lists\InListOperatorValidator;
use Syde\Vendor\Zettle\Unleash\Client\ConstraintValidator\Operator\Lists\NotInListOperatorValidator;
use Syde\Vendor\Zettle\Unleash\Client\ConstraintValidator\Operator\Number\NumberEqualsOperatorValidator;
use Syde\Vendor\Zettle\Unleash\Client\ConstraintValidator\Operator\Number\NumberGreaterThanOperatorValidator;
use Syde\Vendor\Zettle\Unleash\Client\ConstraintValidator\Operator\Number\NumberGreaterThanOrEqualsOperatorValidator;
use Syde\Vendor\Zettle\Unleash\Client\ConstraintValidator\Operator\Number\NumberLowerThanOperatorValidator;
use Syde\Vendor\Zettle\Unleash\Client\ConstraintValidator\Operator\Number\NumberLowerThanOrEqualsOperatorValidator;
use Syde\Vendor\Zettle\Unleash\Client\ConstraintValidator\Operator\String\StringContainsOperatorValidator;
use Syde\Vendor\Zettle\Unleash\Client\ConstraintValidator\Operator\String\StringEndsWithOperatorValidator;
use Syde\Vendor\Zettle\Unleash\Client\ConstraintValidator\Operator\String\StringStartsWithOperatorValidator;
use Syde\Vendor\Zettle\Unleash\Client\ConstraintValidator\Operator\Version\VersionEqualsOperatorValidator;
use Syde\Vendor\Zettle\Unleash\Client\ConstraintValidator\Operator\Version\VersionGreaterThanOperatorValidator;
use Syde\Vendor\Zettle\Unleash\Client\ConstraintValidator\Operator\Version\VersionGreaterThanOrEqualsOperatorValidator;
use Syde\Vendor\Zettle\Unleash\Client\ConstraintValidator\Operator\Version\VersionLowerThanOperatorValidator;
use Syde\Vendor\Zettle\Unleash\Client\ConstraintValidator\Operator\Version\VersionLowerThanOrEqualsOperatorValidator;
use Syde\Vendor\Zettle\Unleash\Client\DTO\Constraint;
use Syde\Vendor\Zettle\Unleash\Client\Enum\ConstraintOperator;
use Syde\Vendor\Zettle\Unleash\Client\Exception\OperatorValidatorException;
final class DefaultConstraintValidator implements ConstraintValidator
{
    #[Override]
    public function validateConstraint(Constraint $constraint, Context $context): bool
    {
        $field = $constraint->getContextName();
        $currentValue = $context->findContextValue($field) ?? '';
        $callback = $this->getValidationCallback($constraint->getOperator());
        $valueToPass = $constraint->getSingleValue() !== null ? $constraint->getSingleValue() : $constraint->getValues();
        $isCaseInsensitive = $constraint->isCaseInsensitive();
        if ($isCaseInsensitive) {
            $currentValue = $this->makeCaseInsensitive($currentValue);
            $valueToPass = $valueToPass === null ? null : $this->makeCaseInsensitive($valueToPass);
        }
        // Catch any possible validator exceptions to avoid inverting result that failed due to
        // wrong data being passed to true.
        try {
            $result = $callback($currentValue, $valueToPass);
            $isInverted = $constraint->isInverted();
            if ($isInverted) {
                $result = !$result;
            }
        } catch (OperatorValidatorException) {
            $result = \false;
        }
        return (bool) $result;
    }
    /**
     * @template T of string|array<mixed>
     *
     * @param T $value
     *
     * @return T
     *
     * @noinspection PhpDocSignatureInspection
     */
    private function makeCaseInsensitive(string|array $value): string|array
    {
        if (is_string($value)) {
            // @phpstan-ignore-next-line return.type
            return mb_strtolower($value);
        }
        $result = [];
        foreach ($value as $key => $item) {
            assert(is_array($item) || is_string($item));
            $result[$key] = $this->makeCaseInsensitive($item);
        }
        // @phpstan-ignore-next-line return.type
        return $result;
    }
    private function getValidationCallback(string $operator): callable
    {
        return match ($operator) {
            // list
            ConstraintOperator::IN_LIST => new InListOperatorValidator(),
            ConstraintOperator::NOT_IN_LIST => new NotInListOperatorValidator(),
            // strings
            ConstraintOperator::STRING_STARTS_WITH => new StringStartsWithOperatorValidator(),
            ConstraintOperator::STRING_ENDS_WITH => new StringEndsWithOperatorValidator(),
            ConstraintOperator::STRING_CONTAINS => new StringContainsOperatorValidator(),
            // numbers
            ConstraintOperator::NUMBER_EQUALS => new NumberEqualsOperatorValidator(),
            ConstraintOperator::NUMBER_GREATER_THAN => new NumberGreaterThanOperatorValidator(),
            ConstraintOperator::NUMBER_GREATER_THAN_OR_EQUALS => new NumberGreaterThanOrEqualsOperatorValidator(),
            ConstraintOperator::NUMBER_LOWER_THAN => new NumberLowerThanOperatorValidator(),
            ConstraintOperator::NUMBER_LOWER_THAN_OR_EQUALS => new NumberLowerThanOrEqualsOperatorValidator(),
            // date
            ConstraintOperator::DATE_AFTER => new DateAfterOperatorValidator(),
            ConstraintOperator::DATE_BEFORE => new DateBeforeOperatorValidator(),
            // version
            ConstraintOperator::VERSION_EQUALS => new VersionEqualsOperatorValidator(),
            ConstraintOperator::VERSION_GREATER_THAN => new VersionGreaterThanOperatorValidator(),
            ConstraintOperator::VERSION_LOWER_THAN => new VersionLowerThanOperatorValidator(),
            ConstraintOperator::VERSION_LOWER_THAN_OR_EQUALS => new VersionLowerThanOrEqualsOperatorValidator(),
            ConstraintOperator::VERSION_GREATER_THAN_OR_EQUALS => new VersionGreaterThanOrEqualsOperatorValidator(),
            default => fn() => \false,
        };
    }
}
