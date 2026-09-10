<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Stores\DeleteDeliveryOptionRequest;
use App\Http\Requests\Stores\StoreDeliveryOptionRequest;
use App\Http\Requests\Stores\UpdateDeliveryOptionRequest;
use App\Http\Resources\DeliveryOptionResource;
use App\Models\DeliveryOption;
use App\Models\Store;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class StoreDeliveryOptionController extends Controller
{
    public function index(Store $store): AnonymousResourceCollection
    {
        abort_unless($store->is_active, Response::HTTP_NOT_FOUND);

        return DeliveryOptionResource::collection(
            $store->deliveryOptions()
                ->active()
                ->orderBy('additional_fee')
                ->get(),
        );
    }

    public function store(
        StoreDeliveryOptionRequest $request,
        Store $store,
    ): JsonResponse {
        $deliveryOption = $store->deliveryOptions()->create(
            $request->validated(),
        );

        return (new DeliveryOptionResource($deliveryOption))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function update(
        UpdateDeliveryOptionRequest $request,
        Store $store,
        DeliveryOption $deliveryOption,
    ): DeliveryOptionResource {
        $deliveryOption->update($request->validated());

        return new DeliveryOptionResource($deliveryOption->refresh());
    }

    public function destroy(
        DeleteDeliveryOptionRequest $request,
        Store $store,
        DeliveryOption $deliveryOption,
    ) {
        $deliveryOption->delete();

        return response()->noContent();
    }
}