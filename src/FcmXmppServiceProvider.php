<?php
/**
 * Created by PhpStorm.
 * User: andre
 * Date: 2017-07-29
 * Time: 2:09 PM
 */

namespace FannyPack\FcmXmpp;


use FannyPack\FcmXmpp\Commands\StreamFcmXmpp;
use FannyPack\FcmXmpp\Xmpp\XmppServer;
use Illuminate\Support\ServiceProvider;

class FcmXmppServiceProvider extends ServiceProvider
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
            
            $this->commands([
                StreamFcmXmpp::class
            ]);
        }
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(XmppServer::class, function($app){
            return new XmppServer($app);
        });
    }

    public function provides()
    {
        return [XmppServer::class];
    }
}