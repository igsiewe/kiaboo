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

        // Si $roles contient une seule chaîne avec des virgules, on la transforme en tableau
        if (count($roles) === 1 && str_contains($roles[0], ',')) {
            $roles = explode(',', $roles[0]);
        }

        $roles = array_map('strtolower', array_map('trim', $roles));

        if (!in_array($userRole, $roles)) {
            abort(403, 'Accès non autorisé');
        }

        return $next($request);
    }


}
