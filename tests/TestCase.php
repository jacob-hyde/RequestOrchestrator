<?php

namespace JacobHyde\RequestOrchestrator\Tests;

use JacobHyde\RequestOrchestrator\RequestOrchestratorServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    public function setUp(): void
    {
        parent::setUp();
        // additional setup
    }
    protected function getPackageProviders($app)
    {
        return [
            RequestOrchestratorServiceProvider::class,
        ];
    }
}