<?php

use Illuminate\Container\Container;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Translation;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Factory as ValidationFactory;
use Illuminate\Database\Capsule\Manager as Capsule;


$app = new Container();
Facade::setFacadeApplication($app);
$app->singleton('validator', function () use ($app) {
    $filesystem = new Filesystem();
    $fileLoader = new Translation\FileLoader($filesystem, __DIR__ . '/Lang');
    $translator = new Translation\Translator($fileLoader, 'en');
    $validator = new ValidationFactory($translator, $app);
    return $validator;
});
$app->singleton('hash', function () use ($app) {
    return new Illuminate\Hashing\HashManager($app);
});
$app->singleton('db', function() use ($app) {
    $capsule = new Capsule($app);
    $capsule->setAsGlobal();
    $capsule->bootEloquent();
    return $capsule;
});
class_alias(Illuminate\Support\Facades\Validator::class, 'Validator');
class_alias(Illuminate\Support\Facades\Hash::class, 'Hash');
$GLOBALS["app"] = $app;