<?php

namespace App\Http\Services;

use App\Models\CalenderGoogle;
use Carbon\Carbon;

class EventService
{
    protected $user;
    public function __construct($user)
    {
        $this->user = $user;
    }

    public function create($data)
    {
        $event = new CalenderGoogle($data);
        $event->save();

        return $event;
    }
    public function update($id, $data)
    {
        $event = CalenderGoogle::find($id);
        $event->fill($data);
        $event->save();

        return $event;
    }

    public function allEvents($fillers)
    {
        $eventQuery = CalenderGoogle::where('user_id', $this->user->id);

        if ($fillers['start']) {
            $eventQuery->where('start', '>=', $fillers['start']);
        }

        if ($fillers['end']) {
            $eventQuery->where('end', '<=', $fillers['end']);
        }

        $events = $eventQuery->get();
        $data = [];

        foreach ($events as $event) {
            $eventData = [
                'id' => $event->id,
                'title' => $event->title,
                'description' => $event->description,
                'start' => $event->start,
                'end' => $event->end,
                'allDay' => (bool)$event->is_all_day,
            ];

            if ($event->is_all_day) {
                // Jika event adalah "All Day", set waktu hanya sebagai tanggal
                $eventData['start'] = Carbon::parse($event->start)->toDateString();
                $eventData['end'] = Carbon::parse($event->end)->toDateString();
            } else {
                // Jika bukan "All Day", pastikan waktu disertakan
                $eventData['start'] = Carbon::parse($event->start)->format('Y-m-d H:i:s');
                $eventData['end'] = Carbon::parse($event->end)->format('Y-m-d H:i:s');
            }

            $eventData['backgroundColor'] = '#fcba03';
            $eventData['borderColor'] = '#fcba03';
            $data[] = $eventData;
        }

        return $data;
    }
}
