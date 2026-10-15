@php [$label, $repeat, $hasLimit] = \App\Support\StoreTheme::SECTIONS[$sec['type']]; @endphp
<div class="list-group-item small">
    <input type="hidden" data-f="id" value="{{ $sec['id'] }}">
    <input type="hidden" data-f="type" value="{{ $sec['type'] }}">
    <div class="d-flex align-items-center gap-2 flex-wrap">
        <i class="fa-solid fa-grip-vertical text-muted drag" style="cursor:grab"></i>
        <input type="hidden" data-f="enabled" value="0">
        <input type="checkbox" class="form-check-input m-0" data-f="enabled" value="1" @checked($sec['enabled']) title="Show">
        <strong style="min-width:190px">{{ $label }}</strong>
        @if ($sec['type'] === 'blog' && ! ($hasBlog ?? true))<span class="badge bg-secondary"><i class="fa-solid fa-lock"></i> plan</span>@endif
        @unless (in_array($sec['type'], ['hero', 'stats', 'banners'], true))
            <input data-f="title" value="{{ $sec['title'] }}" class="form-control form-control-sm" style="max-width:240px" placeholder="Title (optional)">
        @endunless
        @if ($hasLimit)
            <span class="text-muted">show</span><input type="number" data-f="limit" value="{{ $sec['limit'] }}" min="1" max="24" class="form-control form-control-sm" style="width:70px">
        @endif
        @if ($repeat)<button type="button" class="btn btn-sm btn-light text-danger ms-auto rm"><i class="fa-solid fa-trash"></i></button>@endif
    </div>
    @if ($sec['type'] === 'text')
        <textarea data-f="content" rows="4" class="form-control form-control-sm mt-2 font-monospace" placeholder="HTML বা সাধারণ লেখা (script কাজ করবে না)">{{ $sec['content'] }}</textarea>
    @elseif ($sec['type'] === 'image_banner')
        <div class="d-flex gap-2 mt-2 align-items-center flex-wrap">
            @if ($sec['image'])<img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($sec['image']) }}" alt="" style="height:48px;border-radius:6px">@endif
            <input type="file" data-f="image_file" accept="image/*" class="form-control form-control-sm" style="max-width:260px">
            <input data-f="link" value="{{ $sec['link'] }}" class="form-control form-control-sm" style="max-width:320px" placeholder="Link (https://... or /{{ \App\Support\Tenancy::branch()->slug }}/products)">
        </div>
    @endif
</div>
