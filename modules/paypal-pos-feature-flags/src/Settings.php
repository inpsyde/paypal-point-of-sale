<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\FeatureFlags;

/**
 * The connection to Unleash. Merchant sites use the production environment of the PayPal POS
 * project; QA, staging and local sites override it with constants in wp-config.php or
 * environment variables of the same name (e.g. web_environment in .ddev/config.local.yaml).
 */
final readonly class Settings
{
    public const DEFAULT_URL = 'https://us.app.unleash-hosted.com/usqq0324/api/';
    // Public by design: a client token can only read the flag configuration.
    public const DEFAULT_TOKEN = 'paypal-pos:production.v2_D3kWgaKh5XDNV2ZSE7DZcA_bEJIfNkfFOjJW-fowwmM6HZRrCWwDVxJe6SdZat69U0';
    /**
     * @param string $url Without a URL and token, no project is registered and flag checks
     *     return their default.
     * @param bool $overridden Whether the site overrides the built-in connection.
     * @param bool $qaSite Whether the site is marked as a QA site.
     */
    public function __construct(public string $url, public string $token, public bool $overridden = \false, public bool $qaSite = \false)
    {
    }
    public static function fromEnvironment(): self
    {
        $url = self::value('PAYPAL_POS_FEATURE_FLAGS_URL');
        $token = self::value('PAYPAL_POS_FEATURE_FLAGS_TOKEN');
        $qaSite = filter_var(self::value('PAYPAL_POS_FEATURE_FLAGS_QA_SITE') ?? \false, \FILTER_VALIDATE_BOOLEAN);
        // The URL and token are overridden together, so the built-in token never goes to
        // another URL.
        if ($url === null && $token === null) {
            return new self(self::DEFAULT_URL, self::DEFAULT_TOKEN, \false, $qaSite);
        }
        return new self($url ?? '', $token ?? '', \true, $qaSite);
    }
    /**
     * The value of a constant, or else of the environment variable with the same name. An
     * empty value counts as not set, so an empty variable exported by a host doesn't turn
     * the flags off.
     */
    private static function value(string $name): ?string
    {
        $value = defined($name) ? constant($name) : getenv($name);
        if (is_bool($value)) {
            // An undefined environment variable, or a boolean constant.
            $value = $value ? '1' : '';
        }
        return $value === '' ? null : (string) $value;
    }
}
