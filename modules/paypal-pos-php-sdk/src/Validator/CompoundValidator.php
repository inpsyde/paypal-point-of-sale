<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Validator;

// phpcs:disable SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingAnyTypeHint
class CompoundValidator implements ValidatorInterface
{
    /**
     * @var ValidatorInterface[]
     */
    private array $validators;
    /**
     * CompoundValidator constructor.
     *
     * @param ValidatorInterface ...$validatorInterfaces
     */
    public function __construct(ValidatorInterface ...$validatorInterfaces)
    {
        $this->validators = $validatorInterfaces;
    }
    public function accepts(mixed $entity): bool
    {
        foreach ($this->validators as $validator) {
            if ($validator->accepts($entity)) {
                return \true;
            }
        }
        return \false;
    }
    public function validate(mixed $entity): bool
    {
        foreach ($this->validators as $validator) {
            if ($validator->accepts($entity)) {
                $validator->validate($entity);
            }
        }
        return \true;
    }
}
