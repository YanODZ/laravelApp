<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
class Controller extends BaseController
{
    public function showWelcome(Request $request)
    {
        if ($request->hasValidSignature()) {
            if (Auth::check()) {
                $token = $request->input('token');
                $rol = $request->input('rol');
                return view('welcome', ['token' => $token, 'rol'=>$rol]);
            } else {
                return redirect()->route('login');
            }
        } else {
            abort(403, 'Firma no válida');
        }
    }

    protected function goToWelcome(Request $request)
    {
        $rol = $request->input('rol');
        $token = $request->input('token');
        $expiracion = now()->addMinutes(10);
        $urlFirmada = URL::temporarySignedRoute('welcome', $expiracion, ['token' => $token, 'rol'=>$rol]);
        return redirect()->to($urlFirmada);
    }
}
