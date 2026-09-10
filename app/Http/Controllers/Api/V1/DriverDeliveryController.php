<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Deliveries\AcceptDelivery;
use App\Actions\Deliveries\CompleteDelivery;
use App\Actions\Deliveries\MarkOrderPickedUp;
use App\Http\Controllers\Controller;
use App\Http\Requests\Deliveries\AcceptDeliveryRequest;
use App\Http\Requests\Deliveries\UpdateDeliveryRequest;
use App\Http\Requests\Drivers\UpdateDriverAvailabilityRequest;
use App\Http\Resources\DeliveryResource;
use App\Http\Resources\OrderResource;
use App\Models\Delivery;
use App\Models\Order;
use App\Services\DriverMatchingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class DriverDeliveryController extends Controller
{
    public function availableOrders(
        Request $request,
        DriverMatchingService $driverMatchingService,
    ): AnonymousResourceCollection {
        Gate::authorize('viewAvailable', Delivery::class);

        return OrderResource::collection(
            $driverMatchingService->availableOrdersFor($request->user()),
        );
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAvailable', Delivery::class);

        return DeliveryResource::collection(
            $request->user()
                ->deliveries()
                ->with('order.items', 'order.payment')
                ->orderByDesc('id')
                ->paginate(),
        );
    }

    public function accept(
        AcceptDeliveryRequest $request,
        Order $order,
        AcceptDelivery $acceptDelivery,
    ): DeliveryResource {
        return new DeliveryResource(
            $acceptDelivery->handle($order, $request->user()),
        );
    }

    public function markPickedUp(
        UpdateDeliveryRequest $request,
        Delivery $delivery,
        MarkOrderPickedUp $markOrderPickedUp,
    ): DeliveryResource {
        return new DeliveryResource(
            $markOrderPickedUp->handle($delivery, $request->user()),
        );
    }

    public function complete(
        UpdateDeliveryRequest $request,
        Delivery $delivery,
        CompleteDelivery $completeDelivery,
    ): DeliveryResource {
        return new DeliveryResource(
            $completeDelivery->handle($delivery, $request->user()),
        );
    }

    public function updateAvailability(
        UpdateDriverAvailabilityRequest $request,
    ): JsonResponse {
        $driver = $request->user();

        $driver->update($request->validated());

        return response()->json([
            'data' => [
                'is_available_for_delivery' => $driver->is_available_for_delivery,
                'latitude' => $driver->latitude,
                'longitude' => $driver->longitude,
            ],
        ]);
    }
}
