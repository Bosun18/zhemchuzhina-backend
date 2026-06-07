<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Gallery;

class GalleryController extends Controller
{
    public function index()
    {
        $gallery = Gallery::orderBy('sort_order')
            ->get(['id', 'image', 'caption', 'sort_order']);

        return response()->json($gallery);
    }
}
