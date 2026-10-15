@extends('layouts.manager')
@section('title', 'Website — Pages')

@section('content')
@include('manager.website._nav')
<div class="row g-4">
    <div class="col-xl-8">
        <div class="panel">
            <div class="panel-head">Pages @perm('website.edit')<a href="{{ route('manager.pages.create') }}" class="btn btn-sm btn-brand"><i class="fa-solid fa-plus"></i> New page</a>@endperm</div>
            <div class="table-responsive"><table class="table table-hover mb-0 small align-middle">
                <thead class="table-light"><tr><th>Title</th><th>URL</th><th>Header</th><th>Footer</th><th>Status</th><th></th></tr></thead>
                @forelse ($pages as $p)
                    <tr>
                        <td><strong>{{ $p->title }}</strong></td>
                        <td><a href="{{ route('store.page', [$b, $p->slug]) }}" target="_blank">/page/{{ $p->slug }}</a></td>
                        <td>{!! $p->show_in_header ? '<i class="fa-solid fa-check text-success"></i>' : '' !!}</td>
                        <td>{!! $p->show_in_footer ? '<i class="fa-solid fa-check text-success"></i>' : '' !!}</td>
                        <td><span class="badge bg-{{ $p->is_published ? 'success' : 'secondary' }}">{{ $p->is_published ? 'Published' : 'Hidden' }}</span></td>
                        <td class="text-end">@perm('website.edit')<a href="{{ route('manager.pages.edit', $p) }}" class="btn btn-sm btn-light"><i class="fa-solid fa-pen"></i></a>@endperm</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-5">এখনো কোনো পেজ নেই — ডানের টেমপ্লেট থেকে এক ক্লিকে তৈরি করুন।</td></tr>
                @endforelse
            </table></div>
        </div>
    </div>
    @perm('website.edit')
    <div class="col-xl-4">
        <div class="panel"><div class="panel-head">Quick templates</div>
            <div class="list-group list-group-flush small">
                @foreach (\App\Models\Page::TEMPLATES as $slug => [$title])
                    @php $exists = $pages->contains('slug', $slug); @endphp
                    <a href="{{ $exists ? '#' : route('manager.pages.create', ['template' => $slug]) }}" class="list-group-item list-group-item-action d-flex justify-content-between {{ $exists ? 'disabled text-muted' : '' }}">{{ $title }} <span>{!! $exists ? '<i class="fa-solid fa-check"></i>' : '<i class="fa-solid fa-plus"></i>' !!}</span></a>
                @endforeach
            </div>
        </div>
    </div>
    @endperm
</div>
@endsection
