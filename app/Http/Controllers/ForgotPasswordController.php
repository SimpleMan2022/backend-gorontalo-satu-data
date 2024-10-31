<?php

namespace App\Http\Controllers;

use App\Http\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Mail\SendCodeResetPassword;
use Carbon\Carbon;

class ForgotPasswordController extends Controller
{

    use ApiResponse;

    public function sendResetLink(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
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
