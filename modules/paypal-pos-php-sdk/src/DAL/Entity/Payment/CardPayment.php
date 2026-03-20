<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Payment;

use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Payment\Type\PaymentType;
final class CardPayment extends AbstractPaymentMethod
{
    /**
     * @var string
     */
    private $referenceNumber;
    /**
     * @var string
     */
    private $maskedPan;
    /**
     * @var string
     */
    private $cardType;
    /**
     * @var string
     */
    private $cardPaymentEntryMode;
    /**
     * @var string|null
     */
    private $applicationName;
    /**
     * @var string|null
     */
    private $applicationIdentifier;
    /**
     * @var string|null
     */
    private $terminalVerificationResults;
    /**
     * @var int|null
     */
    private $numberOfInstallments;
    /**
     * @var int|null
     */
    private $installmentAmount;
    /**
     * CardPayment constructor.
     *
     * @param string $uuid
     * @param float $amount
     * @param string $referenceNumber
     * @param string $maskedPan
     * @param string $cardType
     * @param string $cardPaymentEntryMode
     * @param string|null $applicationName
     * @param string|null $applicationIdentifier
     * @param string|null $terminalVerificationResults
     * @param int|null $numberOfInstallments
     * @param int|null $installmentAmount
     */
    public function __construct(string $uuid, float $amount, string $referenceNumber, string $maskedPan, string $cardType, string $cardPaymentEntryMode, ?string $applicationName = null, ?string $applicationIdentifier = null, ?string $terminalVerificationResults = null, ?int $numberOfInstallments = null, ?int $installmentAmount = null)
    {
        parent::__construct($uuid, $amount, PaymentType::cardPayment());
        $this->referenceNumber = $referenceNumber;
        $this->maskedPan = $maskedPan;
        $this->cardType = $cardType;
        $this->cardPaymentEntryMode = $cardPaymentEntryMode;
        $this->applicationName = $applicationName;
        $this->applicationIdentifier = $applicationIdentifier;
        $this->terminalVerificationResults = $terminalVerificationResults;
        $this->numberOfInstallments = $numberOfInstallments;
        $this->installmentAmount = $installmentAmount;
    }
    /**
     * @return string
     */
    public function referenceNumber(): string
    {
        return $this->referenceNumber;
    }
    /**
     * @return string
     */
    public function maskedPan(): string
    {
        return $this->maskedPan;
    }
    /**
     * @return string
     */
    public function cardType(): string
    {
        return $this->cardType;
    }
    /**
     * @return string
     */
    public function cardPaymentEntryMode(): string
    {
        return $this->cardPaymentEntryMode;
    }
    /**
     * @return string
     */
    public function applicationName(): string
    {
        return $this->applicationName;
    }
    /**
     * @return string
     */
    public function applicationIdentifier(): string
    {
        return $this->applicationIdentifier;
    }
    /**
     * @return string
     */
    public function terminalVerificationResults(): string
    {
        return $this->terminalVerificationResults;
    }
    /**
     * @return int
     */
    public function numberOfInstallments(): int
    {
        return $this->numberOfInstallments;
    }
    /**
     * @return int|null
     */
    public function installmentAmount(): ?int
    {
        return $this->installmentAmount;
    }
}
