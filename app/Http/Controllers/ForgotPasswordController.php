<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Traits\ApiResponse;
use Illuminate\Support\Facades\DB;
use App\Mail\SendCodeResetPassword;
use Exception;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use App\Models\{User, ResetCodePassword};
use Illuminate\Auth\Events\PasswordReset;
use Symfony\Component\HttpFoundation\Response;

class ForgotPasswordController extends Controller
{

    use ApiResponse;

    public function sendResetLink(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email|exists:users,email',
            ]);

            // Buat token plain text
            $token = Str::random(60);

            $user = User::where('email', $request->email)->first();

            if ($user) {
                // Hapus token lama jika ada
                DB::table('password_reset_tokens')->where('email', $request->email)->delete();

                // Simpan token yang di-hash ke tabel password_resets
                $status = ResetCodePassword::create([
                    'email' => $request->email,
                    'token' => $token, // Hash token sebelum menyimpannya ke database
                    'created_at' => Carbon::now(),
                ]);
                if ($status) {
                    Mail::to($request->email)->send(new SendCodeResetPassword($status));
                    return $this->successResponse(
                        'Email reset password telah dikirim',
                        Response::HTTP_OK
                    );
                }

                throw ValidationException::withMessages([
                    'email' => [trans($status)],
                ]);
            } else {
                return $this->errorResponse('Email tidak ditemukan', Response::HTTP_BAD_REQUEST);
            }
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // Mengatur ulang password
    public function resetPassword(Request $request)
    {
        try {
            $validated = $request->validate([
                'email' => 'required|email',
                'token' => 'required',
                'password' => 'required|min:8|confirmed',
            ]);


            $status = Password::reset(
                $request->only('email', 'password', 'password_confirmation', 'token'),
                function (User $user, string $password) {
                    $user->forceFill([
                        'password' => Hash::make($password)
                    ])->setRememberToken(Str::random(60));

                    $user->save();

                    event(new PasswordReset($user));
                }
            );

            if ($status) {
                // return response()->json(['message' => __($status)], 200);
                return $this->successResponseWithData(
                    $status,
                    'Password berhasil diubah',
                    Response::HTTP_OK
                );
            }

            throw ValidationException::withMessages([
                'email' => [trans($status)],
            ]);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }
}
