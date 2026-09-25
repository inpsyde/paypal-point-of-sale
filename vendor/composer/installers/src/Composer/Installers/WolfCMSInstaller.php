<?php

namespace Composer\Installers;

class WolfCMSInstaller extends \Composer\Installers\BaseInstaller
{
    /** @var array<string, string> */
    protected $locations = array('plugin' => 'wolf/plugins/{$name}/');
}
