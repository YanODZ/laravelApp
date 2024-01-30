<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\URL;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        $urlFirmada = URL::temporarySignedRoute('login', now()->addMinutes(30));
        return view('login', ['urlFirmada' => $urlFirmada]);
    }

    public function showRegisterForm(){
        $urlFirmada = URL::temporarySignedRoute('register', now()->addMinutes(30));
        return view('register', ['urlFirmada' => $urlFirmada]);
    }

    public function login(Request $request)
    {
        $credentials = $request->only('correo', 'contraseña');
    
        $user = User::where('correo', $credentials['correo'])->first();
    
        if (!$user || !Hash::check($credentials['contraseña'], $user->contraseña)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    
        $token = JWTAuth::fromUser($user);
        return $this->respondWithToken($token);
    }    
    
    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60,
        ]);
    }

    public function logout()
    {
        try {
            $token = JWTAuth::getToken();
            JWTAuth::parseToken()->invalidate();
    
            return redirect()->route('login')->with('message', 'Sesión cerrada correctamente')->with('token', $token);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al cerrar sesión'], 500);
        }
    }

    public function register(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'nombre' => 'required|string|max:60|unique:users',
                'correo' => 'required|string|email|max:60|unique:users',
                'contraseña' => [
                    'required',
                    'string',
                    'min:8',
                    'regex:/^(?=.*[a-z])(?=.*[A-Z])/',
                ],
            ]);

            if ($validator->fails()) {
                throw new \Exception('Error de validación: ' . implode(' ', $validator->errors()->all()));
            }

            $role = User::count() === 0 ? 'admin' : 'user';

            $user = User::create([
                'nombre' => $request->input('nombre'),
                'correo' => $request->input('correo'),
                'contraseña' => Hash::make($request->input('contraseña')),
                'role' => $role,
            ]);

            return response()->json(['message' => 'Usuario registrado correctamente']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}
