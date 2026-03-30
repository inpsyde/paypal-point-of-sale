<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Validation;

/**
 * Checks that PHP extensions (functions) are available.
 */
class RequiredExtensionsValidator implements ValidatorInterface
{
    /**
     * @var array<string, string>
     */
    protected array $extensions;
    /**
     * @param array<string, string> $extensions Keys - functions like {@see mb_strtolower()}',
     * values - human-friendly names.
     */
    public function __construct(array $extensions)
    {
        $this->extensions = $extensions;
    }
    /**
     * phpcs:disable Inpsyde.CodeQuality.ArgumentTypeDeclaration.NoArgumentType
     */
    public function validate($value): void
    {
        (new CallbackValidator(function (): ?string {
            $missingExtensions = array_filter(array_keys($this->extensions), static function (string $functionName): bool {
                return !function_exists($functionName);
            });
            if (empty($missingExtensions)) {
                return null;
            }
            return sprintf($this->getErrorMessageTemplate($missingExtensions), implode(', ', array_map(function (string $key): string {
                return $this->extensions[$key];
            }, $missingExtensions)));
        }))->validate(null);
    }
    protected function getErrorMessageTemplate(array $missingExtensions): string
    {
        if (count($missingExtensions) === 1) {
            // translators: %1$s - missing dependency, like "mbstring"
            return __('PayPal Point of Sale requires "%1$s" PHP extension to be installed.', 'paypal-point-of-sale');
        }
        // translators: %1$s - missing dependencies, like "mbstring, json"
        return __('PayPal Point of Sale requires these PHP extensions to be installed: %1$s.', 'paypal-point-of-sale');
    }
}
