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
        if (Auth::check()) {
            $token = $request->input('token');
            $urlFirmada = URL::temporarySignedRoute('welcome', now()->addMinutes(30));
            return view('welcome', ['urlFirmada' => $urlFirmada, 'token' => $token]);
            //return view('welcome', ['token' => $token]);
        } else {
            return redirect()->route('login');
        }
    }
}
