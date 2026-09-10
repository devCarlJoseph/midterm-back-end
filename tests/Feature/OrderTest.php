<?php

use App\Enums\DeliveryOptionName;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

function createCheckoutData(User $customer, int $stock = 5, int $quantity = 2): array
{
    $store = Store::factory()->create([
        'latitude' => 14.5995,
        'longitude' => 120.9842,
    ]);

    $deliveryOption = $store->deliveryOptions()->create([
        'name' => DeliveryOptionName::Saver,
        'description' => 'Lowest-cost delivery option.',
        'additional_fee' => 0.00,
        'estimated_delivery_minutes' => 90,
        'is_active' => true,
    ]);

    $category = Category::factory()->create();

    $product = Product::factory()
        ->for($store)
        ->for($category)
        ->create([
            'price' => 100.00,
            'stock_quantity' => $stock,
            'is_available' => true,
        ]);

    $address = $customer->addresses()->create([
        'label' => 'Home',
        'recipient_name' => $customer->name,
        'phone' => '09171234567',
        'line_one' => '123 Sample Street',
        'city' => 'Manila',
        'province' => 'Metro Manila',
        'latitude' => 14.5995,
        'longitude' => 120.9842,
        'is_default' => true,
    ]);

    $cart = $customer->cart()->create([
        'store_id' => $store->id,
    ]);

    $cart->items()->create([
        'product_id' => $product->id,
        'quantity' => $quantity,
    ]);

    return compact(
        'address',
        'cart',
        'product',
        'store',
        'deliveryOption',
    );
}

test('customer places an order and checkout creates immutable records', function (): void {
    $customer = User::factory()->create();

    $data = createCheckoutData($customer);

    Sanctum::actingAs($customer);

    $response = $this->postJson('/api/v1/orders', [
        'address_id' => $data['address']->id,
        'delivery_option_id' => $data['deliveryOption']->id,
        'payment_method' => PaymentMethod::CashOnDelivery->value,
    ]);

    $response
        ->assertCreated()
        ->assertJsonPath('data.status', OrderStatus::Pending->value)
        ->assertJsonPath('data.subtotal', '200.00')
        ->assertJsonPath('data.delivery_fee', '30.00')
        ->assertJsonPath('data.total', '230.00')
        ->assertJsonPath(
            'data.delivery_option.name',
            DeliveryOptionName::Saver->value,
        )
        ->assertJsonPath(
            'data.delivery_option.estimated_delivery_minutes',
            90,
        )
        ->assertJsonPath('data.items.0.product_name', $data['product']->name)
        ->assertJsonPath('data.items.0.quantity', 2);

    $order = Order::query()->firstOrFail();

    $this->assertDatabaseHas('orders', [
        'id' => $order->id,
        'user_id' => $customer->id,
        'store_id' => $data['store']->id,
        'delivery_option_id' => $data['deliveryOption']->id,
        'delivery_option_name' => DeliveryOptionName::Saver->value,
        'estimated_delivery_minutes' => 90,
        'status' => OrderStatus::Pending->value,
        'subtotal' => '200.00',
        'delivery_fee' => '30.00',
        'total' => '230.00',
    ]);

    $this->assertDatabaseHas('order_items', [
        'order_id' => $order->id,
        'product_id' => $data['product']->id,
        'product_name' => $data['product']->name,
        'quantity' => 2,
        'unit_price' => '100.00',
        'line_total' => '200.00',
    ]);

    $this->assertDatabaseHas('payments', [
        'order_id' => $order->id,
        'method' => PaymentMethod::CashOnDelivery->value,
        'amount' => '230.00',
    ]);

    $this->assertDatabaseHas('order_status_histories', [
        'order_id' => $order->id,
        'to_status' => OrderStatus::Pending->value,
        'changed_by' => $customer->id,
    ]);

    expect($data['product']->refresh()->stock_quantity)->toBe(3);
    expect($data['cart']->refresh()->store_id)->toBeNull();

    $this->assertDatabaseMissing('cart_items', [
        'cart_id' => $data['cart']->id,
    ]);
});

