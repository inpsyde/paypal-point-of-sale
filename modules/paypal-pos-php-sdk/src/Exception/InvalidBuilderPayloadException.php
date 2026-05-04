<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Exception;

use Exception;
use Throwable;
/**
 * phpcs:disable Syde.Functions.ReturnTypeDeclaration.NoReturnType
 * phpcs:disable SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingAnyTypeHint
 * phpcs:disable Syde.Files.LineLength.TooLong
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
        parent::__construct("Could not build {$className} from the given payload", 0, $previous);
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
