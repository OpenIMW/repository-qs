<?php

namespace IMW\RepositoryQS\Tests;

use IMW\RepositoryQS\RepositoryServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('meta', include(__DIR__.'/../config/repository.php'));
    }

    /**
     * @param \Illuminate\Foundation\Application $app
     *
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [
            RepositoryServiceProvider::class,
        ];
    }
}
