<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Customer;
use App\Models\Order;
use App\Models\PosHold;
use App\Models\PosSession;
use App\Services\PosService;
use App\Support\Tenancy;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use RuntimeException;

/** Point of Sale for the branch's physical shop. */
class PosController extends Controller
{
    public function __construct(protected PosService $pos) {}

    /** POS screen, or "open register" form when no session is open. */
    public function index()
    {
        $branch = Tenancy::branch();
        $session = $this->pos->currentSession($branch);

        if (! $session) {
            return view('manager.pos.open', [
                'warehouses' => $branch->warehouses()->where('is_active', true)->orderByDesc('is_default')->get(),
                'last' => $branch->posSessions()->whereNotNull('closed_at')->latest('closed_at')->first(),
            ]);
        }

        return view('manager.pos.screen', [
            'session' => $session->load('warehouse'),
            'holds' => PosHold::where('branch_id', $branch->id)->latest()->get(),
            'methods' => ['cash' => 'Cash', 'card' => 'Card', 'bkash' => 'bKash', 'nagad' => 'Nagad', 'rocket' => 'Rocket'],
        ]);
    }

    public function open(Request $request)
    {
        $branch = Tenancy::branch();
        $data = $request->validate([
            'warehouse_id' => ['required', Rule::exists('warehouses', 'id')->where('branch_id', $branch->id)],
            'opening_cash' => ['required', 'numeric', 'min:0'],
        ]);
        try {
            $this->pos->open($branch, (int) $data['warehouse_id'], (float) $data['opening_cash']);
        } catch (RuntimeException $e) {
            return back()->with('warning', $e->getMessage());
        }

        return redirect()->route('manager.pos.index');
    }

    /** Daily closing form (drawer count). */
    public function closing()
    {
        $session = $this->pos->currentSession(Tenancy::branch());
        if (! $session) {
            return redirect()->route('manager.pos.index');
        }

        return view('manager.pos.close', ['session' => $session->load(['warehouse', 'cashMovements']), 'totals' => $session->totals()]);
    }

    public function close(Request $request)
    {
        $session = $this->pos->currentSession(Tenancy::branch());
        abort_unless($session, 404);
        $data = $request->validate(['counted_cash' => ['required', 'numeric', 'min:0'], 'note' => ['nullable', 'string', 'max:500']]);
        $this->pos->close($session, (float) $data['counted_cash'], $data['note'] ?? null);

        return redirect()->route('manager.pos.session', $session)->with('success', 'রেজিস্টার বন্ধ হয়েছে — Daily Closing রিপোর্ট নিচে।');
    }

    public function cash(Request $request)
    {
        $session = $this->pos->currentSession(Tenancy::branch());
        abort_unless($session, 404);
        $data = $request->validate(['type' => ['required', Rule::in(['in', 'out'])], 'amount' => ['required', 'numeric', 'min:0.01'], 'note' => ['required', 'string', 'max:255']]);
        $movement = $session->cashMovements()->create($data + ['actor_name' => Tenancy::actorName()]);
        if (\App\Services\AccountingService::ready()) {
            $acc = app(\App\Services\AccountingService::class);
            $acc->post(Tenancy::branch(), $acc->accountFor(Tenancy::branch(), 'cash'), $data['type'], (float) $data['amount'],
                $data['type'] === 'in' ? 'drawer_in' : 'drawer_out', now(), $movement, 'POS drawer: '.$data['note']);
        }
        ActivityLog::record('pos.cash_'.$data['type'], "Cash {$data['type']} ৳ {$data['amount']}: {$data['note']}", $session);

        return back()->with('success', 'ক্যাশ '.($data['type'] === 'in' ? 'ইন' : 'আউট').' রেকর্ড হয়েছে।');
    }

    public function sessions()
    {
        return view('manager.pos.sessions', ['sessions' => Tenancy::branch()->posSessions()->with('warehouse')->withCount('orders')->latest('opened_at')->paginate(20)]);
    }

    public function session(PosSession $session)
    {
        abort_unless($session->branch_id === Tenancy::branch()->id, 404);

        return view('manager.pos.session', [
            'session' => $session->load(['warehouse', 'cashMovements']),
            'totals' => $session->closed_at ? ($session->summary ?? $session->totals()) : $session->totals(),
            'orders' => $session->orders()->withCount('items')->latest()->get(),
        ]);
    }

    /* ---------------- JSON endpoints used by the POS screen ---------------- */

    public function customers(Request $request)
    {
        $q = trim((string) $request->query('q'));
        if (mb_strlen($q) < 2) {
            return response()->json([]);
        }

        return response()->json(Tenancy::branch()->customers()->where('is_active', true)
            ->where(fn ($w) => $w->where('phone', 'like', "%{$q}%")->orWhere('name', 'like', "%{$q}%"))
            ->take(8)->get(['id', 'name', 'phone', 'wallet_balance', 'loyalty_points', 'group']));
    }

