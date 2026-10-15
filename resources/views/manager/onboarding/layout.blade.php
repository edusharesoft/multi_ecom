<!doctype html>
<html lang="bn">
<head>
    @include('partials.head')
    <title>@yield('title') — {{ \App\Models\Setting::siteName() }}</title>
</head>
<body style="background:linear-gradient(180deg,#eef4ff,#f8fbff);min-height:100vh">
<div class="container py-4" style="max-width:1200px">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="fw-bold text-brand fs-5"><i class="fa-solid fa-shop"></i> {{ \App\Models\Setting::siteName() }}</div>
        <form method="POST" action="{{ route('logout') }}">@csrf<button class="btn btn-sm btn-link text-muted">Logout</button></form>
    </div>
    @include('manager.onboarding._steps')
    @include('partials.flash')
    @if ($errors->any())
        <div class="alert alert-danger small"><ul class="mb-0">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif
    @yield('content')
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@stack('scripts')
</body>
</html>
