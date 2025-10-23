<?php

namespace Inpsyde\StateMachine\Test;

use Inpsyde\StateMachine\Event\EventDispatcher;
use Inpsyde\StateMachine\Exceptions\DenyTransitionException;
use Inpsyde\StateMachine\State\State;
use Inpsyde\StateMachine\StateMachine;
use Inpsyde\StateMachine\Transition\Transition;
use Mockery;
use MonkeryTestCase\BrainMonkeyWpTestCase;
use UnexpectedValueException;

class StateMachineTest extends BrainMonkeyWpTestCase
{

    public function testInitializeWithInvalidStateName()
    {
        $this->expectException(UnexpectedValueException::class);
        $eventDispatcher = Mockery::mock(EventDispatcher::class);
        $stateMachine = new StateMachine($eventDispatcher);
        $stateMachine->initialize('what');
    }

    public function testInitialize()
    {
        $state = new State('created');
        $eventDispatcher = Mockery::mock(EventDispatcher::class);
        $stateMachine = new StateMachine($eventDispatcher);
        $stateMachine->addState($state);
        $stateMachine->initialize('created');
        $this->assertEquals($state, $stateMachine->currentState());
    }

    public function testAddTransition()
    {
        $state = new State('created');
        $transition = new Transition(
            'process',
            ['created'],
            'processed'
        );

        $eventDispatcher = Mockery::mock(EventDispatcher::class);
        $stateMachine = new StateMachine($eventDispatcher);
        $stateMachine->addState($state);
        $stateMachine->addTransition($transition);

        $this->assertEquals($transition, $stateMachine->transitions()['process']);
        $this->assertEquals($transition, $state->transitions()['process']);
    }

    public function testCan()
    {
        $initialState = new State('created');
        $targetState = new State('processed');
        $transition = new Transition('process', ['created'], 'processed');
        $eventDispatcher = Mockery::mock(EventDispatcher::class);
        $stateMachine = new StateMachine($eventDispatcher);
        $stateMachine->addState($initialState)
            ->addState($targetState)->addTransition($transition)
            ->initialize('created');
        $this->assertTrue($stateMachine->can('process'));
        $this->assertFalse($stateMachine->can('hi'));
    }

    public function testGetAvailableTransitions()
    {
        $state = new State('created');

        $eventDispatcher = Mockery::mock(EventDispatcher::class);
        $stateMachine = new StateMachine($eventDispatcher);
        $stateMachine->addState($state);
        $transition = new Transition('process', ['created'], 'processed');
        $stateMachine->addTransition($transition);
        $stateMachine->initialize('created');

        $this->assertEquals([$transition->name() => $transition], $stateMachine->availableTransitions());
    }

    public function testDenyApply()
    {
        $this->expectException(DenyTransitionException::class);
        $eventDispatcher = Mockery::mock(EventDispatcher::class);
        $stateMachine = new StateMachine($eventDispatcher);

        $stateMachine->addState(new State('created'));
        $stateMachine->initialize('created');

        $this->expectException(DenyTransitionException::class);
        $this->expectExceptionMessage("Transition hello not found");
        $stateMachine->apply('hello');
    }

    public function testApply()
    {
        $eventDispatcher = Mockery::mock(EventDispatcher::class);
        $eventDispatcher->shouldReceive('dispatch');
        $stateMachine = new StateMachine($eventDispatcher);
        $stateMachine->addState(new State('created'));
        $stateMachine->addState(new State('done'));

        $transition = new Transition(
            'process',
            ['created'],
            'done'
        );
        $stateMachine->addTransition($transition);

        $stateMachine->initialize('created');

        $this->assertEquals('created', $stateMachine->currentState()->name());

        $stateMachine->apply('process');

        $this->assertEquals('done', $stateMachine->currentState()->name());
    }
}