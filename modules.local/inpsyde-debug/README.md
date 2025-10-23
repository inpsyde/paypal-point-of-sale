# Inpsyde Debug

This module combines a couple of helpful services to aid during development and debugging. It is currently 
focused on handling Exceptions as they travel through your code

## `ExceptionHandler`
An ExceptionHandler has a `handle( \Throwable $exception )` method that can process arbitrary exceptions. 
The default and most common use case will be to use this for logging via the `LogExceptionHandler`.
You can access this module's exception handler via the `'inpsyde.debug.exception-handler'` key which will 
actually return a `CompositeExceptionHandler` that allows extensions with custom handlers:
```php

    'inpsyde.debug.exception-handler' => function (ContainerInterface $container): ExceptionHandler {
        return new CompositeExceptionHandler(...$container->get('inpsyde.debug.exception-handlers'));
    },

```

## `ExceptionFormatter`
Rendering exceptions in the most meaningful way is a common problem - especially when you're interested in the 
entire exception trace in a somewhat human-friendly format. `ExceptionFormatter` allows you to recursively 
render exception messages depending on their type. To add your own formatters, just add an extension like this:
```php
    'inpsyde.debug.exception-formatters' => function (ContainerInterface $container, array $previous): array {
        $previous[MyApiCallException::class] = new class implements ExceptionFormatter {

            public function format(\Throwable $exception): string
            {
                assert($exception instanceof MyApiCallException);

                return sprintf(
                    'MyApiCallException: %1$s%2$s%3$s%2$s%4$s%2$s',
                    $exception->getMessage(),
                    PHP_EOL,
                    $exception->errorResponse(),
                    $exception->route()
                );
            }
        };

        return $previous;
    }
```

## LogLevels

By default, the module will log any exception as `LogLevel::INFO`. 
You can customize this by adding to the `'inpsyde.debug.exception-log-levels'` map like this:

```php

    'inpsyde.debug.exception-log-levels' => function (ContainerInterface $container, array $map): array {
        $map[MyException::class] = LogLevel::WARNING; // You can use class and interface FQCNs here
        return $map;
    },

```

If no type is supplied via service extension, a fallback will be used
that should be suitable for most occasions.
To use the formatter in your own code, just grab it via the `'inpsyde.debug.exception-formatter'` service key.

## Object Proxies

Using `DebugProxyFactory`, you can create proxies of existing class instances that will catch any exception 
thrown by it an pass it to the `ExceptionHandler`. This allows you to add logging logic without any implementing or consuming class knowing about it.
It will be encapsulated in an entirely separate layer.
 
 
 You can add a logging proxy to existing services of other modules by providing an extension for them like this:

```php

    'http-client' => function (ContainerInterface $container, ClientInterface $client) {
        $proxyFactory = $container->get('inpsyde.debug.proxy-factory');
        assert($proxyFactory instanceof DebugProxyFactory);

        return $proxyFactory->forInstanceMethods($client);
    },

```