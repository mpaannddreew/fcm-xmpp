<?php
/**
 * Created by PhpStorm.
 * User: andre
 * Date: 2017-07-29
 * Time: 2:09 PM
 */

namespace FannyPack\Fcm\Xmpp;


use DOMDocument;
use FannyPack\Fcm\Xmpp\Commands\StreamFcm;
use Illuminate\Support\ServiceProvider;

class XmppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/config/fcmxmpp.php' => config_path('fcmxmpp.php'),
            ], 'fcm-xmpp-config');
        }
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(XmppConfig::class, function($app){
            return new XmppConfig($app);
        });

        $this->app->when(XmppParser::class)
            ->needs(DOMDocument::class)
            ->give(function (){
                $xml = new DOMDocument();
                $xml->recover = true;
                return $xml;
            });

        $this->app->singleton(XmppConnectionStorage::class, function($app){
            return new XmppConnectionStorage();
        });

        $this->app->singleton(XmppConnectionPool::class, function($app){
            return new XmppConnectionPool($app);
        });

        $this->app->singleton(XmppClient::class, function($app){
            return new XmppClient($app);
        });
    }

    public function provides()
    {
        return [XmppClient::class];
    }
}