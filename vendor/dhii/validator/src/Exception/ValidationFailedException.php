<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Dhii\Validator\Exception;

use Syde\Vendor\Zettle\Dhii\Validation\Exception\ValidationFailedExceptionInterface;
use Syde\Vendor\Zettle\Dhii\Validation\ValidatorInterface;
use RuntimeException;
use Stringable;
use Throwable;
/**
 * Signals that validation has failed.
 */
class ValidationFailedException extends RuntimeException implements ValidationFailedExceptionInterface
{
    /** @var ValidatorInterface */
    protected $validator;
    /** @var mixed */
    protected $subject;
    /** @var list<string|Stringable> */
    protected $errors;
    /**
     * @param ValidatorInterface $validator The failed validator.
     * @param mixed $subject The subject that was being validated.
     * @param list<string|Stringable> $errors A list of strings or stringable objects, like {@see Throwable},
     *                                        that represent errors.
     * @param string $message The error message.
     * @param int $code The error code.
     * @param Throwable|null $previous The inner error.
     */
    public function __construct(ValidatorInterface $validator, $subject, iterable $errors, string $message = '', int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->validator = $validator;
        $this->subject = $subject;
        $this->errors = $errors;
    }
    /**
     * @inheritDoc
     */
    public function getValidator(): ValidatorInterface
    {
        return $this->validator;
    }
    /**
     * @inheritDoc
     */
    public function getValidationErrors(): iterable
    {
        return $this->errors;
    }
    /**
     * @inheritDoc
     */
    public function getValidationSubject()
    {
        return $this->subject;
    }
}
