<?php

declare (strict_types=1);
// phpcs:disable Inpsyde.CodeQuality.ReturnTypeDeclaration
// phpcs:disable Inpsyde.CodeQuality.ArgumentTypeDeclaration
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Auth;

use Exception;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Auth\Jwt\ParserInterface;
use Syde\Vendor\Zettle\Syde\Vendor\Zettle\Psr\Container\ContainerInterface;
use Syde\Vendor\Zettle\Syde\Vendor\Zettle\Psr\Container\NotFoundExceptionInterface;
use Throwable;
class CredentialsContainer implements ContainerInterface
{
    /**
     * @var ParserInterface
     */
    private $tokenDecoder;
    /**
     * @var array
     */
    private $data;
    /**
     * @var ContainerInterface|null
     */
    private $inner;
    /**
     * CredentialsContainer constructor.
     *
     * @param ParserInterface $tokenDecoder
     * @param array $data
     * @param ContainerInterface|null $inner
     */
    public function __construct(ParserInterface $tokenDecoder, array $data, ?ContainerInterface $inner = null)
    {
        $this->tokenDecoder = $tokenDecoder;
        $this->data = $data;
        $this->inner = $inner;
    }
    /**
     * get key (id) otherwise throw execption
     *
     * @param string $id
     *
     * @return mixed
     *
     * @throws Exception
     */
    public function get(string $id)
    {
        if (array_key_exists($id, $this->data)) {
            return $this->data[$id];
        }
        if ($this->inner && $this->inner->has($id)) {
            return $this->inner->get($id);
        }
        if ($id === 'client_id' && $this->has('client_id')) {
            return $this->data[$id];
        }
        throw new class(sprintf('%s not found in credentials container', $id)) extends Exception implements NotFoundExceptionInterface
        {
        };
    }
    /**
     * Check if id (key) exists
     *
     * @param string $id
     *
     * @return bool
     */
    public function has(string $id): bool
    {
        if (array_key_exists($id, $this->data)) {
            return \true;
        }
        if ($this->inner && $this->inner->has($id)) {
            return \true;
        }
        if ($id === 'client_id') {
            $this->decodeFromToken($id);
            return array_key_exists($id, $this->data);
        }
        return \false;
    }
    /**
     * Decode the Token and the result into the container
     *
     * @param string $key
     */
    private function decodeFromToken(string $key): void
    {
        try {
            $apiKey = $this->get('api_key');
            $token = $this->tokenDecoder->parse($apiKey);
            $result = $token->getClaims()[$key] ?? '';
        } catch (Throwable $exception) {
            return;
        }
        if (empty($result)) {
            return;
        }
        $this->data[$key] = $result;
    }
}
