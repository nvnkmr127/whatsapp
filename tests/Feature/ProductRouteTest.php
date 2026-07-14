<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Team;

class ProductRouteTest extends TestCase
{
    public function test_product_route()
    {
        $user = User::first();
        if (!$user) {
            $user = User::factory()->create();
            $team = Team::factory()->create(['user_id' => $user->id]);
            $user->current_team_id = $team->id;
            $user->save();
        }
        $this->actingAs($user);
        $response = $this->get('/commerce/products');
        if ($response->status() === 500) {
            echo "500 ERROR CAUGHT:\n";
            if ($response->exception) {
                echo $response->exception->getMessage() . "\n";
                echo $response->exception->getTraceAsString() . "\n";
            } else {
                echo $response->getContent();
            }
        }
        $response->assertStatus(200);
    }
}
