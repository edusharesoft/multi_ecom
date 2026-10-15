@extends('layouts.manager')
@section('title', 'Blog')

@section('content')
<div class="row g-4">
    <div class="col-xl-9">
        <div class="panel">
            <div class="panel-head flex-wrap gap-2">
                <form class="d-flex gap-2" method="GET">
                    <input name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Search title" style="width:200px">
                    <select name="status" class="form-select form-select-sm" style="width:130px"><option value="">All</option><option value="published" @selected(request('status') === 'published')>Published</option><option value="draft" @selected(request('status') === 'draft')>Draft</option></select>
                    <button class="btn btn-sm btn-dark">Filter</button>
                </form>
                <div class="d-flex gap-2">
                    <a href="{{ route('store.blog', \App\Support\Tenancy::branch()) }}" target="_blank" class="btn btn-sm btn-light border">View blog</a>
                    @perm('blog.add')<a href="{{ route('manager.blog.create') }}" class="btn btn-sm btn-brand"><i class="fa-solid fa-plus"></i> New post</a>@endperm
                </div>
            </div>
            <div class="table-responsive"><table class="table table-hover mb-0 small align-middle">
                <thead class="table-light"><tr><th></th><th>Title</th><th>Category</th><th>Status</th><th class="text-end">Views</th><th>Date</th></tr></thead>
                @forelse ($posts as $p)
                    <tr onclick="location='{{ route('manager.blog.edit', $p) }}'" style="cursor:pointer">
                        <td style="width:70px">@if($p->cover_url)<img src="{{ $p->cover_url }}" alt="" style="width:60px;height:40px;object-fit:cover;border-radius:4px">@endif</td>
                        <td><strong>{{ $p->title }}</strong><br><span class="text-muted">{{ \Illuminate\Support\Str::limit($p->summary(), 80) }}</span></td>
                        <td>{{ $p->category?->name ?? '—' }}</td>
                        <td><span class="badge bg-{{ $p->isLive() ? 'success' : ($p->status === 'published' ? 'info' : 'secondary') }}">{{ $p->isLive() ? 'Published' : ($p->status === 'published' ? 'Scheduled' : 'Draft') }}</span></td>
                        <td class="text-end">{{ number_format($p->views) }}</td>
                        <td>{{ ($p->published_at ?? $p->created_at)->format('d M Y') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-5">প্রথম ব্লগ পোস্ট লিখুন — স্টাইল টিপস, পণ্য গাইড, অফারের খবর। Google থেকে বিনামূল্যে ভিজিটর আনে।</td></tr>
                @endforelse
            </table></div>
            <div class="panel-body">{{ $posts->links() }}</div>
        </div>
    </div>
    <div class="col-xl-3">
        <div class="panel"><div class="panel-head">Categories</div>
            <ul class="list-group list-group-flush small">
                @foreach ($categories as $c)
                    <li class="list-group-item d-flex justify-content-between align-items-center">{{ $c->name }} <span class="d-flex gap-2 align-items-center"><span class="text-muted">{{ $c->posts_count }}</span>
                        @perm('blog.delete')<form method="POST" action="{{ route('manager.blog.categories.destroy', $c) }}" onsubmit="return confirm('Delete category?')">@csrf @method('DELETE')<button class="btn btn-sm btn-link text-danger p-0"><i class="fa-solid fa-xmark"></i></button></form>@endperm</span></li>
                @endforeach
            </ul>
            @perm('blog.edit')
            <form method="POST" action="{{ route('manager.blog.categories.store') }}" class="panel-body d-flex gap-1">@csrf<input name="name" class="form-control form-control-sm" placeholder="New category" required><button class="btn btn-sm btn-dark">Add</button></form>
            @endperm
        </div>
    </div>
</div>
@endsection
