<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Stores\BrowseStoresRequest;
use App\Http\Resources\ProductResource;
use App\Models\Store;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Request;

class StoreProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(
        BrowseStoresRequest $request,
        Store $store,
    ): AnonymousResourceCollection
    {
        abort_unless($store->is_active, 404);

        return ProductResource::collection(
            $store->products()
                ->available()
                ->with('category')
                ->when(
                    $request->validated('category'),
                    function ($query, string $categorySlug): void {
                        $query->whereHas('category', function ($categoryQuery) use ($categorySlug): void {
                            $categoryQuery->where('slug', $categorySlug);
                        });
                    },
                )
                ->orderBy('name')
                ->paginate($request->integer('per_page', 15)),
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
