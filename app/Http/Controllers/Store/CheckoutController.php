<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Order;
use App\Services\CartService;
use App\Services\OrderService;
use App\Services\Payment\ShatajPayService;
use App\Support\StoreCustomer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use RuntimeException;
use Throwable;

class CheckoutController extends Controller
{
    public function show(Branch $branch, CartService $cart)
    {
        $items = $cart->items();
        if ($items->isEmpty()) {
            return redirect()->route('store.cart', $branch)->with('warning', 'আপনার কার্ট খালি।');
        }
        $customer = StoreCustomer::current($branch);
        if (! $customer && ! $branch->option('allow_guest_checkout')) {
            return redirect()->route('store.account.login', $branch)->with('warning', 'অর্ডার করতে লগইন করুন।');
        }

        return view('store.checkout', [
            'items' => $items,
            'totals' => $cart->totals($items, $customer),
            'methods' => $branch->paymentMethods(),
            'customer' => $customer ? ['customer_name' => $customer->name, 'customer_phone' => $customer->phone, 'customer_email' => $customer->email] : session('customer', []),
            'account' => $customer,
            'addresses' => $customer?->addresses ?? collect(),
        ]);
    }

    public function place(Request $request, Branch $branch, CartService $cart, OrderService $orders)
    {
        $customer = StoreCustomer::current($branch);
        if (! $customer && ! $branch->option('allow_guest_checkout')) {
            return redirect()->route('store.account.login', $branch);
        }

        $methods = array_keys($branch->paymentMethods());
        $request->merge(['customer_phone' => preg_replace('/^\+?88/', '', preg_replace('/[^\d+]/', '', (string) $request->input('customer_phone')))]);

        $data = $request->validate([
            'customer_name' => ['required', 'string', 'max:100'],
            'customer_phone' => ['required', 'regex:/^(\+?88)?01[3-9]\d{8}$/'],
            'customer_email' => ['nullable', 'email', 'max:150'],
            'shipping_address' => ['required', 'string', 'max:500'],
            'billing_address' => ['nullable', 'string', 'max:500'],
            'note' => ['nullable', 'string', 'max:500'],
            'payment_method' => ['required', Rule::in($methods)],
            'use_wallet' => ['nullable', 'boolean'],
            'save_address' => ['nullable', 'boolean'],
        ], ['customer_phone.regex' => 'সঠিক মোবাইল নম্বর দিন।']);

        $items = $cart->items();
        if ($items->isEmpty()) {
            return redirect()->route('store.cart', $branch)->with('warning', 'আপনার কার্ট খালি।');
        }
        foreach ($items as $item) {
            if (($item->product->product_type ?? 'physical') === 'physical' && $item->quantity > $item->stock) {
                return redirect()->route('store.cart', $branch)->with('warning', "{$item->product->name} — স্টকে মাত্র {$item->stock} টি আছে।");
            }
        }

        $wallet = $customer && $request->boolean('use_wallet') ? (float) $customer->wallet_balance : 0;
        $preview = $cart->totals($items, $customer, $data['customer_phone'], $wallet);
        if ($preview['coupon_error']) {
            $cart->setCoupon(null);

            return redirect()->route('store.cart', $branch)->with('warning', 'কুপন বাতিল: '.$preview['coupon_error']);
        }

        // SaaS plan limits for the branch
        if ($blocked = $this->planBlocks($branch, $preview['total'])) {
            return redirect()->route('store.cart', $branch)->with('warning', $blocked);
        }

        $billing = $data['billing_address'] ?? null;
        $saveAddress = $request->boolean('save_address');
        unset($data['use_wallet'], $data['save_address'], $data['billing_address']);

        try {
            $order = $orders->place($branch, $cart, $data + ['billing_address' => $billing ?: $data['shipping_address']], $customer, $wallet);
        } catch (RuntimeException|\InvalidArgumentException $e) {   // stock / coupon limit / wallet balance changed meanwhile
            return redirect()->route('store.cart', $branch)->with('warning', $e->getMessage());
        }

        if ($customer && $saveAddress && ! $customer->addresses()->where('address', $data['shipping_address'])->exists()) {
            $customer->addresses()->create(['name' => $data['customer_name'], 'phone' => $data['customer_phone'], 'address' => $data['shipping_address'], 'is_default' => $customer->addresses()->doesntExist()]);
        }

        session(['customer' => collect($data)->only(['customer_name', 'customer_phone', 'customer_email', 'shipping_address'])->all()]);
        session()->push('my_orders', $order->order_number);

        if (! $order->needsOnlinePayment()) {
            return redirect()->route('store.order.success', [$branch, $order])->with('success', 'অর্ডার সফলভাবে সম্পন্ন হয়েছে!');
        }

        if ($order->payment_method === 'shatajpay') {
            try {
                return redirect()->away(app(ShatajPayService::class)->initiate($order));
            } catch (Throwable $e) {
                Log::error('[ShatajPay] initiate failed: '.$e->getMessage(), ['order' => $order->order_number]);

                return redirect()->route('store.payment', [$branch, $order])
                    ->with('warning', 'ShatajPay এর সাথে সংযোগ করা যায়নি। আবার চেষ্টা করুন অথবা অন্য পদ্ধতি বেছে নিন।');
            }
        }

        return redirect()->route('store.payment', [$branch, $order]);
    }

    /** Payment page */
    public function payment(Branch $branch, Order $order)
    {
        $this->authorizeOrder($branch, $order);

        if (! $order->needsOnlinePayment()) {
            return redirect()->route('store.order.success', [$branch, $order]);
        }

        return view('store.payment', ['order' => $order->load('items'), 'methods' => $branch->paymentMethods()]);
    }

