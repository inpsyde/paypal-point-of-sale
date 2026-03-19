<?php

namespace Syde\Vendor\Zettle\Dhii\Events\Test\Unit\Event;

use Syde\Vendor\Zettle\PHPUnit\Framework\MockObject\MockObject;
use Syde\Vendor\Zettle\PHPUnit\Framework\TestCase;
use Syde\Vendor\Zettle\Dhii\Events\Event\IsPropagationStoppedCapableInterface as TestSubject;
class IsPropagationStoppedCapableInterfaceTest extends TestCase
{
    /**
     * Creates a new instance of the test subject.
     *
     * @return TestSubject|MockObject
     */
    public function createInstance()
    {
        $mock = $this->getMockBuilder(TestSubject::class)->getMock();
        return $mock;
    }
    /**
     * Tests whether an instance of the test subject can be created.
     */
    public function testCanBeCreated()
    {
        $subject = $this->createInstance();
        $this->assertInstanceOf(TestSubject::class, $subject);
    }
}
