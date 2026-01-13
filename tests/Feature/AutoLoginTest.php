<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\AutoLoginSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AutoLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_login_as_admin_via_dev_route()
    {
        $this->seed(AutoLoginSeeder::class);

        $response = $this->get('/dev/login/admin@example.com');

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs(User::where('email', 'admin@example.com')->first());
    }

    public function test_cannot_login_as_non_existent_user()
    {
        $response = $this->get('/dev/login/nonexistent@example.com');

        $response->assertStatus(404);
    }
}
