<?php

namespace Cochlea;

use Cochlea\Models\MyBBTemplates;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Events\Dispatcher;
use Illuminate\Container\Container;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use JsonSerializable;

class PluginBase
{
    protected $config;
    private $app;
    protected $plugins;
    /** @var DB_Base $db */
    protected $db;
    /** @var MyBB $myBB */
    protected $myBB;
    protected $pluginConfig;
    protected $pluginInfo;
    public $pluginHooks = [];
    public $xhrHooks = [];
    public function __construct($pluginInfo = null)
    {
        if ($pluginInfo) {
            $this->pluginInfo = $pluginInfo;
        }
        if (isset($pluginInfo["config"])) {
            $this->pluginConfig = $pluginInfo["config"];
        }
        $this->myBB = $this->getCore();
        $this->config = $this->myBB->config;
        $this->db = $this->getDatabase();
        $this->plugins = $this->getPlugins();
        $this->initialize();
    }
    protected function initialize()
    {
        $config = $this->config["database"];
        global $app;
        $capsule = $app->get('db');
        $capsule->addConnection([
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
        Paginator::currentPathResolver(function () {
            $queryString = $_SERVER['REQUEST_URI'];
            return $this->removeParam($queryString, "page");
        });
    }
    private function removeParam($url, $param)
    {
        $url = preg_replace('/(&|\?)' . preg_quote($param) . '=[^&]*$/', '', $url);
        $url = preg_replace('/(&|\?)' . preg_quote($param) . '=[^&]*&/', '$1', $url);
        return $url;
    }
    public function debug($msg) {
        $codeName = $this->pluginInfo->codename;
        Log::log(__FUNCTION__, "[$codeName] " . $msg);
    }
    public function error($msg) {
        $codeName = $this->pluginInfo->codename;
        Log::log(__FUNCTION__, "[$codeName] " . $msg);
    }
    public function info($msg) {
        $codeName = $this->pluginInfo->codename;
        Log::log(__FUNCTION__, "[$codeName] " . $msg);
    }
    public function warning($msg) {
        $codeName = $this->pluginInfo->codename;
        Log::log(__FUNCTION__, "[$codeName] " . $msg);
    }
    /** @return Capsule */
    protected function capsule()
    {
        global $app;
        return $app->get('db');
    }
    /** @return Capsule */
    protected function getCapsuleDatabase()
    {
        global $app;
        return $app->get('db');
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
    public function addHook(string $name, callable $func)
    {
        $this->pluginHooks[$name][] = $func;
    }
    /**
     * TODO: Fix Instance "this"
     */
    public function setClassXHRHook(string $action, string $class, string $method)
    {
        $this->xhrHooks["{$this->pluginInfo->codename}_" . $action] = function (...$args) use ($class, $method) {
            return forward_static_call([$class, $method], $args);
        };
    }
    public function setAuthXHRHook(string $action, callable $func) {
        $this->xhrHooks["{$this->pluginInfo->codename}_" . $action] = function(...$args) use ($func) {
            if (!$this->myBB->user['uid']) {
                return ["error" => "Not Authenticated"];
            }
            return $func($args);
        };
    }
    public function setXHRHook(string $action, callable $func)
    {
        $this->xhrHooks["{$this->pluginInfo->codename}_" . $action] = $func;
    }
    public function removeXHRHook(string $action)
    {
        unset($this->xhrHooks["{$this->pluginInfo->codename}_" . $action]);
    }
    public function removeHook($name)
    {
        unset($this->pluginHooks[$name]);
    }
    public function returnJson($data)
    {
        global $charset;
        header("Content-type: application/json; charset={$charset}");

        if ($data instanceof Arrayable) {
            echo json_encode($data->toArray());
        } elseif ($data instanceof Jsonable) {
            echo $data->toJson();
        } elseif ($data instanceof JsonSerializable) {
            echo json_encode($data->jsonSerialize());
        } else {
            echo json_encode($data);
        }
        exit;
    }
    public function onHook(...$args)
    {
        $hookName = $this->plugins->current_hook;
        if ($hookName == 'xmlhttp') {
            $instance = $this;
            if (in_array("{$this->pluginInfo->codename}_" . $this->myBB->get_input('action'), array_keys($this->xhrHooks))) {
                $action = $this->myBB->get_input('action');
                $dataReturn = $this->xhrHooks["{$this->pluginInfo->codename}_" . $action]($args);
                $this->debug("[{$this->pluginInfo->codename}] XHR Hook [$action] executed with args \"" . json_encode($args ?: []) . "\"");
                if ($dataReturn != null) {
                    $this->debug("[{$this->pluginInfo->codename}] XHR Hook [$action] returned \"" . json_encode($dataReturn ?: []) . "\"");
                    $this->returnJson($dataReturn);
                    return true;
                }
            }
        }
        if (isset($this->pluginHooks[$hookName]) && is_array($this->pluginHooks[$hookName])) {
            foreach($this->pluginHooks[$hookName] as $func) {
                if (is_callable($func)) {
                    $func($args);
                    $this->debug("Hook [$hookName] executed");
                }
            }
            return true;
        }
        return false;
    }
    public function getHookAssignments()
    {
        $hookNames = array_keys($this->pluginHooks);
        $fnSetter = [];
        if (!in_array('xmlhttp', $hookNames)) {
            $hookNames[] = 'xmlhttp';
        }
        foreach ($hookNames as $hkName) {
            $fnSetter[$hkName] = "onHook";
        }
        return $fnSetter;
    }
    public function registerHooks()
    {
        $codeName = $this->pluginInfo["codename"];
        $hookAssignments = $this->getHookAssignments();
        foreach ($hookAssignments as $hookKey => $methodName) {
            $this->debug("[$codeName] Registering Hook [$hookKey] => $methodName(...args)");
            $this->plugins->add_hook($hookKey, $codeName . "_" . $methodName);
        }
    }
    /**
     * @return Collection
     */
    public function seedDefaultData(array $data, callable $func)
    {
        if (!is_callable($func)) {
            return null;
        }
        $models = [];
        foreach ($data as $seed) {
            $models[] = $func($seed);
        }
        return collect($models);
    }
    public function readJSONBody() {
        return @json_decode(($stream = fopen('php://input', 'r')) !== false ? stream_get_contents($stream) : "{}");
    }
    /**
     * Gets $_POST/Post Value
     * @param string|array $keys
     * @param callable|mixed $default
     * @return mixed
     */
    public function getInput($keys, $default = null)
    {
        if (is_array($keys)) {
            $items = [];
            foreach ($keys as $key) {
                $items[$key] = isset($_POST[$key]) ? $_POST[$key] : (is_callable($default) ? $default() : $default);
            }
            return $items;
        } else {
            $key = $keys;
            return isset($_POST[$key]) ? $_POST[$key] : (is_callable($default) ? $default() : $default);
        }
    }
    /**
     * Gets $_GET/Query Parameter Value
     * @param string|array $keys
     * @param callable|mixed $default
     * @return mixed
     */
    public function getParam($keys, $default = null)
    {
        if (is_array($keys)) {
            $items = [];
            foreach ($keys as $key) {
                $items[$key] = isset($_GET[$key]) ? $_GET[$key] : (is_callable($default) ? $default() : $default);
            }
            return $items;
        } else {
            $key = $keys;
            return isset($_GET[$key]) ? $_GET[$key] : (is_callable($default) ? $default() : $default);
        }
    }
    public function getTemplate($name, $options = null, ...$args) {
        global $mybb, $db, $header, $footer, $headerinclude, $modcp_nav, $cache, $lang, $templates;
        if ($options != null) {
            if (isset($options["globals"]) && is_array($options["globals"])) {
                foreach($options["globals"] as $__globalkey__ => $__global__) {
                    global $$__globalkey__;
                    $$__globalkey__ = $__global__;
    
                }
            }
        }
        $templateModel = $templates->get($name);
        if ($templateModel == null) {
            return null;
        }
        
        return eval("return \"". $templateModel ."\";"); 
    }
    public function updateTemplate($name, $html, ...$args) {
        $templateModel = MyBBTemplates::where('title', $name)->first();
        if ($templateModel == null) {
            return false;
        }
        $templateModel->template = $html;
        $templateModel->save();
        return $templateModel->parseTemplate($args);
    }
}
