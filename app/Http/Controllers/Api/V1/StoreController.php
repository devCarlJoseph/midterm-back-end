<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Stores\BrowseStoresRequest;
use App\Http\Resources\StoreResource;
use App\Models\Store;
use App\Services\StoreSearchService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Request;

class StoreController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(
        BrowseStoresRequest $request,
        StoreSearchService $storeSearchService,
    ): AnonymousResourceCollection
    {
        return StoreResource::collection(
            $storeSearchService->find(
                $request->validated('category'),
                $request->integer('per_page', 15),
            ),
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
    public function show(Store $store): StoreResource
    {
        abort_unless($store->is_active, 404);

        return new StoreResource($store);
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
