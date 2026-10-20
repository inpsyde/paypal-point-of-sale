<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\PhpSdk\DAL\Builder\Purchase;

use DateTime;
use Exception;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Builder\Coordinates\CoordinatesBuilderInterface;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Builder\User\UserBuilderInterface;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Purchase\Purchase;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Purchase\PurchaseFactory;
use Syde\PayPal\PointOfSale\PhpSdk\DAL\Entity\Purchase\Type\SourceType;

class PurchaseBuilder implements PurchaseBuilderInterface
{
    private PurchaseFactory $purchaseFactory;

    private UserBuilderInterface $userBuilder;

    private CoordinatesBuilderInterface $coordinatesBuilder;

    /**
     * PurchaseBuilder constructor.
     *
     * @param PurchaseFactory $purchaseFactory
     * @param UserBuilderInterface $userBuilder
     * @param CoordinatesBuilderInterface $coordinatesBuilder
     */
    public function __construct(
        PurchaseFactory $purchaseFactory,
        UserBuilderInterface $userBuilder,
        CoordinatesBuilderInterface $coordinatesBuilder
    ) {

        $this->purchaseFactory = $purchaseFactory;
        $this->userBuilder = $userBuilder;
        $this->coordinatesBuilder = $coordinatesBuilder;
    }

    /**
     * @inheritDoc
     */
    public function buildFromArray(array $data): Purchase
    {
        return $this->build($data);
    }

    /**
     * @inheritDoc
     */
    public function createDataArray(Purchase $purchase): array
    {
        $data = [
            'uuid' => $purchase->uuid(),
            'uuid1' => $purchase->uuid1(),
            'timestamp' => $purchase->timestamp(),
            'country' => $purchase->country(),
            'currency' => $purchase->currency(),
            'organizationId' => $purchase->organizationId(),
            'purchaseNumber' => $purchase->purchaseNumber(),
            'amount' => $purchase->amount(),
            'vatAmount' => $purchase->vatAmount(),
            'products' => $purchase->products(),
            'payments' => $purchase->payments(),
            'vatAmounts' => $purchase->vatAmounts(),
            'receiptCopyAllowed' => $purchase->isReceiptCopyAllowed(),
            'refund' => $purchase->isRefund(),
            'refunded' => $purchase->isRefunded(),
        ];

        $data[] = $this->userBuilder->createDataArray($purchase->user());

        if ($purchase->coordinates()) {
            $data[] = $this->coordinatesBuilder->createDataArray($purchase->coordinates());
        }

        if ($purchase->sourceType()) {
            $data['sourceType'] = $purchase->sourceType()->getValue();
        }

        if ($purchase->isPublished() !== null) {
            $data['published'] = $purchase->isPublished();
        }

        return $data;
    }

    /**
     * @param array $data
     *
     * @return Purchase
     *
     * @throws Exception
     */
    private function build(array $data): Purchase
    {
        // CoordinatesBuilder reads lat/long at the top level; pass the nested node.
        $coordinates = !empty($data['gpsCoordinates'])
            ? $this->coordinatesBuilder->buildFromArray($data['gpsCoordinates'])
            : null;

        $sourceRaw = $data['source'] ?? $data['sourceType'] ?? $data['type'] ?? null;
        // Unknown sources must not fail the whole purchase.
        $sourceType = in_array($sourceRaw, SourceType::getValidOptions(), true) ? SourceType::get($sourceRaw) : null;

        $published = isset($data['published']) ? (bool) $data['published'] : null;

        // v2 exposes `groupedVatAmounts`; the entity mirrors the `vatAmounts` array.
        $vatAmounts = $data['vatAmounts'] ?? $data['groupedVatAmounts'] ?? [];

        return $this->purchaseFactory->create(
            $data['purchaseUUID'] ?? $data['uuid'] ?? '',
            new DateTime($data['timestamp'] ?? 'now'),
            $data['country'] ?? '',
            $data['currency'] ?? '',
            $this->userBuilder->buildFromArray($data),
            (int) ($data['organizationId'] ?? 0),
            (int) ($data['purchaseNumber'] ?? 0),
            (float) ($data['amount'] ?? 0),
            (float) ($data['vatAmount'] ?? 0),
            $data['products'] ?? [],
            $data['payments'] ?? [],
            $vatAmounts,
            (bool) ($data['receiptCopyAllowed'] ?? false),
            (bool) ($data['refund'] ?? false),
            (bool) ($data['refunded'] ?? false),
            $coordinates,
            $sourceType,
            $published
        );
    }
}
