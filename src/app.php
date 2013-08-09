<?php

use Silex\Application;
use Silex\Provider\TwigServiceProvider;
use Silex\Provider\UrlGeneratorServiceProvider;
use Silex\Provider\ValidatorServiceProvider;
use Silex\Provider\ServiceControllerServiceProvider;
use Silex\Provider\HttpCacheServiceProvider;
use Easybook\Utils\UtilServiceProvider;

$app = new Application();

$app->register(new UrlGeneratorServiceProvider());

$app->register(new ValidatorServiceProvider());

$app->register(new ServiceControllerServiceProvider());

$app->register(new TwigServiceProvider(), array(
    'twig.path'    => array(__DIR__.'/../templates'),
    'twig.options' => array('cache' => __DIR__.'/../cache/twig'),
));

$app->register(new UtilServiceProvider());


$app->register(new HttpCacheServiceProvider(), array(
   'http_cache.cache_dir' => __DIR__.'/../cache/http_cache',
   'http_cache.esi'       => null,
));

return $app;
