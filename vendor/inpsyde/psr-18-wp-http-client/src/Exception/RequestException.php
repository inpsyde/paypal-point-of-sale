<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Inpsyde\Wp\HttpClient\Exception;

use Syde\Vendor\Zettle\Psr\Http\Client\RequestExceptionInterface;
use Syde\Vendor\Zettle\Psr\Http\Message\RequestInterface;
use Throwable;
/**
 * This exception is thrown where provided to client request is malformed
 * or missing critical data and cannot be sent
 */
class RequestException extends WpHttpClientException implements RequestExceptionInterface
{
    protected RequestInterface $request;
    /**
     * @param string $message Exception message
     * @param int $code Error code
     * @param Throwable|null $previous Previous exception
     * @param RequestInterface|null $request Invalid request because of that this exception is thrown
     */
    public function __construct($message = '', $code = 0, Throwable $previous = null, RequestInterface $request = null)
    {
        parent::__construct($message, $code, $previous);
        $this->request = $request;
    }
    /**
     * @inheritDoc
     */
    public function getRequest(): RequestInterface
    {
        return $this->request;
    }
}
