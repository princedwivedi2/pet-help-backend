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
use Illuminate\Database\QueryException;

class AuthController extends Controller
{
    use ApiResponse;

    /**
     * Register a new user
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        try {
            // Password is hashed automatically via User model 'hashed' cast — no Hash::make needed
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => $request->password,
            ]);
        } catch (QueryException $e) {
            report($e);
            return $this->error('Registration failed. This email may already be in use.', null, 409);
        }

        // Fire Registered event (sends verification email)
        event(new Registered($user));

        $token = $user->createToken('mobile-app')->plainTextToken;

        return $this->created('User registered successfully. Please verify your email.', [
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

        $loginNotice = null;
        $vetMeta = [];

        if ($user->isVet()) {
            $vetProfile = $user->vetProfile;

            if (!$vetProfile) {
                return $this->forbidden('Vet profile not found. Please complete onboarding.');
            }

            $vetMeta = [
                'vet_status' => $vetProfile->vet_status,
                'verification_status' => $vetProfile->verification_status ?? $vetProfile->vet_status,
            ];

            // Allow login for pending vets so they can finish onboarding, but surface a clear notice
            if (!$vetProfile->isApproved()) {
                $loginNotice = 'Vet account is not approved yet. Appointment actions stay disabled until admin approval.';
            }
        }

        $user->forceFill(['last_login_at' => now()])->save();

        $token = $user->createToken('mobile-app')->plainTextToken;

        $payload = [
            'user' => $user,
            'token' => $token,
        ];

        if (!empty($vetMeta)) {
            $payload['vet'] = $vetMeta;
        }

        if ($loginNotice) {
            $payload['login_notice'] = $loginNotice;
        }

        return $this->success('Login successful', $payload);
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
        $user = $request->user();
        $currentToken = $user->currentAccessToken();

        if ($currentToken) {
            $currentToken->delete();
            return $this->success('Logout successful');
        }

        $user->tokens()->delete();

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

        // Revoke all other tokens (keep current session)
        $currentToken = $user->currentAccessToken();
        if ($currentToken) {
            $user->tokens()->where('id', '!=', $currentToken->id)->delete();
        } else {
            $user->tokens()->delete();
        }

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

        // CRIT-03: Explicitly strip role and other sensitive fields from profile updates
        unset($data['role'], $data['email_verified_at'], $data['password']);

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

    /**
     * Delete authenticated user's account.
     * DELETE /api/v1/auth/account
     */
    public function deleteAccount(Request $request): JsonResponse
    {
        $request->validate([
            'password' => 'required|string',
        ]);

        $user = $request->user();

        if (!Hash::check($request->password, $user->password)) {
            return $this->validationError('Password is incorrect', [
                'password' => ['The provided password does not match.'],
            ]);
        }

        // MED-04: Block deletion if user has active appointments, SOS, or payments
        $activeStatuses = ['pending', 'accepted', 'confirmed', 'in_progress'];

        $activeAppointments = $user->appointments()
            ->whereIn('status', $activeStatuses)
            ->exists();
        if ($activeAppointments) {
            return $this->error('Cannot delete account with active appointments. Please cancel or complete them first.', null, 422);
        }

        // Also check appointments where user is the assigned vet
        if ($user->isVet() && $user->vetProfile) {
            $vetActiveAppointments = \App\Models\Appointment::where('vet_profile_id', $user->vetProfile->id)
                ->whereIn('status', $activeStatuses)
                ->exists();
            if ($vetActiveAppointments) {
                return $this->error('Cannot delete account with active vet appointments. Please cancel or complete them first.', null, 422);
            }

            $vetActiveSos = \App\Models\SosRequest::where('assigned_vet_id', $user->vetProfile->id)
                ->whereIn('status', ['sos_pending', 'sos_accepted', 'vet_on_the_way', 'arrived', 'sos_in_progress', 'pending', 'acknowledged', 'in_progress'])
                ->exists();
            if ($vetActiveSos) {
                return $this->error('Cannot delete account with active SOS assignments. Please complete them first.', null, 422);
            }
        }

        $activeSos = $user->sosRequests()
            ->whereIn('status', ['sos_pending', 'sos_accepted', 'vet_on_the_way', 'arrived', 'sos_in_progress', 'pending', 'acknowledged', 'in_progress'])
            ->exists();
        if ($activeSos) {
            return $this->error('Cannot delete account with active SOS requests. Please cancel or complete them first.', null, 422);
        }

        // Revoke all tokens
        $user->tokens()->delete();

        // Delete the user account
        $user->delete();

        return $this->success('Account deleted successfully');
    }
}
