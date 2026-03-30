<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Payment;

use DateTime;
use Exception;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Payment\Type\PaymentType;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Exception\EntityFactoryException;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Validator\Payment\PaymentValidator;
use Syde\PayPal\PointOfSale\PhpSdk\Exception\ValidatorException;

class PaymentFactory
{
    private PaymentValidator $paymentValidator;

    /**
     * PaymentFactory constructor.
     *
     * @param PaymentValidator $paymentValidator
     */
    public function __construct(PaymentValidator $paymentValidator)
    {
        $this->paymentValidator = $paymentValidator;
    }

    /**
     * @param string $uuid
     * @param float $amount
     * @param string $paymentType
     *
     * @return CardOnlinePayment
     *
     * @throws EntityFactoryException
     */
    public function createCardOnlinePayment(
        string $uuid,
        float $amount,
        string $paymentType
    ): CardOnlinePayment {

        try {
            $this->paymentValidator->validate(
                PaymentType::CARD_ONLINE,
                $paymentType
            );
        } catch (ValidatorException $validatorException) {
            throw new EntityFactoryException(
                sprintf(
                    '%s Entity cannot be created, because of: %s',
                    CardOnlinePayment::class,
                    esc_html($validatorException->getMessage())
                )
            );
        }

        return new CardOnlinePayment($uuid, $amount);
    }

    /**
     * @param string $uuid
     * @param float $amount
     * @param string $paymentType
     * @param string $referenceNumber
     * @param string $maskedPan
     * @param string $cardType
     * @param string $cardPaymentEntryMode
     * @param string|null $applicationName
     * @param string|null $applicationIdentifier
     * @param string|null $terminalVerificationResults
     * @param int|null $numberOfInstallments
     *
     * @return CardPayment
     *
     * @throws EntityFactoryException
     */
    public function createCardPayment(
        string $uuid,
        float $amount,
        string $paymentType,
        string $referenceNumber,
        string $maskedPan,
        string $cardType,
        string $cardPaymentEntryMode,
        ?string $applicationName = null,
        ?string $applicationIdentifier = null,
        ?string $terminalVerificationResults = null,
        ?int $numberOfInstallments = null
    ): CardPayment {

        try {
            $this->paymentValidator->validate(
                PaymentType::CARD,
                $paymentType
            );
        } catch (ValidatorException $validatorException) {
            throw new EntityFactoryException(
                sprintf(
                    '%s Entity cannot be created, because of: %s',
                    CardPayment::class,
                    esc_html($validatorException->getMessage())
                )
            );
        }

        return new CardPayment(
            $uuid,
            $amount,
            $referenceNumber,
            $maskedPan,
            $cardType,
            $cardPaymentEntryMode,
            $applicationName,
            $applicationIdentifier,
            $terminalVerificationResults,
            $numberOfInstallments
        );
    }

    /**
     * @param string $uuid
     * @param string $paymentType
     * @param string $amount
     * @param string $handedAmount
     *
     * @return CashPayment
     *
     * @throws EntityFactoryException
     */
    public function createCashPayment(
        string $uuid,
        string $paymentType,
        string $amount,
        string $handedAmount
    ): CashPayment {

        try {
            $this->paymentValidator->validate(
                PaymentType::CASH,
                $paymentType
            );
        } catch (ValidatorException $validatorException) {
            throw new EntityFactoryException(
                sprintf(
                    '%s Entity cannot be created, because of: %s',
                    CashPayment::class,
                    esc_html($validatorException->getMessage())
                )
            );
        }

        return new CashPayment(
            $uuid,
            (float) $amount,
            (float) $handedAmount
        );
    }

    /**
     * @param string $uuid
     * @param string $paymentType
     * @param float $amount
     *
     * @return CustomPayment
     *
     * @throws EntityFactoryException
     */
    public function createCustomPayment(
        string $uuid,
        string $paymentType,
        float $amount
    ): CustomPayment {

        try {
            $this->paymentValidator->validate(
                PaymentType::CUSTOM,
                $paymentType
            );
        } catch (ValidatorException $validatorException) {
            throw new EntityFactoryException(
                sprintf(
                    '%s Entity cannot be created, because of: %s',
                    CustomPayment::class,
                    esc_html($validatorException->getMessage())
                )
            );
        }

        return new CustomPayment($uuid, $amount);
    }

    /**
     * @param string $uuid
     * @param string $paymentType
     * @param float $amount
     *
     * @return GiftcardPayment
     *
     * @throws EntityFactoryException
     */
    public function createGiftcardPayment(
        string $uuid,
        string $paymentType,
        float $amount
    ): GiftcardPayment {

        try {
            $this->paymentValidator->validate(
                PaymentType::GIFTCARD,
                $paymentType
            );
        } catch (ValidatorException $validatorException) {
            throw new EntityFactoryException(
                sprintf(
                    '%s Entity cannot be created, because of: %s',
                    GiftcardPayment::class,
                    esc_html($validatorException->getMessage())
                )
            );
        }

        return new GiftcardPayment($uuid, $amount);
    }

