<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Contracts\OrderServiceInterface;
use App\Contracts\OrderRepositoryInterface;
use App\Contracts\TradeRepositoryInterface;
use App\Http\Resources\OrderResource;
use App\Http\Requests\GetOrdersRequest;
use App\Http\Requests\CreateOrderRequest;
use App\Http\Requests\CancelOrderRequest;
use App\Models\Order;

class OrderController extends Controller
{
    public function __construct(
        protected OrderServiceInterface $service,
        protected OrderRepositoryInterface $repository,
        protected TradeRepositoryInterface $tradeRepository
    ) {}

    public function getAll(GetOrdersRequest $request)
    {
        if ($request->validated()) {

            $orders = $this->repository->getAll($request->all());

            return response()->json([
                'message' => 'Orders fetched successfully!',
                'data' => OrderResource::collection($orders)
            ]);
        }
    }

    public function create(CreateOrderRequest $request)
    {
        $validatedData = $request->validated();
        
        $order = $this->service->create($validatedData);

        return response()->json([
            'message' => 'Order created successfully!',
            'data' => [
                'order' => new OrderResource($order),
                'user' => $order->user->load('assets')
            ]
        ]);
    }

    public function cancel(int $id, Request $request)
    {
        $order = $this->service->cancel($id);

        return response()->json([
            'message' => 'Order cancelled successfully!',
            'data' => [
                'order' => new OrderResource($order),
                'user' => $order->user->load('assets')
            ]
        ]);
    }

    public function getAllTrades(Request $request)
    {
        $trades = $this->tradeRepository->getAll($request->all());

        return response()->json([
                'message' => 'Trades fetched successfully!',
                'data' => $trades
            ]);
    }
}
