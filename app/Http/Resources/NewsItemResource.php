<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NewsItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'title'        => $this->title,
            'summary'      => $this->summary,
            'link'         => $this->link,
            'source'       => $this->source,
            'published_at' => optional($this->published_at)->toIso8601String(),
            'sentiment'    => $this->sentiment,
            'is_urgent'    => (bool) $this->is_urgent,
            'analysis'     => $this->analysis,
            'archived'     => (bool) $this->archived,
        ];
    }
}