    public function pay(Request $request, Branch $branch, Order $order, ShatajPayService $shatajPay)
    {
        $this->authorizeOrder($branch, $order);
        abort_unless($order->needsOnlinePayment(), 409, 'This order does not need payment.');

        $methods = array_keys($branch->paymentMethods());
        $method = $request->validate(['payment_method' => ['required', Rule::in($methods)]])['payment_method'];

        if ($method === 'cod') {
            $order->update(['payment_method' => 'cod', 'payment_status' => 'unpaid']);

            return redirect()->route('store.order.success', [$branch, $order])->with('success', 'ক্যাশ অন ডেলিভারি নির্বাচন করা হয়েছে।');
        }

        if ($method === 'shatajpay') {
            $order->update(['payment_method' => 'shatajpay']);
            try {
                return redirect()->away($shatajPay->initiate($order));
            } catch (Throwable $e) {
                Log::error('[ShatajPay] initiate failed: '.$e->getMessage(), ['order' => $order->order_number]);

                return back()->with('warning', 'ShatajPay এর সাথে সংযোগ করা যায়নি। কিছুক্ষণ পর আবার চেষ্টা করুন অথবা অন্য পদ্ধতি বেছে নিন।');
            }
        }

        // bKash / Nagad manual Send Money
        $data = $request->validate([
            'sender_number' => ['required', 'regex:/^(\+?88)?01[3-9]\d{8}$/'],
            'transaction_id' => ['required', 'string', 'min:6', 'max:40', 'alpha_num', Rule::unique('orders', 'transaction_id')->ignore($order->id)],
        ], ['sender_number.regex' => 'সঠিক মোবাইল নম্বর দিন।', 'transaction_id.unique' => 'এই Transaction ID আগে ব্যবহার করা হয়েছে।']);

        DB::transaction(function () use ($order, $method, $data) {
            $order->update([
                'payment_method' => $method,
                'payment_status' => 'pending_verification',
                'sender_number' => $data['sender_number'],
                'transaction_id' => strtoupper($data['transaction_id']),
            ]);
            $order->payments()->create([
                'gateway' => $method,
                'reference' => $order->order_number.'-'.strtoupper(\Illuminate\Support\Str::random(5)),
                'amount' => $order->payable,
                'transaction_id' => strtoupper($data['transaction_id']),
                'status' => 'initiated',
                'payload' => ['sender_number' => $data['sender_number']],
            ]);
        });

        return redirect()->route('store.order.success', [$branch, $order])
            ->with('success', 'পেমেন্ট তথ্য জমা হয়েছে। ব্রাঞ্চ ম্যানেজার ভেরিফাই করলে অর্ডার কনফার্ম হবে।');
    }

    public function success(Branch $branch, Order $order)
    {
        $this->authorizeOrder($branch, $order);

        return view('store.success', ['order' => $order->load('items')]);
    }

    /** Track an order with order number + phone (works from any device). */
    public function track(Request $request, Branch $branch)
    {
        $order = null;
        if ($request->filled(['order_number', 'phone'])) {
            $phone = preg_replace('/^\+?88/', '', preg_replace('/[^\d+]/', '', $request->phone));
            $order = $branch->orders()->with('items')
                ->where('order_number', strtoupper(trim($request->order_number)))
                ->where('customer_phone', $phone)
                ->first();
            if ($order) {
                session()->push('my_orders', $order->order_number);
            } else {
                session()->now('warning', 'অর্ডার পাওয়া যায়নি।');
            }
        }

        return view('store.track', compact('order'));
    }

    /** Returns a message when the branch's plan does not allow a new order right now. */
    protected function planBlocks(Branch $branch, float $total): ?string
    {
        if ($branch->isReadOnly()) {
            return 'এই স্টোরে বর্তমানে অনলাইন অর্ডার সাময়িকভাবে বন্ধ আছে। দয়া করে পরে চেষ্টা করুন।';
        }
        if (! $branch->withinLimit('max_orders_month') || ! $branch->withinLimit('monthly_sales_limit', $total)) {
            app(\App\Services\Notifier::class)->branch($branch, 'limit.orders', 'অর্ডার লিমিট শেষ', 'এই মাসের অর্ডার/বিক্রির লিমিট শেষ হওয়ায় নতুন অর্ডার নেওয়া যাচ্ছে না। প্ল্যান আপগ্রেড করুন।', route('manager.billing.plans'), 'danger', ['app']);

            return 'এই স্টোর এই মুহূর্তে নতুন অর্ডার নিতে পারছে না। দয়া করে সরাসরি স্টোরে যোগাযোগ করুন।';
        }

        return null;
    }

    protected function authorizeOrder(Branch $branch, Order $order): void
    {
        abort_unless($order->branch_id === $branch->id, 404);

        // Signed links (used after returning from ShatajPay, where the browser session may be lost)
        if (request()->hasValidSignature()) {
            if (! in_array($order->order_number, session('my_orders', []), true)) {
                session()->push('my_orders', $order->order_number);
            }

            return;
        }

        // Logged-in customer who owns the order
        $customer = \App\Support\StoreCustomer::current($branch);
        if ($customer && $order->customer_id === $customer->id) {
            return;
        }

        abort_unless(in_array($order->order_number, session('my_orders', []), true), 403, 'Use "Track Order" with your phone number to view this order.');
    }
}
