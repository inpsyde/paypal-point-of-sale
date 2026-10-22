<?php

namespace Composer\Installers;

class StarbugInstaller extends \Composer\Installers\BaseInstaller
{
    /** @var array<string, string> */
    protected $locations = array('module' => 'modules/{$name}/', 'theme' => 'themes/{$name}/', 'custom-module' => 'app/modules/{$name}/', 'custom-theme' => 'app/themes/{$name}/');
}
