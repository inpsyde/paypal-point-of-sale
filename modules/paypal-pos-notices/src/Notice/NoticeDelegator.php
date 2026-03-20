<?php

declare (strict_types=1);
namespace Syde\Vendor\Zettle\Syde\PayPal\PointOfSale\Notices\Notice;

class NoticeDelegator
{
    /**
     * @var NoticeInterface[]
     */
    private $notices;
    /**
     * NoticeDelegator constructor.
     *
     * @param NoticeInterface[] $notices
     */
    public function __construct(NoticeInterface ...$notices)
    {
        $this->notices = $notices;
    }
    /**
     * @param string $currentState
     */
    public function delegate(string $currentState): void
    {
        if (empty($this->notices)) {
            return;
        }
        foreach ($this->notices as $notice) {
            if (!$notice->accepts($currentState)) {
                continue;
            }
            $this->addAdminNotice($notice);
        }
    }
    /**
     * Add Notice to WordPress
     *
     * @param NoticeInterface $notice
     */
    private function addAdminNotice(NoticeInterface $notice): void
    {
        add_action('admin_notices', static function () use ($notice) {
            echo wp_kses_post($notice->render());
        });
    }
}
