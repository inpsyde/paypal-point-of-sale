<?php
// phpcs:disable

class WC_Admin_Settings
{
    /**
     * Error messages.
     *
     * @var array
     */
    private static $errors = array();

    /**
     * Update messages.
     *
     * @var array
     */
    private static $messages = array();

    /**
     * Add a message.
     *
     * @param string $text Message.
     */
    public static function add_message($text)
    {
        self::$messages[] = $text;
    }

    /**
     * Add an error.
     *
     * @param string $text Message.
     */
    public static function add_error($text)
    {
        self::$errors[] = $text;
    }
}
