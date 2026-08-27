<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TransactionResource;
use App\Models\Transaction;
use App\Services\TransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function __construct(
        protected TransactionService $transactionService
    ) {}

    /**
     * Display transactions list for history.
     */
    public function index(Request $request): JsonResponse
    {
        $outletId = $request->query('outlet_id');
        $query = Transaction::query()->with(['items.variant.product', 'cashier']);

        if ($outletId) {
            $query->where('outlet_id', $outletId);
        }

        $transactions = $query->latest('created_at')->paginate(20);

        return response()->json([
            'success' => true,
            'data' => TransactionResource::collection($transactions->items()),
            'meta' => [
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage(),
                'total' => $transactions->total(),
            ],
        ]);
    }

    /**
     * Store (Place Order / Checkout) transaction.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'outlet_id' => 'required|uuid|exists:outlets,id',
            'cashier_id' => 'required|uuid|exists:users,id',
            'member_id' => 'nullable|uuid|exists:members,id',
            'order_number' => 'nullable|string|max:50',
            'table_number' => 'nullable|string|max:30',
            'people_count' => 'nullable|integer|min:1',
            'order_type' => 'nullable|string|in:dine_in,wait_list,take_away,served',
            'tax_amount' => 'nullable|numeric|min:0',
            'donation_amount' => 'nullable|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'payment_method' => 'required|string|in:cash,card,scan,midtrans,xendit',
            'payment_status' => 'nullable|string|in:paid,pending,failed',
            'items' => 'required|array|min:1',
            'items.*.product_variant_id' => 'required|uuid|exists:product_variants,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        $transaction = $this->transactionService->processTransaction($validated);

        return response()->json([
            'success' => true,
            'message' => 'Transaction created successfully.',
            'data' => new TransactionResource($transaction),
        ], 201);
    }

    /**
     * Void / Cancel transaction.
     */
    public function void(Request $request, Transaction $transaction): JsonResponse
    {
        $validated = $request->validate([
            'voided_by' => 'required|uuid|exists:users,id',
            'reason' => 'required|string|max:500',
        ]);

        $voided = $this->transactionService->voidTransaction(
            $transaction,
            $validated['voided_by'],
            $validated['reason']
        );

        return response()->json([
            'success' => true,
            'message' => 'Transaction voided successfully.',
            'data' => new TransactionResource($voided),
        ]);
    }
}
