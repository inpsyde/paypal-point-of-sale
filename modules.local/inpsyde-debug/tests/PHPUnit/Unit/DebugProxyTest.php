<?php
declare(strict_types=1);

namespace Inpsyde\Debug\Tests\Unit;

use Inpsyde\Debug\DebugProxyFactory;
use Inpsyde\Debug\ExceptionHandler;
use Inpsyde\Debug\Tests\Stub\Foo;
use Inpsyde\Debug\Tests\Stub\VerySpecificException;
use Mockery;
use PHPUnit\Framework\TestCase;

/**
 * phpcs:disable
 */
class DebugProxyTest extends TestCase
{
    public function tearDown(): void {

        parent::tearDown();
        Mockery::close();
    }

    public function testWithoutParams()
    {
        /**
         * Create an instance of our base class which throws an exception
         */
        $class = new Foo();
        /**
         * Create a bogus exception handler. We just wanna know it's been called
         */
        $handler = Mockery::mock(ExceptionHandler::class);
        $handler->shouldReceive('handle')->once();

        /**
         * Create a proxy around the Foo class
         */
        $proxyFactory = new DebugProxyFactory($handler);
        $proxied = $proxyFactory->forInstanceMethods($class);
        $this->expectException(VerySpecificException::class);
        /**
         * Now call one of the methods of Foo, which should work transparently
         */
        $proxied->withoutParams();
    }

    public function testWithParamTypes()
    {
        $class = new Foo();
        $handler = Mockery::mock(ExceptionHandler::class);
        $handler->shouldReceive('handle')->once();
        $proxyFactory = new DebugProxyFactory($handler);
        $proxied = $proxyFactory->forInstanceMethods($class);
        $this->expectException(VerySpecificException::class);
        $proxied->withParamTypes(999, ['foo']);
    }

    public function testWithoutParamType()
    {
        $class = new Foo();
        $handler = Mockery::mock(ExceptionHandler::class);
        $handler->shouldReceive('handle')->once();
        $proxyFactory = new DebugProxyFactory($handler);
        $proxied = $proxyFactory->forInstanceMethods($class);
        $this->expectException(VerySpecificException::class);
        $proxied->withoutParamType('durr');
    }

    public function testWithNullable()
    {
        $class = new Foo();
        $handler = Mockery::mock(ExceptionHandler::class);
        $handler->shouldReceive('handle')->once();
        $proxyFactory = new DebugProxyFactory($handler);
        $proxied = $proxyFactory->forInstanceMethods($class);
        $this->expectException(VerySpecificException::class);
        $proxied->withNullable('durr');
    }

    public function testWithVariadic()
    {
        $class = new Foo();
        $handler = Mockery::mock(ExceptionHandler::class);
        $handler->shouldReceive('handle')->once();
        $proxyFactory = new DebugProxyFactory($handler);
        $proxied = $proxyFactory->forInstanceMethods($class);
        $this->expectException(VerySpecificException::class);
        $proxied->withVariadic('foo', 'bar', 'baz');
    }

    public function testWithReturnValue()
    {
        $class = new Foo();
        $handler = Mockery::mock(ExceptionHandler::class);
        $proxyFactory = new DebugProxyFactory($handler);
        $proxied = $proxyFactory->forInstanceMethods($class);
        $this->assertSame($class->withReturnValue(), $proxied->withReturnValue());
    }

    public function testWithInternalState()
    {
        $class = new Foo();
        $handler = Mockery::mock(ExceptionHandler::class);
        $proxyFactory = new DebugProxyFactory($handler);
        $proxied = $proxyFactory->forInstanceMethods($class, 'somethingThatDoesNotExist');
        $this->assertSame($class->withInternalState(), $proxied->withInternalState());
    }

    public function testPartialProxy()
    {
        $class = new Foo();
        $handler = Mockery::mock(ExceptionHandler::class);
        $handler->shouldReceive('handle')->times(1);
        $proxyFactory = new DebugProxyFactory($handler);
        $proxied = $proxyFactory->forInstanceMethods($class, 'withoutParams');
        $exceptions = 0;
        try {
            $proxied->withoutParams();
        } catch (VerySpecificException $e) {
            $exceptions++;
        }

        try {
            $proxied->withParamTypes(1, []);
        } catch (VerySpecificException $e) {
            $exceptions++;
        }
        $this->assertSame(2, $exceptions);
    }
}
