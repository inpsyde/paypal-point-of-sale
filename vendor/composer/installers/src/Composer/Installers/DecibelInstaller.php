<?php

namespace Composer\Installers;

class DecibelInstaller extends \Composer\Installers\BaseInstaller
{
    /** @var array */
    /** @var array<string, string> */
    protected $locations = array('app' => 'app/{$name}/');
}
