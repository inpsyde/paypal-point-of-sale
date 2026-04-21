<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\PhpSdk\Uuid;

use WC_Product;
use WC_Product_Simple;

use function chr;
use function count;
use function strlen;

use const PHP_INT_SIZE;

/**
 * This class produces valid UUIDv1 strings based on a WC_Product.
 * The resulting strings are not explicitly random any more because the intention is that
 * Each individual WC_Product reliably produces the same UUID. They will look random to onlookers
 * and they're actually not too far off the spec in implementation.
 * -
 * The reason we need this is because Zettle expects us to produce UUIDv1 when syncing products,
 * but we've had a fair amount of trouble when the same product is being synced multiple times,
 * for example in two concurrent web requests. There are other mechanisms to alleviate this problem
 * but this custom UUIDv1 practically resolves the issue at the root.
 * -
 * The randomness of a UUIDv1 basically comes from two things:
 * - A timestamp: We use the date of creation and add the post ID to mimick sub-second precision
 * - A node ID: Should be the MAC address of the network adapter but CAN simply be random
 *   as well if no mac address is obtainable. Here we simply use home_url()
 */
class Uuid
{
    private string $uuid;

    public function __construct(string $uuid)
    {
        $this->uuid = $uuid;
    }

    public static function fromWcProduct(WC_Product $product): self
    {
        $identifier = (int) $product->get_date_created()->format('U') + (int) $product->get_id();

        //Salt simple products so that variants and products use different UUIDs even if they're the same in WC
        if ($product instanceof WC_Product_Simple) {
            $identifier += 1;
        }

        return new self(
            self::generate($identifier)
        );
    }

    public function __toString(): string
    {
        return $this->uuid;
    }

    /**
     * @see http://tools.ietf.org/html/rfc4122#section-4.2.2
     *
     * @param int $identifier
     *
     * @return string
     */
    private static function generate(int $identifier): string
    {
        $identifier = self::hexTimestamp($identifier);

        // https://tools.ietf.org/html/rfc4122#section-4.1.5
        // Since we specifically avoid randomness in this facade implementation, we just set this to 0
        $clockSeq = 0;

        $node = substr(md5(home_url()), -10);

        /**
         * @psalm-suppress PossiblyFalseArgument
         */
        return (string) sprintf(
            '%08s-%04s-1%03s-%04x-%012s',
            // 32 bits for "time_low"
            substr($identifier, -8),
            // 16 bits for "time_mid"
            substr($identifier, -12, 4),
            // 16 bits for "time_hi_and_version",
            // four most significant bits holds version number 1
            substr($identifier, -15, 3),
            // 16 bits:
            // * 8 bits for "clk_seq_hi_res",
            // * 8 bits for "clk_seq_low",
            // two most significant bits holds zero and one for variant DCE1.1
            $clockSeq | 0x8000,
            // 48 bits for "node"
            $node
        );
    }

    private static function hexTimestamp(int $timestamp): string
    {
        if (PHP_INT_SIZE >= 8) {
            return str_pad(
                dechex($timestamp),
                16,
                '0',
                STR_PAD_LEFT
            );
        }

        return bin2hex(
            str_pad(self::toBinary((string) $timestamp), 8, "\0", STR_PAD_LEFT)
        );
    }

    /**
     * @param string $digits
     *
     * @return string
     */
    private static function toBinary(string $digits): string
    {
        $bytes = '';
        $count = strlen($digits);

        while ($count) {
            $quotient = [];
            $remainder = 0;

            for ($i = 0; $i !== $count; ++$i) {
                $carry = $digits[$i] + $remainder * 10;
                $digit = $carry >> 8;
                $remainder = $carry & 0xFF;

                if ($digit || $quotient) {
                    $quotient[] = $digit;
                }
            }

            $bytes = chr($remainder) . $bytes;
            $count = count($digits = $quotient);
        }

        return $bytes;
    }
}
