<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

/**
 * @group Authentication
 *
 * APIs for managing user registration and login.
 */
class AuthController extends Controller
{
    /**
     * Register a new user
     *
     * Creates a new customer account and returns an access token.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create([
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'password' => Hash::make($request->validated('password')),
        ]);

        $user->assignRole('customer');

        $token = $user->createToken('auth_token')->plainTextToken;

        return $this->tokenResponse($token, Response::HTTP_CREATED);
    }

    /**
     * Login
     *
     * Authenticates a user and returns an access token.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        if (! Auth::attempt($request->only('email', 'password'))) {
            throw ApiException::invalidCredentials();
        }

        $user = User::where('email', $request->validated('email'))->firstOrFail();
        $token = $user->createToken('auth_token')->plainTextToken;

        return $this->tokenResponse($token);
    }

    /**
     * Logout
     *
     * Revokes the current access token.
     */
    public function logout(Request $request): Response
    {
        $request->user()->currentAccessToken()->delete();

        return response()->noContent();
    }

    private function tokenResponse(string $token, int $status = Response::HTTP_OK): JsonResponse
    {
        return response()->json([
            'data' => [
                'access_token' => $token,
                'token_type' => 'Bearer',
            ],
        ], $status);
    }
}
