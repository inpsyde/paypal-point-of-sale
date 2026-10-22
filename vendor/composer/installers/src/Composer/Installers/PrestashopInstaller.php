<?php

namespace Composer\Installers;

class PrestashopInstaller extends \Composer\Installers\BaseInstaller
{
    /** @var array<string, string> */
    protected $locations = array('module' => 'modules/{$name}/', 'theme' => 'themes/{$name}/');
}
