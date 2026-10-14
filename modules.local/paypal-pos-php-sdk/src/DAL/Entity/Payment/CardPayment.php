<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Payment;

use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Payment\Type\PaymentType;

final class CardPayment extends AbstractPaymentMethod
{
    private string $referenceNumber;

    private string $maskedPan;

    private string $cardType;

    private string $cardPaymentEntryMode;

    private ?string $applicationName = null;

    private ?string $applicationIdentifier = null;

    private ?string $terminalVerificationResults = null;

    private ?int $numberOfInstallments = null;

    private ?int $installmentAmount = null;

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
    public function __construct(
        string $uuid,
        float $amount,
        string $referenceNumber,
        string $maskedPan,
        string $cardType,
        string $cardPaymentEntryMode,
        ?string $applicationName = null,
        ?string $applicationIdentifier = null,
        ?string $terminalVerificationResults = null,
        ?int $numberOfInstallments = null,
        ?int $installmentAmount = null
    ) {

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

    public function applicationName(): ?string
    {
        return $this->applicationName;
    }

    public function applicationIdentifier(): ?string
    {
        return $this->applicationIdentifier;
    }

    public function terminalVerificationResults(): ?string
    {
        return $this->terminalVerificationResults;
    }

    public function numberOfInstallments(): ?int
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
