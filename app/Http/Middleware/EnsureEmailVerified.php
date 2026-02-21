<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureEmailVerified
{
    /**
     * Reject requests from users who have not verified their email.
     *
     * Usage: ->middleware('verified')
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && !$user->hasVerifiedEmail()) {
            return response()->json([
                'success' => false,
                'message' => 'Your email address is not verified.',
                'data'    => null,
                'errors'  => ['email' => ['Please verify your email address before accessing this resource.']],
            ], 403);
        }

        return $next($request);
    }
}
