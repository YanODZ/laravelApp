<?php

namespace App\Http\Middleware;

use Closure;

class IpRestrictionMiddleware
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
        $allowed_ips = [
            '10.124.1.23',
            '10.124.1.24',
            // Agrega más IPs permitidas según tus necesidades
        ];

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
