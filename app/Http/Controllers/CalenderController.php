<?php

namespace App\Http\Controllers;

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

        return view('calenders.list', [
            'OAuth2Client' => $googleService->getAuthUrl(),
        ]);
    }

    public function syncCalendar(Request $request)
    {
        // Mengambil access token dan refresh token dari database user
        $user = Auth::user();
        $accessToken = $user->calendar_access_token;
        $refreshToken = $user->calendar_refresh_token; // Pastikan refresh token juga tersimpan

        $googleService = new GoogleService(
            config('google.app_id'),
            config('google.app_secret'),
            config('google.app_callback')
        );

        // Cek apakah access token kedaluwarsa
        if ($this->isTokenExpired($accessToken)) {
            // Jika access token kedaluwarsa, gunakan refresh token untuk mendapatkan token baru
            $newTokenData = $googleService->refreshAccessToken($refreshToken);

            // Perbarui access token yang baru di database
            $accessToken = $newTokenData['access_token'];
            $expirationTime = Carbon::now()->addSeconds($newTokenData['expires_in']);
            DB::table('users')->where('id', $user->id)->update([
                'calendar_access_token' => $accessToken,
                'expires_in' => $expirationTime->format('Y-m-d H:i:s'),
            ]);

            // Jika refresh token baru diberikan, simpan juga refresh token yang baru
            if (isset($newTokenData['refresh_token'])) {
                $refreshToken = $newTokenData['refresh_token'];
                DB::table('users')->where('id', $user->id)->update([
                    'calendar_refresh_token' => $refreshToken,
                ]);
            }
        }

        // Setelah memperbarui token, gunakan access token yang baru untuk sinkronisasi
        $syncResult = $googleService->syncCalendarEvents($accessToken);

        $events = $syncResult['events'];
        $nextSyncToken = $syncResult['nextSyncToken'];

        // Simpan event baru atau update ke database
        foreach ($events as $event) {
            $existingEvent = CalenderGoogle::where('event_id', $event['id'])->first();

            if (!$existingEvent) {
                // Simpan event baru
                CalenderGoogle::create([
                    'event_id' => $event['id'],
                    'user_id' => $user->id,
                    'title' => $event['summary'] ?? '',
                    'description' => $event['description'] ?? '',
                    'start' => Carbon::parse($event['start']['dateTime'])->toDateTimeString(),
                    'end' => Carbon::parse($event['end']['dateTime'])->toDateTimeString(),
                    'is_all_day' => isset($event['start']['date']),
                ]);
            }
        }

        return back();
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
        $data = $request->all();
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
        $data = $request->all();
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

    protected function isTokenExpired($accessToken)
    {
        // Di sini, kita cek apakah access token sudah kedaluwarsa atau tidak.
        if ($accessToken > now()->timestamp) {
            return true;
        } else {
            return false; // Implementasikan sesuai dengan kebutuhan Anda
        }
    }
}
