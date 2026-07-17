<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductRouteTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_route_renders()
    {
        config(['app.full_access_all' => true]);

        $user = User::factory()->withPersonalTeam()->create();

        $this->actingAs($user)
            ->get('/commerce/products')
            ->assertStatus(200);
    }
}
