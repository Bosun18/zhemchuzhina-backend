<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\News;

class NewsController extends Controller
{
    public function index()
    {
        $news = News::where('is_published', true)
            ->orderByDesc('published_at')
            ->get()
            ->map(fn (News $item) => $this->formatNews($item));

        return response()->json($news);
    }

    public function show(News $news)
    {
        abort_unless($news->is_published, 404);

        return response()->json($this->formatNews($news));
    }

    private function formatNews(News $news): array
    {
        return [
            'id' => $news->id,
            'title' => $news->title,
            'content' => $news->content,
            'image' => $news->image,
            'published_at' => $news->published_at,
        ];
    }
}
