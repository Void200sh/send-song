<?php

namespace Tests;

use App\Support\Settings;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Setting punya memo static + cache — reset tiap test supaya state
        // (misal photos_enabled) tidak bocor antar test dalam proses yang sama.
        Settings::flushMemo();
    }
}
