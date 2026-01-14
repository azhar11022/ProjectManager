<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Map 'tenant' connection to use the same configuration as 'sqlite' (in-memory)
        config(['database.connections.tenant' => config('database.connections.sqlite')]);
    }
}
