<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $status = $response->status();
        $this->assertTrue(
            in_array($status, [200, 301, 302, 303, 307, 308], true),
            "Se esperaba 200 o redirección para '/', pero se recibió {$status}."
        );
    }
}
