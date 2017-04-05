<?php
/**
 * Created by PhpStorm.
 * User: pedroskakum
 * Date: 03/03/2017
 * Time: 17:31
 */

namespace pedroskakum\ApiClientLayer\Providers;

use pedroskakum\ApiClientLayer\Entities\Remote\ApiServer;
use pedroskakum\ApiClientLayer\Services\ApiConnector;
use Illuminate\Support\ServiceProvider;

class ApiClientLayerProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }


    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('pedroskakum\ApiClientLayer\Services\ApiConnector', function($app){
            if(!$appkey = \Config::get('app.apiClientKey')) throw new ApiConnectorException('Invalid AppKey or apiClientKey config is not defined at config/app.php file!');
            if(!$appsecret = \Config::get('app.apiClientSecret')) throw new ApiConnectorException('Invalid AppSecret or apiClientSecret config is not defined at config/app.php file!');
            if(!$url = \Config::get('app.apiEndpointUrl')) throw new ApiConnectorException('Invalid EndPointUrl or apiEndpointUrl config is not defined at config/app.php file!');
            if($url{strlen($url)-1} == '/') throw new ApiConnectorException('Your EndPoint Url should not end with slash.');
            $apiServer = new ApiServer($appkey, $appsecret, $url);
            return ApiConnector::getInstance($apiServer);
        });
    }
}
