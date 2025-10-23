<?php

namespace Syde\PayPal\PointOfSale\Test;

use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

class EnvMapCredentialsContainer implements ContainerInterface
{

    private $envMap;

    public function __construct()
    {
        $this->envMap = [
            'api_key' => 'IZETTLE_API_KEY',
            'client_id' => 'IZETTLE_CLIENT_ID',
        ];
    }

    /**
     * @inheritDoc
     */
    public function get($id)
    {
        if (!$this->has($id)) {
            throw new class extends \Exception implements NotFoundExceptionInterface {

            };
        }

        return getenv($this->envMap[$id]);
    }

    /**
     * @inheritDoc
     */
    public function has($id)
    {
        return array_key_exists($id, $this->envMap);
    }
}
