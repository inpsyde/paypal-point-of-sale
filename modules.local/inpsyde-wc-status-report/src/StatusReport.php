<?php

declare(strict_types=1);

namespace Inpsyde\WcStatusReport;

class StatusReport implements StatusReportInterface
{
    /**
     * @var string
     */
    protected $title;

    /**
     * @var iterable<ReportItem>
     */
    protected $items;

    /**
     * @param iterable<ReportItem> $items
     */
    public function __construct(string $title, iterable $items)
    {
        $this->title = $title;
        $this->items = $items;
    }

    /**
     * @inheritDoc
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @inheritDoc
     */
    public function getItems(): iterable
    {
        return $this->items;
    }
}
