<?php

namespace Syde\Vendor\Zettle\Unleash\Client\Helper;

/**
 * @internal
 */
final class Uuid
{
    public static function v4(): string
    {
        $uuid = random_bytes(16);
        $uuid[6] = $uuid[6] & "\x0f" | "@";
        $uuid[8] = $uuid[8] & "?" | "\x80";
        $uuid = bin2hex($uuid);
        return substr($uuid, 0, 8) . '-' . substr($uuid, 8, 4) . '-' . substr($uuid, 12, 4) . '-' . substr($uuid, 16, 4) . '-' . substr($uuid, 20, 12);
    }
}
