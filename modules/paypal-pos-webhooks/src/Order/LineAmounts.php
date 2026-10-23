<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Webhooks\Order;

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
    private function __construct(float $quantity, int $netMinor, int $taxMinor, int $subtotalNetMinor, int $subtotalTaxMinor, float $vatPercentage)
    {
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
        $percentage = (float) ($line['vatPercentage'] ?? 0);
        // `grossValue`/`grossTax` are before purchase-level discounts: the WC subtotal.
        $listGross = isset($line['grossValue']) ? abs((int) $line['grossValue']) : (int) round(abs((int) ($line['unitPrice'] ?? 0)) * $quantity);
        $subtotalTax = isset($line['grossTax']) ? abs((int) $line['grossTax']) : self::includedVat($listGross, $percentage);
        $subtotalNet = max(0, $listGross - $subtotalTax);
        // `rowTaxableAmount` is the net after all discounts; tax scales down with it.
        $discountedGross = max(0, $listGross - abs((int) ($line['discountValue'] ?? 0)));
        $net = isset($line['rowTaxableAmount']) ? abs((int) $line['rowTaxableAmount']) : $discountedGross - self::includedVat($discountedGross, $percentage);
        $tax = $subtotalNet > 0 ? (int) round($subtotalTax * $net / $subtotalNet) : 0;
        return new self($quantity, $net, $tax, $subtotalNet, $subtotalTax, $percentage);
    }
    /**
     * The VAT part of a VAT-inclusive amount, for lines that don't state it.
     */
    private static function includedVat(int $gross, float $percentage): int
    {
        return $percentage > 0 ? (int) round($gross * $percentage / (100 + $percentage)) : 0;
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
