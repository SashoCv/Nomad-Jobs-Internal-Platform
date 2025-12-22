<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ImpersonationController extends Controller
{
    /**
     * Start impersonating a user
     *
     * @param Request $request
     * @param User $user The user to impersonate
     * @return JsonResponse
     */
    public function start(Request $request, User $user): JsonResponse
    {
        $currentUser = Auth::user();

        // Check if user has permission to impersonate
        if (!$currentUser->hasPermission(Permission::USERS_IMPERSONATE)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to impersonate users.',
            ], 403);
        }

        // Cannot impersonate yourself
        if ($currentUser->id === $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot impersonate yourself.',
            ], 400);
        }

        // Cannot impersonate GENERAL_MANAGER (admin) users
        if ($user->role_id === Role::GENERAL_MANAGER) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot impersonate admin users.',
            ], 403);
        }

        // Cannot start impersonation if already impersonating
        if ($request->session()->has('impersonating')) {
            return response()->json([
                'success' => false,
                'message' => 'You are already impersonating a user. Stop the current impersonation first.',
            ], 400);
        }

        // Store original user ID and data in session
        $request->session()->put('impersonating', true);
        $request->session()->put('original_user_id', $currentUser->id);
        $request->session()->put('original_user_data', [
            'id' => $currentUser->id,
            'firstName' => $currentUser->firstName,
            'lastName' => $currentUser->lastName,
            'email' => $currentUser->email,
        ]);

        // Log the impersonation
        Log::info('User impersonation started', [
            'original_user_id' => $currentUser->id,
            'original_user_email' => $currentUser->email,
            'impersonated_user_id' => $user->id,
            'impersonated_user_email' => $user->email,
        ]);

        // Login as the target user using web guard (session-based)
        Auth::guard('web')->login($user);

        // Load relationships
        $user->load(['role.permissions', 'company']);

        // Update session with new user data (including impersonation flag)
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
            'message' => 'Now impersonating ' . $user->firstName . ' ' . $user->lastName,
            'data' => $userData,
            'isImpersonating' => true,
            'originalUser' => $request->session()->get('original_user_data'),
        ]);
    }

    /**
     * Stop impersonating and return to original user
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function stop(Request $request): JsonResponse
    {
        // Check if currently impersonating
        if (!$request->session()->has('impersonating')) {
            return response()->json([
                'success' => false,
                'message' => 'You are not currently impersonating anyone.',
            ], 400);
        }

        $originalUserId = $request->session()->get('original_user_id');
        $impersonatedUser = Auth::user();

        // Get the original user
        $originalUser = User::find($originalUserId);

        if (!$originalUser) {
            // Clear impersonation session data
            $request->session()->forget(['impersonating', 'original_user_id', 'original_user_data']);

            return response()->json([
                'success' => false,
                'message' => 'Original user not found.',
            ], 404);
        }

        // Log the end of impersonation
        Log::info('User impersonation ended', [
            'original_user_id' => $originalUserId,
            'impersonated_user_id' => $impersonatedUser?->id,
        ]);

        // Login back as original user using web guard (session-based)
        Auth::guard('web')->login($originalUser);

        // Load relationships
        $originalUser->load(['role.permissions', 'company']);

        // Clear impersonation session data
        $request->session()->forget(['impersonating', 'original_user_id', 'original_user_data']);

        // Update session with original user data
        $userData = [
            'id' => $originalUser->id,
            'firstName' => $originalUser->firstName,
            'lastName' => $originalUser->lastName,
            'email' => $originalUser->email,
            'role' => $originalUser->role->toArray(),
            'company' => $originalUser->company?->toArray(),
        ];

        $request->session()->put('user_data', $userData);

        return response()->json([
            'success' => true,
            'message' => 'Stopped impersonating. Welcome back, ' . $originalUser->firstName . '!',
            'data' => $userData,
            'isImpersonating' => false,
            'originalUser' => null,
        ]);
    }

    /**
     * Get current impersonation status
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function status(Request $request): JsonResponse
    {
        $isImpersonating = $request->session()->has('impersonating');
        $originalUserData = $isImpersonating
            ? $request->session()->get('original_user_data')
            : null;

        return response()->json([
            'success' => true,
            'isImpersonating' => $isImpersonating,
            'originalUser' => $originalUserData,
        ]);
    }
}
