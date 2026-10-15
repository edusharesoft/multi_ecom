<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\CourierAccount;
use App\Models\Order;
use App\Models\Shipment;
use App\Services\Courier\RedxDriver;
use App\Services\Courier\ShipmentService;
use App\Services\Courier\SteadfastDriver;
use App\Support\Tenancy;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use RuntimeException;
use Throwable;

class CourierController extends Controller
{
    public function __construct(protected ShipmentService $shipments) {}

    /* ---------------- settings ---------------- */

    public function settings()
    {
        $branch = Tenancy::branch();

        return view('manager.courier.settings', [
            'accounts' => $branch->courierAccounts()->get()->keyBy('courier'),
            'hasApi' => $branch->hasFeature('courier'),
        ]);
    }

    public function save(Request $request, string $courier)
    {
        abort_unless(isset(CourierAccount::COURIERS[$courier]) && $courier !== 'manual', 404);
        $branch = Tenancy::branch();
        if (! $branch->hasFeature('courier')) {
            return redirect()->route('manager.billing.plans')->with('warning', 'Courier API আপনার প্ল্যানে নেই।');
        }
        $fields = array_keys(CourierAccount::COURIERS[$courier][1]);
        $data = $request->validate([
            'mode' => ['required', Rule::in(['live', 'sandbox', 'demo'])],
            'is_active' => ['nullable', 'boolean'], 'is_default' => ['nullable', 'boolean'],
            'cred' => ['nullable', 'array'], 'cred.*' => ['nullable', 'string', 'max:500'],
        ]);
        // custom base URL may only point at the courier's own https host (no SSRF to internal hosts)
        $base = trim((string) ($data['cred']['base_url'] ?? ''));
        if ($base !== '') {
            $host = strtolower((string) parse_url($base, PHP_URL_HOST));
            $allowed = ['steadfast' => ['packzy.com', 'steadfast.com.bd'], 'pathao' => ['pathao.com'], 'redx' => ['redx.com.bd']][$courier];
            $okHost = collect($allowed)->contains(fn ($d) => $host === $d || str_ends_with($host, '.'.$d));
            if (parse_url($base, PHP_URL_SCHEME) !== 'https' || ! $okHost) {
                return back()->withInput()->with('warning', 'Custom API base URL অবশ্যই কুরিয়ারের নিজস্ব https ঠিকানা হতে হবে ('.implode(', ', $allowed).')।');
            }
        }
        $acc = CourierAccount::firstOrNew(['branch_id' => $branch->id, 'courier' => $courier]);
        $old = $acc->credentials ?? [];
        $new = [];
        foreach (array_merge($fields, ['base_url']) as $f) {
            $v = trim((string) ($data['cred'][$f] ?? ''));
            // blank secret = keep the saved one
            $new[$f] = $v !== '' ? $v : ($old[$f] ?? null);
        }
        $acc->fill(['credentials' => $new, 'mode' => $data['mode'], 'is_active' => (bool) ($data['is_active'] ?? false), 'is_default' => (bool) ($data['is_default'] ?? false)])->save();
        if ($acc->is_default) {
            CourierAccount::where('branch_id', $branch->id)->whereKeyNot($acc->id)->update(['is_default' => false]);
        }
        ActivityLog::record('courier.saved', "Courier settings saved: {$acc->label()} ({$acc->mode})");

        return back()->with('success', $acc->label().' সেটিংস সংরক্ষিত।');
    }

    public function test(string $courier)
    {
        try {
            $acc = CourierAccount::where('branch_id', Tenancy::branch()->id)->where('courier', $courier)->firstOrFail();

            return back()->with('success', $acc->label().': '.$this->shipments->driver($acc)->test());
        } catch (Throwable $e) {
            return back()->with('warning', $e->getMessage());
        }
    }

