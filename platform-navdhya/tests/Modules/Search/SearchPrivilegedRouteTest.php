<?php

namespace Tests\Modules\Search;

use Illuminate\Foundation\Testing\DatabaseTruncation;
use Tests\TestCase;

class SearchPrivilegedRouteTest extends TestCase
{
    use DatabaseTruncation;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate', ['--path' => 'app/Modules/Search/Database/Migrations', '--database' => 'central', '--force' => true]);
    }

    public function test_privileged_route_registered(): void
    {
        $found = false;
        foreach (\Illuminate\Support\Facades\Route::getRoutes() as $r) {
            if (str_contains($r->uri(), 'search/privileged')) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, 'privileged search route should be registered');
    }
}