<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ReviewController extends Controller
{
    public function index()
    {
        $reviews = Review::where('status', 'approved')
            ->with('user:id,name')
            ->latest()
            ->get()
            ->map(fn (Review $review) => [
                'id' => $review->id,
                'rating' => $review->rating,
                'text' => $review->text,
                'user' => $review->user->name,
                'admin_comment' => $review->admin_comment,
                'created_at' => $review->created_at,
            ]);

        return response()->json($reviews);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'booking_id' => 'required|integer|exists:bookings,id',
            'rating' => 'required|integer|min:1|max:10',
            'text' => 'required|string|max:2000',
        ]);

        $booking = Booking::where('id', $data['booking_id'])
            ->where('user_id', $request->user()->id)
            ->where('status', 'confirmed')
            ->first();

        if (! $booking) {
            throw ValidationException::withMessages([
                'booking_id' => ['Бронирование не найдено или ещё не подтверждено.'],
            ]);
        }

        if ($booking->review()->exists()) {
            throw ValidationException::withMessages([
                'booking_id' => ['Отзыв на это бронирование уже оставлен.'],
            ]);
        }

        $review = Review::create([
            'user_id' => $request->user()->id,
            'booking_id' => $booking->id,
            'rating' => $data['rating'],
            'text' => $data['text'],
            'status' => 'pending',
        ]);

        return response()->json([
            'id' => $review->id,
            'rating' => $review->rating,
            'text' => $review->text,
            'status' => $review->status,
        ], 201);
    }
}
