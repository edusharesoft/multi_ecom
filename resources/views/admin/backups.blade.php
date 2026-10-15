@extends('layouts.admin')
@section('title', __('Backup & Restore'))

@section('content')
<div class="row g-4">
    <div class="col-xl-8">
        <div class="panel">
            <div class="panel-head">Backups <span class="small text-muted fw-normal">storage/app/backups</span>
                <div class="d-flex gap-2">
                    <form method="POST" action="{{ route('admin.backups.create') }}">@csrf<button name="type" value="database" class="btn btn-sm btn-brand"><i class="fa-solid fa-database"></i> Database backup</button></form>
                    <form method="POST" action="{{ route('admin.backups.create') }}">@csrf<button name="type" value="uploads" class="btn btn-sm btn-outline-dark"><i class="fa-solid fa-images"></i> Uploads (zip)</button></form>
                </div>
            </div>
            <div class="table-responsive"><table class="table table-sm mb-0 small align-middle">
                <thead class="table-light"><tr><th>File</th><th>Type</th><th class="text-end">Size</th><th>Created</th><th></th></tr></thead>
                @forelse ($files as $f)
                    <tr>
                        <td><code>{{ $f['name'] }}</code></td>
                        <td><span class="badge bg-{{ $f['type'] === 'database' ? 'primary' : 'secondary' }}">{{ $f['type'] }}</span></td>
                        <td class="text-end">{{ number_format($f['size'] / 1048576, 2) }} MB</td>
                        <td>{{ \Illuminate\Support\Carbon::createFromTimestamp($f['time'])->timezone(config('app.timezone'))->format('d M Y h:i A') }}</td>
                        <td class="text-end text-nowrap">
                            <a href="{{ route('admin.backups.download', $f['name']) }}" class="btn btn-sm btn-light border"><i class="fa-solid fa-download"></i></a>
                            @if ($f['type'] === 'database')<button type="button" class="btn btn-sm btn-outline-warning" data-bs-toggle="collapse" data-bs-target="#rs{{ $loop->index }}">Restore</button>@endif
                            <form method="POST" action="{{ route('admin.backups.destroy', $f['name']) }}" class="d-inline" onsubmit="return confirm('Delete this backup?')">@csrf @method('DELETE')<button class="btn btn-sm btn-light text-danger"><i class="fa-solid fa-trash"></i></button></form>
                        </td>
                    </tr>
                    @if ($f['type'] === 'database')
                        <tr class="collapse" id="rs{{ $loop->index }}"><td colspan="5" class="bg-warning-subtle">
                            <form method="POST" action="{{ route('admin.backups.restore', $f['name']) }}" class="d-flex gap-2 align-items-center">@csrf
                                <span><i class="fa-solid fa-triangle-exclamation text-danger"></i> বর্তমান সব ডেটা এই ব্যাকআপ দিয়ে প্রতিস্থাপিত হবে (আগে একটি নিরাপত্তা কপি নেওয়া হবে)। নিশ্চিত করতে <strong>RESTORE</strong> লিখুন:</span>
                                <input name="confirm" class="form-control form-control-sm" style="width:130px" autocomplete="off" required>
                                <button class="btn btn-sm btn-danger">Restore now</button>
                            </form>
                        </td></tr>
                    @endif
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-5">No backups yet — প্রথম ব্যাকআপ নিন।</td></tr>
                @endforelse
            </table></div>
        </div>
    </div>
    <div class="col-xl-4">
        <form method="POST" action="{{ route('admin.backups.settings') }}" class="panel mb-3">@csrf
            <div class="panel-head">Automatic backup</div>
            <div class="panel-body small">
                <div class="form-check mb-2"><input type="hidden" name="backup_auto" value="0"><input type="checkbox" class="form-check-input" name="backup_auto" value="1" @checked($auto)><label class="form-check-label">প্রতি রাত ২:৩০ এ ব্যাকআপ (database + uploads)</label></div>
                <label class="form-label mb-0">শেষ কয়টি ব্যাকআপ রাখবে</label>
                <input type="number" name="backup_keep" value="{{ $keep }}" min="1" max="60" class="form-control form-control-sm mb-2">
                <button class="btn btn-sm btn-brand">Save</button>
                <p class="text-muted mt-2 mb-0">Scheduler চালু থাকতে হবে: <code>php artisan schedule:work</code> (Laragon) বা সার্ভারে cron <code>* * * * * php artisan schedule:run</code>। ম্যানুয়ালি: <code>php artisan backup:run --uploads --force</code></p>
            </div>
        </form>
        <div class="panel"><div class="panel-body small text-muted">
            <strong>পরামর্শ:</strong> ব্যাকআপ ফাইল ডাউনলোড করে Google Drive / অন্য কম্পিউটারে রাখুন — একই সার্ভারে থাকা ব্যাকআপ সার্ভার নষ্ট হলে কাজে আসে না। স্টোর মালিকরা নিজেদের ডেটা Manager → Security → “Export my store data” থেকে CSV হিসেবে নিতে পারেন।
        </div></div>
    </div>
</div>
@endsection
