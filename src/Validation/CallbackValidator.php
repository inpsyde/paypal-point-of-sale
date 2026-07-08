<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Validation;

class CallbackValidator implements ValidatorInterface
{
    /**
     * @var callable
     */
    private $callback;
    public function __construct(callable $callback)
    {
        $this->callback = $callback;
    }
    /**
     * phpcs:disable Syde.Functions.ArgumentTypeDeclaration.NoArgumentType
     * @param mixed $value
     */
    public function validate($value): void
    {
        $error = ($this->callback)($value);
        if ($error !== null) {
            throw new ValidationFailedException(esc_html((string) $error));
        }
    }
}
