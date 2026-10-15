@if ($sec['content'] || $sec['title'])
<section class="section pt-0">
    <div class="container">
        @if ($sec['title'])<h2 class="section-title mb-3">{{ $sec['title'] }}</h2>@endif
        <div class="bg-white rounded p-3 page-content">{!! $sec['content'] !!}</div>
    </div>
</section>
@endif
