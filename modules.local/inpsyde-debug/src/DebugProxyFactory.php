<?php

declare(strict_types=1);

namespace Inpsyde\Debug;

use ReflectionClass;
use ReflectionException;
use ReflectionMethod;

class DebugProxyFactory
{

    /**
     * @var ExceptionHandler
     */
    private $handler;

    public function __construct(ExceptionHandler $handler)
    {
        $this->handler = $handler;
    }

    /**
     * Creates a new proxy class for that will log any exceptions travelling through
     * the specified methods (or all of them if none are specified)
     * With the help of Reflection, this factory will create a new class that extends
     * as well as decorates the given object. This means it is fully compatible with
     * all of its method signatures and delegates everything to it - after wrapping
     * it into a try/catch block that logs and re-throws any exception that went through it
     *
     * @param $subject
     * @param string ...$methods
     * phpcs:disable Inpsyde.CodeQuality.ArgumentTypeDeclaration.NoArgumentType
     * phpcs:disable Inpsyde.CodeQuality.ReturnTypeDeclaration.NoReturnType
     * phpcs:disable Generic.Metrics.NestingLevel.TooHigh
     *
     * @return mixed
     */
    public function forInstanceMethods($subject, string ...$methods)
    {
        try {
            $reflectionClass = new ReflectionClass($subject);
            $methodsToProxy = $this->determineProxyMethods($subject, $methods);

            $methods = array_map(
                function (string $name) use ($subject, $methodsToProxy) {
                    if ($name === '__construct') {
                        return '';
                    }
                    $methodBody = in_array($name, $methodsToProxy, true)
                        ? $this->renderDebugMethodBody($name)
                        : $this->renderDecoratorMethodBody($name);
                    $methodBody = PHP_EOL . $methodBody . PHP_EOL;

                    return sprintf(
                        '%s{%s}',
                        new MethodSignature(new ReflectionMethod($subject, $name)),
                        $methodBody
                    );
                },
                get_class_methods($subject)
            );
            $className = get_class($subject);
            $classNameSuffix = uniqid('');
            $proxyClassName = $this->renderClassName($reflectionClass, $classNameSuffix);
            $phpCode = sprintf(
                '%s class %s extends \%s { %s %s };',
                $this->renderNamespace($reflectionClass),
                $this->renderClassName($reflectionClass, $classNameSuffix, true),
                $className,
                $this->renderConstructor($className),
                implode(PHP_EOL, $methods)
            );
            // phpcs:disable Squiz.PHP.Eval.Discouraged
            eval($phpCode);

            return new $proxyClassName($subject, $this->handler);
        } catch (ReflectionException $exc) {
            return $subject;
        }
    }

    private function renderConstructor(string $subjectFqcn): string
    {
        return sprintf(
            <<<'PHPCODE'

private $proxied;
private $handler;

public function __construct( \%1$s $proxied, \%2$s $handler ){
    $this->proxied=$proxied;
    $this->handler=$handler;
}
PHPCODE
            ,
            $subjectFqcn,
            ExceptionHandler::class
        );
    }

    private function renderDebugMethodBody(string $name)
    {
        return sprintf(
            <<<'PHPCODE'
    try{
        return $this->proxied->%s(...func_get_args());
    }catch(\Throwable $exc){
        $this->handler->handle($exc);
        throw $exc;
    }
PHPCODE
            ,
            $name
        );
    }

    private function renderDecoratorMethodBody(string $name)
    {
        return sprintf(
            <<<'PHPCODE'
        return $this->proxied->%s(...func_get_args());
PHPCODE
            ,
            $name
        );
    }

    private function renderClassName(
        ReflectionClass $reflectionClass,
        string $suffix,
        bool $short = false
    ): string {

        $className = $short
            ? $reflectionClass->getShortName()
            : $reflectionClass->getName();

        return sprintf('%sDebugProxy_%s', $className, $suffix);
    }

    private function renderNamespace(ReflectionClass $reflectionClass): string
    {
        return sprintf('namespace %s;%s', $reflectionClass->getNamespaceName(), PHP_EOL);
    }

    private function determineProxyMethods($subject, array $requestedMethods): array
    {
        $classMethods = get_class_methods($subject);

        if (empty($requestedMethods)) {
            return $classMethods;
        }

        return array_intersect($classMethods, $requestedMethods);
    }
}
