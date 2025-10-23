<?php


namespace Inpsyde\StateMachine\Test;


use Inpsyde\StateMachine\State\State;
use Inpsyde\StateMachine\Transition\Transition;
use PHPUnit\Framework\TestCase;

class StateTest extends TestCase
{
    public function testAddTransition()
    {
        $state = new State('test');
        $this->assertEquals([], $state->transitions());
        $transition = new Transition('test', ['test'], 'done');
        $state->addTransition($transition);
        $this->assertEquals($transition, $state->transitions()['test']);

        $transition = new Transition('ship', ['ship'], 'done');
        $state->addTransition($transition);

        $this->assertEquals($transition, $state->transitions()['ship']);


    }

    public function testCan()
    {
        $state = new State('test');
        $transition = new Transition('ship', ['ship'], 'done');

        $state->addTransition($transition);
        $this->assertTrue($state->can('ship'));
        $this->assertFalse($state->can('test'));
    }
}