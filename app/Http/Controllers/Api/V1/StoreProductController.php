<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Products\StoreProductRequest;
use App\Http\Requests\Products\UpdateProductRequest;
use App\Http\Requests\Stores\BrowseStoresRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class StoreProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(
        BrowseStoresRequest $request,
        Store $store,
    ): AnonymousResourceCollection {
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
    public function store(
        StoreProductRequest $request,
        Store $store,
    ): JsonResponse {
        $product = $store->products()->create($request->validated());

        return (new ProductResource($product->load('category')))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
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
    public function update(
        UpdateProductRequest $request,
        Store $store,
        Product $product,
    ): ProductResource {
        $product->update($request->validated());

        return new ProductResource($product->refresh()->load('category'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(
        UpdateProductRequest $request,
        Store $store,
        Product $product,
    ): Response {
        $product->delete();

        return response()->noContent();
    }
}
