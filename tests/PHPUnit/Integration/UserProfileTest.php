<?php

use Syde\PayPal\PointOfSale\PhpSdk\API\OAuth\Users;
use Syde\PayPal\PointOfSale\Test\AuthenticatedRestRequestTestCase;

/**
 * Class UserProfileTest
 * @group sync
 */
class UserProfileTest extends AuthenticatedRestRequestTestCase
{

    public function testMe()
    {
        /**
         * @var Users
         */
        $testee = $this->users();
        $result = $testee->me();
        $this->assertNotEmpty($result);
        $hurr=1;
    }

    private function users(): Users
    {
        return $this->get('paypal-pos.sdk.api.oauth.users');
    }
}
