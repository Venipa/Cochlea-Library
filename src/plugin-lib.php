<?php

namespace Cochlea\Plugin;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Events\Dispatcher;
use Illuminate\Container\Container;

class PluginBase
{
    protected $config;
    protected $database;
    /** @var pluginSystem $plugins */
    protected $plugins;
    /** @var DB_Base $db */
    protected $db;
    /** @var MyBB $myBB */
    protected $myBB;
    public function __construct($config)
    {
        $this->config = $config;
        $this->myBB = $this->getCore();
        $this->db = $this->getDatabase();
        $this->plugins = $this->getPlugins();
        $this->initialize($config["database"]);
    }
    private function initialize($config) {
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
    /** @return pluginSystem */
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
}
