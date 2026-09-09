<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Orders\AcceptOrder;
use App\Actions\Orders\MarkOrderPreparing;
use App\Actions\Orders\MarkOrderReady;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Orders\UpdateMerchantOrderStatusRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class MerchantOrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewMerchantOrders', Order::class);

        return OrderResource::collection(
            Order::query()
                ->whereHas('store.users', function ($query) use ($request): void {
                    $query->whereKey($request->user());
                })
                ->with('items', 'payment', 'user')
                ->orderByDesc('id')
                ->paginate(),
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
    public function show(Order $order): OrderResource
    {
        Gate::authorize('manageFulfillment', $order);

        return new OrderResource($order->load('items', 'payment', 'user'));
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

    public function accept(
        UpdateMerchantOrderStatusRequest $request,
        Order $order,
        AcceptOrder $acceptOrder,
    ): OrderResource {
        return new OrderResource(
            $acceptOrder->handle($order, $request->user()),
        );
    }

    public function markPreparing(
        UpdateMerchantOrderStatusRequest $request,
        Order $order,
        MarkOrderPreparing $markOrderPreparing,
    ): OrderResource {
        return new OrderResource(
            $markOrderPreparing->handle($order, $request->user()),
        );
    }

    public function markReady(
        UpdateMerchantOrderStatusRequest $request,
        Order $order,
        MarkOrderReady $markOrderReady,
    ): OrderResource {
        return new OrderResource(
            $markOrderReady->handle($order, $request->user()),
        );
    }
}
