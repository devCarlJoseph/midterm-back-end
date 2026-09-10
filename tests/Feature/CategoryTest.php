<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('anyone can view public categories in alphabetical order', function (): void {

    $response = $this->getJson('/api/v1/categories');

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'name',
                    'slug',
                ],
            ],
        ]);

    $names = collect($response->json('data'))->pluck('name')->all();
    expect($names)->toContain('Alcohol', 'Bakery', 'Snacks');
});
