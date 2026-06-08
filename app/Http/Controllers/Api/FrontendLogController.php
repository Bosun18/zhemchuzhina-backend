<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FrontendLogController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'type' => 'required|in:error,unhandled_rejection',
            'message' => 'required|string|max:1000',
            'stack' => 'nullable|string|max:5000',
            'url' => 'nullable|string|max:500',
        ]);

        Log::channel('frontend')->error('[Frontend] '.$data['message'], [
            'type' => $data['type'],
            'stack' => $data['stack'] ?? null,
            'url' => $data['url'] ?? null,
            'ip' => $request->ip(),
            'ua' => $request->userAgent(),
        ]);

        return response()->json(['ok' => true]);
    }
}
