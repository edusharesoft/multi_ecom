@extends('layouts.manager')
@section('title', $product->exists ? 'Edit Product' : 'Add Product')

@section('content')
<form method="POST" action="{{ $product->exists ? route('manager.products.update', $product) : route('manager.products.store') }}" enctype="multipart/form-data">
    @csrf
    @if ($product->exists) @method('PUT') @endif
    <div class="row g-4">
        <div class="col-xl-8">
            <div class="panel mb-4">
                <div class="panel-head">Product Information</div>
                <div class="panel-body row g-3">
                    <div class="col-md-8"><label class="form-label small fw-semibold">Product Name *</label><input name="name" value="{{ old('name', $product->name) }}" class="form-control" required></div>
                    <div class="col-md-4"><label class="form-label small fw-semibold">Product Type</label>
                        <select name="product_type" class="form-select">@foreach (\App\Models\Product::TYPES as $k => $v)<option value="{{ $k }}" @selected(old('product_type', $product->product_type) === $k)>{{ $v }}</option>@endforeach</select></div>
                    <div class="col-md-4"><label class="form-label small fw-semibold">Category</label>
                        <select name="category_id" class="form-select" id="catSel">
                            <option value="">— None —</option>
                            @foreach ($categories as $c)<option value="{{ $c->id }}" @selected(old('category_id', $product->category_id) == $c->id)>{{ $c->name }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-md-4"><label class="form-label small fw-semibold">Subcategory</label>
                        <select name="subcategory_id" class="form-select" id="subSel">
                            <option value="">— None —</option>
                            @foreach ($categories as $c)@foreach ($c->children as $sc)<option value="{{ $sc->id }}" data-parent="{{ $c->id }}" @selected(old('subcategory_id', $product->subcategory_id) == $sc->id)>{{ $sc->name }}</option>@endforeach @endforeach
                        </select>
                    </div>
                    <div class="col-md-4"><label class="form-label small fw-semibold">Brand <a href="{{ route('manager.brands.index') }}" class="small">(manage)</a></label>
                        <select name="brand_id" class="form-select"><option value="">— None —</option>@foreach ($brands as $b)<option value="{{ $b->id }}" @selected(old('brand_id', $product->brand_id) == $b->id)>{{ $b->name }}</option>@endforeach</select></div>
                    <div class="col-md-3"><label class="form-label small fw-semibold">SKU</label><input name="sku" value="{{ old('sku', $product->sku) }}" class="form-control"></div>
                    <div class="col-md-3"><label class="form-label small fw-semibold">Product Code</label><input name="product_code" value="{{ old('product_code', $product->product_code) }}" class="form-control"></div>
                    <div class="col-md-4"><label class="form-label small fw-semibold">Barcode</label>
                        <div class="input-group"><input name="barcode" id="barcode" value="{{ old('barcode', $product->barcode) }}" class="form-control" placeholder="Scan or type">
                            <button type="button" class="btn btn-outline-secondary" onclick="document.getElementById('barcode').value = String(Date.now()).slice(-12)" title="Generate">Gen</button></div></div>
                    <div class="col-md-2"><label class="form-label small fw-semibold">Unit</label>
                        <select name="unit" class="form-select">@foreach (\App\Models\Product::UNITS as $k => $v)<option value="{{ $k }}" @selected(old('unit', $product->unit) === $k)>{{ $v }}</option>@endforeach</select></div>
                    <div class="col-12"><label class="form-label small fw-semibold d-flex justify-content-between">Description
                        @if ($tenant->hasFeature('ai'))<a href="#aiProduct" data-bs-toggle="collapse" class="small text-decoration-none">✨ AI দিয়ে লিখুন</a>@endif</label>
                        @if ($tenant->hasFeature('ai'))
                        <div class="collapse mb-2" id="aiProduct"><div class="border rounded p-2 bg-light small">
                            <textarea id="aiFeatures" rows="2" class="form-control form-control-sm mb-2" placeholder="মূল তথ্য দিন (AI শুধু এগুলো ব্যবহার করবে): যেমন ১০০% কটন, সাইজ M-XXL, ঈদ কালেকশন, হাতে সেলাই"></textarea>
                            <div class="d-flex gap-2 flex-wrap">
                                <select id="aiLang" class="form-select form-select-sm" style="width:auto"><option value="bn">বাংলা</option><option value="en">English</option></select>
                                <select id="aiTone" class="form-select form-select-sm" style="width:auto"><option value="friendly">Friendly</option><option value="premium">Premium</option><option value="simple">Simple</option><option value="energetic">Energetic</option></select>
                                <button type="button" id="aiProductBtn" class="btn btn-sm btn-dark">✨ Generate description + SEO</button>
                            </div>
                            <div class="text-muted mt-1">নাম, ক্যাটাগরি ও দাম আগে দিন। ফলাফল নিচের Description ও SEO ঘরে বসবে — সেভ করার আগে পড়ে ঠিক করে নিন।</div>
                        </div></div>
                        @endif
                        <textarea name="description" rows="5" class="form-control">{{ old('description', $product->description) }}</textarea></div>
                    <div class="col-12"><label class="form-label small fw-semibold">Video URL <small class="text-muted">(YouTube)</small></label><input name="video_url" value="{{ old('video_url', $product->video_url) }}" class="form-control" placeholder="https://youtube.com/watch?v=..."></div>
                </div>
            </div>

            <div class="panel mb-4">
                <div class="panel-head">Price, Tax & Stock</div>
                <div class="panel-body row g-3">
                    <div class="col-md-3"><label class="form-label small fw-semibold">Purchase Price (৳)</label><input type="number" step="0.01" min="0" name="purchase_price" value="{{ old('purchase_price', $product->purchase_price) }}" class="form-control"></div>
                    <div class="col-md-3"><label class="form-label small fw-semibold">Sale Price (৳) *</label><input type="number" step="0.01" min="0" name="price" value="{{ old('price', $product->price) }}" class="form-control" required></div>
                    <div class="col-md-3"><label class="form-label small fw-semibold">Old / MRP (৳)</label><input type="number" step="0.01" min="0" name="old_price" value="{{ old('old_price', $product->old_price) }}" class="form-control" placeholder="struck-through"></div>
                    <div class="col-md-3"><label class="form-label small fw-semibold">Tax / VAT %</label><input type="number" step="0.01" min="0" name="tax_percent" value="{{ old('tax_percent', $product->tax_percent) }}" class="form-control" placeholder="0 = store tax"></div>
                    <div class="col-md-4"><label class="form-label small fw-semibold">Discount / Flash Price (৳)</label><input type="number" step="0.01" min="0" name="sale_price" value="{{ old('sale_price', $product->sale_price) }}" class="form-control"></div>
                    <div class="col-md-4"><label class="form-label small fw-semibold">Discount starts</label><input type="datetime-local" name="sale_starts_at" value="{{ old('sale_starts_at', $product->sale_starts_at?->format('Y-m-d\TH:i')) }}" class="form-control"></div>
                    <div class="col-md-4"><label class="form-label small fw-semibold">Ends <small class="text-muted">(set = Flash Sale)</small></label><input type="datetime-local" name="sale_ends_at" value="{{ old('sale_ends_at', $product->sale_ends_at?->format('Y-m-d\TH:i')) }}" class="form-control"></div>
                    <div class="col-md-4"><label class="form-label small fw-semibold">Stock <small class="text-muted">(ভ্যারিয়েন্ট না থাকলে)</small></label><input type="number" min="0" name="stock" value="{{ old('stock', $product->stock ?? 0) }}" class="form-control"></div>
                    <div class="col-md-4"><label class="form-label small fw-semibold">Minimum Stock (alert)</label><input type="number" min="0" name="min_stock" value="{{ old('min_stock', $product->min_stock ?? 5) }}" class="form-control"></div>
                    @if ($product->purchase_price && $product->price)
                        <div class="col-md-4 d-flex align-items-end small text-muted">Margin: ৳ {{ number_format($product->price - $product->purchase_price, 2) }} ({{ round(($product->price - $product->purchase_price) / max(0.01, $product->price) * 100) }}%)</div>
                    @endif
                </div>
            </div>

            <div class="panel mb-4">
                <div class="panel-head">Specification <button type="button" class="btn btn-sm btn-outline-dark" id="addSpec"><i class="fa-solid fa-plus"></i> Add row</button></div>
                <div class="panel-body" id="specs">
                    @foreach (old('specs', $product->specifications ?? []) as $i => $spec)
                        <div class="row g-2 mb-2 spec"><div class="col-5"><input name="specs[{{ $i }}][key]" value="{{ $spec['key'] ?? '' }}" class="form-control form-control-sm" placeholder="e.g. Fabric"></div><div class="col-6"><input name="specs[{{ $i }}][value]" value="{{ $spec['value'] ?? '' }}" class="form-control form-control-sm" placeholder="e.g. Cotton"></div><div class="col-1"><button type="button" class="btn btn-sm btn-light text-danger rm">×</button></div></div>
                    @endforeach
                </div>
            </div>

            <div class="panel">
                <div class="panel-head">Variants (Size / Color) <button type="button" class="btn btn-sm btn-outline-dark" id="addVariant"><i class="fa-solid fa-plus"></i> Add Variant</button></div>
                <div class="panel-body">
                    <p class="small text-muted">যেমন: <code>Ladies Tops S: 34-36 - Maroon</code>। দাম খালি রাখলে মূল দাম প্রযোজ্য। ভ্যারিয়েন্ট থাকলে স্টক ভ্যারিয়েন্ট অনুযায়ী হিসাব হবে।</p>
                    <div id="variants">
                        @php($variants = old('variants', $product->variants?->map->only(['id', 'name', 'price', 'stock'])->all() ?? []))
                        @foreach ($variants as $i => $v)
                            <div class="row g-2 mb-2 variant">
                                <input type="hidden" name="variants[{{ $i }}][id]" value="{{ $v['id'] ?? '' }}">
                                <div class="col-6"><input name="variants[{{ $i }}][name]" value="{{ $v['name'] ?? '' }}" class="form-control form-control-sm" placeholder="Variant name"></div>
                                <div class="col-2"><input type="number" step="0.01" name="variants[{{ $i }}][price]" value="{{ $v['price'] ?? '' }}" class="form-control form-control-sm" placeholder="Price"></div>
                                <div class="col-2"><input type="number" name="variants[{{ $i }}][stock]" value="{{ $v['stock'] ?? 0 }}" class="form-control form-control-sm" placeholder="Stock"></div>
                                <div class="col-2"><button type="button" class="btn btn-sm btn-light text-danger w-100 rm"><i class="fa-solid fa-xmark"></i></button></div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="panel mb-4">
                <div class="panel-head">Publish</div>
                <div class="panel-body">
                    <div class="form-check form-switch mb-2"><input class="form-check-input" type="checkbox" name="is_active" value="1" id="act" @checked(old('is_active', $product->is_active))><label class="form-check-label small" for="act">Active (visible in store)</label></div>
                    <div class="form-check form-switch mb-3"><input class="form-check-input" type="checkbox" name="is_featured" value="1" id="feat" @checked(old('is_featured', $product->is_featured))><label class="form-check-label small" for="feat">Featured on home page</label></div>
                    <button class="btn btn-brand w-100">{{ $product->exists ? 'Update Product' : 'Save Product' }}</button>
                    <a href="{{ route('manager.products.index') }}" class="btn btn-link w-100 small">Cancel</a>
                </div>
            </div>
            <div class="panel mb-4">
                <div class="panel-head">SEO</div>
                <div class="panel-body">
                    <input name="meta_title" value="{{ old('meta_title', $product->meta_title) }}" class="form-control form-control-sm mb-2" placeholder="SEO title (default: product name)" maxlength="255">
                    <textarea name="meta_description" rows="3" class="form-control form-control-sm mb-2" placeholder="Meta description (155 chars)" maxlength="500">{{ old('meta_description', $product->meta_description) }}</textarea>
                    <input name="meta_keywords" value="{{ old('meta_keywords', $product->meta_keywords) }}" class="form-control form-control-sm" placeholder="keywords, comma separated">
                </div>
            </div>
            @if ($product->exists && $product->barcode)
                <div class="panel mb-4">
                    <div class="panel-head">Barcode & QR <a href="{{ route('manager.products.labels', ['ids' => $product->id]) }}" target="_blank" class="small">Print labels</a></div>
                    <div class="panel-body text-center"><svg id="bcPreview"></svg><div id="qrPreview" class="d-inline-block mt-2"></div></div>
                </div>
            @endif
            <div class="panel">
                <div class="panel-head">Images</div>
                <div class="panel-body">
                    <label class="form-label small fw-semibold">Main Image</label>
                    <img src="{{ $product->image_url }}" class="d-block rounded mb-2" style="width:120px;height:150px;object-fit:cover">
                    <input type="file" name="image" accept="image/*" class="form-control form-control-sm mb-3">
                    <label class="form-label small fw-semibold">Gallery (max 8)</label>
                    <input type="file" name="gallery[]" accept="image/*" multiple class="form-control form-control-sm">
                    @if ($product->exists && $product->images->count())
                        <div class="d-flex flex-wrap gap-2 mt-3">
                            @foreach ($product->images as $img)
                                <div class="position-relative">
                                    <img src="{{ $img->url }}" class="thumb-sm">
                                    <button type="submit" form="del-img-{{ $img->id }}" class="btn btn-danger btn-sm position-absolute top-0 end-0 p-0 px-1" style="font-size:10px">×</button>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</form>

@if ($product->exists)
    @foreach ($product->images as $img)
        <form method="POST" action="{{ route('manager.products.images.destroy', $img) }}" id="del-img-{{ $img->id }}" onsubmit="return confirm('Delete image?')">@csrf @method('DELETE')</form>
    @endforeach
@endif
@endsection

@include('manager.ai._js')
@push('scripts')
<script>
document.getElementById('aiProductBtn')?.addEventListener('click', async function () {
    const f = this.closest('form'), v = n => f.querySelector(`[name="${n}"]`)?.value || '';
    if (!v('name')) { alert('আগে Product Name দিন।'); return; }
    const d = await mbAi(@json(route('manager.ai.product')), {name: v('name'), category_id: v('category_id') || null, brand_id: v('brand_id') || null, price: v('price') || null,
        features: document.getElementById('aiFeatures').value, lang: document.getElementById('aiLang').value, tone: document.getElementById('aiTone').value}, this);
    if (!d) return;
    const set = (n, val) => { const el = f.querySelector(`[name="${n}"]`); if (el && val) el.value = val; };
    set('description', d.description); set('meta_title', d.meta_title); set('meta_description', d.meta_description); set('meta_keywords', d.keywords);
    if (d.remaining !== null && d.remaining !== undefined) this.title = 'বাকি AI requests: ' + d.remaining;
});
</script>
@endpush
@push('scripts')
<script>
(() => {
    const box = document.getElementById('variants');
    let i = {{ count($variants) + 100 }};
    document.getElementById('addVariant').addEventListener('click', () => {
        i++;
        box.insertAdjacentHTML('beforeend', `
        <div class="row g-2 mb-2 variant">
            <div class="col-6"><input name="variants[${i}][name]" class="form-control form-control-sm" placeholder="Variant name"></div>
            <div class="col-2"><input type="number" step="0.01" name="variants[${i}][price]" class="form-control form-control-sm" placeholder="Price"></div>
            <div class="col-2"><input type="number" name="variants[${i}][stock]" value="0" class="form-control form-control-sm" placeholder="Stock"></div>
            <div class="col-2"><button type="button" class="btn btn-sm btn-light text-danger w-100 rm"><i class="fa-solid fa-xmark"></i></button></div>
        </div>`);
    });
    box.addEventListener('click', e => { const b = e.target.closest('.rm'); if (b) b.closest('.variant').remove(); });

    // Specifications
    const specs = document.getElementById('specs');
    let si = 1000;
    document.getElementById('addSpec').addEventListener('click', () => {
        si++;
        specs.insertAdjacentHTML('beforeend', `<div class="row g-2 mb-2 spec"><div class="col-5"><input name="specs[${si}][key]" class="form-control form-control-sm" placeholder="e.g. Fabric"></div><div class="col-6"><input name="specs[${si}][value]" class="form-control form-control-sm" placeholder="e.g. Cotton"></div><div class="col-1"><button type="button" class="btn btn-sm btn-light text-danger rm">×</button></div></div>`);
    });
    specs.addEventListener('click', e => { const b = e.target.closest('.rm'); if (b) b.closest('.spec').remove(); });

    // Subcategory list follows category
    const cat = document.getElementById('catSel'), sub = document.getElementById('subSel');
    const filterSubs = () => sub.querySelectorAll('option[data-parent]').forEach(o => o.hidden = cat.value && o.dataset.parent !== cat.value);
    cat.addEventListener('change', () => { filterSubs(); if (sub.selectedOptions[0]?.hidden) sub.value = ''; });
    filterSubs();
})();
</script>
@if ($product->exists && $product->barcode)
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
JsBarcode('#bcPreview', @json($product->barcode), {height: 50, fontSize: 13, margin: 4});
new QRCode(document.getElementById('qrPreview'), {text: @json($product->url), width: 96, height: 96});
</script>
@endif
@endpush
