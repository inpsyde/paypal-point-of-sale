<?php

namespace Syde\Vendor\Zettle\Unleash\Client\DTO;

use Syde\Vendor\Zettle\JetBrains\PhpStorm\ArrayShape;
use Syde\Vendor\Zettle\JetBrains\PhpStorm\ExpectedValues;
use Syde\Vendor\Zettle\JetBrains\PhpStorm\Pure;
use JsonException;
use LogicException;
use Override;
use Syde\Vendor\Zettle\Unleash\Client\Enum\VariantPayloadType;
final readonly class DefaultVariantPayload implements VariantPayload
{
    public function __construct(
        #[ExpectedValues(valuesFromClass: VariantPayloadType::class)]
        private string $type,
        private string $value
    )
    {
    }
    #[ExpectedValues(valuesFromClass: VariantPayloadType::class)]
    #[Override]
    public function getType(): string
    {
        return $this->type;
    }
    #[Override]
    public function getValue(): string
    {
        return $this->value;
    }
    /**
     * @throws JsonException
     *
     * @return array<mixed>
     */
    #[Override]
    public function fromJson(): array
    {
        if ($this->type !== VariantPayloadType::JSON) {
            throw new LogicException(sprintf("Only payloads of type '%s' can be converted from json, this payload has type '%s'", VariantPayloadType::JSON, $this->type));
        }
        return (array) json_decode($this->value, \true, 512, \JSON_THROW_ON_ERROR);
    }
    /**
     * @return array<string>
     */
    #[Pure]
    #[ArrayShape(['type' => 'string', 'value' => 'string'])]
    #[Override]
    public function jsonSerialize(): array
    {
        return ['type' => $this->type, 'value' => $this->getValue()];
    }
}
