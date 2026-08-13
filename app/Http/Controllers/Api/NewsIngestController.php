<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NewsItem;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class NewsIngestController extends Controller
{
    /**
     * Endpoint yang dipanggil node "HTTP Request" di n8n
     * (letakkan setelah node "Gabung Analisis").
     *
     * Header wajib: X-API-Key: <NEWS_INGEST_TOKEN dari .env>
     * Body: satu objek berita, ATAU array berisi banyak objek berita.
     */
    public function store(Request $request): JsonResponse
    {
        if ($request->header('X-API-Key') !== config('services.n8n.ingest_token')) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $payload = $request->all();
        // Jika n8n mengirim satu item saja (bukan array), bungkus jadi array.
        $items = array_is_list($payload) ? $payload : [$payload];

        $validated = collect($items)->map(function ($item) use ($request) {
            return validator($item, [
                'title'        => 'required|string',
                'link'         => 'required|url',
                'source'       => 'nullable|string',
                'published_at' => 'nullable|date',
                'sentiment'    => 'required|in:positif,netral,negatif',
                'is_urgent'    => 'boolean',
                'analysis'     => 'nullable|string',
                'archived'     => 'boolean',
            ])->validate();
        });

        $saved = $validated->map(fn ($data) => NewsItem::updateOrCreate(
            ['link' => $data['link']],
            $data
        ));

        return response()->json([
            'message' => 'ok',
            'saved'   => $saved->count(),
        ], 201);
    }
}
