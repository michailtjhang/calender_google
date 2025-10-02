<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Service\GoogleService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class OauthController extends Controller
{
    public function callback(Request $request)
    {
        // Handle jika parameter 'code' tidak ada (misalnya, user menolak izin)
        if (!$request->has('code')) {
            return redirect('/calenders')->with('error', 'Google authentication failed or was denied.');
        }

        $googleService = new GoogleService(
            config('google.app_id'),
            config('google.app_secret'),
            config('google.app_callback'),
        );

        try {
            // 1. Get Access Token
            $token = $googleService->getAccessToken($request->code);

            // Validasi data token
            if (!isset($token->access_token) || !isset($token->refresh_token)) {
                Log::error('Google Token retrieval failed.', (array)$token);
                return redirect('/calenders')->with('error', 'Gagal mendapatkan token Google. Silakan coba lagi.');
            }

            $accessToken = $token->access_token;
            $expires_in = $token->expires_in;
            $expirationTime = Carbon::now()->addSeconds($expires_in);
            $formattedTime = $expirationTime->format('Y-m-d H:i:s');
            $refresh_token = $token->refresh_token;

            // 2. Get User Info
            $getUser = $googleService->getUserInfo($accessToken);

            // === PERBAIKAN: Menggunakan $getUser->id sesuai output dd() ===
            if (!is_object($getUser) || !isset($getUser->id)) {
                $errorMessage = "Gagal mengambil ID Google. Respon tidak valid dari API.";
                Log::error('Google User Info failed: Missing property id.', ['user_response' => $getUser, 'user_id' => Auth::id()]);
                return redirect('/calenders')->with('error', $errorMessage);
            }

            // Ganti $getUser->sub menjadi $getUser->id
            $id_google = $getUser->id;

            // 3. Update User Data
            $user_id = Auth::user()->id;

            DB::table('users')->where('id', $user_id)->update([
                'calendar_access_token' => $accessToken,
                'calendar_refresh_token' => $refresh_token,
                'calendar_user_account_info' => $id_google,
                'expires_in' => $formattedTime
            ]);
        } catch (\Exception $e) {
            // Tangkap exception dari GoogleService
            Log::error('Google OAuth Callback Exception: ' . $e->getMessage(), ['user_id' => Auth::id()]);
            return redirect('/calenders')->with('error', 'Terjadi error saat otentikasi: ' . $e->getMessage());
        }

        return redirect('/calenders')->with('success', 'Akun Google berhasil dihubungkan!');
    }
}
