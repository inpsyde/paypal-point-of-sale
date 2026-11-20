<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Auth\Jwt;

use InvalidArgumentException;
/**
 * A basic JWT parser.
 */
class Parser implements ParserInterface
{
    /**
     * @inheritDoc
     */
    public function parse(string $jwt): TokenInterface
    {
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) {
            throw new InvalidArgumentException('');
        }
        $headers = $this->decodeJson($this->decodeBase64Url($parts[0]));
        $claims = $this->decodeJson($this->decodeBase64Url($parts[1]));
        $signature = $parts[2];
        return new Token($headers, $claims, $signature);
    }
    protected function decodeBase64Url(string $base64): string
    {
        $remainder = strlen($base64) % 4;
        if ($remainder > 0) {
            $base64 .= str_repeat('=', 4 - $remainder);
        }
        $data = base64_decode(strtr($base64, '-_', '+/'), \true);
        if (!$data) {
            throw new InvalidArgumentException('Failed to decode JWT base64');
        }
        return $data;
    }
    protected function decodeJson(string $json): array
    {
        $data = json_decode($json, \true);
        if (!is_array($data)) {
            throw new InvalidArgumentException('Failed to decode JWT json');
        }
        return $data;
    }
}
