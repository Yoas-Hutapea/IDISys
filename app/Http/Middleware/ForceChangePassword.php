<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ForceChangePassword
{
    /**
     * If the user is still on the default password, redirect to the change password page.
     * Except when already on the change password page or logging out.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            return $next($request);
        }

        if (! $request->session()->get('must_change_password', false)) {
            return $next($request);
        }

        if ($request->routeIs('account.password.change') || $request->routeIs('account.password.change.submit') || $request->routeIs('api.auth.logout')) {
            return $next($request);
        }

        return redirect()->route('account.password.change');
    }
}
