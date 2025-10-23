<?php //phpcs:disable - There is a weird error on PHP7.4 which breaks phpcs when returning $_POST down below
declare(strict_types=1);

namespace Inpsyde\StateMachine\Event;

class WebRequest extends GenericStateChange
{

    /**
     * @var array
     */
    private $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public static function fromGlobals(): self
    {
        //phpcs:disable WordPress.VIP.SuperGlobalInputUsage.AccessDetected
        //phpcs:disable WordPress.Security.NonceVerification.Recommended
        //phpcs:disable WordPress.Security.NonceVerification.Missing
        $data = array_merge($_POST, $_GET);

        return new static($data);
    }

    public function data(): array
    {
        return $this->data;
    }
}
