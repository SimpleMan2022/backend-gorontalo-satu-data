<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use App\Http\Traits\ApiResponse;
use Exception;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
  use ApiResponse;

  public function login(Request $request)
  {
    try {
      $validate = $request->validate([
        'username' => 'required',
        'password' => 'required|min:8'
      ]);

      if (!Auth::attempt($request->only('username', 'password'))) {
        return $this->errorResponse('Terdapat kesalahan kredensial.', Response::HTTP_UNAUTHORIZED);
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
        'Login berhasil',
        Response::HTTP_OK
      );
    } catch (ValidationException $e) {
      return $this->errorResponseWithData($e->errors(), 'Login gagal', Response::HTTP_BAD_REQUEST);
      return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
    }
  }


  public function getUser(Request $request)
  {
    // return $request->user();
    return $this->successResponseWithData(
      $request->user(),
      'Data pengguna berhasil diambil',
      Response::HTTP_OK
    );
  }

  public function logout(Request $request)
  {
    $request->user()->tokens()->delete();

    return $this->successResponse(
      'Logout berhasil',
      Response::HTTP_OK
    );
  }
}
