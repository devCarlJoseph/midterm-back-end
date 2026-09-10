<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Services\Auth\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function register(
        RegisterRequest $request,
        AuthService $authService,
    ): JsonResponse {
        $result = $authService->register($request->validated());

        return response()->json([
            'message' => 'Registration successful',
            'data' => [
                'user' => new UserResource($result['user']),
                'token' => $result['token'],
                'token_type' => 'Bearer',
            ],
        ], 201);
    }

    public function login(
        LoginRequest $request,
        AuthService $authService,
    ): JsonResponse {
        $result = $authService->login($request->validated());

        return response()->json([
            'message' => 'Login successful',
            'data' => [
                'user' => new UserResource($result['user']),
                'token' => $result['token'],
                'token_type' => 'Bearer',
            ],
        ]);
    }

    public function logout(Request $request, AuthService $authService): JsonResponse
    {
        $user = $request->user();

        $authService->logout($user);

        return response()->json([
            'message' => 'Logout successful.',
        ]);
    }

    public function me(Request $request): UserResource
    {
        $user = $request->user();

        return new UserResource($user);
    }
}
