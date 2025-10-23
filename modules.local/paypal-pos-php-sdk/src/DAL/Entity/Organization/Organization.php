<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Organization;

use DateTime;
use DateTimeZone;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Vat\Vat;

final class Organization
{
    /**
     * @var string
     */
    private $uuid;

    /**
     * @var Vat|null
     */
    private $vat;

    /**
     * @var string
     */
    private $currency;

    /**
     * @var string|null
     */
    private $name;

    /**
     * @var string|null
     */
    private $city;

    /**
     * @var string|null
     */
    private $zipCode;

    /**
     * @var string|null
     */
    private $address;

    /**
     * @var string|null
     */
    private $phoneNumber;

    /**
     * @var string|null
     */
    private $contactEmail;

    /**
     * @var string|null
     */
    private $receiptEmail;

    /**
     * @var string|null
     */
    private $legalEntityType;

    /**
     * @var string|null
     */
    private $legalEntityNr;

    /**
     * @var string|null
     */
    private $country;

    /**
     * @var string|null
     */
    private $language;

    /**
     * @var DateTime|null
     */
    private $created;

    /**
     * @var string|null
     */
    private $ownerUuid;

    /**
     * @var int|null
     */
    private $organizationId;

    /**
     * @var string|null
     */
    private $customerStatus;

    /**
     * @var string
     */
    private $taxationMode;

    /**
     * @var string
     */
    private $taxationType;

    /**
     * @var string|null
     */
    private $customerType;

    /**
     * @var DateTimeZone
     */
    private $timeZone;

    /**
     * @var string|null
     */
    private $addressLine2;

    /**
     * @var string|null
     */
    private $legalName;

    /**
     * @var string|null
     */
    private $legalZipCode;

    /**
     * @var string|null
     */
    private $legalCity;

    /**
     * @var string|null
     */
    private $legalState;

    public function __construct(
        string $uuid,
        ?Vat $vat,
        string $currency,
        ?string $name = null,
        ?string $city = null,
        ?string $zipCode = null,
        ?string $address = null,
        ?string $phoneNumber = null,
        ?string $contactEmail = null,
        ?string $receiptEmail = null,
        ?string $legalEntityType = null,
        ?string $legalEntityNr = null,
        ?string $country = null,
        ?string $language = null,
        ?DateTime $created = null,
        ?string $ownerUuid = null,
        ?int $organizationId = null,
        ?string $customerStatus = null,
        string $taxationMode = TaxationMode::INCLUSIVE,
        string $taxationType = TaxationType::VAT,
        ?string $customerType = null,
        ?DateTimeZone $timeZone = null,
        ?string $addressLine2 = null,
        ?string $legalName = null,
        ?string $legalZipCode = null,
        ?string $legalCity = null,
        ?string $legalState = null
    ) {

        $this->uuid = $uuid;
        $this->vat = $vat;
        $this->currency = $currency;
        $this->name = $name;
        $this->city = $city;
        $this->zipCode = $zipCode;
        $this->address = $address;
        $this->phoneNumber = $phoneNumber;
        $this->contactEmail = $contactEmail;
        $this->receiptEmail = $receiptEmail;
        $this->legalEntityType = $legalEntityType;
        $this->legalEntityNr = $legalEntityNr;
        $this->country = $country;
        $this->language = $language;
        $this->created = $created;
        $this->ownerUuid = $ownerUuid;
        $this->organizationId = $organizationId;
        $this->customerStatus = $customerStatus;
        $this->taxationMode = $taxationMode;
        $this->taxationType = $taxationType;
        $this->customerType = $customerType;
        $this->timeZone = $timeZone;
        $this->addressLine2 = $addressLine2;
        $this->legalName = $legalName;
        $this->legalZipCode = $legalZipCode;
        $this->legalCity = $legalCity;
        $this->legalState = $legalState;
    }

    /**
     * @return string
     */
    public function uuid(): string
    {
        return $this->uuid;
    }

    /**
     * @param string $uuid
     *
     * @return Organization
     */
    public function setUuid(string $uuid): Organization
    {
        $this->uuid = $uuid;

        return $this;
    }

    public function vat(): ?Vat
    {
        return $this->vat;
    }

    public function setVat(?Vat $vat): Organization
    {
        $this->vat = $vat;

        return $this;
    }

    /**
     * @return string
     */
    public function currency(): string
    {
        return $this->currency;
    }

    /**
     * @param string $currency
     *
     * @return Organization
     */
    public function setCurrency(string $currency): Organization
    {
        $this->currency = $currency;

        return $this;
    }

    /**
     * @return string|null
     */
    public function name(): ?string
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return Organization
     */
    public function setName(string $name): Organization
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string|null
     */
    public function city(): ?string
    {
        return $this->city;
    }

    /**
     * @param string $city
     *
     * @return Organization
     */
    public function setCity(string $city): Organization
    {
        $this->city = $city;

        return $this;
    }

    /**
     * @return string|null
     */
    public function zipCode(): ?string
    {
        return $this->zipCode;
    }

    /**
     * @param string $zipCode
     *
     * @return Organization
     */
    public function setZipCode(string $zipCode): Organization
    {
        $this->zipCode = $zipCode;

        return $this;
    }

    /**
     * @return string|null
     */
    public function address(): ?string
    {
        return $this->address;
    }

    /**
     * @param string $address
     *
     * @return Organization
     */
    public function setAddress(string $address): Organization
    {
        $this->address = $address;

        return $this;
    }

    /**
     * @return string|null
     */
    public function phoneNumber(): ?string
    {
        return $this->phoneNumber;
    }

    /**
     * @param string $phoneNumber
     *
     * @return Organization
     */
    public function setPhoneNumber(string $phoneNumber): Organization
    {
        $this->phoneNumber = $phoneNumber;

        return $this;
    }

