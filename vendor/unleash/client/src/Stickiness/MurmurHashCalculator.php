<?php

namespace Syde\Vendor\Zettle\Unleash\Client\Stickiness;

use Syde\Vendor\Zettle\lastguest\Murmur;
use Override;
final class MurmurHashCalculator implements StickinessCalculator
{
    #[Override]
    public function calculate(string $id, string $groupId, int $normalizer = 100, int $seed = 0): int
    {
        return Murmur::hash3_int("{$groupId}:{$id}", $seed) % $normalizer + 1;
    }
}
