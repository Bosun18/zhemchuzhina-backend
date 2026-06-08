<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\NewBookingCreated;
use App\Models\Booking;
use App\Models\Room;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class BookingController extends Controller
{
    public function myBookings(Request $request)
    {
        $bookings = $request->user()->bookings()
            ->with('room.roomType')
            ->latest()
            ->get()
            ->map(fn (Booking $booking) => $this->formatBooking($booking));

        return response()->json($bookings);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'check_in' => 'required|date|after_or_equal:today',
            'check_out' => 'required|date|after:check_in',
            'guests_count' => 'required|integer|min:1',
            'comment' => 'nullable|string|max:1000',
        ]);

        $room = Room::with('roomType')->findOrFail($data['room_id']);

        if ($data['guests_count'] > $room->roomType->max_guests) {
            throw ValidationException::withMessages([
                'guests_count' => ["Максимальное число гостей для этого номера: {$room->roomType->max_guests}."],
            ]);
        }

        if ($this->hasOverlap($room->id, $data['check_in'], $data['check_out'])) {
            throw ValidationException::withMessages([
                'room_id' => ['Номер занят на выбранные даты.'],
            ]);
        }

        $booking = Booking::create([
            'user_id' => $request->user()->id,
            'room_id' => $room->id,
            'check_in' => $data['check_in'],
            'check_out' => $data['check_out'],
            'guests_count' => $data['guests_count'],
            'status' => 'pending',
            'comment' => $data['comment'] ?? null,
        ]);

        activity()->useLog('user')
            ->performedOn($booking)
            ->causedBy($request->user())
            ->log('Создал бронирование');

        $booking->load('room.roomType', 'user');

        foreach (Setting::get('notification_emails_booking', []) as $email) {
            Mail::to($email)->send(new NewBookingCreated($booking));
        }

        return response()->json($this->formatBooking($booking), 201);
    }

    public function cancel(Request $request, Booking $booking)
    {
        $user = $request->user();

        if (! $user->hasAnyRole(['admin', 'director', 'developer']) && $booking->user_id !== $user->id) {
            abort(403);
        }

        $booking->update(['status' => 'cancelled']);

        activity()->useLog('user')
            ->performedOn($booking)
            ->causedBy($request->user())
            ->log('Отменил бронирование');

        return response()->json($this->formatBooking($booking->fresh('room.roomType')));
    }

    public function index()
    {
        $bookings = Booking::with(['user:id,name,email', 'room.roomType'])
            ->latest()
            ->get()
            ->map(fn (Booking $booking) => $this->formatBooking($booking, forStaff: true));

        return response()->json($bookings);
    }

    public function confirm(Booking $booking)
    {
        $booking->update(['status' => 'confirmed']);

        return response()->json($this->formatBooking($booking->fresh(['user:id,name,email', 'room.roomType']), forStaff: true));
    }

    public function storeAdmin(Request $request)
    {
        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
            'room_id' => 'required|exists:rooms,id',
            'check_in' => 'required|date',
            'check_out' => 'required|date|after:check_in',
            'guests_count' => 'required|integer|min:1',
            'status' => 'sometimes|in:pending,confirmed,cancelled',
            'comment' => 'nullable|string|max:1000',
            'admin_comment' => 'nullable|string|max:1000',
        ]);

        if ($this->hasOverlap($data['room_id'], $data['check_in'], $data['check_out'])) {
            throw ValidationException::withMessages([
                'room_id' => ['Номер занят на выбранные даты.'],
            ]);
        }

        $booking = Booking::create([
            'user_id' => $data['user_id'],
            'room_id' => $data['room_id'],
            'check_in' => $data['check_in'],
            'check_out' => $data['check_out'],
            'guests_count' => $data['guests_count'],
            'status' => $data['status'] ?? 'confirmed',
            'comment' => $data['comment'] ?? null,
            'admin_comment' => $data['admin_comment'] ?? null,
        ]);

        return response()->json(
            $this->formatBooking($booking->load(['user:id,name,email', 'room.roomType']), forStaff: true),
            201
        );
    }

    private function hasOverlap(int $roomId, string $checkIn, string $checkOut): bool
    {
        return Booking::where('room_id', $roomId)
            ->whereNotIn('status', ['cancelled'])
            ->where('check_in', '<', $checkOut)
            ->where('check_out', '>', $checkIn)
            ->exists();
    }

    private function formatBooking(Booking $booking, bool $forStaff = false): array
    {
        $data = [
            'id' => $booking->id,
            'check_in' => $booking->check_in->toDateString(),
            'check_out' => $booking->check_out->toDateString(),
            'guests_count' => $booking->guests_count,
            'status' => $booking->status,
            'comment' => $booking->comment,
            'room' => [
                'id' => $booking->room->id,
                'number' => $booking->room->number,
                'type' => $booking->room->roomType->name,
            ],
        ];

        if ($forStaff) {
            $data['user'] = [
                'id' => $booking->user->id,
                'name' => $booking->user->name,
                'email' => $booking->user->email,
            ];
            $data['admin_comment'] = $booking->admin_comment;
        }

        return $data;
    }
}
