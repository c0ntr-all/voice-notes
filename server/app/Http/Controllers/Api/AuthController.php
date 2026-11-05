<?php

namespace App\Http\Controllers\Api;

use App\Application\Auth\AuthenticatedUser;
use App\Application\Auth\LoginUser;
use App\Application\Auth\LogoutUser;
use App\Application\Auth\RegisterUser;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AuthController extends ApiController
{
    public function register(RegisterRequest $request, RegisterUser $registerUser): JsonResponse
    {
        return $this->tokenResponse($registerUser->handle($request->toData()), 201);
    }

    public function login(LoginRequest $request, LoginUser $loginUser): JsonResponse
    {
        return $this->tokenResponse($loginUser->handle($request->toData()), 200);
    }

    public function me(Request $request): UserResource
    {
        return UserResource::make($this->user($request));
    }

    public function logout(Request $request, LogoutUser $logoutUser): Response
    {
        $logoutUser->handle($this->user($request));

        return response()->noContent();
    }

    private function tokenResponse(AuthenticatedUser $session, int $status): JsonResponse
    {
        return response()->json([
            'token' => $session->token,
            'token_type' => 'Bearer',
            'user' => UserResource::make($session->user)->resolve(),
        ], $status);
    }
}
