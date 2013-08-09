<?php

namespace Easybook\Utils;

use Silex\Application;
use Silex\ServiceProviderInterface;
use Easybook\Utils\Slugger;
use Easybook\Utils\Markdown;

/**
 * Misc. utils service provider
 */
class UtilServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['markdown'] = $app->share(function() use($app) {
            return new Markdown();
        });

        $app['slugger'] = $app->share(function() use($app) {
            return new Slugger();
        });
    }

    public function boot(Application $app)
    {
    }
}



