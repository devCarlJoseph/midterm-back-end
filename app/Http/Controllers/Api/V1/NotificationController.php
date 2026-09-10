<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class NotificationController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        return NotificationResource::collection(
            $request->user()
                ->notifications()
                ->latest()
                ->paginate(),
        );
    }

    public function markAsRead(
        Request $request,
        string $notification,
    ): JsonResponse {
        $userNotification = $request->user()
            ->notifications()
            ->findOrFail($notification);

        $userNotification->markAsRead();

        return response()->json([
            'data' => new NotificationResource($userNotification->refresh()),
        ]);
    }
}
