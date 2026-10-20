<?php

declare(strict_types=1);

namespace Syde\PayPal\PointOfSale\Webhooks\Order;

/**
 * Amounts of one Zettle purchase line in absolute minor units: subtotals before discounts,
 * net and tax after them.
 */
final class LineAmounts
{
    private float $quantity;

    private int $netMinor;

    private int $taxMinor;

    private int $subtotalNetMinor;

    private int $subtotalTaxMinor;

    private float $vatPercentage;

    private function __construct(
        float $quantity,
        int $netMinor,
        int $taxMinor,
        int $subtotalNetMinor,
        int $subtotalTaxMinor,
        float $vatPercentage
    ) {

        $this->quantity = $quantity;
        $this->netMinor = $netMinor;
        $this->taxMinor = $taxMinor;
        $this->subtotalNetMinor = $subtotalNetMinor;
        $this->subtotalTaxMinor = $subtotalTaxMinor;
        $this->vatPercentage = $vatPercentage;
    }

    /**
     * @param array<string, mixed> $line
     */
    public static function fromLine(array $line): self
    {
        // Refund lines carry negative quantities and amounts.
        // Zettle sends quantities as strings and allows fractions for unit items.
        $quantity = abs((float) ($line['quantity'] ?? 1));

        // `grossValue`/`grossTax` are before purchase-level discounts: the WC subtotal.
        $listGross = isset($line['grossValue'])
            ? abs((int) $line['grossValue'])
            : (int) round(abs((int) ($line['unitPrice'] ?? 0)) * $quantity);
        $subtotalTax = isset($line['grossTax']) ? abs((int) $line['grossTax']) : 0;
        $subtotalNet = max(0, $listGross - $subtotalTax);

        // `rowTaxableAmount` is the net after all discounts; tax scales down with it.
        $net = isset($line['rowTaxableAmount'])
            ? abs((int) $line['rowTaxableAmount'])
            : max(0, $subtotalNet - abs((int) ($line['discountValue'] ?? 0)));
        $tax = $subtotalNet > 0 ? (int) round($subtotalTax * $net / $subtotalNet) : 0;

        return new self($quantity, $net, $tax, $subtotalNet, $subtotalTax, (float) ($line['vatPercentage'] ?? 0));
    }

    public function quantity(): float
    {
        return $this->quantity;
    }

    public function netMinor(): int
    {
        return $this->netMinor;
    }

    public function taxMinor(): int
    {
        return $this->taxMinor;
    }

    public function subtotalNetMinor(): int
    {
        return $this->subtotalNetMinor;
    }

    public function subtotalTaxMinor(): int
    {
        return $this->subtotalTaxMinor;
    }

    public function vatPercentage(): float
    {
        return $this->vatPercentage;
    }
}
