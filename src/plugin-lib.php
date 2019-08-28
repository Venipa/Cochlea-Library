<?php

namespace Cochlea;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Events\Dispatcher;
use Illuminate\Container\Container;

class PluginBase
{
    protected $config;
    /** @var Capsule $database */
    protected $database;
    protected $plugins;
    /** @var DB_Base $db */
    protected $db;
    /** @var MyBB $myBB */
    protected $myBB;
    protected $pluginInfo;
    public $pluginHooks = [];
    public function __construct($pluginInfo)
    {
        $this->pluginInfo = $pluginInfo;
        $this->myBB = $this->getCore();
        $this->config = $this->myBB->config;
        $this->db = $this->getDatabase();
        $this->plugins = $this->getPlugins();
        $this->initialize();
    }
    protected function initialize() {
        $config = $this->config["database"];
        $this->database = new Capsule;
        $this->database->addConnection([
            'driver'    => 'mysql',
            'host'      => $config['hostname'],
            'database'  => $config['database'],
            'username'  => $config['username'],
            'password'  => $config['password'],
            'charset'   => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix'    => $config['table_prefix'],
            'engine'    => 'InnoDB'
        ]);
        $this->database->setEventDispatcher(new Dispatcher(new Container));
        $this->database->setAsGlobal();
        $this->database->bootEloquent();
    }
    /** @return Capsule */
    protected function capsule() {
        return $this->database;
    }
    /** @return Capsule */
    protected function getCapsuleDatabase() {
        return $this->database;
    }
    public function getPlugins()
    {
        global $plugins;
        return $plugins;
    }
    /** @return DB_Base */
    public function getDatabase()
    {
        global $db;
        return $db;
    }
    /** @return \MyBB */
    public function getCore()
    {
        global $mybb;
        return $mybb;
    }
    public function addHook($name, $func) {
        $this->pluginHooks[$name] = $func;
    }
    public function removeHook($name) {
        unset($this->pluginHooks[$name]);
    }
    public function onHook(...$args) {
        $hookName = $this->plugins->current_hook;
        if(isset($this->pluginHooks[$hookName]) && is_callable($this->pluginHooks[$hookName])) {
            return $this->pluginHooks[$hookName]($args);
        }
        return false;
    }
    public function getHookAssignments()
    {
        $hookNames = array_keys($this->pluginHooks);
        $fnSetter = [];
        foreach($hookNames as $hkName) {
            $fnSetter[$hkName] = "onHook";
        }
        return $fnSetter;
    }
    public function registerHooks()
    {
        $codeName = $this->pluginInfo["codename"];
        $hookAssignments = $this->getHookAssignments();
        foreach ($hookAssignments as $hookKey => $methodName) {
            $this->plugins->add_hook($hookKey, $codeName . "_" . $methodName);
        }
    }
}
