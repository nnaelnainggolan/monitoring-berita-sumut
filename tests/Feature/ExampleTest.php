<?php
namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;
    public function test_dashboard_requires_login(): void
    {
        $this->get('/')->assertRedirect('/login');
    }
}
