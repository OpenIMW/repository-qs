<?php

namespace IMW\RepositoryQS\Tests;

use Symfony\Component\Console\Exception\RuntimeException;

class CommandsTest extends TestCase
{
    /** @test */
    public function make_repository_command_success(): void
    {
        $this->artisan('make:repository BookRepository')
            ->assertExitCode(0);
    }

    /** @test */
    public function make_repository_command_with_duplicated_name_should_fail(): void
    {
        $this->artisan('make:repository BookRepository')
            ->assertExitCode(0);

        $this->artisan('make:repository BookRepository')
            ->expectsOutput("Repository already exists!");
    }

    /** @test */
    public function make_meta_command_without_name_should_fail_with_message(): void
    {
        $this->expectException(RuntimeException::class);

        $this->artisan('make:repository');
    }
}
