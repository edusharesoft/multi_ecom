{{--
    Phase 11 — boxed sidebar menu with colourful icons and collapsible groups.
    $items: list of
      ['sec' => 'Heading']                                           section title
      ['label', 'icon', 'color', 'url', 'active', 'badge'?, 'lock'?, 'blank'?]   link
      ['label', 'icon', 'color', 'children' => [links…]]             group (opens when a child is active)
    Items with 'show' => false are skipped; a group without visible children is skipped.
--}}
@php
    $visible = fn ($i) => ($i['show'] ?? true) !== false;
@endphp
@foreach ($items as $n => $item)
    @continue(! $visible($item))
    @if (isset($item['sec']))
        <div class="sec">{{ $item['sec'] }}</div>
    @elseif (! empty($item['children']))
        @php
            $kids = array_values(array_filter($item['children'], $visible));
            $open = collect($kids)->contains(fn ($k) => ! empty($k['active']));
            $gid = 'pmg'.$n.'_'.substr(md5($item['label']), 0, 6);
        @endphp
        @if ($kids)
            <button type="button" @class(['pm-item', 'open' => $open]) data-bs-toggle="collapse" data-bs-target="#{{ $gid }}" aria-expanded="{{ $open ? 'true' : 'false' }}" title="{{ $item['label'] }}">
                <span class="pm-ic" style="background:{{ $item['color'] }}1f;color:{{ $item['color'] }}"><i class="{{ $item['icon'] }}"></i></span>
                <span class="pm-label">{{ $item['label'] }}</span>
                @if (collect($kids)->sum(fn ($k) => (int) ($k['badge'] ?? 0)))<span class="badge bg-warning text-dark">{{ collect($kids)->sum(fn ($k) => (int) ($k['badge'] ?? 0)) }}</span>@endif
                <i class="fa-solid fa-chevron-right pm-chev"></i>
            </button>
            <div id="{{ $gid }}" @class(['collapse', 'pm-sub', 'show' => $open])>
                @foreach ($kids as $k)
                    <a href="{{ $k['url'] }}" @class(['pm-item', 'active' => ! empty($k['active'])]) @if (! empty($k['blank'])) target="_blank" @endif title="{{ $k['label'] }}">
                        <span class="pm-ic" style="background:{{ $k['color'] ?? $item['color'] }}1f;color:{{ $k['color'] ?? $item['color'] }}"><i class="{{ $k['icon'] }}"></i></span>
                        <span class="pm-label">{{ $k['label'] }}</span>
                        @if (! empty($k['badge']))<span class="badge bg-warning text-dark">{{ $k['badge'] }}</span>@endif
                        @if (! empty($k['lock']))<i class="fa-solid fa-lock small text-muted"></i>@endif
                    </a>
                @endforeach
            </div>
        @endif
    @else
        <a href="{{ $item['url'] }}" @class(['pm-item', 'active' => ! empty($item['active'])]) @if (! empty($item['blank'])) target="_blank" @endif title="{{ $item['label'] }}">
            <span class="pm-ic" style="background:{{ $item['color'] }}1f;color:{{ $item['color'] }}"><i class="{{ $item['icon'] }}"></i></span>
            <span class="pm-label">{{ $item['label'] }}</span>
            @if (! empty($item['badge']))<span class="badge bg-warning text-dark">{{ $item['badge'] }}</span>@endif
            @if (! empty($item['lock']))<i class="fa-solid fa-lock small text-muted"></i>@endif
        </a>
    @endif
@endforeach
