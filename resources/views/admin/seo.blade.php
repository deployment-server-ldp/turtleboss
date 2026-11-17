@extends('admin.layouts.admin')
@section('title', 'SEO Management')
@section('content')
<div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
    @foreach($missing as $l => $n)<div class="card p-4"><p class="text-xs text-muted">{{ $l }} without meta description</p><p class="text-2xl font-bold {{ $n ? 'text-amber-600' : 'text-green-700' }}">{{ $n }}</p></div>@endforeach
    <div class="card p-4"><p class="text-xs text-muted">Product images without ALT</p><p class="text-2xl font-bold {{ $noAlt ? 'text-amber-600' : 'text-green-700' }}">{{ $noAlt }}</p></div>
</div>
<div class="mt-6 grid gap-4 md:grid-cols-2 lg:grid-cols-3">
    <a href="{{ route('admin.settings.show', 'seo') }}" class="card p-5 hover:border-navy"><p class="font-semibold">Global SEO</p><p class="text-sm text-muted">Site title, default description, share image, robots.txt, Google Analytics & Search Console.</p></a>
    <a href="{{ route('admin.redirects.index') }}" class="card p-5 hover:border-navy"><p class="font-semibold">Redirects ({{ $redirects }})</p><p class="text-sm text-muted">301 / 302 redirects for moved or removed URLs. Applied automatically on 404s.</p></a>
    <div class="card p-5"><p class="font-semibold">XML sitemap</p><p class="text-sm text-muted"><a href="/sitemap.xml" target="_blank" class="text-brand underline">/sitemap.xml</a> · <a href="/robots.txt" target="_blank" class="text-brand underline">/robots.txt</a></p><form method="post" action="{{ route('admin.seo.sitemap') }}" class="mt-3">@csrf<button class="btn btn-outline btn-sm">Regenerate sitemap</button></form></div>
</div>
<div class="card mt-6 p-5 text-sm text-muted"><p class="mb-1 font-semibold text-navy">Per-page SEO</p>Product, category, page and blog editors each have an “SEO settings” panel (title, description, focus keyword, canonical, Open Graph, noindex). Structured data (Product, BreadcrumbList, Organization, WebSite, BlogPosting), canonical tags, Open Graph and Twitter cards are emitted automatically — one set per page. {{ $noindex }} product(s) are currently set to noindex.</div>
@endsection
