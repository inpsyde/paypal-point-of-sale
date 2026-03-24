<?php
declare(strict_types=1);
/**
 * phpcs:disable Inpsyde.CodeQuality.ReturnTypeDeclaration
 * phpcs:disable Inpsyde.CodeQuality.ArgumentTypeDeclaration
 */

namespace Inpsyde\Queue\Tests;

use Inpsyde\Queue\Queue\EphemeralLocker;
use Inpsyde\Queue\Queue\Job\EphemeralJobRepository;
use Inpsyde\Queue\Queue\Job\JobRepository;
use Inpsyde\Queue\Queue\Locker;
use Inpsyde\Queue\Queue\QueueWalker;
use Inpsyde\Queue\Queue\UnstoppableQueueWalker;
use Psr\Container\ContainerInterface;

class EphemeralQueueTestCase extends QueueStandaloneTestCase
{

    public function setUp(): void
    {
        $this->injectFactory(
            'inpsyde.queue.repository',
            $this->single(
                function (): JobRepository {
                    return new EphemeralJobRepository();
                }
            )
        );
        $this->injectFactory(
            'inpsyde.queue.locker',
            $this->single(
                function (): Locker {
                    return new EphemeralLocker();
                }
            )
        );
        $this->injectFactory(
            'inpsyde.queue.walker',
            $this->single(
                function (ContainerInterface $container): QueueWalker {
                    return new UnstoppableQueueWalker($container->get('inpsyde.queue.iterator'));
                }
            )
        );
        parent::setUp();
    }
}
