<?php

namespace App\Http\Controllers\Api;

use App\Models\ExternalSystem;
use App\Services\ExternalSystemService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Hash;

class ExternalSystemAuth extends Controller
{
    public function __construct(
        private ExternalSystemService $externalSystemService
    ) {}

    /**
     * External system login
     *
     * Exchange `client_id` and `client_secret` for a Sanctum Bearer token. Use the token on all other API routes.
     *
     * @group Authentication
     *
     * @unauthenticated
     *
     * @bodyParam client_id string required Your external system client identifier. Example: acme-dashboard
     * @bodyParam client_secret string required Your client secret (plain text; compared to the hashed value stored for the system). Example: your-secret-value
     *
     * @response 200 scenario="success" {
     *   "success": "Token Generated",
     *   "data": {
     *     "token": "1|abcdefghijklmnopqrstuvwxyz0123456789",
     *     "token_type": "Bearer"
     *   }
     * }
     * @response 401 scenario="invalid_credentials" {
     *   "message": "Invalid client credentials"
     * }
     * @response 401 scenario="deactivated" {
     *   "message": "System deactivated"
     * }
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'client_id' => ['required', 'string'],
            'client_secret' => ['required', 'string'],
        ]);

        $externalSystem = ExternalSystem::firstWhere('client_id', $request->client_id);

        if (! $externalSystem || ! Hash::check($request->client_secret, $externalSystem->client_secret)) {
            return response()->json(['message' => 'Invalid client credentials'], 401);
        }

        if (! $externalSystem->is_active) {
            return response()->json(['message' => 'System deactivated'], 401);
        }

        $token = $this->externalSystemService->generateAccessToken($externalSystem);

        return response()->json([
            'success' => 'Token Generated',
            'data' => [
                'token' => $token,
                'token_type' => 'Bearer',
            ],
        ]);
    }

    /**
     * Revoke access tokens
     *
     * Invalidates all personal access tokens for the authenticated external system (current Bearer token’s owner).
     *
     * @group Authentication
     *
     * @authenticated
     *
     * @response 204 scenario="success"
     */
    public function revoke(Request $request)
    {
        $this->externalSystemService->revokeAllTokens($request->user());

        return response()->noContent();
    }
}
