<?php

namespace Syde\Vendor\Zettle\Unleash\Client\DTO;

use Syde\Vendor\Zettle\JetBrains\PhpStorm\ExpectedValues;
use JsonSerializable;
use Syde\Vendor\Zettle\Unleash\Client\Enum\Stickiness;
/**
 * @method bool isFeatureEnabled()
 */
interface Variant extends JsonSerializable
{
    public function getName(): string;
    public function isEnabled(): bool;
    public function getPayload(): ?VariantPayload;
    public function getWeight(): int;
    /**
     * @return array<VariantOverride>
     */
    public function getOverrides(): array;
    #[ExpectedValues(valuesFromClass: Stickiness::class)]
    public function getStickiness(): string;
}
