<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(private OrderService $orderService) {}

    public function index(Request $request)
    {
        [$perPage, $perPageOptions] = $this->resolvePagination($request);

        $search = $this->getSearch($request);

        $orders = $this->orderService->getOrders($perPage, $search);

        return view('orders.index', [
            'orders' => $orders,
            'perPage' => $perPage,
            'perPageOptions' => [5, 10, 25],
            'search' => $search ?? '',
        ]);
    }

    public function show(Order $order)
    {
        $order = $this->orderService->getOrder($order);

        return view('orders.view', [
            'order' => $order,
        ]);
    }

    public function syncStatus(Order $order)
    {
        $this->orderService->setShop($order->shop)->syncOrderStatus($order);

        return back()->with('success', __('Order status synced from the marketplace.'));
    }
}
