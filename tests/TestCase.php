<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();

        if (class_exists(\Spatie\Activitylog\ActivityLogStatus::class)) {
            app(\Spatie\Activitylog\ActivityLogStatus::class)->disable();
        }
    }
}
