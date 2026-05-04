<?php

namespace Syde\Vendor\Zettle\Http\Discovery\Strategy;

use Syde\Vendor\Zettle\Psr\Http\Message\RequestFactoryInterface;
use Syde\Vendor\Zettle\Psr\Http\Message\ResponseFactoryInterface;
use Syde\Vendor\Zettle\Psr\Http\Message\ServerRequestFactoryInterface;
use Syde\Vendor\Zettle\Psr\Http\Message\StreamFactoryInterface;
use Syde\Vendor\Zettle\Psr\Http\Message\UploadedFileFactoryInterface;
use Syde\Vendor\Zettle\Psr\Http\Message\UriFactoryInterface;
/**
 * @internal
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 *
 * Don't miss updating src/Composer/Plugin.php when adding a new supported class.
 */
final class CommonPsr17ClassesStrategy implements DiscoveryStrategy
{
    /**
     * @var array
     */
    private static $classes = [RequestFactoryInterface::class => ['Syde\Vendor\Zettle\Phalcon\Http\Message\RequestFactory', 'Syde\Vendor\Zettle\Nyholm\Psr7\Factory\Psr17Factory', 'Syde\Vendor\Zettle\GuzzleHttp\Psr7\HttpFactory', 'Syde\Vendor\Zettle\Http\Factory\Diactoros\RequestFactory', 'Syde\Vendor\Zettle\Http\Factory\Guzzle\RequestFactory', 'Syde\Vendor\Zettle\Http\Factory\Slim\RequestFactory', 'Syde\Vendor\Zettle\Laminas\Diactoros\RequestFactory', 'Syde\Vendor\Zettle\Slim\Psr7\Factory\RequestFactory', 'Syde\Vendor\Zettle\HttpSoft\Message\RequestFactory'], ResponseFactoryInterface::class => ['Syde\Vendor\Zettle\Phalcon\Http\Message\ResponseFactory', 'Syde\Vendor\Zettle\Nyholm\Psr7\Factory\Psr17Factory', 'Syde\Vendor\Zettle\GuzzleHttp\Psr7\HttpFactory', 'Syde\Vendor\Zettle\Http\Factory\Diactoros\ResponseFactory', 'Syde\Vendor\Zettle\Http\Factory\Guzzle\ResponseFactory', 'Syde\Vendor\Zettle\Http\Factory\Slim\ResponseFactory', 'Syde\Vendor\Zettle\Laminas\Diactoros\ResponseFactory', 'Syde\Vendor\Zettle\Slim\Psr7\Factory\ResponseFactory', 'Syde\Vendor\Zettle\HttpSoft\Message\ResponseFactory'], ServerRequestFactoryInterface::class => ['Syde\Vendor\Zettle\Phalcon\Http\Message\ServerRequestFactory', 'Syde\Vendor\Zettle\Nyholm\Psr7\Factory\Psr17Factory', 'Syde\Vendor\Zettle\GuzzleHttp\Psr7\HttpFactory', 'Syde\Vendor\Zettle\Http\Factory\Diactoros\ServerRequestFactory', 'Syde\Vendor\Zettle\Http\Factory\Guzzle\ServerRequestFactory', 'Syde\Vendor\Zettle\Http\Factory\Slim\ServerRequestFactory', 'Syde\Vendor\Zettle\Laminas\Diactoros\ServerRequestFactory', 'Syde\Vendor\Zettle\Slim\Psr7\Factory\ServerRequestFactory', 'Syde\Vendor\Zettle\HttpSoft\Message\ServerRequestFactory'], StreamFactoryInterface::class => ['Syde\Vendor\Zettle\Phalcon\Http\Message\StreamFactory', 'Syde\Vendor\Zettle\Nyholm\Psr7\Factory\Psr17Factory', 'Syde\Vendor\Zettle\GuzzleHttp\Psr7\HttpFactory', 'Syde\Vendor\Zettle\Http\Factory\Diactoros\StreamFactory', 'Syde\Vendor\Zettle\Http\Factory\Guzzle\StreamFactory', 'Syde\Vendor\Zettle\Http\Factory\Slim\StreamFactory', 'Syde\Vendor\Zettle\Laminas\Diactoros\StreamFactory', 'Syde\Vendor\Zettle\Slim\Psr7\Factory\StreamFactory', 'Syde\Vendor\Zettle\HttpSoft\Message\StreamFactory'], UploadedFileFactoryInterface::class => ['Syde\Vendor\Zettle\Phalcon\Http\Message\UploadedFileFactory', 'Syde\Vendor\Zettle\Nyholm\Psr7\Factory\Psr17Factory', 'Syde\Vendor\Zettle\GuzzleHttp\Psr7\HttpFactory', 'Syde\Vendor\Zettle\Http\Factory\Diactoros\UploadedFileFactory', 'Syde\Vendor\Zettle\Http\Factory\Guzzle\UploadedFileFactory', 'Syde\Vendor\Zettle\Http\Factory\Slim\UploadedFileFactory', 'Syde\Vendor\Zettle\Laminas\Diactoros\UploadedFileFactory', 'Syde\Vendor\Zettle\Slim\Psr7\Factory\UploadedFileFactory', 'Syde\Vendor\Zettle\HttpSoft\Message\UploadedFileFactory'], UriFactoryInterface::class => ['Syde\Vendor\Zettle\Phalcon\Http\Message\UriFactory', 'Syde\Vendor\Zettle\Nyholm\Psr7\Factory\Psr17Factory', 'Syde\Vendor\Zettle\GuzzleHttp\Psr7\HttpFactory', 'Syde\Vendor\Zettle\Http\Factory\Diactoros\UriFactory', 'Syde\Vendor\Zettle\Http\Factory\Guzzle\UriFactory', 'Syde\Vendor\Zettle\Http\Factory\Slim\UriFactory', 'Syde\Vendor\Zettle\Laminas\Diactoros\UriFactory', 'Syde\Vendor\Zettle\Slim\Psr7\Factory\UriFactory', 'Syde\Vendor\Zettle\HttpSoft\Message\UriFactory']];
    public static function getCandidates($type)
    {
        $candidates = [];
        if (isset(self::$classes[$type])) {
            foreach (self::$classes[$type] as $class) {
                $candidates[] = ['class' => $class, 'condition' => [$class]];
            }
        }
        return $candidates;
    }
}