    /**
     * @return string|null
     */
    public function contactEmail(): ?string
    {
        return $this->contactEmail;
    }

    /**
     * @param string $contactEmail
     *
     * @return Organization
     */
    public function setContactEmail(string $contactEmail): Organization
    {
        $this->contactEmail = $contactEmail;

        return $this;
    }

    /**
     * @return string|null
     */
    public function receiptEmail(): ?string
    {
        return $this->receiptEmail;
    }

    /**
     * @param string $receiptEmail
     *
     * @return Organization
     */
    public function setReceiptEmail(string $receiptEmail): Organization
    {
        $this->receiptEmail = $receiptEmail;

        return $this;
    }

    /**
     * @return string|null
     */
    public function legalEntityType(): ?string
    {
        return $this->legalEntityType;
    }

    /**
     * @param string $legalEntityType
     *
     * @return Organization
     */
    public function setLegalEntityType(string $legalEntityType): Organization
    {
        $this->legalEntityType = $legalEntityType;

        return $this;
    }

    /**
     * @return string|null
     */
    public function legalEntityNr(): ?string
    {
        return $this->legalEntityNr;
    }

    /**
     * @param string $legalEntityNr
     *
     * @return Organization
     */
    public function setLegalEntityNr(string $legalEntityNr): Organization
    {
        $this->legalEntityNr = $legalEntityNr;

        return $this;
    }

    /**
     * @return string|null
     */
    public function country(): ?string
    {
        return $this->country;
    }

    /**
     * @param string $country
     *
     * @return Organization
     */
    public function setCountry(string $country): Organization
    {
        $this->country = $country;

        return $this;
    }

    /**
     * @return string|null
     */
    public function language(): ?string
    {
        return $this->language;
    }

    /**
     * @param string $language
     *
     * @return Organization
     */
    public function setLanguage(string $language): Organization
    {
        $this->language = $language;

        return $this;
    }

    /**
     * @return DateTime|null
     */
    public function created(): ?DateTime
    {
        return $this->created;
    }

    /**
     * @param DateTime $created
     *
     * @return Organization
     */
    public function setCreated(DateTime $created): Organization
    {
        $this->created = $created;

        return $this;
    }

    /**
     * @return string|null
     */
    public function ownerUuid(): ?string
    {
        return $this->ownerUuid;
    }

    /**
     * @param string $ownerUuid
     *
     * @return Organization
     */
    public function setOwnerUuid(string $ownerUuid): Organization
    {
        $this->ownerUuid = $ownerUuid;

        return $this;
    }

    /**
     * @return int|null
     */
    public function organizationId(): ?int
    {
        return $this->organizationId;
    }

    /**
     * @param int $organizationId
     *
     * @return Organization
     */
    public function setOrganizationId(int $organizationId): Organization
    {
        $this->organizationId = $organizationId;

        return $this;
    }

    /**
     * @return string|null
     */
    public function customerStatus(): ?string
    {
        return $this->customerStatus;
    }

    /**
     * @param string $customerStatus
     *
     * @return Organization
     */
    public function setCustomerStatus(string $customerStatus): Organization
    {
        $this->customerStatus = $customerStatus;

        return $this;
    }

    /**
     * One of the TaxationMode values.
     * @return string
     */
    public function taxationMode(): string
    {
        return $this->taxationMode;
    }

    /**
     * One of the TaxationType values.
     * @return string
     */
    public function taxationType(): string
    {
        return $this->taxationType;
    }

    /**
     * @return string|null
     */
    public function customerType(): ?string
    {
        return $this->customerType;
    }

    /**
     * @param string $customerType
     *
     * @return Organization
     */
    public function setCustomerType(string $customerType): Organization
    {
        $this->customerType = $customerType;

        return $this;
    }

    /**
     * @return DateTimeZone
     */
    public function timeZone(): ?DateTimeZone
    {
        return $this->timeZone;
    }

    /**
     * @param DateTimeZone $timeZone
     *
     * @return Organization
     */
    public function setTimeZone(DateTimeZone $timeZone): Organization
    {
        $this->timeZone = $timeZone;

        return $this;
    }

    /**
     * @return string|null
     */
    public function addressLine2(): ?string
    {
        return $this->addressLine2;
    }

    /**
     * @param string|null $addressLine2
     *
     * @return Organization
     */
    public function setAddressLine2(?string $addressLine2): Organization
    {
        $this->addressLine2 = $addressLine2;

        return $this;
    }

    /**
     * @return string|null
     */
    public function legalName(): ?string
    {
        return $this->legalName;
    }

    /**
     * @param string|null $legalName
     *
     * @return Organization
     */
    public function setLegalName(?string $legalName): Organization
    {
        $this->legalName = $legalName;

        return $this;
    }

    /**
     * @return string|null
     */
    public function legalZipCode(): ?string
    {
        return $this->legalZipCode;
    }

    /**
     * @param string|null $legalZipCode
     *
     * @return Organization
     */
    public function setLegalZipCode(?string $legalZipCode): Organization
    {
        $this->legalZipCode = $legalZipCode;

        return $this;
    }

    /**
     * @return string|null
     */
    public function legalCity(): ?string
    {
        return $this->legalCity;
    }

    /**
     * @param string|null $legalCity
     *
     * @return Organization
     */
    public function setLegalCity(?string $legalCity): Organization
    {
        $this->legalCity = $legalCity;

        return $this;
    }

    /**
     * @return string|null
     */
    public function legalState(): ?string
    {
        return $this->legalState;
    }

    /**
     * @param string|null $legalState
     *
     * @return Organization
     */
    public function setLegalState(?string $legalState): Organization
    {
        $this->legalState = $legalState;

        return $this;
    }
}
