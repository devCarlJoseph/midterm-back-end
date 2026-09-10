<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Orders\PlaceOrder;
use App\Http\Controllers\Controller;
use App\Http\Requests\Orders\PlaceOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Gate;
use App\Actions\Orders\CancelOrder;
use App\Http\Requests\Orders\CancelOrderRequest;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        return OrderResource::collection(
            Order::query()
                ->whereBelongsTo($request->user())
                ->with('items', 'payment')
                ->orderByDesc('id')
                ->paginate(),
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(
        PlaceOrderRequest $request,
        PlaceOrder $placeOrder,
    ): JsonResponse {
        $order = $placeOrder->handle(
            $request->user(),
            $request->integer('address_id'),
            $request->integer('delivery_option_id'),
            $request->enum('payment_method', \App\Enums\PaymentMethod::class),
        );

        return (new OrderResource($order))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     */
    public function show(Order $order): OrderResource
    {
        Gate::authorize('view', $order);

        return new OrderResource($order->load('items', 'payment'));
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

    public function cancel(
        CancelOrderRequest $request,
        Order $order,
        CancelOrder $cancelOrder,
    ): OrderResource {
        return new OrderResource(
            $cancelOrder->handle($order, $request->user()),
        );
    }
}
