@extends('layouts.manager')
@section('title', 'POS — Daily Closing')

@section('content')
@include('manager.pos._nav')
<div class="row g-4">
    <div class="col-lg-7">@include('manager.pos._summary', ['session' => $session, 'totals' => $totals])</div>
    <div class="col-lg-5">
        <form method="POST" action="{{ route('manager.pos.close') }}" class="panel" onsubmit="return confirm('রেজিস্টার বন্ধ করবেন?')">@csrf
            <div class="panel-head">Close Register</div>
            <div class="panel-body">
                <div id="offlineWarn" class="alert alert-danger small py-2 d-none"><i class="fa-solid fa-triangle-exclamation"></i> এই ডিভাইসে <strong></strong>টি অফলাইন বিক্রি এখনো সিস্টেমে যোগ হয়নি। আগে POS পাতায় গিয়ে নেট চালু রেখে সিঙ্ক করুন, নাহলে এগুলো আজকের হিসাবে আসবে না।</div>
                <p class="small mb-1">Expected cash in drawer: <strong>@money($totals['expected_cash'])</strong></p>
                <label class="form-label small mb-0">Counted cash ৳</label>
                <input type="number" step="0.01" min="0" name="counted_cash" id="counted" class="form-control form-control-lg mb-1" required autofocus>
                <div class="small mb-3" id="diff"></div>
                <textarea name="note" rows="2" class="form-control form-control-sm mb-3" placeholder="Note (optional)"></textarea>
                <button class="btn btn-danger w-100">Close & Print Z-Report</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/pos-offline.js') }}?v={{ \App\Http\Controllers\PwaController::VERSION }}"></script>
<script>
// Phase 10: warn before closing while offline sales from this device are still waiting
window.PosOffline && PosOffline.queued({{ (int) $session->branch_id }}).then(list => {
    if (!list.length) return;
    const w = document.getElementById('offlineWarn');
    w.querySelector('strong').textContent = list.length;
    w.classList.remove('d-none');
});
document.getElementById('counted').addEventListener('input', e => {
    const d = (+e.target.value || 0) - {{ (float) $totals['expected_cash'] }};
    const el = document.getElementById('diff');
    el.textContent = d === 0 ? 'Balanced ✓' : (d > 0 ? 'Over: ৳' : 'Short: ৳') + Math.abs(d).toFixed(2);
    el.className = 'small mb-3 ' + (d === 0 ? 'text-success' : 'text-danger');
});
</script>
@endpush
