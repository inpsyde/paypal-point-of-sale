<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Inpsyde\FeatureFlags\Adapter\Unleash\Http;

use Syde\Vendor\Zettle\Psr\Http\Client\RequestExceptionInterface;
use Syde\Vendor\Zettle\Psr\Http\Message\RequestInterface;
/**
 * A successful flag fetch whose body contains no flag list.
 */
final class InvalidFeatureResponse extends \RuntimeException implements RequestExceptionInterface
{
    public function __construct(private readonly RequestInterface $request, string $message)
    {
        parent::__construct($message);
    }
    public function getRequest(): RequestInterface
    {
        return $this->request;
    }
}
