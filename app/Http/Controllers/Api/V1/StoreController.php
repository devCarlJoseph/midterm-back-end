<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Stores\BrowseStoresRequest;
use App\Http\Requests\Stores\StoreStoreRequest;
use App\Http\Requests\Stores\UpdateStoreRequest;
use App\Http\Resources\StoreResource;
use App\Models\Store;
use App\Services\StoreSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class StoreController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(
        BrowseStoresRequest $request,
        StoreSearchService $storeSearchService,
    ): AnonymousResourceCollection {
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
    public function store(StoreStoreRequest $request): JsonResponse
    {
        $store = Store::query()->create($request->validated());

        $store->user()->attach($request->user(), [
            'role' => 'owner',
        ]);

        return (new StoreResource($store))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
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
    public function update(UpdateStoreRequest $request, Store $store): StoreResource
    {
        $store->update($request->validated());

        return new StoreResource($store->refresh());
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(UpdateStoreRequest $request, Store $store): Response
    {
        $store->delete();

        return response()->noContent();
    }
}
