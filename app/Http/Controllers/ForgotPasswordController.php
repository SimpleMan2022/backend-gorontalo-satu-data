<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Traits\ApiResponse;
use Illuminate\Support\Facades\DB;
use App\Mail\SendCodeResetPassword;
use Exception;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

class ForgotPasswordController extends Controller
{

    use ApiResponse;

    public function sendResetLink(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email|exists:users,email',
            ]);

            // Mengirim link reset password
            // $status = Password::sendResetLink(
            //     $request->only('email')
            // );

            // Buat token plain text
            $token = Str::random(60);

            // Hapus token lama jika ada
            DB::table('password_reset_tokens')->where('email', $request->email)->delete();

            // Simpan token yang di-hash ke tabel password_resets
            $status = DB::table('password_reset_tokens')->insert([
                'email' => $request->email,
                'token' => Hash::make($token), // Hash token sebelum menyimpannya ke database
                'created_at' => Carbon::now(),
            ]);

            if ($status) {
                $tokenData = DB::table('password_reset_tokens')
                    ->where('email', $request->email)
                    ->first();

                // Jika token ditemukan, kirimkan email kustom
                if ($tokenData) {
                    Mail::to($request->email)->send(new SendCodeResetPassword($tokenData));
                    return response()->json(['message' => $tokenData], 200);
                }
            }

            throw ValidationException::withMessages([
                'email' => [trans($status)],
            ]);
        } catch (ValidationException $e) {
            return $this->errorResponseWithData($e->errors(), 'Gagal mengirimkan email reset password', Response::HTTP_BAD_REQUEST);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // Mengatur ulang password
    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'token' => 'required',
            'password' => 'required|min:8|confirmed',
        ]);

        // Proses reset password
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->save();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json(['message' => __($status)], 200);
        }

        throw ValidationException::withMessages([
            'email' => [trans($status)],
        ]);
    }
}
