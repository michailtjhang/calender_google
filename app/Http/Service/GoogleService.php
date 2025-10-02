<?php

namespace App\Http\Service;

use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class GoogleService
{
    protected $client_id;
    protected $client_secret;
    protected $callback;
    private const VERSION_API = 'v3';

    // Default scope Google OAuth
    protected $scopes = 'https://www.googleapis.com/auth/userinfo.email https://www.googleapis.com/auth/userinfo.profile https://www.googleapis.com/auth/calendar.events';

    public function __construct($client_id, $client_secret, $callback)
    {
        $this->client_id = $client_id;
        $this->client_secret = $client_secret;
        $this->callback = $callback;
    }

    // --- OAuth Methods ---

    public function getAuthUrl()
    {
        $customData = [
            'csrf'   => bin2hex(random_bytes(16)),
            'return' => 'profile',
        ];

        $state = base64_encode(json_encode($customData));
        session(['google_oauth_state' => $customData['csrf']]);

        $params = [
            'scope'         => $this->scopes,
            'access_type'   => 'offline',
            'response_type' => 'code',
            'state'         => $state,
            'client_id'     => $this->client_id,
            'redirect_uri'  => $this->callback,
            'prompt'        => 'consent select_account', // Minta izin ulang jika scope berubah
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

        // Menggunakan metode curl untuk POST form_params
        $response = $this->curl($url, $params, "application/x-www-form-urlencoded", "POST");
        $response = $response->getBody()->getContents();
        return json_decode($response);
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
            // Menggunakan metode curl untuk POST form_params
            $response = $this->curl($url, $params, "application/x-www-form-urlencoded", "POST");
            $responseBody = $response->getBody()->getContents();
            return json_decode($responseBody, true);
        } catch (\Exception $e) {
            throw new \Exception("Error refreshing access token: " . $e->getMessage());
        }
    }

    public function getUserInfo($accessToken)
    {
        $url = "https://www.googleapis.com/oauth2/v2/userinfo"; // Gunakan v2 untuk userinfo

        $headers = [
            'Authorization' => 'Bearer ' . $accessToken,
        ];

        // Tidak perlu parameter di body untuk GET userinfo
        $response = $this->curl($url, [], "application/json", "GET", $headers);
        $response = $response->getBody()->getContents();
        return json_decode($response);
    }

    // --- Calendar Sync Method (Sesuai dengan perbaikan sebelumnya) ---

    // Mengambil event dari Google Calendar
    public function syncCalendarEvents($accessToken, $syncToken = null, $extraParams = [], $calendarId = 'primary')
    {
        $url = "https://www.googleapis.com/calendar/" . self::VERSION_API . "/calendars/{$calendarId}/events";

        // Access token dipindah ke header
        $headers = [
            'Authorization' => 'Bearer ' . $accessToken,
        ];

        $params = [
            // Parameter penting untuk sinkronisasi
            'showDeleted' => 'true',
            'showHiddenInvitations' => 'true',
        ];

        $isFullSync = false;

        if ($syncToken) {
            // --- INCREMENTAL SYNC ---
            $params['syncToken'] = $syncToken;
            // DILARANG: timeMin, maxResults, orderBy.
        } else {
            // --- FULL SYNC ---
            $isFullSync = true;
            // Batasi full sync maksimal 1 tahun ke belakang
            $params['singleEvents'] = 'true'; // Perlu di-set untuk recurrence
            $params['orderBy'] = 'startTime'; // Perlu di-set bersama timeMin
            $params['maxResults'] = 250; // Batas maksimal per permintaan
        }

        // Gabungkan parameter tambahan (termasuk timeMax jika ada)
        $params = array_merge($params, $extraParams);

        $allEvents = [];
        $nextPageToken = null;
        $responseBody = null;

        do {
            try {
                // Tambahkan pageToken untuk paging
                if ($nextPageToken) {
                    $params['pageToken'] = $nextPageToken;
                }

                // Kirim permintaan GET ke Google API
                $response = $this->curl($url, $params, "application/x-www-form-urlencoded", 'GET', $headers);
                $statusCode = $response->getStatusCode();
                $responseBody = json_decode($response->getBody()->getContents(), true);

                // Cek jika sync token kedaluwarsa (HTTP 410 Gone)
                if ($statusCode === 410) {
                    if (!$isFullSync) {
                        // Throw exception spesifik agar caller tahu harus melakukan full sync ulang
                        throw new \Exception("Sync Token Invalid (410 Gone). Must perform full sync.");
                    }
                    // Jika 410 terjadi pada full sync, itu adalah error fatal API.
                } elseif ($statusCode >= 400) {
                    // Error API lainnya
                    throw new \Exception("Google Calendar API Error: Status {$statusCode}. " . ($responseBody['error']['message'] ?? 'Unknown error'));
                }

                // Tambahkan event
                if (isset($responseBody['items'])) {
                    $allEvents = array_merge($allEvents, $responseBody['items']);
                }

                // Dapatkan token untuk halaman berikutnya
                $nextPageToken = $responseBody['nextPageToken'] ?? null;
            } catch (\Exception $e) {
                // Jangan hilangkan exception 410
                throw $e;
            }
        } while ($nextPageToken);

        // Ambil token sinkronisasi berikutnya
        $nextSyncToken = $responseBody['nextSyncToken'] ?? null;

        return [
            'events' => $allEvents,
            'nextSyncToken' => $nextSyncToken,
        ];
    }

    // --- Other Calendar Methods (Updated to use Authorization Header) ---

    // Mengambil event hari ini
    public function getCalendarEvents($accessToken, $calendarId = 'primary')
    {
        $url = "https://www.googleapis.com/calendar/" . self::VERSION_API . "/calendars/{$calendarId}/events";

        $headers = [
            'Authorization' => 'Bearer ' . $accessToken,
        ];

        $params = [
            'timeMin' => now()->startOfDay()->toRfc3339String(),
            'timeMax' => now()->endOfDay()->toRfc3339String(),
            'singleEvents' => 'true',
            'orderBy' => 'startTime'
        ];

        $response = $this->curl($url, $params, "application/x-www-form-urlencoded", "GET", $headers);
        $response = $response->getBody()->getContents();
        return json_decode($response, true);
    }

    // Membuat event baru di Google Calendar
    public function createCalendarEvent($accessToken, $calendarId = 'primary', $eventData)
    {
        $url = "https://www.googleapis.com/calendar/" . self::VERSION_API . "/calendars/{$calendarId}/events";

        $headers = [
            'Authorization' => 'Bearer ' . $accessToken,
            'Content-Type' => 'application/json',
        ];

        try {
            // Menggunakan metode curl untuk POST JSON
            $response = $this->curl($url, $eventData, "application/json", 'POST', $headers);
            $response = $response->getBody()->getContents();
            return json_decode($response, true);
        } catch (\Exception $e) {
            throw new \Exception("Error creating event: " . $e->getMessage());
        }
    }


    // Mengupdate event yang sudah ada di Google Calendar
    public function updateCalendarEvent($accessToken, $eventId, $calendarId = 'primary', $eventData)
    {
        $url = "https://www.googleapis.com/calendar/" . self::VERSION_API . "/calendars/{$calendarId}/events/{$eventId}";

        $headers = [
            'Authorization' => 'Bearer ' . $accessToken,
            'Content-Type' => 'application/json',
        ];

        try {
            // Menggunakan metode curl untuk PUT JSON
            $response = $this->curl($url, $eventData, "application/json", 'PUT', $headers);
            $response = $response->getBody()->getContents();
            return json_decode($response, true);
        } catch (\Exception $e) {
            throw new \Exception("Error updating event: " . $e->getMessage());
        }
    }

    // Menghapus event dari Google Calendar
    public function deleteCalendarEvent($accessToken, $eventId, $calendarId = 'primary')
    {
        $url = "https://www.googleapis.com/calendar/" . self::VERSION_API . "/calendars/{$calendarId}/events/{$eventId}";

        $headers = [
            'Authorization' => 'Bearer ' . $accessToken,
        ];

        $response = $this->curl($url, [], "application/json", 'DELETE', $headers);
        return $response->getStatusCode() === 204; // 204 berarti berhasil dihapus
    }

    // --- Guzzle/cURL Helper (Diperbaiki untuk JSON/Form/Query Params) ---
    private function curl($url, $parameters, $content_type, $method = 'GET', $headers = [])
    {
        $client = new Client();

        $options = [
            'verify' => true, // Pertahankan SSL verification
            'headers' => $headers,
        ];

        if ($method === 'POST' || $method === 'PUT') {
            if ($content_type === "application/json") {
                // Untuk POST/PUT event (JSON Payload)
                $options['json'] = $parameters;
            } else {
                // Untuk POST token (Form Parameters)
                $options['form_params'] = $parameters;
            }
        } elseif ($method === 'GET' || $method === 'DELETE') {
            // Untuk GET/DELETE (Query Parameters)
            if (!empty($parameters)) {
                $options['query'] = $parameters;
            }
        }

        try {
            $response = $client->request($method, $url, $options);
            return $response;
        } catch (RequestException $e) {
            // Tangkap Guzzle exceptions dan lempar exception standar PHP
            $statusCode = $e->hasResponse() ? $e->getResponse()->getStatusCode() : 500;

            // Re-throw exception untuk penanganan 410 di syncCalendarEvents
            if ($statusCode === 410) {
                throw new \Exception("Sync Token Invalid (410 Gone). Must perform full sync.");
            }

            throw new \Exception("Error during Google API request: [Status: {$statusCode}] " . $e->getMessage());
        }
    }
}
