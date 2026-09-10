<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Models\Order;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

function createMerchantTestOrder(
    Store $store,
    User $customer,
    OrderStatus $status = OrderStatus::Pending,
): Order {
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
        'status' => $status,
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

function createMerchantForStore(Store $store): User
{
    $merchant = User::factory()->create([
        'role' => UserRole::Merchant,
    ]);

    $store->users()->attach($merchant, [
        'role' => 'owner',
    ]);

    return $merchant;
}

test('merchant only sees orders belonging to their stores', function (): void {
    $merchantStore = Store::factory()->create();
    $otherStore = Store::factory()->create();

    $merchant = createMerchantForStore($merchantStore);
    $otherMerchant = createMerchantForStore($otherStore);

    $customer = User::factory()->create([
        'role' => UserRole::Customer,
    ]);

    $merchantOrder = createMerchantTestOrder($merchantStore, $customer);
    $otherOrder = createMerchantTestOrder($otherStore, $customer);

    Sanctum::actingAs($merchant);

    $this->getJson('/api/v1/merchant/orders')
        ->assertOk()
        ->assertJsonFragment([
            'id' => $merchantOrder->id,
        ])
        ->assertJsonMissing([
            'id' => $otherOrder->id,
        ]);

    expect($otherMerchant->id)->not->toBe($merchant->id);
});

test('merchant accepts prepares and marks an order ready', function (): void {
    $store = Store::factory()->create();
    $merchant = createMerchantForStore($store);

    $customer = User::factory()->create([
        'role' => UserRole::Customer,
    ]);

    $order = createMerchantTestOrder($store, $customer);

    Sanctum::actingAs($merchant);

    $this->postJson("/api/v1/merchant/orders/{$order->id}/accept")
        ->assertOk()
        ->assertJsonPath('data.status', OrderStatus::Accepted->value);

    $this->postJson("/api/v1/merchant/orders/{$order->id}/preparing")
        ->assertOk()
        ->assertJsonPath('data.status', OrderStatus::Preparing->value);

    $this->postJson("/api/v1/merchant/orders/{$order->id}/ready")
        ->assertOk()
        ->assertJsonPath('data.status', OrderStatus::Ready->value);

    $this->assertDatabaseHas('orders', [
        'id' => $order->id,
        'status' => OrderStatus::Ready->value,
    ]);

    $this->assertDatabaseHas('order_status_histories', [
        'order_id' => $order->id,
        'from_status' => OrderStatus::Pending->value,
        'to_status' => OrderStatus::Accepted->value,
        'changed_by' => $merchant->id,
    ]);

    $this->assertDatabaseHas('order_status_histories', [
        'order_id' => $order->id,
        'from_status' => OrderStatus::Accepted->value,
        'to_status' => OrderStatus::Preparing->value,
        'changed_by' => $merchant->id,
    ]);

    $this->assertDatabaseHas('order_status_histories', [
        'order_id' => $order->id,
        'from_status' => OrderStatus::Preparing->value,
        'to_status' => OrderStatus::Ready->value,
        'changed_by' => $merchant->id,
    ]);
});

test('returns 422 when merchant skips an order status transition', function (): void {
    $store = Store::factory()->create();
    $merchant = createMerchantForStore($store);

    $customer = User::factory()->create([
        'role' => UserRole::Customer,
    ]);

    $order = createMerchantTestOrder($store, $customer);

    Sanctum::actingAs($merchant);

    $this->postJson("/api/v1/merchant/orders/{$order->id}/preparing")
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['order']);

    $this->assertDatabaseHas('orders', [
        'id' => $order->id,
        'status' => OrderStatus::Pending->value,
    ]);

    $this->assertDatabaseMissing('order_status_histories', [
        'order_id' => $order->id,
        'to_status' => OrderStatus::Preparing->value,
    ]);
});

test('returns 403 when merchant attempts to manage another store order', function (): void {
    $ownedStore = Store::factory()->create();
    $otherStore = Store::factory()->create();

    $merchant = createMerchantForStore($ownedStore);

    $customer = User::factory()->create([
        'role' => UserRole::Customer,
    ]);

    $otherOrder = createMerchantTestOrder($otherStore, $customer);

    Sanctum::actingAs($merchant);

    $this->postJson("/api/v1/merchant/orders/{$otherOrder->id}/accept")
        ->assertForbidden();

    $this->assertDatabaseHas('orders', [
        'id' => $otherOrder->id,
        'status' => OrderStatus::Pending->value,
    ]);
});

test('returns 403 when a customer requests merchant orders', function (): void {
    $customer = User::factory()->create([
        'role' => UserRole::Customer,
    ]);

    Sanctum::actingAs($customer);

    $this->getJson('/api/v1/merchant/orders')
        ->assertForbidden();
});

test('returns 401 when no token is provided for merchant orders', function (): void {
    $this->getJson('/api/v1/merchant/orders')
        ->assertUnauthorized();
});
