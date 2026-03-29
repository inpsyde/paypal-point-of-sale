<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\PhpSdk\Exception;

use Exception;
use Throwable;

class ZettleRestException extends Exception
{
    const TYPE_ENTITY_NOT_FOUND = 'ENTITY_NOT_FOUND';
    const TYPE_PRODUCT_NOT_TRACKED = 'PRODUCT_NOT_TRACKED';
    const TYPE_UNAUTHENTICATED = 'UNAUTHENTICATED';
    const TYPE_UNKNOWN = 'UNKNOWN';

    private array $json;

    private array $payload;

    public function __construct(
        string $message = "",
        int $code = 0,
        array $json = [],
        array $payload = [],
        ?Throwable $previous = null
    ) {

        parent::__construct($message, $code, $previous);
        $this->json = $json;
        $this->payload = $payload;
    }

    /**
     * Returns error context data received from Zettle
     *
     * @return array
     */
    public function json(): array
    {
        return $this->json;
    }

    /**
     * Inspects the error context data to check if the error type matches the specified one
     *
     * @param string $errorType
     *
     * @return bool
     */
    public function isType(string $errorType): bool
    {
        return $this->type() === $errorType;
    }

    public function type(): string
    {
        return $this->json['errorType'] ?? self::TYPE_UNKNOWN;
    }

    public function developerMessage(): string
    {
        return $this->json['developerMessage'] ?? '';
    }

    public function violations(): array
    {
        return $this->json['violations'] ?? [];
    }

    public function payload(): array
    {
        return $this->payload;
    }
}
