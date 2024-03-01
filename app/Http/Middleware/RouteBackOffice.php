<?php

namespace App\Http\Middleware;

use App\Http\Enums\UserRolesEnum;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RouteBackOffice
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            if (Auth::user()->type_user_id == UserRolesEnum::BACKOFFICE->value || Auth::user()->type_user_id == UserRolesEnum::SUPADMIN->value) {
                return $next($request);
            } else {
                return redirect()->back()->withErrors('Unauthorized User');
            }
        } else {
            return redirect()->back()->withErrors('Unauthorized User');
        }
    }
}
