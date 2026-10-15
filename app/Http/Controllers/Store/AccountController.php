<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Order;
use App\Models\ReturnRequest;
use App\Services\Notifier;
use App\Support\StoreCustomer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/** Storefront customer account: register, login, profile, addresses, orders, wallet, returns. */
class AccountController extends Controller
{
    /* ---------------- Auth ---------------- */

    public function showLogin(Branch $branch)
    {
        if (StoreCustomer::current($branch)) {
            return redirect()->route('store.account', $branch);
        }

        return view('store.account.login');
    }

    public function login(Request $request, Branch $branch)
    {
        $data = $request->validate(['login' => ['required', 'string', 'max:150'], 'password' => ['required', 'string']]);
        $login = trim($data['login']);
        $field = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';
        if ($field === 'phone') {
            $login = preg_replace('/^\+?88/', '', preg_replace('/[^\d+]/', '', $login));
        }

        if (! Auth::guard('customer')->attempt(['branch_id' => $branch->id, $field => strtolower($login), 'password' => $data['password'], 'is_active' => true], $request->boolean('remember'))) {
            return back()->withInput($request->only('login'))->withErrors(['login' => 'মোবাইল/ইমেইল অথবা পাসওয়ার্ড সঠিক নয়।']);
        }
        $request->session()->regenerate();
        $customer = Auth::guard('customer')->user();
        $customer->forceFill(['last_login_at' => now()])->save();
        $this->mergeGuestWishlist($branch, $customer);

        return redirect()->to(session()->pull('customer.intended', route('store.account', $branch)))->with('success', "স্বাগতম, {$customer->name}!");
    }

    public function showRegister(Branch $branch)
    {
        return view('store.account.register');
    }

