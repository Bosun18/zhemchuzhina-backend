<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class RoomController extends Controller
{
    public function index()
    {
        $rooms = Room::active()
            ->with('roomType')
            ->orderBy('number')
            ->get()
            ->map(fn ($room) => $this->formatRoom($room));

        return response()->json($rooms);
    }

    public function show(Room $room)
    {
        $room->load('roomType');

        return response()->json($this->formatRoom($room));
    }

    public function availability(Request $request)
    {
        $request->validate([
            'check_in' => 'required|date|after_or_equal:today',
            'check_out' => 'required|date|after:check_in',
        ]);

        $checkIn = $request->date('check_in');
        $checkOut = $request->date('check_out');

        // Найти занятые номера на эти даты
        $busyRoomIds = Booking::whereNotIn('status', ['cancelled'])
            ->where(function ($query) use ($checkIn, $checkOut) {
                $query->where(function ($q) use ($checkIn, $checkOut) {
                    $q->where('check_in', '<', $checkOut)
                        ->where('check_out', '>', $checkIn);
                });
            })
            ->pluck('room_id');

        $rooms = Room::active()
            ->with('roomType')
            ->orderBy('number')
            ->get()
            ->map(fn ($room) => array_merge(
                $this->formatRoom($room),
                ['is_available' => ! $busyRoomIds->contains($room->id)]
            ));

        return response()->json($rooms);
    }

    public function calendar(Request $request)
    {
        $request->validate([
            'from' => 'required|date',
            'to' => 'required|date|after:from|before_or_equal:'.Carbon::parse($request->input('from'))->addMonths(3)->toDateString(),
        ]);

        $from = $request->date('from');
        $to = $request->date('to');

        $rooms = Room::active()
            ->with([
                'roomType',
                'bookings' => function ($query) use ($from, $to) {
                    $query->whereIn('status', ['pending', 'confirmed'])
                        ->where('check_in', '<', $to)
                        ->where('check_out', '>', $from)
                        ->orderBy('check_in');
                },
            ])
            ->orderBy('number')
            ->get()
            ->map(fn ($room) => [
                'id' => $room->id,
                'number' => $room->number,
                'floor' => $room->floor,
                'type' => [
                    'id' => $room->roomType->id,
                    'name' => $room->roomType->name,
                    'max_guests' => $room->roomType->max_guests,
                ],
                'bookings' => $room->bookings->map(fn (Booking $booking) => [
                    'check_in' => $booking->check_in->toDateString(),
                    'check_out' => $booking->check_out->toDateString(),
                    'status' => $booking->status,
                ])->values()->all(),
            ]);

        return response()->json($rooms);
    }

    private function formatRoom(Room $room): array
    {
        return [
            'id' => $room->id,
            'number' => $room->number,
            'floor' => $room->floor,
            'type' => [
                'id' => $room->roomType->id,
                'name' => $room->roomType->name,
                'max_guests' => $room->roomType->max_guests,
                'description' => $room->roomType->description,
                'photos' => collect($room->roomType->photos ?? [])
                    ->map(fn ($path) => Storage::url($path))
                    ->values()
                    ->all(),
            ],
        ];
    }
}
