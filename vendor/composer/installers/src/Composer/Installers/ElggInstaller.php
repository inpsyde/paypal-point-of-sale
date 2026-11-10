<?php

namespace Composer\Installers;

class ElggInstaller extends \Composer\Installers\BaseInstaller
{
    /** @var array<string, string> */
    protected $locations = array('plugin' => 'mod/{$name}/');
}
