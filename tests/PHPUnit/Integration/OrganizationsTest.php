<?php

use Syde\PayPal\PointOfSale\PhpSdk\API\OAuth\Organizations;
use Syde\PayPal\PointOfSale\Test\AuthenticatedRestRequestTestCase;

/**
 * Class OrganizationsTest
 * @group sync
 */
class OrganizationsTest extends AuthenticatedRestRequestTestCase
{

    public function testAccount()
    {
        /**
         * @var Organizations
         */
        $testee = $this->organizations();
        $result = $testee->account();
        $this->assertNotEmpty($result);
        $hurr = 1;
    }

    private function organizations(): Organizations
    {
        return $this->get('paypal-pos.sdk.api.oauth.organizations');
    }
}