    public function areas(Request $request)
    {
        try {
            $acc = $this->shipments->account(Tenancy::branch(), 'redx');
            $q = mb_strtolower(trim((string) $request->query('q')));
            $areas = collect((new RedxDriver($acc))->areas())
                ->filter(fn ($a) => $q === '' || str_contains(mb_strtolower($a[1].' '.$a[2]), $q))->take(30)
                ->map(fn ($a) => ['id' => $a[0], 'name' => $a[1], 'district' => $a[2]])->values();

            return response()->json($areas);
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function fraudCheck(Request $request)
    {
        try {
            $acc = $this->shipments->account(Tenancy::branch(), 'steadfast');
            $r = (new SteadfastDriver($acc))->fraudCheck((string) $request->query('phone'));

            return response()->json($r ?? ['message' => 'No data']);
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /* ---------------- shipments ---------------- */

    public function index(Request $request)
    {
        $branch = Tenancy::branch();
        $shipments = $branch->shipments()->with('order:id,order_number,customer_name,customer_phone,status,total')
            ->when($request->filled('courier'), fn ($q) => $q->where('courier', $request->courier))
            ->when($request->filled('group'), fn ($q) => $q->where('status_group', $request->group))
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($w) => $w->where('tracking_code', 'like', '%'.$request->q.'%')->orWhere('consignment_id', 'like', '%'.$request->q.'%')
                ->orWhereHas('order', fn ($o) => $o->where('order_number', 'like', '%'.$request->q.'%')->orWhere('customer_phone', 'like', '%'.$request->q.'%'))))
            ->latest()->paginate(30)->withQueryString();
        $counts = $branch->shipments()->selectRaw('status_group, count(*) c, sum(cod_amount) cod')->groupBy('status_group')->get()->keyBy('status_group');

        return view('manager.courier.index', ['shipments' => $shipments, 'counts' => $counts]);
    }

    public function book(Request $request, Order $order)
    {
        $this->own($order);
        $data = $this->bookRules($request);
        try {
            $s = $this->shipments->book($order, $data['courier'], $data);
        } catch (RuntimeException $e) {
            return back()->withInput()->with('warning', $e->getMessage());
        } catch (Throwable $e) {
            report($e);

            return back()->withInput()->with('warning', 'কুরিয়ার সার্ভারে সমস্যা: '.$e->getMessage());
        }

        return back()->with('success', "{$s->courierLabel()} এ বুক হয়েছে — Tracking: ".($s->tracking_code ?: $s->consignment_id ?: '—'));
    }

    public function bulkBook(Request $request)
    {
        $data = $request->validate(['ids' => ['required', 'array', 'max:100'], 'ids.*' => ['integer'], 'courier' => ['required', Rule::in(['steadfast', 'pathao'])]]);
        $orders = Tenancy::branch()->orders()->whereIn('id', $data['ids'])->get();
        $ok = 0;
        $errors = [];
        foreach ($orders as $o) {
            try {
                $this->shipments->book($o, $data['courier'], []);
                $ok++;
            } catch (Throwable $e) {
                $errors[] = $o->order_number.': '.\Illuminate\Support\Str::limit($e->getMessage(), 80);
            }
        }

        return back()->with($errors ? 'warning' : 'success', "{$ok} টি অর্ডার কুরিয়ারে বুক হয়েছে।".($errors ? ' ব্যর্থ: '.implode(' | ', array_slice($errors, 0, 5)) : ''));
    }

    public function sync(Shipment $shipment)
    {
        $this->ownShipment($shipment);
        try {
            $this->shipments->sync($shipment);
        } catch (Throwable $e) {
            return back()->with('warning', $e->getMessage());
        }

        return back()->with('success', 'স্ট্যাটাস: '.$shipment->fresh()->status);
    }

    public function syncAll()
    {
        [$ok, $fail] = $this->shipments->syncOpen(Tenancy::branch());

        return back()->with('success', "{$ok} টি পার্সেল আপডেট হয়েছে".($fail ? ", {$fail} টি ব্যর্থ" : '').'।');
    }

    public function setStatus(Request $request, Shipment $shipment)
    {
        $this->ownShipment($shipment);
        $data = $request->validate(['group' => ['required', Rule::in(array_keys(Shipment::GROUPS))], 'note' => ['nullable', 'string', 'max:60']]);
        $this->shipments->setStatus($shipment, $data['group'], $data['note'] ?? null);

        return back()->with('success', 'শিপমেন্ট স্ট্যাটাস আপডেট হয়েছে।');
    }

    protected function bookRules(Request $request): array
    {
        return $request->validate([
            'courier' => ['required', Rule::in(array_keys(ShipmentService::DRIVERS))],
            'cod_amount' => ['nullable', 'numeric', 'min:0'],
            'weight' => ['nullable', 'numeric', 'min:0.1', 'max:50'],
            'note' => ['nullable', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:200'],
            'address' => ['nullable', 'string', 'max:250'],
            'area_id' => ['nullable', 'integer'],
            'courier_name' => ['nullable', 'required_if:courier,manual', 'string', 'max:60'],
            'tracking' => ['nullable', 'string', 'max:80'],
            'tracking_url' => ['nullable', 'url', 'max:250'],
            'charge' => ['nullable', 'numeric', 'min:0'],
        ]);
    }

    protected function own(Order $o): void
    {
        abort_unless($o->branch_id === Tenancy::branch()->id, 404);
    }

    protected function ownShipment(Shipment $s): void
    {
        abort_unless($s->branch_id === Tenancy::branch()->id, 404);
    }
}
