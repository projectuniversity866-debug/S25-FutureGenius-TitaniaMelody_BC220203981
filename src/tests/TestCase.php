<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Feature tests check server-rendered HTML; they must not depend on
        // `npm run build` having been run.
        $this->withoutVite();
    }
}
