<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Validation;

class CompositeValidator implements ValidatorInterface
{

    /**
     * @var ValidatorInterface[]
     */
    private $validators;

    /**
     * @param ValidatorInterface[] $validators
     */
    public function __construct(array $validators)
    {
        $this->validators = $validators;
    }

    /**
     * phpcs:disable Inpsyde.CodeQuality.ArgumentTypeDeclaration.NoArgumentType
     * @param mixed $value
     */
    public function validate($value): void
    {
        $errors = [];

        foreach ($this->validators as $validator) {
            try {
                $validator->validate($value);
            } catch (ValidationFailedException $exception) {
                $errors[] = $exception;
            }
        }

        if ($errors) {
            throw new ValidationFailedException('Validation failed', $errors);
        }
    }
}
