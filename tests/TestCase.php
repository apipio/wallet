<?php

namespace Apip\Wallet\Tests;

use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Facade;
use Illuminate\Translation\FileLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Factory as ValidationFactory;
use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected Container $container;

    protected function setUp(): void
    {
        parent::setUp();

        $this->container = new Container();
        Container::setInstance($this->container);
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication($this->container);

        $this->container->instance('config', new Repository([
            'apip' => [
                'uri' => 'https://apip.io/api/',
                'app_id' => 'wallet-abc',
                'app_key' => 'secret-key',
            ],
        ]));

        $translator = new Translator(new FileLoader(new Filesystem(), __DIR__.'/lang'), 'en');
        $this->container->instance('translator', $translator);
        $this->container->instance('validator', new ValidationFactory($translator));
        $this->container->instance(Factory::class, new Factory());
    }

    protected function tearDown(): void
    {
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication(null);

        parent::tearDown();
    }
}
