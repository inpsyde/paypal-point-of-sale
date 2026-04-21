<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Finance\Type;

use Syde\Vendor\Zettle\Werkspot\Enum\AbstractEnum;
/**
 * @method static AccountTypeGroup liquid()
 * @method bool isLiquid()
 * @method static AccountTypeGroup preliminary()
 * @method bool isPreliminary()
 */
final class AccountTypeGroup extends AbstractEnum
{
    public const LIQUID = 'LIQUID';
    public const PRELIMINARY = 'PRELIMINARY';
}
