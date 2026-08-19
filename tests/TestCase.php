<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel as ConsoleKernel;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    public function createApplication()
    {
        $app = require dirname(__DIR__) . '/bootstrap/app.php';

        $kernel = $app->make(ConsoleKernel::class);
        $kernel->bootstrap();
        $app['config']->set('app.env', 'testing');
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite.database', ':memory:');
        $app['db']->purge('sqlite');

        $kernel->call('migrate', [
            '--force' => true,
        ]);

        return $app;
    }
}