test('returns 422 when customer checks out with an empty cart', function (): void {
    $customer = User::factory()->create();

    $address = $customer->addresses()->create([
        'label' => 'Home',
        'recipient_name' => $customer->name,
        'phone' => '09171234567',
        'line_one' => '123 Sample Street',
        'city' => 'Manila',
        'province' => 'Metro Manila',
        'latitude' => 14.5995,
        'longitude' => 120.9842,
    ]);

    $store = Store::factory()->create();

    $deliveryOption = $store->deliveryOptions()->create([
        'name' => DeliveryOptionName::Saver,
        'description' => 'Lowest-cost delivery option.',
        'additional_fee' => 0.00,
        'estimated_delivery_minutes' => 90,
        'is_active' => true,
    ]);

    Sanctum::actingAs($customer);

    $this->postJson('/api/v1/orders', [
        'address_id' => $address->id,
        'delivery_option_id' => $deliveryOption->id,
        'payment_method' => PaymentMethod::CashOnDelivery->value,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['cart']);

    $this->assertDatabaseCount('orders', 0);
});

test('returns 404 when customer checks out using another customer address', function (): void {
    $customer = User::factory()->create();
    $otherCustomer = User::factory()->create();

    $data = createCheckoutData($customer);

    $otherAddress = $otherCustomer->addresses()->create([
        'label' => 'Home',
        'recipient_name' => $otherCustomer->name,
        'phone' => '09171234567',
        'line_one' => '456 Other Street',
        'city' => 'Manila',
        'province' => 'Metro Manila',
        'latitude' => 14.5995,
        'longitude' => 120.9842,
    ]);

    Sanctum::actingAs($customer);

    $this->postJson('/api/v1/orders', [
        'address_id' => $otherAddress->id,
        'delivery_option_id' => $data['deliveryOption']->id,
        'payment_method' => PaymentMethod::CashOnDelivery->value,
    ])->assertNotFound();

    $this->assertDatabaseCount('orders', 0);
    expect($data['product']->refresh()->stock_quantity)->toBe(5);
});

test('returns 422 and makes no changes when cart quantity exceeds stock', function (): void {
    $customer = User::factory()->create();

    $data = createCheckoutData($customer, stock: 1, quantity: 2);

    Sanctum::actingAs($customer);

    $this->postJson('/api/v1/orders', [
        'address_id' => $data['address']->id,
        'delivery_option_id' => $data['deliveryOption']->id,
        'payment_method' => PaymentMethod::CashOnDelivery->value,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['cart']);

    $this->assertDatabaseCount('orders', 0);

    $this->assertDatabaseHas('cart_items', [
        'cart_id' => $data['cart']->id,
        'product_id' => $data['product']->id,
        'quantity' => 2,
    ]);

    expect($data['product']->refresh()->stock_quantity)->toBe(1);
});

test('customer cancels a pending order and stock is restored', function (): void {
    $customer = User::factory()->create();

    $data = createCheckoutData($customer);

    Sanctum::actingAs($customer);

    $this->postJson('/api/v1/orders', [
        'address_id' => $data['address']->id,
        'delivery_option_id' => $data['deliveryOption']->id,
        'payment_method' => PaymentMethod::CashOnDelivery->value,
    ])->assertCreated();

    $order = Order::query()->firstOrFail();

    $this->postJson("/api/v1/orders/{$order->id}/cancel")
        ->assertOk()
        ->assertJsonPath('data.status', OrderStatus::Cancelled->value);

    $this->assertDatabaseHas('orders', [
        'id' => $order->id,
        'status' => OrderStatus::Cancelled->value,
    ]);

    $this->assertDatabaseHas('order_status_histories', [
        'order_id' => $order->id,
        'from_status' => OrderStatus::Pending->value,
        'to_status' => OrderStatus::Cancelled->value,
        'changed_by' => $customer->id,
    ]);

    expect($data['product']->refresh()->stock_quantity)->toBe(5);
});

test('returns 403 when customer attempts to cancel an accepted order', function (): void {
    $customer = User::factory()->create();

    $data = createCheckoutData($customer);

    Sanctum::actingAs($customer);

    $this->postJson('/api/v1/orders', [
        'address_id' => $data['address']->id,
        'delivery_option_id' => $data['deliveryOption']->id,
        'payment_method' => PaymentMethod::CashOnDelivery->value,
    ])->assertCreated();

    $order = Order::query()->firstOrFail();

    $order->update([
        'status' => OrderStatus::Accepted,
    ]);

    $this->postJson("/api/v1/orders/{$order->id}/cancel")
        ->assertForbidden();

    expect($data['product']->refresh()->stock_quantity)->toBe(3);
});

test('returns 401 when no token is provided while placing an order', function (): void {
    $this->postJson('/api/v1/orders', [
        'address_id' => 1,
        'delivery_option_id' => 1,
        'payment_method' => PaymentMethod::CashOnDelivery->value,
    ])->assertUnauthorized();
});