<?php

use Cochlea\Models\MyBBUsers;
use Cochlea\Services\Log\LogManager;
use Illuminate\Container\Container;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Translation;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Factory as ValidationFactory;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Log\Logger;
use Illuminate\Support\Facades\App;
use Illuminate\Validation\DatabasePresenceVerifier;

$_storagePath = __DIR__ . "/../storage";

$app = new Container();

Facade::setFacadeApplication($app);
$app->singleton('hash', function () use ($app) {
    return new Illuminate\Hashing\HashManager($app);
});

$app->singleton('db', function() use ($app) {
    $capsule = new Capsule($app);
    $capsule->setAsGlobal();
    $capsule->bootEloquent();
    return $capsule;
});
$app->singleton('path.public', function() use($app, $_storagePath) {
    return $_storagePath;
});
$app->singleton('filesystem', function() use($app, $_storagePath) {
    
    $filesystemFactory = new FilesystemManager($app);
    
    return $filesystemFactory->createLocalDriver([
        'driver' => 'local',
        'root' => $_storagePath
    ]);
});
$app->singleton('log', function() use ($app, $_storagePath) {
    $logManager = new LogManager($app, [
        "storagePath" => $_storagePath,
        "logging.default" => "single"
    ]);
    return $logManager;
});
$app->singleton('validator', function () use ($app) {
    $filesystem = new Filesystem();
    $fileLoader = new Translation\FileLoader($filesystem, __DIR__ . '/Lang');
    $translator = new Translation\Translator($fileLoader, 'en');
    $validator = new ValidationFactory($translator, $app);
    $validator->setPresenceVerifier(new DatabasePresenceVerifier(MyBBUsers::getConnectionResolver()));
    return $validator;
});
class_alias(Illuminate\Support\Facades\Validator::class, 'Validator');
class_alias(Illuminate\Support\Facades\Hash::class, 'Hash');
$GLOBALS["app"] = $app;