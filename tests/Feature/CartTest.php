<?php

use App\Enums\UserRole;
use App\Models\Cart;
use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('returns an empty cart for a customer without cart items', function (): void {
    $customer = User::factory()->create([
        'role' => UserRole::Customer,
    ]);

    Sanctum::actingAs($customer);

    $this->getJson('/api/v1/cart')
        ->assertOk()
        ->assertJsonPath('data.items', [])
        ->assertJsonPath('data.subtotal', '0.00');
});

test('adds an available product to the customer cart', function (): void {
    $customer = User::factory()->create([
        'role' => UserRole::Customer,
    ]);

    $product = Product::factory()->create([
        'price' => 250.00,
        'stock_quantity' => 10,
    ]);

    Sanctum::actingAs($customer);

    $this->postJson('/api/v1/cart/items', [
        'product_id' => $product->id,
        'quantity' => 2,
    ])
        ->assertCreated()
        ->assertJsonPath('data.items.0.product.id', $product->id)
        ->assertJsonPath('data.items.0.quantity', 2)
        ->assertJsonPath('data.subtotal', '500.00');

    $cart = $customer->cart()->firstOrFail();

    $this->assertDatabaseHas('cart_items', [
        'cart_id' => $cart->id,
        'product_id' => $product->id,
        'quantity' => 2,
    ]);
});

test('updates a cart item quantity', function (): void {
    $customer = User::factory()->create([
        'role' => UserRole::Customer,
    ]);

    $product = Product::factory()->create([
        'price' => 100.00,
        'stock_quantity' => 10,
    ]);

    $cart = $customer->cart()->create([
        'store_id' => $product->store_id,
    ]);

    $cartItem = $cart->items()->create([
        'product_id' => $product->id,
        'quantity' => 1,
    ]);

    Sanctum::actingAs($customer);

    $this->patchJson("/api/v1/cart/items/{$cartItem->id}", [
        'quantity' => 3,
    ])
        ->assertOk()
        ->assertJsonPath('data.items.0.quantity', 3)
        ->assertJsonPath('data.subtotal', '300.00');

    $this->assertDatabaseHas('cart_items', [
        'id' => $cartItem->id,
        'quantity' => 3,
    ]);
});

test('removes a cart item and resets the store when it is the last item', function (): void {
    $customer = User::factory()->create([
        'role' => UserRole::Customer,
    ]);

    $product = Product::factory()->create();

    $cart = $customer->cart()->create([
        'store_id' => $product->store_id,
    ]);

    $cartItem = $cart->items()->create([
        'product_id' => $product->id,
        'quantity' => 1,
    ]);

    Sanctum::actingAs($customer);

    $this->deleteJson("/api/v1/cart/items/{$cartItem->id}")
        ->assertOk()
        ->assertJsonPath('data.items', [])
        ->assertJsonPath('data.subtotal', '0.00');

    $this->assertDatabaseMissing('cart_items', [
        'id' => $cartItem->id,
    ]);

    expect($cart->refresh()->store_id)->toBeNull();
});

test('returns 422 when a customer adds products from different stores', function (): void {
    $customer = User::factory()->create([
        'role' => UserRole::Customer,
    ]);

    $category = Category::factory()->create();
    $firstStore = Store::factory()->create();
    $secondStore = Store::factory()->create();

    $firstProduct = Product::factory()
        ->for($firstStore)
        ->for($category)
        ->create();

    $secondProduct = Product::factory()
        ->for($secondStore)
        ->for($category)
        ->create();

    Sanctum::actingAs($customer);

    $this->postJson('/api/v1/cart/items', [
        'product_id' => $firstProduct->id,
        'quantity' => 1,
    ])->assertCreated();

    $this->postJson('/api/v1/cart/items', [
        'product_id' => $secondProduct->id,
        'quantity' => 1,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['product_id']);

    $this->assertDatabaseCount('cart_items', 1);
});

test('returns 422 when the requested quantity exceeds stock', function (): void {
    $customer = User::factory()->create([
        'role' => UserRole::Customer,
    ]);

    $product = Product::factory()->create([
        'stock_quantity' => 2,
    ]);

    Sanctum::actingAs($customer);

    $this->postJson('/api/v1/cart/items', [
        'product_id' => $product->id,
        'quantity' => 3,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['product_id']);

    $this->assertDatabaseMissing('cart_items', [
        'product_id' => $product->id,
    ]);
});

test('returns 403 when another customer updates a cart item', function (): void {
    $owner = User::factory()->create([
        'role' => UserRole::Customer,
    ]);

    $otherCustomer = User::factory()->create([
        'role' => UserRole::Customer,
    ]);

    $product = Product::factory()->create();

    $cart = $owner->cart()->create([
        'store_id' => $product->store_id,
    ]);

    $cartItem = $cart->items()->create([
        'product_id' => $product->id,
        'quantity' => 1,
    ]);

    Sanctum::actingAs($otherCustomer);

    $this->patchJson("/api/v1/cart/items/{$cartItem->id}", [
        'quantity' => 2,
    ])->assertForbidden();

    $this->assertDatabaseHas('cart_items', [
        'id' => $cartItem->id,
        'quantity' => 1,
    ]);
});

test('returns 401 when no token is provided for the cart', function (): void {
    $this->getJson('/api/v1/cart')
        ->assertUnauthorized();
});