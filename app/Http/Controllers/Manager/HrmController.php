<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\Payroll;
use App\Models\PayrollItem;
use App\Services\HrmService;
use App\Support\Tenancy;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use RuntimeException;

/** HRM: employees, daily attendance, leave, payroll. */
class HrmController extends Controller
{
    public function __construct(protected HrmService $hrm) {}

    /* ---------------- employees ---------------- */

    public function employees(Request $request)
    {
        $b = Tenancy::branch();

        return view('manager.hrm.employees', [
            'employees' => Employee::where('branch_id', $b->id)->with('staff:id,email')
                ->when($request->filled('q'), fn ($q) => $q->where(fn ($w) => $w->where('name', 'like', '%'.$request->q.'%')->orWhere('phone', 'like', '%'.$request->q.'%')->orWhere('code', $request->q)))
                ->when($request->input('status') === 'inactive', fn ($q) => $q->where('is_active', false), fn ($q) => $q->where('is_active', true))
                ->orderBy('name')->get(),
            'staff' => $b->staff()->orderBy('name')->get(['id', 'name', 'email']),
            'edit' => $request->filled('edit') ? Employee::where('branch_id', $b->id)->find($request->integer('edit')) : null,
        ]);
    }

    public function saveEmployee(Request $request, ?Employee $employee = null)
    {
        $b = Tenancy::branch();
        if ($employee?->exists) {
            $this->own($employee);
        }
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'code' => ['nullable', 'string', 'max:20'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:150'],
            'designation' => ['nullable', 'string', 'max:80'],
            'department' => ['nullable', 'string', 'max:80'],
            'join_date' => ['nullable', 'date'],
            'salary_type' => ['required', Rule::in(['monthly', 'daily'])],
            'basic_salary' => ['required', 'numeric', 'min:0', 'max:10000000'],
            'allowance' => ['nullable', 'numeric', 'min:0', 'max:10000000'],
            'staff_id' => ['nullable', Rule::exists('staff', 'id')->where('branch_id', $b->id)],
            'address' => ['nullable', 'string', 'max:255'],
            'emergency_contact' => ['nullable', 'string', 'max:120'],
            'is_active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'photo_file' => ['nullable', 'image', 'max:2048'],
        ]);
        unset($data['photo_file']);
        $data['allowance'] = (float) ($data['allowance'] ?? 0);
        $data['is_active'] = (bool) ($data['is_active'] ?? true);
        if ($f = $request->file('photo_file')) {
            if (! $b->hasStorageFor($f)) {
                return back()->withInput()->with('warning', 'স্টোরেজ লিমিট শেষ।');
            }
            $b->deleteUpload($employee?->photo);
            $data['photo'] = $b->storeUpload($f, "branches/{$b->id}/employees");
        }
        if ($employee?->exists) {
            $employee->update($data);
        } else {
            $employee = Employee::create($data + ['branch_id' => $b->id]);
            ActivityLog::record('employee.created', "Employee added: {$employee->name}", $employee);
        }

