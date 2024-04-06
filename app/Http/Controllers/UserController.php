<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use PragmaRX\Google2FA\Google2FA;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    protected function goToIndex(Request $request)
    {
        $usuario = Auth::user();
        $rol = $usuario->role;
        $token = $request->input('token');
        $expiracion = now()->addMinutes(10);
        $urlFirmada = URL::temporarySignedRoute('usuarios', $expiracion,['token' => $token, 'rol'=>$rol]);
        //return redirect()->to($urlFirmada);
        // Verificar si hay un valor 'auth' en la sesión
        if (session()->has('auth')) {
            // Si existe, redirigir a la URL firmada agregando 'auth' como parámetro de consulta
            return redirect()->to($urlFirmada)->with('auth', session('auth'));
        }else if(session()->has('message') || session()->has('factor')){
            return redirect()->to($urlFirmada)->with('message',session('message'))->with('factor',session('factor'));
        }else {
            // Si no existe, simplemente redirigir a la URL firmada
            return redirect()->to($urlFirmada);
        }
    }

    public function index(Request $request)
    {
        if ($request->hasValidSignature()) {
            $token = $request->input('token');
            $rol = $request->input('rol');
            $users = User::all();
            return view('users', ['token' => $token, 'users' => $users, 'rol'=>$rol]);
        } else {
            abort(403, 'Firma no válida');
        }
    }

    protected function goToCreate(Request $request)
    {
        $token = $request->input('token');
        $expiracion = now()->addMinutes(10);
        $urlFirmada = URL::temporarySignedRoute('createUser', $expiracion,['token' => $token]);
        return redirect()->to($urlFirmada);
    }

    public function create(Request $request)
    {
        if ($request->hasValidSignature()) {
            $token = $request->input('token');
            return view('usersCreate', ['token' => $token]);
        } else {
            abort(403, 'Firma no válida');
        }
    }

    public function store(Request $request)
    {
        $token = $request->input('token');
        //User::create($request->all());
        //return redirect()->route('usuariosSigned', ['token' => $token]);
        try {
            $validator = Validator::make($request->all(), [
                'nombre' => 'required|string|max:60|unique:users',
                'correo' => 'required|string|email|max:60|unique:users',
                'role' => 'required',
                'contraseña' => [
                    'required',
                    'string',
                    'min:8',
                    'regex:/^(?=.*[a-z])(?=.*[A-Z])/',
                ],
            ]);
            if ($validator->fails()) {
                return redirect()->route('usuariosSigned', ['token' => $token])->with(['auth' => implode(' ', $validator->errors()->all())]);
                //return redirect()->route('register')->with(['auth' => implode(' ', $validator->errors()->all())]);
            }
            $user = User::create([
                'nombre' => $request->input('nombre'),
                'correo' => $request->input('correo'),
                'contraseña' => Hash::make($request->input('contraseña')),
                'role' => $request->input('role'),
            ]);
            $secret = null;
            if ($user->isAdmin()) {
                $google2fa = app(Google2FA::class);
                $secret = $google2fa->generateSecretKey();
                $encryptedSecret = Crypt::encryptString($secret);
                $user->google2fa_secret = $encryptedSecret;
                $user->save();
            }
            return redirect()->route('usuariosSigned', ['token' => $token])->with(['message' => 'Usuario registrado correctamente', 'factor' => $secret,]);
            //return redirect()->route('register')->with(['message' => 'Usuario registrado correctamente', 'factor' => $secret,]);
        } catch (\Exception $e) {
            return redirect()->route('usuariosSigned', ['token' => $token])->with(['auth' => $e->getMessage()]);
            //return redirect()->route('register')->with(['auth' => 'Algo salió mal con el registro, contacta con la administración']);
        }
    }

    protected function goToEdit(User $user, Request $request)
    {
        $token = $request->input('token');
        $expiracion = now()->addMinutes(10);
        $urlFirmada = URL::temporarySignedRoute('editUser', $expiracion,['token' => $token, 'user' => $user]);
        return redirect()->to($urlFirmada);
    }

    public function edit(User $user, Request $request)
    {
        if ($request->hasValidSignature()) {
            $token = $request->input('token');
            return view('usersUpdate', ['token' => $token, 'user' => $user]);
        } else {
            abort(403, 'Firma no válida');
        }
    }

    public function update(Request $request, User $user)
    {
        try {
            $token = $request->input('token');
            $user = User::findOrFail($request->id);
            //$user->update($request->all());
            //return redirect()->route('usuariosSigned', ['token' => $token]);
            try {
                $updateData = [
                    'nombre' => $request->input('nombre'),
                    'correo' => $request->input('correo'),
                    'role' => $request->input('role'),
                ];
                $validatorRules = [
                    'role' => 'required',
                ];
                if ($user->nombre !== $updateData['nombre']) {
                    $validatorRules['nombre'] = 'required|string|max:60|unique:users';
                }
                if ($user->correo !== $updateData['correo']) {
                    $validatorRules['correo'] = 'required|string|email|max:60|unique:users';
                }
                if (!empty($validatorRules)) {
                    $validator = Validator::make($updateData, $validatorRules);
                    if ($validator->fails()) {
                        return redirect()->route('usuariosSigned', ['token' => $token])->with(['auth' => implode(' ', $validator->errors()->all())]);
                    }
                }
                $user->nombre = $request->input('nombre');
                $user->correo = $request->input('correo');
                $user->role = $request->input('role');
                $user->save();
                $secret = $user->google2fa_secret;
                if ($user->isAdmin()) {
                    if($secret == null){
                        $google2fa = app(Google2FA::class);
                        $secret = $google2fa->generateSecretKey();
                        $encryptedSecret = Crypt::encryptString($secret);
                        $user->google2fa_secret = $encryptedSecret;
                        $user->code = null;
                        $user->code_used = false;
                        $user->save();
                    }else{
                        $secret = null;
                    }
                }else{
                    $secret = null;
                    $user->google2fa_secret = null;
                    $user->code = null;
                    $user->code_used = false;
                    $user->save();
                }
                return redirect()->route('usuariosSigned', ['token' => $token])->with(['message' => 'Usuario actualizado correctamente', 'factor' => $secret,]);
                //return redirect()->route('register')->with(['message' => 'Usuario registrado correctamente', 'factor' => $secret,]);
            } catch (\Exception $e) {
                return redirect()->route('usuariosSigned', ['token' => $token])->with(['auth' => $e->getMessage()]);
                //return redirect()->route('register')->with(['auth' => 'Algo salió mal con el registro, contacta con la administración']);
            }
        } catch (ModelNotFoundException $e) {
            abort(404, "Usuario no encontrado"); // El usuario no fue encontrado
        } catch (\Exception $e) {
            abort(500, "Error inesperado"); // Ocurrió un error inesperado
        }
    }

    public function destroy(User $usuario, Request $request)
    {
        $token = $request->input('token');
        $usuario->delete();
        return redirect()->route('usuariosSigned', ['token' => $token]);
    }
}
