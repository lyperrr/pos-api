<?php

namespace App\Services;

use App\Models\Transaction;
use Illuminate\Database\Eloquent\Collection;

class OrderLineService
{
    /**
     * Get live active order line items (In Kitchen, Wait List, Ready, Served).
     */
    public function getLiveOrders(?string $outletId = null, ?string $typeFilter = null): Collection
    {
        $query = Transaction::query()
            ->with(['items.variant.product'])
            ->where('status', '!=', 'voided');

        if ($outletId) {
            $query->where('outlet_id', $outletId);
        }

        if ($typeFilter && $typeFilter !== 'all') {
            $query->where('order_type', $typeFilter);
        }

        return $query->latest('created_at')->limit(20)->get();
    }
}