    public function quickCustomer(Request $request)
    {
        $branch = Tenancy::branch();
        $request->merge(['phone' => preg_replace('/^\+?88/', '', preg_replace('/[^\d+]/', '', (string) $request->phone))]);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'regex:/^01[3-9]\d{8}$/'],
        ]);
        $existing = $branch->customers()->where('phone', $data['phone'])->first();
        if ($existing) {
            return response()->json($existing->only(['id', 'name', 'phone', 'wallet_balance', 'loyalty_points', 'group']));
        }
        if (! $branch->withinLimit('max_customers')) {
            return response()->json(['message' => 'Customer limit of your plan reached.'], 422);
        }
        $c = $branch->customers()->create($data + ['password' => \Illuminate\Support\Str::random(16)]);

        return response()->json($c->only(['id', 'name', 'phone', 'wallet_balance', 'loyalty_points', 'group']));
    }

    public function checkout(Request $request)
    {
        $branch = Tenancy::branch();

        // same sale sent twice (lost response / offline sync retry) → the order made the first time,
        // checked first so a retry still succeeds after the register was closed
        $ref = $request->input('client_ref');
        if (is_string($ref) && preg_match('/^[A-Za-z0-9-]{1,40}$/', $ref) && ($existing = $branch->orders()->where('client_ref', $ref)->first())) {
            return $this->checkoutResponse($existing);
        }

        $session = $this->pos->currentSession($branch);
        if (! $session) {
            return response()->json(['message' => 'POS session is closed. রেজিস্টার খুলে আবার চেষ্টা করুন।'], 409);
        }
        $data = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer'],
            'items.*.variant_id' => ['nullable', 'integer'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.price' => ['nullable', 'numeric', 'min:0'],
            'customer_id' => ['nullable', 'integer'],
            'discount_type' => ['nullable', Rule::in(['fixed', 'percent'])],
            'discount_value' => ['nullable', 'numeric', 'min:0'],
            'method' => ['required', Rule::in(['cash', 'card', 'bkash', 'nagad', 'rocket'])],
            'tendered' => ['nullable', 'numeric', 'min:0'],
            'reference' => ['nullable', 'string', 'max:100'],
            'note' => ['nullable', 'string', 'max:255'],
            'hold_id' => ['nullable', 'integer'],
            // Phase 10 offline POS
            'client_ref' => ['nullable', 'string', 'max:40', 'regex:/^[A-Za-z0-9-]+$/'],
            'offline' => ['nullable', 'boolean'],
            'offline_at' => ['nullable', 'date'],
            'client_total' => ['nullable', 'numeric', 'min:0'],
            'seller' => ['nullable', 'string', 'max:100'],
        ]);

        $customer = ! empty($data['customer_id']) ? $branch->customers()->find($data['customer_id']) : null;
        // an offline sale must have happened in the past (≥ 3 s ago, ≤ 30 days) — "offline" skips the stock check
        $soldAt = null;
        try {
            $soldAt = ! empty($data['offline_at']) ? \Carbon\Carbon::parse($data['offline_at'])->timezone(config('app.timezone')) : null;
        } catch (\Throwable) {
        }
        $offline = ! empty($data['offline']) && $soldAt && $soldAt->lte(now()->subSeconds(3)) && $soldAt->gte(now()->subDays(30));
        $note = $data['note'] ?? null;
        if ($offline) {
            $note = trim(($note ? $note.' · ' : '').'অফলাইন বিক্রি '.$soldAt->format('d M Y h:i A')
                .(! empty($data['seller']) ? ' · বিক্রেতা: '.$data['seller'] : '')
                .(isset($data['tendered']) ? ' · নেওয়া ৳'.$data['tendered'] : ''));
        }

        try {
            $order = $this->pos->checkout($branch, $session, $data['items'], $customer,
                ['type' => $data['discount_type'] ?? 'fixed', 'value' => $data['discount_value'] ?? 0],
                ['method' => $data['method'], 'tendered' => $data['tendered'] ?? null, 'reference' => $data['reference'] ?? null],
                $note, ['client_ref' => $data['client_ref'] ?? null, 'offline' => $offline, 'sold_at' => $offline ? $soldAt : null]);
        } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {   // must stay before RuntimeException (it is a subclass)
            // two tabs / devices synced the same sale at the same moment
            $existing = ! empty($data['client_ref']) ? $branch->orders()->where('client_ref', $data['client_ref'])->first() : null;
            if (! $existing) {
                throw $e;
            }

            return $this->checkoutResponse($existing);
        } catch (\Illuminate\Database\QueryException $e) {
            report($e);

            return response()->json(['message' => 'Database error — please try again.'], 500);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
        // offline sale whose price changed before it reached the server → visible on the order
        if ($offline && isset($data['client_total']) && abs((float) $data['client_total'] - (float) $order->total) >= 0.01) {
            $order->forceFill(['note' => trim(($order->note ? $order->note.' · ' : '').'⚠ দোকানে নেওয়া হয়েছে ৳'.number_format((float) $data['client_total'], 2).', সিস্টেমে ৳'.number_format((float) $order->total, 2))])->saveQuietly();
        }
        app(\App\Services\Messaging\OrderNotifier::class)->placed($order);
        if (! empty($data['hold_id'])) {
            PosHold::where('branch_id', $branch->id)->whereKey($data['hold_id'])->delete();
        }

        return $this->checkoutResponse($order);
    }

    protected function checkoutResponse(Order $order)
    {
        return response()->json([
            'order_number' => $order->order_number,
            'total' => (float) $order->total,
            'change' => (float) $order->change_due,
            'receipt_url' => route('manager.pos.receipt', $order),
        ]);
    }

    /**
     * Phase 10 — full sellable catalogue of the open register's warehouse, kept on the device
     * (IndexedDB) for instant search and offline selling. No cost prices.
     */
    public function catalog(Request $request)
    {
        $branch = Tenancy::branch();
        $warehouseId = $this->pos->currentSession($branch)?->warehouse_id;
        $stock = $warehouseId
            ? \App\Models\WarehouseStock::where('warehouse_id', $warehouseId)->get(['product_id', 'variant_id', 'quantity'])
                ->mapWithKeys(fn ($r) => [$r->product_id.'-'.$r->variant_id => (int) $r->quantity])
            : collect();

        $items = [];
        $branch->products()->with('variants')->orderBy('id')->chunk(500, function ($products) use (&$items, $stock, $branch, $warehouseId) {
            foreach ($products as $p) {
                $tax = (float) $p->tax_percent > 0 ? (float) $p->tax_percent : (float) $branch->tax_percent;
                foreach ($p->variants->isEmpty() ? [null] : $p->variants as $v) {
                    $v?->setRelation('product', $p);
                    $key = $p->id.'-'.($v?->id ?? 0);
                    $items[] = [
                        'p' => $p->id, 'v' => $v?->id ?? 0,
                        'n' => $p->name.($v ? ' — '.$v->name : ''),
                        's' => $p->sku, 'b' => $p->barcode, 'c' => $p->product_code,
                        'pr' => $v ? $v->effectivePrice() : $p->currentPrice(),
                        't' => $tax,
                        'st' => $warehouseId ? ($stock[$key] ?? 0) : (int) ($v ? $v->stock : $p->stock),
                        'ph' => $p->product_type === 'physical',
                    ];
                }
            }
        });

        return response()->json(['at' => now()->toIso8601String(), 'warehouse' => $warehouseId, 'items' => $items])
            ->header('Cache-Control', 'no-store');
    }

    public function hold(Request $request)
    {
        $data = $request->validate(['label' => ['required', 'string', 'max:100'], 'cart' => ['required', 'array']]);
        $h = PosHold::create(['branch_id' => Tenancy::branch()->id, 'label' => $data['label'], 'cart' => $data['cart'], 'actor_name' => Tenancy::actorName()]);

        return response()->json(['id' => $h->id, 'label' => $h->label]);
    }

    public function holds()
    {
        return response()->json(PosHold::where('branch_id', Tenancy::branch()->id)->latest()->get(['id', 'label', 'cart', 'actor_name', 'created_at']));
    }

    public function deleteHold(PosHold $hold)
    {
        abort_unless($hold->branch_id === Tenancy::branch()->id, 404);
        $hold->delete();

        return response()->json(['ok' => true]);
    }

    /** 80mm thermal receipt */
    public function receipt(Order $order)
    {
        abort_unless($order->branch_id === Tenancy::branch()->id, 404);

        return view('manager.pos.receipt', ['order' => $order->load(['items', 'branch', 'customer'])]);
    }

    /* ---------------- Return / Exchange ---------------- */

    public function returns(Request $request)
    {
        $order = null;
        if ($request->filled('invoice')) {
            $order = Tenancy::branch()->orders()->with('items')->where('order_number', strtoupper(trim($request->invoice)))->first();
            if (! $order) {
                session()->now('warning', 'ইনভয়েস পাওয়া যায়নি।');
            }
        }

        return view('manager.pos.returns', ['order' => $order]);
    }

    public function processReturn(Request $request, Order $order)
    {
        $data = $request->validate([
            'qty' => ['required', 'array'], 'qty.*' => ['integer', 'min:0'],
            'method' => ['required', Rule::in(['cash', 'wallet', 'card', 'bkash', 'nagad'])],
            'note' => ['nullable', 'string', 'max:255'],
            'exchange' => ['nullable', 'boolean'],
        ]);
        try {
            $amount = $this->pos->returnItems(Tenancy::branch(), $order, $data['qty'], $data['method'], $data['note'] ?? null);
        } catch (RuntimeException $e) {
            return back()->with('warning', $e->getMessage());
        }
        $msg = "৳ ".number_format($amount, 2)." ফেরত ({$data['method']}) — স্টকে পণ্য যোগ হয়েছে।";
        if ($request->boolean('exchange')) {
            return redirect()->route('manager.pos.index')->with('success', $msg.' এখন বদলি পণ্যের নতুন বিল করুন।');
        }

        return redirect()->route('manager.pos.returns', ['invoice' => $order->order_number])->with('success', $msg);
    }
}
