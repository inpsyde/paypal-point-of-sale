<?php
declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Test;

use Mockery\Adapter\Phpunit\MockeryTestCase;
use function Brain\Monkey\Functions\when;
use function Brain\Monkey\setUp;
use function Brain\Monkey\tearDown;

class MonkeryTestCase extends MockeryTestCase
{
	protected function setUp(): void
	{
		parent::setUp();
        setUp();

        when('trailingslashit')->alias(function ($value) {
            return rtrim( $value, '/\\' ) . '/';
        });
        when('esc_html')->returnArg();
	}

	protected function tearDown(): void
	{
		parent::tearDown();
		tearDown();
	}
}
