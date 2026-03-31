<?php

declare(strict_types=1);

namespace Inpsyde\WcStatusReport;

class ReportItem implements ReportItemInterface
{
    protected string $label;

    protected string $exportedLabel;

    protected mixed $value;

    public function __construct(string $label, string $exportedLabel, $value)
    {
        $this->label = $label;
        $this->exportedLabel = $exportedLabel;
        $this->value = $value;
    }

    /**
     * @inheritDoc
     */
    public function getLabel(): string
    {
        return $this->label;
    }

    /**
     * @inheritDoc
     */
    public function getExportedLabel(): string
    {
        return $this->exportedLabel;
    }

    /**
     * @inheritDoc
     */
    public function getValue(): mixed
    {
        return $this->value;
    }
}
