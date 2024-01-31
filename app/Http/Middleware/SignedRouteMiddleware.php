<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SignedRouteMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle($request, Closure $next)
    {
        $route = $request->route();
        $signature = $request->query('firma');
        if (!$signature) {
            $signature = $this->generateSignature($route->getName());
            return redirect()->to($this->addSignatureToUrl($request->fullUrl(), $signature));
        }
        if ($this->isValidSignature($route->getName(), $signature)) {
            return $next($request);
        }
        return redirect()->route('error.invalid_signature');
    }

    private function isValidSignature($routeName, $signature)
    {
        $secretKey = config('app.secret_key');
        $expectedSignature = hash('sha256', $routeName . $secretKey);
        return $signature === $expectedSignature;
    }

    private function generateSignature($routeName)
    {
        $secretKey = config('app.secret_key');
        return hash('sha256', $routeName . $secretKey);
    }

    private function addSignatureToUrl($url, $signature)
    {
        return $url . (parse_url($url, PHP_URL_QUERY) ? '&' : '?') . 'firma=' . $signature;
    }
}
