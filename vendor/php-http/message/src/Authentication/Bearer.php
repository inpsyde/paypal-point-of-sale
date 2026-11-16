<?php

namespace Syde\Vendor\Zettle\Http\Message\Authentication;

use Syde\Vendor\Zettle\Http\Message\Authentication;
use Syde\Vendor\Zettle\Psr\Http\Message\RequestInterface;
/**
 * Authenticate a PSR-7 Request using a token.
 *
 * @author Márk Sági-Kazár <mark.sagikazar@gmail.com>
 */
final class Bearer implements Authentication
{
    /**
     * @var string
     */
    private $token;
    /**
     * @param string $token
     */
    public function __construct($token)
    {
        $this->token = $token;
    }
    public function authenticate(RequestInterface $request)
    {
        $header = sprintf('Bearer %s', $this->token);
        return $request->withHeader('Authorization', $header);
    }
}
