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
use PragmaRX\Google2FA\Google2FA;
use Illuminate\Support\Facades\Session;


class AuthController extends Controller
{
    public function showLoginForm()
    {
        return view('login');
    }

    public function showRegisterForm(){
        return view('register');
    }

    public function login(Request $request)
    {
        try {
            $credentials = $request->only('correo', 'contraseña');
            
            $user = User::where('correo', $credentials['correo'])->first();
            
            if (!$user || !Hash::check($credentials['contraseña'], $user->contraseña)) {
                return response()->json(['auth' => 'Verifica tus credencialess']);
            }
    
            if ($user->isAdmin()) {
                $google2fa = app(Google2FA::class);
            
                if (!$request->google2fa_code) {
                    return response()->json(['auth' => 'Código de autenticación en dos pasos requerido']);
                }
            
                if (!$google2fa->verifyKey($user->google2fa_secret, $request->input('google2fa_code'))) {
                    return response()->json(['auth' => 'Código de autenticación en dos pasos incorrecto']);
                }
            }
            
            $token = JWTAuth::fromUser($user);
            return $this->respondWithToken($token);
    
        } catch (\Exception $e) {
            return response()->json(['auth' => 'Ha ocurrido un error. Por favor, inténtalo de nuevo.']);
        }
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
                return response()->json(['auth' => implode(' ', $validator->errors()->all())]);
            }

            $role = User::count() === 0 ? 'admin' : 'user';

            $user = User::create([
                'nombre' => $request->input('nombre'),
                'correo' => $request->input('correo'),
                'contraseña' => Hash::make($request->input('contraseña')),
                'role' => $role,
            ]);

            if ($user->isAdmin()) {
                $google2fa = app(Google2FA::class);
                $user->google2fa_secret = $google2fa->generateSecretKey();
                $user->save();
            }

            return response()->json(['message' => 'Usuario registrado correctamente', 'factor' => $user->google2fa_secret,]);
        } catch (\Exception $e) {
            return response()->json(['auth' => 'Algo salió mal con el registro, contacta con la administración']);
        }
    }
}
