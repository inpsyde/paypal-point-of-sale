<?php

declare(strict_types=1);

namespace Inpsyde\WcStatusReport;

class ReportItem implements ReportItemInterface
{
    /**
     * @var string
     */
    protected $label;

    /**
     * @var string
     */
    protected $exportedLabel;

    /**
     * @var mixed
     */
    protected $value;

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
    public function getValue()
    {
        return $this->value;
    }
}
