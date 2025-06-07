<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckUserRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle($request, Closure $next, ...$roles)
    {
        $userRole = strtolower(auth()->user()->type->name_type_user);
        if (!in_array($userRole, array_map('strtolower', $roles))) {
            abort(403, 'Accès non autorisé');
        }
        return $next($request);
    }

}
