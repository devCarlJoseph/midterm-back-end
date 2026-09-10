<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Addresses\StoreAddressRequest;
use App\Http\Requests\Addresses\UpdateAddressRequest;
use App\Http\Resources\AddressResource;
use App\Models\Address;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class AddressController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        return AddressResource::collection(
            $request->user()
                ->addresses()
                ->orderByDesc('is_default')
                ->orderByDesc('id')
                ->get(),
        );
    }

    public function store(StoreAddressRequest $request): JsonResponse
    {
        $address = DB::transaction(function () use ($request): Address {
            $user = $request->user();
            $data = $request->validated();

            if (($data['is_default'] ?? false) === true) {
                $user->addresses()->update(['is_default' => false]);
            }

            $address = $user->addresses()->create($data);

            if ($user->addresses()->count() === 1) {
                $address->update(['is_default' => true]);
            }

            return $address;
        });

        return (new AddressResource($address))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function update(
        UpdateAddressRequest $request,
        Address $address,
    ): AddressResource {
        DB::transaction(function () use ($request, $address): void {
            $data = $request->validated();

            if (($data['is_default'] ?? false) === true) {
                $address->user->addresses()
                    ->whereKeyNot($address)
                    ->update(['is_default' => false]);
            }

            $address->update($data);
        });

        return new AddressResource($address->refresh());
    }

    public function destroy(
        UpdateAddressRequest $request,
        Address $address,
    ): Response {
        $address->delete();

        return response()->noContent();
    }
}
