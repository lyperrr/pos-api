<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'table_number' => $this->table_number,
            'people_count' => (int) $this->people_count,
            'order_type' => $this->order_type,
            'subtotal' => (float) $this->subtotal,
            'tax_amount' => (float) $this->tax_amount,
            'donation_amount' => (float) $this->donation_amount,
            'discount_amount' => (float) $this->discount_amount,
            'total_amount' => (float) $this->total_amount,
            'payment_method' => $this->payment_method,
            'payment_status' => $this->payment_status,
            'status' => $this->status,
            'created_at' => $this->created_at?->toIso8601String(),
            'items' => $this->items->map(function ($item) {
                return [
                    'id' => $item->id,
                    'product_variant_id' => $item->product_variant_id,
                    'name' => $item->variant?->product?->name ?? 'Item',
                    'quantity' => (int) $item->quantity,
                    'unit_price' => (float) $item->unit_price,
                    'subtotal' => (float) $item->subtotal,
                ];
            }),
        ];
    }
}
