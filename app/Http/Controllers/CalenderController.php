<?php

namespace App\Http\Controllers;

use Exception;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\CalenderGoogle;
use App\Http\Service\EventService;
use Illuminate\Support\Facades\DB;
use App\Http\Service\GoogleService;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\CreateEventRequest;
use App\Http\Requests\UpdateEventRequest;

class CalenderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $googleService = new GoogleService(
            config('google.app_id'),
            config('google.app_secret'),
            config('google.app_callback'),
        );

        $user = Auth::user();
        $hasCalendarAccess = !empty($user->calendar_access_token);

        return view('calenders.list', [
            'OAuth2Client' => $googleService->getAuthUrl(),
            'hasCalendarAccess' => $hasCalendarAccess,
            'user' => $user,
        ]);
    }

    public function syncCalendar(Request $request)
    {
        $user = Auth::user();
        $accessToken = $user->calendar_access_token;
        $refreshToken = $user->calendar_refresh_token;

        // Cek apakah data otentikasi ada
        if (!$accessToken || !$refreshToken) {
            return back()->with('error', 'Akun Google belum terhubung atau data token hilang.');
        }

        $googleService = new GoogleService(
            config('google.app_id'),
            config('google.app_secret'),
            config('google.app_callback')
        );

        // Cek token expired dan lakukan refresh jika perlu
        if ($this->isTokenExpired($user->expires_in)) {
            try {
                $newTokenData = $googleService->refreshAccessToken($refreshToken);

                $accessToken = $newTokenData['access_token'];
                $expirationTime = Carbon::now()->addSeconds($newTokenData['expires_in']);

                // === FIX: Menggunakan toDateTimeString() sebagai METHOD ===
                $updateData = [
                    'calendar_access_token' => $accessToken,
                    'expires_in' => $expirationTime->toDateTimeString(), // BENAR
                ];

                if (isset($newTokenData['refresh_token'])) {
                    $updateData['calendar_refresh_token'] = $newTokenData['refresh_token'];
                }

                DB::table('users')->where('id', $user->id)->update($updateData);
            } catch (Exception $e) {
                // Penanganan error refresh token (misalnya, refresh token juga expired)
                return back()->with('error', 'Gagal memperbarui token Google. Silakan otentikasi ulang akun Anda. Error: ' . $e->getMessage());
            }
        }

        // --- 1. SETTING BATAS WAKTU SINKRONISASI ---
        // Kita hanya akan menyinkronkan event sampai 2 tahun ke depan untuk mengurangi event berulang (recurring events).
        $timeMax = Carbon::now()->addYears(2)->toRfc3339String();
        $params = ['timeMax' => $timeMax];
        // ------------------------------------------

        $lastSyncToken = $user->calendar_sync_token ?? null;
        $message = 'Calendar synchronized!';

        try {
            // 2. KIRIMKAN PARAMETER (termasuk timeMax) ke GoogleService
            $syncResult = $googleService->syncCalendarEvents($accessToken, $lastSyncToken, $params);
        } catch (Exception $e) {
            if (str_contains($e->getMessage(), '410 Gone')) {
                // Sync token tidak valid, lakukan FULL SYNC
                $syncResult = $googleService->syncCalendarEvents($accessToken, null);
                $message = 'Sinkronisasi penuh berhasil karena token sebelumnya kadaluarsa.';
            } else {
                return back()->with('error', 'Gagal sinkronisasi kalender: ' . $e->getMessage());
            }
        }

        $events = $syncResult['events'] ?? [];
        $nextSyncToken = $syncResult['nextSyncToken'] ?? null;

        // Catatan: Jika Anda ingin melihat data yang sudah difilter di dd(), 
        // Anda harus mengubah array $events di sini, tetapi kode di bawah ini 
        // akan langsung melewati event ulang tahun di dalam loop.

        // Simpan atau update event
        foreach ($events as $event) {

            // =========================================================
            // === FILTERING EVENT: SKIP EVENT DENGAN eventType = 'birthday' ===
            // === Menggunakan 'continue' agar event ini tidak diproses/disimpan ===
            // =========================================================
            if (isset($event['eventType']) && $event['eventType'] === 'birthday') {
                // Kita abaikan event ulang tahun, dan langsung lanjut ke event berikutnya
                continue;
            }
            // =========================================================
            
            // Cek Event Status (e.g., 'cancelled')
            if (isset($event['status']) && $event['status'] === 'cancelled') {
                CalenderGoogle::where('event_id', $event['id'])->delete();
                continue;
            }

            // Tentukan apakah ini All Day Event
            $isAllDay = isset($event['start']['date']);

            // Ambil waktu mulai dan berakhir
            // Untuk All Day Events, kita ambil 'date', jika tidak ada, ambil 'dateTime'
            $startDateTime = $event['start']['dateTime'] ?? $event['start']['date'];
            $endDateTime = $event['end']['dateTime'] ?? $event['end']['date'];

            // HANYA jika bukan All Day Event, kita parse ke string DATETIME
            $start = $isAllDay ? $startDateTime : Carbon::parse($startDateTime)->toDateTimeString();
            $end = $isAllDay ? $endDateTime : Carbon::parse($endDateTime)->toDateTimeString();

            // Lakukan update atau create menggunakan ID event yang unik (termasuk instance ID)
            CalenderGoogle::updateOrCreate(
                ['event_id' => $event['id']],
                [
                    'user_id' => $user->id,
                    'title' => $event['summary'] ?? '',
                    'description' => $event['description'] ?? '',

                    // TAMBAHAN: Simpan ID Event Berulang (jika ada)
                    'recurring_event_id' => $event['recurringEventId'] ?? null,

                    'start' => $start,
                    'end' => $end,

                    'is_all_day' => $isAllDay,
                ]
            );
        }

        // Update sync token di DB
        if ($nextSyncToken) {
            DB::table('users')->where('id', $user->id)->update([
                'calendar_sync_token' => $nextSyncToken,
            ]);
        }

        return back()->with('success', $message);
    }

    public function refetchEvents(Request $request)
    {
        $eventService = new EventService(Auth::user());
        $eventData = $eventService->allEvents($request->all());

        return response()->json($eventData);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateEventRequest $request)
    {
        $data = $request->validated();
        $user = Auth::user();
        $data['user_id'] = $user->id;

        // Simpan event di database terlebih dahulu
        $eventService = new EventService($user);
        $event = $eventService->create($data);

        if ($event) {
            // Siapkan data untuk membuat event di Google Calendar
            $googleEventData = [
                'summary' => $event->title,
                'description' => $event->description,
                'start' => [
                    'dateTime' => Carbon::parse($event->start)->toRfc3339String(),
                    'timeZone' => 'Indonesia/Jakarta',  // Sesuaikan zona waktu jika perlu
                ],
                'end' => [
                    'dateTime' => Carbon::parse($event->end)->toRfc3339String(),
                    'timeZone' => 'Indonesia/Jakarta',  // Sesuaikan zona waktu jika perlu
                ],
                'reminders' => [
                    'useDefault' => true,
                ],
            ];

            // Buat event di Google Calendar
            $googleService = new GoogleService(
                config('google.app_id'),
                config('google.app_secret'),
                config('google.app_callback')
            );

            // Kirim permintaan ke Google Calendar API
            $googleService->createCalendarEvent($user->calendar_access_token, 'primary', $googleEventData);

            return response()->json(['status' => 'success']);
        }

        return response()->json(['status' => 'failed']);
    }


    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateEventRequest $request, string $id)
    {
        $data = $request->validated();
        $user = Auth::user();

        // Update event di database
        $eventService = new EventService($user);
        $event = $eventService->update($id, $data);

        if ($event) {
            // Update event di Google Calendar
            $googleService = new GoogleService(
                config('google.app_id'),
                config('google.app_secret'),
                config('google.app_callback')
            );

            $googleEventData = [
                'summary' => $event->title,
                'description' => $event->description,
                'start' => ['dateTime' => Carbon::parse($event->start)->toRfc3339String()],
                'end' => ['dateTime' => Carbon::parse($event->end)->toRfc3339String()],
            ];

            $googleService->updateCalendarEvent($user->calendar_access_token, $event->google_event_id, 'primary', $googleEventData);

            return response()->json(['status' => 'success']);
        }

        return response()->json(['status' => 'failed']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $event = CalenderGoogle::find($id);

        if ($event) {
            // Hapus event dari Google Calendar
            $googleService = new GoogleService(
                config('google.app_id'),
                config('google.app_secret'),
                config('google.app_callback')
            );

            $googleService->deleteCalendarEvent(Auth::user()->calendar_access_token, $event->google_event_id);

            // Hapus event dari database
            $event->delete();

            return response()->json(['status' => 'success']);
        }

        return response()->json(['status' => 'failed']);
    }

    public function resizeEvent(Request $request)
    {
        $data = $request->all();

        // Handle all-day event duration
        if (isset($data['is_all_day']) && $data['is_all_day'] == 1) {
            $data['end'] = Carbon::createFromTimestamp(strtotime($data['end']))->addDays(-1)->toDateString();
        }

        $user = Auth::user();
        $eventService = new EventService($user);
        $event = $eventService->update($data['id'], $data);

        if ($event) {
            // Update event di Google Calendar
            $googleService = new GoogleService(
                config('google.app_id'),
                config('google.app_secret'),
                config('google.app_callback')
            );

            $googleEventData = [
                'summary' => $event->title,
                'description' => $event->description,
                'start' => ['dateTime' => Carbon::parse($event->start)->toRfc3339String()],
                'end' => ['dateTime' => Carbon::parse($event->end)->toRfc3339String()],
            ];

            $googleService->updateCalendarEvent($user->calendar_access_token, $event->google_event_id, 'primary', $googleEventData);

            return response()->json(['status' => 'success']);
        }

        return response()->json(['status' => 'failed']);
    }

    private function isTokenExpired($expiresIn): bool
    {
        // Menggunakan try-catch jika format expires_in tidak valid
        try {
            return Carbon::parse($expiresIn)->subMinutes(5)->isPast();
        } catch (Exception $e) {
            // Jika parsing gagal, anggap token expired atau format tidak valid
            return true;
        }
    }
}
