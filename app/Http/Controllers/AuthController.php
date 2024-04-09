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

use App\Mail\ExampleMail;
use Illuminate\Support\Facades\Mail;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        return view('login');
    }

    public function showRegisterForm(){
        return view('register');
    }

    public function showCorreoForm(){
        return view('correo');
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
                $allowedIPs = explode(',', env('ADMIN_ALLOWED_IPS'));
                $clientIP = $request->getClientIp();
                if (!in_array($clientIP, $allowedIPs)) {
                    Log::info('Intento de sesión de administrador desde una IP no autorizada: ' . $user->correo . ' IP:' . $clientIP);
                    return redirect()->route('login')->with(['auth' => 'Acceso no autorizado desde esta dirección IP']);
                }
                if (!$request->google2fa_code) {
                    Log::info('Intento de sesión sin código: ' . $user->correo . ' IP:' . $request->getClientIp());
                    return redirect()->route('login')->with(['auth' => 'Código de autenticación en dos pasos requerido']);
                }
                $encryptedSecret = $user->code;
                $decryptedSecret = Crypt::decryptString($encryptedSecret);
                if ($decryptedSecret != $request->input('google2fa_code') || $user->code_used == 1) {
                    Log::info('Intento de sesión con código: ' . $user->correo . ' IP:' . $request->getClientIp());
                    return redirect()->route('login')->with(['auth' => 'Código de autenticación en dos pasos incorrecto o usado ']);
                }
            }else{
                if (!$request->google2fa_code) {
                    Log::info('Intento de sesión sin código: ' . $user->correo . ' IP:' . $request->getClientIp());
                    return redirect()->route('login')->with(['auth' => 'Código de autenticación en dos pasos requerido']);
                }
                $encryptedSecret = $user->code;
                $decryptedSecret = Crypt::decryptString($encryptedSecret);
                if ($decryptedSecret != $request->input('google2fa_code') || $user->code_used == 1) {
                    Log::info('Intento de sesión con código: ' . $user->correo . ' IP:' . $request->getClientIp());
                    return redirect()->route('login')->with(['auth' => 'Código de autenticación en dos pasos incorrecto o usado ']);
                }
            }

            if($user->isAdmin()){
                $user->code_used = true;
                $user->save();
            }else{
                $user->code_used = true;
                $user->save();
            }
            if($user->token != null){
                $decryptedToken = Crypt::decryptString($user->token);
                if (JWTAuth::setToken($decryptedToken)->check()) {
                    // El token no está expirado, podemos invalidarlo
                    JWTAuth::setToken($decryptedToken)->invalidate();
                }
            }
            $user->token = null;
            $token = JWTAuth::fromUser($user);
            $user->token = Crypt::encryptString($token);
            $user->save();
            Log::info('Usuario ha iniciado sesión: ' . $user->correo . ' IP:' . $request->getClientIp());
            return $this->respondWithToken($token, $user->role);
    
        } catch (\Exception $e) {
            Log::info('Error al iniciar sesión: IP:' . $request->getClientIp());
            return redirect()->route('login')->with(['auth' => 'Ha ocurrido un error. Por favor, inténtalo de nuevo.']);
        }
    }       
    
    protected function respondWithToken($token, $rol)
    {
        $expiracion = now()->addMinutes(10);
        $urlFirmada = URL::temporarySignedRoute('welcome', $expiracion, ['token' => $token, 'rol' => $rol]);
        return redirect()->to($urlFirmada);
    }

    public function logout(Request $request)
    {
        try {
            $token = JWTAuth::getToken();
            $user = JWTAuth::setToken($token)->authenticate();
            $user->token = null;
            $user->save();
            Log::info('Usuario ha cerrado sesión:' . $user->correo . ' IP:' . $request->getClientIp());
            JWTAuth::parseToken()->invalidate();
            return redirect()->route('login')->with('message', 'Sesión cerrada correctamente');
        } catch (\Exception $e) {
            Log::info('Error al cerrar sesión: IP:' . $request->getClientIp());
            return redirect()->route('login')->with(['auth' => 'Error al cerrar sesión'], 500);
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

    public function generateCode(Request $request)
    {
        try {
            // Validar la solicitud
            $validator = Validator::make($request->all(), [
                'correo' => 'required|email',
                'google2fa_secret' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json(['error' => $validator->errors()->first()], 400);
            }

            // Obtener el usuario
            $user = User::where('correo', $request->input('correo'))->first();
            if (!$user) {
                return response()->json(['error' => 'Usuario no encontrado'], 404);
            }

            // Verificar el secreto de Google 2FA
            $google2fa = app(Google2FA::class);
            $encryptedSecret = $user->google2fa_secret;
            $decryptedSecret = Crypt::decryptString($encryptedSecret);
            if ($decryptedSecret !== $request->input('google2fa_secret')) {
                return response()->json(['error' => 'Código incorrecto'], 400);
            }

            // Generar un nuevo código
            $code = mt_rand(100000, 999999);

            $encryptedCode = Crypt::encryptString($code);

            // Guardar el código en la base de datos
            $user->code = $encryptedCode;
            $user->code_used = false;
            $user->save();

            return response()->json(['codigoApp' => $code], 200);
        } catch (\Exception $e) {
            Log::error('Error al generar el código: ' . $e->getMessage());
            return response()->json(['error' => 'Error al generar el código'], 500);
        }
    }

    public function enviarCorreo(Request $request)
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
                // Manejar los errores de validación aquí
                return redirect()->route('correos')->with(['auth' => implode(' ', $validator->errors()->all())]);
            }

            $credentials = $request->only('correo', 'contraseña');

            $user = User::where('correo', $credentials['correo'])->first();

            if (!$user) {
                // El usuario no existe, redirigir con el mensaje correspondiente
                return redirect()->route('correos')->with(['auth' => 'Verifica tus credenciales']);
            }

            if($user->isAdmin()){
                return redirect()->route('correos')->with(['auth' => 'No puedes generar correo para este usuario!']);
            }

            if (!$user || !Hash::check($credentials['contraseña'], $user->contraseña)) {
                // Credenciales inválidas, redirigir de vuelta al login
                return redirect()->route('correos')->with(['auth' => 'Verifica tus credenciales']);
            }

            $code = mt_rand(100000, 999999);
            $encryptedCode = Crypt::encryptString($code);
            $user->code = $encryptedCode;
            $user->code_used = false;
            $user->save();
            Mail::to($user->correo)->send(new ExampleMail($user->nombre, $code));

            // Redirigir a alguna ruta después de enviar el correo
            return redirect()->route('login')->with(['message' => 'Correo enviado']);
        } catch (\Exception $e) {
            // Manejar cualquier error inesperado aquí
            return redirect()->route('correos')->with(['auth' => 'Ha ocurrido un error. Por favor, inténtalo de nuevo.']);
        }
    }

}
