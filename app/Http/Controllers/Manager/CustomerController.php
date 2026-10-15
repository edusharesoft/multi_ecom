<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Customer;
use App\Support\Tenancy;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

/** CRM: customers, groups, segments, wallet, loyalty points, notes. */
class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $branch = Tenancy::branch();
        $customers = $branch->customers()
            ->withCount(['orders as orders_count' => fn ($q) => $q->where('status', '!=', 'cancelled')])
            ->withSum(['orders as spent' => fn ($q) => $q->where('status', '!=', 'cancelled')], 'total')
            ->withMax('orders as last_order_at', 'created_at')
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($w) => $w->where('name', 'like', '%'.$request->q.'%')->orWhere('phone', 'like', '%'.$request->q.'%')->orWhere('email', 'like', '%'.$request->q.'%')))
            ->when($request->filled('group'), fn ($q) => $q->where('group', $request->group))
            ->when($request->input('sort') === 'spent', fn ($q) => $q->orderByDesc('spent'), fn ($q) => $q->latest())
            ->get();

        foreach ($customers as $c) {
            $c->setRelation('branch', $branch);
            $c->seg = $c->segment((float) $c->spent, (int) $c->orders_count, $c->last_order_at ?? false);   // false = no orders (skips a query per customer)
        }
        $segmentCounts = $customers->countBy('seg');
        if ($request->filled('segment')) {
            $customers = $customers->where('seg', $request->segment)->values();
        }

        if ($request->input('export') === 'csv' && Tenancy::can('customers.export')) {
            return response()->streamDownload(function () use ($customers) {
                $out = fopen('php://output', 'w');
                fputcsv($out, ['Name', 'Phone', 'Email', 'Group', 'Segment', 'Orders', 'Spent', 'Wallet', 'Points', 'Joined']);
                foreach ($customers as $c) {
                    fputcsv($out, [$c->name, $c->phone, $c->email, $c->group, $c->seg, $c->orders_count, $c->spent, $c->wallet_balance, $c->loyalty_points, $c->created_at->toDateString()]);
                }
                fclose($out);
            }, 'customers-'.now()->format('Ymd').'.csv');
        }

        $page = max(1, (int) $request->page);

        return view('manager.customers.index', [
            'customers' => new \Illuminate\Pagination\LengthAwarePaginator($customers->forPage($page, 25), $customers->count(), 25, $page, ['path' => $request->url(), 'query' => $request->query()]),
            'segmentCounts' => $segmentCounts,
        ]);
    }

    public function show(Customer $customer)
    {
        $this->own($customer);
        $customer->load(['addresses', 'walletTransactions' => fn ($q) => $q->take(20), 'returns']);

        return view('manager.customers.show', [
            'customer' => $customer,
            'orders' => $customer->orders()->withCount('items')->get(),
            'segment' => $customer->segment(),
            'wishlist' => $customer->wishlist()->with('product')->get(),
            'reviews' => \App\Models\Review::where('customer_id', $customer->id)->with('product')->latest()->get(),
        ]);
    }

    public function store(Request $request)
    {
        $branch = Tenancy::branch();
        if (! $branch->withinLimit('max_customers')) {
            return back()->with('warning', 'প্ল্যানের কাস্টমার লিমিট শেষ।');
        }
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'regex:/^01[3-9]\d{8}$/', Rule::unique('customers')->where('branch_id', $branch->id)],
            'email' => ['nullable', 'email', Rule::unique('customers')->where('branch_id', $branch->id)],
            'group' => ['required', Rule::in(array_keys(Customer::GROUPS))],
        ]);
        $data['password'] = \Illuminate\Support\Str::random(16);   // customer can set it via "forgot password" later
        $customer = $branch->customers()->create($data);
        ActivityLog::record('customer.created', "Customer added: {$customer->name}", $customer);

        return redirect()->route('manager.customers.show', $customer)->with('success', 'কাস্টমার যোগ হয়েছে।');
    }

    public function update(Request $request, Customer $customer)
    {
        $this->own($customer);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['nullable', 'email', Rule::unique('customers')->where('branch_id', $customer->branch_id)->ignore($customer->id)],
            'group' => ['required', Rule::in(array_keys(Customer::GROUPS))],
            'notes' => ['nullable', 'string', 'max:5000'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $data['is_active'] = $request->boolean('is_active');
        $customer->update($data);

        return back()->with('success', 'কাস্টমার আপডেট হয়েছে।');
    }

    public function wallet(Request $request, Customer $customer)
    {
        $this->own($customer);
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'not_in:0'],
            'description' => ['required', 'string', 'max:255'],
        ]);
        try {
            $customer->adjustWallet((float) $data['amount'], 'manual', $data['description'].' — '.Tenancy::actorName());
        } catch (InvalidArgumentException $e) {
            return back()->with('warning', $e->getMessage());
        }
        ActivityLog::record('customer.wallet', "Wallet {$data['amount']} for {$customer->name}: {$data['description']}", $customer);

        return back()->with('success', 'ওয়ালেট আপডেট হয়েছে।');
    }

    public function points(Request $request, Customer $customer)
    {
        $this->own($customer);
        $points = (int) $request->validate(['points' => ['required', 'integer', 'not_in:0']])['points'];
        $customer->forceFill(['loyalty_points' => max(0, $customer->loyalty_points + $points)])->save();
        ActivityLog::record('customer.points', "Points {$points} for {$customer->name}", $customer);

        return back()->with('success', 'পয়েন্ট আপডেট হয়েছে।');
    }

    protected function own(Customer $customer): void
    {
        abort_unless($customer->branch_id === Tenancy::branch()->id, 404);
    }
}
