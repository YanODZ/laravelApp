<?php

namespace App\Http\Middleware;

use Closure;

class IpRestrictionMovilMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // Lista de direcciones IP permitidas
        $allowed_ips = explode(',', env('APPMOVIL_ALLOWED_IPS'));

        // Obtiene la dirección IP del cliente
        $client_ip = $request->ip();

        // Verifica si la IP del cliente está en la lista de IPs permitidas
        if (!in_array($client_ip, $allowed_ips)) {
            // Si la IP no está permitida, redirige o muestra un mensaje de error, según tu preferencia
            abort(403, 'Acceso denegado.');
        }

        // Si la IP está permitida, continúa con la solicitud normalmente
        return $next($request);
    }
}
