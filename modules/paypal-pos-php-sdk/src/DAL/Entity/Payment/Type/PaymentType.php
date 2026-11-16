<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Payment\Type;

use Syde\Vendor\Zettle\Werkspot\Enum\AbstractEnum;
/**
 * @method static PaymentType cardPayment()
 * @method bool isCardPayment()
 * @method static PaymentType cardOnlinePayment()
 * @method bool isCardOnlinePayment()
 * @method static PaymentType cashPayment()
 * @method bool isCashPayment()
 * @method static PaymentType giftCardPayment()
 * @method bool isGiftCardPayment()
 * @method static PaymentType invoicePayment()
 * @method bool isInvoicePayment()
 * @method static PaymentType mobilePayment()
 * @method bool isMobilePayment()
 * @method static PaymentType klarnaPayment()
 * @method bool isKlarnaPayment()
 * @method static PaymentType paypalPayment()
 * @method bool isPaypalPayment()
 * @method static PaymentType storeCreditPayment()
 * @method bool isStoreCreditPayment()
 * @method static PaymentType swishPayment()
 * @method bool isSwishPayment()
 * @method static PaymentType vippsPayment()
 * @method bool isVippsPayment()
 * @method static PaymentType customPayment()
 * @method bool isCustomPayment()
 */
final class PaymentType extends AbstractEnum
{
    public const CARD = 'IZETTLE_CARD';
    public const CARD_ONLINE = 'IZETTLE_CARD_ONLINE';
    public const CASH = 'IZETTLE_CASH';
    public const GIFTCARD = 'GIFTCARD';
    public const INVOICE = 'IZETTLE_INVOICE';
    public const MOBILE = 'MOBILE_PAY';
    public const KLARNA = 'KLARNA';
    public const PAYPAL = 'PAYPAL';
    public const STORE_CREDIT = 'STORE_CREDIT';
    public const SWISH = 'SWISH';
    public const VIPPS = 'VIPPS';
    public const CUSTOM = 'CUSTOM';
}
