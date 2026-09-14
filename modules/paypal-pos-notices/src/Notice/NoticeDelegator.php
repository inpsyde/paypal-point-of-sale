<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Notices\Notice;

class NoticeDelegator
{
    /**
     * @var NoticeInterface[]
     */
    private array $notices;
    public function __construct(NoticeInterface ...$notices)
    {
        $this->notices = $notices;
    }
    public function delegate(string $currentState): void
    {
        foreach ($this->notices as $notice) {
            if (!$notice->accepts($currentState)) {
                continue;
            }
            $this->renderAdminNotice($notice);
        }
    }
    private function renderAdminNotice(NoticeInterface $notice): void
    {
        echo wp_kses_post($notice->render());
    }
}
