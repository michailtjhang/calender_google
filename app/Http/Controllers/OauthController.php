<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\Google\GoogleService;

class OauthController extends Controller
{
    public function callback(Request $request)
    {
        $googleService = new GoogleService(
            config('services.google.client_id'),
            config('services.google.client_secret'),
            config('services.google.callback'),
        );

        if ($request->has('code')) {
            $token = $googleService->getAccessToken($request->code);
            $user = $googleService->getUserInfo($token);
        }
    }
}
