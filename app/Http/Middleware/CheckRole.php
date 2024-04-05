<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $role
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        try {
            // Intenta obtener el usuario del token JWT
            $user = JWTAuth::parseToken()->authenticate();
        } catch (\Exception $e) {
            // Si hay algún error al analizar el token, responde con un error de autenticación
            abort(403, 'Error Inesperado');
        }

        // Verifica si el usuario tiene el rol de "admin" o "coordinador"
        if ($user->hasRole('admin') || $user->hasRole('coordinador')) {
            // Si el usuario tiene el rol adecuado, permite que continúe con la solicitud
            return $next($request);
        } else {
            // Si el usuario no tiene los roles adecuados, responde con un error 403
            abort(403, 'No tienes el rol para hacer esto');
        }
    }
}
