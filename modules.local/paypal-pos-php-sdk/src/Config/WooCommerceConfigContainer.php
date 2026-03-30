<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\PhpSdk\Config;

use Exception;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use RuntimeException;

class WooCommerceConfigContainer implements ContainerInterface
{
    /**
     * @param string $id
     *
     * @return mixed|void
     *
     * @throws RuntimeException
     */
    public function get(string $id)
    {
        if (!$this->has($id)) {
            throw new class (
                sprintf('Given WooCommerce Setting: woocommerce_%s doesnt exists.', esc_html($id))
            ) extends Exception implements NotFoundExceptionInterface {
            };
        }

        return get_option($this->key($id));
    }

    /**
     * @param string $id
     *
     * @return bool
     */
    public function has(string $id): bool
    {
        return !!get_option($this->key($id));
    }

    private function key(string $id): string
    {
        return "woocommerce_{$id}";
    }
}
