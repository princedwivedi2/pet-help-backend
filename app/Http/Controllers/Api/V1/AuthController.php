<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\ChangePasswordRequest;
use App\Http\Requests\Api\V1\Auth\ForgotPasswordRequest;
use App\Http\Requests\Api\V1\Auth\LoginRequest;
use App\Http\Requests\Api\V1\Auth\RegisterRequest;
use App\Http\Requests\Api\V1\Auth\ResetPasswordRequest;
use App\Http\Requests\Api\V1\Auth\UpdateProfileRequest;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;

class AuthController extends Controller
{
    use ApiResponse;

    /**
     * Register a new user
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        // Password is hashed automatically via User model 'hashed' cast — no Hash::make needed
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => $request->password,
        ]);

        // Auto-verify email for development/testing
        // TODO: Remove this in production and uncomment the event below
        $user->markEmailAsVerified();

        // Fire Registered event (sends verification email)
        // event(new Registered($user));

        $token = $user->createToken('mobile-app')->plainTextToken;

        return $this->created('User registered successfully.', [
            'user' => $user,
            'token' => $token,
        ]);
    }

    /**
     * Login user
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            Log::channel('stack')->warning('Login failed', [
                'email' => $request->email,
                'ip' => $request->ip(),
            ]);
            return $this->unauthorized('Invalid credentials', [
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Block non-approved vets from logging in
        if ($user->isVet()) {
            $vetProfile = $user->vetProfile;

            if (!$vetProfile || !$vetProfile->isApproved()) {
                $status = $vetProfile?->vet_status ?? 'pending';
                $messages = [
                    'pending'   => 'Your vet profile is pending admin approval.',
                    'rejected'  => 'Your vet profile has been rejected. Please contact support.',
                    'suspended' => 'Your vet account has been suspended. Please contact support.',
                ];

                return $this->forbidden($messages[$status] ?? 'Your vet account is not active.');
            }
        }

        // Auto-verify email for development/testing
        // TODO: Remove this in production
        if (!$user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
        }

        $token = $user->createToken('mobile-app')->plainTextToken;

        return $this->success('Login successful', [
            'user' => $user,
            'token' => $token,
        ]);
    }

    /**
     * Get authenticated user
     */
    public function me(Request $request): JsonResponse
    {
        return $this->success('User retrieved successfully', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Logout user
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return $this->success('Logout successful');
    }

    // ─── Email Verification ─────────────────────────────────────────

    /**
     * Resend email verification link.
     * POST /api/v1/auth/email/resend
     */
    public function resendVerification(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return $this->success('Email already verified');
        }

        $user->sendEmailVerificationNotification();

        return $this->success('Verification email sent');
    }

    /**
     * Verify email address via signed URL.
     * GET /api/v1/auth/email/verify/{id}/{hash}
     */
    public function verifyEmail(Request $request, int $id, string $hash): JsonResponse
    {
        $user = User::findOrFail($id);

        if (!hash_equals(sha1($user->getEmailForVerification()), $hash)) {
            return $this->forbidden('Invalid verification link');
        }

        if ($user->hasVerifiedEmail()) {
            return $this->success('Email already verified');
        }

        $user->markEmailAsVerified();

        return $this->success('Email verified successfully');
    }

    // ─── Password Reset ─────────────────────────────────────────────

    /**
     * Send password reset link.
     * POST /api/v1/auth/forgot-password
     */
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $status = Password::sendResetLink($request->only('email'));

        if ($status === Password::RESET_LINK_SENT) {
            return $this->success('Password reset link sent to your email');
        }

        Log::channel('stack')->warning('Password reset failed', [
            'email' => $request->email,
            'status' => $status,
        ]);

        return $this->error('Unable to send reset link', [
            'email' => [__($status)],
        ], 422);
    }

    /**
     * Reset password using token.
     * POST /api/v1/auth/reset-password
     */
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill(['password' => $password])->save();
                $user->tokens()->delete(); // Revoke all tokens
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return $this->success('Password reset successfully');
        }

        return $this->error('Password reset failed', [
            'token' => [__($status)],
        ], 422);
    }

    // ─── Change Password ────────────────────────────────────────────

    /**
     * Change password (requires current password).
     * PUT /api/v1/auth/change-password
     */
    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return $this->validationError('Current password is incorrect', [
                'current_password' => ['The current password does not match.'],
            ]);
        }

        $user->forceFill(['password' => $request->password])->save();

        // Revoke all other tokens
        $user->tokens()->where('id', '!=', $user->currentAccessToken()->id)->delete();

        return $this->success('Password changed successfully');
    }

    // ─── Profile Update ─────────────────────────────────────────────

    /**
     * Update authenticated user's profile.
     * PUT /api/v1/auth/profile
     */
    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validated();

        // Handle avatar upload
        if ($request->hasFile('avatar')) {
            $path = $request->file('avatar')->store('avatars', 'public');
            $data['avatar'] = $path;
        }

        $user->update($data);

        return $this->success('Profile updated successfully', [
            'user' => $user->fresh(),
        ]);
    }
}
