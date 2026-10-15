<?php

namespace App\Services;

use App\Models\AccountCategory;
use App\Models\ActivityLog;
use App\Models\Attendance;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\Payroll;
use App\Models\PayrollItem;
use App\Support\Features;
use App\Support\Tenancy;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Payroll rules (simple, editable before payment):
 *   monthly salary: basic + allowance − (basic ÷ working days) × (absent + ½ half-day + unpaid leave days)
 *   daily wage:     rate × (present + late + ½ half-day) + allowance
 *   overtime:       hours × (monthly basic ÷ working days ÷ 8)  or  (daily rate ÷ 8)
 * Days without an attendance entry are treated as worked (so small shops need not mark every day).
 */
class HrmService
{
    public function __construct(protected AccountingService $accounting) {}

    public function generate(Branch $b, string $month, int $workingDays = 30): Payroll
    {
        if (! preg_match('/^\d{4}-\d{2}$/', $month)) {
            throw new RuntimeException('Invalid month.');
        }
        $start = Carbon::parse($month.'-01')->startOfMonth();
        $end = $start->copy()->endOfMonth();
        $workingDays = max(1, min(31, $workingDays));

        return DB::transaction(function () use ($b, $month, $start, $end, $workingDays) {
            $payroll = Payroll::firstOrCreate(['branch_id' => $b->id, 'month' => $month], ['status' => 'draft', 'working_days' => $workingDays, 'actor_name' => Tenancy::actorName()]);
            $payroll = Payroll::whereKey($payroll->id)->lockForUpdate()->first();   // serialise with pay()
            if ($payroll->status === 'paid') {
                throw new RuntimeException('এই মাসের বেতন আগেই পরিশোধ হয়েছে।');
            }
            $payroll->update(['working_days' => $workingDays]);
            $existing = $payroll->items()->get()->keyBy('employee_id');

            $employees = Employee::where('branch_id', $b->id)->where('is_active', true)
                ->where(fn ($q) => $q->whereNull('join_date')->orWhere('join_date', '<=', $end))->get();
            foreach ($employees as $e) {
                $old = $existing[$e->id] ?? null;
                if ($old?->paid_at) {
                    continue;   // already paid individually
                }
                $att = Attendance::where('employee_id', $e->id)->whereBetween('date', [$start->toDateString(), $end->toDateString()])->get();
                $present = $att->whereIn('status', ['present', 'late'])->count();
                $half = $att->where('status', 'half_day')->count();
                $absent = $att->where('status', 'absent')->count();
                $ot = (float) $att->sum('overtime_hours');
                $unpaid = $this->unpaidLeaveDays($e, $start, $end);

                if ($e->salary_type === 'daily') {
                    $rate = (float) $e->basic_salary;
                    $basic = round($rate * ($present + $half * 0.5), 2);
                    $deduction = 0;
                    $hourly = $rate / 8;
                } else {
                    $basic = (float) $e->basic_salary;
                    $daily = $basic / $workingDays;
                    $deduction = round($daily * ($absent + $half * 0.5 + $unpaid), 2);
                    $hourly = $daily / 8;
                }
                $item = $old ?? new PayrollItem(['payroll_id' => $payroll->id, 'employee_id' => $e->id]);
                $item->fill([
                    'present_days' => min(255, $present + $half), 'absent_days' => min(255, $absent), 'unpaid_leave_days' => min(255, $unpaid),
                    'overtime_hours' => $ot, 'basic' => $basic, 'allowance' => (float) $e->allowance,
                    'overtime_amount' => round($ot * $hourly, 2), 'deduction' => min($deduction, $basic),
                ]);
                $item->bonus ??= 0;
                $item->other_deduction ??= 0;
                $item->recalc();
                $item->save();
            }
            // drop draft lines of employees who are no longer active
            $payroll->items()->whereNull('paid_at')->whereNotIn('employee_id', $employees->pluck('id'))->delete();
            $this->total($payroll);
            ActivityLog::record('payroll.generated', "Payroll {$month} generated", $payroll);

            return $payroll->fresh('items.employee');
        });
    }

    protected function unpaidLeaveDays(Employee $e, Carbon $start, Carbon $end): int
    {
        $days = 0;
        foreach (LeaveRequest::where('employee_id', $e->id)->where('status', 'approved')->where('type', 'unpaid')
            ->where('from_date', '<=', $end->toDateString())->where('to_date', '>=', $start->toDateString())->get() as $l) {
            $from = $l->from_date->max($start);
            $to = $l->to_date->min($end);
            $days += (int) $from->diffInDays($to) + 1;
        }

        return $days;
    }

    public function total(Payroll $p): void
    {
        $p->update(['total_net' => round((float) $p->items()->sum('net'), 2)]);
    }

    /** Pay all unpaid lines (or the given ones) from an account; posts Salary expense vouchers when accounting is on. */
    public function pay(Payroll $p, ?int $accountId, ?array $itemIds = null): int
    {
        $b = Branch::findOrFail($p->branch_id);
        $useAccounting = $accountId && $b->hasFeature('accounting') && Features::ready('vouchers');
        $category = null;
        if ($useAccounting) {
            $this->accounting->ensureDefaults($b);
            $category = AccountCategory::firstOrCreate(['branch_id' => $b->id, 'kind' => 'expense', 'name' => 'Salary'], ['is_active' => true]);
        }
        $count = 0;
        DB::transaction(function () use ($p, $b, $accountId, $itemIds, $useAccounting, $category, &$count) {
            Payroll::whereKey($p->id)->lockForUpdate()->first();   // serialise with generate() / a second pay click
            $items = $p->items()->with('employee')->whereNull('paid_at')->when($itemIds, fn ($q) => $q->whereIn('id', $itemIds))->lockForUpdate()->get();
            foreach ($items as $item) {
                $voucher = null;
                if ($useAccounting && (float) $item->net > 0) {
                    $voucher = $this->accounting->createVoucher($b, [
                        'kind' => 'expense', 'account_category_id' => $category->id, 'account_id' => $accountId, 'amount' => (float) $item->net,
                        'voucher_date' => now()->toDateString(), 'party' => $item->employee?->name, 'reference' => 'Payroll '.$p->month,
                        'note' => "Salary {$p->month}: basic {$item->basic} + allowance {$item->allowance} + OT {$item->overtime_amount} + bonus {$item->bonus} − {$item->deduction} − {$item->other_deduction}",
                    ]);
                }
                $item->update(['paid_at' => now(), 'voucher_number' => $voucher?->number]);
                $count++;
            }
            if (! $p->items()->whereNull('paid_at')->exists()) {
                $p->update(['status' => 'paid', 'paid_at' => now()]);
            }
        });
        ActivityLog::record('payroll.paid', "Payroll {$p->month}: {$count} salary paid", $p);

        return $count;
    }
}
