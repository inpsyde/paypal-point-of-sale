<?php # -*- coding: utf-8 -*-
declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Test;

use Syde\Vendor\Zettle\Psr\Container\ContainerInterface;
use RuntimeException;

class WooCommerceConfigContainerFaker implements ContainerInterface
{
    /**
     * @var array
     */
    private $configMap;

    /**
     * WooCommerceSettingsContainer constructor.
     *
     * @param array|null $config
     */
    public function __construct(array $config = [])
    {
        $this->configMap = array_merge(
            [
                'weight_unit' => 'kg',
                'currency' => 'GBP'
            ],
            $config
        );
    }

    /**
     * @param string $id
     *
     * @return mixed
     *
     * @throws \Exception
     */
    public function get(string $id)
    {
        if (!$this->has($id)) {
            throw new RuntimeException("Given {$id} WooCommerce Setting doesnt exist.");
        }

        return $this->configMap[$id];
    }

    /**
     * @param string $id
     *
     * @return bool
     */
    public function has(string $id): bool
    {
        return array_key_exists($id, $this->configMap);
    }
}
