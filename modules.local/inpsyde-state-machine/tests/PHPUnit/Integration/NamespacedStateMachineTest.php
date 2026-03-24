<?php
declare(strict_types=1);

use Inpsyde\StateMachine\Event\PostTransition;
use Inpsyde\StateMachine\Event\PreTransition;
use Inpsyde\StateMachine\Event\TransitionAwareListenerProvider;
use Inpsyde\StateMachine\State\State;
use Inpsyde\StateMachine\StateMachine;
use Inpsyde\StateMachine\Test\StateMachineStandaloneTestCase;
use Inpsyde\StateMachine\Transition\Transition;
use Psr\Container\ContainerInterface;

class NamespacedStateMachineTest extends StateMachineStandaloneTestCase
{

    /**
     * @var PostTransition|null
     */
    private $preTransitionEvent;

    /**
     * @var PostTransition|null
     */
    private $postTransitionEvent;

    public function setUp(): void
    {
        $args = $this->getProvidedData();
        $namespace = $args[0];
        $this->injectFactory(
            "inpsyde.state-machine.namespace",
            $this->scalar($namespace)
        );
        $this->injectFactory(
            "{$namespace}.states",
            $this->single(
                function (): array {
                    return [
                        new State('foo', State::TYPE_INITIAL),
                        new State('bar'),
                    ];
                }
            )
        );
        $this->injectFactory(
            "{$namespace}.transitions",
            $this->single(
                function (): array {
                    return [
                        new Transition('baz', ['foo'], 'bar'),
                    ];
                }
            )
        );

        $this->injectExtension(
            "inpsyde.state-machine.events.listener-provider.transition-aware",
            $this->single(
                function (
                    TransitionAwareListenerProvider $listenerProvider,
                    ContainerInterface $container
                ): TransitionAwareListenerProvider {
                    $listenerProvider->listen('baz', function (PreTransition $event): void {
                        $this->preTransitionEvent = $event;
                    });
                    $listenerProvider->listen('baz', function (PostTransition $event): void {
                        $this->postTransitionEvent = $event;
                    });

                    return $listenerProvider;
                }
            )
        );
        parent::setUp();
    }

    /**
     * @dataProvider defaultData
     */
    public function testWithNamespace(string $namespace)
    {
        $stateMachine = $this->stateMachine();
        $this->assertSame('foo', $stateMachine->currentState()->name());
        $transitions = $stateMachine->availableTransitions();
        $this->assertCount(1, $transitions);
        foreach ($transitions as $transition) {
            if ($stateMachine->currentState()->can($transition)) {
                $this->assertSame('baz', $transition->name());
                $stateMachine->apply($transition->name());
            }
        }
        $this->assertSame('bar', $stateMachine->currentState()->name());
        $this->assertEquals('foo', $this->preTransitionEvent->fromState());
        $this->assertEquals('foo', $this->postTransitionEvent->fromState());
    }

    private function stateMachine(): StateMachine
    {
        return $this->get('inpsyde.state-machine');
    }

    public function defaultData()
    {
        yield 'inpsyde' => [
            'inpsyde',
        ];
        yield 'biont' => [
            'biont',
        ];
    }
}
