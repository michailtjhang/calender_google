<?php

namespace App\Http\Service;

use Carbon\Carbon;
use GuzzleHttp\Client;
use App\Models\CalenderGoogle;
use GuzzleHttp\Exception\RequestException;

class GoogleService
{
    protected $client_id;
    protected $client_secret;
    protected $callback;
    private const VERSION_API = 'v3';

    // Default scope Google OAuth
    protected $scopes = 'https://www.googleapis.com/auth/userinfo.email https://www.googleapis.com/auth/userinfo.profile https://www.googleapis.com/auth/calendar';

    public function __construct($client_id, $client_secret, $callback)
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

        $response = $this->curl($url, $params, "application/x-www-form-urlencoded", "POST");
        $response = $response->getBody()->getContents();
        $responses = json_decode($response);

        return $responses;
    }

    public function refreshAccessToken($refreshToken)
    {
        $url = "https://oauth2.googleapis.com/token";
        $params = [
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret,
            'refresh_token' => $refreshToken,
            'grant_type' => 'refresh_token',
        ];

        try {
            $response = $this->curl($url, $params, "application/x-www-form-urlencoded", "POST");
            $responseBody = $response->getBody()->getContents();
            $newTokenData = json_decode($responseBody, true);

            return $newTokenData;
        } catch (\Exception $e) {
            throw new \Exception("Error refreshing access token: " . $e->getMessage());
        }
    }

    public function getUserInfo($accessToken)
    {
        $url = "https://www.googleapis.com/oauth2/" . self::VERSION_API . "/userinfo";
        $params = [
            'access_token' => $accessToken,
        ];

        $response = $this->curl($url, $params, "application/x-www-form-urlencoded", "GET");
        $response = $response->getBody()->getContents();
        $responses = json_decode($response);

        return $responses;
    }


    // Mengambil event dari Google Calendar
    public function syncCalendarEvents($accessToken, $syncToken = null, $calendarId = 'primary')
    {
        $url = "https://www.googleapis.com/calendar/" . self::VERSION_API . "/calendars/{$calendarId}/events";
        $params = [
            'access_token' => $accessToken,
            'singleEvents' => 'true',  // Pastikan ini dalam string, bukan boolean
            'orderBy' => 'startTime',
            'maxResults' => 2500,  // Atur batas maksimal event yang bisa diambil dalam satu permintaan
        ];

        if ($syncToken) {
            $params['syncToken'] = $syncToken;
        } else {
            $params['timeMin'] = now()->startOfYear()->toRfc3339String();  // Mengambil semua event sejak awal tahun
        }

        $allEvents = [];  // Array untuk menyimpan semua event
        do {
            try {
                // Kirim permintaan GET ke Google API
                $response = $this->curl($url, $params, "application/x-www-form-urlencoded", 'GET');
                $responseBody = json_decode($response->getBody()->getContents(), true);

                // Tambahkan event dari response ke $allEvents
                if (isset($responseBody['items'])) {
                    $allEvents = array_merge($allEvents, $responseBody['items']);
                }

                // Cek apakah ada nextPageToken untuk halaman selanjutnya
                $nextPageToken = $responseBody['nextPageToken'] ?? null;
                if ($nextPageToken) {
                    $params['pageToken'] = $nextPageToken;  // Jika ada, gunakan untuk permintaan berikutnya
                }
            } catch (\Exception $e) {
                throw new \Exception("Error syncing calendar events: " . $e->getMessage());
            }
        } while ($nextPageToken);  // Ulangi jika ada halaman berikutnya

        // Ambil token sinkronisasi berikutnya (jika ada)
        $nextSyncToken = $responseBody['nextSyncToken'] ?? null;

        return [
            'events' => $allEvents,
            'nextSyncToken' => $nextSyncToken,
        ];
    }


    // Mengambil event dari Google Calendar
    public function getCalendarEvents($accessToken, $calendarId = 'primary')
    {
        $url = "https://www.googleapis.com/calendar/" . self::VERSION_API . "/calendars/{$calendarId}/events";

        $params = [
            'access_token' => $accessToken,
            'timeMin' => now()->startOfDay()->toRfc3339String(),
            'timeMax' => now()->endOfDay()->toRfc3339String(),
            'singleEvents' => true,
            'orderBy' => 'startTime'
        ];

        $response = $this->curl($url, $params, "application/x-www-form-urlencoded", false);
        $response = $response->getBody()->getContents();
        $events = json_decode($response, true);

        return $events;
    }

    // Membuat event baru di Google Calendar
    public function createCalendarEvent($accessToken, $calendarId = 'primary', $eventData)
    {
        $url = "https://www.googleapis.com/calendar/" . self::VERSION_API . "/calendars/{$calendarId}/events";

        $params = [
            'headers' => [
                'Authorization' => 'Bearer ' . $accessToken,  // Tambahkan access token dalam Authorization header
                'Content-Type' => 'application/json',
            ],
            'json' => $eventData,
        ];  

        try {
            $response = $this->curl($url, $params, "application/json", 'POST');
            $response = $response->getBody()->getContents();
            $event = json_decode($response, true);

            return $event;  // Kembalikan event yang dibuat
        } catch (\Exception $e) {
            throw new \Exception("Error creating event: " . $e->getMessage());
        }
    }


    // Mengupdate event yang sudah ada di Google Calendar
    public function updateCalendarEvent($accessToken, $eventId, $calendarId = 'primary', $eventData)
    {
        $url = "https://www.googleapis.com/calendar/" . self::VERSION_API . "/calendars/{$calendarId}/events/{$eventId}";

        $params = [
            'access_token' => $accessToken,
            'json' => $eventData,
        ];

        $response = $this->curl($url, $params, "application/json", 'PUT');
        $response = $response->getBody()->getContents();
        $event = json_decode($response, true);

        return $event;
    }

    // Menghapus event dari Google Calendar
    public function deleteCalendarEvent($accessToken, $eventId, $calendarId = 'primary')
    {
        $url = "https://www.googleapis.com/calendar/" . self::VERSION_API . "/calendars/{$calendarId}/events/{$eventId}";

        $params = [
            'access_token' => $accessToken,
        ];

        $response = $this->curl($url, $params, "application/json", 'DELETE');
        return $response->getStatusCode() === 204; // 204 berarti berhasil dihapus
    }

    // Fungsi curl untuk handling request
    private function curl($url, $parameters, $content_type, $method = 'GET')
    {
        $client = new Client();
        $options = [
            'verify' => true,
        ];

        if ($method === 'POST' || $method === 'PUT') {
            $options['headers'] = ['Content-Type' => $content_type];
            $options['form_params'] = $parameters;
        } else {
            // Untuk GET, tambahkan parameter ke URL
            $url .= '?' . http_build_query($parameters);
        }

        try {
            $response = $client->request($method, $url, $options);
            return $response;
        } catch (RequestException $e) {
            throw new \Exception("Error during request: " . $e->getMessage());
        }
    }
}
