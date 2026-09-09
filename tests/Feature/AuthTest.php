<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;

uses(RefreshDatabase::class);

test('a customer can register', function (): void {
    $response = $this->postJson('/api/v1/auth/register', [
        'name' => 'Juan Dela Cruz',
        'email' => 'juan@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'device_name' => 'DALI Mobile App',
    ]);

    $response
        ->assertCreated()
        ->assertJsonPath('data.user.email', 'juan@example.com')
        ->assertJsonPath('data.user.role', 'customer')
        ->assertJsonStructure([
            'message',
            'data' => [
                'user',
                'token',
                'token_type',
            ],
        ]);

    $user = User::query()->where('email', 'juan@example.com')->firstOrFail();

    expect($user->role)->toBe(UserRole::Customer);
    expect($user->tokens()->count())->toBe(1);
});

test('a user can log in with valid credentials', function (): void {
    $user = User::factory()->create();

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => $user->email,
        'password' => 'password',
        'device_name' => 'DALI Mobile App',
    ]);

    $response
        ->assertOk()
        ->assertJsonPath('data.user.id', $user->id)
        ->assertJsonPath('data.token_type', 'Bearer')
        ->assertJsonStructure([
            'data' => [
                'token',
            ],
        ]);
});

test('a user cannot log in with invalid credentials', function (): void {
    $user = User::factory()->create();

    $this->postJson('/api/v1/auth/login', [
        'email' => $user->email,
        'password' => 'incorrect-password',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});

test('an authenticated user can view their profile', function (): void {
    $user = User::factory()->create();
    $token = $user->createToken('DALI Test')->plainTextToken;

    $this->withToken($token)
        ->getJson('/api/v1/auth/me')
        ->assertOk()
        ->assertJsonPath('data.id', $user->id);
});

test('a user cannot access protected endpoints after logout', function (): void {
    $user = User::factory()->create();
    $token = $user->createToken('DALI Test')->plainTextToken;

    $this->withToken($token)
        ->postJson('/api/v1/auth/logout')
        ->assertOk();

    Auth::forgetGuards();

    $this->withToken($token)
        ->getJson('/api/v1/auth/me')
        ->assertUnauthorized();
});
