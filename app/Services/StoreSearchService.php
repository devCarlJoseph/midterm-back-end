<?php

namespace App\Services;

use App\Models\Store;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class StoreSearchService
{
    public function find(?string $categorySlug, int $perPage): LengthAwarePaginator
    {
        return Store::query()
            ->active()
            ->when($categorySlug, function ($query, string $categorySlug): void {
                $query->whereHas('products', function ($productQuery) use ($categorySlug): void {
                    $productQuery
                        ->available()
                        ->whereHas('category', function ($categoryQuery) use ($categorySlug): void {
                            $categoryQuery->where('slug', $categorySlug);
                        });
                });
            })
            ->orderBy('name')
            ->paginate($perPage);

    }
}
