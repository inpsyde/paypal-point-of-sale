<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Auth\OAuth\Token;

interface TokenInterface
{
    public function access(): string;

    public function expires(): int;

    public function refresh(): string;

    public function toArray(): array;
}
