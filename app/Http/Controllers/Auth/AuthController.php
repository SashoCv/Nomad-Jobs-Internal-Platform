<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{

    /**
     * Login user with Sanctum session-based authentication
     *
     * @param LoginRequest $request
     * @return JsonResponse
     */
    public function login(LoginRequest $request): JsonResponse
    {
        try {
            // Attempt authentication with session
            if (!Auth::attempt([
                'email' => $request->validated('email'),
                'password' => $request->validated('password')
            ])) {
                Log::warning('Login attempt failed', [
                    'email' => $request->validated('email'),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Invalid credentials',
                ], 401);
            }

            // Regenerate session to prevent fixation
            $request->session()->regenerate();

            // Get authenticated user with role and permissions
            $user = Auth::user();
            $user->load(['role.permissions', 'company']);

            // Store user data in session for fast retrieval
            $userData = [
                'id' => $user->id,
                'firstName' => $user->firstName,
                'lastName' => $user->lastName,
                'email' => $user->email,
                'role' => $user->role->toArray(),
                'company' => $user->company?->toArray(),
            ];

            $request->session()->put('user_data', $userData);

            Log::info('User logged in successfully', [
                'user_id' => $user->id,
                'email' => $user->email,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Login successful',
                'data' => $userData,
            ]);

        } catch (\Exception $e) {
            Log::error('Login error', [
                'email' => $request->validated('email'),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred during login',
            ], 500);
        }
    }

    /**
     * Logout user and invalidate session
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();

            if ($user) {
                Log::info('User logged out successfully', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                ]);
            }

            // Logout user from web guard (Sanctum uses 'web' guard for session auth)
            Auth::guard('web')->logout();

            // Invalidate session (this internally calls flush() and migrate(true))
            // This properly removes the old session from Redis
            $request->session()->invalidate();

            // Regenerate CSRF token for security
            $request->session()->regenerateToken();

            return response()->json([
                'success' => true,
                'message' => 'Logged out successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('Logout error', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Logged out successfully',
            ]);
        }
    }

    /**
     * Get current authenticated user from session (fast) or database (fallback)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function user(Request $request): JsonResponse
    {
        // Check impersonation status
        $isImpersonating = $request->session()->has('impersonating');
        $originalUserData = $isImpersonating
            ? $request->session()->get('original_user_data')
            : null;

        // First, check if user data exists in session (Redis - very fast!)
        $userData = $request->session()->get('user_data');

        if ($userData) {
            // Return from session - no database query needed!
            return response()->json([
                'success' => true,
                'data' => $userData,
                'isImpersonating' => $isImpersonating,
                'originalUser' => $originalUserData,
            ]);
        }

        // Fallback: fetch from database if not in session
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated',
            ], 401);
        }

        $user->load(['role.permissions', 'company']);

        // Store in session for next time
        $userData = [
            'id' => $user->id,
            'firstName' => $user->firstName,
            'lastName' => $user->lastName,
            'email' => $user->email,
            'role' => $user->role->toArray(),
            'company' => $user->company?->toArray(),
        ];

        $request->session()->put('user_data', $userData);

        return response()->json([
            'success' => true,
            'data' => $userData,
            'isImpersonating' => $isImpersonating,
            'originalUser' => $originalUserData,
        ]);
    }

}
