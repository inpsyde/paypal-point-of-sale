<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Dhii\Validator;

use Syde\Vendor\Zettle\Dhii\Validation\Exception\ValidationFailedExceptionInterface;
use Syde\Vendor\Zettle\Dhii\Validation\ValidatorInterface;
use Syde\Vendor\Zettle\Dhii\Validator\Exception\ValidationFailedException;
use Exception;
use RuntimeException;
class CallbackValidator implements ValidatorInterface
{
    use VarDumpTrait;
    /**
     * @var callable
     */
    protected $callback;
    /**
     * @param callable $callback The validating callback.
     *                           Will receive the value to be validated.
     *                           Return a string to signal validation failure, providing the reason.
     */
    public function __construct(callable $callback)
    {
        $this->callback = $callback;
    }
    /**
     * @inheritDoc
     */
    public function validate($value): void
    {
        try {
            $callback = $this->callback;
            $result = $callback($value);
        } catch (Exception $e) {
            if ($e instanceof ValidationFailedExceptionInterface) {
                throw $e;
            }
            throw new RuntimeException(sprintf('Failed validating %1$s', $this->varDump($value)), 0, $e);
        }
        if ($result === null) {
            return;
        }
        $message = $result === \false ? sprintf('Value is invalid: %1$s', $this->varDump($value)) : (string) $result;
        throw new ValidationFailedException($this, $value, [$message], $message);
    }
}
