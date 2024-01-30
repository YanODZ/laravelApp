<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Auth;

class JwtRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  ...$roles
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle($request, Closure $next, $role = null)
    {
        try {
            $user = JWTAuth::user();

            if (!$user) {
                throw new \Exception('No hay usuario autenticado');
            }

            if ($role && $user->role !== $role) {
                return response()->json(['error' => 'Acceso no autorizado'], 403);
            }

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 401);
        }

        return $next($request);
    }
}
