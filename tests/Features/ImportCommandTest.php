<?php

declare(strict_types=1);

namespace Tests\Features;

use App\User;
use App\Wall;
use function count;
use Tests\TestCase;

final class ImportCommandTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->loadMigrationsFrom(database_path('migrations'));
        $this->artisan('migrate:fresh', ['--database' => 'testbench'])->run();
    }

    public function testImport(): void
    {
        Wall::bootSearchable();

        factory(User::class, 5)->create();

        // Detects searchable models.
        $userIndexMock = $this->mockIndex(User::class);
        $userIndexMock->expects('clear')->once();
        $userIndexMock->expects('saveObjects')->once()->with(\Mockery::on(function ($argument) {
            return count($argument) === 5 && $argument[0]['objectID'] === 1;
        }));

        // Detects aggregators.
        $wallIndexMock = $this->mockIndex(Wall::class);
        $wallIndexMock->expects('clear')->once();
        $wallIndexMock->expects('saveObjects')->once()->with(\Mockery::on(function ($argument) {
            return count($argument) === 5 && $argument[0]['objectID'] === 'users_1';
        }));

        $this->artisan('scout:import');
    }
}
