<?php

namespace Composer\Installers;

class EliasisInstaller extends \Composer\Installers\BaseInstaller
{
    /** @var array<string, string> */
    protected $locations = array('component' => 'components/{$name}/', 'module' => 'modules/{$name}/', 'plugin' => 'plugins/{$name}/', 'template' => 'templates/{$name}/');
}