        return redirect()->route('manager.hrm.employees')->with('success', 'কর্মচারীর তথ্য সংরক্ষিত।');
    }

    /* ---------------- attendance ---------------- */

    public function attendance(Request $request)
    {
        $b = Tenancy::branch();
        $date = $request->filled('date') ? Carbon::parse($request->date)->toDateString() : now()->toDateString();
        $employees = Employee::where('branch_id', $b->id)->where('is_active', true)->orderBy('name')->get();

        return view('manager.hrm.attendance', [
            'date' => $date, 'employees' => $employees,
            'records' => Attendance::where('branch_id', $b->id)->whereDate('date', $date)->get()->keyBy('employee_id'),
            'onLeave' => LeaveRequest::where('branch_id', $b->id)->where('status', 'approved')->whereDate('from_date', '<=', $date)->whereDate('to_date', '>=', $date)->pluck('employee_id')->flip(),
        ]);
    }

    public function saveAttendance(Request $request)
    {
        $b = Tenancy::branch();
        $data = $request->validate([
            'date' => ['required', 'date', 'before_or_equal:today'],
            'rows' => ['required', 'array'],
            'rows.*.status' => ['nullable', Rule::in(array_keys(Attendance::STATUSES))],
            'rows.*.check_in' => ['nullable', 'date_format:H:i'],
            'rows.*.check_out' => ['nullable', 'date_format:H:i'],
            'rows.*.overtime_hours' => ['nullable', 'numeric', 'min:0', 'max:24'],
            'rows.*.note' => ['nullable', 'string', 'max:255'],
        ]);
        $ids = Employee::where('branch_id', $b->id)->pluck('id')->flip();
        $n = 0;
        DB::transaction(function () use ($data, $b, $ids, &$n) {
            foreach ($data['rows'] as $empId => $row) {
                if (! isset($ids[$empId])) {
                    continue;
                }
                if (empty($row['status'])) {
                    Attendance::where('employee_id', $empId)->whereDate('date', $data['date'])->delete();

                    continue;
                }
                Attendance::updateOrCreate(['employee_id' => (int) $empId, 'date' => $data['date']], [
                    'branch_id' => $b->id, 'status' => $row['status'], 'check_in' => $row['check_in'] ?? null, 'check_out' => $row['check_out'] ?? null,
                    'overtime_hours' => (float) ($row['overtime_hours'] ?? 0), 'note' => $row['note'] ?? null, 'actor_name' => Tenancy::actorName(),
                ]);
                $n++;
            }
        });

        return back()->with('success', "{$n} জনের হাজিরা সংরক্ষিত ({$data['date']})।");
    }

    public function attendanceReport(Request $request)
    {
        $b = Tenancy::branch();
        $month = preg_match('/^\d{4}-\d{2}$/', (string) $request->month) ? $request->month : now()->format('Y-m');
        $start = Carbon::parse($month.'-01');
        $end = $start->copy()->endOfMonth();
        $rows = Attendance::where('branch_id', $b->id)->whereBetween('date', [$start->toDateString(), $end->toDateString()])->get()
            ->groupBy('employee_id')->map(fn ($g) => $g->keyBy(fn ($a) => $a->date->day));

        return view('manager.hrm.attendance-report', [
            'month' => $month, 'days' => $end->day, 'start' => $start, 'rows' => $rows,
            'employees' => Employee::where('branch_id', $b->id)->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    /* ---------------- leave ---------------- */

    public function leaves()
    {
        $b = Tenancy::branch();

        return view('manager.hrm.leaves', [
            'leaves' => LeaveRequest::where('branch_id', $b->id)->with('employee')->latest()->paginate(30),
            'employees' => Employee::where('branch_id', $b->id)->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function storeLeave(Request $request)
    {
        $b = Tenancy::branch();
        $data = $request->validate([
            'employee_id' => ['required', Rule::exists('employees', 'id')->where('branch_id', $b->id)],
            'type' => ['required', Rule::in(array_keys(LeaveRequest::TYPES))],
            'from_date' => ['required', 'date'], 'to_date' => ['required', 'date', 'after_or_equal:from_date'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);
        $days = (int) Carbon::parse($data['from_date'])->diffInDays(Carbon::parse($data['to_date'])) + 1;
        if ($days > 90) {
            return back()->with('warning', 'একবারে সর্বোচ্চ ৯০ দিনের ছুটি।');
        }
        LeaveRequest::create($data + ['branch_id' => $b->id, 'days' => $days, 'status' => 'pending']);

        return back()->with('success', 'ছুটির আবেদন যোগ হয়েছে।');
    }

    public function decideLeave(Request $request, LeaveRequest $leave)
    {
        abort_unless($leave->branch_id === Tenancy::branch()->id, 404);
        $status = $request->validate(['status' => ['required', Rule::in(['approved', 'rejected'])]])['status'];
        DB::transaction(function () use ($leave, $status) {
            $leave->update(['status' => $status, 'decided_by' => Tenancy::actorName()]);
            if ($status === 'approved') {
                // mark those days as "leave" in attendance (without overwriting days already marked)
                for ($d = $leave->from_date->copy(); $d->lte($leave->to_date); $d->addDay()) {
                    Attendance::firstOrCreate(['employee_id' => $leave->employee_id, 'date' => $d->toDateString()],
                        ['branch_id' => $leave->branch_id, 'status' => 'leave', 'note' => LeaveRequest::TYPES[$leave->type].' leave', 'actor_name' => Tenancy::actorName()]);
                }
            }
        });

        return back()->with('success', 'ছুটির আবেদন '.($status === 'approved' ? 'অনুমোদিত' : 'বাতিল').'।');
    }

    /* ---------------- payroll ---------------- */

    public function payrolls()
    {
        $b = Tenancy::branch();

        return view('manager.hrm.payrolls', ['payrolls' => Payroll::where('branch_id', $b->id)->withCount('items')->orderByDesc('month')->get()]);
    }

    public function generatePayroll(Request $request)
    {
        $data = $request->validate(['month' => ['required', 'date_format:Y-m'], 'working_days' => ['required', 'integer', 'min:1', 'max:31']]);
        try {
            $p = $this->hrm->generate(Tenancy::branch(), $data['month'], (int) $data['working_days']);
        } catch (RuntimeException $e) {
            return back()->with('warning', $e->getMessage());
        }

        return redirect()->route('manager.hrm.payroll', $p)->with('success', 'বেতন শিট তৈরি হয়েছে — বোনাস/কর্তন ঠিক করে “Pay” চাপুন।');
    }

    public function payroll(Payroll $payroll)
    {
        $this->ownPayroll($payroll);
        $b = Tenancy::branch();

        return view('manager.hrm.payroll', [
            'payroll' => $payroll->load('items.employee'),
            'accounts' => $b->hasFeature('accounting') && \App\Support\Features::ready('accounts') ? $b->accounts()->where('is_active', true)->orderByDesc('is_default')->get() : collect(),
        ]);
    }

    public function updatePayroll(Request $request, Payroll $payroll)
    {
        $this->ownPayroll($payroll);
        $data = $request->validate([
            'items' => ['required', 'array'],
            'items.*.bonus' => ['nullable', 'numeric', 'min:0'], 'items.*.other_deduction' => ['nullable', 'numeric', 'min:0'],
            'items.*.deduction' => ['nullable', 'numeric', 'min:0'], 'items.*.note' => ['nullable', 'string', 'max:255'],
        ]);
        foreach ($payroll->items()->whereNull('paid_at')->get() as $item) {
            if (! isset($data['items'][$item->id])) {
                continue;
            }
            $r = $data['items'][$item->id];
            $item->fill(['bonus' => (float) ($r['bonus'] ?? 0), 'other_deduction' => (float) ($r['other_deduction'] ?? 0),
                'deduction' => (float) ($r['deduction'] ?? $item->deduction), 'note' => $r['note'] ?? null]);
            $item->recalc();
            $item->save();
        }
        $this->hrm->total($payroll);

        return back()->with('success', 'আপডেট হয়েছে।');
    }

    public function payPayroll(Request $request, Payroll $payroll)
    {
        $this->ownPayroll($payroll);
        $b = Tenancy::branch();
        $data = $request->validate([
            'account_id' => ['nullable', Rule::exists('accounts', 'id')->where('branch_id', $b->id)],
            'item_ids' => ['nullable', 'array'], 'item_ids.*' => ['integer'],
        ]);
        $n = $this->hrm->pay($payroll, isset($data['account_id']) ? (int) $data['account_id'] : null, $data['item_ids'] ?? null);

        return back()->with('success', "{$n} জনের বেতন পরিশোধ হয়েছে".(! empty($data['account_id']) ? ' — Expenses এ Salary হিসেবে যোগ হয়েছে।' : '।'));
    }

    public function payslip(PayrollItem $item)
    {
        $item->load(['payroll', 'employee']);
        $this->ownPayroll($item->payroll);

        return view('manager.hrm.payslip', ['item' => $item, 'b' => Tenancy::branch()]);
    }

    protected function own(Employee $e): void
    {
        abort_unless($e->branch_id === Tenancy::branch()->id, 404);
    }

    protected function ownPayroll(Payroll $p): void
    {
        abort_unless($p->branch_id === Tenancy::branch()->id, 404);
    }
}
