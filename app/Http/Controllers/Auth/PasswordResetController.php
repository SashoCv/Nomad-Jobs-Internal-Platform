<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Jobs\SendPasswordResetEmailJob;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PasswordResetController extends Controller
{
    /**
     * Token expiration time in hours
     */
    private const TOKEN_EXPIRY_HOURS = 24;

    /**
     * Handle forgot password request
     * Generates a reset token and dispatches email job
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        try {
            $user = User::where('email', $request->email)->first();

            // Always return success to prevent email enumeration attacks
            if (!$user) {
                Log::info('Password reset requested for non-existent email', [
                    'email' => $request->email,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'If an account with that email exists, we have sent password reset instructions.',
                ]);
            }

            // Generate a secure random token
            $token = Str::random(64);

            // Delete any existing tokens for this email
            DB::table('password_resets')->where('email', $request->email)->delete();

            // Store the hashed token
            DB::table('password_resets')->insert([
                'email' => $request->email,
                'token' => Hash::make($token),
                'created_at' => Carbon::now(),
            ]);

            // Build the reset URL
            $resetUrl = config('app.frontend_url', 'https://nomadjobs.cloud') . '/reset-password?token=' . $token . '&email=' . urlencode($request->email);

            // Dispatch the email job
            SendPasswordResetEmailJob::dispatch(
                $request->email,
                $resetUrl,
                $user->firstName
            );

            Log::info('Password reset email dispatched', [
                'email' => $request->email,
                'user_id' => $user->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'If an account with that email exists, we have sent password reset instructions.',
            ]);

        } catch (\Exception $e) {
            Log::error('Forgot password error', [
                'email' => $request->email,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred. Please try again later.',
            ], 500);
        }
    }

    /**
     * Validate a password reset token
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function validateToken(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'token' => 'required|string',
        ]);

        try {
            $resetRecord = DB::table('password_resets')
                ->where('email', $request->email)
                ->first();

            if (!$resetRecord) {
                return response()->json([
                    'success' => true,
                    'valid' => false,
                    'message' => 'Invalid or expired reset link.',
                ]);
            }

            // Check if token has expired (24 hours)
            $createdAt = Carbon::parse($resetRecord->created_at);
            if ($createdAt->addHours(self::TOKEN_EXPIRY_HOURS)->isPast()) {
                // Clean up expired token
                DB::table('password_resets')->where('email', $request->email)->delete();

                return response()->json([
                    'success' => true,
                    'valid' => false,
                    'message' => 'This reset link has expired. Please request a new one.',
                ]);
            }

            // Verify the token hash
            if (!Hash::check($request->token, $resetRecord->token)) {
                return response()->json([
                    'success' => true,
                    'valid' => false,
                    'message' => 'Invalid reset link.',
                ]);
            }

            return response()->json([
                'success' => true,
                'valid' => true,
                'message' => 'Token is valid.',
            ]);

        } catch (\Exception $e) {
            Log::error('Token validation error', [
                'email' => $request->email,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'valid' => false,
                'message' => 'An error occurred while validating the token.',
            ], 500);
        }
    }

    /**
     * Reset the user's password
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'token' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        try {
            $resetRecord = DB::table('password_resets')
                ->where('email', $request->email)
                ->first();

            if (!$resetRecord) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid or expired reset link.',
                ], 400);
            }

            // Check if token has expired
            $createdAt = Carbon::parse($resetRecord->created_at);
            if ($createdAt->addHours(self::TOKEN_EXPIRY_HOURS)->isPast()) {
                DB::table('password_resets')->where('email', $request->email)->delete();

                return response()->json([
                    'success' => false,
                    'message' => 'This reset link has expired. Please request a new one.',
                ], 400);
            }

            // Verify the token hash
            if (!Hash::check($request->token, $resetRecord->token)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid reset link.',
                ], 400);
            }

            // Find and update the user
            $user = User::where('email', $request->email)->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found.',
                ], 404);
            }

            // Update password (bcrypt only, no plain text storage)
            $user->password = bcrypt($request->password);
            $user->save();

            // Delete the used token
            DB::table('password_resets')->where('email', $request->email)->delete();

            Log::info('Password reset successful', [
                'user_id' => $user->id,
                'email' => $user->email,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Password has been reset successfully. You can now log in.',
            ]);

        } catch (\Exception $e) {
            Log::error('Password reset error', [
                'email' => $request->email,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while resetting your password.',
            ], 500);
        }
    }
}
