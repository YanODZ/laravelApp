<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Producto;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Auth;

class ProductoController extends Controller
{
    protected function goToIndex(Request $request)
    {
        $usuario = Auth::user();
        $rol = $usuario->role;
        $token = $request->input('token');
        $expiracion = now()->addMinutes(10);
        $urlFirmada = URL::temporarySignedRoute('productos', $expiracion,['token' => $token, 'rol'=>$rol]);
        return redirect()->to($urlFirmada);
    }

    public function index(Request $request)
    {
        if ($request->hasValidSignature()) {
            $token = $request->input('token');
            $rol = $request->input('rol');
            $productos = Producto::all();
            return view('products', ['token' => $token, 'productos' => $productos, 'rol'=>$rol]);
        } else {
            abort(403, 'Firma no válida');
        }
    }

    protected function goToCreate(Request $request)
    {
        $token = $request->input('token');
        $expiracion = now()->addMinutes(10);
        $urlFirmada = URL::temporarySignedRoute('create', $expiracion,['token' => $token]);
        return redirect()->to($urlFirmada);
    }

    public function create(Request $request)
    {
        if ($request->hasValidSignature()) {
            $token = $request->input('token');
            return view('productosCreate', ['token' => $token]);
        } else {
            abort(403, 'Firma no válida');
        }
    }

    public function store(Request $request)
    {
        $token = $request->input('token');
        Producto::create($request->all());
        return redirect()->route('productosSigned', ['token' => $token]);
    }

    protected function goToEdit(Producto $producto, Request $request)
    {
        $token = $request->input('token');
        $expiracion = now()->addMinutes(10);
        $urlFirmada = URL::temporarySignedRoute('edit', $expiracion,['token' => $token, 'producto' => $producto]);
        return redirect()->to($urlFirmada);
    }

    public function edit(Producto $producto, Request $request)
    {
        if ($request->hasValidSignature()) {
            $token = $request->input('token');
            return view('productosUpdate', ['token' => $token, 'producto' => $producto]);
        } else {
            abort(403, 'Firma no válida');
        }
    }

    public function update(Request $request, Producto $producto)
    {
        $token = $request->input('token');
        $producto->update($request->all());
        return redirect()->route('productosSigned', ['token' => $token]);
    }

    public function destroy(Producto $producto, Request $request)
    {
        $token = $request->input('token');
        $producto->delete();
        return redirect()->route('productosSigned', ['token' => $token]);
    }
}
