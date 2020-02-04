<?php

namespace IMW\RepositoryQS;

use Illuminate\Support\ServiceProvider;
use IMW\RepositoryQS\Contracts\Repository;
use IMW\RepositoryQS\Commands\RepositoryMakeCommand;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register repositories to be injected in specifique controllers.
     *
     * @var array
     */
    public $repositories = [
        // \App\Repositories\BookRepository::class => [
        //     \App\Http\Controllers\BookController:class,
        //     \App\Http\Controllers\AnotherBookController:class,
        // ]
    ];

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                RepositoryMakeCommand::class
            ]);
        }
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        foreach ($this->repositories as $repository => $controllers) {
            $this->app->when($controllers)
                ->needs(Repository::class)
                ->give($repository);
        }
    }
}
