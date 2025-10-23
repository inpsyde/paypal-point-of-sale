<?php
declare(strict_types=1);

use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Presentation\Presentation;
use Syde\PayPal\PointOfSale\PhpSdk\Exception\ValidatorException;
use Syde\PayPal\PointOfSale\PhpSdk\Validator\PresentationValidator;
use MonkeryTestCase\BrainMonkeyWpTestCase;

/**
 * phpcs:disable
 */
class PresentationValidatorTest extends BrainMonkeyWpTestCase
{

    /**
     * @dataProvider defaultTestData
     *
     * @param bool $passes
     *
     * @throws ValidatorException
     */
    public function testValidate(bool $passes)
    {
        $color = $this->dataName();
        $entity = Mockery::mock(Presentation::class);
        $entity->shouldReceive('backgroundColor')->andReturn($color);
        $entity->shouldReceive('textColor')->andReturn($color);
        $testee = new PresentationValidator();
        if (!$passes) {
            $this->expectException(ValidatorException::class);
        }
        $this->assertSame($passes, $testee->validate($entity));
    }

    public function defaultTestData()
    {
        yield 'ccc' => [false];
        yield 'aabbcc' => [true];
        yield '#aabbcc' => [true];
        yield 'ffe5dcf7' => [true];
        yield 'I am not even a hex color lol' => [false];
    }
}
