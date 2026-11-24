<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/** Role-based access: `role:player` or `role:super_admin,admin`. */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles)
    {
        $user = $request->user();

        if (! $user || ! in_array($user->role, $roles, true) || ! $user->is_active) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $user && ! $user->is_active
                    ? 'Your account has been deactivated. Please contact the organiser.'
                    : 'You are not allowed to access this resource.'], 403);
            }
            if ($user) {
                Auth::guard('web')->logout();
                $request->session()->invalidate();
            }

            return redirect()->route('admin.login')->withErrors(['email' => 'Admin access only.']);
        }

        return $next($request);
    }
}
