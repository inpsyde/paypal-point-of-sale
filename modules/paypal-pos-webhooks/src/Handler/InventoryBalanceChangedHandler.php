<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Webhooks\Handler;

use Syde\Vendor\Zettle\Inpsyde\Queue\Exception\QueueRuntimeException;
use Syde\Vendor\Zettle\Inpsyde\Queue\Queue\Job\Context;
use Syde\Vendor\Zettle\Inpsyde\Queue\Queue\Job\EphemeralJobRepository;
use Syde\Vendor\Zettle\Psr\Log\LoggerInterface;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\API\Webhooks\Entity\Payload;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Exception\IdNotFoundException;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\PhpSdk\Map\OneToManyMapInterface;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Webhooks\EventName;
use Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Webhooks\Job\InventoryBalanceChangedJob;
class InventoryBalanceChangedHandler implements WebhookHandler
{
    private InventoryBalanceChangedJob $inventoryBalanceChanged;
    private OneToManyMapInterface $variantIdMap;
    private string $integrationUuid;
    private LoggerInterface $logger;
    public function __construct(InventoryBalanceChangedJob $inventoryBalanceChanged, LoggerInterface $logger, OneToManyMapInterface $variantIdMap, string $integrationUuid)
    {
        $this->inventoryBalanceChanged = $inventoryBalanceChanged;
        $this->logger = $logger;
        $this->variantIdMap = $variantIdMap;
        $this->integrationUuid = $integrationUuid;
    }
    /**
     * @inheritDoc
     */
    public function accepts(Payload $payload): bool
    {
        if ($payload->eventName() !== EventName::INVENTORY_BALANCE_CHANGED) {
            return \false;
        }
        $data = $payload->payload();
        /**
         * Read out the 'externalUuid' property that the inventory client passed with the stock change.
         * If it matches, we know that we ourselves caused this stock change. So to avoid a loop,
         * we can bail here
         */
        if (isset($data['externalUuid']) && $data['externalUuid'] === $this->integrationUuid) {
            $this->logger->info(sprintf('Ignoring %s event since it was triggered from our plugin', EventName::INVENTORY_BALANCE_CHANGED));
            return \false;
        }
        return \true;
    }
    /**
     * @inheritDoc
     *
     * @throws QueueRuntimeException
     * phpcs:disable Syde.Functions.FunctionLength.TooLong
     */
    public function handle(Payload $payload): void
    {
        $eventPayload = $payload->payload();
        $variantsBefore = [];
        foreach ($eventPayload['balanceBefore'] as $item) {
            $variantsBefore[$item['variantUuid']] = $item;
        }
        $variantsAfter = [];
        foreach ($eventPayload['balanceAfter'] as $item) {
            $variantsAfter[$item['variantUuid']] = $item;
        }
        if (count($variantsBefore) !== count($variantsAfter)) {
            $this->logger->warning(sprintf('balanceBefore has %d items while balanceAfter has %d items.', count($variantsBefore), count($variantsAfter)));
        }
        foreach ($variantsBefore as $variantUuid => $itemBefore) {
            if (!array_key_exists($variantUuid, $variantsAfter)) {
                $this->logger->warning(sprintf('variantUuid %s not found in balanceAfter.', $variantUuid));
                continue;
            }
            $itemAfter = $variantsAfter[$variantUuid];
            try {
                $localId = $this->variantIdMap->localId($variantUuid);
            } catch (IdNotFoundException $exception) {
                $this->logger->warning(sprintf('%s: could not find local variant ID for PayPal Point of Sale UUID %s', __CLASS__, $variantUuid));
                continue;
            }
            $balanceBefore = $itemBefore['balance'];
            $balanceAfter = $itemAfter['balance'];
            $remoteStockDiff = $balanceAfter - $balanceBefore;
            $this->logger->info(sprintf('Attempting to update stock of product %d with an amount of %d', $localId, $remoteStockDiff));
            $this->inventoryBalanceChanged->execute(Context::fromArray([
                // This salt prevents duplicate invocations of the same webhook message
                'messageUuid' => (string) $payload->messageId(),
                'localId' => $localId,
                'change' => $remoteStockDiff,
            ]), new EphemeralJobRepository(), $this->logger);
        }
    }
}
