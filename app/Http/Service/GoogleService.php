<?php

namespace App\Services\Google;

use GuzzleHttp\Client;
use GuzzleHttp\Promise;

class GoogleService
{
    protected $client_id;
    protected $client_secret;
    protected $callback;
    private const VERSION_API = 'v3';

    // Default scope Google OAuth
    protected $scopes = 'https://www.googleapis.com/auth/userinfo.email https://www.googleapis.com/auth/userinfo.profile https://www.googleapis.com/auth/calendar';

    public function __construct(string $client_id, string $client_secret, string $callback)
    {
        $this->client_id = $client_id;
        $this->client_secret = $client_secret;
        $this->callback = $callback;
    }

    public function getAuthUrl()
    {
        // Buat state untuk keamanan
        $state = bin2hex(random_bytes(16));
        session(['google_oauth_state' => $state]);

        // Param untuk URL otentikasi Google dengan scope default
        $params = [
            'response_type' => 'code',
            'client_id' => $this->client_id,
            'redirect_uri' => $this->callback,
            'scope' => $this->scopes,
            'state' => $state,
            'access_type' => 'offline',
            'prompt' => 'consent'
        ];

        // Buat URL otentikasi Google
        return "https://accounts.google.com/o/oauth2/auth?" . http_build_query($params);
    }

    public function getAccessToken($code)
    {
        $url = "https://oauth2.googleapis.com/token";
        $params = [
            'code' => $code,
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret,
            'redirect_uri' => $this->callback,
            'grant_type' => 'authorization_code',
        ];

        $response = $this->curl($url, $params, "application/x-www-form-urlencoded");
        $response = $response->getBody()->getContents();
        $responses = json_decode($response);

        return $responses;
    }

    public function getUserInfo($accessToken)
    {
        $url = "https://www.googleapis.com/oauth2/" . self::VERSION_API . "/userinfo";
        $params = [
            'access_token' => $accessToken,
        ];

        $response = $this->curl($url, $params, "application/x-www-form-urlencoded", false);
        $response = $response->getBody()->getContents();
        $responses = json_decode($response);

        return $responses;
    }

    private function curl($url, $parameters, $content_type, $post = true)
    {
        $client = new Client();
        $options = [
            'verify' => true,
        ];

        if ($post) {
            $options['headers'] = ['Content-Type' => $content_type];
            $options['form_params'] = $parameters;
        } else {
            $url .= '?' . http_build_query($parameters);
        }

        try {
            $response = $client->request($post ? 'POST' : 'GET', $url, $options);
            return $response;
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
