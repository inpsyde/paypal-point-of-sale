<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Http;

use UnexpectedValueException;
class PageReloader implements PageReloaderInterface
{
    /**
     * @inheritDoc
     */
    public function reload(): void
    {
        $key = 'REQUEST_URI';
        $requestUrl = filter_input(\INPUT_SERVER, $key, \FILTER_SANITIZE_URL);
        if (!is_string($requestUrl)) {
            throw new UnexpectedValueException(sprintf('Could not retrieve server variable "%1$s"', esc_html($key)));
        }
        wp_safe_redirect($requestUrl);
        exit;
    }
}
