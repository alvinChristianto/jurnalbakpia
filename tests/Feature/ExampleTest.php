<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_the_application_root_redirects(): void
    {
        // Root URL redirects to the Filament admin login.
        $this->get('/')->assertRedirect();
    }

    public function test_health_endpoint_is_reachable(): void
    {
        $this->get('/up')->assertOk();
    }
}
