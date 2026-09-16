<?php

namespace Composer\Installers;

class CodeIgniterInstaller extends \Composer\Installers\BaseInstaller
{
    /** @var array<string, string> */
    protected $locations = array('library' => 'application/libraries/{$name}/', 'third-party' => 'application/third_party/{$name}/', 'module' => 'application/modules/{$name}/');
}
