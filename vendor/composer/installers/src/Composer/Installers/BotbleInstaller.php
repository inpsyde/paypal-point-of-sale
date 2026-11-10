<?php

namespace Composer\Installers;

class BotbleInstaller extends \Composer\Installers\BaseInstaller
{
    /** @var array<string, string> */
    protected $locations = array('plugin' => 'platform/plugins/{$name}/', 'theme' => 'platform/themes/{$name}/');
}
