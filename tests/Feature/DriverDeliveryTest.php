<?php

use App\Enums\DeliveryStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Models\Delivery;
use App\Models\Order;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

function createAvailableDriver(
    float $latitude = 14.5995,
    float $longitude = 120.9842,
): User {
    return User::factory()->create([
        'role' => UserRole::Driver,
        'is_available_for_delivery' => true,
        'latitude' => $latitude,
        'longitude' => $longitude,
    ]);
}

function createReadyOrderForDelivery(Store $store, User $customer): Order
{
    $order = Order::query()->create([
        'order_number' => 'DALI-'.Str::upper((string) Str::uuid()),
        'user_id' => $customer->id,
        'store_id' => $store->id,
        'delivery_address' => [
            'label' => 'Home',
            'recipient_name' => $customer->name,
            'phone' => '09171234567',
            'line_one' => '123 Sample Street',
            'line_two' => null,
            'barangay' => null,
            'city' => 'Manila',
            'province' => 'Metro Manila',
            'postal_code' => null,
        ],
        'status' => OrderStatus::Ready,
        'payment_method' => PaymentMethod::CashOnDelivery,
        'subtotal' => '100.00',
        'delivery_fee' => '30.00',
        'total' => '130.00',
    ]);

    $order->items()->create([
        'product_name' => 'Chicken Breast',
        'unit' => 'kg',
        'unit_price' => '100.00',
        'quantity' => 1,
        'line_total' => '100.00',
    ]);

    $order->payment()->create([
        'method' => PaymentMethod::CashOnDelivery,
        'status' => PaymentStatus::Pending,
        'amount' => '130.00',
    ]);

    return $order;
}

test('available driver sees only nearby ready orders', function (): void {
    config([
        'delivery.driver_matching_radius_in_kilometers' => 5,
    ]);

    $driver = createAvailableDriver();

    $customer = User::factory()->create([
        'role' => UserRole::Customer,
    ]);

    $nearbyStore = Store::factory()->create([
        'latitude' => 14.6000,
        'longitude' => 120.9845,
    ]);

    $farStore = Store::factory()->create([
        'latitude' => 14.8000,
        'longitude' => 121.1000,
    ]);

    $nearbyOrder = createReadyOrderForDelivery($nearbyStore, $customer);
    $farOrder = createReadyOrderForDelivery($farStore, $customer);

    Sanctum::actingAs($driver);

    $this->getJson('/api/v1/driver/orders/available')
        ->assertOk()
        ->assertJsonFragment([
            'id' => $nearbyOrder->id,
        ])
        ->assertJsonMissing([
            'id' => $farOrder->id,
        ]);
});

test('available driver accepts a ready order for delivery', function (): void {
    $driver = createAvailableDriver();

    $customer = User::factory()->create([
        'role' => UserRole::Customer,
    ]);

    $store = Store::factory()->create([
        'latitude' => 14.5995,
        'longitude' => 120.9842,
    ]);

    $order = createReadyOrderForDelivery($store, $customer);

    Sanctum::actingAs($driver);

    $this->postJson("/api/v1/driver/orders/{$order->id}/delivery")
        ->assertOk()
        ->assertJsonPath('data.status', DeliveryStatus::Assigned->value);

    $this->assertDatabaseHas('deliveries', [
        'order_id' => $order->id,
        'driver_id' => $driver->id,
        'status' => DeliveryStatus::Assigned->value,
    ]);

    expect($order->refresh()->status)->toBe(OrderStatus::Ready);
});

test('returns 422 when driver accepts another order while having an active delivery', function (): void {
    $driver = createAvailableDriver();

    $customer = User::factory()->create([
        'role' => UserRole::Customer,
    ]);

    $store = Store::factory()->create([
        'latitude' => 14.5995,
        'longitude' => 120.9842,
    ]);

    $firstOrder = createReadyOrderForDelivery($store, $customer);
    $secondOrder = createReadyOrderForDelivery($store, $customer);

    Sanctum::actingAs($driver);

    $this->postJson("/api/v1/driver/orders/{$firstOrder->id}/delivery")
        ->assertOk();

    $this->postJson("/api/v1/driver/orders/{$secondOrder->id}/delivery")
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['delivery']);

    $this->assertDatabaseCount('deliveries', 1);
});

test('returns 403 when another driver attempts to pick up or complete a delivery', function (): void {
    $ownerDriver = createAvailableDriver();
    $otherDriver = createAvailableDriver(
        latitude: 14.6000,
        longitude: 120.9850,
    );

    $customer = User::factory()->create([
        'role' => UserRole::Customer,
    ]);

    $store = Store::factory()->create([
        'latitude' => 14.5995,
        'longitude' => 120.9842,
    ]);

    $order = createReadyOrderForDelivery($store, $customer);

    Sanctum::actingAs($ownerDriver);

    $this->postJson("/api/v1/driver/orders/{$order->id}/delivery")
        ->assertOk();

    $delivery = Delivery::query()->firstOrFail();

    Sanctum::actingAs($otherDriver);

    $this->postJson("/api/v1/driver/deliveries/{$delivery->id}/pickup")
        ->assertForbidden();

    $this->postJson("/api/v1/driver/deliveries/{$delivery->id}/complete")
        ->assertForbidden();

    expect($delivery->refresh()->status)->toBe(DeliveryStatus::Assigned);
    expect($order->refresh()->status)->toBe(OrderStatus::Ready);
});

test('driver pickup and completion update delivery and order statuses', function (): void {
    $driver = createAvailableDriver();

    $customer = User::factory()->create([
        'role' => UserRole::Customer,
    ]);

    $store = Store::factory()->create([
        'latitude' => 14.5995,
        'longitude' => 120.9842,
    ]);

    $order = createReadyOrderForDelivery($store, $customer);

    Sanctum::actingAs($driver);

    $this->postJson("/api/v1/driver/orders/{$order->id}/delivery")
        ->assertOk();

    $delivery = Delivery::query()->firstOrFail();

    $this->postJson("/api/v1/driver/deliveries/{$delivery->id}/pickup")
        ->assertOk()
        ->assertJsonPath('data.status', DeliveryStatus::PickedUp->value);

    $this->postJson("/api/v1/driver/deliveries/{$delivery->id}/complete")
        ->assertOk()
        ->assertJsonPath('data.status', DeliveryStatus::Delivered->value);

    $this->assertDatabaseHas('deliveries', [
        'id' => $delivery->id,
        'status' => DeliveryStatus::Delivered->value,
    ]);

    $this->assertDatabaseHas('orders', [
        'id' => $order->id,
        'status' => OrderStatus::Delivered->value,
    ]);

    $this->assertDatabaseHas('order_status_histories', [
        'order_id' => $order->id,
        'from_status' => OrderStatus::Ready->value,
        'to_status' => OrderStatus::PickedUp->value,
        'changed_by' => $driver->id,
    ]);

    $this->assertDatabaseHas('order_status_histories', [
        'order_id' => $order->id,
        'from_status' => OrderStatus::PickedUp->value,
        'to_status' => OrderStatus::Delivered->value,
        'changed_by' => $driver->id,
    ]);
});

test('returns 403 when a customer requests driver orders', function (): void {
    $customer = User::factory()->create([
        'role' => UserRole::Customer,
    ]);

    Sanctum::actingAs($customer);

    $this->getJson('/api/v1/driver/orders/available')
        ->assertForbidden();
});

test('returns 401 when no token is provided for driver orders', function (): void {
    $this->getJson('/api/v1/driver/orders/available')
        ->assertUnauthorized();
});