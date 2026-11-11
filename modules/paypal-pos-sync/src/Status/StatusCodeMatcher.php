<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Sync\Status;

use ArrayAccess;
/**
 * @implements ArrayAccess<string, string>
 */
class StatusCodeMatcher implements ArrayAccess
{
    /**
     * @var array<string, string>
     */
    private array $statusMap;
    public function __construct(array $statusMap)
    {
        $this->statusMap = $statusMap;
    }
    /**
     * Match the given status codes and return the messages
     *
     * @param string[] $statusCodes
     *
     * @return array<string, string>
     */
    public function match(array $statusCodes): array
    {
        $map = [];
        foreach ($statusCodes as $statusCode) {
            $message = $this[$statusCode];
            if (!$message) {
                $statusCode = SyncStatusCodes::UNDEFINED;
                $message = $this[$statusCode];
            }
            $map[$statusCode] = (string) $message;
        }
        return $map;
    }
    /**
     * @inheritDoc
     */
    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        return $this->offsetExists($offset) ? $this->statusMap[$offset] : null;
    }
    /**
     * @inheritDoc
     */
    public function offsetSet($offset, $value): void
    {
        /**
         * @psalm-suppress PossiblyNullArrayOffset
         */
        $this->statusMap[$offset] = $value;
    }
    /**
     * @inheritDoc
     */
    public function offsetExists($offset): bool
    {
        return isset($this->statusMap[$offset]);
    }
    /**
     * @inheritDoc
     */
    public function offsetUnset($offset): void
    {
        unset($this->statusMap[$offset]);
    }
}
