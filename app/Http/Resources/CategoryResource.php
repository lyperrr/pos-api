<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug ?? strtolower(str_replace(' ', '_', $this->name)),
            'icon' => $this->icon ?? 'utensils',
            'item_count' => $this->products_count ?? $this->products()->count(),
        ];
    }
}
