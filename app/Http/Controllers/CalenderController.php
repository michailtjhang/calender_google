<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Services\EventService;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\CreateEventRequest;
use App\Http\Requests\UpdateEventRequest;
use App\Models\CalenderGoogle;

class CalenderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('calenders.list');
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
        $eventService = new EventService($user);

        $event = $eventService->create($data);
        if ($event) {
            return response()->json([
                'status' => 'success',
            ]);
        } else {
            return response()->json([
                'status' => 'failed',
            ]);
        }
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
        $eventService = new EventService($user);

        $event = $eventService->update($id, $data);
        if ($event) {
            return response()->json([
                'status' => 'success',
            ]);
        } else {
            return response()->json([
                'status' => 'failed',
            ]);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $id = CalenderGoogle::find($id);
            $id->delete();
            return response()->json([
                'status' => 'success',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'failed',
            ]);
        }
    }
}
