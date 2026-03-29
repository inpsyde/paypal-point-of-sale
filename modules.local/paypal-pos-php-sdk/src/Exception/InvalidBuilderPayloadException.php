<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\PhpSdk\Exception;

use Exception;
use Throwable;

/**
 * phpcs:disable Inpsyde.CodeQuality.ReturnTypeDeclaration.NoReturnType
 * phpcs:disable Inpsyde.CodeQuality.ArgumentTypeDeclaration.NoArgumentType
 * phpcs:disable Inpsyde.CodeQuality.LineLength.TooLong
 */
class InvalidBuilderPayloadException extends Exception implements BuilderException, ValidatorException
{
    protected $payload;

    /**
     * @var string[]
     */
    protected array $errorCodes;

    /**
     * @param string[] $errorCodes Values of ValidationErrorCodes.
     */
    public function __construct(string $className, $payload, array $errorCodes, ?Throwable $previous = null)
    {
        $this->payload = $payload;
        $this->errorCodes = $errorCodes;

        parent::__construct(
            "Could not build {$className} from the given payload",
            0,
            $previous
        );
    }

    public function payload()
    {
        return $this->payload;
    }

    public function errorCodes(): array
    {
        return $this->errorCodes;
    }
}
