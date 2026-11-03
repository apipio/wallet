<?php

namespace Apip\Wallet;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;

/**
 * apip.io 钱包客户端服务提供者.
 *
 * 注册 apip 配置与 Apip 单例, 并支持发布配置文件.
 */
class ApipServiceProvider extends ServiceProvider
{
    /**
     * 合并默认配置并注册单例.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/apip.php', 'apip');

        $this->app->singleton(Apip::class, function (Application $app): Apip {
            $uri = $app['config']->get('apip.uri');

            return new Apip(is_string($uri) ? $uri : '');
        });
    }

    /**
     * 发布配置文件.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/apip.php' => $this->app->configPath('apip.php'),
            ], 'apip-config');
        }
    }
}
