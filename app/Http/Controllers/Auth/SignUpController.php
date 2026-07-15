<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SignUpController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $user = User::query()->create($request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'lowercase',
                'max:255',
                'unique:users',
                Rule::in(config('registration.email_whitelist')),
            ],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]));

        return response()->json([
            'user' => $user,
            'token' => $user->createToken('api')->plainTextToken,
        ], 201);
    }
}
