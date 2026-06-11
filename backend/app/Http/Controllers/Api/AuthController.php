<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function setupStatus()
    {
        return response()->json([
            'registration_open' => ! User::exists(),
            'message' => User::exists()
                ? 'El registro publico esta cerrado. Un administrador debe crear nuevos usuarios.'
                : 'El sistema aun no tiene administrador inicial.',
        ]);
    }

    public function register(Request $request)
    {
        abort_if(User::exists(), 403, 'El registro publico esta cerrado. Un administrador debe crear nuevos usuarios.');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:160', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        $user = User::create($data + ['role' => User::ROLE_ADMIN, 'status' => 'active']);

        return $this->tokenResponse($user, 'Administrador inicial creado correctamente');
    }

    public function createUser(Request $request)
    {
        abort_unless($request->user()->canManageUsers(), 403, 'Solo un administrador puede crear usuarios.');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:160', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', 'in:admin,user'],
        ]);

        $user = User::create($data + ['status' => 'active']);

        return response()->json([
            'message' => 'Usuario creado correctamente',
            'user' => $user,
        ], 201);
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Credenciales invalidas.'],
            ]);
        }

        if ($user->status !== 'active') {
            throw ValidationException::withMessages([
                'email' => ['Usuario bloqueado o inactivo.'],
            ]);
        }

        return $this->tokenResponse($user, 'Sesion iniciada');
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json(['message' => 'Sesion cerrada']);
    }

    private function tokenResponse(User $user, string $message)
    {
        return response()->json([
            'message' => $message,
            'token' => $user->createToken('mobile')->plainTextToken,
            'user' => $user,
        ]);
    }
}
