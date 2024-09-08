<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Service\GoogleService;
use Illuminate\Support\Facades\Auth;

class OauthController extends Controller
{
    public function callback(Request $request)
    {
        $googleService = new GoogleService(
            config('google.app_id'),
            config('google.app_secret'),
            config('google.app_callback'),
        );

        if ($request->has('code')) {
            $token = $googleService->getAccessToken($request->code);
            $accessToken = $token->access_token;
            $expires_in = $token->expires_in;
            $expirationTime = Carbon::now()->addSeconds($expires_in);
            $formattedTime = $expirationTime->format('Y-m-d H:i:s');

            $refresh_token = $token->refresh_token;
            $id_token = $token->id_token;

            $getUser = $googleService->getUserInfo($accessToken);
            $id_google = $getUser->sub;
            
            $user_id = Auth::user()->id;

            DB::table('users')->where('id', $user_id)->update([
                'calendar_access_token' => $accessToken,
                'calendar_refresh_token' => $refresh_token,
                'calendar_user_account_info' => $id_google,
                'expires_in' => $formattedTime
            ]);
        }

        return redirect('/calenders');
    }
}
