<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Track;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CustomerCartFlowTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsCustomer(User $user): void
    {
        Sanctum::actingAs($user, [
            'carts:self:get',
            'carts:self:post',
            'carts:self:delete',
            'cart-items:self:get',
            'cart-items:self:post',
            'cart-items:self:patch',
            'cart-items:self:delete',
            'checkout:self:post',
        ]);
    }

    public function test_customer_can_create_own_cart(): void
    {
        $customer = User::factory()->create(['role' => 2]);
        $this->actingAsCustomer($customer);

        $response = $this->postJson('/api/my-carts', [
            'date' => '2026-03-31',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.user_id', $customer->id);

        $this->assertDatabaseHas('carts', [
            'user_id' => $customer->id,
            'date' => '2026-03-31',
        ]);
    }

    public function test_customer_can_add_track_to_own_cart(): void
    {
        $customer = User::factory()->create(['role' => 2]);
        $cart = Cart::factory()->create(['user_id' => $customer->id]);
        $track = Track::factory()->create();

        $this->actingAsCustomer($customer);

        $response = $this->postJson('/api/my-cart-items', [
            'cart_id' => $cart->id,
            'track_id' => $track->id,
            'pcs' => 1,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.cart_id', $cart->id)
            ->assertJsonPath('data.track_id', $track->id)
            ->assertJsonPath('data.pcs', 1);

        $this->assertDatabaseHas('cart_items', [
            'cart_id' => $cart->id,
            'track_id' => $track->id,
            'pcs' => 1,
        ]);
    }

    public function test_customer_cannot_add_item_to_other_users_cart(): void
    {
        $owner = User::factory()->create(['role' => 2]);
        $otherCustomer = User::factory()->create(['role' => 2]);
        $otherCart = Cart::factory()->create(['user_id' => $otherCustomer->id]);
        $track = Track::factory()->create();

        $this->actingAsCustomer($owner);

        $response = $this->postJson('/api/my-cart-items', [
            'cart_id' => $otherCart->id,
            'track_id' => $track->id,
            'pcs' => 1,
        ]);

        $response->assertForbidden();
    }

    public function test_customer_can_delete_own_cart_item(): void
    {
        $customer = User::factory()->create(['role' => 2]);
        $cart = Cart::factory()->create(['user_id' => $customer->id]);
        $item = CartItem::factory()->create(['cart_id' => $cart->id]);

        $this->actingAsCustomer($customer);

        $response = $this->deleteJson("/api/my-cart-items/{$item->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $item->id);

        $this->assertDatabaseMissing('cart_items', ['id' => $item->id]);
    }
}

