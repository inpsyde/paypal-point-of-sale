<?php

namespace Composer\Installers;

class ItopInstaller extends \Composer\Installers\BaseInstaller
{
    /** @var array<string, string> */
    protected $locations = array('extension' => 'extensions/{$name}/');
}
