<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $defaultVariant = $this->variants?->first();
        $defaultStock = $defaultVariant?->stocks?->first();

        return [
            'id' => $this->id,
            'tenant_id' => $this->tenant_id,
            'outlet_id' => $this->outlet_id,
            'category_id' => $this->category_id,
            'name' => $this->name,
            'category' => $this->category ? strtolower($this->category->slug ?? $this->category->name) : 'general',
            'category_label' => $this->category ? $this->category->name : 'General',
            'price' => (float) $this->base_price,
            'cost_price' => (float) ($this->base_price * 0.7),
            'image' => $this->image ?? 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=500&auto=format&fit=crop&q=80',
            'is_special' => (bool) $this->is_special,
            'is_active' => (bool) ($this->is_active ?? true),
            'variant_id' => $defaultVariant ? $defaultVariant->id : null,
            'barcode' => $this->barcode,
            'sku' => $defaultVariant ? $defaultVariant->sku : ('SKU-'.strtoupper(substr($this->id, 0, 6))),
            'stock' => $defaultStock ? (int) $defaultStock->quantity : 25,
            'min_stock' => $defaultStock ? (int) $defaultStock->min_stock_alert : 5,
            'created_at' => $this->created_at ? $this->created_at->toIso8601String() : now()->toIso8601String(),
        ];
    }
}
