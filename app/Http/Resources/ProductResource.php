<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $defaultVariant = $this->variants->first();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'category' => $this->category ? strtolower($this->category->slug ?? $this->category->name) : 'all',
            'category_label' => $this->category ? $this->category->name : 'General',
            'price' => (float) $this->base_price,
            'image' => $this->image ?? 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=500&auto=format&fit=crop&q=80',
            'is_special' => (bool) $this->is_special,
            'variant_id' => $defaultVariant ? $defaultVariant->id : null,
            'barcode' => $this->barcode,
        ];
    }
}
