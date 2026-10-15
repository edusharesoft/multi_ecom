<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use RuntimeException;

class OrderApiController extends ApiController
{
    public function index(Request $request)
    {
        $request->validate(['from' => ['nullable', 'date'], 'to' => ['nullable', 'date']]);
        $q =$this->branch()->orders()
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('payment_status'), fn ($q) => $q->where('payment_status', $request->payment_status))
            ->when($request->filled('from'), fn ($q) => $q->where('created_at', '>=', $request->date('from')->startOfDay()))
            ->when($request->filled('to'), fn ($q) => $q->where('created_at', '<=', $request->date('to')->endOfDay()))
            ->when($request->filled('phone'), fn ($q) => $q->where('customer_phone', $request->phone))
            ->orderByDesc('id');

        return $this->paginated($q->paginate($this->perPage()), fn ($o) => self::order($o));
    }

    public function show(string $number)
    {
        return ['data' => self::order($this->find($number)->load(['items', 'shipment']), true)];
    }

    public function status(Request $request, string $number, OrderService $orders)
    {
        $o = $this->find($number);
        $data = $request->validate(['status' => ['required', Rule::in(Order::STATUSES)], 'note' => ['nullable', 'string', 'max:255']]);
        try {
            $orders->changeStatus($o, $data['status'], 'API'.(! empty($data['note']) ? ': '.$data['note'] : ''));
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return ['data' => self::order($o->fresh(['items', 'shipment']), true)];
    }

    protected function find(string $number): Order
    {
        return $this->branch()->orders()->where('order_number', $number)->when(ctype_digit($number), fn ($q) => $q->orWhere(fn ($w) => $w->where('branch_id', $this->branch()->id)->whereKey((int) $number)))->firstOrFail();
    }
}
