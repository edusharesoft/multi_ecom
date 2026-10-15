{{-- Branch action buttons. expects $b --}}
<div class="d-inline-flex gap-1 flex-wrap justify-content-end">
    <form method="POST" action="{{ route('admin.branches.impersonate', $b) }}">@csrf
        <button class="btn btn-sm btn-light" title="Login as this branch"><i class="fa-solid fa-user-secret"></i></button>
    </form>
    @unless ($b->isVerified())
        <form method="POST" action="{{ route('admin.branches.verify', $b) }}">@csrf @method('PATCH')
            <button class="btn btn-sm btn-light text-success" title="Mark mobile verified"><i class="fa-solid fa-circle-check"></i></button>
        </form>
    @endunless
    @if ($b->isSuspended())
        <form method="POST" action="{{ route('admin.branches.activate', $b) }}">@csrf @method('PATCH')
            <button class="btn btn-sm btn-success" title="Activate"><i class="fa-solid fa-play"></i> Activate</button>
        </form>
    @else
        <form method="POST" action="{{ route('admin.branches.suspend', $b) }}" onsubmit="const r = prompt('Suspend reason (optional):'); if (r === null) return false; this.reason.value = r;">@csrf @method('PATCH')
            <input type="hidden" name="reason">
            <button class="btn btn-sm btn-outline-danger" title="Suspend"><i class="fa-solid fa-ban"></i></button>
        </form>
    @endif
</div>
