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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Crypt;

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
            $validator = Validator::make($request->all(), [
                'correo' => 'required',
                'g-recaptcha-response' => 'required',
                'contraseña' => [
                    'required',
                    'string',
                    'min:8',
                    'regex:/^(?=.*[a-z])(?=.*[A-Z])/',
                ],
            ]);

            if ($validator->fails()) {
                Log::info('Intento de login:' . $request->correo . ' ' . implode(' ', $validator->errors()->all()) . ' IP:' . $request->getClientIp());
                return redirect()->route('login')->with(['auth' => implode(' ', $validator->errors()->all())]);
            }

            $credentials = $request->only('correo', 'contraseña');
            
            $user = User::where('correo', $credentials['correo'])->first();
            
            if (!$user || !Hash::check($credentials['contraseña'], $user->contraseña)) {
                Log::info('Intento de sesión no válido: ' . $request->correo . ' IP:' . $request->getClientIp());
                return redirect()->route('login')->with(['auth' => 'Verifica tus credencialess']);
            }
    
            if ($user->isAdmin()) {
                $google2fa = app(Google2FA::class);
            
                if (!$request->google2fa_code) {
                    Log::info('Intento de sesión sin código: ' . $user->correo . ' IP:' . $request->getClientIp());
                    return redirect()->route('login')->with(['auth' => 'Código de autenticación en dos pasos requerido']);
                }
                $encryptedSecret = $user->google2fa_secret;
                $decryptedSecret = Crypt::decryptString($encryptedSecret);
                if (!$google2fa->verifyKey($decryptedSecret, $request->input('google2fa_code'))) {
                    Log::info('Intento de sesión con código: ' . $user->correo . ' IP:' . $request->getClientIp());
                    return redirect()->route('login')->with(['auth' => 'Código de autenticación en dos pasos incorrecto']);
                }
            }
            
            $token = JWTAuth::fromUser($user);
            Log::info('Usuario ha iniciado sesión: ' . $user->correo . ' IP:' . $request->getClientIp());
            return $this->respondWithToken($token);
    
        } catch (\Exception $e) {
            Log::info('Error al iniciar sesión: IP:' . $request->getClientIp());
            return redirect()->route('login')->with(['auth' => 'Ha ocurrido un error. Por favor, inténtalo de nuevo.']);
        }
    }       
    
    protected function respondWithToken($token)
    {
        return redirect()->route('welcome', ['token' => $token]);
    }

    public function logout(Request $request)
    {
        try {
            $token = JWTAuth::getToken();
            $user = JWTAuth::setToken($token)->authenticate();
            Log::info('Usuario ha cerrado sesión:' . $user->correo . ' IP:' . $request->getClientIp());
            JWTAuth::parseToken()->invalidate();
            return redirect()->route('login')->with('message', 'Sesión cerrada correctamente');
        } catch (\Exception $e) {
            Log::info('Error al cerrar sesión: IP:' . $request->getClientIp());
            return response()->json(['error' => 'Error al cerrar sesión'], 500);
        }
    }

    public function register(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'nombre' => 'required|string|max:60|unique:users',
                'correo' => 'required|string|email|max:60|unique:users',
                'g-recaptcha-response' => 'required',
                'contraseña' => [
                    'required',
                    'string',
                    'min:8',
                    'regex:/^(?=.*[a-z])(?=.*[A-Z])/',
                ],
            ]);

            if ($validator->fails()) {
                Log::info('Intento de registro:' . $request->correo . ' ' . implode(' ', $validator->errors()->all()) . ' IP:' . $request->getClientIp());
                return redirect()->route('register')->with(['auth' => implode(' ', $validator->errors()->all())]);
            }

            $role = User::count() === 0 ? 'admin' : 'user';

            $user = User::create([
                'nombre' => $request->input('nombre'),
                'correo' => $request->input('correo'),
                'contraseña' => Hash::make($request->input('contraseña')),
                'role' => $role,
            ]);
            $secret = null;
            if ($user->isAdmin()) {
                $google2fa = app(Google2FA::class);
                $secret = $google2fa->generateSecretKey();
                $encryptedSecret = Crypt::encryptString($secret);
                $user->google2fa_secret = $encryptedSecret;
                $user->save();
            }
            Log::info('Usuario registrado:' . $user->correo . ' IP:' . $request->getClientIp());
            return redirect()->route('register')->with(['message' => 'Usuario registrado correctamente', 'factor' => $secret,]);
        } catch (\Exception $e) {
            Log::info('Error al registrar: IP:' . $request->getClientIp());
            return redirect()->route('register')->with(['auth' => 'Algo salió mal con el registro, contacta con la administración']);
        }
    }
}
