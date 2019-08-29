<?php

namespace Cochlea\Controllers;

use Cochlea\PluginBase;

class BaseController {
    /**
     * @var PluginBase
     */
    protected $plugin;
    public function __construct(PluginBase $pluginBase) {
        $this->plugin = $pluginBase;
    }
    function __callStatic($name, $arguments)
    {
        $ref = $arguments[0];
        array_slice($arguments, 0, 1);
        $controller = new self($ref);
        return $controller[$name]($arguments);
    }
}