    public function register(Request $request, Branch $branch)
    {
        $request->merge([
            'phone' => preg_replace('/^\+?88/', '', preg_replace('/[^\d+]/', '', (string) $request->phone)),
            'email' => $request->filled('email') ? strtolower(trim($request->email)) : null,
        ]);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'regex:/^01[3-9]\d{8}$/', Rule::unique('customers')->where('branch_id', $branch->id)],
            'email' => ['nullable', 'email', 'max:150', Rule::unique('customers')->where('branch_id', $branch->id)],
            'password' => ['required', 'confirmed', Password::min(6)],
        ], ['phone.regex' => 'সঠিক মোবাইল নম্বর দিন।', 'phone.unique' => 'এই নম্বরে আগেই অ্যাকাউন্ট আছে — লগইন করুন।']);

        if (! $branch->withinLimit('max_customers')) {
            app(Notifier::class)->branch($branch, 'limit.customers', 'কাস্টমার লিমিট শেষ', 'নতুন কাস্টমার রেজিস্ট্রেশন বন্ধ — প্ল্যান আপগ্রেড করুন।', route('manager.billing.plans'), 'danger', ['app']);

            return back()->withInput()->with('warning', 'এই মুহূর্তে নতুন অ্যাকাউন্ট খোলা যাচ্ছে না। গেস্ট হিসেবে অর্ডার করতে পারেন।');
        }

        $customer = $branch->customers()->create($data);
        // Note: earlier guest orders are NOT auto-linked by phone (the number is not OTP-verified yet).

        Auth::guard('customer')->login($customer);
        $request->session()->regenerate();
        $this->mergeGuestWishlist($branch, $customer);
        ActivityLog::record('customer.registered', "New customer: {$customer->name} ({$customer->phone})", $customer, [], $branch->id);
        app(Notifier::class)->branch($branch, 'customer.new', 'নতুন কাস্টমার', "{$customer->name} — {$customer->phone}", null, 'info', ['app']);

        return redirect()->to(session()->pull('customer.intended', route('store.account', $branch)))->with('success', 'অ্যাকাউন্ট তৈরি হয়েছে!');
    }

    public function logout(Request $request, Branch $branch)
    {
        Auth::guard('customer')->logout();
        $request->session()->regenerateToken();

        return redirect()->route('store.home', $branch);
    }

    /* ---------------- Account pages ---------------- */

    public function dashboard(Branch $branch)
    {
        $customer = StoreCustomer::current($branch);

        return view('store.account.dashboard', [
            'customer' => $customer,
            'orders' => $customer->orders()->withCount('items')->paginate(10),
            'stats' => [
                'orders' => $customer->orders()->count(),
                'spent' => (float) $customer->orders()->where('status', '!=', 'cancelled')->sum('total'),
                'wishlist' => $customer->wishlist()->count(),
            ],
        ]);
    }

    public function order(Branch $branch, Order $order)
    {
        $customer = StoreCustomer::current($branch);
        abort_unless($order->branch_id === $branch->id && $order->customer_id === $customer->id, 404);

        return view('store.account.order', ['order' => $order->load(['items', 'histories', 'returns'])]);
    }

    public function profile(Branch $branch)
    {
        return view('store.account.profile', ['customer' => StoreCustomer::current($branch)]);
    }

    public function updateProfile(Request $request, Branch $branch)
    {
        $customer = StoreCustomer::current($branch);
        $request->merge(['email' => $request->filled('email') ? strtolower(trim($request->email)) : null]);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:150', Rule::unique('customers')->where('branch_id', $branch->id)->ignore($customer->id)],
        ]);
        $customer->update($data);

        return back()->with('success', 'প্রোফাইল আপডেট হয়েছে।');
    }

    public function updatePassword(Request $request, Branch $branch)
    {
        $customer = StoreCustomer::current($branch);
        $request->validate(['current_password' => ['required'], 'password' => ['required', 'confirmed', Password::min(6)]]);
        if (! Hash::check($request->current_password, $customer->password)) {
            return back()->withErrors(['current_password' => 'বর্তমান পাসওয়ার্ড সঠিক নয়।']);
        }
        $customer->update(['password' => $request->password]);

        return back()->with('success', 'পাসওয়ার্ড পরিবর্তন হয়েছে।');
    }

    public function addresses(Branch $branch)
    {
        return view('store.account.addresses', ['addresses' => StoreCustomer::current($branch)->addresses]);
    }

    public function saveAddress(Request $request, Branch $branch)
    {
        $customer = StoreCustomer::current($branch);
        $data = $request->validate([
            'id' => ['nullable', 'integer'],
            'label' => ['required', 'string', 'max:30'],
            'name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'regex:/^01[3-9]\d{8}$/'],
            'district' => ['nullable', 'string', 'max:60'],
            'upazila' => ['nullable', 'string', 'max:60'],
            'area' => ['nullable', 'string', 'max:100'],
            'address' => ['required', 'string', 'max:500'],
            'is_default' => ['nullable', 'boolean'],
        ]);
        $data['is_default'] = $request->boolean('is_default') || $customer->addresses()->doesntExist();
        if ($data['is_default']) {
            $customer->addresses()->update(['is_default' => false]);
        }
        $id = $data['id'] ?? null;
        unset($data['id']);
        $id ? $customer->addresses()->whereKey($id)->firstOrFail()->update($data) : $customer->addresses()->create($data);

        return back()->with('success', 'ঠিকানা সংরক্ষণ হয়েছে।');
    }

    public function deleteAddress(Branch $branch, CustomerAddress $address)
    {
        abort_unless($address->customer_id === StoreCustomer::current($branch)->id, 404);
        $address->delete();

        return back()->with('success', 'ঠিকানা মুছে ফেলা হয়েছে।');
    }

    public function wallet(Branch $branch)
    {
        $customer = StoreCustomer::current($branch);

        return view('store.account.wallet', ['customer' => $customer, 'transactions' => $customer->walletTransactions()->paginate(20)]);
    }

    public function returns(Branch $branch)
    {
        return view('store.account.returns', ['returns' => StoreCustomer::current($branch)->returns()->with('order')->get()]);
    }

    public function requestReturn(Request $request, Branch $branch, Order $order)
    {
        $customer = StoreCustomer::current($branch);
        abort_unless($order->branch_id === $branch->id && $order->customer_id === $customer->id, 404);
        if (! $order->canRequestReturn()) {
            return back()->with('warning', 'এই অর্ডারের জন্য রিটার্ন রিকোয়েস্ট করা যাবে না।');
        }

        $data = $request->validate([
            'type' => ['required', Rule::in(array_keys(ReturnRequest::TYPES))],
            'reason' => ['required', Rule::in(array_keys(ReturnRequest::REASONS))],
            'details' => ['nullable', 'string', 'max:1000'],
            'qty' => ['required', 'array'],
            'qty.*' => ['integer', 'min:0'],
        ]);
        $items = $order->items->mapWithKeys(fn ($i) => [$i->id => $i])->all();
        $lines = collect($data['qty'])->filter(fn ($q, $id) => $q > 0 && isset($items[$id]))
            ->map(fn ($q, $id) => ['order_item_id' => (int) $id, 'quantity' => min((int) $q, $items[$id]->quantity)])->values()->all();
        if (! $lines) {
            return back()->with('warning', 'অন্তত একটি পণ্যের পরিমাণ দিন।');
        }

        $return = $branch->returns()->create([
            'order_id' => $order->id, 'customer_id' => $customer->id, 'number' => ReturnRequest::generateNumber(),
            'type' => $data['type'], 'reason' => $data['reason'], 'details' => $data['details'] ?? null, 'items' => $lines,
        ]);
        $return->update(['refund_amount' => $return->itemsValue()]);
        ActivityLog::record('return.requested', "Return {$return->number} for {$order->order_number}", $return, [], $branch->id);
        app(Notifier::class)->branch($branch, 'return.new', "রিটার্ন রিকোয়েস্ট {$return->number}", "{$customer->name} — {$order->order_number}", route('manager.returns.show', $return), 'warning', ['app']);

        return redirect()->route('store.account.returns', $branch)->with('success', "রিটার্ন রিকোয়েস্ট {$return->number} জমা হয়েছে।");
    }

    /** Move session wishlist into the customer's saved wishlist. */
    protected function mergeGuestWishlist(Branch $branch, Customer $customer): void
    {
        foreach (session('wishlist.'.$branch->id, []) as $pid) {
            if ($branch->products()->whereKey($pid)->exists()) {
                $customer->wishlist()->firstOrCreate(['product_id' => $pid]);
            }
        }
        session()->forget('wishlist.'.$branch->id);
    }
}
