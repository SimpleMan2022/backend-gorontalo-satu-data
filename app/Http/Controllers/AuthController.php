<?php

namespace App\Http\Controllers;

use App\Http\Helpers\ResponseHelper;
use App\Http\Requests\AuthRequest;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use App\Http\Traits\ApiResponse;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    use ApiResponse;

    public function create(Request $request)
    {
        // $validated = $request->validated();
        $validated = $request->validate([
            'username' => 'required',
            'email' => 'required',
            'password' => 'required',
        ]);
        $validated['password'] = Hash::make($validated['password']);
        $user = User::create($validated);
        return $this->successResponseWithData(
            $user,
            'success',
            Response::HTTP_OK
        );
    }

    public function login(Request $request)
    {
        if (!Auth::attempt($request->only('username', 'password'))) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 401);
        }
        $user = User::where('username', $request->username)->firstOrFail();
        $token = $user->createToken('auth_token')->plainTextToken;
        $data = [
            'token_type' => 'Bearer',
            'token' => $token,
            // 'id' => $user
        ];

        return $this->successResponseWithData(
            $data,
            'success',
            Response::HTTP_OK
        );
    }

    public function getUser(Request $request)
    {
        // return $request->user();
        return $this->successResponseWithData(
            $request->user(),
            'success',
            Response::HTTP_OK
        );
    }

    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();

        return $this->successResponse(
            'success',
            Response::HTTP_OK
        );
    }
}