    /**
     * @param string $uuid
     * @param string $paymentType
     * @param float $amount
     * @param string $orderUuid
     * @param string $invoiceNumber
     * @param string $dueDate
     *
     * @return InvoicePayment
     *
     * @throws EntityFactoryException
     * @throws Exception
     */
    public function createInvoicePayment(
        string $uuid,
        string $paymentType,
        float $amount,
        string $orderUuid,
        string $invoiceNumber,
        string $dueDate
    ): InvoicePayment {

        try {
            $this->paymentValidator->validate(
                PaymentType::INVOICE,
                $paymentType
            );
        } catch (ValidatorException $validatorException) {
            throw new EntityFactoryException(
                sprintf(
                    '%s Entity cannot be created, because of: %s',
                    InvoicePayment::class,
                    esc_html($validatorException->getMessage())
                )
            );
        }

        return new InvoicePayment(
            $uuid,
            $amount,
            $orderUuid,
            $invoiceNumber,
            new DateTime($dueDate)
        );
    }

    /**
     * @param string $uuid
     * @param string $paymentType
     * @param float $amount
     *
     * @return KlarnaPayment
     *
     * @throws EntityFactoryException
     */
    public function createKlarnaPayment(
        string $uuid,
        string $paymentType,
        float $amount
    ): KlarnaPayment {

        try {
            $this->paymentValidator->validate(
                PaymentType::KLARNA,
                $paymentType
            );
        } catch (ValidatorException $validatorException) {
            throw new EntityFactoryException(
                sprintf(
                    '%s Entity cannot be created, because of: %s',
                    KlarnaPayment::class,
                    esc_html($validatorException->getMessage())
                )
            );
        }

        return new KlarnaPayment($uuid, $amount);
    }

    /**
     * @param string $uuid
     * @param string $paymentType
     * @param float $amount
     *
     * @return MobilePayment
     *
     * @throws EntityFactoryException
     */
    public function createMobilePayment(
        string $uuid,
        string $paymentType,
        float $amount
    ): MobilePayment {

        try {
            $this->paymentValidator->validate(
                PaymentType::MOBILE,
                $paymentType
            );
        } catch (ValidatorException $validatorException) {
            throw new EntityFactoryException(
                sprintf(
                    '%s Entity cannot be created, because of: %s',
                    MobilePayment::class,
                    esc_html($validatorException->getMessage())
                )
            );
        }

        return new MobilePayment($uuid, $amount);
    }

    /**
     * @param string $uuid
     * @param string $paymentType
     * @param float $amount
     *
     * @return PaypalPayment
     *
     * @throws EntityFactoryException
     */
    public function createPaypalPayment(
        string $uuid,
        string $paymentType,
        float $amount
    ): PaypalPayment {

        try {
            $this->paymentValidator->validate(
                PaymentType::PAYPAL,
                $paymentType
            );
        } catch (ValidatorException $validatorException) {
            throw new EntityFactoryException(
                sprintf(
                    '%s Entity cannot be created, because of: %s',
                    PaypalPayment::class,
                    esc_html($validatorException->getMessage())
                )
            );
        }

        return new PaypalPayment($uuid, $amount);
    }

    /**
     * @param string $uuid
     * @param string $paymentType
     * @param float $amount
     *
     * @return StoreCreditPayment
     *
     * @throws EntityFactoryException
     */
    public function createStoreCreditPayment(
        string $uuid,
        string $paymentType,
        float $amount
    ): StoreCreditPayment {

        try {
            $this->paymentValidator->validate(
                PaymentType::STORE_CREDIT,
                $paymentType
            );
        } catch (ValidatorException $validatorException) {
            throw new EntityFactoryException(
                sprintf(
                    '%s Entity cannot be created, because of: %s',
                    StoreCreditPayment::class,
                    esc_html($validatorException->getMessage())
                )
            );
        }

        return new StoreCreditPayment($uuid, $amount);
    }

    /**
     * @param string $uuid
     * @param string $paymentType
     * @param float $amount
     *
     * @return SwishPayment
     *
     * @throws EntityFactoryException
     */
    public function createSwishPayment(
        string $uuid,
        string $paymentType,
        float $amount
    ): SwishPayment {

        try {
            $this->paymentValidator->validate(
                PaymentType::SWISH,
                $paymentType
            );
        } catch (ValidatorException $validatorException) {
            throw new EntityFactoryException(
                sprintf(
                    '%s Entity cannot be created, because of: %s',
                    SwishPayment::class,
                    esc_html($validatorException->getMessage())
                )
            );
        }

        return new SwishPayment($uuid, $amount);
    }

    /**
     * @param string $uuid
     * @param string $paymentType
     * @param float $amount
     *
     * @return VippsPayment
     *
     * @throws EntityFactoryException
     */
    public function createVippsPayment(
        string $uuid,
        string $paymentType,
        float $amount
    ): VippsPayment {

        try {
            $this->paymentValidator->validate(
                PaymentType::VIPPS,
                $paymentType
            );
        } catch (ValidatorException $validatorException) {
            throw new EntityFactoryException(
                sprintf(
                    '%s Entity cannot be created, because of: %s',
                    VippsPayment::class,
                    esc_html($validatorException->getMessage())
                )
            );
        }

        return new VippsPayment($uuid, $amount);
    }
}
