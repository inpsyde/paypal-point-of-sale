<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Auth\HTTPlug;

use Http\Client\Common\Plugin;
use Http\Message\Authentication;
use Http\Promise\Promise;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
use Syde\PayPal\PointOfSale\Auth\Exception\AuthenticationException;
use Syde\PayPal\PointOfSale\Auth\Exception\InvalidTokenException;
use Syde\PayPal\PointOfSale\Auth\OAuth\AuthSuccessHandler;
use Syde\PayPal\PointOfSale\Auth\OAuth\Grant\GrantType;

/**
 * phpcs:disable Syde.Functions.ReturnTypeDeclaration.NoReturnType
 */
class ZettleAuthPlugin implements Plugin
{
    public const OAUTH_URL = 'https://oauth.izettle.com/token';

    private Authentication $auth;

    /**
     * Flag attempted refreshes for each request.
     */
    private array $chainStorage = [];

    /**
     * @var callable
     */
    private $shouldAuthenticate;

    private UriFactoryInterface $uriFactory;

    private StreamFactoryInterface $streamFactory;

    private GrantType $authGrantType;

    private GrantType $refreshGrantType;

    private AuthSuccessHandler $authSuccessHandler;

    public function __construct(
        Authentication $auth,
        callable $shouldAuthenticate,
        UriFactoryInterface $uriFactory,
        StreamFactoryInterface $streamFactory,
        GrantType $authGrantType,
        GrantType $refreshGrantType,
        AuthSuccessHandler $authSuccessHandler
    ) {

        $this->auth = $auth;
        $this->uriFactory = $uriFactory;
        $this->authGrantType = $authGrantType;
        $this->refreshGrantType = $refreshGrantType;
        $this->shouldAuthenticate = $shouldAuthenticate;
        $this->authSuccessHandler = $authSuccessHandler;
        $this->streamFactory = $streamFactory;
    }

    /**
     * @inheritDoc
     */
    public function handleRequest(RequestInterface $request, callable $next, callable $first): Promise
    {
        if (!($this->shouldAuthenticate)($request)) {
            return $next($request);
        }

        $chainIdentifier = spl_object_hash((object) $first);
        $authGrantType = $this->refreshGrantType;

        $request = $this->auth->authenticate($request);
        /**
         * Use auth grant if no existing authorization header could be added
         */
        if (!$request->hasHeader('Authorization')) {
            $authGrantType = $this->authGrantType;
        }

        return $next($request)->then(
            function (ResponseInterface $response) use (
                $request,
                $next,
                $first,
                $chainIdentifier,
                $authGrantType
            ) {
                if (array_key_exists($chainIdentifier, $this->chainStorage)) {
                    return $response;
                }

                $status = $response->getStatusCode();

                if ($status === 400 || $status === 401 || $status === 500) {
                    $body = $response->getBody();
                    $body->rewind();
                    $contents = $body->getContents();

                    $json = json_decode($contents, true);

                    $this->chainStorage[$chainIdentifier] = true;

                    /**
                     * Fire the attemptRefresh callback we were passed in the constructor.
                     * We will simply expect this to run successfully, so the validation
                     * is now successful when we retry
                     */
                    try {
                        $this->handleAuthRequest($first, $request, $authGrantType)->wait();
                    } catch (AuthenticationException $authException) {
                        do_action('inpsyde.zettle.auth.failed', $authException);
                        throw $authException;
                    }

                    /**
                     * Handle the request again as if nothing happened.
                     * Wait for the promise to unwrap and return the response
                     */
                    $promise = $this->handleRequest($request, $next, $first);

                    $ret = $promise->wait();

                    do_action('inpsyde.zettle.auth.succeeded');

                    return $ret;
                }

                do_action('inpsyde.zettle.auth.succeeded');

                unset($this->chainStorage[$chainIdentifier]);

                return $response;
            },
            static function (ClientExceptionInterface $exception) use ($request, $next, $first) {
                //TODO: This block is unimplemented and is merely a draft
                if ($exception->getCode() !== 401) {
                    throw $exception;
                }

                return $first($request);
            }
        );
    }

    private function handleAuthRequest(
        callable $first,
        RequestInterface $request,
        GrantType $grant
    ): Promise {

        try {
            $data = array_merge(
                [
                    'grant_type' => $grant->type(),
                ],
                $grant->payload()
            );
        } catch (InvalidTokenException $exception) {
            throw new AuthenticationException(
                'Could not retrieve payload data',
                0,
                $exception // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
            );
        }

        $pairs = [];

        foreach ($data as $key => $value) {
            $pairs[] = "$key=$value";
        }

        $payload = implode('&', $pairs);
        $authRequest = $request->withUri($this->uriFactory->createUri(self::OAUTH_URL))
            ->withMethod('POST')->withBody($this->streamFactory->createStream($payload))->withHeader(
                'Content-Type',
                'application/x-www-form-urlencoded'
            );

        return $first($authRequest)->then(
            function (ResponseInterface $response) use ($grant) {
                if ($response->getStatusCode() !== 200) {
                    $body = $response->getBody();
                    $body->rewind();
                    $contents = $body->getContents();

                    throw new AuthenticationException(
                        "Authentication attempt rejected: '" . $contents . "'", // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
                        $response->getStatusCode() // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
                    );
                }
                $this->authSuccessHandler->handle($response);
                return $response;
            }
        );
    }
}
