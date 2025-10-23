# Inpsyde Queue
## Installation

Grab the composer package via 
```composer require inpsyde/inpsyde-queue```

## Configuration

### As a module
Inpsyde Queue implements the [Dhii - Module Interface](https://github.com/Dhii/module-interface).
If your plugin uses modules, require and set it up like the rest of your modules and you're good to go

### Standalone

You can of course use Inpsyde Queue as a standalone package as well. 
Just instantiate a `QueueLibrary` with your configuration:
```php
// Sets up the internal DI Container with your configuration
$standalone = new \Inpsyde\Queue\QueueLibrary();
//Sets up actions and filters for the queue to run
$standalone->initialize();
// Access the container to fetch specific services
$container = $standalone->container();
```

### Bootstrapping
Inpsyde queue needs to perform an initial setup before it can work.
If you are using this module in a plugin, you might want to wire it up to your plugin_activation_hook:
```php
    register_activation_hook(__FILE__, function () {
        $container = init(); // Get access to your service container somehow
        $bootstrap = $container->get('inpsyde.queue.bootstrap');
        $bootstrap->activate();
    });
    register_deactivation_hook(__FILE__, function () {
        $container = init(); // Get access to your service container somehow
        $bootstrap = $container->get('inpsyde.queue.bootstrap');
        $bootstrap->deactivate();
    });
```

## Usage

### Namespacing your queue
Inpsyde will create a database table and reister hooks, wp-cli commands, etc. under the `inpsyde` namespace.
It is possible - and encouraged - to change this default namespace with something specific to your project.
This will make it possible to have several packages setup and use their own queues without conflict.

Using the Module system, simply define an extension like this:

```php

class MyModule implements ModuleInterface
{

    /**
     * @inheritDoc
     */
    public function setup(): ServiceProviderInterface
    {
        $job = function (string $type) {
            return "zettle.job.{$type}";
        };
        return new ServiceProvider([
            // Your service definitions
        ], [
            'inpsyde.queue.namespace' => function (ContainerInterface $c, string $previous) {
                return "my-project";
            }
        ]);

    }

    /**
     * @inheritDoc
     */
    public function run(ContainerInterface $c)
    {
        // Do stuff here
    }
}

```

### Registering custom Job types
By default, Inpsyde Queue will query its own Container whenever it needs to fetch and instantiate a Job object.
It will look under the `"{$namespace}.job.{$jobType}"` key. So just like you added your namespace to the configuration, 
you can register your Jobs by extending the Container.

Jobs must implement the `\Inpsyde\Queue\Queue\Job\Job` Interface:

```php
class HelloWorld implements Job
{

    const TYPE = 'hello-world';

    /**
     * @inheritDoc
     */
    public function execute(ContextInterface $context, LoggerInterface $logger): bool
    {
        $logger->notice($context->args()->message);

        return true;
    }

    /**
     * @inheritDoc
     */
    public function isUnique(): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function type(): string
    {
        return self::TYPE;
    }
}
```

### Enqueuing Jobs

You can enqueue a registered job via the hooks provided by Inpsyde Queue. Hooks are added depending on your configured namespace.
For example, you can run 
```php
  do_action('my-project.queue.create-job', 'my-job-type', ['message' => 'hello world!']);

```

### Error handling

The queue processor can perform some (very) basic error recovery which will be expanded in the future.
If the `Job::execute()`-method in your implementation returns `false` or if there is a `QueueRuntimeException` thrown during execution,
the queue-processor will reschedule the Job up to 3 times. This can be changed by setting `'inpsyde.queue.failed.retry.count'` to the desired value

However, there is currently no delay applied before a retry takes place. PRs welcome.


## WP-Cli
`wp <namespace> queue process`

Processes all jobs in the queue

`wp <namespace> queue live`

Runs the `process` command and re-checks for new items every 2 seconds
This command is intended to run forever in the background.
## Crafted by Inpsyde

The team at [Inpsyde](https://inpsyde.com) is engineering the Web since 2006.

## License

Copyright (c) 2020 Moritz Meißelbach, Inpsyde

Good news, this plugin is free for everyone! Since it's released under the [GPL-2.0 License](LICENSE) you can use it free of charge on your personal or commercial website.

## Contributing

All feedback / bug reports / pull requests are welcome.
