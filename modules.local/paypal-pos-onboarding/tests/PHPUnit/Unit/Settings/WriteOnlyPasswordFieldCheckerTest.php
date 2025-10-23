<?php

declare(strict_types=1);

use Syde\PayPal\PointOfSale\Onboarding\Settings\WriteOnlyPasswordFieldChecker;
use PHPUnit\Framework\TestCase;

class WriteOnlyPasswordFieldCheckerTest extends TestCase
{
    protected $placeholderChar = '-';

    protected $maxPlaceholderLength = 3;

    protected $sut;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sut = new WriteOnlyPasswordFieldChecker($this->placeholderChar, $this->maxPlaceholderLength);
    }

    /**
     * @dataProvider filledValues
     */
    public function testFilled(string $value)
    {
        self::assertTrue(($this->sut)($value));
    }

    /**
     * @dataProvider notFilledValues
     */
    public function testNotFilled(string $value)
    {
        self::assertFalse(($this->sut)($value));
    }

    public static function filledValues()
    {
        yield ['a'];
        yield ['abc'];
        yield ['abc123'];
        yield ['abc123-'];
    }

    public static function notFilledValues()
    {
        yield [''];
        yield ['            '];
        yield ['-'];
        yield ['---'];
        yield ['a-'];
    }
}
