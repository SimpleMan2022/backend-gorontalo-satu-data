<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use App\Http\Traits\ApiResponse;
use App\Mail\SendCodeResetPassword;
use Exception;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use App\Models\ResetCodePassword;
use Illuminate\Support\Facades\Mail;

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

      if($user->hasRole('enumerator')) {
        return $this->errorResponse('Enumerator login menggunakan aplikasi mobile.', Response::HTTP_UNAUTHORIZED);
      }
      $token = $user->createToken('auth_token')->plainTextToken;
      $data = [
        'token_type' => 'Bearer',
        'token' => $token,
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
  public function loginEnumerator(Request $request)
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

      if(!$user->hasRole('enumerator')) {
        return $this->errorResponse('Operator login menggunakan aplikasi website.', Response::HTTP_UNAUTHORIZED);
      }
      $token = $user->createToken('auth_token')->plainTextToken;
      $data = [
        'token_type' => 'Bearer',
        'token' => $token,
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
      [
        'id' => $request->user()->id,
        'username' => $request->user()->username,
        'email' => $request->user()->email,
        'role' => [
          'id' => $request->user()->roles->first()->id,
          'name' => $request->user()->roles->first()->name,
          'permissions' => $request->user()->roles->first()->permissions->pluck('name')
        ],
      ],
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

  public function forgotPassword(Request $request)
  {
    $data = $request->validate([
      'email' => 'required|email|exists:users',
    ]);

    // Delete all old code that user send before.
    ResetCodePassword::where('email', $request->email)->delete();

    // Generate random code
    $data['token'] = mt_rand(100000, 999999);

    // Create a new code
    $codeData = ResetCodePassword::create($data);

    // Send email to user
    Mail::to($request->email)->send(new SendCodeResetPassword($codeData->token));

    return response(['message' => trans('passwords.sent')], 200);
  }
}
