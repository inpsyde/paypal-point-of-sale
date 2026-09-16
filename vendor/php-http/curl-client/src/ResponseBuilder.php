<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Http\Client\Curl;

use Syde\Vendor\Zettle\Http\Message\Builder\ResponseBuilder as OriginalResponseBuilder;
use Syde\Vendor\Zettle\Psr\Http\Message\ResponseInterface;
/**
 * Extended response builder.
 */
class ResponseBuilder extends OriginalResponseBuilder
{
    /**
     * Replace response with a new instance.
     *
     * @param ResponseInterface $response
     */
    public function setResponse(ResponseInterface $response): void
    {
        $this->response = $response;
    }
}
