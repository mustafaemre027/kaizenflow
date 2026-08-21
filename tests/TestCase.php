<?php

namespace Tests;

use App\Testing\DatabaseSafetyGuard;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Boot the testing helper traits.
     *
     * @return array<class-string, class-string>
     */
    protected function setUpTraits()
    {
        // Enforce fail-closed database isolation guard before ANY trait 
        // (including RefreshDatabase) runs.
        DatabaseSafetyGuard::verify();

        return parent::setUpTraits();
    }
}
