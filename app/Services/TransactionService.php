<?php

namespace App\Services;

use App\Models\Stock;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TransactionService
{
    /**
     * Process checkout transaction atomically inside a database transaction.
     */
    public function processTransaction(array $data): Transaction
    {
        return DB::transaction(function () use ($data) {
            $items = $data['items'] ?? [];
            if (empty($items)) {
                throw ValidationException::withMessages([
                    'items' => 'Transaction must contain at least one item.',
                ]);
            }

            $calculatedSubtotal = 0;
            $preparedItems = [];

            foreach ($items as $item) {
                $qty = (int) ($item['quantity'] ?? 1);
                $unitPrice = (float) ($item['unit_price'] ?? 0);
                $itemSubtotal = $qty * $unitPrice;
                $calculatedSubtotal += $itemSubtotal;

                $preparedItems[] = [
                    'product_variant_id' => $item['product_variant_id'],
                    'quantity' => $qty,
                    'unit_price' => $unitPrice,
                    'subtotal' => $itemSubtotal,
                ];

                // Deduct stock if stock record exists
                if (! empty($item['product_variant_id']) && ! empty($data['outlet_id'])) {
                    $stock = Stock::where('product_variant_id', $item['product_variant_id'])
                        ->where('outlet_id', $data['outlet_id'])
                        ->lockForUpdate()
                        ->first();

                    if ($stock) {
                        if ($stock->quantity < $qty) {
                            throw ValidationException::withMessages([
                                'stock' => "Insufficient stock for variant ID {$item['product_variant_id']}.",
                            ]);
                        }
                        $stock->decrement('quantity', $qty);
                    }
                }
            }

            $taxAmount = $data['tax_amount'] ?? Number((string) ($calculatedSubtotal * 0.06));
            $donationAmount = $data['donation_amount'] ?? ($calculatedSubtotal > 0 ? 1.00 : 0.00);
            $discountAmount = $data['discount_amount'] ?? 0.00;
            $totalAmount = $calculatedSubtotal - $discountAmount + $taxAmount + $donationAmount;

            $transaction = Transaction::create([
                'outlet_id' => $data['outlet_id'],
                'cashier_id' => $data['cashier_id'],
                'member_id' => $data['member_id'] ?? null,
                'order_number' => $data['order_number'] ?? ('FO'.rand(1000, 9999)),
                'table_number' => $data['table_number'] ?? '01',
                'people_count' => $data['people_count'] ?? 2,
                'order_type' => $data['order_type'] ?? 'dine_in',
                'subtotal' => $calculatedSubtotal,
                'discount_amount' => $discountAmount,
                'tax_amount' => $taxAmount,
                'donation_amount' => $donationAmount,
                'total_amount' => $totalAmount,
                'payment_method' => $data['payment_method'] ?? 'card',
                'payment_status' => $data['payment_status'] ?? 'paid',
                'status' => 'completed',
            ]);

            foreach ($preparedItems as $prep) {
                $transaction->items()->create($prep);
            }

            return $transaction->load(['items.variant.product', 'cashier']);
        });
    }

    /**
     * Void a transaction and restore stock.
     */
    public function voidTransaction(Transaction $transaction, string $voidedById, string $reason): Transaction
    {
        return DB::transaction(function () use ($transaction, $voidedById, $reason) {
            if ($transaction->status === 'voided') {
                throw ValidationException::withMessages([
                    'status' => 'Transaction has already been voided.',
                ]);
            }

            $transaction->update([
                'status' => 'voided',
                'voided_by' => $voidedById,
                'voided_reason' => $reason,
            ]);

            // Restore stocks
            foreach ($transaction->items as $item) {
                Stock::where('product_variant_id', $item->product_variant_id)
                    ->where('outlet_id', $transaction->outlet_id)
                    ->increment('quantity', $item->quantity);
            }

            return $transaction;
        });
    }
}
