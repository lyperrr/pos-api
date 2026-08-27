<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TransactionResource;
use App\Services\OrderLineService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderLineController extends Controller
{
    public function __construct(
        protected OrderLineService $orderLineService
    ) {}

    /**
     * Display live active order line cards (#FO027, #FO028, #FO019).
     */
    public function index(Request $request): JsonResponse
    {
        $outletId = $request->query('outlet_id');
        $typeFilter = $request->query('order_type', 'all');

        $orders = $this->orderLineService->getLiveOrders($outletId, $typeFilter);

        return response()->json([
            'success' => true,
            'data' => TransactionResource::collection($orders),
        ]);
    }
}
